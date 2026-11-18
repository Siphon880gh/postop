# Offline mode and caching

## Analysis: how caching worked before Offline mode

Everything lives in `index.php`: PHP routes are `?action=` handlers, and the
CSS (`css()`) and JS (`js()`) are inlined into every HTML page. There are no
separate static asset files or web fonts, so every rendered page carries its
own code.

| Piece | Where | What it does |
| --- | --- | --- |
| `APP_VERSION` | `define('APP_VERSION', …)` | First 10 hex chars of `sha1(filemtime:filesize)` of `index.php`. Any edit to the file changes it. |
| `?action=version` | PHP | Returns `{version}` with `no-store`. The page calls it on load, on `pageshow` (bfcache) and on `visibilitychange`, and does a guarded `location.replace` when the server version differs. |
| `?action=manifest` | PHP | Web app manifest (`start_url ./index.php`, scope `./`, icons from `?action=icon`). Linked with `&v=APP_VERSION`. |
| `?action=service-worker` | PHP | Service worker script, `no-store`, registered with `&v=APP_VERSION` and `updateViaCache:'none'`, so every code change installs a new worker. A `controllerchange` triggers one reload. |
| Shell cache `swcv-shell-<version>` | SW `install` | Holds only a metadata entry (`./__swcv-cache-meta`: version + cachedAt). The HTML shell itself was **not** precached. Old versions are deleted on `activate`. |
| Patient caches `swcv-patient-<id>` | page JS `syncAll()` | **Sync all to this device** (sidebar of a patient page) downloads the snapshot JSON, one day page per calendar day per library, a gallery page per library and every full-size photo, for **one** patient. Progress is shown as photos `n / N`. IndexedDB `skin-wound-viewer` → `meta` stores completion per patient. |
| SW `fetch` | SW | Only `GET` same-origin requests. `action=media` is cache-first. Navigations are network-first with a fallback to an exact cached match, then any cached page of any synced patient, then a 503 "Offline" page. Everything else goes straight to the network. |
| Outbox | page JS `stage()` | When `navigator.onLine` is false, form submits (except logout) are serialized (fields + files) into IndexedDB `outbox`. **Review and sync N changes** replays them with `expected_revision` and stops on a 409 conflict. |
| Cache modal (c091572) | footer **App · Cache** | Shows the last shell-cache date and version, per-browser clear steps (with environment detection) and **Clear app cache now** (deletes `swcv-shell-*` and unregisters the SW; keeps patient copies). |
| Logout | PHP | Sends `Clear-Site-Data: "cache", "storage"`. |

Gaps that Offline mode fixes:

1. The front page (patient list) was never cached, so opening `./` or
   `index.php` with no network showed "No synced record".
2. The network-first navigation still needed a real network failure. On a
   clinic Wi-Fi that is connected but has no internet (captive portal, blocked
   uplink) requests hang or return portal pages instead of failing fast.
3. Only one patient at a time could be synced, with no size estimate.
4. `navigator.onLine` is true on captive Wi-Fi, so the outbox never engaged
   and writes could hit a dead network.
