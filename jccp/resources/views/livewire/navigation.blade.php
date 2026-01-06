<nav class="navbar navbar-expand-lg bg-primary" data-bs-theme="dark">
  <div class="container">
    <a class="navbar-brand" href="/">
      <img style="background-color: white;  width: fit-content; height: 3em; border-radius: 0.3em; margin-right: 1em;" src="/images/logo.png">
    </a>
    <div class="navbar-brand">
      <div>Conformance Tool</div>
      <div style="font-size: 0.7em">{{ env('BUILD_VERSION', 'Unkown version') }}</div>
    </div>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a  class="nav-link" href="/" wire:navigate>Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/about" wire:navigate>About</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/statistics" wire:navigate>Statistics</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/faq" wire:navigate>FAQ</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/terms" wire:navigate>Terms & Conditions</a>
        </li>
      </ul>
    </div>
  </div>
</nav>
