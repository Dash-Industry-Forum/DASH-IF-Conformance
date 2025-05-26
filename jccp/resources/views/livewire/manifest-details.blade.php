<div>
  @session('mpd')
    <h4> Parsed segment urls </h4>
    @foreach ($this->segmentUrls() as $periodUrl)
        @foreach ($periodUrl as $adaptationUrl)
            @foreach ($adaptationUrl as $representationUrl)
{{ var_dump($representationUrl) }}
            @endforeach
        @endforeach
    @endforeach

    <h4>Resolved Manifest</h4>
    <pre>{{ $this->getManifestChecks() }}</pre>

  @endsession

{{ $this->logs() }}
</div>
