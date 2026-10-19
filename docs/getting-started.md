# Getting started

Written for: Galero, Guevarra and Pineda — first-time setup on your own machine.

Every command below was run against a clean clone before this was written, so
the order is the order that actually works. It ends with 517 passing tests.

Do it once. After that, skip to **Every time you sit down**.

---

## Before you start

You need these installed. Check with the commands on the right:

| Tool | Check | If missing |
| --- | --- | --- |
| PHP 8.2+ | `php -v` | [php.net/downloads](https://www.php.net/downloads) or Laragon/XAMPP |
| Composer | `composer -V` | [getcomposer.org](https://getcomposer.org) |
| Node 18+ | `node -v` | [nodejs.org](https://nodejs.org) |
| Git | `git --version` | [git-scm.com](https://git-scm.com) |

---

## First-time setup

### 1. Clone

```bash
git clone https://github.com/aaruanto/apx-automai.git
cd apx-automai
```

### 2. Install dependencies

```bash
composer install
npm install
npm run build
```

`composer install` takes a few minutes the first time. Both `vendor/` and
`node_modules/` are deliberately not in Git — they are rebuilt from the lock
files, which is why everyone ends up with identical versions.

### 3. Create your environment file

```bash
cp .env.example .env
php artisan key:generate
```

**This file is yours and never goes into Git.** It holds secrets. More on that
below.

### 4. Create the database file

```bash
touch database/database.sqlite
```

On Windows PowerShell:

```powershell
New-Item database/database.sqlite
```

Do not skip this. The file is gitignored, so a fresh clone has none, and the
next step fails with `Database file at path [...] does not exist` — which reads
like a configuration problem and is not one.

### 5. Build the database

```bash
php artisan migrate
php artisan db:seed
```

`db:seed` loads the service catalogue, the three PMS packages and the three
service intervals. Without it the site runs but every page looks empty and
broken.

### 6. Make yourself an admin account

There is no public way to register an admin, by design.

```bash
php artisan apx:ensure-admin --email=you@example.com --password='PickAStrongOne123!'
```

### 7. Check it worked

```bash
php artisan test
```

Expect **517 passed**. If they pass, your setup is correct — that is the whole
point of running them now rather than later.

### 8. Run it

```bash
php artisan serve
```

Open <http://127.0.0.1:8000>. Sign in at `/login` with the account from step 6.

To test anything on a schedule (reminders, no-show cancellations), open a
second terminal and leave this running:

```bash
php artisan schedule:work
```

---

## Every time you sit down

```bash
git pull
composer install     # only if composer.lock changed in the pull
php artisan migrate  # only if someone added a migration
```

**Always pull first.** Most merge conflicts are two people working from
different starting points, not two people editing the same line.

If you skip `php artisan migrate` after pulling someone's migration, you get a
confusing "column not found" error later instead of a clear one.

### Before you push

```bash
php artisan test
```

All 517 must pass. `main` deploys straight to the live site, so a broken push
is a broken demo.

```bash
git add -A
git commit -m "what changed and why"
git push
```

---

## What never goes in Git

| Stays local | Why |
| --- | --- |
| `.env` | SMTP password, SMS key, cron token |
| `database/database.sqlite` | Your test data is yours |
| `vendor/`, `node_modules/` | Rebuilt from lock files |

**Never paste a real secret into the repo, a group chat, the paper, or a
screenshot.** `.env.example` holds variable *names* only. Git keeps history, so
deleting a committed key later does not remove it — if one ever gets in, say so
immediately and it gets rotated.

Your local `.env` defaults to `MAIL_MAILER=log`, which writes mail to
`storage/logs/laravel.log` instead of sending it. That is deliberate: you can
develop freely without emailing a real customer by accident.

---

## Avoiding merge conflicts

Three view files carry their own CSS and JavaScript inline and run past a
thousand lines each:

- `resources/views/dashboard/customer-dashboard.blade.php`
- `resources/views/index.blade.php`
- `resources/views/layouts/admin.blade.php`

Two people editing the same one on the same day **will** conflict. Two people in
different files never will.

So divide the work by area, not by task:

| Area | Owner's file |
| --- | --- |
| Landing page, booking modal | `index.blade.php` |
| Customer dashboard | `customer-dashboard.blade.php` |
| Admin panel | `layouts/admin.blade.php` + `admin/` views |

Say in the group chat what you are touching before you start. One message
prevents more conflicts than any Git command.

When something does go wrong, the recovery steps are in the main
[README](../README.md#when-git-goes-wrong). Nothing committed is ever lost.

---

## Sharing one Claude account

You are all signed into the same Claude Pro account in VS Code. Two things
follow from that:

- **Your sessions are separate.** Claude on your machine sees your clone and
  your files only. It does not see anyone else's work, and it cannot pull for
  you. Git is still the only way work moves between you.
- **You share one usage limit.** Four people running long sessions at once will
  hit it sooner than four people working at different times. If Claude starts
  refusing, that is usually why.

Claude edits files on *your* machine. Those edits are not real to anyone else
until you commit and push them, like any other change you make by hand.

---

## When something is broken

In order:

1. `composer install` and `php artisan migrate` — most "it suddenly broke after
   I pulled" is one of these two.
2. `php artisan config:clear` — if a `.env` change seems to have no effect.
   `php artisan serve` reads `.env` at launch, so restart it too.
3. `php artisan test` — tells you whether the problem is your setup or your
   code.
4. Paste the actual error into the group chat. The error text is the useful
   part; "it's not working" is not.
