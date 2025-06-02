<div>
  @session('mpd')
  <h4>Features</h4>
  <pre>
    {{ var_dump($this->getFeatures()) }}
  </pre>

    <h4>Resolved Manifest</h4>
    <pre>{{ $this->getManifestChecks() }}</pre>

  @endsession

{{ $this->logs() }}
</div>
