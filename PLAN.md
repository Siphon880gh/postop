# Build prompt: file-based, offline-first PWA PHP wound-progress gallery

Build a fully working, self-contained PHP application for documenting a patient's wound-photo progress. This is a demo/recordkeeping app, not a clinical decision tool. Do not build a mockup with nonfunctional controls.

## Deliverable and constraints

* Create one runnable entry file: `index.php`. Keep the PHP, HTML, CSS, and any small vanilla JavaScript in that file. It must run from the project root with `php -S localhost:8000`.
* Keep the source self-contained in `index.php`. For PWA support, it may dynamically serve a web app manifest, service-worker JavaScript, PWA icon SVGs, offline-sync manifest JSON, and authenticated media routes from dedicated `index.php?action=...` endpoints with the correct content types and headers. Do not add external dependencies.
* The authoritative app is file based only. Do not use MySQL, SQLite, Firebase, an API, a remote service, a framework, Composer, or any remote database/service.
* Do not use browser `localStorage`, browser `sessionStorage`, or cookies for clinical/application data. The only allowed client-side persistence is the browser Cache Storage and IndexedDB needed for the user's explicitly requested offline PWA copy and its pending-change outbox. Server-side JSON and image files remain the source of truth.
* PHP sessions are allowed solely to keep the user authenticated during the current browser session. Do not store clinical/app data in the session.
* Let `index.php` create its own writable `storage/` folders and seed data on first run. Store JSON metadata and image files separately; never embed uploaded photos as base64 in JSON.
* When online, all create, edit, delete, upload, and reorder operations must be real server-side PHP POST actions handled by `index.php`, followed by redirect-after-POST. When offline, the PWA may stage a visibly pending change in its IndexedDB outbox, but it must never present that change as saved on the server until PHP has accepted it.
* Use no external images, CDNs, or third-party dependencies.
* Use semantic, accessible HTML: visible focus states, keyboard-operable controls, real labels, sufficient contrast, descriptive alternative text, and no information conveyed by color alone.
* A production deployment must use HTTPS; PWA installation/service-worker testing may use `localhost` during development.

## Demo login and initial state

* Show a login page first.
* Use demo credentials: username `admin`, password `password`.
* Clearly label these credentials as demo-only. Use PHP session authentication and include a Logout control.
* Seed exactly one non-identifying patient named `Sample Patient` and show that patient in a Patients screen.
* Seed enough sample data to demonstrate all states below: at least two progress libraries, several black serial-number placeholder images, at least one date with no updates, at least one date where only one of two wounds was updated, and notes at several scopes.

## Domain model

Use this hierarchy and terminology consistently:

`Patient → Progress Library → Longitudinal Wound → Date-specific Wound Update → Angle Photos`

### Progress Library

A progress library is one wound-progress gallery for a patient. It has:

* `id`
* Display name, such as “Left Heel Post-op Recovery”
* A type: `Pressure Injury`, `Mole Monitoring`, `Postoperative Wound`, or `Custom`
* A required starting date
* An optional description
* A collection of library-level notes
* A collection of longitudinal wounds

The whole library uses the same layout regardless of type. The type is a clear label/filter, not a separate UI or data model. Allow a custom type value when `Custom` is selected.

### Longitudinal wound

A wound belongs to a library and represents a site tracked over time, for example “Lateral incision” or “Donor site.” It has:

* `id`
* Name
* Optional location/description
* Active/inactive state
* A collection of wound-level notes

Create, read, edit, and delete wounds from the library. Deleting a wound must require confirmation and remove its date updates and associated photo directory safely.

### Date-specific update

The library’s starting date creates a selectable timeline from that day through today. Do not create empty JSON files or folders merely to render blank days.

* A date can have a day-level note.
* An explicit update for a wound on that date marks the wound as updated; it may contain a note and zero or more photos.
* If a selected date has no wound updates, its date chip is muted/gray and the content view clearly says “No wound updates recorded.”
* For every selected date, show every active wound in the library. If a particular wound has no explicit update that day, render its card muted/gray with “Not updated on this date,” rather than hiding it.
* A note-only wound update counts as an update and should say “No photos added” rather than appear as missing.
* Allow creating, editing, and deleting day-level and date-specific wound updates.

### Notes

Support one or more timestamped notes at all three requested levels:

1. The whole progress library
2. A selected day
3. A longitudinal wound

Make note presence obvious everywhere it matters: use a visible accent border, a note icon/text, and a numeric badge such as `Notes 2`. The badge needs an accessible label such as “2 notes.” Do not rely on the border color alone. Opening a note control must show the notes for that exact scope and allow add/edit/delete.

## Required screens and behavior

### 1. Login

Provide a polished but compact sign-in screen with the demo credentials visible and a clear error for bad credentials.

### 2. Patients screen

After login, show a Patients page with the single seeded patient as a selectable card or row. Keep the patient list deliberately simple; patient CRUD is not required for this version.

### 3. Patient and progress-library screen

When the patient is selected:

* Show patient name and a back-to-patients action.
* Show a left-side or top library selector with every progress library, its type, starting date, and note count.
* Include functional controls to add, edit, and delete a library. The library form must require a name, type, and starting date.
* After selecting a library, show its name, type, starting date, description, and its library-note indicator.
* Provide a date navigator grouped by month or otherwise paged so a long-running library remains usable. Default to the most recent relevant date. Include previous/next navigation and a Today/latest shortcut.
* A date chip with no updates is gray/muted. A date with activity is visually distinct. If a gray date has a day note, preserve its note badge as well.

### 4. Selected-day view

For the selected day, show:

* The date and the day-note control
* All active wound cards
* A clear “add/update this wound for this date” action for each unupdated wound
* The wound-level note indicator and management control
* The date-specific update content for updated wounds
* An easy action to manage the library’s wound list

Each updated wound card should present an angle-photo gallery. Each photo has an editable angle label, such as `Front`, `Side`, `Close-up`, or a custom label; an optional caption; and actions to edit, delete, and reorder it.

## Photo handling

Photos are file-backed and belong to a specific patient, library, wound, and date.

* Support JPEG, PNG, and WebP uploads with server-side MIME/image validation and a reasonable configurable size limit, such as 15 MB. Reject executable files and never trust the browser filename or MIME type.

* Store uploads under a clear hierarchy such as:

  `storage/patients/{patient-id}/libraries/{library-id}/wounds/{wound-id}/{YYYY-MM-DD}/`

* Store metadata in JSON under the relevant library directory. Metadata must include an internal photo ID, angle label, caption, created timestamp, sort order, and stored filename.

* Include a library/photo revision or content-version value in the sync manifest and media cache key so that a changed server file cannot silently show an obsolete cached image.

* Use safe generated IDs and filenames; never accept a client-supplied path. Escape all rendered text and validate IDs before every filesystem operation to prevent path traversal.

* Provide a `Create placeholder photo` action for an updated wound. It must create a black image file with a unique random serial number in white text, such as `IMG-7F3C92`, and add it to the gallery. SVG is an acceptable dependency-free placeholder format; use PHP GD for PNG only if available. Do not generate realistic wound imagery.

* Serve image files through an authenticated PHP route/action that resolves a known photo record, rather than accepting arbitrary paths in a query string.

* Delete photo must delete both the JSON record and the matching image file after confirmation.

## Offline-first PWA and user-controlled device sync

The app must be an installable PWA. Its offline feature is not automatic: it is a deliberate, reversible local copy made only after the user chooses it.

### PWA implementation

* Serve a valid web app manifest, a service worker, and simple generated icons from `index.php` endpoints. The service worker must have the correct scope/header to control the app.
* Cache the app shell needed to open the application interface offline. Do not cache external resources because there are none.
* Add an authenticated `offline-sync-manifest` endpoint that returns the selected patient's current JSON snapshot, library revisions, and the exact list of full-size image/media URLs with byte sizes or a total-size estimate.
* Cache the selected patient's complete current snapshot and original-size photos in Cache Storage. Use IndexedDB only for the small local sync-status metadata and offline change outbox; do not use `localStorage`.
* Treat the server-side JSON/files as canonical. The device cache is an offline replica and must be clearly labeled as such.
* Use cache-first behavior for a photo that has already been synced to the device. For structured data, prefer a fresh network response when available and fall back to the last synced snapshot when offline or slow. Never block the cached gallery from rendering while waiting for the network.
* Support an offline route/fallback so the user can reopen the installed app without a connection after a successful sync. Display a clear `Offline — showing copy from <date/time>` status.

### User-facing sync controls

For the selected patient, add an obvious `Sync all to this device` control. In this version, all means every library, every date, all note data, and every full-size photo for that patient—not merely the thumbnails visible today.

* Before the first sync, show the approximate number of photos and storage size if known, a browser-storage availability check, and a clear privacy warning: the wound records and photos will be stored in this browser on this device. Do not start the download until the user confirms.
* Ask the browser for persistent storage when supported, but accurately state that a browser may still evict cached data if it cannot guarantee persistence.
* Show meaningful sync progress: photos downloaded / total, bytes or estimated size where possible, success/failure state, and a Cancel action. A partial sync must be labeled incomplete; preserve safely downloaded files so the user can resume.
* After success, replace the first action with `Available offline`, the last successful sync time, `Update offline copy`, and `Clear device copy`.
* `Update offline copy` must compare the latest server manifest/revision, fetch only new or changed JSON/photos where possible, and remove cache entries for photos that were deleted on the server.
* `Clear device copy` must require confirmation and remove only this app's patient snapshot, photo cache, and related offline outbox. It must not delete server records.
* On logout, clear the local patient snapshot and media cache by default so a later user of the same browser does not see medical photos. If an offline outbox has unsynced edits, warn the user before removal.

### Offline behavior and changes

* After a completed sync, viewing must work without internet: launch/open the PWA, select libraries and dates, read notes, see gray missing-update states, open all synced full-size photos, and browse the gallery.
* Display network state prominently but unobtrusively: `Online`, `Syncing`, `Offline`, or `Pending changes`. Do not show misleading online-only actions as completed while offline.
* Support an offline outbox for edits so the core recordkeeping workflow remains usable during a connection outage. At minimum queue library/wound/day/note edits, photo metadata edits, deletions, reorder operations, placeholder requests, and selected upload image blobs. Each queued item must display `Pending sync` and remain clearly distinguishable from server-saved data.
* When connectivity returns, show `Review and sync N changes`; let the user trigger the upload/replay explicitly. Do not rely solely on Background Sync because it is not consistently available across browsers.
* Replay queued operations in a safe order with the library revision expected by the offline copy. Upload staged image blobs before their associated metadata when needed. PHP performs the final file-system write, validation, naming, and reorder normalization.
* If the server changed since the device's last known revision, stop at the conflict, preserve the queued local change, and present a clear choice to reload the server version or review/retry the local change. Never silently overwrite another server-side change.
* A locally staged placeholder may be rendered as a temporary black serial-number SVG while offline, but PHP must create the final authoritative placeholder file when the queued request is accepted by the server.
* If a browser cannot provide enough storage for a selected upload or the full sync, show a clear error and keep the existing offline copy intact.

### Privacy and cache safety

* Do not make an offline copy until the signed-in user explicitly chooses `Sync all to this device`.
* Do not claim that browser caching encrypts data. Warn users not to enable offline copies on shared/public devices.
* Serve only known, authenticated media IDs; the service worker must not turn arbitrary filesystem paths into cacheable URLs.
* Keep the cache scoped to this application and patient. Do not expose photos through a public asset directory or a service-worker route that bypasses app authentication.

## Server-side reordering and file changes

Reordering must go through PHP, not merely rearrange DOM elements.

* Provide reliable Move left/right or Move up/down buttons; drag-and-drop is an optional enhancement only.
* On every reorder, POST the intended photo order/IDs to PHP.
* PHP must validate that all photos belong to the current update, rewrite their sort order in JSON, and normalize filenames to include the order prefix, for example `01-front-<id>.jpg`, `02-side-<id>.jpg`. Use safe temporary names during rename operations to prevent collisions.
* After deletion/reordering, compact order numbers and do not leave orphaned files.

## File integrity and safety

* Use a JSON read/write helper that writes to a temporary file and atomically renames it into place. Use file locking where appropriate.
* Create storage folders only as needed, with sensible permissions.
* Protect every mutating form with a CSRF token.
* Use POST for mutations, confirmation UI for destructive actions, server-side validation messages, and flash messages after redirects.
* Keep image files out of JSON. Do not silently delete unrelated storage paths. When deleting a library or wound, resolve and validate the exact expected directory before recursive deletion.
* If the storage path cannot be created or written, show an actionable error rather than failing silently.

## Design direction

* Build a clean, calm clinical-record interface without pretending to be an EHR.
* Make it responsive for desktop, tablet, and phone.
* Use cards, chips, and a clear visual hierarchy. Muted cards/chips should still have readable text.
* Use black placeholder thumbnails with visible serial numbers. Real uploaded thumbnails should preserve aspect ratio.
* Add a small visible footer disclaimer: “Demo wound-photo recordkeeping app — not for diagnosis or emergency use.”

## Acceptance criteria

Before you consider the implementation complete, verify that all of these work after a browser refresh:

1. Sign in with `admin` / `password`, log out, and sign back in.
2. Open the one seeded patient and switch between at least two progress libraries.
3. Create, edit, and delete a library with a type and starting date.
4. Create, edit, deactivate, and delete a longitudinal wound.
5. Select any date from the library start date onward, including an empty date, and see the correct gray state.
6. On a date where one wound is updated and another is not, see the latter gray but still visible.
7. Add/edit/delete library, day, and wound notes, with visible/countable note indicators.
8. Add a placeholder photo, upload an allowed image, edit angle metadata, delete a photo, and reorder two photos. Confirm the gallery and files remain correct after reload.
9. Confirm no database, remote service, `localStorage`, `sessionStorage`, or fake CRUD control is used. Cache Storage and IndexedDB may exist only for the user-approved PWA cache/outbox.
10. Install the PWA or run it as an installed-app equivalent on `localhost`/HTTPS. Click `Sync all to this device`, confirm the privacy warning, and verify that all seeded libraries, notes, placeholders, and full-size photos are downloaded with progress shown.
11. Disable/throttle the network after sync. Reopen the app, navigate every library/date, read notes, and open every cached photo without a network request. Verify the offline timestamp/status is visible.
12. Make at least one offline edit and one offline photo-metadata/reorder change. Verify they are visibly pending, then restore connectivity and explicitly sync them to PHP without duplicate or lost records.
13. Verify an update sync retrieves a changed/new photo and removes a server-deleted photo from the device cache. Verify `Clear device copy` and logout remove only local offline data, not server data.
14. Confirm browser `localStorage` and `sessionStorage` are unused; Cache Storage and IndexedDB are used only for the opt-in PWA cache/outbox, never as the canonical record.

If you are working in a code workspace, create the runnable `index.php` and a concise `README.md` with launch instructions. Otherwise, return the complete contents of both files in separate code blocks. Do not omit core behavior for brevity.
