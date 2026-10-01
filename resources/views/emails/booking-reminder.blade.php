@component('mail::message')
# Appointment Reminder 🔔

Hi **{{ $booking->user->name }}**,

This is a friendly reminder that you have an upcoming service appointment **tomorrow** at APX Motors Service Center.

@component('mail::panel')
**Reference No:** {{ $booking->reference_number }}
**Service:** {{ $booking->service->name }}
**Date:** {{ \Carbon\Carbon::parse($booking->booking_date)->format('F d, Y') }}
**Time:** {{ \Carbon\Carbon::parse($booking->booking_time)->format('g:i A') }}
@endcomponent

Please make sure to arrive **10 minutes early**. If you need to reschedule, contact us right away.

@component('mail::button', ['url' => config('app.url') . '/customer/dashboard', 'color' => 'red'])
View My Booking
@endcomponent

See you tomorrow!

**APX AutoMai Team**
Tandang Sora Branch, Quezon City
@endcomponent