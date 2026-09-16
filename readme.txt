=== Tunet Core ===
Contributors: tunetdesign
Tags: blocks, effects, animation, block-editor, carousel
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.50
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Opt-in block effects, custom blocks and an effects runtime for the Tunet ecosystem — nothing hardcoded, everything driven by theme tokens.

== Description ==

Tunet Core is the shared engine of the Tunet ecosystem. It adds motion and interaction to WordPress blocks without ever hardcoding a style: every effect is opt-in per block, and every value (color, easing, duration) is read from the active theme's design tokens (`--tnt-*`). Switching themes never breaks your content.

**What it provides**

* **Effects on native blocks (tf* attributes).** A "Tunet Effects" panel is added to Group, Columns, Cover, Image, Heading, Paragraph and Buttons. Choose an entrance animation (fade-up, clip-reveal, mask-up, blur-in, scale-in, slide, text-stagger), a hover effect (lift, glow, tilt, magnetic, image-zoom, underline-grow), a scroll effect (parallax, sticky-pin, reveal, progress), blend modes and animated borders. A block with no effect attribute stays completely clean.
* **Custom blocks where the core falls short.** Section (advanced backgrounds: video, gradient, mesh, overlay, shape dividers), Marquee (infinite band), Counter (animated number on scroll), Before/After (image comparator), Slider and Testimonials (Swiper-powered), Brand, Icon and Badge.
* **A lightweight effects runtime.** Effects are detected per page and their assets are enqueued only when a block on the page uses them. Reveal uses the native IntersectionObserver and scroll effects run on requestAnimationFrame — no third-party animation library. Swiper (MIT) is the only bundled library, loaded solely on pages with a slider. `prefers-reduced-motion` is always respected.
* **An options panel + demo importer.** Global settings, JSON import/export, sidebar layout controls, and a per-theme demo importer with a progress bar and one-click rollback.
* **A free theme to start with.** Tunet Starter, our block theme on WordPress.org, is built for this engine: a designed home the moment you activate it, three looks, blog and page templates. The Themes screen links to it, and to the premium themes from the tunetdesign.com catalog — open it when you want it; it never nags.
* **A Get started screen.** Site status, the three steps to a finished site (theme → demo → effects) and the doors to the documentation.

**Design principles**

* Nothing hardcoded — no effect is applied unless a block attribute activates it.
* Motor/design separation — functionality lives here, presentation lives in each theme.
* Degrades with dignity — the engine ships sensible token defaults, so effects and blocks look good under any theme, not only the Tunet themes.

**Who makes it**

Developed and maintained by TUNET Design (https://tunetdesign.com). Source and issue tracker: https://github.com/aitunet. Support: ai@tunetdesign.com

== Installation ==

1. Upload the `tunet-core` folder to `/wp-content/plugins/`, or install it from the Plugins screen in wp-admin.
2. Activate the plugin through the *Plugins* menu in WordPress.
3. Open *Tunet Core → Settings* (its own item in the admin menu) to configure global options, or edit any block and open the *Tunet Effects* panel to add an effect.

Requires WordPress 6.6 or newer and PHP 7.4 or newer.

== Frequently Asked Questions ==

= Does it change how my site looks by default? =

No. Every effect is opt-in per block. Installing and activating the plugin applies nothing until you add an effect from the Tunet Effects panel.

= Do I need a Tunet theme? =

No. The engine ships default design-token values so effects and blocks look good under any theme — the plugin is theme-agnostic. If you want a theme made for it, Tunet Starter is free on WordPress.org (Appearance → Themes → Add New, search "Tunet Starter"), and the premium themes add bespoke tokens, patterns and a one-click demo.

= Does it respect reduced-motion preferences? =

Yes. The runtime honors `prefers-reduced-motion: reduce`, disabling motion while keeping content visible.

= Does the plugin send any data anywhere? =

No. Nothing is tracked or sent, and nothing runs in the background. The plugin talks to exactly two external services, both listed below under *External services*: the tunetdesign.com theme catalog (only when you open *Tunet Core → Themes*) and Google Fonts (only if you pick a Google font in *Settings → Typography*).

= Is it accessible and performant? =

Effects load conditionally (only when a page uses them); the runtime is vanilla JavaScript and the only bundled library, Swiper, is enqueued on pages with a slider. The plugin follows WordPress standards for escaping, sanitization, nonces and internationalization.

== External services ==

Tunet Core works fully offline. It connects to a third-party service only in the two cases below, never in the background and never with personal data.

**tunetdesign.com theme catalog** — When you open *Tunet Core → Themes* in wp-admin, the plugin sends one GET request to `https://tunetdesign.com/wp-json/tunet/v1/themes` to list the themes designed for this engine (name, tagline, price, preview image, demo link). The request carries no site URL, no user data and a neutral user agent; the response is cached for 12 hours. The screen is optional — if you never open it, the request is never made. Service by TUNET Design: terms https://tunetdesign.com/terms/ · privacy https://tunetdesign.com/privacy/

**Google Fonts** — Only if you choose a Google font family in *Tunet Core → Settings → Typography*, the front end and the editor load that family's stylesheet from `https://fonts.googleapis.com` (and the font files from `fonts.gstatic.com`), like most themes and page builders do. Visitors' browsers request the files directly from Google; the plugin sends nothing itself. Leave the typography setting on your theme's fonts and no request to Google is made. Google Fonts terms https://developers.google.com/fonts/terms · privacy https://policies.google.com/privacy

**Bundled library** — Swiper 11 (MIT, https://github.com/nolimits4web/swiper) is included as a minified file and enqueued only on pages that contain a slider; its readable source lives in that repository. The plugin's own JavaScript is shipped unminified. Full source: https://github.com/aitunet/tunet-core

== Screenshots ==

1. The *Tunet Effects* panel is added to every block — including the ones that ship with WordPress. The first option is "None (clean block)", and it is the default: nothing moves until you ask it to.
2. Nine blocks for the things the editor does not cover: sections, sliders, marquees, counters, icons, badges, testimonials, a before/after comparer and a brand block.
3. *Tunet Section* handles the backgrounds a hero needs — image, video, gradient, mesh — with an overlay you control, including a reinforcement that only applies on small screens.
4. Global settings: brand colours, typefaces and shape. Left empty, the active theme decides; a value here overrides that token site-wide.
5. Themes can ship a demo. The importer creates it as native, editable blocks, copies every image into your Media Library, and undoes the whole thing with one click.

== Changelog ==
= 0.1.50 =
* Themes screen and Get started: Tunet Starter, the free theme on WordPress.org, is now listed first with a one-click install/activate link — no network request needed.

= 0.1.49 =
* Demo importer: a manifest page can ship as a draft (`'status' => 'draft'`), the same flag projects and posts already accept — for guides that are written but not published yet.

= 0.1.48 =
* Removed the bundled GSAP library (its licence is not GPL-compatible); the cinematic-zoom scroll effect is now a vanilla requestAnimationFrame scrub with the same range and easing. The runtime has no third-party animation dependency.
* Demo importer rollback restores Easy Digital Downloads settings through EDD's own API.
* readme: External services section (theme catalog, Google Fonts) and bundled-library notes for the WordPress.org review.

= 0.1.47 =
* Demo importer: a store manifest can send buyers to a page of its own after logging in (`login_redirect` accepts a page slug, e.g. an account dashboard) instead of EDD's Order History; existing settings are never overwritten.

= 0.1.46 =
* Theme updates: the guard that hides false "update available" notices for premium themes now drops only entries that come from the WordPress.org directory, so a theme's own updater (Update URI on its vendor's host) works as intended.

= 0.1.45 =
* New brand mark in the admin menu: the Tunet t with the C it already contains cut out along a diagonal — white at rest, the C turns cyan on hover and on the active screen (CSS masks, with a static fallback).

= 0.1.44 =
* Demo importer: manifest pages accept `parent` (a slug or a path such as `docs/tunet-core`) to nest pages; the parent must exist or be declared earlier, and a child only needs a slug unique under its parent.


= 0.1.43 =
* New Get started screen behind the Tunet Core menu entry: site status (theme, demo, effects), the three steps to a finished site, what the engine adds, the latest changes and the doors to docs and support. Settings moved to its own submenu. A one-time dismissible notice points there after activation — no redirect.

= 0.1.42 =
* Demo importer: an EDD store manifest can declare its download categories (`edd` → `categories`: name, slug, description). Existing terms are adopted by slug; created ones are tracked, and rollback removes only those that are still empty.

= 0.1.41 =
* Section block: `overflow: clip` instead of `hidden` (same clipping of backgrounds and dividers, but a sticky column inside a section now works).

= 0.1.40 =
* Theme updates: only themes that update from tunetdesign.com (premium) are excluded from the WordPress.org update check; a Tunet theme hosted on WordPress.org keeps its updates.
* Demo: the submenu only appears when the active theme ships a demo manifest.
* New `tunet_core_admin_menu` action for themes that add screens under the Tunet Core menu.

= 0.1.39 =
* Themes screen: catalog entries are read as plain scalars before sanitizing (a malformed catalog cannot break the screen); the placeholder name shown when a theme has no board image is no longer underlined.

= 0.1.38 =
* New *Tunet Core → Themes* screen: the premium themes designed for this engine, listed from the tunetdesign.com catalog (name, price, live preview, board), with an "Installed"/"Active" badge for the ones already on the site and a shortcut to import the active theme's demo. Opt-in by design: it only appears when you open it, fetches the catalog only then (cached 12 hours, neutral user agent, nothing about your site is sent) and shows a plain link if the store cannot be reached. The Demo screen's empty state now links to it.

= 0.1.37 =
* Demo importer: a page entry can set its block template (`'template' => 'page-narrow'`) and declare itself the site's Privacy Policy page (`'privacy' => true`, restored on rollback). The `edd` block accepts `'settings'` (key => value) so a demo can switch on the checkout agreements pointing at its own legal pages; previous values are restored on rollback.

= 0.1.35 =
* Effects runtime: the `text-stagger` word glue introduced in 0.1.30 now applies only to single-token inline markup ("do<mark>e</mark>rs", "<a>word</a>,"). A whole phrase in inline markup followed by punctuation ("<em>in person</em>.") keeps animating word by word as before, instead of appearing at once.

= 0.1.34 =
* Demo importer: an optional `edd` block in the manifest (`'pages' => true`) sets up the Easy Digital Downloads store pages when EDD is active — the pages EDD itself creates are adopted (never duplicated), missing ones are restored through EDD's own installer, and a Login page (`edd/login`) is created and set as EDD's login page so wp-login.php redirects to a branded screen; `'login_redirect' => true` sends customers to Order history after logging in. Rollback removes only the pages the import created and restores the previous settings.

= 0.1.33 =
* Demo importer: a project or post entry can carry `status => draft` to be imported unpublished (archived work the site owner may bring back later); everything else stays published.

= 0.1.32 =
* Demo importer: the slides of a Content Slider are wired to the Media Library like every other demo image (`imageId` + library URL), so a buyer replaces them from the block sidebar and the rollback removes them.

= 0.1.31 =
* Settings: the Google Fonts catalog of the typography picker can be extended from a theme or plugin with the `tunet_core_fonts_text` and `tunet_core_fonts_mono` filters (family => weights), so a theme can list its own defaults there.

= 0.1.30 =
* Demo importer: an optional `brand` block in the manifest sets the site title, tagline and the engine's main/alternative logos from the theme's demo images; rollback restores the previous values.
* Demo importer: projects and posts accept a `date`, so a demo keeps its real chronology instead of stamping everything with the import minute.
* Demo importer: the content of projects and posts goes through the same media wiring as page patterns, so a gallery inside a case study points at the Media Library copies and stays editable.
* Demo importer: the Contact Form 7 form can be defined by the manifest (`form` and `mail_body`), for themes that ship their own intake form.
* Demo importer: the "already exists" guard is scoped to one post type. It used `get_page_by_path()`, which silently also matches attachments, so a demo image named like a project slug made the importer skip the project with no error.
* Effects runtime: `text-stagger` no longer splits a word around inline markup — "do<mark>e</mark>rs" animates as one word instead of three fragments.

= 0.1.29 =
* New declarative content-type framework. `tunet_core_register_content_type( $slug, $args )` and the `tunet_core_content_types` filter let a theme or plugin register a post type with its taxonomies and meta — labels, REST exposure, per-field sanitising, per-object capability checks, admin columns and Block Bindings — without editing the plugin. Declaring the same slug twice merges section by section, so a meta field can be added to an existing type without restating it. Invalid entries are dropped instead of breaking the whole registration, and core types cannot be removed through the filter.
* `project` and `project_type` are now registered through that framework. Same slugs, same rewrites, same REST surface: the only visible change is that the four case-study meta fields now carry a human `label`, so they show a readable name in the Block Bindings UI instead of the raw meta key.
* Rewrite rules are flushed automatically, once, when the set of registered types changes — no more 404 on a brand-new archive.
* Spanish catalogues completed: the breadcrumb block strings shipped in 0.1.28 were untranslated in all seven locales.

= 0.1.28 =
* New block `tunet/breadcrumbs`: a server-rendered trail from the site home to the current page, covering pages (including nested ones), posts, custom post types with their archive, hierarchical taxonomies, date and author archives, search and 404. No SEO plugin needed. It emits `BreadcrumbList` structured data (opt-out), marks the current item with `aria-current`, and keeps the separator in CSS so screen readers do not read it out.
* New filter `tunet_core_breadcrumb_trail` to rewrite the trail before it renders.

= 0.1.27 =
* New filter `tunet_core_icon_set`: themes and plugins can now add icons to the icon set (and override one by slug) without editing the plugin. Core icons cannot be removed through it, so a theme that already uses one keeps working. Invalid entries are dropped instead of breaking the render.

= 0.1.26 =
* Demo importer: a recommended plugin a theme asks for is no longer dropped without a word. The wizard can only offer a plugin it knows how to detect, and until now anything it could not resolve disappeared silently — the theme author saw nothing, and the buyer was never offered a plugin the demo actually uses. It now says so in the error log when `WP_DEBUG` is on, naming the plugin and the line the manifest is missing.

= 0.1.25 =
* **Translations: the text domain is now `tunet-core`, matching the plugin slug.** It was `tunet`, and WordPress.org serves community translations by slug — so the two had to agree for any translation from translate.wordpress.org to ever reach you. The Spanish that ships with the plugin is unaffected. If you maintained your own `.mo` file, rename it from `tunet-{locale}.mo` to `tunet-core-{locale}.mo`.
* The plugin no longer calls `load_plugin_textdomain()`. WordPress has resolved plugin translations on its own since 4.6, and the bundled ones still load — verified, not assumed.
* Housekeeping for the WordPress.org review: coding-standard fixes across the block render files and the admin screens. No change in behaviour.

= 0.1.24 =
* Settings: the alternative logo — the one that exists for dark backgrounds — is now previewed on a dark board instead of the light one. A light logo on a light board looked like an empty box, which read as a failed upload.

= 0.1.23 =
* The admin menu now carries its own mark — a core with the T set in the counterform — instead of a borrowed WordPress icon. It follows your admin colour scheme, so it lights up with the menu item instead of staying grey while the label turns white.

= 0.1.22 =
* Hardening (security review). The demo importer now confines the pattern files it loads to the active theme's `patterns` folder, and the images it copies into the Media Library to the theme's own directory, instead of trusting the path it was given. A file that is not an image is no longer added to the library labelled as one.
* Hardening: gradient overlays in Section and the Slider are validated with a single strict rule before being written to an inline style — the Slider builds its style by hand, so WordPress's own filter was not always underneath. An unusable value now degrades to transparent rather than leaving the overlay undefined, which used to paint an opaque panel over the background photo.
* Project meta now checks permissions against the post being edited rather than a blanket "can edit posts", so the REST check no longer relies on an earlier gate to hold.

= 0.1.21 =
* Counter: the prefix/suffix no longer takes the theme's accent colour by default. It is part of the figure, not decoration, and with a dark accent over a dark band it disappeared. It now follows the number's colour, so it can never be less readable than the figure it belongs to. A theme can still opt into the accent with the new `--tnt-counter-affix-color` variable.

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
