<div class="container-fluid">
  @session('mpd')
  <h4>SpecManager state</h4>
  <pre>{{ $this->specManagerState() }}</pre>
    <h4>Resolved Manifest</h4>
    <pre>{{ $this->logs() }}</pre>


  <div class="accordion" id="specAccordion">
  @foreach ($this->getSpecs() as $spec)
    <div class="accordion-item">
    <h2 class="accordion-header">
      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $this->slugify($spec) }}" aria-expanded="true" aria-controls="collapseOne">
        {{ $spec }}
      </button>
    </h2>
    <div id="{{ $this->slugify($spec) }}" class="accordion-collapse collapse" data-bs-parent="#specAccordion">
      <div class="accordion-body">
          @foreach ($this->getSections($spec) as $section)
            <h4>{{ $section }}</h4>
          @endforeach
      </div>
    </div>
  </div>

  @endforeach
  </div>



  @endsession

{{ $this->logs() }}
</div>
