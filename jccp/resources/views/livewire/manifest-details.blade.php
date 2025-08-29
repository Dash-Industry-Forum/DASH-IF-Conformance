<div class="container">
  @session('mpd')
  <h4>SpecManager state</h4>
  <pre>{{ $this->specManagerState() }}</pre>

    <h4>Segment Checks</h4>
  <div>
    <ul class="nav">
      @foreach ($this->getSpecs() as $spec)
        <li class="nav-item">
          <span class="nav-link" wire:click="selectSpec('{{$spec}}')">
            {{ $spec }}
          </span>
        </li>
      @endforeach
    </ul>
  </div>
  <table class="table table-striped">
    <tr>
      <th class="col-1" scope="col">State</th>
      <th class="col-2" scope="col">Section</th>
      <th class="col-4" scope="col">Statement</th>
      <th scope="col">Messages</th>
    </tr>
    @foreach ($this->transformResults($this->selectedSpec, "Segments") as $check)
      <tr>
        <td class="col-1">{{ $check['state'] }}</td>
        <td class="col-2">{{ $check['section'] }}</td>
        <td class="col-4">{{ $check['check'] }}</td>
        <td>
          @foreach ($check['messages'] as $msg)
            <div>{{ $msg }}</div>
          @endforeach
        </td>
      </tr>
    @endforeach
  </table>

    <h4>Manifest Checks</h4>

  <div>
    <ul class="nav">
      @foreach ($this->getSpecs() as $spec)
        <li class="nav-item">
          <span class="nav-link" wire:click="selectSpec('{{$spec}}')">
            {{ $spec }}
          </span>
        </li>
      @endforeach
    </ul>
  </div>
  <table class="table table-striped">
    <tr>
      <th class="col-1" scope="col">State</th>
      <th class="col-2" scope="col">Section</th>
      <th class="col-4" scope="col">Statement</th>
      <th scope="col">Messages</th>
    </tr>
    @foreach ($this->transformResults($this->selectedSpec, "MPD") as $check)
      <tr>
        <td class="col-1">{{ $check['state'] }}</td>
        <td class="col-2">{{ $check['section'] }}</td>
        <td class="col-4">{{ $check['check'] }}</td>
        <td>
          @foreach ($check['messages'] as $msg)
            <div>{{ $msg }}</div>
          @endforeach
        </td>
      </tr>
    @endforeach
  </table>
  @endsession

  <pre>{{ $this->getFeatures() }}</pre>
</div>
