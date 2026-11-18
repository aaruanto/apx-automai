{{--
    "We will call you to verify this" — for a guest booking only.

    One partial, included by both the acknowledgment and the confirmation,
    because the two emails are sent to the same person about the same booking
    and the promise must not be worded differently in each. Which of them a
    guest receives depends on whether today was full when they booked, and
    they should not be able to tell the difference by what this paragraph
    says.

    ── Guests only ───────────────────────────────────────────────────────

    is_guest is the account flag, and the condition is the account's rather
    than the booking's because that is where the fact lives: a guest booking
    creates a users row with is_guest = true and no password. A signed-in
    customer has already proved they hold the address they booked with, so
    there is nothing to verify and an unexpected "we will call you" would
    read as though something were wrong with their booking.

    A guest who later claims their account stops being is_guest, and a
    confirmation emailed to them after that carries no such line. That is
    right: by then they are a customer.

    No phone, no paragraph. The guest form requires one now
    (App\Support\PhoneNumber), but an older guest booking has none on record
    and promising a call to a number we do not hold is worse than saying
    nothing.
--}}
@if (($booking->user?->is_guest ?? false) && filled($booking->user?->phone))
**We will call you on {{ $booking->user->phone }}** to confirm these details and check that we can reach you on that number. If it is wrong, reply to this email or call the shop and we will correct it.
@endif
