# Tunet Core — Motor del ecosistema

Plugin/motor compartido por todos los themes Tunet. **No se vende por separado.**
La funcionalidad (efectos, blocks, opciones) vive aquí; la presentación vive en
cada theme. Cambiar de theme nunca debe romper el contenido (CLAUDE.md §0–§2).

## Estado: PIEZA 2 · FASE B COMPLETA — catálogo tf* (vanilla)

Catálogo completo del §4.1, **todo vanilla** (IntersectionObserver + rAF +
`position:sticky` + scroll-driven CSS como mejora progresiva). Sin GSAP: el
scrub-pinned complejo del hero es trabajo de theme, no de estos atributos.

**Entrada (`tfAnimation`)** — `fade-up`, `clip-reveal`, `mask-up`, `blur-in`,
`scale-in`, `slide-left`, `slide-right`, `text-stagger` + `tfStagger`.

**Hover (`tfHover`)** — `lift`, `glow`, `underline-grow`, `image-zoom` (CSS);
`tilt`, `magnetic` (vanilla JS, rAF/lerp).

**Scroll (`tfScroll`)** — `reveal-on-scroll` (IO), `parallax` (+`tfParallaxSpeed`,
rAF o view-timeline), `sticky-pin` (`position:sticky`), `progress` (rAF o
scroll-timeline).

**Composición** — `tfBlend` (mix-blend-mode), `tfBorderFx` (`gradient`,
`conic-rotate`, técnica de anillo con `mask-composite` + `@property`).

Todo desde tokens `--tnt-*`; los efectos son independientes entre sí y opt-in.

### Composición de `transform` (sin sobrescritura)
Apilar efectos en un block es el caso esperado. Ningún efecto escribe
`transform` directamente: cada uno escribe solo sus custom properties
namespaced y un único `transform` compuesto las combina (translate **suma**,
scale **multiplica**, rotate identidad):

- entrada → `--tf-enter-x/y/scale` · parallax → `--tf-parallax-y`
- hover → `--tf-hover-x/y/scale` · tilt → `--tf-rot-x/y` (+`--tf-perspective`)

Las variables se registran con `@property` (tipadas) para ser interpolables:
cada efecto transiciona **su** variable con su curva/duración, sin transicionar
`transform`. `--tf-parallax-y` no se transiciona → sigue al scroll en tiempo
real. `opacity`/`filter`/`clip-path` van por separado. `will-change: transform`
solo en efectos continuos (parallax/hover interactivo). El split contenedor/hijo
queda como técnica complementaria opcional; el caso por defecto compone solo.

El pipeline base (atributos → controles → inyección → runtime → CSS → carga
condicional) viene de la FASE A.

```
tunet-core/
├─ tunet-core.php                  Orquestador: constantes, i18n, activación,
│                                  filtro should_load_separate_core_block_assets,
│                                  arranque de módulos.
├─ extensions/                     Atributos tf* sobre blocks nativos.
│  ├─ class-tunet-extensions.php   Encola el script del editor.
│  └─ effects-editor.js            registerBlockType + panel "Tunet Effects"
│                                  + getSaveContent.extraProps (blocks estáticos).
├─ blocks/                         Blocks propios (block.json + render dinámico).
│  ├─ marquee/                     tunet/marquee: banda infinita (InnerBlocks).
│  ├─ counter/                     tunet/counter: número animado on-scroll.
│  ├─ before-after/                tunet/before-after: comparador arrastrable.
│  ├─ slider/                      tunet/slider: Swiper.js vendorizado + lazy.
│  ├─ section/                     tunet/section: fondos avanzados + dividers.
│  └─ brand/                       tunet/brand: marca del sitio (logo→título, a Home).
├─ runtime/                        Runtime de efectos + carga condicional.
│  ├─ class-tunet-runtime.php      Detecta efectos, encola on-demand, inyecta
│  │                               data-tf-* en blocks dinámicos (render_block).
│  ├─ effects.js                   Scanner IntersectionObserver → .is-tf-in.
│  └─ effects.css                  Estados de fade-up agnósticos (tokens --tnt-*).
├─ admin/                          Ajustes (tabs: General/Branding/Logos) +
│                                  submenú Herramientas (import-export + demo)
│                                  + enlace en Apariencia. Logos de marca con
│                                  espejo a custom_logo; helper tunet_core_logo().
├─ languages/                      i18n (dominio: tunet).
└─ package.json                    Build con @wordpress/scripts (FASE B+).
```

### Allowlist de blocks con efectos tf*
Nativos: `core/group`, `core/columns`, `core/cover`, `core/image`,
`core/heading`, `core/paragraph`, `core/buttons`, `core/button`.
Propios: `tunet/section`, `tunet/marquee`, `tunet/counter`,
`tunet/before-after`, `tunet/slider` (también pueden tener entrada/hover/scroll).

### Contrato de markup que produce un efecto
- `data-tf-animation="<tipo>"` (trigger de la animación de entrada).
- `data-tf-stagger="<ms>"` (marcador de contenedor con hijos escalonados;
  no se emite en `text-stagger`, que escalona palabras).
- `data-tf-hover="<tipo>"` (trigger del efecto hover; independiente de la
  entrada). `tilt` lo alimenta el runtime con `--tf-rot-x/--tf-rot-y` y
  `magnetic` con `--tf-hover-x/--tf-hover-y`; el resto es CSS puro.
- `data-tf-scroll="<tipo>"` (+ `--tf-parallax-speed` en parallax). El runtime
  fija `--tf-parallax-y` / `--tf-progress` salvo soporte scroll-driven nativo.
- `data-tf-blend="<modo>"` y `data-tf-border-fx="<tipo>"` (CSS puro).
- CSS vars inline opcionales: `--tf-delay`, `--tf-duration`,
  `--tf-ease: var(--tnt-ease-<curva>)`, `--tf-stagger`. Si no se definen, el
  CSS cae a los tokens del theme (`--tnt-dur-base`, `--tnt-ease-expo`,
  `--tnt-space-*`).
- El runtime añade `.is-tf-in` al entrar en viewport; en `text-stagger` parte
  el texto en `.tf-word`, y con `tfStagger` marca los hijos como
  `.tf-stagger-item`. Las `--tf-from-*` del contenedor heredan a los hijos, de
  modo que el escalonado reutiliza la misma animación (mejor con fade/slide/
  scale/blur; clip-reveal y mask-up no clipan en hijos escalonados).

## Cómo funciona la carga condicional

1. El orquestador activa `should_load_separate_core_block_assets` → WordPress
   carga el CSS de cada block del core solo si ese block aparece en la página.
2. `Tunet_Core_Runtime::register_assets()` **registra** (no encola) el runtime
   del motor (`tunet-core-runtime` / `tunet-core-effects`).
3. Los blocks propios y las extensiones tf* llamarán a
   `Tunet_Core_Runtime::enqueue()` en su render **solo cuando** un block use un
   efecto. Si no hay efectos en la página, el runtime no se carga.

Regla inquebrantable: **nada hardcodeado**. Ningún efecto se aplica salvo que un
atributo del block lo active, y los valores salen de los tokens `--tnt-*` del
theme, no de números mágicos en el motor.

## Build

```bash
npm install
npm run build      # compila src/ → build/ (cuando existan blocks)
npm run start      # modo watch para desarrollo
```

## Verificación (PIEZA 1)

Ver la sección "Cómo verificar" del resumen de sesión. En resumen:
- Activación sin errores en WP 6.6+ / PHP 7.4+.
- `wp_script_is('tunet-core-runtime','registered')` → `true`.
- `wp_script_is('tunet-core-runtime','enqueued')` → `false` en una página sin
  blocks Tunet (la carga condicional aún no tiene a quién servir).
- `apply_filters('should_load_separate_core_block_assets', false)` → `true`.
