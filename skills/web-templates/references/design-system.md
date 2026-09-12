# Sistema Global de Diseño

La ESTRUCTURA de tokens (roles, pasos de escala, breakpoints) es compartida por TODAS las
plantillas (ecommerce y corporate) y se define **una vez**. Lo que cambia entre proyectos es la
POSICIÓN en los cinco ejes perceptuales — escala, ground, densidad, composición, elevación — que
`ux-design-system` resuelve con el cliente, independiente de qué `TPL-*` se haya elegido. Diseñado
mobile-first. Compatible con Elementor (Global Settings) y Divi (Theme Options + Global Presets),
y con la skill `html-mockup` (variables `--*` en `:root`).

Este archivo es la **autoridad sobre NOMBRES y VALORES de token**, incluidos los valores de cada
posición de eje (`--type-ratio`, `--display-lh`, `--fs-h1-max`, `--sp-scale`, `--c-bg`,
`--c-bg-alt`, `--c-text`, `--elev-rest`, `--elev-hover`): ver "Perceptual axes — token values" al
final. `ux-design-system/references/style-catalog/` es la autoridad sobre QUÉ POSICIÓN toma cada ancla en cada eje y
sobre los nombres concretos de tipografía; no define valores de token.
`ux-design-system/references/motion.md` manda sobre la curva y los rangos de duración/lift.
`ux-design-system/references/design-tokens.md` explica los ROLES (para qué sirve cada token, cómo
derivar la paleta de un logo) y no define ni valores ni nombres de fuente. Ante cualquier
diferencia, manda el archivo correspondiente a lo consultado.

Los pasos de tipografía y de spacing de abajo **no son números elegidos a mano**: derivan de
`--type-ratio` y `--sp-scale`, que fija la posición de eje. Cambiar la posición mueve la escala
entera de una vez — que es exactamente lo que antes no pasaba, cuando `--fs-h1` estaba clavado en
un tope de 56px y todos los clientes recibían la misma tipografía.

## Breakpoints (mobile-first)

| Nombre | Rango | Uso |
|--------|-------|-----|
| mobile | `< 768px` | base — se diseña primero |
| tablet | `768–1024px` | ajustes de columnas |
| desktop | `> 1024px` | layout completo |

Se escribe el CSS base para mobile y se sube con `min-width`. Nunca al revés.

## Tipografía

Dos familias máximo. Escala fluida con `clamp()`, **derivada de `--type-ratio`**: ningún paso de
heading se escribe a mano. El término preferido de cada `clamp()` interpola el SUELO de ese paso
hasta su PROPIO tope entre 430px y 1280px — no es un `vw` suelto, porque un `vw` suelto es igual en
las cuatro posiciones y aplana el eje. La fórmula completa está en "Scale" al final; aquí van los
tokens ya resueltos.

| Rol | Token | Valor | Peso | Uso |
|-----|-------|-------|------|-----|
| Font principal | `--font-primary` | — | — | Headings + UI |
| Font secundaria | `--font-secondary` | — | — | Body (opcional; puede = principal) |
| Base en px | `--fs-base` | `16` **sin unidad** | — | el puente entre `1rem` y los tramos fluidos |
| Progreso fluido | `--fluid` | `clamp(0px, calc((100vw - 430px) / 850), 1px)` | — | 0 en 430, 1px en 1280 |
| Ratio de escala | `--type-ratio` | según posición del eje Scale | — | genera todos los pasos |
| Leading display | `--display-lh` | según posición del eje Scale | — | `line-height` de h1/h2 |
| Tope de h1 | `--fs-h1-max` | según posición del eje Scale, **sin unidad** | — | fija toda la cadena de topes |
| H1 | `--fs-h1` | ver "Scale": suelo `--fs-base × ratio³` → tope `--fs-h1-max` | 700 | 1 solo por página |
| H2 | `--fs-h2` | ver "Scale": suelo `--fs-base × ratio²` → tope `--fs-h1-max ÷ ratio` | 700 | título de sección |
| H3 | `--fs-h3` | ver "Scale": suelo `--fs-base × ratio` → tope `--fs-h1-max ÷ ratio²` | 600 | subtítulos |
| Body | `--fs-body` | `clamp(1rem, 1.2vw, 1.25rem)` | 400 | párrafos |
| Small | `--fs-small` | `0.875rem` | 400 | notas, meta |
| Eyebrow/label | `--fs-eyebrow` | `0.75rem` | 600 uppercase, tracking | etiqueta sobre título |
| Precio | `--fs-price` | `clamp(1.1rem, 1.6vw, 1.35rem)` | 700 | precio actual (ecommerce) |
| Precio anterior | `--fs-price-old` | `0.95rem` | 400 line-through, muted | precio tachado |
| Botón | `--fs-button` | `1rem` | 600 | CTAs |
| Navegación | `--fs-nav` | `0.95rem` | 500 | menú |

`line-height`: h1/h2 usan `var(--display-lh)` (lo fija la posición del eje Scale), h3 `1.25`, body
`1.6`. Un `1.15` fijo para todos los headings era la otra mitad del defecto: el leading del display
es lo que separa un titular contenido de uno monumental, y no puede ser una constante.

## Colores

| Rol | Token | Nota |
|-----|-------|------|
| Primary | `--c-primary` | color de marca / CTA principal |
| Secondary | `--c-secondary` | acciones secundarias |
| Accent | `--c-accent` | detalles, hover, highlights (uno solo, para CTAs) |
| Background | `--c-bg` | fondo base |
| Background secondary | `--c-bg-alt` | secciones alternadas |
| Text | `--c-text` | texto principal |
| Text muted | `--c-text-muted` | texto secundario, meta |
| On accent | `--c-on-accent` | la tinta que va ENCIMA del acento (etiqueta del botón primario). No se elige a mano: es el que más contraste da de `--c-text` / `--c-bg`, medido. Ver "Ground" |
| Border | `--c-border` | bordes, divisores |
| Success | `--c-success` | stock, confirmación |
| Error | `--c-error` | errores, sin stock |
| Sale / promo | `--c-sale` | badges de descuento, precio oferta (ecommerce) |

Cambiar estos tokens = re-brandear todo el sitio.

## Botones

| Variante | Clase | Uso |
|----------|-------|-----|
| Primary | `.btn-primary` | CTA principal (fondo `--c-primary`) |
| Secondary | `.btn-secondary` | acción secundaria |
| Outline | `.btn-outline` | terciaria (borde, fondo transparente) |

Estados: `:hover` (oscurecer 8–10% o accent), `:active` (scale 0.98), `:disabled` (opacity 0.5).
Dos familias de botón solamente (alineado con `ux-design-system`): sólido + ghost/outline;
ambos con hover legible en los dos estados.

La **etiqueta** del botón sólido no es un color que se elija: es `--c-on-accent`, y sale de medir
`--c-text` y `--c-bg` contra el relleno y quedarse con el que más contraste da. Escribir un blanco
ahí es el defecto que este token existe para quitar — sobre el verde por defecto medía 3.05:1.
Regla, medidas por ground y la banda donde ninguno de los dos llega a AA: sección "Ground".

```
--btn-padding: 0.875rem 1.75rem;
--btn-radius: var(--radius-button);
--btn-font: var(--fs-button);
--btn-transition: all 0.2s ease;
--btn-border-width: 1.5px;
```

## Spacing

Escala única, multiplicada entera por `--sp-scale` (la posición del eje de densidad). Prohibido
inventar márgenes sueltos.

| Token | Valor | Uso |
|-------|-------|-----|
| `--sp-xs` | `calc(0.5rem * var(--sp-scale))` | gaps internos |
| `--sp-s` | `calc(1rem * var(--sp-scale))` | entre elementos |
| `--sp-m` | `calc(1.5rem * var(--sp-scale))` | entre bloques |
| `--sp-l` | `calc(3rem * var(--sp-scale))` | padding sección mobile |
| `--sp-xl` | `calc(5rem * var(--sp-scale))` | padding sección desktop |
| `--sp-xxl` | `calc(7.5rem * var(--sp-scale))` | separaciones grandes desktop |

Con `--sp-scale: 1` los pasos valen 8 / 16 / 24 / 48 / 80 / 120px — los mismos rems fijos que este
archivo declaraba antes de que la densidad fuera un eje. La multiplicación va sobre la escala
entera, nunca sobre un token suelto: así el ritmo sobrevive por construcción y lo único que cambia
es la sensación de aire.

Padding vertical de sección: `padding-block: var(--sp-section)`, fluido y sin breakpoint a mano.
Interpola su propio suelo hasta su propio tope entre 430 y 1280, los dos multiplicados por
`--sp-scale`, así que las cuatro densidades se separan a TODOS los anchos. Fórmula en "Density".

## Contenedores

| Token | Valor | Nota |
|-------|-------|------|
| `--container-max` | `1280px` | ancho máximo |
| `--content-width` | `clamp(1140px, 85vw, 100vw)` | ancho de contenido, **proporcional** |
| `--pad-x-desktop` | `5%` | padding horizontal desktop |
| `--pad-x-tablet` | `32px` | padding horizontal tablet |
| `--pad-x-mobile` | `20px` | padding horizontal mobile |

**EL MARGEN ES UNA PROPORCIÓN DE LA PANTALLA; LA BANDA ES LO QUE SOBRA.** Ese orden es toda la
regla, e invertirlo se ha entregado dos veces.

`--content-width` fue primero `1140px` fijo y después
`clamp(1140px, calc(1140px + (100vw - 1280px) * 0.5), 1600px)`. Las dos versiones tienen el mismo
defecto de fondo. Dos de las cuatro composiciones (`LP-ASYMMETRIC`, `LP-BROKEN-GRID`) sangran hasta
`full-end`, que ES el borde del viewport, y su rejilla es `minmax(pad,1fr)` + 12 columnas + otro
`minmax(pad,1fr)`: las pistas tienen que sumar la pantalla, así que si topas las columnas lo único
que puede absorber una pantalla más ancha es el margen — y nada acota un `1fr`. Medido en la
galería, margen exterior total como fracción del viewport:

| banda | 1440 | 2000 | 2560 |
|-------|------|------|------|
| `1140px` fijo | 20.8% | — | 55.5% |
| `clamp(1140, +0.5, 1600)` | 15.3% | 25.0% | 37.5% |
| **`clamp(1140px, 85vw, 100vw)`** | **15%** | **15%** | **15%** |

La segunda fila fue el arreglo anterior: mejoró el NÚMERO a 2560 y dejó intacta la DIRECCIÓN, así
que una pantalla mayor seguía recibiendo un diseño proporcionalmente menor. El lector lo miró a
2000px, donde marca 25%, y lo volvió a decir: *"de 2560px para abajo tiene que ser responsive, con
márgenes reales"*. Tenía razón las dos veces.

`85vw` deja el margen en 7.5% por lado, **constante** por encima de la rodilla (1341px) y también a
3440 y a 5120: una banda proporcional aguanta su ratio para siempre por construcción, y ninguna
banda topada puede. **El 85 está DERIVADO, no elegido**: es la banda constante más grande — el
margen más pequeño — que nunca deja la página más estrecha que la fórmula anterior en el rango que
mide `qa-review/references/house-rules.md` fila 32 por encima de 1280. El ancho que ata es 1440,
donde la banda vieja era 1220 de 1440 = 84.72%. El suelo `1140px` mantiene intacto todo lo medido
hasta 1280.

**Lo que cuesta, medido.** Una banda sin tope deja crecer una columna de texto con la pantalla, y
por eso existía el tope. Recorriendo cada nodo de texto de la galería a 2560 y dividiendo cada
línea renderizada por el avance de su propio `0`, exactamente UNA corrida se pasa de medida:
`.lede`, 68.4ch a 1440 → 103.1ch a 2560. Todo lo demás está acotado por su contenedor y marca
78.2ch a 1440 y 78.2ch a 2560, plano. El trato honesto es topar la COLUMNA DE TEXTO y dejar crecer
la banda y las imágenes: `.lede{max-width:66ch}`, una declaración. Topar la banda para proteger un
párrafo era pagar ese párrafo con la página entera.

## Border radius

| Elemento | Token | Default |
|----------|-------|---------|
| Cards | `--radius-card` | `12px` |
| Buttons | `--radius-button` | `8px` |
| Images | `--radius-image` | `8px` |
| Inputs | `--radius-input` | `8px` |
| Containers | `--radius-container` | `16px` |

Estos son los defaults. El ancla resuelta puede moverlos cuando su **Card recipe** en
`ux-design-system/references/style-catalog/` lo pide (p. ej. `STY-MATTER` pone la imagen al radio del contenedor);
donde la receta calla, manda la tabla. Los NOMBRES de token no cambian nunca.

## Notas de implementación

**html-mockup:** declara todos los `--*` en `:root` una sola vez; cada sección los referencia.
**Elementor:** tokens → Site Settings > Global Colors + Global Fonts; el resto → Global Custom
CSS con las `--*` en `:root`. **Divi:** colores/fuentes → Theme Customizer + Global Presets;
`--*` → Theme Options > Custom CSS.

Definir las variables `--*` en `:root` una sola vez = cambiar branding sin tocar módulos. Es la
clave de la reutilización, y hace que maqueta HTML y build nativo compartan el mismo origen.

## Perceptual axes — token values

### Scale (`--type-ratio`, `--display-lh`, `--fs-h1-max`)
| Position | `--type-ratio` | `--display-lh` | `--fs-h1-max` |
|---|---|---|---|
| `contained` | 1.200 | 1.25 | 48 |
| `classic` | 1.333 | 1.10 | 64 |
| `editorial` | 1.500 | 0.95 | 88 |
| `monumental` | 1.618 | 0.82 | 120 |

`--fs-h1-max` is a **unitless px count**, not a length, and that is load-bearing rather than
cosmetic. The preferred term below has to multiply a token difference against `100vw`, and
`calc()` cannot divide a length by a length — so the coefficient must arrive without a unit.
`--fs-base: 16`, the px count `1rem` resolves to, is the single bridge back to a length.

Every heading step derives from the ratio by exponentiation, never by hand. `n` is the step
(h3 = 1, h2 = 2, h1 = 3): the floor is `--fs-base × ratio^n`, the cap is `--fs-h1-max ÷ ratio^(3−n)`,
and the preferred value interpolates that step's OWN floor into that step's OWN cap across
430 → 1280. One number per position still pins all three. Written out in full:

```css
--fs-base: 16;                                            /* unitless: the px count of 1rem */
--fluid: clamp(0px, calc((100vw - 430px) / 850), 1px);    /* 0 at 430px, 1px at 1280px */

--n-h1: calc(var(--fs-base) * var(--type-ratio) * var(--type-ratio) * var(--type-ratio));
--n-h2: calc(var(--fs-base) * var(--type-ratio) * var(--type-ratio));
--n-h3: calc(var(--fs-base) * var(--type-ratio));
--n-h1-cap: var(--fs-h1-max);
--n-h2-cap: calc(var(--fs-h1-max) / var(--type-ratio));
--n-h3-cap: calc(var(--fs-h1-max) / var(--type-ratio) / var(--type-ratio));

--fs-h1: clamp(calc(var(--n-h1) / var(--fs-base) * 1rem),
               calc(var(--n-h1) / var(--fs-base) * 1rem + (var(--n-h1-cap) - var(--n-h1)) * var(--fluid)),
               calc(var(--n-h1-cap) * 1px));
--fs-h2: clamp(calc(var(--n-h2) / var(--fs-base) * 1rem),
               calc(var(--n-h2) / var(--fs-base) * 1rem + (var(--n-h2-cap) - var(--n-h2)) * var(--fluid)),
               calc(var(--n-h2-cap) * 1px));
--fs-h3: clamp(calc(var(--n-h3) / var(--fs-base) * 1rem),
               calc(var(--n-h3) / var(--fs-base) * 1rem + (var(--n-h3-cap) - var(--n-h3)) * var(--fluid)),
               calc(var(--n-h3-cap) * 1px));
```

`--n-h? ÷ --fs-base × 1rem` is exactly `ratio^n × 1rem`, so the floors stay rem-relative and a
reader's default font size still moves the small end. The fluid segment and the cap are px, as
every viewport-driven step necessarily is.

h1 and h2 take `line-height: var(--display-lh)`; h3 stays `1.25` and body `1.6`. `--fs-body` keeps
a plain `1.2vw` preferred term on purpose: body is NOT an axis — it reads the same at every
position — so there is no axis for a viewport-only term to flatten there.

MEASURED in a browser at a 16px root, not reasoned about:

| Position | 430 | 768 | 1280 | 1920 |
|---|---|---|---|---|
| `editorial` h1 | 54.00px | 67.52px | **88.00px** | 88.00px |
| `monumental` h1 | 67.77px | 88.54px | **120.00px** | 120.00px |

The cap engages at 1280 — a laptop — which is the entire point. The version this replaces used
`calc(3.3vw + 1rem)` as its preferred term: no `--type-ratio` in it and no `--fs-h1-max` in it, so
it was byte-identical at all four positions. Its 88px cap engaged only above ~2181px and its 120px
cap above ~3151px; on a 1280px laptop the two positions actually rendered **58.24px and 67.77px**,
a 16% gap where this table promised 88 against 120. The paragraph that used to sit here claimed
"a 68px floor and a 120px cap" as if both were reachable — the floor was, the cap was not. Before
that it was a hardcoded `clamp(2rem, 5vw, 3.5rem)`: a 56px cap on every client site.

### Density (`--sp-scale`)
| Position | `--sp-scale` |
|---|---|
| `compact` | 0.8 |
| `standard` | 1.0 |
| `generous` | 1.35 |
| `monumental` | 1.7 |

One multiplier over the whole `--sp-*` scale, so rhythm consistency survives by construction.
Section padding interpolates its own floor into its own cap over the same 430 → 1280 range, with
`--sp-scale` on BOTH ends — which is what makes the axis visible at every width:

```css
--n-sec:     calc(2 * var(--fs-base) * var(--sp-scale));   /* unitless: 2rem worth, scaled */
--n-sec-cap: calc(7 * var(--fs-base) * var(--sp-scale));   /* unitless: 7rem worth, scaled */
--sp-section: clamp(calc(var(--n-sec) / var(--fs-base) * 1rem),
                    calc(var(--n-sec) / var(--fs-base) * 1rem + (var(--n-sec-cap) - var(--n-sec)) * var(--fluid)),
                    calc(var(--n-sec-cap) * 1px));
```

`section { padding-block: var(--sp-section) }`. MEASURED at a 16px root:

| Position | 430 | 768 | 1280 | 1920 |
|---|---|---|---|---|
| `generous` (1.35) | 43.20px | 86.14px | 151.20px | 151.20px |
| `compact` (0.8) | 25.60px | 51.05px | 89.60px | 89.60px |

Every width separates, by the constant `1.35 ÷ 0.8`. The rule this replaces was
`clamp(calc(2rem * var(--sp-scale)), 6vw, calc(7rem * var(--sp-scale)))`, whose middle term had no
`--sp-scale` in it at all: between 720px and 1493px `generous` and `compact` produced the SAME
padding — measured at **46.08px at 768 and 76.80px at 1280 on both**. A density axis that is
invisible at the two widths clients actually review on is not a density axis.

### Ground (`--c-bg`, `--c-bg-alt`, `--c-text`)
Every cell is a literal. `--c-text` is derived exactly as `design-tokens.md` step 3 specifies — the
darkest brand neutral pushed toward near-black, or toward near-white on a dark ground — and each
pair was contrast-checked against its OWN `--c-bg`, not against white. "very light blue-grey" and
"near-black" were the two positions that shipped as adjectives; a builder cannot paint an adjective.

| Position | `--c-bg` | `--c-bg-alt` | `--c-text` | Measured contrast |
|---|---|---|---|---|
| `paper` | `#FFFFFF` | `#F6F7F8` | `#15181A` | 17.8:1 |
| `warm` | `#FFF3E3` | `#F7E8D4` | `#241C14` | 15.3:1 |
| `cool` | `#F2F5F8` | `#E8EDF3` | `#141C24` | 15.7:1 |
| `cream` | `#FBF4DD` | `#F3E7C4` | `#221D0E` | 15.3:1 |
| `earth` | `#F3E4C8` | `#EEDBB8` | `#2B1608` | 13.7:1 |
| `saturated` | `#F6D8DC` | `#F3D0D5` | `#2B1015` | 13.3:1 |
| `ink` | `#0E1113` | `#171B1E` | `#F4F6F7` | 17.5:1 |
| `ink-warm` | `#171008` | `#221808` | `#F7EFE2` | 16.5:1 |
| `ink-cool` | `#0B0F1C` | `#141B2E` | `#EAF0FF` | 16.7:1 |

**Nine positions, not four (style-catalog PR 3a).** The hue anchor moves; the floors do not — every
row still clears the same 7:1 AAA this axis has always required (`_build-gallery.php` now gates it
for these nine directly, not only for the brands appended below). `paper`/`warm`/`cool`/`ink` are
unchanged. `cream` and `earth` are warm without being `warm`'s own peach — a pale butter and a
deeper sandy ochre. **Neither is a medium-value ground, and that is geometry, not timidity**: the
7:1 AAA floor forces `--c-bg` toward one extreme or the other — a mid-luminance ground clears
neither a dark nor a light text at 7:1 (measured: at `--c-bg` luminance 0.20, black text tops out
at 4.75:1 and white text at 5.03:1, both under the bar) — so every position in this table, old or
new, sits near-white or near-black by construction. `earth`'s own headroom is tighter than the
other light rows for the same reason: its `--c-text-muted` (blended toward `--c-bg-alt`, the
row two tables down) only clears 4.5:1 at 13.7:1 because `--c-bg-alt` was pulled closer to
`--c-bg` than the other rows' — a visibly subtler band, not a design choice. `saturated` is a
genuinely chromatic rose rather than a tinted neutral. `ink-warm` and `ink-cool` split what `ink`
alone used to carry: an amber-black and a navy-black, so a dark project stops defaulting to one
hue the way three of the four light positions used to default to near-white.

`ink` inverts the derivation, and that is the one that bites: the accent has to be re-derived to
clear 4.5:1 against `#0E1113`, because an accent that passed on `paper` will usually fail here.

#### The other six ground-dependent colours are DERIVED, not tabled

The three columns above are the axis INPUT. A build has more colours than that which depend on the
ground, and for a while they did not move with it: `es-builder.php` shipped `muted`, `text_soft`,
`border`, `surface_inverse` and `on_inverse` as constants sampled off a white page. Measured on the
`ink` row of the table above, against its own `--c-bg` — which is the rule this file states two
paragraphs up and enforced for `--c-text` alone: `muted` **3.70:1**, `text_soft` **2.27:1**,
`surface_inverse` **1.06:1** (the dark button was invisible on its own page) and `border`
**15.24:1**, a near-white hairline slashed across a near-black page.

They are now blended out of `--c-text` and `--c-bg` rather than tabled per position, and the reason
is coverage: this table has nine rows and a client's ground is whatever their brand is. A derived
neutral is right on grounds nobody has thought of yet; a tabled one is right on nine. It also
follows `design-tokens.md` step 4, which already said to derive neutrals off the contrast colour.

| Derived token | Recipe | paper | warm | cool | cream | earth | saturated | ink | ink-warm | ink-cool |
|---|---|---|---|---|---|---|---|---|---|---|
| `--c-text-soft` | text → bg, 23% | 8.49:1 | 7.55:1 | 7.71:1 | 7.53:1 | 7.20:1 | 7.15:1 | 10.50:1 | 10.00:1 | 10.07:1 |
| `--c-text-muted` | text → **bg-alt**, 36.6% | 5.08:1 | 4.60:1 | 4.78:1 | 4.58:1 | 4.53:1 | 4.59:1 | 7.08:1 | 6.76:1 | 6.72:1 |
| `--c-border` | text → bg, 89% | 1.25:1 | 1.25:1 | 1.25:1 | 1.25:1 | 1.24:1 | 1.24:1 | 1.31:1 | 1.31:1 | 1.30:1 |
| `--c-surface-inverse` | text (0%) | 17.84:1 | 15.33:1 | 15.71:1 | 15.27:1 | 13.71:1 | 13.28:1 | 17.48:1 | 16.52:1 | 16.74:1 |
| `--c-on-inverse` | bg (0%) | 17.84:1 | 15.33:1 | 15.71:1 | 15.27:1 | 13.71:1 | 13.28:1 | 17.48:1 | 16.52:1 | 16.74:1 |

Every cell is a **measured contrast against that position's own `--c-bg`** — except the last row,
which is measured against `--c-surface-inverse`, since that is what that ink sits on. The two 0%
rows are the point rather than a rounding curiosity: the surface that flips the page over IS the
contrast colour, and the ink on it IS the page's ground, so on `warm` an inverted panel is dark
brown carrying cream, not dark brown carrying a white that appears nowhere else in the palette.

**These cells are evidence, not values, and that is why the position names in this table's header
are not backticked.** `RT_AXIS_VALUE_MISSING` reads any table row containing a backticked position
name and requires a token-shaped value beside it; a contrast ratio is not one, so backticking them
here would fail the audit on correct documentation. The verifier for a derived token is not a column
in this file — it is `tests/test-write-path.php`, which recomputes every ratio above on every run
against all nine positions and requires body copy ≥ 4.5:1 and the inverse surface ≥ 3:1. That is
strictly stronger than a documented literal, which is only ever as true as the day it was typed.

**`--c-text-muted` se mezcla hacia `--c-bg-alt`, y su celda se mide contra `--c-bg-alt`.**
Es la única fila de esta tabla que no toma `--c-bg` como referencia, y la razón es que es la
única que llegaba a rozar el límite. La banda alterna está SIEMPRE más cerca del texto que el
fondo base — más oscura en un ground claro, más clara en uno oscuro — así que es la superficie
dura: un muted que pasa AA sobre ella pasa también sobre `--c-bg`. Medido al revés, contra
`--c-bg`, **cuatro de los once grounds del catálogo caían por debajo de 4.5:1 en sus propias
secciones `.bg-alt`** — warm 4.35:1, `b-alinea` 4.41:1, `b-aranda` 4.47:1, `b-bergara` 4.49:1 —
con la tabla en verde, porque la celda medía contra una superficie que esas secciones no pintan.
Ninguna medida baja con el cambio: sobre `--c-bg` los mismos cuatro pasan de 4.78/5.02/5.00/5.08
a 5.06/5.48/5.39/5.46.

`--c-border` is asserted as a **range** (1.05–2.5:1) rather than against WCAG 1.4.11's 3:1. It is a
divider, and it has never reached 3:1 on any ground including the white one it was drawn for. What
went wrong on `ink` was not that it was too faint but that it stopped being a hairline at all.
**Open, not closed:** the outline button's edge reads this same token, and *that* is a control at
1.25:1, so it is a real 1.4.11 gap. Fixing it means a separate control-edge token with its own
measured cell, and it is recorded here rather than hidden behind the range.

#### `--c-on-accent` — the label on the primary button is CHOSEN, not tabled

The ink that sits on the accent is whichever of the two ground extremes — `--c-text` or `--c-bg` —
measures higher against it. It is the one derived colour that cannot be tabled per ground position,
because the surface it sits on is the **brand's** accent, and the accent is not an axis.

It was a literal `#FFFFFF`, and on this framework's own accent that is **3.05:1** — below AA, on the
label of every primary button the framework emits. Pinning the other extreme instead fixes exactly
one brand and breaks the next: a navy accent needs the white label back.

| Ground | Accent | `--c-on-accent` | Measured on the accent | White would have been |
|---|---|---|---|---|
| `paper` | `#0FA968` | `#15181A` | 5.86:1 | 3.05:1 — fails AA |
| `warm` | `#0FA968` | `#241C14` | 5.51:1 | 2.78:1 — fails AA |
| `cool` | `#0FA968` | `#141C24` | 5.65:1 | 2.78:1 — fails AA |
| `ink` | `#0FA968` | `#0E1113` | 6.22:1 | 2.81:1 — fails AA |

`ink` is the row that proves the rule is a measurement: there the winner is the near-black **`--c-bg`**,
not the near-white `--c-text`, and the same code produced both. Two more, to show the span: a pale
`#F4D03F` resolves to `#15181A` at 11.84:1, a dark `#1B2A4A` resolves to `#FFFFFF` at 14.22:1.

**There is a band of accents where NEITHER extreme reaches AA, and it is geometry rather than bad
luck.** At the accent where the two candidates cross, both measure exactly `sqrt(the ground's own
contrast)`: on `paper` (17.84:1) that is **4.22:1**, so no accent in that band can clear 4.5 against
either ink — only a pure-black-on-pure-white ground (21:1 → 4.58) would close it. Ordinary brand
colours live there: `#008899` measures 4.23 / 4.22, `#1177EE` measures 4.16 / 4.29. The build paints
the better of the two and **warns naming both measurements**; the way out is to move the accent or
to set `on_accent` by hand, and both are decisions somebody has to make rather than defaults.

**The hover moves AWAY from the page, and the ground picks which way.** `accent_hover` is the accent
shifted 18.5% and `border_hover` the hairline shifted 6.5% — *away from* `--c-bg`, not
unconditionally darker. Darkening raises contrast on a light page and lowers it on a dark one, so
while the shift was absolute the hover **receded** on all three dark positions and read as
*disabled*. Measured against each ground's own `--c-bg`, accent `#FF3D8A`:

| Position | `--c-bg` | rest | `accent_hover` before | `accent_hover` now |
|---|---|---|---|---|
| `paper` | `#FFFFFF` | 3.34:1 | `#D03270` 4.80:1 | `#D03270` 4.80:1 — unchanged |
| `ink` | `#0E1113` | 5.67:1 | `#D03270` 3.95:1 — receded | `#FF61A0` 6.74:1 |
| `ink-warm` | `#171008` | 5.64:1 | `#D03270` 3.92:1 — receded | `#FF61A0` 6.70:1 |
| `ink-cool` | `#0B0F1C` | 5.72:1 | `#D03270` 3.98:1 — receded | `#FF61A0` 6.79:1 |

**This was an affordance defect, not an accessibility one** — 3.95:1 still clears AA-large's 3:1, so
no `qa-review` row caught it and it survived until somebody measured the *sign*. The direction is
measured per build rather than read off a luminance threshold, so a brand ground nobody has
documented gets it right too; the magnitude is still the fitted factor, because `0.815` is what
reproduces the house `#0C8A55` exactly on `paper`.

**Still open on the light grounds:** the *label* on the hovered fill. With the derived `on_accent`
the hover measures **4.06:1 on `paper`, 3.82 warm, 3.92 cool** — below AA, unchanged, and now for a
stated reason: on a light ground the label is dark *and* the hover correctly darkens, so the
affordance and the label pull opposite ways. It was below AA before this token was derived at all
(white on `#0C8A55` is 4.39:1), so it is a pre-existing gap, and it is narrower than it was — the
dark grounds fixed themselves here (`ink` 4.32 → 7.63) because a lighter fill is what a near-black
label wanted. Closing the rest needs a second on-colour for the hover state, or an accent with more
room.

#### The accent is spent by ROLE, and the whitelist is a list of roles

`ux-design-system/references/design-tokens.md` has the whole rule in one line and it names roles:
*"ONE color. CTAs, action icons, important links, active states. Never body text, never
decoration."* This section is that sentence made enforceable. It said nothing at all for a long
time, and the silence had a measured cost: counting elements whose computed `color`, `background`
or visible border IS the accent, at rest, on the template gallery before any rule — **12 marks on
a corporate strip and 16 on an ecommerce one**, an eyebrow on every section, four ticks in a
benefits bar, a disclosure triangle per FAQ row, and the buttons. A hue that appears a dozen times
is not an accent. It is a second text colour, and a page with two text colours reads as a theme.

**The rule.** On the resting page the accent may be painted only by something filling one of
design-tokens.md's four roles, plus anything an anchor's own recipe names. Everything else that
wanted colour wants `--c-text-muted`: a label's job is to be read before the heading and then get
out of the way, which is what a muted tone does and what a saturated hue cannot.

**A ROLE, never a count — and the difference is not pedantry, it cost the gallery a round.** The
first attempt at this rule read `craft-probe-2026-08-16.html`'s "one spend on the resting page" as
a *number* and spent the budget down to it. Three things went wrong at once, and only a role list
separates them:

- **The ticks were right to lose the accent, and for the wrong stated reason.** A tick beside
  "Envío en 72 h" is a confirmation mark. That is *decoration*, which the sentence above forbids
  outright — not "a fourth mark over budget". The distinction matters because the first reading
  invites putting it back when the page feels quiet, and the second never does.
- **The page then felt quiet, and the cause was somewhere else entirely.** Every photograph had
  been turned to greyscale in the same round (see "One treatment for the photographs" below). A
  colour complaint answered by repainting the nearest small object is a symptom treated at the
  wrong site.
- **A count gets a third case wrong that a role list gets right for free.** `TPL-E-02` renders
  eight product tiles, each with an add-to-cart button. Under a budget of one they stayed neutral
  outlines on an archetype called *Product-First*; under the role list they are **one role eight
  times**, which is what a catalogue with eight products has. A rule that grows quieter as the shop
  grows bigger is a rule that punishes the shop for selling.

**Key the whitelist by role.** A mark cannot be added without claiming one of the four roles out
loud, and a mark that claims none has nowhere to be written. This is the same shape as
`RT_MOCKUP_BLEED_NOT_MEDIA`, and for the same reason: a number is satisfied by moving a spend
somewhere the counter cannot see.

**Name the empty roles rather than dropping them.** A role with no members is a claim — that this
page has no important links outside its buttons, that no chip is marked current — and a claim the
next reader can check. Deleting the key makes it unfalsifiable.

Three exclusions are part of the rule rather than holes in it:

- **Interaction states are exempt** — `:hover`, `:focus`, `:active`, `:checked`. A colour that
  appears when you touch something is feedback, and feedback is supposed to be findable.
- **`::marker` and other resting pseudo-elements are NOT exempt.** A disclosure triangle is on
  screen at rest, so an accent-coloured one is a spend. It sat in the exempt list for one revision
  of the gallery's own gate and mutation is what found it.
- **`box-shadow` is not a mark.** `--elev-rest: 0 0 0 1px …accent…` is the elevation axis spending
  its own token, which this file tables as `accent-glow`.

**An anchor's card recipe outranks this whitelist.** `references/style-catalog/` gives
`STY-INSTITUTIONAL` "chip de icono en accent" in as many words; a rule written here does not get
to overrule a style's own definition. Name the exception under a role and move on.

**Where the biggest spend goes: the close.** A whitelist is only interesting because it lets you
spend loudly somewhere. The closing band is that somewhere — see
`ux-design-system/references/layout-patterns.md` § "The close is a designed moment".

##### Two ways a gate for this rule reports marks that are really there as no marks at all

Both were found in the gallery by asserting that **every whitelisted class must match something**,
per class, and both had been sitting in a green build. A permission that never fires and a spend
that never registers are the same bug seen from its two ends:

- **Nobody writes `border-top-color`.** A gate matching `border-*-color` misses
  `border-top: 2px solid var(--c-accent)` and `border: 1px solid color-mix(…var(--c-accent)…)`,
  which is how a 2px rule on every strip header AND the hairline on the closing band both went
  uncounted for a round. Match the shorthands, and `fill`/`stroke` too — an accent-filled SVG icon
  is an accent mark whatever property carries it.
- **The accent is also spelled as a literal.** A band that resolves its tokens writes
  `background:#FF6A1A`, not `background:var(--c-accent)`. Derive the literal hexes from the accent
  table and look for both.

The two arms cover different holes and neither substitutes for the other — verified by disabling
each alone and re-running. Say which mutation proves which arm, or the second one becomes a story
told about the first.

#### One treatment for the photographs, and it BIASES rather than REPLACES

A page whose photographs come from different shoots wants one treatment over all of them; that
much is ordinary. What is not ordinary, and what this framework got wrong for three commits, is
**which** treatment and **for how long**.

**Do not derive a duotone from a ground.** A duotone maps luminance onto two inks, so the two inks
have to be genuinely two colours or it is a greyscale with a tint. Deriving them from `--c-bg` and
`--c-text` cannot produce that: a ground's two extremes are chosen for *contrast*, which is the
property that makes them neutral. Measured on the gallery's four grounds, as the R−B spread of the
dark ink: **5, 5, 16, 17** — three of the four were grey, and the client's report was four words
long: *"ahora no se ven colores"*. If a duotone is genuinely wanted, take its inks from something
that HAS chroma — the accent, or a stated warm/cool bias — and say what they were taken from.

**Prefer a grade.** Two primitives do the job and both are exactly expressible in SVG, so the same
arithmetic can run in the generator and be asserted:

- a **chroma restraint** (`feColorMatrix type="saturate"`), which pulls the loud objects toward the
  set's own body without pulling the subject to grey;
- a **split tone** (`feComponentTransfer`, a per-channel `tableValues` curve applied to each
  channel's own value), so the blacks land on a shadow ink, the whites on a highlight ink, and the
  midtones pass through.

**Tint the shadow, never lift it.** Mixing a bright accent into a near-black endpoint raises its
luminance, and a raised black is a faded print. Put the tinted ink back on the luminance of the
neutral endpoint it replaced and assert it — the gallery's first cut drove its paper shadow from
`#232628` to `#432C25` and washed out every dark frame on the page.

**Never take colour off the merchandise.** A photograph that ILLUSTRATES can take a treatment; a
photograph that IS the product cannot lose the thing being sold. The gallery learned this twice:
first as a carve-out for two stone swatches, then again when the carve-out put two full-colour
tiles among six greyscale ones and the catalogue read as a rendering error. A product grid is
merchandise all the way across. A treatment that keeps colour needs no carve-out at all.

**Check the property, not the mechanism.** The swatch carve-out existed because two stones measured
a chroma of 2 apart under the duotone. When the treatment changed, the right move was not to keep
the carve-out but to assert the thing it was protecting: *the two swatches must stay distinguishable*
— measured at 22–26 apart under the grade, against 0.1–0.6 under the duotone. A property check
cannot be satisfied by moving the mechanism somewhere else.

**And put an expiry on it.** The gallery's duotone was justified by a real defect — six photographs
in three incompatible registers. The asset set was then rebuilt to eleven distinct shoots and the
justification expired, but the treatment stayed for three more commits because nothing tied the two
together. **Whenever a treatment exists to repair an asset set, write down which measurement
justifies it**, so the next person can re-take it and find out it no longer does.

### Elevation (`--elev-rest`, `--elev-hover`)
| Position | `--elev-rest` | `--elev-hover` |
|---|---|---|
| `none` | `none` | `none` — separation is whitespace |
| `hairline` | `0 0 0 1px var(--c-border)` | `0 0 0 1px var(--c-text)` |
| `soft-shadow` | `0 1px 2px color-mix(in srgb,var(--c-text) 4%,transparent)` | `0 18px 40px -12px color-mix(in srgb,var(--c-text) 16%,transparent)` |
| `accent-glow` | `0 0 0 1px color-mix(in srgb,var(--c-accent) 22%,transparent)` | `0 14px 34px -10px color-mix(in srgb,var(--c-accent) 40%,transparent)` |

**`soft-shadow` tints off `--c-text`, style-catalog PR 3b.** The row above used to read
`rgba(0,0,0,.04)` / `rgba(21,24,26,.16)` — a fixed cool near-black, which happens to be `paper`'s
own `--c-text` and reads wrong on any other ground: nine ground families later (PR 3a) that literal
sits either too cold (`warm`/`earth`/`cream`, whose text is warm-black) or backwards (any `ink-*`
ground, whose text is near-white). Same percentages the literal used, same `color-mix` syntax
`accent-glow` already established one row down — **"tint the shadow, never lift it"** applied to
elevation, not only to the photographic ink. On a light ground this still reads as a shadow
(`--c-text` is dark); on a dark ground it reads as a glow (`--c-text` is light) rather than as no
shadow at all, which is why `PERS-VITRINE` (ground `ink`, position `soft-shadow`) overrides its own
rest state to `none` and keeps the derived value for hover only — see its § Imagery.

### Composition (one blueprint per position)
The only axis whose value is a layout rule rather than a number, so each position names a blueprint
instead. The blueprints are defined in `ux-design-system/references/layout-patterns.md`, where each
one fixes column count, where the content sits, and what an image may do — enough specificity that
two anchors over identical content render as visibly different pages. Four prose sentences, which
is what this table held before, were not: nothing downstream could act on them.

| Position | Blueprint | In one line |
|---|---|---|
| `centered` | `LP-CENTERED` | one symmetric axis, nothing bleeds |
| `asymmetric` | `LP-ASYMMETRIC` | copy on 7 of 12 columns, one image bleeding a viewport edge |
| `strict-grid` | `LP-STRICT-GRID` | every element starts and ends on a column line |
| `broken-grid` | `LP-BROKEN-GRID` | one element per section crosses the grid or overlaps a neighbour |

### Chassis (one blueprint per position)
A different axis from composition (composition fixes the grid, chassis fixes how each content
BLOCK is bounded inside it), but the same shape: no property carries "carded" or "bordered", so
each position names a blueprint. The blueprints are defined in
`ux-design-system/references/layout-patterns.md` § "Chassis blueprints", beside the composition
ones. `CHS-STRICT-GRID` is a distinct id from `LP-STRICT-GRID` even though both read "strict
grid" in English — one fixes column alignment, the other fixes a block's own boundary — so the two
never resolve against each other by accident.

| Position | Blueprint | In one line |
|---|---|---|
| `bare` | `CHS-BARE` | no border, no fill, no shadow — spacing alone separates blocks |
| `carded` | `CHS-CARDED` | a filled `--c-bg-alt` rectangle, no shadow, at rest or on hover |
| `soft-carded` | `CHS-SOFT-CARDED` | filled at rest, `soft-shadow` reserved for hover/focus |
| `bordered` | `CHS-BORDERED` | a hairline frame is the whole chrome, no fill step |
| `rule-divided` | `CHS-RULE-DIVIDED` | no block boundary; a hairline rule separates content |
| `hard-shadow` | `CHS-HARD-SHADOW` | a zero-blur offset shadow, present at rest, not just on hover |
| `strict-grid` | `CHS-STRICT-GRID` | bare surface, but every edge lands on a fixed grid line |
| `layered` | `CHS-LAYERED` | blocks overlap by a fixed offset, `z-index` sets the stack |

### Accent (how loudly the ONE accent colour is deployed)
`none` is a literal — the accent is spent nowhere beyond the whitelist's four roles, and a page can
say so without a blueprint. Every other position changes HOW the accent itself is rendered, not
where, so each one names a blueprint the same way composition does: a policy nobody wrote down is
the "a position with no value is an adjective" failure in new clothes.

| Position | Blueprint | In one line |
|---|---|---|
| `none` | `none` | the whitelist's four roles, nothing more |
| `reserved` | `ACC-RESERVED` | the accent whitelist itself, spent nowhere it is not already named |
| `tinted-field` | `ACC-TINTED-FIELD` | a bounded surface washed in a low-opacity mix of the accent |
| `duotone` | `ACC-DUOTONE` | the accent is one of the two inks a photograph's duotone grade takes its chroma from |
| `gradient` | `ACC-GRADIENT` | the accent runs as a two-stop fill on one bounded surface, never on text |
| `metallic` | `ACC-METALLIC` | the accent carries a banded light/dark gradient simulating a brushed metal sheen |
| `polychrome` | `ACC-POLYCHROME` | the ONE-colour rule is lifted, under a named exception, for a stated bounded set of marks |

#### `ACC-RESERVED`
- Is today's global accent whitelist (§ "The accent is spent by ROLE") verbatim, restated as a
  blueprint so the axis has something to point at: CTAs, action icons, important links, active
  states, plus anything an anchor's own card recipe names — nothing else.
- No literal beyond the accent hex itself is introduced; this position spends what the whitelist
  already allows and not one mark more.

#### `ACC-TINTED-FIELD`
- Exactly one bounded surface per page may carry `color-mix(in srgb, var(--c-accent) 8-14%,
  var(--c-bg))` as its `background`. Body text inside it stays `--c-text`; the tint is a wash, not
  a theme change.
- The tinted surface still counts against the whitelist's roles: it earns the tint by hosting a
  CTA, a form, or the close (`layout-patterns.md` § "The close is a designed moment"), never as
  unclaimed decoration.

#### `ACC-DUOTONE`
- The accent supplies ONE of the duotone's two inks (see § "One treatment for the photographs" —
  "take its inks from something that HAS chroma — the accent"); the other ink is the ground's own
  dark neutral, never a second invented hue.
- Applies to the photograph treatment only. Chrome, type and controls are unaffected — a duotone
  photograph is not licence to duotone the page around it.

#### `ACC-GRADIENT`
- A `linear-gradient` between the accent and one `color-mix` step of it (never a second hue) fills
  exactly one bounded surface per section — a button, a badge, or the close's own field.
- Never on text and never full-bleed: a gradient across the whole viewport reads as a background,
  not as the accent spending its one spend.

#### `ACC-METALLIC`
- A `linear-gradient` with at least three stops alternating lighter and darker `color-mix` steps of
  the accent, simulating a brushed sheen, on the same bounded surfaces `ACC-GRADIENT` allows.
- Reserved for surfaces the light is meant to catch — a CTA, a price tag, a badge — never a full
  section background, which would read as foil wrap rather than a material.

#### `ACC-POLYCHROME`
- The ONE-colour rule is suspended for exactly one named, bounded set — a tag list, a category
  swatch grid — and the exception is written down beside the set it covers, the same discipline
  `references/style-catalog/` already asks of an anchor's own card-recipe exception.
- Every mark outside that named set still answers to the ordinary whitelist; polychrome is scoped,
  never ambient.

### Ornament (the mark that is neither type, photograph, nor chrome)
`none` is a literal — the page carries no ornament beyond type, photography and the chassis
itself. Every other position is a blueprint, the same discipline composition and accent already
use: a mark with no fixed shape is a doodle wearing a design-system name.

| Position | Blueprint | In one line |
|---|---|---|
| `none` | `none` | no ornament beyond type, photography and chassis |
| `rule` | `ORN-RULE` | a single hairline divider, never a filled band |
| `texture` | `ORN-TEXTURE` | a low-contrast surface grain, applied to grounds, never to type |
| `pattern` | `ORN-PATTERN` | a repeating geometric mark, bounded to named surfaces only |
| `illustration` | `ORN-ILLUSTRATION` | line-art icons or figures accompanying specific section kinds |

#### `ORN-RULE`
- Exactly `--c-border` at `1px`, laid between two stacked elements (image/text, or two sections) —
  the same hairline `elevation: hairline` already tables, reused as a divider rather than a frame.
- Never doubled and never filled: a second parallel rule or a tinted band is `ORN-TEXTURE` or a
  chassis position wearing this one's name.

#### `ORN-TEXTURE`
- An SVG noise or grain filter (`feTurbulence` + a low-opacity `feColorMatrix`) composited onto a
  section's own ground, contrast unaffected — `--c-text` on `--c-bg` still clears the same ratio
  with or without it, because the grain sits in an overlay layer, not in the type's own paint.
- Applies to grounds and photograph mounts only; a textured control (a button, an input) is a
  material glitch, not a personality.

#### `ORN-PATTERN`
- A repeating geometric motif (stripes, a dot grid, a hatch) at low contrast, confined to ONE named
  surface per page — a section background or a card's own ground — never the whole viewport.
- Never behind body copy: a pattern under running text is the accessibility failure `ORN-TEXTURE`'s
  contrast rule exists to prevent, worn by a different mechanism.

#### `ORN-ILLUSTRATION`
- Custom line-art icons or small figures, one per section of a stated kind (a process step, a
  feature), drawn at a single stroke weight so the set reads as one hand.
- Never a stock icon font substituted at the last minute: an inconsistent stroke weight across the
  set is the same "adjective with a code number" failure this axis exists to prevent.
