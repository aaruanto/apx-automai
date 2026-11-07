{{--
    The email twin of a booking bell entry, for the events worth emailing:
    a cancellation, a reschedule, and the work being finished.

    The closing line is chosen by status rather than by event, because what a
    customer needs to do next is a property of where the booking now stands.
    Telling someone whose car is already serviced to "arrive 10 minutes early"
    is how that line read before completion was mailed at all.

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
@elseif($booking->status === 'completed')
Your vehicle is **ready for pickup** at the branch below. Please bring your
reference number when you collect it.
@else
Please arrive **10 minutes early** so we can serve you right away.
@endif

@component('mail::button', ['url' => config('app.url') . '/customer/dashboard', 'color' => 'red'])
View My Bookings
@endcomponent

Thank you for choosing **APX Motors Service Center**.

**APX AUTOMAI Team**
Tandang Sora Branch, Quezon City
@endcomponent
