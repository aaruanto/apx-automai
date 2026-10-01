@component('mail::message')
# Welcome to APX AutoMai! 

Hi **{{ $user->name }}**,

Your booking has been received! We've automatically created an account for you so you can track your bookings anytime.

To access your account, you need to **set up your password** by clicking the button below:

@component('mail::button', ['url' => $resetUrl, 'color' => 'red'])
Set Up My Password
@endcomponent

This link will expire in **60 minutes**. If you didn't book with us, you can ignore this email.

Once your password is set, you can login at:
**{{ config('app.url') }}/login**

**APX AutoMai Team**
Tandang Sora Branch, Quezon City
@endcomponent