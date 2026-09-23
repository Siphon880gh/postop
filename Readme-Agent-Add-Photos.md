# Add photos to a wound progress library

Drop the photos in `pipe-in`, name the files, then prompt Cursor or Claude Code with this repo open. You do not look up storage folders or edit JSON.

The drop folder is `pipe-in` at the project root, next to `index.php`. The app's `pipeline-in` folder is a separate first-time patient import. Leave that one alone.

## 1. Create `pipe-in` and move the photos in

```bash
mkdir -p pipe-in
```

Move this batch of photos into `pipe-in/`. Leave them loose in that folder. JPEG, PNG, or WebP, each under 15 MB.

One batch at a time. When the agent finishes, `pipe-in` should be empty.

`storage/` is gitignored. Do not commit the photos sitting in `pipe-in`.

## 2. Name the files

The filename tells the agent which wound the picture belongs to. Use lowercase words separated by hyphens.

| File | Meaning |
| --- | --- |
| `left-lateral-heel-gauze.jpg` | Wound named left lateral heel gauze |
| `lateral-incision-front.jpg` | Shot of an existing wound named lateral incision |
| `left-lateral-heel-gauze-a.jpg` | Same wound, angle label `A` |

A single letter after the last hyphen (`-a`, `-b`) is the angle. Skip it when you do not want an angle label.

Rename camera files such as `IMG_4521.jpg` before you prompt. Those names do not say which wound they are.

## 3. Prompt Cursor or Claude Code

Open this project, then paste:

```text
Follow Readme-Agent-Add-Photos.md.

Create for PATIENT_NAME's Post Op library, add to the day 9/22/26 what's in pipe-in. We'll have a new wound photo called left lateral heel gauze which is a photo of how much bleeding on the gauze.
```

Change the patient name, the date, and the sentence about the photos. `9/22/26` means `2026-09-22`.

Say **new wound** when that wound is not already on the library. Put the caption in the same sentence, the way the example describes bleeding on the gauze. If several files are in `pipe-in`, say what each one is.

## What the agent does

Match the patient by name under `storage/patients/*/record.json`. Match the Post Op library: type `Postoperative Wound`, or the library named in the prompt.

For each image in `pipe-in`:

1. Add it to that library on that calendar day.
2. Create the wound when the prompt says it is new. Use the same wound fields the app already stores (`id`, `name`, `location`, `active`, `notes`, `day_notes`, `updates`).
3. Set the caption from the prompt. Set the angle from a trailing `-a` / `-b` in the filename when one is there.
4. Copy the image into that patient's library photo folder and append the photo on that day's wound update, using the same photo fields and filename pattern the app already uses (`id`, `angle`, `caption`, `created_at`, `sort_order`, `filename`, `mime`, `bytes`).
5. Delete the file from `pipe-in` after it is stored.

Do not ask for a folder path. Do not leave `record.json` half-updated. When you are done, `pipe-in` is empty.

## Check

Sign in, open that patient, open the Post Op library, and open the day. The new wound and photo should be there.
