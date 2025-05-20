<div>
  @session('mpd')
    @foreach ($this->segmentUrls() as $url)
      <p>Segment: <span>{{ $url }}</span></p>
    @endforeach

    <p>Resolved: <span>{{ $this->getManifestChecks() }}</span></p>

  @endsession

{{ $this->logs() }}
</div>
