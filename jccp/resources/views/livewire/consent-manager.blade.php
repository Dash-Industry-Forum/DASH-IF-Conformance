@session('process-consent')
   <div class="alert alert-info">
    <h4>You have given permission for us to collect usage data in order to improve the tool</h4>
    <button class="btn btn-outline-danger" type="button" wire:click="revoke">Revoke</button>
  </div>
@else
  <div class="alert alert-danger">
    <h4>We collect usage data in order to improve the tool</h4>
    <p>
      If you work with private data, consider <a href="https://github.com/Dash-Industry-Forum/DASH-IF-Conformance/wiki/Installation--guide">Self Hosting</a> instead.
    </p>
    <button class="btn btn-outline-success" type="button" wire:click="accept">Accept</button>
  </div>
@endsession
