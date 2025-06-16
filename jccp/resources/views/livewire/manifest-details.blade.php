<div>
  @session('mpd')
    <h4>Resolved Manifest</h4>
    <pre>{{ $this->logs() }}</pre>

  <h4>Features</h4>
  <pre>
    {{ var_dump($this->getFeatures()) }}
  </pre>


  @endsession

{{ $this->logs() }}
</div>
