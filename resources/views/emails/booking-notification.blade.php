{{--
    The email twin of a booking bell entry, for the events worth emailing:
    a cancellation and a reschedule.

    It takes the title and body the notification already composed rather than
    writing the copy a second time — so the email and the bell cannot drift
    into saying different things about the same booking, and a change to the
    wording lands in both. The panel below repeats the booking's details
    because an email is read away from the app, with no dashboard beside it.
--}}
@component('mail::message')
# {{ $heading }}

Hi **{{ $name }}**,

{{ $body }}

@component('mail::panel')
**Reference No:** {{ $booking->reference_number }}
**Service:** {{ $booking->service_list }}
**Date:** {{ \Carbon\Carbon::parse($booking->booking_date)->format('F d, Y') }}
**Time:** {{ \Carbon\Carbon::parse($booking->booking_time)->format('g:i A') }}
@if($booking->vehicle)
**Vehicle:** {{ $booking->vehicle->display_plate }}
@endif
@endcomponent

@if($booking->status === 'cancelled')
No visit is needed. If this was not what you expected, or you would like to
book again, you can do so from your dashboard.
@else
Please arrive **10 minutes early** so we can serve you right away.
@endif

@component('mail::button', ['url' => config('app.url') . '/customer/dashboard', 'color' => 'red'])
View My Bookings
@endcomponent

Thank you for choosing **APX Motors Service Center**.

**APX AutoMai Team**
Tandang Sora Branch, Quezon City
@endcomponent
