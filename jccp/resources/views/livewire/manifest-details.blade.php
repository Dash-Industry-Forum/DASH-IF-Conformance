<div class="container">
  @session('mpd')
  <h4>SpecManager state</h4>
  <pre>{{ $this->specManagerState() }}</pre>
    <h4>Resolved Manifest</h4>

  <div>
  <ul class="nav">
  @foreach ($this->getSpecs() as $spec)
    <li class="nav-item">
      <span class="nav-link" wire:click="selectSpec('{{$spec}}')">
        {{ $spec }}
      </span>
    </li>
  @endforeach
  </ul>
  </div>
  @foreach ($this->transformResults($this->selectedSpec) as $check)
    <div class="row">
      <div class="col-1">{{ $check['state'] }}</div>
      <div class="col-2">{{ $check['section'] }}</div>
      <div class="col-5">{{ $check['check'] }}</div>
      <div class="col-4">
        @foreach ($check['messages'] as $msg)
            <div>{{ $msg }}</div>
        @endforeach
      </div>
    </div>
  @endforeach



  @endsession

    <!--pre>{{ $this->getFeatures() }}</pre-->
</div>
