# Eduvixo CMS - role assignment and Demo User mode

Date: 2026-08-29
Environment: production demo CMS at `demo.eduvixo.com`
Status: implemented, deployed and verified

## Objective

Correct the role assigned to the demo account and provide an explicit Demo User option that exposes the complete CMS while preventing every persistent change.

## Root cause

The user-access form used independent checkboxes and intentionally supported a union of several roles. The production audit log showed four valid submissions for the demo account: Admissions & Support, then Administrator plus Admissions & Support, then Administrator plus Admissions & Support plus Editor, and finally Admissions & Support plus Editor. The backend stored the submitted sets correctly, but the multi-select interface did not match the expected single primary-role workflow.

The form now uses a required radio group named `role_id`. Saving an account replaces its prior role assignments with exactly one selected role. The backend also limits the legacy `role_ids[]` input to one role for backward compatibility.

## Demo User implementation

- Migration `023_demo_user_read_only.sql` adds `users.is_demo` as an additive, non-null boolean with a safe default of `0`.
- The user form includes a clearly described Demo User checkbox.
- Demo users receive the complete permission catalogue and unrestricted campus visibility at runtime without receiving or persisting the Owner role.
- `AccessControl::enforceRequest()` centrally blocks every authenticated non-safe request for a Demo User before route dispatch.
- `GET`, `HEAD` and `OPTIONS` remain available; `POST /logout` is explicitly allowed.
- Blocked AJAX/JSON requests return HTTP 403 with a clear read-only message.
- Non-JavaScript submissions return to a same-origin page with a warning.
- A persistent read-only banner appears throughout the CMS.
- The last active writable Owner cannot be converted into a Demo User.

## Production data correction

The existing account `demo@eduvixo.com` was changed from the combined Editor and Admissions & Support roles to exactly one Administrator role, and Demo User mode was enabled. The similarly spelled address without the final `o` did not exist.

No passwords, profile data, campuses or other user records were changed.

## Files changed

- `.cms/source/app/Core/AccessControl.php`
- `.cms/source/app/Core/Auth.php`
- `.cms/source/app/Http/DashboardController.php`
- `.cms/source/app/Views/console.php`
- `.cms/source/app/Views/console-access-control.php`
- `.cms/source/public/theme/eduvixo-demo-mode.css`
- `.cms/source/database/migrations/023_demo_user_read_only.sql`

The `.cms/` source tree remains intentionally excluded from the public website repository by the existing security boundary. This work record is versioned in the parent repository.

## Backup and rollback

Pre-deployment file and database backup:

`/root/eduvixo-backups/access-demo-user-pre-20260829-224831`

The backup is root-only and includes the affected application files plus a dump of `users`, `user_roles`, `roles`, `role_permissions`, `migrations` and `activity_log`.

Safe application rollback:

1. Restore the six original files from the backup's `files/` tree.
2. Remove the new `public/theme/eduvixo-demo-mode.css` file.
3. Set `is_demo=0` for the demo account and restore its previous Editor and Admissions & Support role relations using role slugs.
4. The additive database column may remain in place safely. A full schema rollback may additionally remove the migration record and column after the old code is restored.

The database dump is the final recovery source if an exact point-in-time restoration is required. Because it contains access and audit tables, it should only be imported during a controlled rollback before any later access changes are accepted.

## Verification

- PHP syntax validation passed for all five changed PHP files locally and on production.
- Migration `023_demo_user_read_only.sql` was applied successfully.
- The demo account is active, has `is_demo=1`, and has only the `administrator` role.
- The demo account receives all 39 of 39 registered permissions.
- Campus scope resolves to global.
- A simulated authenticated Demo User POST to `/content/pages` returned HTTP 403 JSON and did not reach the controller.
- The writable Owner account remains `is_demo=0` with the protected Owner role.
- `/login` returned HTTP 200.
- The new stylesheet returned HTTP 200 with `text/css`.
- Browser verification confirmed the Primary role radio group, Demo User checkbox, corrected Administrator selection and read-only account label.
- Apache and website error logs contained no new PHP fatal errors, parse errors or uncaught exceptions.
