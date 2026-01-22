<div>
  <h4 class="text-center">Quick Results</h4>
  @if ($this->stateCount('queued') > 0)
      <div wire:poll></div>
  @else
  <div>
      @if ($this->stateCount('failed') > 0)
        <div class="alert alert-danger">
        <b>Failed segment downloads</b>
        <ul>
        @foreach ($this->failedSegments() as $failed)
        <li>{{$failed}} </li>
        @endforeach
        </ul>
        </div>
      @else
        <div class="alert alert-success">
          <b>All segments downloaded</b>
        </div>
      @endif
  </div>
  @endif
<table class="container table table-striped">
  <thead>
  <tr class="row">
    <th class="col-6"></th>
    <th class="col-2 text-center">MPD</th>
    <th class="col-2 text-center">Segments</th>
    <th class="col-2 text-center">Cross Validation</th>
  </tr>
  </thead>
  <tbody>
  @foreach ($this->table as $key => $tableSection)
  <tr class="row">
    <td class="col-6">
      {{$key}}
    </td>
    <td class="col-2 text-center {{ $this->getResultClass($key, 'MPD') }}">
      {{ $this->getResult($key, 'MPD') }}
    </td>
    <td class="col-2 text-center {{ $this->getResultClass($key, 'Segments') }}">
      {{ $this->getResult($key, 'Segments') }}
    </td>
    <td class="col-2 text-center {{ $this->getResultClass($key, 'CrossValidation') }}">
      {{ $this->getResult($key, 'CrossValidation') }}
    </td>
  </tr>

  @endforeach
  </tbody>
</table>
</div>
