<x-layout>
@session('process-consent')
@else
  <livewire:consent-manager />
@endsession

  <livewire:select-mpd />

  <livewire:manifest-details />

@session('process-consent')
  <livewire:consent-manager />
@endsession
</x-layout>
