@props(['name' => null])
@php
    // Single source of truth for the daily greeting — reference this component
    // everywhere a "Welcome back" style greeting is needed instead of
    // recomputing the weekday in each view.
    $trimmedName = trim((string) $name);
    $weekday     = now()->format('l'); // Monday .. Sunday, in the app timezone
@endphp
@if($trimmedName !== '')
<h2>Happy {{ $weekday }}, <span>{{ $trimmedName }}</span>!</h2>
@else
<h2>Greetings!</h2>
@endif
