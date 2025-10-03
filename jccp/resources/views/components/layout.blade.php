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
      <div>
        {{ $slot }}
      </div>
    </body>
</html>
