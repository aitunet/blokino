# BloqUIX

Opt-in block effects, custom blocks and a lightweight effects runtime for the WordPress block editor. Nothing is hardcoded: every effect is enabled per block, and every value (color, easing, duration) is read from the active theme's design tokens (`--tnt-*`), with sensible defaults when a theme doesn't define them.

BloqUIX adds opt-in entrance, hover and scroll effects to the blocks you already use, plus the blocks the editor lacks. It is made by [TUNET Design](https://tunetdesign.com), works with any block theme, is free software (GPL-2.0-or-later) and is being submitted to the [WordPress.org plugin directory](https://wordpress.org/plugins/).

## What it provides

- **Effects on native blocks (`tf*` attributes).** A *BloqUIX Effects* panel on Group, Columns, Cover, Image, Heading, Paragraph and Buttons: entrance animations (fade-up, clip-reveal, mask-up, blur-in, scale-in, slide, text-stagger), hover effects (lift, glow, tilt, magnetic, image-zoom, underline-grow), scroll effects (parallax, sticky-pin, reveal, progress), blend modes and animated borders. A block with no effect attribute stays completely clean.
- **Custom blocks where the core falls short.** Section (advanced backgrounds: image, video, gradient, mesh, overlay, shape dividers), Marquee, Counter, Before/After, Slider and Testimonials (Swiper-powered), Brand, Icon, Badge and Breadcrumbs.
- **A lightweight runtime.** Effects are detected per page and their assets are enqueued only when a block on the page uses them. Reveals use the native `IntersectionObserver` and scroll effects run on `requestAnimationFrame` — no third-party animation library (GSAP was removed: its licence is not GPL-compatible). Swiper (MIT) is the only bundled library, loaded solely on pages with a slider. `prefers-reduced-motion` is always respected.
- **A free theme to start with.** [Tunet Starter](https://wordpress.org/themes/tunet-starter/) is built for this engine and free on WordPress.org; the Themes screen lists it first, then the premium themes.
- **Options + demo importer.** Global settings, JSON import/export, content-layout (sidebar) controls, and a per-theme demo importer with a progress bar and one-click rollback. Themes opt in by shipping a manifest; without one there is nothing to import and nothing fails.
- **Extensible from outside.** Themes and plugins can register extra icons and declare content types through documented filters.

## Requirements

- WordPress 6.6+ (tested up to 7.1)
- PHP 7.4+

## Installation

Download the latest release ZIP (or clone this repository into `wp-content/plugins/bloquix`) and activate **BloqUIX** from *Plugins*. No build step is required to run the plugin: the shipped JavaScript and CSS are plain, unbundled files.

## Development

```
npm install
npm run lint:js
npm run lint:css
```

Conventions: PHP prefix `bloquix_` / `Bloquix_`, CSS custom properties `--tnt-`, block namespace `bloquix/`, data attributes `data-tf-`, text domain `bloquix`. Every public capability must be additive and backward compatible; themes already built on the engine must keep working after any change.

Translations live in `languages/` (`bloquix.pot` + compiled `.mo`/`.json` per locale).

## Design principles

1. **Nothing hardcoded.** No effect is applied unless a block attribute activates it.
2. **Engine/design separation.** Functionality lives here; presentation lives in each theme. Switching themes never breaks content.
3. **Degrades with dignity.** The engine ships token defaults so effects and blocks look good under any theme.

## Contributing

Issues and pull requests are welcome at <https://github.com/aitunet/bloquix>. Please keep changes additive, respect the conventions above and run the linters before submitting.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).

Made by [TUNET Design](https://tunetdesign.com) — [profiles.wordpress.org/tunetdesign](https://profiles.wordpress.org/tunetdesign/).
