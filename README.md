# APX AutoMai

Vehicle service booking system for APX Motors and Services, Quezon City.
Laravel 12 + Blade + Bootstrap. SQLite locally, PostgreSQL on Render.

---

## Working as a team

**Setting up for the first time?** Follow
[`docs/getting-started.md`](docs/getting-started.md) — every command in it was
run against a clean clone, in that order, and ends with the full test suite
passing.

The code lives on GitHub. Everything else does not, and that is deliberate.

### What GitHub carries

Code, migrations, seeders, tests, docs. Clone it and follow **Local setup**
above.

### What GitHub does NOT carry, and must never

| Not in the repo | Why | What to do |
|---|---|---|
| `.env` | Holds the SMTP password, the SMS key and the cron token | Copy `.env.example` and fill in your own |
| `database/database.sqlite` | Everyone's local data is their own | `php artisan migrate` then the seeders |
| `/vendor`, `/node_modules` | Rebuilt from lock files | `composer install`, `npm install` |
| `/storage` contents | Logs and generated files | Created on first run |

**Never put a real secret in the repo, in a chat, or in the paper** — not in
`.env.example`, not in a screenshot, not in an appendix. `.env.example` holds
variable *names* only. If a key is ever committed, say so immediately and
rotate it: Git keeps history, so deleting the line later does not remove it.

Each person gets their own local `.env`. Only the deployed environment needs
the real production values, and those live in Render's **Environment** tab.

### Day-to-day

```bash
git pull                 # before you start, every time
# ...work...
php artisan test         # must pass before you push
git add -A
git commit -m "what changed and why"
git push
```

Two rules that prevent most of the pain:

- **Pull before you start.** Most merge conflicts are two people editing from
  different starting points.
- **Small, frequent commits** beat one large one at the end of the week.

If you both edited the same file, Git will say so on pull. Open the file, keep
the correct version of the marked section, delete the `<<<<<<<` markers, then
commit. Nothing is lost; Git stops rather than guessing.

### After pulling someone else's work

```bash
composer install     # if composer.lock changed
php artisan migrate  # if anyone added a migration
```

A new migration from a teammate will not apply itself, and the symptom is a
confusing "column not found" error rather than a clear one.

### Who deploys

Render deploys automatically from `main`, so **a push to `main` goes live.**
Agree on who does that, and run `php artisan test` first.

### When Git goes wrong

Git does not lose committed work. A bad pull or a conflict can make the
working copy look wrong, but every commit still exists and can be recovered.
The one way to genuinely lose work is to leave it **uncommitted** and then pull
on top of it. So commit often, even if you are not ready to push.

**Where conflicts actually happen here.** Three view files carry their own CSS
and JavaScript inline and are over a thousand lines each:
`resources/views/dashboard/customer-dashboard.blade.php`,
`resources/views/index.blade.php` and `resources/views/layouts/admin.blade.php`.
Two people in the same one on the same day will conflict. Two people in
different files never will. Divide the work by file and most of this section
never comes up.

#### "I have unfinished changes and need to pull"

```bash
git stash          # put your changes aside
git pull
git stash pop      # put them back on top
```

#### "I got a conflict"

Git names the files. Open each one and look for:

```
<<<<<<< HEAD
your version
=======
their version
>>>>>>> main
```

Keep what belongs — often both parts — and delete the three marker lines. Then:

```bash
git add .
git commit         # finishes the merge
```

Nothing is lost here. Git stopped precisely so it would not have to guess.

#### "This merge is a mess, I want out"

```bash
git merge --abort      # back to exactly before the pull
```

#### "I broke my working copy and want today's last commit back"

```bash
git checkout -- .      # discards UNCOMMITTED changes - they are gone
```

#### "I committed something broken"

```bash
git revert <commit>    # a new commit undoing it; safe once pushed
```

Avoid `git reset --hard` and `git push --force` on a shared branch. Both rewrite
history that someone else may already have pulled, and that is the one way to
make a mess that is genuinely hard to undo.

#### "I think everything is lost"

It is not. `git reflog` lists every commit your repository has pointed at,
including ones no branch mentions any more. Find the commit from before things
went wrong and `git checkout <hash>`. Ask before running anything destructive.

#### Lock files

A conflict in `composer.lock` or `package-lock.json` is not worth resolving by
hand. Take one side and regenerate:

```bash
git checkout --theirs composer.lock && composer install
```

---

## Local setup

```bash
composer install
npm install && npm run build
cp .env.example .env          # then fill in the values below
php artisan key:generate

# Create the SQLite file before migrating. It is gitignored, so a fresh
# clone does not have one, and `migrate` fails with
# "Database file at path [...] does not exist" rather than offering to
# create it.
touch database/database.sqlite        # Windows PowerShell: New-Item database/database.sqlite

php artisan migrate

# Reference data, not sample data. Without it the landing page, the booking
# flow and the admin screens all come up empty and the app looks broken.
php artisan db:seed --class=ServiceSeeder
php artisan db:seed --class=MaintenancePackageSeeder
php artisan db:seed --class=MaintenanceRuleSeeder

php artisan serve
```

`php artisan db:seed` runs all three in the right order if you prefer. Every
seeder creates only what is missing and never overwrites a row that already
exists, so running them again is always safe.

There is no public route to an admin account by design. Create the first one
from the environment:

```bash
php artisan apx:ensure-admin --email=you@example.com --password='a-strong-password'
```

Run the test suite with `php artisan test`.

---

## The scheduler

Everything automatic in this app is a scheduled job (see `routes/console.php`):

| Job | Frequency | What it does |
|---|---|---|
| `reminders:send` | hourly | appointment reminders at 7d / 3d / 1d (2h is off by default) |
| `reminders:maintenance` | daily, 08:00 | tells customers their vehicle is due for servicing |
| `bookings:cancel-no-shows` | every 5 min | cancels bookings past their grace period |
| `accounts:purge-expired` | daily, 03:00 | anonymises accounts past the 30-day deletion window |
| `loyalty:expire` | daily, 03:30 | retires loyalty points past their expiry date |
| heartbeat | every 5 min | records `scheduler_last_run` so the admin screen reports real status |

Nothing here is finer-grained than five minutes, because the production trigger
fires on five-minute boundaries. `schedule:run` only runs jobs due in the
*current minute*, so a job scheduled off those boundaries would never fire.

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

Render's free tier has no cron jobs, no background workers and no shell, so
neither of the above is available. Its paid **Cron Job** service would work,
but is not necessary.

Instead this app exposes a token-guarded endpoint that runs the scheduler, and
a free external cron service calls it. Laravel still decides what is due, so
the frequencies stay in `routes/console.php`.

```
cron-job.org  ──every 5 min──▶  GET /cron/schedule?token=…  ──▶  schedule:run
```

1. Generate a secret: `php artisan apx:cron-token` (or use Render's **Generate**
   button on the variable).
2. Set it as `APX_CRON_TOKEN` in the Render **Environment** tab and redeploy.
3. Create one job at [cron-job.org](https://cron-job.org), every 5 minutes,
   pointing at `https://<your-app>.onrender.com/cron/schedule?token=<token>`.

Without `APX_CRON_TOKEN` set the endpoint returns 404 rather than running, so a
deploy that forgets the variable cannot leave an open way to make the app send
mail. The token may also be passed as an `X-Cron-Token` header, which keeps it
out of access logs.

Check it worked under **Admin → Message Templates**: the Scheduler card reads a
real heartbeat timestamp, so "Running" means something actually ran.

Full detail, including why duplicate sends are not a worry, is in
[`docs/scheduler-on-render.md`](docs/scheduler-on-render.md).

### Queue worker

Not needed. Mail that would otherwise delay a response is sent with
`dispatch(…)->afterResponse()`, which runs after the response is flushed in the
same process — no worker to pay for or keep alive.

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

SMS goes through [Semaphore](https://semaphore.co/docs), a Philippine gateway
routed domestically, reaching Globe, Smart, Sun and DITO.

```
SEMAPHORE_KEY=your-api-key
SEMAPHORE_SENDER_NAME=
SEMAPHORE_BASE_URL=https://api.semaphore.co/api/v4
```

**Leaving `SEMAPHORE_KEY` blank switches SMS off.** The app skips the channel
and says so; it does not fail, and no booking is affected.

`SEMAPHORE_KEY` is sent as an ordinary form field named `apikey` — not a
header, not a bearer token. `SEMAPHORE_SENDER_NAME` is **optional**: left
blank, Semaphore uses the sender name registered to the account, which reaches
every network with no registration of your own. Setting it to an unregistered
name is the usual cause of a 422.

### Why not UniSMS

The project was built against UniSMS first. Its free sender ID stopped
reaching Smart, TNT and Sun — Philippine carriers require alphanumeric sender
IDs to be registered per network, and registering one takes 2–4 weeks plus a
letter of authorisation per carrier. Semaphore's default sender name is
already registered across all of them.

That swap touched only the HTTP call. Number normalisation, segment counting,
`sms_logs` and the admin test send are provider-independent and were unchanged;
the UniSMS implementation is in the git history if its coverage returns.

### Messages beginning with "TEST"

Semaphore **silently discards** any message whose body starts with `TEST`: the
API returns an ordinary success and nothing is delivered. `SmsService` refuses
such a message up front and says why, rather than letting it look sent. Keep
it in mind when writing templates.

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
✓ SMS — Queued with Semaphore (id 123456). 148 characters, GSM-7, 1 segment.
```

### The log

Every attempt writes a row to `sms_logs`, successes and failures alike, with
the recipient, segment count, trigger and the gateway's reference. The failure
rows are the valuable ones: when a customer says no reminder arrived, a row
reading `invalid_number`, `test_prefix` or `http_402` answers it far faster than the gateway
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
