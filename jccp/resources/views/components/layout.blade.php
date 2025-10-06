<!DOCTYPE html>
<html>
    <head>
      <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>DASH-IF Conformance Tool</title>
  @vite(['resources/js/app.js'])
  <style>
    body,
    #root {
      padding: 0;
      margin: 0;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
        }
      </style>
    </head>
    <body>
      <livewire:navigation />
      <div class="alert alert-info">
        <h4>This version is currently in <strong>alpha</strong> state.</h4>
        <p>
            Many checks and elements have not been ported yet, if you need them, please use the <a href="https://conformance.dashif.org/">Stable version</a> instead.
        </p>
      </div>
      <div>
        {{ $slot }}
      </div>
    </body>
</html>
