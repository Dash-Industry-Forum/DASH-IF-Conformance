<div>
  @if ($this->isLoading() != $this->segmentCount())
    <div class="alert alert-info" wire:poll>
      <b>Downloading segments</b>
    <div class="row">
      <div class="col-1">
    {{$this->isLoading()}} / {{$this->segmentCount()}}
      </div>
      <div class="col-11">
        <div class="progress">
          <div class="progress-bar" style="width: {{$this->isLoading() / $this->segmentCount() * 100 }}%"></div>
        </div>
      </div>
      </div>
    </div>
  @else
    @if ($this->stateCount('failed') > 0)
      <div class="alert alert-danger">
      <b>Failed segment downloads</b>
      <ul>
      @foreach ($this->failedSegments() as $segment => $reason)
          <li>{{$segment}}<ul><li>{{$reason}}</li></ul></li>
      @endforeach
      </ul>
      </div>
    @else
      <div class="alert alert-success">
        <b>All segments downloaded</b>
      </div>
    @endif
  @endif
</div>
