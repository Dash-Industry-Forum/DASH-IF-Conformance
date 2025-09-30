<div class="container">
  @session('mpd')
  <h4>SpecManager state</h4>
  <pre>{{ $this->specManagerState() }}</pre>

  <livewire:mpd-results section="MPD" />

  <livewire:mpd-results section="Segments" />

  @endsession
</div>
