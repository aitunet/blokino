=== Tunet Core ===
Contributors: tunetdesign
Tags: blocks, effects, animation, gutenberg, carousel
Requires at least: 6.6
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 0.1.20
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Opt-in block effects, custom blocks and an effects runtime for the Tunet ecosystem — nothing hardcoded, everything driven by theme tokens.

== Description ==

Tunet Core is the shared engine of the Tunet ecosystem. It adds motion and interaction to WordPress blocks without ever hardcoding a style: every effect is opt-in per block, and every value (color, easing, duration) is read from the active theme's design tokens (`--tnt-*`). Switching themes never breaks your content.

**What it provides**

* **Effects on native blocks (tf* attributes).** A "Tunet Effects" panel is added to Group, Columns, Cover, Image, Heading, Paragraph and Buttons. Choose an entrance animation (fade-up, clip-reveal, mask-up, blur-in, scale-in, slide, text-stagger), a hover effect (lift, glow, tilt, magnetic, image-zoom, underline-grow), a scroll effect (parallax, sticky-pin, reveal, progress), blend modes and animated borders. A block with no effect attribute stays completely clean.
* **Custom blocks where the core falls short.** Section (advanced backgrounds: video, gradient, mesh, overlay, shape dividers), Marquee (infinite band), Counter (animated number on scroll), Before/After (image comparator), Slider and Testimonials (Swiper-powered), Brand, Icon and Badge.
* **A lightweight effects runtime.** Effects are detected per page and their assets are enqueued only when a block on the page uses them. Reveal uses the native IntersectionObserver; heavier libraries load on demand. `prefers-reduced-motion` is always respected.
* **An options panel + demo importer.** Global settings, JSON import/export, sidebar layout controls, and a per-theme demo importer with a progress bar and one-click rollback.

**Design principles**

* Nothing hardcoded — no effect is applied unless a block attribute activates it.
* Motor/design separation — functionality lives here, presentation lives in each theme.
* Degrades with dignity — the engine ships sensible token defaults, so effects and blocks look good under any theme, not only the Tunet themes.

== Installation ==

1. Upload the `tunet-core` folder to `/wp-content/plugins/`, or install it from the Plugins screen in wp-admin.
2. Activate the plugin through the *Plugins* menu in WordPress.
3. Open *Settings → Tunet Core* to configure global options, or edit any block and open the *Tunet Effects* panel to add an effect.

Requires WordPress 6.6 or newer and PHP 7.4 or newer.

== Frequently Asked Questions ==

= Does it change how my site looks by default? =

No. Every effect is opt-in per block. Installing and activating the plugin applies nothing until you add an effect from the Tunet Effects panel.

= Do I need a Tunet theme? =

No. The engine ships default design-token values so effects and blocks look good under any theme. Tunet premium themes provide bespoke tokens and patterns, but the plugin is theme-agnostic.

= Does it respect reduced-motion preferences? =

Yes. The runtime honors `prefers-reduced-motion: reduce`, disabling motion while keeping content visible.

= Is it accessible and performant? =

Effects load conditionally (only when a page uses them) and heavy libraries load on demand. The plugin follows WordPress standards for escaping, sanitization, nonces and internationalization.

== Changelog ==

= 0.1.20 =
* Spanish: the block editor panels are now translated. Every control, option and hint in the Tunet blocks and in the Tunet Effects panel ships in Spanish (es_ES, plus the common Latin American locales). Translations for editor scripts are served as JSON, which the plugin was not shipping — so the panels stayed in English no matter which language the site used.
* Badge: the editor script was missing its dependency list, which also meant WordPress never wired up its translations. Fixed, so the Badge panel is translated too and no longer relies on other scripts having loaded first.

= 0.1.19 =
* Section: an overlay can now be reinforced on small screens. A directional gradient scrim protects the text column on a wide screen but stops covering it on a phone, where the text spans the full width — the reinforcement is a separate layer, so the overlay you picked stays exactly as you set it. Off by default.

= 0.1.18 =
* Section, carousel and marquee: colours written as `rgb()`, `rgba()` or `hsl()` were silently dropped from the inline style by WordPress, which left an overlay opaque and hid the background photo entirely. Colours are now normalized before output, so the transparency you set is the transparency you get.
* Hardening: inline colour values are validated in full, closing a way to append extra CSS declarations through a colour field.

= 0.1.17 =
* Accessibility: with reduced motion the marquee and background video no longer just stop — they keep their design, start still, and offer a Play control, so a visitor who prefers less motion can still choose to see it. The marquee also stays on one line and can be scrolled by hand.
* Brand: the block gains an automatic variant that picks the main or the alternative logo from the active palette, so a dark style variation no longer shows a dark logo on a dark header.
* Section: background video gains poster, autoplay and loop controls. With a poster set, the video is not downloaded until it is actually going to play.

= 0.1.16 =
* Section: overlays can now be a **gradient**, not just a flat colour — so a photo background can carry a directional scrim and still be edited from the sidebar.
* Carousel: the same Colour | Gradient overlay is available per slide.
* Section: the background image and video pickers now show a thumbnail of what is set, with Replace and Remove, instead of an unlabelled button.
* Options: brand colours warn when they are overriding the active theme, explain that a style variation cannot change them while set, and can be cleared in one click. Setting a background without a text colour is called out, since that pair is what leaves a site unreadable.

= 0.1.15 =
* Accessibility: marquees and background videos now ship a pause/play control (WCAG 2.2.2). It is injected progressively, keyboard focusable, and hidden when the visitor prefers reduced motion.
* Carousels: responsive breakpoints, so slideshows show one slide on phones and expand on larger screens instead of cramming every slide in.
* Effects: styles and the runtime are now also detected in block templates and template parts, removing a flash of unstyled content on themes that place effects outside the post content.
* Importer: newly created categories are tracked and removed on rollback; manifest entries are guarded so an incomplete manifest no longer stops the import.
* Blocks: before/after keeps image dimensions, background video derives its MIME type from the attachment, and duplicated marquee content is hidden from assistive technology.
* Internal: shortcode rendering and meta description logic centralized in the engine.

= 0.1.14 =
* Editor: block previews for Brand and the shared carousel are inert (no accidental navigation while editing).
* Importer: content is inserted slashed so serialized block attributes survive the round-trip.
* Housekeeping and internationalization fixes across the admin, demo importer, blocks, extensions and runtime.

= 0.1.0 =
* Initial engine: tf* effect attributes on native blocks, custom blocks (Section, Marquee, Counter, Before/After, Slider, Testimonials, Brand, Icon, Badge), conditional effects runtime, options panel, and per-theme demo importer with rollback.

== Upgrade Notice ==

= 0.1.19 =
Overlays can be reinforced on small screens, so a hero built around a side gradient stays readable on a phone. Optional, nothing changes unless you turn it on.

= 0.1.18 =
Fixes overlays turning opaque and hiding the background image when the colour was set with transparency. Recommended for all sites.

= 0.1.17 =
Reduced-motion visitors get a Play control instead of frozen media, the Brand block picks the right logo for the active palette, and background video gains poster/autoplay/loop controls. Recommended for all sites.

= 0.1.16 =
Gradient overlays for sections and carousels, media previews in the block sidebar, and clearer brand-colour overrides. Recommended for all sites.

= 0.1.15 =
Accessibility improvements (pause/play for motion), responsive carousel breakpoints and importer robustness. Recommended for all sites.

= 0.1.14 =
Editor-safety and importer robustness fixes. Recommended for all sites.
