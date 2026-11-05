# Diagrams

Mermaid source, written to be imported into Excalidraw. Each `.mmd` file is one
diagram and is copy-pasteable on its own.

There are **two sets of DFDs of the same system**. Pick by audience:

- **`*-simple.mmd`** — for the defense and the slides. Six processes, eight
  datastores, arrow labels that are data names. This is what you put on screen.
- **everything else** — for the appendix and for answering "where is that in
  the code?". Nine processes, twelve datastores, full prose labels, and a
  class-and-method map in every file header.

They describe the same build. The simple set merges processes and groups
datastores; it does not contradict the detailed set. **The D-numbers differ
between the two — see the crosswalk below before quoting one in the paper.**

## Legend

Stated once here; the files use these conventions without restating them.

| Shape | Mermaid | Means |
| --- | --- | --- |
| Stadium / rounded | `P1(["1.0 Name"])` | Process |
| Rectangle | `CUST["Customer"]` | External entity |
| Cylinder | `D4[("D4 Bookings")]` | Datastore |
| Double-headed arrow | `P1 <-->|"label"| D1` | The process both reads and writes that store |

Processes are numbered so a level traces to its parent. Every arrow is
labelled with the data that moves on it.

## The defense set

| File | Diagram | Arrows |
| --- | --- | --- |
| `dfd-context-simple.mmd` | Level 0 context | 12 |
| `dfd-level1-simple.mmd` | Level 1, processes 1.0 to 6.0, datastores D1 to D8 | 56 |
| `dfd-level2-booking-simple.mmd` | Level 2 of 2.0 — booking and pricing | 27 |
| `dfd-level2-reminders-simple.mmd` | Level 2 of 5.0 — the scheduled reminder chain | 22 |

Each of these four carries a **sixty second narration** in its header comment:
one line per process, in the order you would say them out loud. Comments do not
render, so it stays out of the image.

The two Level 2s are the ones chosen on purpose. They are the two questions a
panel reliably asks:

- *"What stops someone editing the price in the browser?"* →
  `dfd-level2-booking-simple.mmd`. Every arrow leaving the browser carries a
  **choice**, never a figure. Point at them.
- *"The clock runs every five minutes and the window is a whole day — why not
  1,440 duplicate texts?"* → `dfd-level2-reminders-simple.mmd`. Process 5.3 is
  the answer, and it sits on the path rather than being implied.

## The detailed set

| File | Diagram | Arrows |
| --- | --- | --- |
| `dfd-context.mmd` | Level 0 context, full labels | 12 |
| `dfd-level1.mmd` | Level 1, processes 1.0 to 9.0, datastores D1 to D12 | 85 |
| `dfd-level2-booking-intake.mmd` | Level 2 of 2.0 — availability, guest vs authenticated, vehicle selection | 37 |
| `dfd-level2-pricing-redemption.mmd` | Level 2 of 3.0 — server-side totals, snapshots, FIFO point lots | 31 |
| `dfd-level2-scheduled-notifications.mmd` | Level 2 of 7.0 with 6.1 and 6.2 — cron tick to dedupe claim to gateway | 44 |
| `dfd-level2-booking-lifecycle.mmd` | Level 2 of 4.0 — status ladder, cancellation, reschedule, audit | 47 |

## ERD

| File | Diagram |
| --- | --- |
| `erd.mmd` | `erDiagram`, authoritative, full types and PK/FK/UK markers |
| `erd-flowchart.mmd` | The same entities as a flowchart, for editable Excalidraw shapes |

One ERD serves both audiences; it was not simplified.

## Datastore crosswalk

| Simple set | Detailed set | Tables |
| --- | --- | --- |
| D1 Users | D1 | `users`, `customers` |
| D2 Vehicles | D2 | `vehicles` |
| D3 Catalog | D3 + `mechanics` | `services`, `bundles`, `bundle_service`, `mechanics` |
| D4 Bookings | D4 + D12 | `bookings`, `booking_service`, `booking_reschedule_requests` |
| D5 Loyalty Ledger | D5 | `loyalty_ledger` |
| D6 Reminder and Message Log | D6 + D8 | `reminders`, `sms_logs` |
| D7 Settings and Rules | D9 + D7 | `settings`, `maintenance_rules` |
| D8 Audit and Deletions | D10 + D11 | `audit_logs`, `account_deletions` |

The grouping also fixes a wart in the detailed set: `mechanics` had no
D-number there, because the agreed list ran D1 to D12 and had no slot for it.
Here it sits in D3 with the rest of the catalog.

Note that an earlier version of `dfd-level1.mmd` numbered `audit_logs` as D6.
Three numbering schemes now exist in the git history. Only the two in the table
above are current.

## What the simple set merges

| Detailed | Simple | Why it is honest |
| --- | --- | --- |
| 2.0 intake + 3.0 pricing | 2.0 Booking and Scheduling | A customer never prices a booking as a separate act; it happens inside one submission |
| 6.0 maintenance detection + 7.0 dispatch | 5.0 Reminders and Notifications | Detection only ever runs to feed a reminder, or the dashboard panel that mirrors it |
| 8.0 catalog/settings + 9.0 reporting | 6.0 Administration and Reports | Both are the admin reading and writing configuration and figures |

Nothing was dropped to reach six processes — 1.0, the booking lifecycle and
loyalty all survive intact, renumbered 1.0, 3.0 and 4.0.

## Datastore numbering

`D1` to `D12`, used by the detailed set only:

| | |
| --- | --- |
| D1 | `users`, `customers` |
| D2 | `vehicles` |
| D3 | `services`, `bundles`, `bundle_service` |
| D4 | `bookings`, `booking_service` |
| D5 | `loyalty_ledger` |
| D6 | `reminders` |
| D7 | `maintenance_rules` |
| D8 | `sms_logs` |
| D9 | `settings` |
| D10 | `audit_logs` |
| D11 | `account_deletions` |
| D12 | `booking_reschedule_requests` |

## Deliberately absent

`payments`, `notification_logs`, `rewards` and the old `employees` table exist
in the schema and are migration leftovers that nothing reads or writes. They
are not in any diagram, and should not be added to one. Laravel's own framework
tables — `sessions`, `password_reset_tokens`, `cache`, `jobs` and the rest —
are also left out, as is the `notifications` table behind the in-app bell.

## Verifying a change

Parsing is the gate. A diagram that will not parse will not import. Bare `%%`
comment lines break Mermaid, because its comment-stripping regex requires at
least one character after the `%%`, so write `%% -` for a blank comment line.
