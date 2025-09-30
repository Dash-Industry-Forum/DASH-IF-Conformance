<div class="container">
    <div class="row">
      <div class="col">Session id</div>
      <div class="col">{{ session()->getId() }}</div>
    </div>
    <div class="row">
      <div class="col">Session mpd</div>
      <div class="col">{{ session()->get('mpd') }}</div>
    </div>
    <button wire:click="clearSession">Reset Session</button>
</div>
