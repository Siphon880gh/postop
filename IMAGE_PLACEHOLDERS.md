# Image generation plan

Generate every file in this list, in order, and save it in `pipe/` using the exact filename. Do not rename files. When an entry names an image reference, pass that earlier `pipe/` file into the generator as the image reference before generating the new image.

This plan covers the Skin Wound Clinical Viewer demo. The only seeded patient is **Sample Patient** (female, 64, account A-10042): a postoperative left heel wound with a posterior heel donor site, in the **Left Heel Post-op Recovery** library. Dates are relative to the day the sample pack was created. Match photos by the photo id below, not by a calendar date.

Every clinical photo in this list is stand-in demo imagery. It must include the words **AI Generated / Not real patient** in the frame.

## Shared visual identity

Use this identity in every Sample Patient image.

- Woman of about 64. Light-to-medium olive skin, mature texture, a few ordinary age spots on the lower leg and ankle. Short silver-gray hair, visible only in the portrait.
- The same left foot in every wound photo: modest adult heel, no tattoos, no jewelry, no nail polish, no hospital wristband, no face, no name.
- Clinical still photography, not an illustration and not a 3D render. Soft even overhead light, slightly cool white balance, blue disposable drape, a narrow edge of white sheet. No flash hotspots, no date stamp, no logo, no ruler with readable text.
- Keep wounds modest and clinically plausible. No heavy bleeding, no graphic trauma, no surgical instruments in frame.
- Wound photos are 4:3 landscape, at least 960×720. The viewer scales them with `object-fit: contain`, so keep the heel centered with a little drape around it.
- Portraits are 4:5, at least 640×800. The viewer crops them with `object-fit: cover` in an 88×110 card and a 44×54 header, so keep the face in the upper two-thirds.

Do not pass the portrait into a wound-photo generator. A face reference pulls a face into the heel pictures. Wound photos share the written identity above and the left-heel reference chain.

## Not part of this plan

- The install icon is a drawn teal medical cross, not a missing image.
- Pencil, expand, and note marks are interface icons.
- **Create placeholder photo** still makes a black serial-number SVG when someone adds a new shot later. Those files do not exist until that button is used.
- **Mole Monitoring → Medial site** has no photos on purpose.
- The donor site’s newest day is a note-only update (“Clean and dry”) and has no photo.
- `README-assets/*.png` are missing screenshots of this app’s real screens. They are not generated clinical images.
- Seed entries `photo-seeded-concern-*` and `photo-seeded-change-*` are not created. Those dates already have the photos listed below, and the concern or change note is attached to that existing shot.

## Generation order

| # | Filename | Role | Replaces |
| --- | --- | --- | --- |
| 1 | `avatar-female.png` | Sample Patient portrait | `placeholders/female.svg` and the patient avatar `IMG-AVATAR-FEMALE.svg` |
| 2 | `avatar-male.png` | Generic male portrait | `placeholders/male.svg` |
| 3 | `lateral-baseline.png` | Canonical left heel and fresh incision | `photo-seeded-baseline` |
| 4 | `lateral-day9-front.png` | Same incision, edges closer | `photo-seeded-day-9-front` |
| 5 | `lateral-day9-side.png` | Same day as 4, side angle | `photo-seeded-day-9-side` |
| 6 | `lateral-day7-progress.png` | Dressing just removed | `photo-seeded-day-7` |
| 7 | `lateral-day5-front.png` | Smaller, drier wound bed | `photo-seeded-day-5-front` |
| 8 | `lateral-day5-side.png` | Same day as 7, side angle | `photo-seeded-day-5-side` |
| 9 | `donor-day5-posterior.png` | Posterior heel donor site | `photo-seeded-donor-day-5` |
| 10 | `lateral-day3-progress.png` | Tiny opening at the distal tip | `photo-seeded-day-3` |
| 11 | `lateral-day2-progress.png` | Next dressing change | `photo-seeded-prior-date` |
| 12 | `lateral-day1-shot1.png` | Routine set, overview | `photo-1` |
| 13 | `lateral-day1-shot2.png` | Routine set, close detail | `photo-2` |
| 14 | `lateral-day1-shot3.png` | Routine set, wider context | `photo-3` |

Items 3–14 are shown on the day record and in the photo gallery for **Left Heel Post-op Recovery**. Lateral shots belong to the wound **Lateral incision** (left lateral heel). The donor shot belongs to **Donor site** (posterior heel).

---

## 1. Sample Patient portrait

**Placeholder:** Black portrait card labeled `AVATAR-FEMALE` / `IMG-AVATAR-FEMALE`. Shown on the Patients card and in the compact header after the record is opened. Alt text is “Portrait placeholder for Sample Patient.” Served from `placeholders/female.svg` and copied to the patient folder as `IMG-AVATAR-FEMALE.svg`.

**Generated filename:** `avatar-female.png`

**Image reference:** None. This is the canonical person.

**Prompt:**

Photorealistic ID-style portrait of a generic 64-year-old woman, not a celebrity and not a real patient. Short silver-gray hair, light-to-medium olive skin, calm neutral expression, looking at the camera. Head and shoulders, plain soft gray-green clinical background, even soft light, no jewelry, no glasses glare, no text, no logo, no hospital badge. 4:5 vertical composition, face centered in the upper two-thirds, simple dark top. Natural mature skin texture. Dignified and ordinary.

**Consistency and usage:** 4:5, at least 640×800. This face is Sample Patient everywhere the female avatar appears. Later wound photos must match her skin tone in the written prompts. Do not use this file as an image reference for heel photos.

---

## 2. Generic male portrait

**Placeholder:** Black portrait card labeled `AVATAR-MALE` in `placeholders/male.svg`. Used for a male patient record that has no custom avatar, and by the generic male avatar route. Sample Patient does not use this image.

**Generated filename:** `avatar-male.png`

**Image reference:** None. This is a different person from Sample Patient.

**Prompt:**

Photorealistic ID-style portrait of a generic man in his 60s, not a celebrity and not a real patient. Short gray hair, light skin with ordinary mature texture, calm neutral expression, looking at the camera. Head and shoulders, plain soft gray-green clinical background, even soft light, no jewelry, no text, no logo, no hospital badge. 4:5 vertical composition, face centered in the upper two-thirds, simple dark top. He must not resemble the woman in any Sample Patient portrait.

**Consistency and usage:** 4:5, at least 640×800. Independent of the heel series.

---

## 3. Lateral incision, baseline

**Placeholder:** Black SVG labeled `IMG-10C8A3` on the lateral-incision update 11 days before the sample “today.”

**App location:** Left Heel Post-op Recovery → Lateral incision → day record and photo gallery. Photo id `photo-seeded-baseline`. Angle **Baseline**. Caption: “Initial postoperative reference image.” Wound note on this day: “CONCERN: New redness along the proximal incision edge.”

**Generated filename:** `lateral-baseline.png`

**Image reference:** None. This is the canonical left heel, foot shape, drape, and lighting. Every later heel photo must stay consistent with it.

**Prompt:**

Photorealistic clinical photograph, 4:3 landscape, of the left lateral heel of a 64-year-old woman with light-to-medium olive skin and a few ordinary age spots on the ankle. The same modest left foot, no tattoos, no jewelry, no face. A fresh postoperative incision about 6 cm long along the lateral heel, edges not fully together, a slight moist sheen, a few pale closure strips, and new mild pink-redness along the proximal third of the incision only. Surrounding skin otherwise intact. Blue disposable drape, narrow white sheet edge, soft even clinical light, heel centered. No blood pools, no instruments, no date stamp, no logo. Small white sans-serif text in the lower-left corner reading exactly: AI Generated / Not real patient.

**Consistency and usage:** 4:3, at least 960×720, subject centered for `object-fit: contain`. This file is the image reference for the healing series and the donor-site photo.

---

## 4. Lateral incision, earlier healing, front

**Placeholder:** Black SVG labeled `IMG-5E72B1` on the lateral-incision update 9 days before the sample “today.”

**App location:** Photo id `photo-seeded-day-9-front`. Angle **Front**. Caption: “Healing progress reference image.” Wound note: “CHANGE: Wound edges are closer together than on the prior photo day.”

**Generated filename:** `lateral-day9-front.png`

**Image reference:** Use `pipe/lateral-baseline.png` as the image reference.

**Prompt:**

Same left lateral heel, same foot, same blue drape, same camera distance and soft clinical light as the reference. 4:3 landscape. The incision is the same line, now with edges sitting closer together than in the reference, less gap, and the proximal redness faded to a faint pink. Closure strips still present. Skin drier than the baseline photo. Do not move the incision, change the foot shape, or add a new wound. No face. Small white sans-serif text in the lower-left corner reading exactly: AI Generated / Not real patient.

**Consistency and usage:** Front view, heel centered. This is the front view for this day and the reference for the matching side view.

---

## 5. Lateral incision, earlier healing, side

**Placeholder:** Black SVG labeled `IMG-91F4D0`, same date as item 4.

**App location:** Photo id `photo-seeded-day-9-side`. Angle **Side**. Caption: “Healing progress reference image.” Same wound note as item 4.

**Generated filename:** `lateral-day9-side.png`

**Image reference:** Use `pipe/lateral-day9-front.png` as the image reference.

**Prompt:**

Same healing stage as the reference, photographed from about 30 degrees toward the side of the left heel so the incision is seen in slight profile. Same foot, same light-to-medium olive skin, same blue drape, same soft clinical light. Edges still closer together and proximal redness still faint pink. Do not reopen the wound or change its length. 4:3 landscape, heel centered, no face. Small white sans-serif text in the lower-left corner reading exactly: AI Generated / Not real patient.

**Consistency and usage:** Must read as the same day as `lateral-day9-front.png`, only the camera angle changes.

---

## 6. Lateral incision, dressing change

**Placeholder:** Black SVG labeled `IMG-3A6E9C` on the lateral-incision update 7 days before the sample “today.”

**App location:** Photo id `photo-seeded-day-7`. Angle **Progress check**. Caption: “Dressing-change reference image.” Wound note: “CONCERN: Increased tenderness reported when the dressing was removed.” Tenderness is not a visible finding; show the heel just after a dressing comes off.

**Generated filename:** `lateral-day7-progress.png`

**Image reference:** Use `pipe/lateral-day9-front.png` as the image reference.

**Prompt:**

Same left lateral heel and incision as the reference, a little further healed: edges approximated, faint pink line, no new opening. A soft gauze dressing has just been lifted and rests partly out of the lower frame, with a mild flush on the surrounding skin where the dressing sat. No active bleeding. Same foot shape, olive skin, blue drape, and clinical light. 4:3 landscape, front three-quarter view, heel centered, no face. Small white sans-serif text in the lower-left corner reading exactly: AI Generated / Not real patient.

**Consistency and usage:** Continue the same incision. Do not restart a fresh wound.

---

## 7. Lateral incision, mid-recovery, front

**Placeholder:** Black SVG labeled `IMG-C8472E` on the lateral-incision update 5 days before the sample “today.”

**App location:** Photo id `photo-seeded-day-5-front`. Angle **Front**. Caption: “Mid-recovery reference image.” Wound note: “CHANGE: The wound bed is smaller, with less drainage and drier surrounding skin than the last dressing change.”

**Generated filename:** `lateral-day5-front.png`

**Image reference:** Use `pipe/lateral-day7-progress.png` as the image reference.

**Prompt:**

Same left lateral heel incision, further healed than the reference. The open portion of the wound bed is visibly smaller, drainage is scant or absent, and the surrounding skin is drier and less flushed. The line of the incision is unchanged. Closure strips may be fewer. Same foot, same olive skin with ankle age spots, same blue drape and soft clinical light. No gauze covering the wound. 4:3 landscape, front view, heel centered, no face. Small white sans-serif text in the lower-left corner reading exactly: AI Generated / Not real patient.

**Consistency and usage:** Front view for this day. Reference for the matching side view and for the donor-site skin tone at this stage.

---

## 8. Lateral incision, mid-recovery, side

**Placeholder:** Black SVG labeled `IMG-64B9F5`, same date as item 7.

**App location:** Photo id `photo-seeded-day-5-side`. Angle **Side**. Caption: “Mid-recovery reference image.” Same wound note as item 7.

**Generated filename:** `lateral-day5-side.png`

**Image reference:** Use `pipe/lateral-day5-front.png` as the image reference.

**Prompt:**

Same healing stage as the reference, from about 30 degrees to the side of the left heel. Smaller dry wound bed, scant or no drainage, drier surrounding skin. Same foot and drape. Do not enlarge the wound. 4:3 landscape, heel centered, no face. Small white sans-serif text in the lower-left corner reading exactly: AI Generated / Not real patient.

**Consistency and usage:** Same day as `lateral-day5-front.png`. Only the angle changes.

---

## 9. Donor site, posterior heel

**Placeholder:** Black SVG labeled `IMG-D8A51F` on the donor-site update 5 days before the sample “today.” This is the only donor-site photo.

**App location:** Left Heel Post-op Recovery → Donor site → day record and photo gallery. Photo id `photo-seeded-donor-day-5`. Angle **Posterior**. Caption: “Donor-site progress reference image.” Wound note: “CHANGE: Donor-site wound is smaller and less moist than the earlier photo.” There is no earlier donor photo in the app; show a mid-healing donor site, not a fresh one.

**Generated filename:** `donor-day5-posterior.png`

**Image reference:** Use `pipe/lateral-day5-front.png` as the image reference for the same foot, skin tone, and lighting. Change the body site. Do not copy the lateral incision into this frame.

**Prompt:**

Photorealistic clinical photograph, 4:3 landscape, of the posterior heel of the same left foot as the reference: same light-to-medium olive skin, same age spots, same blue drape, same soft clinical light. The camera looks at the back of the heel, not the lateral side. A healing split-thickness donor site, smaller than a fresh donor bed, lightly pink, mostly dry, with early new epithelium and no shine of a wet wound. The lateral incision must not be the subject. No face, no blood, no instruments. Heel centered. Small white sans-serif text in the lower-left corner reading exactly: AI Generated / Not real patient.

**Consistency and usage:** Same person and same left foot as the lateral series. Different site and different camera angle.

---

## 10. Lateral incision, small distal opening

**Placeholder:** Black SVG labeled `IMG-2F7C68` on the lateral-incision update 3 days before the sample “today.”

**App location:** Photo id `photo-seeded-day-3`. Angle **Progress check**. Caption: “Latest dressing-change reference image.” Wound note: “CONCERN: A small open area remains at the distal tip of the incision.”

**Generated filename:** `lateral-day3-progress.png`

**Image reference:** Use `pipe/lateral-day5-front.png` as the image reference.

**Prompt:**

Same left lateral heel incision, more closed than the reference. Most of the line is a dry pale-pink seam. Only a small open area, a few millimeters, remains at the distal tip. Surrounding skin dry. No dressing covering the tip. Same foot, drape, and clinical light. 4:3 landscape, front view, heel centered, no face. Small white sans-serif text in the lower-left corner reading exactly: AI Generated / Not real patient.

**Consistency and usage:** The distal tip opening is the finding a reviewer should be able to see. Keep it small.

---

## 11. Lateral incision, prior day

**Placeholder:** Black SVG labeled `IMG-4D2A71` on the lateral-incision update 2 days before the sample “today.”

**App location:** Photo id `photo-seeded-prior-date`. Angle **Progress check**. Caption: “Seeded prior-day reference image.” Related notes that day: day note “Patient reported improved comfort.” and wound note “Observe incision edge and surrounding skin.” The stored update text is “Dressing changed; prior progress image available.”

**Generated filename:** `lateral-day2-progress.png`

**Image reference:** Use `pipe/lateral-day3-progress.png` as the image reference.

**Prompt:**

Same left lateral heel the day after the reference. The incision edge is calm and dry. The tiny distal opening is still present but slightly smaller and dry, not wet. A dressing change has just happened: a corner of clean gauze is visible at the edge of the frame and does not cover the incision. Same foot, olive skin, blue drape, soft clinical light. 4:3 landscape, front view, heel centered, no face. Small white sans-serif text in the lower-left corner reading exactly: AI Generated / Not real patient.

**Consistency and usage:** One quiet step forward from item 10. Do not introduce a new wound.

---

## 12. Routine set, shot 1

**Placeholder:** Black SVG labeled `IMG-7F3C92` on the lateral-incision update 1 day before the sample “today.” This date has three shots with blank angle names, so the viewer labels them Shot 1, Shot 2, and Shot 3.

**App location:** Photo id `photo-1`. Angle blank. Caption: “Seeded reference image.” The day’s update text is “Routine progress image set.” The donor site on this same date has no photo.

**Generated filename:** `lateral-day1-shot1.png`

**Image reference:** Use `pipe/lateral-day2-progress.png` as the image reference.

**Prompt:**

Routine overview of the same left lateral heel, one day further on. The incision is a dry pale line and the distal tip is nearly closed, with only a pinpoint dry spot. No gauze on the wound. Same foot shape, olive skin, blue drape, and clinical light as the reference. 4:3 landscape, straight front view, the whole heel and a little of the lateral ankle in frame, centered, no face. Small white sans-serif text in the lower-left corner reading exactly: AI Generated / Not real patient.

**Consistency and usage:** This is Shot 1, the overview. Shot 2 and Shot 3 must match this healing stage.

---

## 13. Routine set, shot 2

**Placeholder:** Black SVG labeled `IMG-A91D40`, same date as item 12. Viewer label **Shot 2**.

**App location:** Photo id `photo-2`. Angle blank. Caption: “Seeded reference image.”

**Generated filename:** `lateral-day1-shot2.png`

**Image reference:** Use `pipe/lateral-day1-shot1.png` as the image reference.

**Prompt:**

Closer clinical detail of the same left lateral heel on the same day as the reference. Fill more of the frame with the distal tip and the lower half of the incision. Same dry pale line and pinpoint dry spot, same skin, same light. Do not show a wider ankle view and do not change the healing stage. 4:3 landscape, no face, no gauze. Small white sans-serif text in the lower-left corner reading exactly: AI Generated / Not real patient.

**Consistency and usage:** Same day as Shot 1. Closer framing only.

---

## 14. Routine set, shot 3

**Placeholder:** Black SVG labeled `IMG-2B81EF`, same date as item 12. Viewer label **Shot 3**.

**App location:** Photo id `photo-3`. Angle blank. Caption: “Seeded reference image.”

**Generated filename:** `lateral-day1-shot3.png`

**Image reference:** Use `pipe/lateral-day1-shot1.png` as the image reference.

**Prompt:**

Wider clinical context of the same left heel on the same day as the reference. Show the lateral heel, the dry pale incision, and more of the ankle and lower calf than Shot 1, still with the blue drape around the limb. Same pinpoint dry distal spot, same skin tone, same soft light. Do not add a second wound and do not show the posterior donor site as the subject. 4:3 landscape, no face. Small white sans-serif text in the lower-left corner reading exactly: AI Generated / Not real patient.

**Consistency and usage:** Same day and same healing stage as Shot 1. Wider framing only.
