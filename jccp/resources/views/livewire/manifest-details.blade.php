<div class="container">
  @session('mpd')
  <h4>SpecManager state</h4>
  <pre>{{ $this->specManagerState() }}</pre>
    <h4>Resolved Manifest</h4>

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
    <th scope="col">State</th>
    <th scope="col">Section</th>
    <th scope="col">Statement</th>
    <th scope="col">Messages</th>
  </tr>
  @foreach ($this->transformResults($this->selectedSpec) as $check)
    <tr>
      <td>{{ $check['state'] }}</td>
      <td>{{ $check['section'] }}</td>
      <td>{{ $check['check'] }}</td>
      <td>
        @foreach ($check['messages'] as $msg)
            <div>{{ $msg }}</div>
        @endforeach
      </td>
    </tr>
  @endforeach
  </table>



  @endsession

    <!--pre>{{ $this->getFeatures() }}</pre-->
</div>
