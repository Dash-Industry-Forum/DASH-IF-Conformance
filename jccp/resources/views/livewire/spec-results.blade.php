<div>
  @foreach ($this->transformResults() as $check)
    <div class="row">
      <div class="col-1">{{ $check['state'] }}</div>
      <div class="col-1">{{ $check['section'] }}</div>
      <div class="col-5">{{ $check['check'] }}</div>
      <div class="col-5">{{ $check['messages'] }}</div>
    </div>
  @endforeach
</div>
