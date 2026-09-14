---
slug: barro
nombre: BARRO
tipo: ecommerce
sector: cerámica, textil y madera de talleres pequeños, piezas irrepetibles
objetivo: tienda-lote
enfoque: lujo-oscuro
paginas: [portada, categoria, ficha, carro, pago, pedido-recibido, mi-cuenta, la-marca, contacto, condiciones-venta-envios, aviso-legal, privacidad, cookies, 404]
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

Las 14 de ecommerce (`paginas-obligatorias.md`), completas. Cinco llevan artboard propio en
`canvas/`, porque su composición es trabajo de diseño: **portada** (`Barro.dc.html`), **categoría**
(`Categoria.dc.html`, la mesa: diez piezas de cuatro talleres, taller antes que objeto), **ficha de
producto** (`BarroPieza.dc.html`, el Cuenco hondo), **la marca** (`LaMarca.dc.html`, los cinco
talleres y por qué el lote es corto) y **contacto** (`Contacto.dc.html`, formulario más los datos del
taller de Bailén). Las nueve restantes son derivadas directamente en `maqueta/index.html` a partir
del sistema — cabecera, pie, tipo, color, tablas y formularios — sin lámina propia: **carro**,
**pago**, **pedido recibido**, **mi cuenta**, **condiciones de venta y envíos**, **aviso legal**,
**privacidad**, **cookies** y **404**.

**La categoría dibuja diez piezas y las treinta y una del catálogo llevan a la misma ficha.** Sólo
el Cuenco hondo (Marta Sedano) tiene lámina de detalle, y las diez filas de la categoría —y las tres
tarjetas del estante de portada— llevan a ella, como las doce filas del registro de `marzo` llevan a
su único abrigo y las dieciocho tarjetas de `delao` a su única ficha. La maqueta demuestra que el
tipo de página existe y se alcanza desde el listado y desde el estante; en el sitio del cliente cada
pieza tiene la suya, generada por la plantilla de producto único de WooCommerce.

## Procedencia y decisiones abiertas

El diseño sigue siendo el canvas de `canvas/`, la autoridad — todo cambio empieza ahí y baja después a
la maqueta, nunca al revés. `maqueta/index.html` ya existe: un único fichero autocontenido, con los dos
puntos de ruptura que Elementor expresa, 1024 y 767, y el margen de página en fracción (7,5%) en vez de
píxel fijo para que aguante entre los dos.

**Qué se tradujo y qué se construyó a partir de los tokens.** Los cinco artboards se tradujeron
sección por sección —mismo orden, mismo texto, mismos tokens— a una escala tipográfica fluida entre
375 y 1440px en vez de los píxeles fijos del lienzo a 1440, porque una maqueta de cliente tiene que
sostenerse en cualquier ancho intermedio y el canvas sólo dibuja uno. Las nueve páginas de sistema no
tienen lámina que traducir: se construyeron con los mismos tokens de color, la misma pareja
tipográfica y los mismos componentes (fila de categoría, listas de definición, rejilla de ficha
técnica, formulario) que ya usaban las cinco dibujadas, para que el carro o el aviso legal no se
sientan de otra plantilla. El listado de portada y de la categoría se corrigió respecto al primer
borrador del canvas: dos de las tres tarjetas del estante enlazaban a la propia portada en vez de a
la ficha — el mismo defecto que `paginas-obligatorias.md` existe para cerrar — y la maqueta ya sale
con las tres, y las diez filas de la categoría, apuntando a `#ficha`.

**Tipografías resueltas.** Newsreader y Schibsted Grotesk ya están en `html-mockup/assets/fonts/`, con
su `OFL.txt` y dadas de alta en `_fonts.php` — Newsreader con sus dos caras, redonda y cursiva. La
maqueta las embebe como `data:` woff2 entre los marcadores `NM-FONTS`, igual que el resto de la
biblioteca, leídas de los `.woff2` reales, no tecleadas.

**Veinte ficheros de `img/` quedan fuera de esta plantilla.** `img/` guarda treinta `.webp`, pero el
manifiesto sólo cubre diez: los que ya llevaban las dos láminas originales. Los otros veinte
(`barro-alfar-agost`, `barro-almacen`, `barro-botijo`… la lista completa en el propio directorio) los
generó un proceso que se interrumpió a mitad de tirada; no se puede recuperar su origen ni su sesión,
y este repositorio es público, así que no se usan en ningún artboard ni en la maqueta. Siguen donde
estaban — no se han movido, renombrado ni borrado — porque decidir qué hacer con ellos no es una
decisión de este encargo.

**Sin veredicto todavía.** La geometría de origen está medida —108px = 7,5% de margen, cero raíles
por dentro, cero tinta al cristal, cero desborde— y ahora hay una maqueta completa de 14 páginas sobre
la que correr un barrido, pero no ha corrido: falta `blind-judges` (juez A contra la biblioteca, juez B
sobre esta maqueta) y `visual-verification` a 430, 768 y 1280 en cada página. Sin las dos, esta
plantilla no se ofrece a un cliente — lo dice `_indice.md`, que la sigue marcando «sin veredicto».
