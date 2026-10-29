<?php

use Illuminate\Support\Carbon;

/**
 * The date helpers in tests/Pest.php, tested.
 *
 * Nearly every booking test picks its date through them, so a flaw in one does
 * not fail honestly — it fails somewhere else, on some days of the week, in a
 * test about something entirely different. That is exactly what happened:
 * nextWeekdayAfter() maps a day offset onto the next opening day and that
 * mapping is not one-to-one, so a loop feeding it 1, 2, 3 got the same Monday
 * twice on a Friday. The plate-normalising test then booked two slots on one
 * date, the second came back 422, and the failure read as a plate bug.
 *
 * These travel through a whole week, because a helper about weekends cannot be
 * trusted on the evidence of the one day the suite happens to be run.
 */
afterEach(fn () => Carbon::setTestNow());

it('never returns a day the shop is closed all afternoon', function () {
    foreach (range(0, 6) as $day) {
        Carbon::setTestNow(Carbon::parse('2026-11-02')->addDays($day));

        expect(Carbon::parse(nextWeekday())->dayOfWeekIso)->toBeLessThan(6);

        foreach (range(0, 14) as $offset) {
            expect(Carbon::parse(nextWeekdayAfter($offset))->dayOfWeekIso)
                ->toBeLessThan(6, 'weekend from offset '.$offset.' on a '.Carbon::today()->format('D'));
        }
    }
});

it('gives distinct opening days whatever day it is run', function () {
    foreach (range(0, 6) as $day) {
        Carbon::setTestNow(Carbon::parse('2026-11-02')->addDays($day));
        $today = Carbon::today()->format('D');

        foreach ([[3, 1], [3, 10], [5, 0], [2, 4], [10, 2]] as [$count, $from]) {
            $dates = nextWeekdays($count, $from);
            $where = $count.' from '.$from.' on a '.$today;

            expect($dates)->toHaveCount($count);
            expect(array_unique($dates))->toHaveCount($count, 'duplicate date, '.$where);

            // In order, and no earlier than asked for.
            expect($dates)->toBe(collect($dates)->sort()->values()->all(), 'out of order, '.$where);
            expect($dates[0])->toBeGreaterThanOrEqual(
                Carbon::today()->addDays($from)->toDateString(), 'too early, '.$where
            );

            foreach ($dates as $date) {
                expect(Carbon::parse($date)->dayOfWeekIso)->toBeLessThan(6, 'weekend, '.$where);
            }
        }
    }
});

it('agrees with nextWeekdayAfter on the first date it gives', function () {
    foreach (range(0, 6) as $day) {
        Carbon::setTestNow(Carbon::parse('2026-11-02')->addDays($day));

        // The one-date helper stays correct and stays the one to use for a
        // single date; nextWeekdays() exists for the case it cannot serve.
        expect(nextWeekdays(1)[0])->toBe(nextWeekdayAfter(1));
        expect(nextWeekdays(1, 7)[0])->toBe(nextWeekdayAfter(7));
        expect(nextWeekday())->toBe(nextWeekdayAfter(1));
    }
});
