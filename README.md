# Skin Wound Clinical Viewer

A self-contained, file-backed wound progress recordkeeping demo. The application
uses one PHP entry file with no database, framework, package install, CDN, or
remote service.

## Run

```bash
php -S localhost:8000
```

Open <http://localhost:8000>, then sign in with the visible demo credentials:

* Username: `admin`
* Password: `password`

On first run the app creates `storage/`, exactly one non-identifying sample
patient, two progress libraries, example notes and black serial-number photo
placeholders. Set `POSTOP_STORAGE_DIR` to use a different writable data folder,
which is useful for isolated testing.

## Offline use

Use **Sync all to this device** from the patient workspace and accept the privacy
warning. This deliberately downloads the complete patient snapshot and every
full-size photo. Browser Cache Storage holds that replica; IndexedDB holds only
sync metadata and the pending-change outbox. **Clear device copy** removes the
browser copy without changing server records. Logout asks the browser to clear
the local cache and storage.

Production use requires HTTPS. Service workers are also supported on localhost
for development. This is a recordkeeping demo, not a diagnostic tool, emergency
service, certified EHR, or compliance claim.

