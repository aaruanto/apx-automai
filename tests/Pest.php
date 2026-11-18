<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * The next day the shop is open all day.
 *
 * Saturday and Sunday close at noon, so a test that books an afternoon slot
 * on today()->addDay() passes most of the week and fails every Friday and
 * Saturday. Several did, which is a test that reports the calendar rather
 * than the code.
 */
function nextWeekday(): string
{
    return nextWeekdayAfter(1);
}

/**
 * The first full opening day at least $days from now.
 *
 * Same trap as nextWeekday(), one step further out: a test that books an
 * afternoon slot on today()->addDays(5) passes for most of the week and fails
 * whenever that lands on a Saturday or Sunday. Any test that picks a booking
 * date arithmetically should come through here.
 */
function nextWeekdayAfter(int $days): string
{
    $date = Carbon\Carbon::today()->addDays($days);

    while ($date->dayOfWeekIso >= 6) {
        $date->addDay();
    }

    return $date->toDateString();
}

/**
 * $count full opening days, each a different date, from $from days out.
 *
 * nextWeekdayAfter() maps a day offset onto the next opening day, and that
 * mapping is not one-to-one: on a Friday, offsets 1 and 2 both answer with the
 * following Monday. A loop feeding it $i therefore asked for several dates and
 * got the same one twice, so its second booking collided with its first and the
 * request came back 422. Two tests did exactly that, and between them failed on
 * five days of the week — the plate-normalising test on Friday, Saturday and
 * Sunday, the reschedule-walk test on Monday and Wednesday — each passing
 * whenever it happened to be run on one of the other days.
 *
 * So anything needing more than one date asks for them together and gets
 * distinct ones. Use nextWeekdayAfter() only for a single date.
 *
 * @return list<string>
 */
function nextWeekdays(int $count, int $from = 1): array
{
    $dates = [];
    $date  = Carbon\Carbon::today()->addDays($from);

    while (count($dates) < $count) {
        while ($date->dayOfWeekIso >= 6) {
            $date->addDay();
        }

        $dates[] = $date->toDateString();
        $date->addDay();
    }

    return $dates;
}

/**
 * Freeze the clock on a weekday morning and answer with that day's date.
 *
 * For the tests that are about TODAY — the daily cap holding a same-day
 * booking back for review — and they cannot be written against the real
 * clock. Run at 7pm, or any time on a Sunday afternoon, "today" has no
 * opening hours left and reserve() refuses every slot on it before the rule
 * under test is ever reached: the test would pass all morning and fail all
 * evening, which is a test that reports the time of day.
 *
 * So the clock is pinned to 08:00 on a day the shop is open 9 to 6 — a
 * weekday, via nextWeekdayAfter(), for the same reason every other date in
 * this suite comes from there. Carbon::now() is then that instant
 * everywhere, so "today" is a known full opening day and a 10:00 slot on it
 * is bookable.
 *
 * Call Carbon::setTestNow() with no argument to let go of it; a test file
 * using this should do that in an afterEach, because a frozen clock leaking
 * into the next test is a very confusing failure.
 */
function freezeOnOpenMorning(string $at = '08:00'): string
{
    $date = nextWeekdayAfter(1);

    Carbon\Carbon::setTestNow(Carbon\Carbon::parse($date.' '.$at, config('app.timezone')));

    return $date;
}
