{{--
    The pre-approval acknowledgment.

    No reference number anywhere in here, and nothing telling the customer to
    bring or quote one — this goes out while the booking is still 'pending',
    and the shop has not agreed to the slot yet. The ticket is
    emails/booking-confirmed.blade.php. See App\Mail\BookingReceived.

    What is left is what a receipt owes the customer: what they asked for,
    when they asked for it, that it is not settled yet, and what happens next.
--}}
@component('mail::message')
# We have your request

Hi **{{ $booking->user->name }}**,

Thanks for booking with APX Motors Service Center. Your request is in and is **waiting for our review** — it is not confirmed yet.

@component('mail::panel')
**Service:** {{ $booking->service_list }}
**Requested date:** {{ \Carbon\Carbon::parse($booking->booking_date)->format('F d, Y') }}
**Requested time:** {{ \Carbon\Carbon::parse($booking->booking_time)->format('g:i A') }}
**Status:** Pending review ⏳
@endcomponent

Our staff will check the slot and get back to you. **Once it is confirmed we will email you again**, and that email will carry your reference number — the one to bring with you on the day.

Nothing to do for now. If you need to change or cancel this request, just reply to this email and we will sort it out.

Thank you for choosing **APX Motors Service Center**!

**APX AUTOMAI Team**
Tandang Sora Branch, Quezon City
@endcomponent
