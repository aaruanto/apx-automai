# Running the scheduler on Render

Written for: whoever deploys APX AutoMai.

## The problem

Every automated message in APX AutoMai is a scheduled task: appointment
reminders, service-due reminders, no-show sweeps, loyalty expiry, account
purges. All of them are declared in `routes/console.php` and all of them depend
on Laravel's scheduler ticking once a minute.

Render's free tier runs **one web service and nothing else**:

| What Laravel expects | What Render free gives you |
| --- | --- |
| A system crontab entry running `schedule:run` | No cron jobs |
| A long-lived `schedule:work` process | No background workers |
| A shell to start one in | No shell |

So the scheduler never ticked in production. Nothing errored, nothing appeared
in the logs — reminders simply never went out. Silence is the failure mode,
which is why it went unnoticed.

Render does offer cron jobs and background workers on paid plans. If the project
moves to one, use those instead and skip the rest of this document; the
schedule itself does not change.

## The fix

An external cron service calls a token-guarded endpoint, and Laravel decides on
each call which commands are actually due. The frequencies stay in
`routes/console.php` where they belong; the outside cron is a dumb heartbeat
that knows nothing about the schedule.

```
cron-job.org  ──every 5 min──▶  GET /cron/schedule?token=…  ──▶  schedule:run
```

### 1. Generate a token

```bash
php artisan apx:cron-token
```

It prints one value and does not write it anywhere. Do not commit it.

### 2. Set it on Render

Render dashboard → your service → **Environment** → add:

```
APX_CRON_TOKEN = <the generated value>
```

Save. Render redeploys. Until this variable is set the endpoint returns **404**,
on purpose: a deploy that forgets it must not leave an unauthenticated URL that
anyone can use to make the app send mail.

### 3. Point a cron service at it

Any of these work on a free plan. [cron-job.org](https://cron-job.org) is the
simplest:

| Field | Value |
| --- | --- |
| URL | `https://<your-app>.onrender.com/cron/schedule?token=<the token>` |
| Schedule | Every 5 minutes |
| Method | GET |

Prefer the header form if your cron service supports custom headers, since a
query string ends up in access logs:

```
X-Cron-Token: <the token>
```

### 4. Confirm it is working

Two independent checks:

- **Admin → Message Templates** has a Scheduler card. It reads a real heartbeat
  timestamp written on every tick, so "Running" means something actually ran.
  It used to be hard-coded markup that claimed "Running" unconditionally.
- Call the URL yourself. A healthy response looks like:

  ```
  ok schedule in 240ms
  ```

## Why every 5 minutes

Everything in `routes/console.php` lands on a five-minute boundary, so a
five-minute tick catches all of it. `schedule:run` only runs tasks due in the
*current minute*, so a cron interval that drifts off those boundaries would
silently skip the daily jobs — which is the trap to avoid here.

| Task | Frequency |
| --- | --- |
| `reminders:send` | hourly |
| `reminders:maintenance` | daily, 08:00 |
| `bookings:cancel-no-shows` | every 5 minutes |
| `accounts:purge-expired` | daily, 03:00 |
| `loyalty:expire` | daily, 03:30 |
| heartbeat | every 5 minutes |

One side effect worth knowing: a request every five minutes keeps the Render
free instance from idling, so the site stays warm. Free web services get 750
instance hours a month against roughly 730 hours in a month, so always-on fits
— but only for a single service.

## Why the endpoint is safe to expose

- **Token compared in constant time** with `hash_equals`.
- **404, not 403**, on a bad or missing token, and the token is never logged.
- **Rate limited** to 10 requests a minute per IP.
- **Allow-list, not a parameter.** The URL segment is a key into a fixed map
  (`schedule` → `schedule:run`). If it were a command name taken from the
  request, a leaked token would be the ability to run any artisan command on
  production, `migrate:fresh` included.
- **Outside the maintenance middleware** by design, so reminders for bookings
  the shop has already taken keep going out while the public site is switched
  off.

## Why duplicate sends are not a worry

`reminders:send` matches bookings by date, and a date stays true all day. With
no memory, a booking seven days out would be mailed on every single tick —
about 1,440 identical emails a day against Brevo's 300/day limit.

Every send now claims a row in the `reminders` table first, keyed on a
`dedupe_key` with a UNIQUE constraint, so the **database** decides who wins
rather than a read-then-write in PHP that two overlapping ticks can both pass.
If the claim is rejected, the reminder has already gone out and nothing is sent.
A failed send releases its claim so the next run can retry.

This is why the de-duplication had to be built before the scheduler was ever
allowed to run.
