# KAMSSA Offline Sync — Setup Guide

This adds offline-capable data entry to KAMSSA — marks entry, student
registrations, and submission documents — for schools/offices with
unreliable or no internet. A school runs its own local copy of this same
app (e.g. under XAMPP), works completely offline, then presses **Sync
Now** once internet is available.

Everything below is now driven from the browser — no `.env` editing and
no command line needed for day-to-day use. The Artisan commands still
exist underneath and work identically if you ever prefer the terminal.

No new Composer packages are required — sync auth is a small built-in
bearer-token system, chosen deliberately so a remote install never needs
a working internet connection just to `composer install` a dependency.

---

## 1. What was added

```
database/migrations/2026_07_25_090000_create_sync_devices_table.php
database/migrations/2026_07_25_090100_create_sync_outbox_table.php
database/migrations/2026_07_25_090200_create_sync_conflicts_table.php
database/migrations/2026_07_25_090300_create_sync_state_table.php
database/migrations/2026_07_25_120000_add_uuid_to_student_registrations_table.php
database/migrations/2026_07_25_120100_add_uuid_to_submission_documents_table.php
config/sync.php
app/Support/Sync/SyncContext.php
app/Support/Sync/Syncable.php
app/Support/Sync/EnvWriter.php
app/Models/SyncOutbox.php
app/Models/SyncConflict.php
app/Models/SyncDevice.php
app/Models/SyncState.php
app/Services/Sync/SyncManager.php
app/Services/Sync/SyncClient.php
app/Services/Sync/SyncTokenIssuer.php
app/Http/Middleware/SyncTokenAuth.php
app/Http/Controllers/Api/SyncController.php
app/Http/Controllers/SyncDashboardController.php
app/Console/Commands/SyncPush.php
app/Console/Commands/SyncPull.php
app/Console/Commands/SyncIssueToken.php
resources/views/sync/dashboard.blade.php
resources/views/sync/setup.blade.php
resources/views/sync/tokens.blade.php
resources/views/sync/conflicts.blade.php
```

Modified:
- `app/Models/Mark.php`, `app/Models/MarkPaper.php` — `Syncable` trait + natural `syncKey()`
- `app/Models/StudentRegistration.php` — `Syncable` trait + `uuid` key + resolves its linked `SubmissionDocument` by uuid instead of local id
- `app/Models/SubmissionDocument.php` — `Syncable` trait + `uuid` key + embeds/writes the actual file bytes on sync
- `app/Http/Kernel.php` — registered the `sync.token` middleware alias
- `app/Http/Middleware/StudentAuth.php` — let marks entrants reach `/sync` (Sync Now only — setup/tokens/conflicts still require a full admin login)
- `app/Console/Kernel.php` — scheduled auto push/pull every 5 minutes
- `resources/views/layouts-side-bar/side-menu.blade.php` — new **Offline Sync** menu entry
- `routes/api.php` — `/api/sync/push`, `/api/sync/pull`
- `routes/web.php` — `/sync`, `/sync/run`, `/sync/setup`, `/sync/tokens`, `/sync/conflicts`

---

## 2. Central server — one-time setup

```bash
composer install
php artisan migrate          # picks up the sync_* tables + the new uuid columns
```

Nothing needs to go in `.env` on the central server — `SYNC_ROLE`
defaults to `central`, meaning it only ever **receives** pushes and
**answers** pulls.

### Issue a token for each school/office — from the browser

Log in as a normal (non-marks-entrant) admin, open the sidebar:

**Offline Sync → Manage School Tokens** (`/sync/tokens`)

1. Enter the school number (e.g. `07-2026`) and a label for the machine
   (e.g. "St. Mary's HQ Laptop").
2. Click **Issue Token**.
3. A green box appears with a ready-to-copy 5-line block — copy it (there's
   a **Copy to clipboard** button) and hand it to whoever is setting up
   that school's machine. It is shown once; if lost, just issue a new one
   and revoke the old row.
4. The table below lists every token ever issued, its last-used time, and
   a **Revoke** button — revoking takes effect immediately and doesn't
   affect any other school.

This same thing can still be done from the terminal if you'd rather:
```bash
php artisan sync:issue-token 07-2026 "St. Mary's HQ Laptop"
```

---

## 3. School/office install — one-time setup

1. Install XAMPP, start Apache + MySQL.
2. Copy this codebase to `htdocs/kamssa`, `composer install` (do this
   once while still online — in town, or on a machine before it heads
   out to the field).
3. Create an empty MySQL database via phpMyAdmin, point `.env`
   (`DB_*` vars) at it, then `php artisan migrate`.
4. Open the app in a browser and log in. Go to
   **Offline Sync → Connect This Install** (`/sync/setup`).
5. Paste the block you were given (fastest), or use the **Fill in fields
   manually** tab if you'd rather type each value in. Click **Connect
   This Install** — this writes the 5 `SYNC_*` values into `.env` and
   clears the config cache automatically. No terminal needed.
6. Once connected, the page shows "already connected" with a summary of
   the school number/central address/device name, and offers a
   **Reconfigure this install** link if you ever need to point the
   machine at a different token or server.
7. Before heading to the field, run **Sync Now** once while still
   online — this pulls down that school's existing students/marks/
   registrations so the machine has current data before going offline.
8. Give the marks entrant a desktop shortcut straight to the app URL —
   they never need to see phpMyAdmin, `.env`, or a terminal.

### While offline
Staff use the app exactly as before — same forms, same validation, same
controllers. Nothing about their day-to-day workflow changes.

### Once back online
Open **Offline Sync → Sync Now** (or `/sync`) and press **Sync Now**. That:
1. Pushes every queued change since the last sync.
2. Pulls anything changed centrally since the last pull.
3. Shows pending count, last error (if any), and a recent-activity table.

If a machine has a cron job / Windows Task Scheduler entry running
`php artisan schedule:run` every minute, this also happens automatically
every 5 minutes — the button is the reliable fallback, not the only path.

---

## 4. What's syncable now

| Model | Matched by | Notes |
|---|---|---|
| `Mark` | `student_id` + `subject_id` | Natural key already used by `ItebController` |
| `MarkPaper` | `student_id` + `subject_id` + `paper_number` | Same |
| `StudentRegistration` | `uuid` (new column) | Safe for offline-created rows; its link to a `SubmissionDocument` travels as that document's uuid and is resolved back to a local id on the receiving side |
| `SubmissionDocument` | `uuid` (new column) | The actual file is embedded (base64) in the sync payload and written to disk on the receiving side at the same relative path |

Reference/shared data (subjects, grading settings, exam categories, etc.)
is intentionally **not** in this list yet — schools only need to *read*
that, never push it. Add it the same way (see section 6) when you're
ready, with `school_scoped => false`.

---

## 5. Conflict handling

Marks/registrations/documents are "school-owned" — normally only that
school's own staff touch them, so conflicts should be rare. When one
does happen (e.g. a registrar corrected something centrally after the
school made its own offline edit), the push is **not** silently
overwritten. It's logged and a central admin resolves it at
**Offline Sync → Review Conflicts** (`/sync/conflicts`), picking either
the offline (incoming) or the central (current) value.

Sync also refuses (rather than silently accepting) any pushed change
whose `school_number`/`school_id` doesn't match the school the pushing
device's token was issued for — this stops one school's install from
ever writing another school's data, even by accident.

---

## 6. Known limitations (read before a real rollout)

- **Bulk `Model::query()->update()` calls bypass Eloquent events.**
  Laravel doesn't fire `saved()` for mass updates done through the query
  builder (only for `$model->update()` on an individual instance). One
  example already in the codebase: `SchoolsController` bulk-locks
  `StudentRegistration` rows and sets `submission_document_id` via a
  `whereIn(...)->update([...])` call — that specific field change won't
  be queued to the outbox automatically. If that particular workflow
  needs to work offline too, either change that call to update rows one
  at a time, or manually call `SyncOutbox::recordChange(...)` after the
  bulk update. Worth auditing the rest of the codebase for similar bulk
  updates on syncable models before depending on this for critical data.
- **`submitted_by` on `SubmissionDocument` travels as a raw user id.**
  If a school's local user ids don't match the central server's for the
  same staff member, the synced document can end up attributed to the
  wrong (or no) user centrally. Reconciling user accounts across
  installs is a bigger piece of work not covered by this pilot.
- **Large files.** Submission documents are base64-encoded into the
  sync payload. Fine for typical scanned pages/PDFs; a very large file
  (video, big multi-page scan) will make that one push slower and use
  more memory. If large files become common, swap this for a dedicated
  chunked/multipart upload step instead.
- **This has not been run against a live database in the environment it
  was built in** (no internet access to Packagist there) — only
  `php -l` syntax-checked. Run section 7's smoke test before trusting it
  with real exam data.

---

## 7. Smoke test checklist

1. On the **school** install, connect via `/sync/setup`, then enter a
   mark for a test student via `/enter-marks`.
2. Confirm a row appears in `sync_outbox` with `synced_at` still null:
   `SELECT * FROM sync_outbox;`
3. Press **Sync Now** — confirm "Pending" drops to 0 and the recent
   table shows it as OK.
4. On the **central** server, confirm the `marks`/`mark_papers` row now
   exists with the value you entered.
5. Register a brand-new test student offline at the school (creates a
   `StudentRegistration`), sync, and confirm it appears centrally with a
   real `uuid` — not a duplicate/colliding id.
6. Upload a small test submission document offline, sync, and confirm
   the file itself (not just the database row) appears centrally under
   `public/submission_docs/`.
7. Edit a central mark directly (simulate a registrar correction), edit
   the *same* student+subject mark offline at the school, sync, and
   confirm it lands in **Offline Sync → Review Conflicts** instead of
   silently overwriting — resolve it there and confirm the chosen value
   wins.
8. From **Offline Sync → Manage School Tokens** centrally, revoke that
   school's token and confirm the next **Sync Now** at the school fails
   with a clear "unreachable"/auth error rather than silently doing
   nothing.

---

## 8. Extending to another model

1. Add `use App\Support\Sync\Syncable;` to the model.
2. If rows can be **created offline**, add a `uuid` migration (mirror
   `2026_07_25_120000_add_uuid_to_student_registrations_table.php`,
   including the backfill loop for existing rows) and override
   `syncKey()` to return `['uuid' => $this->uuid]`. If instead it's
   always matched by an existing natural key (like `Mark`), just
   override `syncKey()` with that.
3. Add an entry to `config/sync.php` under `models`, with
   `school_scoped` + `school_column` (+ `school_resolution` if that
   column stores a house id rather than the school_number string) —
   or `school_scoped => false` for shared reference data.
4. If the model references another syncable model by its local id (like
   `StudentRegistration` → `SubmissionDocument`), override `syncPayload()`
   to swap that id for the related row's uuid, and
   `syncMaterializePayload()` to resolve it back on the receiving side —
   copy the pattern in `StudentRegistration.php`.
5. Nothing else changes — `sync:push`/`sync:pull`/the dashboard/the
   conflict screen/the token UI all already work generically off that
   config list.
