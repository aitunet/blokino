=== Blokino – Block Effects & Custom Blocks ===
Contributors: tunetdesign
Tags: blocks, effects, animation, block-editor, carousel
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Opt-in entrance, hover and scroll effects for the blocks you already use, plus sections, sliders, marquees and counters, driven by theme tokens.

== Description ==

Blokino adds motion and interaction to WordPress blocks without ever hardcoding a style: every effect is opt-in per block, and every value (color, easing, duration) is read from the active theme's design tokens (`--tnt-*`). Switching themes never breaks your content.

**What it provides**

* **Effects on native blocks (tf* attributes).** A "Blokino Effects" panel is added to Group, Columns, Cover, Image, Heading, Paragraph and Buttons. Choose an entrance animation (fade-up, clip-reveal, mask-up, blur-in, scale-in, slide, text-stagger), a hover effect (lift, glow, tilt, magnetic, image-zoom, underline-grow), a scroll effect (parallax, sticky-pin, reveal, progress), blend modes and animated borders. A block with no effect attribute stays completely clean.
* **Custom blocks where the core falls short.** Section (advanced backgrounds: video, gradient, mesh, overlay, shape dividers), Marquee (infinite band), Counter (animated number on scroll), Before/After (image comparator), Slider and Testimonials (Swiper-powered), Brand, Icon and Badge.
* **A lightweight effects runtime.** Effects are detected per page and their assets are enqueued only when a block on the page uses them. Reveal uses the native IntersectionObserver and scroll effects run on requestAnimationFrame — no third-party animation library. Swiper (MIT) is the only bundled library, loaded solely on pages with a slider. `prefers-reduced-motion` is always respected.
* **An options panel + demo importer.** Global settings, JSON import/export, sidebar layout controls, and a per-theme demo importer with a progress bar and one-click rollback.
* **A free theme to start with.** Blokmark, our free block theme, is built for this engine: a designed home the moment you activate it, three looks, blog and page templates. The Themes screen links to it, then lists the premium themes.
* **A Get started screen.** Site status, the three steps to a finished site (theme → demo → effects) and the doors to the documentation.

**Design principles**

* Nothing hardcoded — no effect is applied unless a block attribute activates it.
* Motor/design separation — functionality lives here, presentation lives in each theme.
* Degrades with dignity — the engine ships sensible token defaults, so effects and blocks look good under any theme, not only the ones built for it.

**Who makes it**

Developed and maintained by TUNET Design (https://tunetdesign.com). Source and issue tracker: https://github.com/aitunet. Support: info@tunetdesign.com

== Installation ==

1. Upload the `blokino` folder to `/wp-content/plugins/`, or install it from the Plugins screen in wp-admin.
2. Activate the plugin through the *Plugins* menu in WordPress.
3. Open *Blokino → Settings* (its own item in the admin menu) to configure global options, or edit any block and open the *Blokino Effects* panel to add an effect.

Requires WordPress 6.6 or newer and PHP 7.4 or newer.

== Frequently Asked Questions ==

= Does it change how my site looks by default? =

No. Every effect is opt-in per block. Installing and activating the plugin applies nothing until you add an effect from the Blokino Effects panel.

= Do I need a special theme? =

No. The engine ships default design-token values so effects and blocks look good under any theme; the plugin is theme-agnostic. If you want a theme made for it, Blokmark is free at https://www.tunetdesign.com/downloads/blokmark/, and the premium themes add bespoke tokens, patterns and a one-click demo.

= Does it respect reduced-motion preferences? =

Yes. The runtime honors `prefers-reduced-motion: reduce`, disabling motion while keeping content visible.

= Does the plugin send any data anywhere? =

No. Nothing is tracked or sent, and nothing runs in the background. The plugin talks to exactly two external services, both listed below under *External services*: the tunetdesign.com theme catalog (only when you open *Blokino → Themes*) and Google Fonts (only if you pick a Google font in *Settings → Typography*).

= Is it accessible and performant? =

Effects load conditionally (only when a page uses them); the runtime is vanilla JavaScript and the only bundled library, Swiper, is enqueued on pages with a slider. The plugin follows WordPress standards for escaping, sanitization, nonces and internationalization.

== External services ==

Blokino works fully offline. It connects to a third-party service only in the two cases below, never in the background and never with personal data.

**tunetdesign.com theme catalog** — When you open *Blokino → Themes* in wp-admin, the plugin sends one GET request to `https://tunetdesign.com/wp-json/tunet/v1/themes` to list the themes designed for this engine (name, tagline, price, preview image, demo link). The request carries no site URL, no user data and a neutral user agent; the response is cached for 12 hours. The screen is optional — if you never open it, the request is never made. Service by TUNET Design: terms https://tunetdesign.com/terms/ · privacy https://tunetdesign.com/privacy/

**Google Fonts** — Only if you choose a Google font family in *Blokino → Settings → Typography*, the front end and the editor load that family's stylesheet from `https://fonts.googleapis.com` (and the font files from `fonts.gstatic.com`), like most themes and page builders do. Visitors' browsers request the files directly from Google; the plugin sends nothing itself. Leave the typography setting on your theme's fonts and no request to Google is made. Google Fonts terms https://developers.google.com/fonts/terms · privacy https://policies.google.com/privacy

**Bundled library** — Swiper 11 (MIT, https://github.com/nolimits4web/swiper) is included as a minified file and enqueued only on pages that contain a slider; its readable source lives in that repository. The plugin's own JavaScript is shipped unminified. Full source: https://github.com/aitunet/blokino

== Screenshots ==

1. The *Blokino Effects* panel is added to every block — including the ones that ship with WordPress. The first option is "None (clean block)", and it is the default: nothing moves until you ask it to.
2. Ten blocks for the things the editor does not cover: sections, sliders, marquees, counters, icons, badges, testimonials, a before/after comparer, a brand block and breadcrumbs.
3. *Blokino Section* handles the backgrounds a hero needs — image, video, gradient, mesh — with an overlay you control, including a reinforcement that only applies on small screens.
4. Global settings: brand colours, typefaces and shape. Left empty, the active theme decides; a value here overrides that token site-wide.
5. Themes can ship a demo. The importer creates it as native, editable blocks, copies every image into your Media Library, and undoes the whole thing with one click.

== Changelog ==
= 1.0.0 =
* First release on WordPress.org.
