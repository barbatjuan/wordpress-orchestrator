---
slug: noir-claro
nombre: Maison Noir · Claro
tipo: ecommerce
sector: perfumería de autor — extractos en partidas numeradas, vendidos por set de muestras antes que por frasco
objetivo: muestra-primero
enfoque: editorial
paginas: [portada, tienda, ficha, set-descubrimiento, cesta, pago, pedido-recibido, mi-cuenta, la-maison, contacto, condiciones-venta-envios, aviso-legal, privacidad, cookies, 404]
fuentes: [prata, jost]
variantes: {}
html_widgets_max: 0
css_custom_max: 0
---

# Maison Noir · Claro · que lo pruebe en casa antes de pagar el frasco, a plena luz

Versión clara del encargo MAISON NOIR. La Plantilla hermana `noir` es la oscura: el mismo texto, las mismas
quince páginas, el mismo frasco y el mismo Objetivo. Esta cambia la sala a oscuras por papel
cálido y bronce, la didone con itálica por Prata redonda, y recompone dos bandas de la portada: el hero pasa
a **imagen a sangre con un panel claro enmarcado** y la franja de ventajas a **una banda bronce**. Se ofrecen
como alternativa entre sí para un mismo cliente.

## Para qué sirve

Una tienda que vende **algo caro que no se puede elegir por la foto**: un perfume, y por extensión un té de
origen, una cosmética de alta gama o un destilado en miniatura. El visitante no compra el frasco de
285&nbsp;€ la primera vez; compra un **set de descubrimiento** de nueve viales por 35&nbsp;€, lo vive una
semana y **el importe se descuenta íntegro del primer frasco**. El camino que la Plantilla existe para
sostener es set → crédito en la cuenta → frasco; las tres muestras de regalo y la sesión olfativa en la
boutique son el mismo argumento en otras dos formas.

**Frente a `noir`, decide el tono.** Una casa que se vende como nocturna, densa y de lujo cerrado parte de
`noir`. Una casa que se vende como atelier luminoso, botánico o de piel —colonias, cítricos, cosmética de
día, un té— parte de `noir-claro`.

## Para qué NO sirve

- **La duda es la talla o el material, y se resuelve con una tabla** → `tienda-talla` (MARZO).
- **La pieza sale una vez y se agota, y quién la hizo es el argumento** → `tienda-lote` (BARRO).
- **La entrega se repite con cadencia** → `suscripcion` (TUESTE). El set es una compra única que lleva a
  una compra suelta grande.
- **Un catálogo ancho que se navega por categoría** → `catalogo-amplio`. Nueve fórmulas en cuatro familias
  es un catálogo corto que se elige oliendo.
- **Una marca cuyo registro es la noche y el negro** → `noir`: pasar esta a oscuro es la otra Plantilla.

## ADN — lo que no se toca al adaptarla

- **El set de descubrimiento tiene página propia y precio cerrado, y se descuenta del primer frasco.** La
  banda «Prueba antes de decidir» lo explica en tres pasos y lleva a su ficha; el pie lo enlaza; Mi cuenta
  enseña el crédito pendiente; Condiciones de venta lo regula.
- **Papel cálido en tres tonos** (`#FAF6F0` suelo, `#F1EAE0` alterno, `#ECE4D8` marco) y tinta casi negra
  cálida. Pasarla a oscuro es `noir`.
- **Hero a sangre con panel claro.** La fotografía ocupa la banda entera; todo el texto va en un panel opaco
  de 620&nbsp;px con filete bronce, a la izquierda del raíl. Nunca texto directamente sobre la foto.
- **Bronce como campo una vez y como letra el resto.** La franja de ventajas es el único campo bronce;
  después pinta precio, antetítulo, la palabra final del titular y el botón principal.
- **Didone redonda sin itálica** en titulares (Prata 400) y palo seco a 400 en versalitas espaciadas para
  todo lo demás. La palabra destacada del titular va en bronce, no en cursiva.
- **Filetes, no sombras.** Tarjetas, reseñas, resumen, panel del hero y pirámide olfativa se separan con
  filete de 1&nbsp;px en bronce apagado.

## Qué se cambia para un cliente

| Se cambia | Se conserva |
|---|---|
| La casa, las nueve fórmulas, sus notas y precios | Que el set tenga ficha, precio cerrado y se descuente del primer frasco |
| Las catorce fotografías | Clave alta: un frasco por foto, de frente, sobre piedra o madera clara, sin sangrar salvo el fondo del hero |
| La foto de fondo del hero | Panel claro opaco encima; la foto no lleva texto |
| Número de viales, precio y validez del crédito | Que la cifra sea la misma en portada, ficha del set, cuenta y condiciones |
| Bronce `#6F5326` (re-medido) | Un solo acento cálido: una banda como campo, el resto como letra y botón |
| Prata + Jost | Didone redonda de un peso + palo seco geométrico en versalitas |
| La boutique y la sesión olfativa | Que «Reservar cita» aterrice en contacto con su motivo ya en el selector |

## Paleta medida

Medida con `color.php`, no copiada del lienzo. `color.php --maqueta` re-mide los veintiséis pares del
`:root` y sale en `0`.

| Papel | Color | Sobre | Contraste |
|---|---|---|---|
| Suelo / alterno / marco de foto | `#FAF6F0` / `#F1EAE0` / `#ECE4D8` | — | — |
| Titulares | `#16120F` | los tres suelos | 17,30 / 15,60 / 14,77:1 |
| Texto intenso | `#1E1815` | los tres suelos | 16,30 / 14,70 / 13,92:1 |
| Menú, resumen (α .78) | `#4D4743` | los tres suelos | 8,49 / 7,65 / 7,25:1 |
| Cuerpo (α .75) | `#544E4A` | los tres suelos | 7,60 / 6,85 / 6,49:1 |
| Rótulos, notas, pies (α .72) | `#5A5550` | los tres suelos | 6,84 / 6,17 / 5,84:1 |
| Bronce como letra | `#6F5326` / al pasar `#4F3A1A` | los tres suelos | 6,63 / 5,98 / 5,66:1 · 9,99 / 9,01 / 8,53:1 |
| Papel sobre bronce (botón, franja de ventajas) | `#FAF6F0` | `#6F5326` / `#4F3A1A` | 6,63 / 9,99:1 |
| Borde de campo, **re-medido** | `#827D78` (antes `rgba(28,22,18,.22)` ≈ `#C9C5BF`, 1,36:1 sobre el marco) | los tres suelos | 3,78 / 3,41 / 3,23:1 |
| Seleccionado (chip, envío, crédito) | bronce / `#4D4743` sobre `#E9E2D8` | — | 5,55 / 7,11:1 (a mano: `--c-sel` no lleva `bg` para que el matcher no lo cruce con todo) |
| Texto del hero | los tokens de arriba sobre el panel opaco `#FAF6F0` | peor píxel de `noir-bodegon-extrait` con velo 1 | 6,63:1 bronce · 6,84:1 rótulo (`scrim.php`): la foto no toca el texto |

Filetes decorativos (`#E4DCD0`, `#E1D9CC`, `#DBD2C4`), el del botón con borde (`#7A6036`, cuyo texto es
bronce), la regla entre números del carrusel (`#B7B3AD`) y el rombo de la franja (`#D0C5B3` sobre bronce):
separan, no identifican un control. **Todo color y toda familia viven en `:root`.**

## Techo de composición

**Un solo raíl a cualquier ancho**, el mismo de `noir`: `--pad-x: max(min(15%, calc(50% - 420px)),
calc((100% - 1008px) / 2))`, con el cruce del centrado de 1008&nbsp;px exactamente en 1440; bajo 1024
`max(32px, calc(50% - 420px))`; bajo 767, 22&nbsp;px. El lienzo claro no dice otra cosa.

Dos bandas del lienzo claro no seguían el raíl y se ajustan:

- **El hero** sangra la foto de borde a borde (es la composición) y deja el panel en el raíl: el texto
  queda a raíl + relleno del panel (`clamp(28px, 4vw, 52px)`), un sangrado interior, no un segundo raíl.
  `min(88vh, 820px)` pasa a 792&nbsp;px; bajo 767, alto libre con 120&nbsp;px de foto encima del panel.
- **La franja de ventajas** venía centrada con 22&nbsp;px de relleno y 40&nbsp;px entre rótulo y rombo, lo
  que abría un raíl por dentro del margen (202&nbsp;px a 1440). Se ciñe al raíl con `space-between`: 35&nbsp;px
  de hueco a 1440 y 16 de mínimo a 1280. A 1024 y menos, dos columnas sin rombos.

Medido con `medir-geometria.mjs`: margen dominante 192&nbsp;px a 1280 (15&nbsp;%), 216 a 1440 (15&nbsp;%) y 456 a
1920 (contenido 1008), **cero raíles por dentro del margen** y nada al cristal.

## Objetivo

**`muestra-primero`**, el mismo de `noir` y por las mismas señales: set de descubrimiento de pago, «se
descuenta de tu primer frasco», muestras de regalo, sesión en la boutique. Fronteras en `recomendador.md`.

## Enfoque, eje por eje

Leído de `canvas/NoirClaro.dc.html` y medido en la maqueta. **No puede ser `vitrina`**: ecommerce +
`muestra-primero` + `vitrina` ya es `noir`, y además el lienzo claro contradice la frase que define
`vitrina` («si las referencias llevan la foto a sangre de borde a borde, no es este enfoque»): su hero
sangra y su suelo es papel. Candidatas: `editorial` (posición · `delao`) y `materia` (posición · `marzo`).

| Eje | Lo que mide el lienzo claro | `editorial` | `materia` |
|---|---|---|---|
| Escala | H1 `clamp(40px, 5,4vw, 78px)` sobre cuerpo de 15&nbsp;px; H2 de 46–49 | sí (editorial) | no (clásica) |
| Densidad | Secciones de 81&nbsp;px, panel del hero con 52&nbsp;px de relleno | sí (generosa) | no (estándar) |
| Fondo | `#FAF6F0` / `#F1EAE0`, papel cálido | sí (`delao`: claro y cálido) | sí (cálido claro) |
| Elevación | Sin sombras; filete de 1&nbsp;px en bronce apagado | a medias (ninguna, pero hay filete) | sí (filete) |
| Composición | Hero con texto a la izquierda y foto a sangre; rejillas de 3 y 4 | a medias (asimétrica en el hero, estricta después) | a medias (estricta, pero el hero sangra) |
| Acento | Bronce en precio, antetítulo, palabra final y botón; una banda como campo | no (ninguno) | a medias (campo teñido una vez) |
| Chasis | Panel del hero, familias y reseñas enmarcadas; pasos, pirámide y filas divididos por filete | a medias (dividido por filetes) | a medias (enmarcado por filete) |
| Ornamento | Regla de 46&nbsp;px antes del antetítulo y rombos de 4&nbsp;px | sí (filete) | no (textura) |

**Recuento:** `editorial` cinco y medio; `materia` cuatro. **Es `editorial`.** Deciden también las frases:
`editorial` son «titulares con serifa grande sobre fondo claro, mucho aire, fotografía encuadrada como en
una revista, filetes finos en lugar de tarjetas y casi ningún color fuera del texto», y el par
«display con serifa de alto contraste + palo seco de texto» es Prata + Jost. `materia` pide que la página
«se sienta como la sustancia» con producto a luz cálida de borde a borde en la rejilla y textura; aquí la
rejilla de producto va enmarcada y no hay textura.

**Divergencias declaradas:** el bronce sí se gasta como campo en la franja de ventajas y como relleno del
botón (`editorial` pide acento ninguno).

**Frente a `delao`, que encarna `editorial`:** otro tipo (corporate), otro Objetivo, serifa condensada y
sin acento. **Frente a `marzo`, que usa el mismo palo seco** (Jost): MARZO es Bodoni Moda con itálica, sin
foto en la colección y sin acento; NOIR · Claro es Prata redonda, foto por tarjeta y bronce. Un juez de
«misma mano» debería mirar juntas `noir-claro`, `marzo` y `noir`.

**Dirección de imagen:** el frasco de frente sobre piedra, madera o terciopelo, luz lateral cálida; hero
de ancho completo con la pieza fuera del panel; taller y boutique en la misma luz.

## Mapeo nativo

**Propuesto, no verificado todavía contra el vocabulario nativo introspeccionado.** Elementor Pro con
WooCommerce, sin widget HTML y sin CSS a medida; la columna Divi es orientativa y **no validada**. Colores y
las dos familias en los ajustes globales; la entrada `entra` del carrusel es la animación de entrada nativa.
Todo lo que no se nombra abajo es igual que en `noir`.

| Sección | Elementor + WooCommerce (nativo) | Divi (no validado) | Nota |
|---|---|---|---|
| Cabecera | Theme Builder: contenedor flex + Encabezado (logotipo en texto) + Menú de navegación + `woocommerce-menu-cart` en modo texto «Cesta (n)» | Cabecera global: Menú + Carrito | Opaca y pegajosa |
| `hero` | Contenedor flex de alto mínimo 792&nbsp;px con **imagen de fondo** (cubrir, centrada) · contenedor hijo de 620&nbsp;px con fondo papel y borde de 1&nbsp;px: **Carrusel anidado** con tres diapositivas (Divisor 46×1 + Encabezado + Encabezado H1/H2 con la palabra final como segundo Encabezado en bronce + Editor de texto), flechas y paginación numérica, autoplay 6,5&nbsp;s · Botón relleno | Sección con imagen de fondo + Slider en una columna con fondo | Bajo 767, alto libre; la foto asoma 120&nbsp;px por encima del panel |
| `servicios` | Contenedor flex con fondo bronce, justificado `space-between`, hueco mínimo 16&nbsp;px: cuatro Encabezados + tres **Divisores** de 4×4 girados 45° (o Icono de rombo) · bajo 1024, contenedor rejilla de 2 con los rombos ocultos por dispositivo | Fila con fondo + Texto ×4 | |
| `seleccion` | Cabecera flex (Encabezado + Botón de texto subrayado) + `woocommerce-products` (3 columnas) con su plantilla de tarjeta: Imagen 3/4 con borde + título + atributo «notas» + precio | Tienda con 3 productos | Bajo 767, tarjeta en fila (imagen 42&nbsp;%) |
| `familias` | Divisor + Encabezados + rejilla de 4 (2 bajo 1024) de contenedores enlazados con borde | Blurbs ×4 con borde | |
| `taller`, La Maison | Contenedor rejilla de 2: Imagen · Encabezados + Editor de texto + Botón | Fila 2 columnas | |
| `muestras` | Contenedor con fondo alterno: cabecera flex + 3 pasos con borde superior + Botón con borde a la ficha del set | Blurbs ×3 + Botón | Plantilla global |
| `opiniones` | Cabecera flex + rejilla de 3 (1 bajo 1024): **Testimonio** con borde y Estrellas | Testimonios ×3 | |
| `cartas` | Contenedor rejilla de 2: Encabezados · `Formulario` (Correo + Aceptación) | Opt-in de correo | |
| `encuentra` | Contenedor centrado: Encabezado + Editor de texto + dos Botones | Llamada a la acción | |
| Tienda, ficha, set, cesta, pago, pedido recibido, mi cuenta, contacto, legales, 404, pie | Como en `noir` (`woocommerce-*`, `Formulario`, Theme Builder) | Como en `noir` | Mismas notas sobre crédito del set, muestras de regalo y mapa estático |

**Techo declarado: cero widgets HTML y cero reglas de CSS a medida.** Como en `noir`, el crédito del set
que se descuenta del primer frasco y las tres muestras de regalo a elegir en la cesta son lógica de pedido,
no de maquetación, y piden una extensión confirmada antes de prometerlos.

**Quitado del lienzo por no tener expresión nativa:** las rejillas `auto-fit`, las medidas en `vh`, la
monoespaciada de sistema de las etiquetas de hueco. Se quedan porque sí la tienen: el carrusel con
autoplay, la imagen de fondo del hero, el rombo (Divisor girado), la cabecera pegajosa y la entrada animada.
El halo radial de `noir` no existe en el lienzo claro y no se trae.

## Páginas

Quince, las mismas de `noir`: las catorce de ecommerce más **set-descubrimiento**. Del lienzo salen
**portada**, **tienda**, **ficha** (Nuit Absolue), **cesta**, **pago**, **la-maison** y **contacto**; se derivan
del sistema **set-descubrimiento**, **pedido-recibido**, **mi-cuenta**, **condiciones-venta-envios**,
**aviso-legal**, **privacidad**, **cookies** y **404**. Todas las tarjetas llevan a la única ficha dibujada;
la paginación de la tienda es texto; filtros y selectores cambian de estado pero no filtran. Cero enlaces
internos muertos y ningún recurso remoto.

## Procedencia y decisiones abiertas

El lienzo claro llegó junto al oscuro con una lámina de test tipográfico; los dos se copiaron sin tocar
y las correcciones viven en la maqueta (`canvas/MANIFIESTO.md`). La maqueta parte de la de `noir` y cambia
tokens, familias, hero y franja de ventajas. Fuentes embebidas de `_fonts.php`: **Prata 400, añadida para
esta Plantilla** (OFL, subset latin, 19&nbsp;224&nbsp;bytes) y Jost 300–500, ya registrada.

**Fotografías: trece propias en clave alta y una compartida.** Las de `noir` son frasco sobre negro y sobre
papel se leían como bloques oscuros; se regeneraron en Magnific usando cada oscura como referencia de imagen
(mismo frasco, tapón y composición) sobre mármol crema, travertino, caliza o madera clara con luz de día
difusa. El hero se recompuso en 21:9 con el frasco al 80&nbsp;% y va anclado a la derecha: desde 1280 el frasco
queda fuera del panel; a 1024 y menos, el panel lo tapa. Compartida con `noir`: `noir-taller`, que ya era
clara. Detalle y créditos en `manifiesto-imagenes.md`.

Abiertas:

- **Sin artboards móviles**: 1024 y 767 se heredaron de `noir`.
- **Una sola ficha dibujada** para seis tarjetas, y la segunda página de la tienda sin dibujar.
- **Dominio y teléfono del lienzo** (`maisonnoir.com`, `+33 1 42 60 18 04`) se revisan antes de publicar.
- **`enfoques.md`** no tiene columna «En `noir-claro`» bajo `editorial`.

**Sin veredicto.** Contraste, velo, barrido y geometría medidos; faltan `blind-judges` —con el juez A
mirando contra `noir`, `marzo` y `delao`— y `visual-verification`. Sin los dos, esta Plantilla no se ofrece a
un cliente.
