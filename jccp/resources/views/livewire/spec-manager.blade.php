<div>
  <h4>SpecManager state</h4>
  @if ($this->segmentsLoading())
      <div class="row" wire:poll>
  @else
      <div class="row">
  @endif
      <button type="button" disabled class="col-3 btn btn-success">Global Module</button>
      @foreach ($this->mpdSpecs() as $spec)
        <button type="button" {{ $this->isDisabled($spec) ? "disabled" : "" }} class="col-3 btn {{ $this->buttonClassForSpec($spec) }}" wire:click="enable('{{$spec}}')">{{$spec}}</button>
      @endforeach
      @foreach ($this->segmentSpecs() as $spec)
        <button type="button" {{ $this->isDisabled($spec) ? "disabled" : "" }} class="col-3 btn {{ $this->buttonClassForSpec($spec) }}" wire:click="enable('{{$spec}}')">{{$spec}}</button>
      @endforeach
  </div>
</div>
