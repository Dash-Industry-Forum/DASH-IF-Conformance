<div class="container">
  <livewire:spec-manager />
  @session('mpd')

  <div class="bg-body-secondary" style="padding: 10px">
      <div class="accordion accordion-flush" id="resultsAccordion">
        @foreach ($this->sections as $section)
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button {{ $this->isOpenSection($section) ? "" : "collapsed" }}"
                      type="button"
                      data-bs-toggle="collapse"
                      data-bs-target="#collapse{{$section}}"
                      aria-expanded="{{ $this->isOpenSection($section) ? "true" :  "false" }}"
                      aria-controls="collapse{{$section}}">
                {{ $section }} checks
              </button>
            </h2>
            <div id="collapse{{$section}}"
                 class="accordion-collapse collapse {{ $this->isOpenSection($section) ? "show" : "" }}"
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
