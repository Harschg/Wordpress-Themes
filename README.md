# Stillframe

A custom WordPress theme for a personal photography and projects site. Laid-back, dusk-gold, slow motion.

## Pages

| Page | Template | Content |
| --- | --- | --- |
| Home | `front-page.php` | Directory into About, Gallery, Projects, and Contact |
| About | `page-about.php` (slug `about`) or assign the **About** page template | Bio + optional portrait (featured image) |
| Gallery | `/gallery/` archive | Series buttons first, then photographs with no series |
| Series | `/series/your-series/` | All photographs assigned to that series |
| Projects | `/projects/` archive | `project` custom post type |
| Contact | `page-contact.php` (slug `contact`) or assign the **Contact** page template | Native form + optional email / Instagram / GitHub |

## Install

1. Copy this folder into `wp-content/themes/` (folder name can be `stillframe` or `Wordpress-Themes`).
2. In **Appearance → Themes**, activate **Stillframe**.
3. **Settings → Permalinks → Save** once so the gallery and projects URLs work.
4. **Settings → Reading**: set a static front page if you want, or leave it — `front-page.php` is used either way.
5. Create a page with slug `about` and a page with slug `contact` (or assign the About / Contact templates).
6. Add **Photographs** (featured image + location / camera / year in the sidebar). To group them, create a **Series** under Photographs → Series and check it on each photo. The gallery shows a Series section; clicking a series opens only those photos.
7. Add **Projects** (excerpt, stack, GitHub, live URL, featured image).
8. Optional: **Appearance → Customize → Stillframe** for a home subtitle, footer note, contact email, and social URLs. **Appearance → Menus** to replace the default nav.

Local WordPress options: [Local](https://localwp.com/), Laragon, or `wp-env`.

## Motion

- Spinner while the page loads, then the page fades in.
- Buttons and directory cards ripple on click and lift on hover.
- Respects `prefers-reduced-motion`.

## Customize

Site title, tagline, and custom logo come from **Appearance → Customize**. Photograph and project fields are native meta boxes — no extra plugins required.
