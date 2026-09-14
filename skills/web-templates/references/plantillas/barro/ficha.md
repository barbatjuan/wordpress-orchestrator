---
slug: barro
nombre: BARRO
tipo: ecommerce
sector: cerámica, textil y madera de talleres pequeños, piezas irrepetibles
objetivo: tienda-lote
enfoque: lujo-oscuro
paginas: [portada, ficha]
fuentes: [newsreader, schibsted-grotesk]
canvas_url: https://claude.ai/code/artifact/c52ec5bb-d146-464e-bc81-f32a1aa069f2
variantes: {}
html_widgets_max: 0
css_custom_max: 0
---

# BARRO · quien lo hace es el titular

## Para qué sirve

Una tienda que reúne **talleres con nombre y apellido**: la ceramista de Bailén, la tejedora de
Béjar, el tornero de Urbasa. Cada pieza sale de un lote corto y ninguna es igual a la anterior, así que
el titular es quien la hace y el objeto va de pie de foto.

Sirve para cooperativas de artesanía, tiendas de autor y cualquier catálogo donde la procedencia
vale más que la referencia.

## Para qué NO sirve

Un catálogo amplio de hogar con precio como argumento: eso es `escuadra`. Aquí no hay «desde», no
hay departamentos y no hay miles de artículos; hay tres talleres en portada.

## ADN — lo que no se toca al adaptarla

- **Oscura a propósito.** Suelo casi negro cálido, texto crema. Las fotografías de producto sobre
  blanco tiza se pegan borde con borde en una banda de 1440: dejan de parecer pegatinas blancas sobre
  negro y pasan a ser un estante iluminado.
- **El nombre del taller va antes que el del objeto.** «Marta Sedano» en display; «Cuenco hondo» en el
  cuerpo, junto al precio.
- **La ficha técnica es el producto.** Chamota, temperaturas de bizcocho y esmalte, tolerancias de
  ±0,4 cm, comportamiento en lavavajillas y microondas. Un juez la llamó relleno desde una captura
  escalada; leída, es lo que justifica el precio. No se recorta.
- **El verde esmalte nunca es texto.** `#6E7B4F` mide 3,81:1 sobre el suelo: vale para el subrayado de
  los enlaces de compra, no para una letra.
- **Pies de foto en rejilla propia.** Bajo cada banda a sangre, cada pie va a 108 de su columna, no a
  32 de su foto: dos raíles en la misma columna se leen como error.

## Qué se cambia para un cliente

| Se cambia | Se conserva |
|---|---|
| Talleres, nombres y piezas | Que el taller sea el titular |
| Las diez fotografías | Producto sobre blanco en banda continua; ambiente a sangre |
| La ficha técnica de cada material | Que exista y sea completa |
| El par tipográfico | Serif de texto con eje óptico + grotesca sobria |
| El verde esmalte (re-medido) | Que no toque texto |

## Paleta medida

| Papel | Color | Sobre | Contraste |
|---|---|---|---|
| Suelo | `#1C1A16` | — | — |
| Suelo alterno | `#262219` | — | — |
| Tinta crema | `#EDE7DA` | `#1C1A16` | 14,10:1 |
| Texto secundario | `#A89F8C` | `#1C1A16` | 6,62:1 |
| Texto secundario en alterno | `#A89F8C` | `#262219` | 6,04:1 |
| Verde esmalte, sólo interfaz | `#6E7B4F` | `#1C1A16` | 3,81:1 — pasa el 3:1 de interfaz, no el 4,5:1 de texto |

**El suelo va en `html, body` además de en el envoltorio.** Declarado sólo en el selector del canvas,
el runtime lo pierde y la página sale crema sobre casi blanco; declarado sólo en el envoltorio, el
frame pinta blanco donde el contenido no llega. Las dos cosas se pagaron en esta plantilla.

## Mapeo nativo

**Propuesto, no verificado todavía contra el vocabulario nativo introspeccionado.** Elementor Pro con
WooCommerce, sin widget HTML y sin CSS a medida.

| Sección | Elementor (nativo) | Nota |
|---|---|---|
| Cabecera | Plantilla de cabecera del Theme Builder + `woocommerce-menu-cart` | |
| Banda de tres productos | Contenedor flex de 1440 sin hueco + tres Imagen de 480 | Contenedor a ancho completo, sin relleno |
| Pies de la banda | Contenedor flex + tres contenedores de 480 con relleno lateral de 108 + Encabezado + Editor de texto + Botón de texto | El subrayado verde es el borde inferior del botón |
| Las dos casas | Contenedor flex de dos Imagen de 720 + pies con el mismo relleno | |
| Ficha: compra | `woocommerce-product-title` + `woocommerce-product-price` + `woocommerce-product-add-to-cart` | |
| Ficha: rejilla de especificaciones | Contenedor rejilla de 4 × 3 con relleno lateral de 80 + doce contenedores con borde | La regla cruza los 1440; el texto cae en 108 |
| Migas | `woocommerce-breadcrumb` | |
| Pie | Plantilla de pie del Theme Builder | |

## Páginas

Portada y ficha. Carro, pago y cuenta, nativas de WooCommerce. Más aviso legal, privacidad, cookies,
términos y 404.

## Procedencia y decisiones abiertas

Canvas en `canvas/`. Sin maqueta todavía.

**Tipografías resueltas.** Newsreader y Schibsted Grotesk ya están en
`html-mockup/assets/fonts/`, con su `OFL.txt` y dadas de alta en `_fonts.php`. La maqueta las embebe
por ruta relativa, como el resto de la biblioteca.

**Sin veredicto.** Geometría medida: 108px = 7,5%, cero raíles por dentro, cero tinta al cristal, cero
desborde. Falta la mitad de jueces.
