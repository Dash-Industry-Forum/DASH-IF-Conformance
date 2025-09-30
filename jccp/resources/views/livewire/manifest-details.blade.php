<div class="container">
  @session('mpd')
  <h4>SpecManager state</h4>
  <pre>{{ $this->specManagerState() }}</pre>

  <div class="bg-body-secondary" style="padding: 10px">
      <div class="accordion accordion-flush" id="resultsAccordion">
        @foreach ($this->sections as $section)
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button {{ $loop->index > 0 ? "collapsed" : "" }}"
                      type="button"
                      data-bs-toggle="collapse"
                      data-bs-target="#collapse{{$section}}"
                      aria-expanded="{{ $loop->index > 0 ? "false" : "true" }}"
                      aria-controls="collapse{{$section}}">
                {{ $section }} checks
              </button>
            </h2>
            <div id="collapse{{$section}}"
                 class="accordion-collapse collapse {{ $loop->index > 0 ? "" : "show" }}"
                 data-bs-parent="#resultsAccordion">
              <div class="accordion-body">
                <livewire:mpd-results :section="$section" />
              </div>
            </div>
          </div>
        @endforeach
      </div>
  </div>
  @endsession
</div>
