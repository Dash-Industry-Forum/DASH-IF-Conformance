// GDPR Consent Management

class GDPRConsentManager {
  constructor() {
    this.consentKey = 'dashIfConformanceConsent';
    this.banner = null;
    this.initialized = false;
  }

  init() {
    if (this.initialized) return;
    
    // Create banner if it doesn't exist
    if (!document.getElementById('gdpr-consent-banner')) {
      this.createBanner();
    } else {
      this.banner = document.getElementById('gdpr-consent-banner');
    }
    
    // Add event listeners
    document.getElementById('gdpr-accept').addEventListener('click', () => this.acceptConsent());
    document.getElementById('gdpr-reject').addEventListener('click', () => this.rejectConsent());
    
    // Check existing consent
    if (this.hasConsentStatus() === null) {
      this.showBanner();
    } else if (this.hasConsentStatus() === false) {
      // User previously declined - block process functionality
      this.disableProcessFeature();
    }
    
    // Add consent notices
    this.addConsentNotices();
    
    // Intercept process button clicks
    this.interceptProcessButton();
    
    this.initialized = true;
  }
  
  createBanner() {
    const banner = document.createElement('div');
    banner.id = 'gdpr-consent-banner';
    banner.innerHTML = `
      <div class="gdpr-content">
        <p>This site processes uploaded files on our servers for validation purposes. 
           By using this service, you consent to our 
           <a href="javascript:void(0)" onclick="return showTerms()">Terms and Privacy Policy</a>.</p>
        <div class="gdpr-buttons">
          <button id="gdpr-accept" class="btn btn-primary">Accept</button>
          <button id="gdpr-reject" class="btn btn-secondary">Decline</button>
        </div>
      </div>
    `;
    document.body.appendChild(banner);
    this.banner = banner;
  }
  
  showBanner() {
    this.banner.style.display = 'block';
  }
  
  hideBanner() {
    this.banner.style.display = 'none';
  }
  
  acceptConsent() {
    localStorage.setItem(this.consentKey, 'true');
    this.hideBanner();
    this.enableProcessFeature();
  }
  
  rejectConsent() {
    // Store the rejection explicitly
    localStorage.setItem(this.consentKey, 'false');
    alert('This service requires consent to process files. The process feature will be disabled.');
    this.hideBanner();
    this.disableProcessFeature();
  }
  
  hasConsentStatus() {
    const status = localStorage.getItem(this.consentKey);
    if (status === null) return null;
    return status === 'true';
  }
  
  addConsentNotices() {
    // Find validator component
    const validatorContainer = document.querySelector('.validator-component') || 
                             document.querySelector('.tool-container') || 
                             document.querySelector('form');
    
    if (validatorContainer) {
      const notice = document.createElement('div');
      notice.className = 'upload-consent-notice';
      notice.innerHTML = '<p>By uploading files for validation, you consent to processing your content on our servers.</p>';
      
      // Insert at beginning of validator or form
      validatorContainer.insertAdjacentElement('afterbegin', notice);
    }
  }
  
  interceptProcessButton() {
    const self = this;
    
    // Event delegation for all clicks - this is the most efficient approach
    document.addEventListener('click', function(event) {
      // Check for clicks on Process button or its children
      let targetButton = null;
      
      // Case 1: Direct click on a button
      if (event.target.tagName === 'BUTTON') {
        targetButton = event.target;
      } 
      // Case 2: Click on a span inside a button (this is how validator.js implements it)
      else if (event.target.tagName === 'SPAN' && 
               event.target.textContent === 'Process' && 
               event.target.parentElement && 
               event.target.parentElement.tagName === 'BUTTON') {
        targetButton = event.target.parentElement;
      }
      // Case 3: Click on any element inside a button
      else if (event.target.closest('button')) {
        targetButton = event.target.closest('button');
      }
      
      // If we identified a button, check if it's a process button
      if (targetButton) {
        // Check if this is a process button
        const isProcessButton = 
          targetButton.textContent.includes('Process') || 
          Array.from(targetButton.querySelectorAll('span'))
            .some(span => span.textContent === 'Process');
        
        if (isProcessButton) {
          // Handle consent check
          if (self.hasConsentStatus() === false) {
            event.preventDefault();
            event.stopPropagation();
            self.showConsentError(targetButton);
            return false;
          } else if (self.hasConsentStatus() === null) {
            event.preventDefault();
            event.stopPropagation();
            self.showBanner();
            alert('Please accept the terms before processing files.');
            return false;
          }
        }
      }
    }, true);
    
    // mark and style already existing Process buttons on page load
    this.findAndStyleProcessButtons();
    
    // Set up a MutationObserver to watch for dynamically added buttons
    const observer = new MutationObserver(() => {
      this.findAndStyleProcessButtons();
    });
    
    // Start observing
    observer.observe(document.body, { 
      childList: true, 
      subtree: true 
    });
  }

  findAndStyleProcessButtons() {
    // This method now only adds styling to Process buttons, not event handlers
    // since event handlers are handled by event delegation
    const allButtons = document.querySelectorAll('button');
    
    allButtons.forEach(button => {
      // Skip already processed buttons
      if (button.hasAttribute('data-gdpr-processed')) return;
      
      // Check if this is a process button
      const processSpan = Array.from(button.querySelectorAll('span'))
        .find(span => span.textContent === 'Process');
      
      if (processSpan) {
        // Mark as processed
        button.setAttribute('data-gdpr-processed', 'true');
        
        // If consent is declined, add visual indicator
        if (this.hasConsentStatus() === false) {
          button.classList.add('consent-disabled');
          button.title = "Consent required to process files";
          
          if (!button.querySelector('.consent-indicator')) {
            const indicator = document.createElement('span');
            indicator.className = 'consent-indicator';
            indicator.innerHTML = '🔒';
            indicator.title = "Consent required to process files";
            button.appendChild(indicator);
          }
        }
      }
    });
  }
  
  showConsentError(button) {
    // Add an error tooltip near the button
    const errorMessage = document.createElement('div');
    errorMessage.className = 'consent-error-tooltip';
    errorMessage.innerHTML = `
      File processing requires consent. 
      <a href="#" class="reset-consent">Reset consent preferences</a>
    `;
    
    // Position near button
    const buttonRect = button.getBoundingClientRect();
    errorMessage.style.top = `${buttonRect.bottom + window.scrollY + 5}px`;
    errorMessage.style.left = `${buttonRect.left + window.scrollX}px`;
    
    document.body.appendChild(errorMessage);
    
    // Add reset link handler
    const resetLink = errorMessage.querySelector('.reset-consent');
    resetLink.addEventListener('click', (e) => {
      e.preventDefault();
      this.resetConsent();
      errorMessage.remove();
    });
    
    // Auto-remove after 4 seconds
    setTimeout(() => {
      errorMessage.remove();
    }, 4000);
  }
  
  disableProcessFeature() {
    // Add visual indicators to all process buttons
    const processButtons = document.querySelectorAll('button');
    
    processButtons.forEach(button => {
      const isProcessButton = 
        button.textContent.includes('Process') || 
        Array.from(button.querySelectorAll('span'))
          .some(span => span.textContent === 'Process');
      
      if (isProcessButton) {
        button.classList.add('consent-disabled');
        button.title = "Consent required to process files";
        
        if (!button.querySelector('.consent-indicator')) {
          const indicator = document.createElement('span');
          indicator.className = 'consent-indicator';
          indicator.innerHTML = '🔒';
          indicator.title = "Consent required to process files";
          button.appendChild(indicator);
        }
      }
    });
    
    // Add global notice
    this.addGlobalConsentNotice();
  }
  
  addGlobalConsentNotice() {
    // Remove existing notice if any
    const existingNotice = document.querySelector('.global-consent-notice');
    if (existingNotice) existingNotice.remove();
    
    // Add a global notice at the top of the page
    const notice = document.createElement('div');
    notice.className = 'global-consent-notice';
    
    notice.innerHTML = `
      <p>You declined consent to process files on our servers. 
      File processing is disabled. 
      <a href="#" class="reset-consent">Reset consent preferences</a></p>
    `;
    
    document.body.insertBefore(notice, document.body.firstChild);
    
    // Add reset listener
    notice.querySelector('.reset-consent').addEventListener('click', (e) => {
      e.preventDefault();
      this.resetConsent();
    });
  }
  
  resetConsent() {
    localStorage.removeItem(this.consentKey);
    this.showBanner();
    this.enableProcessFeature();
  }
  
  enableProcessFeature() {
    // Re-enable all process buttons
    const processButtons = document.querySelectorAll('.consent-disabled');
    processButtons.forEach(button => {
      button.classList.remove('consent-disabled');
      button.title = "";
      
      // Remove indicator if present
      const indicator = button.querySelector('.consent-indicator');
      if (indicator) indicator.remove();
    });
    
    // Remove notices
    const globalNotice = document.querySelector('.global-consent-notice');
    if (globalNotice) globalNotice.remove();
  }
}

// Initialize once when the DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initGDPR);
} else {
  initGDPR();
}

// Single initialization function
function initGDPR() {
  if (!window.gdprManager) {
    const gdprManager = new GDPRConsentManager();
    gdprManager.init();
    window.gdprManager = gdprManager;
  }
}

// For testing: Add reset function to clear consent
window.resetGDPRConsent = function() {
  localStorage.removeItem('dashIfConformanceConsent');
  console.log("GDPR consent reset. Refresh page to see banner.");
  if (window.gdprManager) {
    window.gdprManager.showBanner();
    window.gdprManager.enableProcessFeature();
  }
  return "Consent reset. Banner should appear on page refresh.";
};

window.showTerms = function() {
  // Update the hash for URL consistency
  location.hash = "terms";
  // trigger navigation
  if (window.mainView && typeof window.mainView.handleLocationChange === "function") {
    window.mainView.handleLocationChange("terms");
  }
  return false;
};