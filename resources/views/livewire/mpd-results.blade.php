<div>
  @empty($this->getSpecs())
    <div class="alert alert-info">No {{ $this->section }} checks enabled</div>
  @else
  <div class="container">
    <ul class="nav nav-tabs">
      @foreach ($this->getSpecs() as $spec)
        <li class="nav-item">
    @if ($this->selectedSpec == $spec)
          <span class="nav-link active" wire:click="selectSpec('{{$spec}}')">
    @else
          <span class="nav-link" wire:click="selectSpec('{{$spec}}')">
    @endif
            {{ $this->getSpecResult($spec) }}
            {{ $spec }}
          </span>
        </li>
      @endforeach
    </ul>
  </div>
  <table class="container table table-striped">
    <tr class="row">
      <th class="col-1" scope="col">State</th>
      <th class="col-2" scope="col">Section</th>
      <th class="col-4" scope="col">Statement</th>
      <th class="col-5" scope="col">Messages</th>
    </tr>
    @foreach ($this->transformResults($this->selectedSpec) as $check)
      <tr class="row">
        <td class="col-1">{{ $check['state'] }}</td>
        <td class="col-2">{{ $check['section'] }}</td>
        <td class="col-4">{{ $check['check'] }}</td>
        <td class="col-5">
          @foreach ($check['messages'] as $msg)
            <div>{{ $msg }}</div>
          @endforeach
        </td>
      </tr>
    @endforeach
  </table>
  @endempty
</div>
