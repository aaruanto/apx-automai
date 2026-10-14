# APX AutoMai

Vehicle service booking system for APX Motors and Services, Quezon City.
Laravel 12 + Blade + Bootstrap. SQLite locally, PostgreSQL on Render.

---

## Local setup

```bash
composer install
npm install && npm run build
cp .env.example .env          # then fill in the values below
php artisan key:generate
php artisan migrate
php artisan db:seed --class=ServiceSeeder
php artisan serve
```

There is no public route to an admin account by design. Create the first one
from the environment:

```bash
php artisan apx:ensure-admin --email=you@example.com --password='a-strong-password'
```

Run the test suite with `php artisan test`.

---

## The scheduler

Three jobs run on a schedule (see `routes/console.php`):

| Job | Frequency | What it does |
|---|---|---|
| `reminders:send` | every minute | booking reminders at 7d / 3d / 1d / 2h |
| `bookings:cancel-no-shows` | every minute | cancels bookings past their grace period |
| `accounts:purge-expired` | daily, 03:00 | anonymises accounts past the 30-day deletion window |
| heartbeat | every minute | records `scheduler_last_run` so the admin screen can report real status |

**Nothing works unless something runs the scheduler.** Laravel does not run it
for you.

### Locally

```bash
php artisan schedule:work
```

Leave it running alongside `php artisan serve`.

### On a traditional server

```
* * * * * cd /path/to/this/app && php artisan schedule:run >> /dev/null 2>&1
```

### On Render

Render does not run crontabs, so the line above does nothing there. Use a
**Cron Job** service instead:

1. Render dashboard → **New** → **Cron Job**.
2. Point it at the same repository and `Dockerfile` as the web service.
3. **Schedule:** `* * * * *` (every minute).
4. **Command:** `php artisan schedule:run`
5. Attach the **same environment variables and the same database** as the web
   service, or it will run against nothing.

Cost: Render's Cron Jobs are a paid feature and are not included in the free
tier. If you would rather not pay, the alternatives are to run
`php artisan schedule:run` from any machine that is already online — a free
third-party cron pinger hitting a dedicated route, or a laptop running
`schedule:work` during a demo.

### Queue worker

Not needed. No mailable or notification in this project implements
`ShouldQueue`, so mail is sent during the request that triggers it. If anything
is queued later, a Render **Background Worker** running `php artisan queue:work`
would be required, and that is also a paid service.

---

## Email

`MAIL_MAILER` defaults to `log`, which writes messages to
`storage/logs/laravel.log` instead of sending them. That is useful in
development and is why no mail arrives until it is changed.

### Why not Gmail

Render's free instances **drop outbound traffic to ports 25, 465 and 587** at
the firewall (since 26 September 2025). Gmail's SMTP only listens on 465 and
587, so it cannot be reached from a free instance no matter how it is
configured. The packets are dropped rather than refused, so the failure
arrives as a *connection timeout* — which reads like a network fault and is
easily mistaken for wrong credentials.

Port **2525** is not blocked. Brevo serves SMTP there, so that is what this
project uses. Gmail still works fine for local development and on any host
that permits 587; only the deployed environment is constrained.

### Settings

Add these to `.env` locally and to the **Environment** tab on Render:

```
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=2525
MAIL_SCHEME=null
MAIL_USERNAME=your-brevo-login@example.com
MAIL_PASSWORD=your-brevo-smtp-key
MAIL_FROM_ADDRESS=your-verified-sender@example.com
MAIL_FROM_NAME="APX AutoMai"
```

`MAIL_USERNAME` is the Brevo **login email**. `MAIL_PASSWORD` is an **SMTP
key** generated under *SMTP & API → SMTP keys* — not the account password.
`MAIL_FROM_ADDRESS` must be verified under *Senders, Domains & Dedicated
IPs*; verifying a single address is enough, no domain purchase required.

This app reads **`MAIL_SCHEME`**, not `MAIL_ENCRYPTION` — see
`config/mail.php`. Leave it `null` on 2525 and 587, where STARTTLS is
negotiated on the wire; set it to `smtps` only for an implicit-TLS port such
as 465 or 2465. Setting `MAIL_ENCRYPTION` has no effect here.

Brevo's free tier allows **300 messages per day** with no expiry. Past that,
sends are rejected until the window rolls over. For comparison, a free Gmail
account allows roughly 500/day. If volume ever outgrows either, `config/mail.php`
already has entries for Postmark, Resend and SES, so it stays an env-var
change rather than a rewrite.

### Checking it works

Admin panel → **Message Templates** → **Send Test**. Enter an address and
submit; the result dialog reports per channel what happened, including the
mailer's own error text if the send failed. Common causes:

| Message | Cause |
|---|---|
| `MAIL_MAILER is set to "log"` | Still on the log driver — nothing is being sent |
| `Connection timed out` | The host is blocking that port. On Render, 25/465/587 are dropped — use 2525 |
| `Failed to authenticate` | `MAIL_PASSWORD` is not a valid SMTP key, or `MAIL_USERNAME` is not the Brevo login email |
| `Sender not valid` / `unrecognised sender` | `MAIL_FROM_ADDRESS` has not been verified in Brevo |
| Mail sends but the body is blank | The template was never saved — open Message Templates and press **Save Templates** once |

The error names the username it authenticated with. If that is not the value
you just set, **restart `php artisan serve`**: the command injects the `.env`
values into the server process when it launches, and dotenv will not overwrite
variables already present in the environment, so a server left running from
before an edit keeps serving the old credentials indefinitely. Editing `.env`
is not enough; the process has to be restarted. The same applies on Render,
where saving an environment variable triggers a redeploy — wait for it to
finish before retesting.

Never commit these values. `.env` is gitignored, and Render's environment
variables are set through the dashboard.

## SMS

SMS goes through [UniSMS](https://unismsapi.com/docs/sms), a Philippine
gateway covering Globe, Smart, DITO, Sun and TNT.

```
UNISMS_KEY=your-api-secret-key
UNISMS_SENDER_ID=YOURSENDERID
UNISMS_BASE_URL=https://unismsapi.com/api
```

**Leaving `UNISMS_KEY` blank switches SMS off.** The app skips the channel and
says so; it does not fail, and no booking is affected.

`UNISMS_KEY` is sent as the HTTP Basic *username* with an empty password, not
as a bearer token. `UNISMS_SENDER_ID` is required on every message — new
accounts are issued a random one for testing, and a registered sender ID has
to be applied for before production traffic. That is a Philippine telco rule
rather than a UniSMS one, and an unregistered ID is the usual cause of a 422.

### Numbers

Any of these are accepted and normalised to `+639171234567`, with spaces,
dashes and brackets ignored:

```
09171234567    9171234567    639171234567    +639171234567
```

Anything that is not a Philippine mobile number — a landline, a foreign
number, a typo — is **skipped rather than sent**, so it cannot be silently
billed. The skip is recorded in `sms_logs` with the reason.

### Cost

SMS bills per 160-character segment, but a message that splits is billed at
**153** characters per part, because the concatenation header takes the rest.
A single non-GSM character — a smart quote pasted in from Word is the usual
culprit — drops the whole message to UCS-2, where the allowance is **70**, or
67 once split. One such character can therefore triple the cost of every
reminder you send.

The test send reports the encoding and segment count for exactly this reason:

```
✓ SMS — Queued with UniSMS (ref msg_84e8…). 148 characters, GSM-7, 1 segment.
```

### The log

Every attempt writes a row to `sms_logs`, successes and failures alike, with
the recipient, segment count, trigger and the gateway's reference. The failure
rows are the valuable ones: when a customer says no reminder arrived, a row
reading `invalid_number` or `http_401` answers it far faster than the gateway
dashboard will.

### Mail and request latency

Mail is sent synchronously, inside the request. Every send site is wrapped in
`try`/`catch` and logs on failure, so a dead SMTP server cannot fail a
booking — but it does add the SMTP round trip (typically under two seconds)
to that request. Moving mail onto the queue would remove that, and
`QUEUE_CONNECTION` is already `database` with a `jobs` table in place, but it
needs a worker process running `queue:work`. On Render that is a separate
paid Background Worker, so it is deliberately not enabled.

To check it works without making a booking:

```bash
php artisan tinker
>>> Mail::raw('test', fn ($m) => $m->to('you@example.com')->subject('APX test'));
```

### What sends mail

| Trigger | Mailable |
|---|---|
| Guest booking submitted | `BookingReceived` |
| New guest account created | `GuestAccountCreated` |
| Admin confirms a booking | `BookingConfirmed` |
| Scheduled reminders | `BookingReminder` |

In-app notifications (the bell) are separate and work regardless of mail
configuration.

---

## Deployment

Render builds from the `Dockerfile`. `docker-entrypoint.sh` runs migrations,
seeds the service catalogue, provisions the admin account from `ADMIN_EMAIL` /
`ADMIN_PASSWORD` if those are set, and caches config, routes and views before
starting Apache.

`APP_DEBUG` is `false` in `render.yaml`, so production never shows stack traces
or file paths. Keep it that way.
