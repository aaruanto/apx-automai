<?php

use App\Models\LoyaltyLedger;

/**
 * Things that pass on SQLite and fail on the live site.
 *
 * The suite runs on SQLite; production is PostgreSQL. Where the two grammars
 * differ, a green suite proves nothing — and one of those differences took
 * down every cancellation on the live site while all 709 tests passed.
 *
 * ->lockForUpdate()->sum() is the shape. Laravel keeps the lock when it
 * rewrites a query as an aggregate, so PostgreSQL is handed
 *
 *     select sum("points") as aggregate from "loyalty_ledger" ... for update
 *
 * and refuses it — FOR UPDATE is not allowed with aggregate functions. The
 * SQLite grammar drops FOR UPDATE entirely, so locally it is a plain sum and
 * nothing ever complained.
 *
 * These tests read generated SQL rather than running it, so they need no
 * PostgreSQL server and no driver.
 */

/** The SQL a query would send to Postgres, without executing it. */
function pgSql(\Illuminate\Database\Eloquent\Builder $query, ?string $aggregate = null): string
{
    $base = $query->getQuery();

    if ($aggregate !== null) {
        // The public property Builder::aggregate() fills in before running.
        // Set directly because setAggregate() is protected, and because the
        // point here is what the grammar compiles, not how it got there.
        $base->aggregate = ['function' => $aggregate, 'columns' => ['points']];
    }

    return $base->toSql();
}

it('shows the shape that breaks, so the rule below has a reason', function () {
    // Not a thing the app does any more — this is the hazard itself, written
    // down. If a future Laravel stopped carrying the lock into the aggregate,
    // this test would fail and the guard beneath could be relaxed.
    $sql = pgSql(
        LoyaltyLedger::on('pgsql')->where('booking_id', 1)->lockForUpdate(),
        aggregate: 'sum'
    );

    expect($sql)->toContain('sum("points")')
        ->and($sql)->toContain('for update');
});

it('locks rows without asking Postgres to lock an aggregate', function () {
    // What LoyaltyService::lockedSum() does: select the rows under a lock and
    // add them up in PHP. A row lock with no aggregate is valid everywhere.
    $sql = pgSql(LoyaltyLedger::on('pgsql')->where('booking_id', 1)->lockForUpdate());

    expect($sql)->toContain('for update')
        ->and($sql)->not->toContain('aggregate');
});

it('never combines a row lock with an aggregate anywhere in the app', function () {
    // A source guard, because no SQLite test can catch this one. It reads the
    // shape that is unsafe rather than any particular caller, so a new
    // ->lockForUpdate()->sum() added next year fails here instead of on the
    // live site.
    $offenders = [];

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path()));

    foreach ($files as $file) {
        if ($file->isDir() || $file->getExtension() !== 'php') {
            continue;
        }

        // Comments stripped first, with the tokenizer rather than a regex:
        // the fix for this very bug documents the bad shape in prose, and a
        // naive scan flags the explanation as the offence.
        $source = '';

        foreach (token_get_all(file_get_contents($file->getPathname())) as $token) {
            if (is_array($token)) {
                if ($token[0] === T_COMMENT || $token[0] === T_DOC_COMMENT) {
                    continue;
                }
                $source .= $token[1];
            } else {
                $source .= $token;
            }
        }

        // Look at what follows each lockForUpdate(). An aggregate reached
        // before the query is materialised is the unsafe shape; one reached
        // after ->get() is a Collection sum done in PHP, which is the fix, so
        // the scan stops at whatever ends the query.
        $offset = 0;

        while (($at = strpos($source, 'lockForUpdate()', $offset)) !== false) {
            $offset = $at + 1;
            $tail   = substr($source, $at, 220);

            // Where the database query stops and PHP takes over.
            $ends = PHP_INT_MAX;
            foreach (['->get(', '->pluck(', '->cursor(', '->first(', ';'] as $terminator) {
                $pos = strpos($tail, $terminator);
                if ($pos !== false) {
                    $ends = min($ends, $pos);
                }
            }

            $sqlPart = substr($tail, 0, $ends === PHP_INT_MAX ? null : $ends);

            if (preg_match('~->(sum|count|avg|min|max)\(~', $sqlPart)) {
                $offenders[] = str_replace(app_path().DIRECTORY_SEPARATOR, '', $file->getPathname());
                break;
            }
        }
    }

    expect($offenders)->toBe([]);
});
