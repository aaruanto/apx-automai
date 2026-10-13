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

To send real email through Gmail, add these to `.env` locally and to the
**Environment** tab on Render:

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=your-address@gmail.com
MAIL_PASSWORD=your-16-character-app-password
MAIL_FROM_ADDRESS=your-address@gmail.com
MAIL_FROM_NAME="APX AutoMai"
```

`MAIL_PASSWORD` must be a Google **App Password**, not the account password.
Generate one at <https://myaccount.google.com/apppasswords>; it requires
2-Step Verification to be on. Remove the spaces Google shows in it.

Never commit these values. `.env` is gitignored, and Render's environment
variables are set through the dashboard.

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
