@component('mail::message')
# Booking Confirmed! ✅

Hi **{{ $booking->user->name }}**,

Your booking has been **confirmed** by APX AutoMai. Here are your appointment details:

@component('mail::panel')
**Reference No:** {{ $booking->reference_number }}
**Service:** {{ $booking->service->name }}
**Date:** {{ \Carbon\Carbon::parse($booking->booking_date)->format('F d, Y') }}
**Time:** {{ \Carbon\Carbon::parse($booking->booking_time)->format('g:i A') }}
**Status:** Confirmed ✅
@endcomponent

Please arrive **10 minutes early** so we can serve you right away.

If you need to reschedule or cancel, please contact us as soon as possible.

@component('mail::button', ['url' => config('app.url') . '/customer/dashboard', 'color' => 'red'])
View My Booking
@endcomponent

Thank you for choosing **APX Motors Service Center**!

**APX AutoMai Team**
Tandang Sora Branch, Quezon City
@endcomponent