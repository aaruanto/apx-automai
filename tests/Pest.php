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
