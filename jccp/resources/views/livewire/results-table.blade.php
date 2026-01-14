<div class="container">
  <div class="row">
    <div class="col-6"></div>
    <div class="col-2 text-center"><strong>MPD</strong></div>
    <div class="col-2 text-center"><strong>Segments</strong></div>
    <div class="col-2 text-center"><strong>CrossValidation</strong></div>
  </div>
  @foreach ($this->table as $key => $tableSection)
  <div class="row">
    <div class="col-6">
      {{$key}}
    </div>
    <div class="col-2 text-center {{ $this->getResultClass($key, 'MPD') }}">
      {{ $this->getResult($key, 'MPD') }}
    </div>
    <div class="col-2 text-center {{ $this->getResultClass($key, 'Segments') }}">
      {{ $this->getResult($key, 'Segments') }}
    </div>
    <div class="col-2 text-center {{ $this->getResultClass($key, 'CrossValidation') }}">
      {{ $this->getResult($key, 'CrossValidation') }}
    </div>
  </div>

  @endforeach
</div>
