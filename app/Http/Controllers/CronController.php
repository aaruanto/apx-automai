<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * An HTTP door onto the task scheduler.
 *
 * Render's free tier runs one web service and nothing else: no cron jobs, no
 * background workers, and no shell to start `schedule:work` in. So the
 * scheduler had no way to tick in production, and every automated reminder the
 * paper promises simply never fired. Nobody noticed because the failure mode
 * is silence.
 *
 * The fix is to let something outside the app do the ticking. An external cron
 * service calls this endpoint on a fixed interval and Laravel's own scheduler
 * decides, on each call, which commands are actually due — so the frequencies
 * stay declared in routes/console.php where they belong, and the external cron
 * stays a dumb heartbeat that knows nothing about the schedule.
 *
 * It is guarded by a shared secret in APX_CRON_TOKEN, compared in constant
 * time. With no token configured the route 404s rather than running: a
 * misconfigured deploy must not leave an open endpoint that anyone can use to
 * make the app send mail.
 */
class CronController extends Controller
{
    /**
     * The commands this endpoint is willing to run.
     *
     * An allow-list, not a parameter. Taking a command name from the request
     * would turn a leaked token into the ability to run any artisan command on
     * production — including migrate:fresh.
     */
    private const COMMANDS = [
        'schedule' => 'schedule:run',
    ];

    public function __invoke(Request $request, string $task = 'schedule'): Response
    {
        $expected = (string) config('apx.cron_token');

        // No token set means the feature is not configured. Behave exactly as
        // if the route did not exist.
        if ($expected === '') {
            abort(404);
        }

        $provided = (string) ($request->header('X-Cron-Token') ?: $request->query('token', ''));

        if (! hash_equals($expected, $provided)) {
            // The token itself is never logged, only that something missed.
            Log::warning('Cron endpoint called with a bad or missing token from '.$request->ip());

            abort(404);
        }

        if (! isset(self::COMMANDS[$task])) {
            abort(404);
        }

        // A tick that sends mail can outlive the default limit, and the cron
        // service hangs up as soon as it has a status code — which must not
        // kill the run halfway through a batch of reminders.
        @set_time_limit(0);
        ignore_user_abort(true);

        $started = microtime(true);

        Artisan::call(self::COMMANDS[$task]);

        $output  = trim(Artisan::output());
        $elapsed = round((microtime(true) - $started) * 1000);

        return response(
            "ok {$task} in {$elapsed}ms\n".$output."\n",
            200,
            ['Content-Type' => 'text/plain; charset=utf-8'],
        );
    }
}
