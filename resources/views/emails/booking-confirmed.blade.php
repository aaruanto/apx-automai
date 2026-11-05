{{--
    The ticket, and the first place the customer is given their reference.

    BookingReceived — the acknowledgment sent at creation — deliberately
    carries no reference number, because the booking is still pending when it
    goes out. So this email is not a second copy of details the customer
    already has: it is where the number comes from, which is why it leads with
    it and says plainly that it is the thing to bring.

    Shared, not duplicated. Only an admin moving a booking to 'confirmed'
    sends it (Admin\BookingController::update), so "confirmed" is safe to
    state as fact here.
--}}
@component('mail::message')
# Your booking is confirmed ✅

Hi **{{ $booking->user->name }}**,

Good news — APX Motors Service Center has **confirmed** your booking. Your reference number is below.

@component('mail::panel')
{{--
    Inline styles and a plain div, not a markdown heading. The markdown mail
    theme pins h1 at 18px, which is the same size as the body text — so the
    number that is the whole point of this email read as just another line.
    Raw HTML in a markdown mailable has to start at column 0 or the parser
    treats the indentation as a code block.
--}}
<div style="font-family:'Courier New',Courier,monospace;font-size:30px;font-weight:bold;letter-spacing:2px;color:#1a1a1a;text-align:center;padding:6px 0 10px;">{{ $booking->reference_number }}</div>

**This is the number to bring with you.** Show it at the counter when you arrive.
@endcomponent

@component('mail::panel')
**Service:** {{ $booking->service_list }}
**Date:** {{ \Carbon\Carbon::parse($booking->booking_date)->format('F d, Y') }}
**Time:** {{ \Carbon\Carbon::parse($booking->booking_time)->format('g:i A') }}
**Status:** Confirmed ✅
@endcomponent

Please arrive **10 minutes early** so we can serve you right away.

If you need to reschedule or cancel, let us know as early as you can — just reply to this email or call the shop.

@component('mail::button', ['url' => config('app.url') . '/customer/dashboard', 'color' => 'red'])
View My Booking
@endcomponent

Thank you for choosing **APX Motors Service Center**!

**APX AUTOMAI Team**
Tandang Sora Branch, Quezon City
@endcomponent
