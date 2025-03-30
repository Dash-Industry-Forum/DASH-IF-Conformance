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
    if (!this.hasConsent()) {
      this.showBanner();
    }
    
    // Add upload consent notices
    this.addUploadConsentNotices();
    
    // Intercept form submissions and file uploads
    this.interceptUploads();
    
    this.initialized = true;
  }
  
  createBanner() {
    const banner = document.createElement('div');
    banner.id = 'gdpr-consent-banner';
    banner.innerHTML = `
      <div class="gdpr-content">
        <p>This site processes uploaded files on our servers for validation purposes. 
           By using this service, you consent to our 
           <a href="/Conformance-Frontend/terms.html" target="_blank">Terms and Privacy Policy</a>.</p>
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
  }
  
  rejectConsent() {
    alert('This service requires consent to process your data. You may not be able to use all features.');
    this.hideBanner();
  }
  
  hasConsent() {
    return localStorage.getItem(this.consentKey) === 'true';
  }
  
  addUploadConsentNotices() {
    // Find validator component
    const validatorContainer = document.querySelector('.validator-component') || 
                             document.querySelector('.tool-container') || 
                             document.querySelector('form');
    
    if (validatorContainer) {
      const notice = document.createElement('div');
      notice.className = 'upload-consent-notice';
      notice.innerHTML = '<p>By uploading files, you consent to processing your content on our servers for validation purposes.</p>';
      
      // Insert at beginning of validator or form
      validatorContainer.insertAdjacentElement('afterbegin', notice);
    }
  }
  
  interceptUploads() {
    // Override fetch to check consent
    const originalFetch = window.fetch;
    const self = this;
    
    window.fetch = function() {
      if (!self.hasConsent()) {
        self.showBanner();
        alert('Please accept the terms before uploading files.');
        return Promise.reject(new Error('Consent required'));
      }
      return originalFetch.apply(this, arguments);
    };
    
    // Also intercept forms
    document.addEventListener('submit', (e) => {
      if (!this.hasConsent()) {
        e.preventDefault();
        this.showBanner();
        alert('Please accept the terms before uploading files.');
      }
    });
  }
}

// Initialize on DOM content loaded
document.addEventListener('DOMContentLoaded', () => {
  const gdprManager = new GDPRConsentManager();
  gdprManager.init();
  
  // Make available globally
  window.gdprManager = gdprManager;
});

// Ensure initialization if page is already loaded
if (document.readyState === 'complete' || document.readyState === 'interactive') {
  setTimeout(() => {
    if (!window.gdprManager) {
      const gdprManager = new GDPRConsentManager();
      gdprManager.init();
      window.gdprManager = gdprManager;
    }
  }, 100);
}

// For testing: Add reset function to clear consent
window.resetGDPRConsent = function() {
  localStorage.removeItem('dashIfConformanceConsent');
  console.log("GDPR consent reset. Refresh page to see banner.");
  if (window.gdprManager) {
    window.gdprManager.showBanner();
  }
  return "Consent reset. Banner should appear on page refresh.";
};