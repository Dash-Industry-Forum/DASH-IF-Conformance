<div>
  <h4>SpecManager state</h4>
  @foreach ($this->specs() as $spec)
    <button type="button" {{ $spec == "Global Module" ? "disabled" : "" }} class="btn {{ $this->buttonClassForSpec($spec) }}" wire:click="enable('{{$spec}}')">{{$spec}}</button>
  @endforeach
</div>
