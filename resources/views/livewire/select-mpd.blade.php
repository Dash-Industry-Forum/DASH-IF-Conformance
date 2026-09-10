<div class="container">
  <div class="row">
      <div class="input-group mb-3 col-12">
         <span class="input-group-text">Manifest URL<br/>or Contents</span>
         @session('mpd')
             <textarea class="form-control" rows="3" wire:model.live="mpd" disabled></textarea>
             <button class="btn btn-outline-danger" type="button" wire:click="clearSession">Clear</button>
         @else
             @session('process-consent')
                 <textarea class="form-control" wire:model.live="mpd"></textarea>
                 <button class="btn btn-outline-secondary" type="button" wire:click="process">Process</button>
             @else
                 <textarea class="form-control" rows="3" wire:model.live="mpd" disabled></textarea>
                 <button class="btn btn-outline-secondary" type="button" wire:click="process" disabled>Process</button>
             @endsession
         @endsession
      </div>
  </div>
  @if ($this->mpdError())
    <div class="alert alert-danger">
      <b>Unable to parse MPD</b>
      <div>{{ $this->mpdError() }}</div>
    </div>
  @endif
</div>
