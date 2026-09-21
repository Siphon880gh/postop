# E5 graph prompt — Secure Angle-Photo Gallery

**Outcome:** users can safely add, describe, order, view, and remove file-backed
photos for an explicit wound update.

## Milestone graph

`E4.M2` → `E5.M1 Authenticated media and validation` →
`E5.M2 Uploads and placeholders` → `E5.M3 Photo metadata, order, and deletion`

### E5.M1 — Authenticated media and validation

Implement generated media identities, ownership lookup, authenticated serving,
content headers, image MIME/dimension validation, size limit, and cache version.

**Acceptance:** anonymous/foreign/unknown IDs fail without leaking paths; valid
JPEG/PNG/WebP works; spoofed, corrupt, executable, and oversized content fails;
metadata contains filenames but never image bytes or client paths.

### E5.M2 — Uploads and placeholders

Implement real uploads and server-created black SVG placeholders with unique
visible serials under the specified hierarchy.

**Acceptance:** files and metadata persist after refresh; filenames/IDs are
server generated; concurrent additions do not collide; placeholder is black
with a white random serial and is not realistic wound imagery; failures leave no
partial metadata/file pair.

### E5.M3 — Photo metadata, order, and deletion

Implement angle/caption edits, ordered gallery, server-backed move controls,
safe collision-free renames, compact sort order, and confirmed deletion.

**Acceptance:** reorder POST validates the exact owned set; filenames receive
normalized order prefixes; reload preserves order; edit changes cache revision;
delete removes exactly its file/record; no temp or orphan file remains after
success or injected failure.

