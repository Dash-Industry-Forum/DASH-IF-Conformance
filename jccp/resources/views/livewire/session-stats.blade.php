<div>
      <div style="display:flex">
        <div>Session id</div>
        <div>{{ session()->getId() }}</div>
      </div>
      <div style="display:flex">
        <div>Session mpd</div>
        <div>{{ session()->get('mpd') }}</div>
      </div>
</div>
