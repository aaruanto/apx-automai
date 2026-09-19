# Team Git Workflow — Preventing Broken Pulls

If a commit looks clean on the author's machine but breaks for everyone
else after `git pull`, it's almost never bad luck — it's one of a few
repeatable causes. This doc covers the one-time fix this repo currently
needs, plus the habits that prevent it going forward.

## 1. One-time cleanup (do this together, once)

`node_modules/` is supposed to be ignored (it's in `.gitignore`), but
**5,800+ files under it are still tracked in git** — it got committed
before the ignore rule was added, and `.gitignore` never retroactively
untracks files. This is very likely your main source of "works for me,
broken for you":

- `node_modules` holds OS/architecture-specific compiled binaries
  (esbuild, etc.). A binary built on a Windows machine can't run on
  macOS/Linux and vice versa — pull it, and builds/dev servers break
  with no code change involved.
- Files like `node_modules/.vite/deps/_metadata.json` get rewritten
  slightly differently by every `npm install`, so they show up as
  "modified" constantly and cause noisy, confusing merge conflicts.

**Steps (one person runs this, everyone else follows the "after"
step):**

```bash
# Everyone: commit or stash any in-progress work first, then pull latest main
git status   # make sure it's clean before proceeding

# One person, on an up-to-date main:
git rm -r --cached node_modules
git commit -m "chore: stop tracking node_modules"
git push

# Everyone else, after pulling that commit:
rm -rf node_modules
npm install
```

`vendor/` and `.env` are already correctly untracked — no action needed
there.

## 2. After every `git pull`, run this

Pulling code doesn't pull its *side effects* — new dependencies, new
database columns, or new env variables don't apply themselves. Missing
this step is the second most common cause of "it broke after I pulled."

```bash
composer install      # picks up new/updated PHP packages (composer.lock)
npm install            # picks up new/updated JS packages (package-lock.json)
php artisan migrate     # applies any new database migrations
```

If `.env.example` gained new keys (check `git diff` on it), copy the
new ones into your own local `.env` manually — `.env` itself is never
committed or pulled, so it never updates on its own.

Optional but recommended: turn this into a git hook so it runs itself.
Create `.git/hooks/post-merge` (executable, not tracked by git — each
teammate sets it up once locally):

```bash
#!/bin/sh
composer install
npm install
php artisan migrate
```

## 3. Branch discipline

The repo already has feature branches (`dev`, `Guest`,
`feature/laravel-migration`, etc.) — keep using them instead of
committing straight to `main`:

- Branch per feature/fix, open a PR into `main` (or `dev`) instead of
  pushing directly.
- Pull `main` into your branch (`git pull origin main`) *before* you
  start a big change, not after, so you're not resolving a huge
  conflict at the end.
- Keep generated/compiled files (`node_modules`, `vendor`,
  `public/build`, `storage/framework/*`) out of every commit — they're
  in `.gitignore` for a reason; if `git status` shows one of them as
  new/modified, that's a sign something's wrong (like this repo's
  current node_modules situation), not something to `git add`.

## 4. Quick checklist for "it's broken after I pulled"

1. `git status` — anything unexpected tracked that shouldn't be (built
   files, `node_modules`, `.env`)?
2. `composer install && npm install` — did a dependency change?
3. `php artisan migrate` — did the database schema change?
4. `php artisan config:clear && php artisan route:clear && php artisan view:clear` —
   stale Laravel cache from before the pull?
5. Compare your `.env` against `.env.example` — missing a new key?
