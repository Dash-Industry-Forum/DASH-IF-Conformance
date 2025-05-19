<!DOCTYPE html>
<html>
    <head>
      <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>DASH-IF Conformance Tool</title>
  <link href="./css/bootstrap.min.css" rel="stylesheet">
  <link href="./res/fontawesome/css/fontawesome.min.css" rel="stylesheet">
  <link href="./res/fontawesome/css/solid.min.css" rel="stylesheet">
  <link href="./css/custom.css" rel="stylesheet">
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
        @persist('session-stats')
          <livewire:session-stats />
        @endpersist
      <div style="display:flex">
        @persist('sidemenu')
          <livewire:sidebar/>
        @endpersist
        <div>
        {{ $slot }}
        </div>
      </div>
    </body>
</html>
