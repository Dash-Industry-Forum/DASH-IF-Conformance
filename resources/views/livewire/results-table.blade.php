<div>
  <h4 class="text-center">Quick Results</h4>
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
