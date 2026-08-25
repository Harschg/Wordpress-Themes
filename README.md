# Stillframe

A custom WordPress theme for a personal photography and projects site. Laid-back, dusk-gold, slow motion.

## Pages

| Page | Template | Content |
| --- | --- | --- |
| Home | `front-page.php` | Directory into About, Gallery, and Projects |
| About | `page-about.php` (slug `about`) or assign the **About** page template | Bio + optional portrait (featured image) |
| Gallery | `/gallery/` archive | `photograph` custom post type |
| Projects | `/projects/` archive | `project` custom post type |

## Install

1. Copy this folder into `wp-content/themes/` (folder name can be `stillframe` or `Wordpress-Themes`).
2. In **Appearance → Themes**, activate **Stillframe**.
3. **Settings → Permalinks → Save** once so the gallery and projects URLs work.
4. **Settings → Reading**: set a static front page if you want, or leave it — `front-page.php` is used either way.
5. Create a page with slug `about` (or assign the About template).
6. Add **Photographs** (featured image + location / camera / year in the sidebar).
7. Add **Projects** (excerpt, stack, GitHub, live URL, featured image).
8. Optional: **Appearance → Customize → Stillframe** for the home vibe line and footer note. **Appearance → Menus** to replace the default nav.

Local WordPress options: [Local](https://localwp.com/), Laragon, or `wp-env`.

## Motion

- Load curtain with the site name, then content fades up.
- Internal links play a leave animation before the next page.
- Buttons and directory cards ripple on click and lift on hover.
- Respects `prefers-reduced-motion`.

## Customize

Site title, tagline, and custom logo come from **Appearance → Customize**. Photograph and project fields are native meta boxes — no extra plugins required.
