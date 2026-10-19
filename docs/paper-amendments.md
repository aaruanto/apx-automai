# Paper amendments — where the manuscript and the system differ

Written for: the APX AutoMai capstone team (Galero, Guevarra, Pineda, Ruanto),
for the chapter revisions and for answering panel questions.

Every line below is a place where the manuscript describes something other than
what the code does. Each one is either **fixed in the system** (the paper was
right, the build was behind) or **needs a paper edit** (the build is right, the
paper is out of date). Nothing here is left as "to be decided".

---

## 1. Automated reminders — FIXED IN THE SYSTEM

**The paper said:** the system notifies customers of upcoming appointments
*and* when their vehicle is due for scheduled maintenance.

**What existed:** only appointment reminders, and they never ran in production
(see §2). There was no notion of a service interval, no record of when a vehicle
was last serviced, and nothing that could decide a vehicle was due.

**What was built:**

- `maintenance_rules` — service intervals in months and/or kilometres, each
  pointing at the PMS package to recommend, with a lead time for how far ahead
  to warn.
- `vehicles.last_service_date` — stamped automatically whenever a booking for
  that vehicle is marked completed, so it is correct without anyone maintaining
  it. It only ever moves forward, so a backdated walk-in cannot rewind a
  vehicle's history.
- `vehicles.mileage` / `mileage_at_last_service` — **optional**. Distance since
  the last service is a subtraction rather than a guess about how far the
  customer drives. A vehicle with no reading is still reminded, on time alone.
- `reminders:maintenance` — one message per vehicle per interval, for the most
  urgent rule outstanding. A car behind on three things gets one email, not
  three.
- The customer dashboard shows the same due status the email is based on, so
  the screen and the inbox cannot disagree.

The paper's claim now holds. Three default rules ship seeded: Basic PMS at
6 months / 5,000 km, Standard at 12 months / 10,000 km, Major at 24 months /
40,000 km.

**One design choice worth defending at the panel:** the system never *requires*
mileage. Most customers do not know their odometer reading, and a form that
demands it is a form they abandon. Time intervals always apply; distance only
ever brings a service forward, never pushes it back, because oil ages in the
sump whether the car is driven or not.

---

## 2. The scheduler never ran — FIXED IN THE SYSTEM

**The paper said:** reminders are sent automatically, without staff action.

**What existed:** the schedule was declared correctly in `routes/console.php`,
but Render's free tier has no cron jobs, no worker process and no shell to start
`schedule:work` in. Nothing ticked. Nothing errored either — the failure mode is
silence, which is why it was not noticed.

**What was built:** a token-guarded `/cron/schedule` endpoint that an external
cron service calls every five minutes, with Laravel still deciding what is due.
Full setup in [`docs/scheduler-on-render.md`](scheduler-on-render.md).

**Also fixed, and the reason the above had to wait:** the reminder command had
no memory. Its windows match on a date, which stays true all day, so a booking
seven days out would have been mailed on *every tick* — roughly 1,440 identical
emails a day against Brevo's 300/day limit. The scheduler never running was the
only thing preventing it. Sends now claim a row in a new `reminders` table under
a UNIQUE `dedupe_key`, so the database enforces exactly-once rather than a
read-then-write two overlapping ticks could both pass.

If the paper describes the deployment as using a system crontab, that sentence
needs changing to describe the external trigger.

---

## 3. `activity_logs` → `audit_logs` — AMEND THE PAPER

Table 4 names `activity_logs`. The table is called **`audit_logs`** and does the
same job: who did what, the old values, the new values, and the reason.

Renaming the table to match the paper was considered and rejected: it is
referenced throughout the admin code and a rename buys a word. Change the
manuscript, and change the column list with it — `audit_logs` carries
`actor_name` (denormalised so a log entry survives the account being deleted),
`old_values`/`new_values` as JSON, and `reason`.

---

## 4. `service_categories` — AMEND THE PAPER

Table 4 lists a `service_categories` table. There is none. A category is a
**string column on `services`** (`services.category`).

This is the right call for the data: the landing-page filters and the admin
settings screen both read the column directly, there are a handful of fixed
categories, and a separate table would add a join to every service query to
store a name. Describe it as a column in the paper.

Related: the paper may imply the guided booking paths (General Check-up / PMS /
Browse All) are categories. They are not — they are defined in `config/apx.php`
as a *view* of existing data, deliberately not a re-filing of it, because a
service can reasonably be both routine maintenance and something you browse to
directly.

---

## 5. MySQL → PostgreSQL, Redis → database queue — AMEND THE PAPER

**The paper said:** MySQL for the database, Redis for the queue/cache.

**What is deployed:**

| Concern | Paper | Reality |
| --- | --- | --- |
| Database (production) | MySQL | **PostgreSQL 18** on Render |
| Database (development) | MySQL | **SQLite** (`database/database.sqlite`) |
| Queue | Redis | **No queue worker.** Post-response work uses `dispatch(…)->afterResponse()` |
| Cache | Redis | **Database/file cache** |

Render's free tier offers managed PostgreSQL and no managed Redis, which decided
both. The code is database-agnostic Eloquent, so this is a deployment fact
rather than a design change — but the paper should state what was actually
deployed and defended, not what was planned.

The queue point is worth a sentence of its own. Confirmation emails are sent
*after* the HTTP response is flushed, in the same process. A customer used to
wait about seven seconds on a "Submitting…" button while two SMTP round trips
completed — after their booking had already been saved. A hung Confirm button is
how you lose a booking that already exists. `afterResponse()` gets the same
result as a queue worker with nothing extra to pay for or keep alive.

---

## 6. Loyalty points are a ledger, not a column — AMEND THE PAPER

If the paper describes `users.loyalty_points` and `users.tier`, that is not how
it works. Points live in **`loyalty_ledger`**, one row per movement, with the
balance *derived* from the history rather than stored alongside it.

Why this matters enough to rewrite the paragraph: a stored balance can disagree
with its own history, and when it does there is no way to tell which is wrong.
A ledger cannot. Lots are consumed FIFO with `remaining` tracked per lot,
redemption locks rows before reading them, and a unique constraint on
`(booking_id, reason)` makes earning idempotent so completing a booking twice
cannot mint points twice.

There are no tiers.

---

## 7. There is no staff portal — AMEND THE PAPER

The only roles are **admin** and **customer**. Mechanics are *records*, not
accounts: a name and a contact number so work can be assigned and someone can
be rung about it. There is no mechanic login.

If the paper describes a three-role system or a staff-facing portal, that
section needs removing rather than softening.

---

## 8. Tables in the schema that the paper should not claim

Two tables exist in the database and are read and written by **nothing**:

- `payments`
- `notification_logs`

They are migration leftovers. They are deliberately **excluded from the ERD** in
`docs/diagrams/`, because putting unused tables in a diagram invites a panel
question with no good answer. Do not describe payment processing as a feature —
the system records prices, and money changes hands at the counter.

`rewards` and `employees` are in the same family; `employees` was renamed to
`mechanics`.

---

## Figures to regenerate

All four diagrams in [`docs/diagrams/`](diagrams/) have been updated to match
the system as it now stands, including `maintenance_rules`, `reminders`,
`vehicles.last_service_date` and the external cron trigger.

| File | Use |
| --- | --- |
| `erd.mmd` | Proper `erDiagram`. Best for a paper figure via [mermaid.live](https://mermaid.live). |
| `erd-flowchart.mmd` | Same relationships in flowchart syntax. **Use this for Excalidraw** — it imports as editable shapes, whereas `erDiagram` imports as a flat image. |
| `dfd-context.mmd` | Level 0. |
| `dfd-level1.mmd` | Level 1. Process 8.0 (Maintenance Due Detection) is new. |
