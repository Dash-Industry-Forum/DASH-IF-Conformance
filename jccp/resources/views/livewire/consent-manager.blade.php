<div class="container-fluid mt-5">
@session('process-consent')
  <div class="alert alert-info">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-11">
          <b>You have given us permission to collect usage data in order to improve the application.</b>
        </div>
        <button class="col-1 btn btn-outline-danger" type="button" wire:click="revoke">Revoke</button>
      </div>
    </div>
  </div>
@else
  <div class="alert alert-warning">
    <div class="container">
      <div class="row align-items-center">
        <button class="btn btn-outline-success col-1" type="button" wire:click="accept">Accept</button>
        <div class="col-11">
          <b>We collect usage data in order to improve the application.</b>
          <div>
            If you work with private data, consider <a target="_blank" href="https://github.com/Dash-Industry-Forum/DASH-IF-Conformance/wiki/Installation--guide">Self Hosting</a> instead.
          </div>
        </div>
      </div>
    </div>
  </div>
@endsession
</div>
