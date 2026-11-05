# APX AUTOMAI — working notes for Claude

Read this before changing anything. It is the context a new session does not
have, and most of it was learned the expensive way.

## What this is

A vehicle service booking system for **APX Motors Service Center**, Tandang
Sora, Quezon City. Final-year capstone by Galero, Guevarra, Pineda and Ruanto;
adviser Mr. Erwin Mendoza. It has a real client and a real deployment, so
"it works on my machine" is not the bar.

Laravel 12 + Blade. Bootstrap 5 on the landing page; hand-rolled CSS on the
admin and customer dashboards. SQLite locally, PostgreSQL on Render, shipped as
a Docker image.

## Rules that are not negotiable

**Never print, log, echo or commit a credential.** Not the SMTP password, the
Semaphore key, or the cron token. Read them from `.env` or `config()`, never
hardcode, and never paste a value into a reply — refer to variables by name.
`.env.example` carries variable *names* only. If a key ever reaches Git, say so
immediately so it can be rotated; history keeps it even after the line is
deleted.

**Money and points are never trusted from the browser.** Totals, discounts and
redemptions are computed and validated server-side, inside transactions. The
request says what the customer *wants*, never what it costs.

**Do not run destructive migrations or delete data without asking.** This
includes `migrate:fresh` anywhere but a local SQLite file.

**Do not change unrelated functionality.** Fix a shared component rather than
duplicating it.

**Tests must pass before anything is pushed.** `php artisan test` — currently
1,051. A push to `main` deploys to the live site, so a broken push is a broken
demo.

## Verify, don't assume

Claims about this codebase have been wrong before in ways that cost real time.
Run the thing. Render the page and look at it. Query the database. A passing
assertion about behaviour beats a careful reading of the source, and a
screenshot beats both when the question is visual.

Comments here explain **why**, usually including what broke before. Keep that
style; it is the reason the traps below are known at all.

## Traps that have already cost us

**Mail config.** Laravel 11+ reads `MAIL_SCHEME`, not `MAIL_ENCRYPTION`.
Setting the latter does nothing.

**Render blocks SMTP ports.** Outbound 25, 465 and 587 are dropped at the
firewall on free instances, so Gmail SMTP can never work there. Brevo on port
**2525** is what this project uses. The failure looks like a connection
timeout, which reads like wrong credentials and is not.

**Render's free tier has no cron, no workers and no shell.** Nothing scheduled
runs on its own. An external cron service calls `/cron/schedule?token=…` every
five minutes and Laravel decides what is due. Keep every scheduled frequency on
a five-minute boundary or it will never fire.

**Semaphore** takes its key as an ordinary form field named `apikey`, returns an
array, and **silently discards any message starting with "TEST"** — reporting
success and delivering nothing.

**A Semaphore account cannot send without an approved sender name.** Credits
and sender names are separate things; topping up grants nothing. With none
registered, every call fails with HTTP 500 and `No active sender name found`,
which reads like a key or billing problem and is neither. Leaving
`SEMAPHORE_SENDER_NAME` unset is correct — Semaphore then uses the name
registered to the account — but *some* name has to be approved there first.
The first one is free, is applied for in their dashboard, and **cannot be
changed afterwards**. Ours is `APXMOTORS`, which is why the SMS bodies say
"APX Motors" while the email branding stays "APX AUTOMAI".

**One non-GSM-7 character triples SMS cost.** An em-dash or a `₱` forces the
whole message to UCS-2, cutting a segment from 160 characters to 70. The
configured branch name contains an em-dash. `SmsService::gsmSafe()` maps the
offenders; measure segments *after* it, never before.

**Reminders must be de-duplicated.** Day windows match on a date, which stays
true all day, so without a claim in the `reminders` table one booking is mailed
on every tick — about 1,440 times a day against a 300/day limit. The UNIQUE
`dedupe_key` is what makes a send exactly-once; never bypass `ReminderLedger`.

**Seeders run on every container start.** They must create what is missing and
never overwrite what exists, or a price the shop corrected in Admin is silently
reset within the hour.

**`php artisan serve` reads `.env` at launch.** Editing `.env` does nothing
until you restart it.

**A fresh clone has no SQLite file.** `touch database/database.sqlite` before
`php artisan migrate`, or it fails with a message that reads like a
misconfiguration.

**Browsers cache images by filename.** Replacing an image's contents without
changing its name serves the old one to anyone who has visited before.

## Traps in the tests

- **Weekends close at noon.** A test booking an afternoon slot on
  `today()->addDays(n)` passes most of the week and fails on the rest. Use
  `nextWeekday()` / `nextWeekdayAfter(n)` from `tests/Pest.php`.
- **`actingAs` persists for the whole test.** A signed-out assertion after a
  signed-in one is still signed in. Split them into separate tests.
- **`Mail::fake()` ignores `Mail::html()`.** `MailFake::sendMail` returns early
  for anything that is not a Mailable, so `assertSentCount` reads zero however
  much mail was sent. Use the array transport and read
  `Mail::getSymfonyTransport()->messages()`.
- **`afterResponse` work re-runs on the next request in the same test.**
  `dispatch(...)->afterResponse()` registers an application *terminating*
  callback, and `Application::terminate()` never clears that list. A test that
  makes two requests therefore runs the **first** request's closure again on
  the second, so the mailbox shows the first booking's mail twice. A real
  request gets its own process, so this is an artifact of reusing one
  application — but it makes any mail count spanning two requests meaningless.
  Set up the prior state by creating the rows, not by making the first request.

## How the important parts fit together

- **`BookingPricing`** is the single source of truth for what a booking costs.
  Prices are snapshotted into `booking_service.price` so completing or editing a
  booking cannot silently re-price history.
- **`LoyaltyService` + `loyalty_ledger`** — points are a ledger, not a column.
  The balance is derived from its own history, lots are consumed FIFO, and a
  unique `(booking_id, reason)` makes earning idempotent. There are no tiers.
- **`ReminderLedger`** makes every reminder exactly-once, for both appointment
  and maintenance reminders.
- **`SmsService` + `App\Services\Sms`** — `SMS_DRIVER` picks who carries a
  text: `log`, `unisms` or `semaphore`. `SmsService` owns everything true of
  any provider (GSM-7 folding, number normalisation, segments, the
  `sms_logs` row, the ok/failed shape); a gateway owns only the HTTP call.
  **The default is `log`**, which runs every rule and contacts nobody, so a
  fresh clone cannot text a real customer and reminder logic can be debugged
  without spending a credit per attempt. An unknown value falls back to
  `log` rather than throwing. Add a provider by implementing `SmsGateway`
  and naming it in `AppServiceProvider::register()` — never by editing
  `SmsService`, which is the part that must not differ between providers.
- **`MaintenanceDue`** decides whether a vehicle is due. The scheduled command
  and the customer dashboard both call it, so the screen and the inbox cannot
  disagree.
- **Roles are `admin` and `customer`, and that is all.** Mechanics are records
  with a name and a number, not accounts. There is no staff portal.

## Temporary, and meant to be removed

**The demo accounts panel.** `DEMO_ACCOUNTS` makes `/login` print two working
logins on the page and `apx:demo-accounts` provision them on every boot. It
exists for the capstone demonstration and nothing else; it publishes a working
admin password to anyone who opens the sign-in page. Off unless set, and that
is the state to leave it in. Winding it down is `apx:demo-accounts --prune`
*while the variables are still set*, then `DEMO_ACCOUNTS=false` — README has
the order and the reason. Do not build anything on it.

## Things that exist but must not be used

`payments` and `notification_logs` are migration leftovers that nothing reads
or writes. Do not build on them, and do not put them in a diagram. `rewards`
and the old `employees` table (now `mechanics`) are the same story.

## Where the written-down answers are

| Question | File |
| --- | --- |
| Setting up, daily routine | `docs/getting-started.md` |
| Mail, SMS, scheduler, deployment | `README.md` |
| Running the scheduler on Render | `docs/scheduler-on-render.md` |
| Where the paper and the system disagree | `docs/paper-amendments.md` |
| ERD and data flow diagrams | `docs/diagrams/` |

## Known-sloppy things, if you are looking for work

- The brand mark is stretched on every public page. `apx-black-logo-240.png`
  is square and the navbar renders it at `width="100" height="120"`, so it is
  drawn 20% taller than its true proportions. Undistorting it means choosing a
  square box — 100 or 120 — and either choice changes how the navbar looks, so
  it is a decision for the client rather than a fix. The 1.3 MB original is
  still in `public/assets/img/` as the master; nothing serves it.
- `CONTRIBUTING.md` still describes a `node_modules` cleanup that has already
  been done.
- The public pages load Font Awesome 5 while the admin side loads 6. A 6-only
  icon name — `fa-xmark` rather than `fa-times` — renders a correctly sized,
  perfectly invisible glyph on a public page. No current icon is affected;
  every `fa-xmark` is on an admin screen. The trap is for the next person.
- The landing page 404s on every load for a Cloudflare `email-decode` script
  baked into the template. Harmless, and it fails on the live site too.
- The bookings list loads every booking and filters in the browser. Fine at
  today's volume; server-side filtering would have to bring pagination with it.

The invented statistics, the footer address, the 2025 copyright year, the
0-byte favicon and the 12px phone overflow were all on this list and are all
fixed. The overflow is worth a note, because what is written here about it was
wrong: it was not the template's absolutely positioned badges. `g-5` gives a
row -24px of margin per side while `.container` pads 12px, and above 576px the
container's max-width absorbs the difference — below it there is no slack and
12px hangs off each edge. The badges were a separate fault found while looking
for that one.
