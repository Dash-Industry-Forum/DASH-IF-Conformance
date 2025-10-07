<div>
  @if ($this->isLoading() != $this->segmentCount())
    <div class="row">
      <p wire:poll class="col-6">
        Downloading segments {{$this->isLoading()}} / {{$this->segmentCount()}}
      </p>
      <div class="col-6">
        <div class="progress">
          <div class="progress-bar" style="width: {{$this->isLoading() / $this->segmentCount() * 100 }}%"></div>
        </div>
      </div>
    </div>
  @endif
</div>
