<div class="container">
  @if ($this->show())
  <livewire:segment-queue />

  <div class="row">
    <div class="col-4">
      <livewire:spec-manager/>
    </div>
    <span class="col-1"></span>

    <div class="col-7">
      <livewire:results-table class="col-6"/>
    </div>
  </div>


  <div class="row bg-body-secondary mt-4 " style="padding: 10px">
  <h4 class="text-center mb-2">Detailed results</h4>
      <div class="accordion accordion-flush" id="resultsAccordion">
        @foreach ($this->sections as $section)
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed"
                      type="button"
                      data-bs-toggle="collapse"
                      data-bs-target="#collapse{{$section}}"
                      aria-expanded="false"
                      aria-controls="collapse{{$section}}">
                {{ $section }} checks
              </button>
            </h2>
            <div id="collapse{{$section}}"
                 class="accordion-collapse collapse"
                 data-bs-parent="#resultsAccordion">
              <div class="accordion-body">
                <livewire:mpd-results :section="$section" />
              </div>
            </div>
          </div>
        @endforeach
      </div>
  </div>
  @endif
</div>
