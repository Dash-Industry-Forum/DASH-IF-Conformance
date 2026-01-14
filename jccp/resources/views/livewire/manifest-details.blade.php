<div class="container">
  @if ($this->show())
  <livewire:segment-queue />
  <livewire:spec-manager />

  <div class="container">
    <div class="row">
      <div class="col-6"></div>
      <div class="col-2"><strong>Manifest</strong></div>
      <div class="col-2"><strong>Segments</strong></div>
      <div class="col-2"><strong>CrossValidation</strong></div>
    </div>
    @foreach ($this->table as $key => $tableSection)
    <div class="row">
      <div class="col-6">{{$key}}</div>
      <div class="col-2">{{ array_key_exists('MPD', $tableSection) ? $tableSection['MPD'] : '' }}</div>
      <div class="col-2">{{ array_key_exists('Segments', $tableSection) ? $tableSection['Segments'] : '' }}</div>
      <div class="col-2">{{ array_key_exists('CrossValidation', $tableSection) ? $tableSection['CrossValidation'] : '' }}</div>
    </div>

    @endforeach
  </div>

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
  @endif
</div>
