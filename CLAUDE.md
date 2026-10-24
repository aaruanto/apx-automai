# APX AutoMai — working notes for Claude

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
553. A push to `main` deploys to the live site, so a broken push is a broken
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
"APX Motors" while the email branding stays "APX AutoMai".

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

## How the important parts fit together

- **`BookingPricing`** is the single source of truth for what a booking costs.
  Prices are snapshotted into `booking_service.price` so completing or editing a
  booking cannot silently re-price history.
- **`LoyaltyService` + `loyalty_ledger`** — points are a ledger, not a column.
  The balance is derived from its own history, lots are consumed FIFO, and a
  unique `(booking_id, reason)` makes earning idempotent. There are no tiers.
- **`ReminderLedger`** makes every reminder exactly-once, for both appointment
  and maintenance reminders.
- **`MaintenanceDue`** decides whether a vehicle is due. The scheduled command
  and the customer dashboard both call it, so the screen and the inbox cannot
  disagree.
- **Roles are `admin` and `customer`, and that is all.** Mechanics are records
  with a name and a number, not accounts. There is no staff portal.

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

- The landing page carries invented statistics from the original template —
  "15 Years Experience", "4.8/5 from 500+ Reviews", "5,000+ Happy Customers" —
  now sitting next to real photographs of the shop.
- The footer shows `hello@apxautomai.com`, while the shop's own sign says
  `Hi@APXMOTORS.COM`, and the copyright year is 2025.
- `apx-black-logo.png` is 1.3 MB and loads on every page. `favicon.ico` is a
  0-byte file.
- A 12px horizontal overflow at phone width, from the template's absolutely
  positioned badges.
- `CONTRIBUTING.md` still describes a `node_modules` cleanup that has already
  been done.
