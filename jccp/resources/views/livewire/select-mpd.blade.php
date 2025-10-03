<div class="container">
  <div class="row">
      <div class="input-group mb-3 col-12">
         <span class="input-group-text">Manifest URL</span>
         @session('mpd')
             <input disabled type="text" class="form-control" wire:model.live="mpd">
             <button class="btn btn-outline-danger" type="button" wire:click="clearSession">Clear</button>
         @else
             @session('process-consent')
                 <input type="text" class="form-control" wire:model.live="mpd">
                 <button class="btn btn-outline-secondary" type="button" wire:click="process">Process</button>
             @else
                 <input type="text" class="form-control" wire:model.live="mpd" disabled>
                 <button class="btn btn-outline-secondary" type="button" wire:click="process" disabled>Process</button>
             @endsession
         @endsession
      </div>
  </div>
  @if ($this->mpdError())
    <div class="alert alert-danger">
      <h4>Unable to parse MPD: {{ $this->mpdError() }}</h4>
    </div>
  @endif
</div>
