@component('mail::message')
# Booking Received! 

Hi **{{ $booking->user->name }}**,

We have received your booking request at APX Motors Service Center. It is currently **pending review** and will be confirmed shortly.

@component('mail::panel')
**Reference No:** {{ $booking->reference_number }}
**Service:** {{ $booking->service->name }}
**Date:** {{ \Carbon\Carbon::parse($booking->booking_date)->format('F d, Y') }}
**Time:** {{ \Carbon\Carbon::parse($booking->booking_time)->format('g:i A') }}
**Status:** Pending ⏳
@endcomponent

You will receive another email once your booking has been **confirmed** by our staff.

Please bring this reference number when you arrive:
**{{ $booking->reference_number }}**

Thank you for choosing **APX Motors Service Center**!

**APX AutoMai Team**
Tandang Sora Branch, Quezon City
@endcomponent