=== Blokino – Block Effects & Custom Blocks ===
Contributors: tunetdesign
Tags: blocks, effects, animation, block-editor, carousel
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Opt-in entrance, hover and scroll effects for the blocks you already use, plus sections, sliders, marquees and counters, driven by theme tokens.

== Description ==

Blokino adds motion and interaction to WordPress blocks without ever hardcoding a style: every effect is opt-in per block, and every value (color, easing, duration) is read from the active theme's design tokens (`--tnt-*`). Switching themes never breaks your content.

**What it provides**

* **Effects on native blocks (tf* attributes), previewed in the editor.** A "Blokino Effects" panel is added to Group, Columns, Cover, Image, Heading, Paragraph, Buttons, Media & Text, Query Loop and more. Hover, borders and blend modes show live on the canvas, and an entrance plays the moment you pick it. Choose an entrance animation (fade-up, clip-reveal, mask-up, blur-in, scale-in, slide, text-stagger), a hover effect (lift, glow, tilt, magnetic, image-zoom, underline-grow), a scroll effect (parallax, sticky-pin, reveal, progress), blend modes and animated borders. A block with no effect attribute stays completely clean.
* **Custom blocks where the core falls short.** Section (advanced backgrounds: video, gradient, mesh, overlay, shape dividers), Marquee (infinite band), Counter (animated number on scroll), Before/After (image comparator), Slider and Testimonials (Swiper-powered), Brand, Icon and Badge.
* **A lightweight effects runtime.** Effects are detected per page and their assets are enqueued only when a block on the page uses them. Reveal uses the native IntersectionObserver and scroll effects run on requestAnimationFrame — no third-party animation library. Swiper (MIT) is the only bundled library, loaded solely on pages with a slider. `prefers-reduced-motion` is always respected.
* **An options panel + demo importer.** Global settings, JSON import/export, sidebar layout controls, and a per-theme demo importer with a progress bar and one-click rollback.
* **A free theme to start with.** Blokmark, our free block theme on WordPress.org, is built for this engine: a designed home the moment you activate it, three looks, blog and page templates. The Themes screen installs it with WordPress's own theme installer, then lists the premium themes.
* **Ready-made patterns and a one-click playground.** Six patterns that work with any theme (heroes, cards, key figures, logo band, testimonials) and a "Try it on a new page" button that opens a draft with effects already in place.
* **A Get started screen.** It starts with what the plugin does — add an effect, insert a block or pattern — and keeps themes as an optional extra.

**Design principles**

* Nothing hardcoded — no effect is applied unless a block attribute activates it.
* Motor/design separation — functionality lives here, presentation lives in each theme.
* Degrades with dignity — the engine ships sensible token defaults, so effects and blocks look good under any theme, not only the ones built for it.

**Who makes it**

Developed and maintained by TUNET Digital Agency (https://tunetdesign.com). Source and issue tracker: https://github.com/aitunet. Support: info@tunetdesign.com

== Installation ==

1. Upload the `blokino` folder to `/wp-content/plugins/`, or install it from the Plugins screen in wp-admin.
2. Activate the plugin through the *Plugins* menu in WordPress.
3. Open *Blokino → Settings* (its own item in the admin menu) to configure global options, or edit a supported block (Group, Image, Heading…) and open the *Blokino Effects* panel to add an effect.

Requires WordPress 6.6 or newer and PHP 7.4 or newer.

== Frequently Asked Questions ==

= Does it change how my site looks by default? =

No. Every effect is opt-in per block. Installing and activating the plugin applies nothing until you add an effect from the Blokino Effects panel. With a theme that is not made for Blokino, the plugin also adds no menus, no /work/ address and nothing to your page head: the portfolio content type is a setting (*Settings → General*), off by default.

= Do I need a special theme? =

No. The engine ships default design-token values so effects and blocks look good under any theme; the plugin is theme-agnostic. If you want a theme made for it, Blokmark is free on WordPress.org (Appearance → Themes → Add New, search "Blokmark"), and the premium themes add bespoke tokens, patterns and a one-click demo.

= Does it work with Elementor or the Classic Editor? =

No. Blokino works inside the block editor (Gutenberg): its effects panel, blocks and patterns live there. Pages built with Elementor or edited with the Classic Editor do not get them. The Get started screen tells you when one of those is active.

= Does it respect reduced-motion preferences? =

Yes. The runtime honors `prefers-reduced-motion: reduce`, disabling motion while keeping content visible.

= Does the plugin send any data anywhere? =

Not on its own. Nothing is tracked and nothing runs in the background. The plugin talks to three external services, each only when you act, all listed under *External services*: the theme catalog (when you open *Blokino → Themes*), the optional deactivation feedback (only if you press *Submit & deactivate*) and Google Fonts (only if you pick a Google font in *Settings → Typography*).

= Is it accessible and performant? =

Effects load conditionally (only when a page uses them); the runtime is vanilla JavaScript and the only bundled library, Swiper, is enqueued on pages with a slider. The plugin follows WordPress standards for escaping, sanitization, nonces and internationalization.

== External services ==

Blokino works fully offline. It connects to an external service only in the three cases below, never in the background, and sends personal data only if you explicitly allow it (deactivation feedback with "You may contact me" ticked).

**tunetdesign.com theme catalog** — When you open *Blokino → Themes* in wp-admin, the plugin sends one GET request to `https://tunetdesign.com/wp-json/tunet/v1/themes` to list the themes designed for this engine (name, tagline, price, preview image, demo link). The request carries no site URL, no user data and a neutral user agent; the response is cached for 12 hours. The theme preview images on that screen are loaded by your browser from tunetdesign.com, with no referrer sent. The screen is optional — if you never open it, the request is never made. Service by TUNET Digital Agency: terms https://tunetdesign.com/terms/ · privacy https://tunetdesign.com/privacy/

**Deactivation feedback (optional)** — When you click *Deactivate* on Blokino in the Plugins screen, a dialog asks why you are leaving. Nothing is sent unless you pick a reason and press *Submit & deactivate*; *Skip & deactivate* deactivates right away and sends nothing. On submit, your site's server sends one POST request to `https://tunetdesign.com/wp-json/tunet-store/v1/feedback` with the reason, the text you typed, the Blokino, WordPress and PHP versions, the admin language and the active theme's name, with a neutral user agent. Your email address and your site's URL are included only if you tick *You may contact me about this*. Answers are read by the plugin's authors to fix what made people leave. Service by TUNET Digital Agency: terms https://tunetdesign.com/terms/ · privacy https://tunetdesign.com/privacy/

**Google Fonts** — Only if you choose a Google font family in *Blokino → Settings → Typography*, the front end and the editor load that family's stylesheet from `https://fonts.googleapis.com` (and the font files from `fonts.gstatic.com`), like most themes and page builders do. Visitors' browsers request the files directly from Google; the plugin sends nothing itself. Leave the typography setting on your theme's fonts and no request to Google is made. Google Fonts terms https://developers.google.com/fonts/terms · privacy https://policies.google.com/privacy

**Bundled library** — Swiper 11 (MIT, https://github.com/nolimits4web/swiper) is included as a minified file and enqueued only on pages that contain a slider; its readable source lives in that repository. The plugin's own JavaScript is shipped unminified. Full source: https://github.com/aitunet/blokino

== Screenshots ==

1. The *Blokino Effects* panel is added to Group, Columns, Column, Cover, Image, Gallery, Media & Text, Video, Heading, Paragraph, List, Quote, Separator, Buttons, Site Title, Post Title, Featured Image and Query Loop, and to Blokino's Section, Marquee, Counter and Before/After blocks (developers can change the list with the blokino_effect_blocks filter). The first option is "None (clean block)", and it is the default: nothing moves until you ask it to.
2. Ten blocks for the things the editor does not cover: sections, sliders, marquees, counters, icons, badges, testimonials, a before/after comparer, a brand block and breadcrumbs.
3. *Blokino Section* handles the backgrounds a hero needs — image, video, gradient, mesh — with an overlay you control, including a reinforcement that only applies on small screens.
4. Global settings: brand colours, typefaces and shape. Left empty, the active theme decides; a value here overrides that token site-wide.
5. Export your Blokino settings to a JSON file and import them on another site, for example to move a design from staging to production.

== Changelog ==
= 1.2.0 =
* New: effects preview in the editor. Hover, animated borders, blend modes, outline text, parallax and zoom show live on the canvas; "Preview animation" (and picking an entrance) plays it right there.
* New: "Blokino" category in the inserter and in Patterns, with six patterns that work with any theme, and a preview for every block.
* New: "Try it on a new page" on Get started opens a draft with sample sections that already use effects.
* New: Section is inserted ready to use — sample content and padding, plus variations: Hero with gradient, Hero with image, Color band. Text and link color support, "Space between blocks" and a color under the image.
* New: Testimonials and Content slider are inserted with sample items.
* New: the Effects panel is available on more blocks (Column, Gallery, Media & Text, Video, List, Quote, Separator, Site Title, Post Title, Featured Image, Query Loop); developers can change the list with the blokino_effect_blocks filter.
* Improved: default colors follow the active theme's palette (light or dark), so Blokino blocks and effects match any theme. Content made with a Blokino theme keeps its spacing and colors with other themes.
* Improved: every control in the Effects panel explains what it does; Outline and Blend appear only on blocks where they show; clearer easing names.
* Improved: Get started leads with the effect; Settings says what it affects when the theme is not made for Blokino.
* Changed: with a theme not made for Blokino, the plugin no longer registers the portfolio (Projects at /work/), changes how core block styles load or prints a meta description. The portfolio is a setting (Settings → General); sites that already have Blokino projects keep it.
* Fix: Text fill ends on the text's own color (a white heading stays white).
* Fix: when a Section has a text color, its headings use it too, even in themes that color every heading (Twenty Twenty-Four, Astra, Kadence); a heading with its own color keeps it.
* Accessibility: carousels with autoplay get a pause button and stop while focused, and announce in the site language; Before/After lets you scroll on touch and says what it shows; Breadcrumbs mark only the current page.
* Developers: blokino_demo_imported and blokino_demo_removed actions.
* Fix: saving or importing Blokino settings no longer removes a site logo that was set elsewhere (Site Logo block or Customizer). The Main logo field now shows that logo when Blokino has none of its own.
* Security: background image URLs in Section and Content slider are encoded inside url(), so a crafted URL can no longer add CSS to the page.
* Fix: if the effects script cannot run (a caching plugin delays it, a blocker stops it), animated content is shown after 3 seconds instead of staying hidden.
* Fix: the welcome notice appears only on the Dashboard and Plugins screens.
* Privacy: the deactivation feedback sends only your theme's name, as the dialog says. Theme preview images on the Themes screen load only from the catalog's domain, with no referrer.
* Hardening: the demo wizard checks the permission to install and to activate separately, shows server messages as plain text, keeps the reason when a plugin install fails, and reports a failed Undo.
* New: deleting the plugin removes its settings and cached data (your content, media, projects and logo are kept). Deactivating it removes its /work/ address rules.

= 1.1.1 =
* Fix: in the editor, Marquee items stay on one row like the front-end band (scroll sideways to reach the last ones) instead of wrapping onto several lines.
* Fix: in the editor, Marquee no longer forces its row layout onto blocks nested inside an item (a query, its cards), and its separators no longer jump when you select an item.
* Fix: in the editor, a rounded Section keeps its rounded corners on the background image and the overlay.
* Fix: the Badge block now previews in the editor as on the front end (rotating ring and arrow) instead of a plain line of text.
* Fix: Text fill accent words are now coloured in the editor too, as on the front end, without changing the text you edit.
* New: "category" icon in the Icon block set.
* Fix: in the editor, Section content now lays out exactly like on the front end. Its blocks sit directly inside the section (no extra wrappers), so multi-column theme layouts no longer stack in the editor.
* New: an optional feedback dialog when you deactivate Blokino (why you are leaving, plus a text box). Nothing is sent unless you press "Submit & deactivate"; your email and site URL only if you tick "You may contact me". See External services.

= 1.1.0 =
* Blokmark, the free theme made for this engine, is now on WordPress.org: the Themes and Get started screens install it with WordPress's own theme installer instead of linking to a download.
* Fix: the editor preview of Content slider and Testimonials no longer fails with "414 Request-URI Too Large" when a carousel has many slides (the preview is now requested by POST). Same for the Brand block.
* Fix: Section no longer triggers the "wp.blockEditor.useSetting is deprecated" warning in the editor; it reads the theme palette and gradients with useSettings.

= 1.0.0 =
* First release on WordPress.org.
