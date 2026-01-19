<div>
  <h4 class="text-center">Enabled specifications</h4>
  @if ($this->segmentsLoading())
      <div class="row" wire:poll>
  @else
      <div class="row">
  @endif
      <button type="button" disabled class="col-12 btn btn-success">Global Module</button>
      <h5 class="col-12 text-center">Manifest</h5>
      @foreach ($this->mpdSpecs() as $spec)
        <button type="button" class="col-6 btn {{ $this->buttonClassForSpec($spec, 'MPD') }}" wire:click="enable('{{$spec}}', 'MPD')">{{$spec}}</button>
      @endforeach
      <h5 class="col-12 text-center">Segments</h5>
      @foreach ($this->segmentSpecs() as $spec)
        <button type="button" {{ $this->isDisabled($spec) ? "disabled" : "" }} class="col-6 btn {{ $this->buttonClassForSpec($spec, 'Segments') }}" wire:click="enable('{{$spec}}', 'Segments')">{{$spec}}</button>
      @endforeach
  </div>
</div>
