<div class="container-fluid">
@session('process-consent')
   <div class="alert alert-info row">
    <b class="col-11">You have given permission for us to collect usage data in order to improve the tool</b>
    <button class="col-1 btn btn-outline-danger" type="button" wire:click="revoke">Revoke</button>
  </div>
@else
  <div class="alert alert-warning row">
    <button class="btn btn-outline-success col-1" type="button" wire:click="accept">Accept</button>
    <div class="col-11">
      <b>We collect usage data in order to improve the tool</b>
      <div>
        If you work with private data, consider <a href="https://github.com/Dash-Industry-Forum/DASH-IF-Conformance/wiki/Installation--guide">Self Hosting</a> instead.
      </div>
    </div>
  </div>
@endsession
</div>
