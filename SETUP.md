# Radian Training — WordPress Setup Guide

This theme is a 1:1 conversion of the static design in the `/Design` folder into a working
WordPress theme. Each design page (`index`, `cisrs`, `getmie-safe`, `certificate`, `course`,
`enrol`) is reproduced exactly using its own React app + stylesheet, wired together through
WordPress page templates.

Follow these steps **in order**. It takes about 5 minutes.

---

## 1. Activate the theme

1. Log into **WP Admin** → **Appearance → Themes**.
2. Find **“Radian Training”** and click **Activate**.

(The theme folder is `wp-content/themes/Training` — already in place.)

---

## 2. Set pretty permalinks  ⚠️ REQUIRED

The site’s internal links use clean URLs like `/cisrs/` and `/course/?id=cisrs-l1`.
These only work with “Post name” permalinks.

1. Go to **Settings → Permalinks**.
2. Select **Post name**.
3. Click **Save Changes**.

---

## 3. Create the pages  ⚠️ slugs must match EXACTLY

Go to **Pages → Add New** and create the following pages. The **title** can be anything,
but the **slug (URL)** must be exactly as shown — the theme picks the right template and
stylesheet from the slug.

| Page title (your choice) | Slug (must match) | Template auto‑used      |
|--------------------------|-------------------|-------------------------|
| Home                     | `home`            | `front-page.php`        |
| CISRS OTS                | `cisrs`           | `page-cisrs.php`        |
| Getmie Safe              | `getmie-safe`     | `page-getmie-safe.php`  |
| Certificates             | `certificate`     | `page-certificate.php`  |
| Course                   | `course`          | `page-course.php`       |
| Enrol                    | `enrol`           | `page-enrol.php`        |

> To set/confirm a slug: open the page, and in the **Permalink** box under the title set the
> URL slug. (In the block editor it’s in the right sidebar → **Page → URL**.)

You don’t need to add any content to these pages — the template renders everything.
Leave the page content empty.

---

## 4. Set the front (home) page

1. Go to **Settings → Reading**.
2. Under **“Your homepage displays”**, choose **A static page**.
3. Set **Homepage** = **Home** (the page you made with slug `home`).
4. Click **Save Changes**.

> The home page automatically gets `front-page.php` (the full landing page with the 3D hero,
> calendar, gallery, videos, etc.) and the intro loader animation.

---

## 5. Done — verify

Visit the site front-end. You should see:

- **Home** (`/`) — animated intro loader, 3D scaffold hero, marquee, about, CISRS, Getmie,
  interactive training calendar, gallery, videos, CTA.
- **`/cisrs/`** — CISRS OTS catalogue (horizontal scroll strip of 6 courses).
- **`/getmie-safe/`** — Working-at-Height + Rescue tracks.
- **`/certificate/`** — certificate verification portal (try the demo chips at the bottom).
- **`/course/?id=cisrs-l1`** — course detail (reached by clicking any course card).
- **`/enrol/?id=cisrs-l1`** — multi-step enrolment wizard (reached from a course page).

The top navigation and the links between pages are all wired to these WordPress URLs.

---

## How it’s built (for future editing)

```
Training/
├── style.css                 → theme header (WordPress requires this)
├── functions.php             → enqueues per-page CSS + React/Babel/Three.js/GSAP,
│                               exposes window.RADIAN_URLS, sets body classes
├── header.php                → <head>, intro loader (home only), shared top nav
├── footer.php                → wp_footer()
├── front-page.php            → HOME page React app (was Design/index.html)
├── page-cisrs.php            → CISRS page React app (was Design/cisrs.html)
├── page-getmie-safe.php      → Getmie page React app (was Design/getmie-safe.html)
├── page-certificate.php      → Certificate portal React app
├── page-course.php           → Course detail React app (reads ?id=)
├── page-enrol.php            → Enrolment wizard React app (reads ?id=)
├── assets/css/
│   ├── home.css  cisrs.css  getmie.css
│   └── certificate.css  course.css  enrol.css   (one verbatim stylesheet per page)
└── Design/                   → original design + shared JS, still used live:
    ├── image-slot.js   (drag-to-preview image placeholders)
    ├── scaffold3d.js   (interactive 3D scaffold — window.createScaffold3D)
    ├── motion.js       (GSAP scroll reveals — window.RadianMotion)
    ├── course-data.js  (all course content — window.COURSES_DATA)
    └── loader.js       (intro reveal animation)
```

- **React + Babel** run in the browser (same as the original design), so the page templates
  contain the JSX inline in `<script type="text/babel">` blocks — edit them just like the
  original `.html` files.
- **Internal links** use `window.RADIAN_URLS` (printed in `<head>` by `functions.php`) so they
  always point at the correct WordPress URLs regardless of domain.
- **Course content** lives in `Design/course-data.js`. To add/edit a course, edit that file
  (and the course arrays in `page-cisrs.php` / `page-getmie-safe.php` if you want it listed).

### About the images
The photo areas use the `<image-slot>` element (dashed “Drop a photo” boxes). These let you
preview an image by dragging a file in, but **previews are not saved** — they’re a design
placeholder. To put permanent images on the site, replace an `<image-slot …></image-slot>`
tag in the relevant template with a normal `<img>` tag, e.g.:

```html
<img src="<?php echo get_template_directory_uri(); ?>/assets/img/your-photo.jpg"
     style="width:100%;height:100%;object-fit:cover;" alt="…"/>
```
(Create an `assets/img/` folder for your photos, or use the Media Library URL.)

### Notes
- The certificate verification and enrolment forms are **front-end demos** (no data is sent to
  a server yet). When you’re ready to make them live, wire the submit handlers in
  `page-certificate.php` / `page-enrol.php` to the WordPress REST API or an email/CRM endpoint.
- The training calendar, course catalogue, prices (shown in TT$) and sample certificate records
  are the same demo data as the original design. Update them in the template / `course-data.js`.
- Requires an internet connection on the visitor’s browser: React, Babel, Three.js and GSAP load
  from CDNs (exactly as the original design did).
