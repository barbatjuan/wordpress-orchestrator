---
slug: marzo
nombre: MARZO
tipo: ecommerce
sector: moda de fibra natural, prendas de telar con tirada corta
objetivo: tienda-talla
enfoque: materia
paginas: [portada, categoria, ficha, carro, pago, pedido-recibido, mi-cuenta, la-marca, contacto, condiciones-venta-envios, aviso-legal, privacidad, cookies, 404]
fuentes: [bodoni-moda, jost]
canvas_url: https://claude.ai/code/artifact/c52ec5bb-d146-464e-bc81-f32a1aa069f2
variantes: {}
html_widgets_max: 0
css_custom_max: 0
---

# MARZO · la tienda es un registro

## Para qué sirve

Una marca de ropa que vende **por la fibra y por el telar**, no por la temporada. Pocas prendas,
cada una con su gramaje, su composición y el pueblo donde se tejió. Quien compra aquí lee antes de
mirar: la colección se presenta como un registro de fichas técnicas, no como una rejilla de fotos.

Sirve igual para mantas, punto, calzado artesanal o cualquier catálogo corto donde la ficha técnica
es el argumento de venta.

## Para qué NO sirve

Moda de rotación rápida con cientos de referencias, filtros por color y rebajas. Ahí la primera
intención es filtrar y comparar miniaturas, y esta plantilla quita las miniaturas a propósito.

## ADN — lo que no se toca al adaptarla

- **La colección es una tabla, no una rejilla.** Referencia, nombre, fibra, gramaje, pueblo del
  telar y precio. Sin miniaturas: una fotografía no dice el gramaje.
- **No hay color de acento.** El botón de comprar es un rectángulo de tinta. El único color fuera
  del blanco roto y la tinta es `#8C4A3F`, y sólo marca el estado de existencias.
- **Una fotografía por página rompe el margen, y sólo una.** En la portada la del abrigo, en la ficha
  la de las dos vistas: sangran por la derecha mientras el texto mantiene sus 108 a la izquierda.
  Repetirlo en cada sección lo convierte en ruido.
- **El color de existencias marca filas enteras.** «Últimas 3 unidades», «Agotada · vuelve en
  noviembre», y en la tabla de tallas la fila completa de la talla agotada, cifras incluidas.
- **Display de alto contraste con eje óptico.** Bodoni Moda usa su eje `opsz`: el mismo tipo es fino
  en el titular y robusto en la cifra.

## Qué se cambia para un cliente

| Se cambia | Se conserva |
|---|---|
| Marca, nombre de las prendas y pueblos | Las seis columnas del registro |
| Las nueve fotografías | Que la colección no lleve miniaturas |
| El copy | Que la ficha lleve composición, gramaje y cuidado |
| El par tipográfico | Display de alto contraste + palo seco geométrico |
| El color de existencias (re-medido) | Que sea el único color y no se use como acento |

## Paleta medida

| Papel | Color | Sobre | Contraste |
|---|---|---|---|
| Suelo | `#F1EDE6` | — | — |
| Suelo alterno | `#E4DDD2` | — | — |
| Tinta | `#1A1815` | `#F1EDE6` | 15,18:1 |
| Texto secundario | `#5C5852` | `#F1EDE6` | 6,05:1 |
| Texto secundario en alterno | `#5C5852` | `#E4DDD2` | 5,24:1 |
| Estado de existencias | `#8C4A3F` | `#F1EDE6` | 5,68:1 |

## Mapeo nativo

**Propuesto, no verificado todavía contra el vocabulario nativo introspeccionado.** Elementor Pro con
WooCommerce, sin widget HTML y sin CSS a medida. Colores y tipografías en los ajustes globales.

| Sección | Elementor (nativo) | Nota |
|---|---|---|
| Cabecera | Plantilla de cabecera del Theme Builder: contenedor flex + Logotipo + Menú + `woocommerce-menu-cart` | |
| El registro | Loop Grid de Elementor Pro con una plantilla de Loop Item de una fila: contenedor flex + etiquetas dinámicas de título y precio | Fibra, gramaje y pueblo son atributos del producto. **No verificado** que una etiqueta dinámica nativa lea un atributo suelto por fila |
| Fotografía a sangre | Contenedor rejilla de dos pistas (datos 300 · foto) con hueco de columna de 16% en portada y la marca y de 56 en la ficha, relleno izquierdo de 7,5% y cero a la derecha + Imagen. En tableta y móvil, una columna y relleno derecho de 8,108% en el contenedor de datos | La sangre es el relleno asimétrico del contenedor, no un margen negativo. El hueco es el aire del lienzo entre los datos y la foto (212 y 56 a 1440): como hueco y no como pista vacía no pide un contenedor vacío. El relleno derecho en una columna devuelve a los datos el mismo margen que a la izquierda |
| Ficha: galería de dos vistas | `woocommerce-product-images` | |
| Ficha: título, precio, compra | `woocommerce-product-title` + `woocommerce-product-price` + `woocommerce-product-add-to-cart` | El botón toma el color de tinta de los ajustes globales |
| Ficha: tabla de tallas | Contenedores flex por fila + Encabezado + Editor de texto | Una fila por talla |
| Ficha: costura y cuidado | Contenedor dos columnas + Editor de texto | |
| Migas | `woocommerce-breadcrumb` | |
| Pie | Plantilla de pie del Theme Builder | Enlaces legales incluidos |

## Páginas

Las 14 de ecommerce (`paginas-obligatorias.md`), completas. Cinco llevan artboard propio en
`canvas/`, porque su composición es trabajo de diseño: **portada** (`Marzo.dc.html`), **categoría**
(`Categoria.dc.html`, el registro completo), **ficha de producto** (`MarzoPieza.dc.html`, el Abrigo
Sagra), **la marca** (`LaMarca.dc.html`, el taller y la tirada corta) y **contacto**
(`Contacto.dc.html`, formulario + datos del taller). Las nueve restantes son derivadas directamente
en `maqueta/index.html` a partir del sistema — cabecera, pie, tipo, color, tablas y formularios —
sin lámina propia: **carro**, **pago**, **pedido recibido**, **mi cuenta**, **condiciones de venta y
envíos**, **aviso legal**, **privacidad**, **cookies** y **404**.

**El registro dibuja una ficha y la usan las once filas.** Sólo el Abrigo Sagra (`LM-620`) tiene
lámina de detalle, y las once filas del registro llevan a ella, como las dieciocho tarjetas de delao
llevan a su única ficha. La maqueta demuestra que el tipo de página existe y se alcanza desde el
listado; en el sitio del cliente cada unidad tiene la suya, generada por la plantilla de producto
único. Las once se comportan igual: una tabla donde una fila se pulsa y diez no se lee como rota,
que es precisamente el defecto que `paginas-obligatorias.md` vino a cerrar.

## Procedencia y decisiones abiertas

El diseño sigue siendo el canvas de `canvas/`, la autoridad — todo cambio empieza ahí y baja después a
la maqueta, nunca al revés. `maqueta/index.html` ya existe: un único fichero autocontenido, con los dos
puntos de ruptura que Elementor expresa, 1024 y 767, y el margen de página en fracción (7,5%) en vez de
píxel fijo para que aguante entre los dos.

**Qué se dibujó y qué se derivó.** Los cinco artboards se tradujeron sección por sección — mismo
orden, mismo texto, mismos tokens — a una escala tipográfica fluida entre 375 y 1440px en vez de los
píxeles fijos del lienzo a 1440, porque una maqueta de cliente tiene que sostenerse en cualquier ancho
intermedio y el canvas sólo dibuja uno. Las nueve páginas de sistema no tienen lámina que traducir: se
construyeron con los mismos tokens de color, la misma pareja tipográfica y los mismos componentes
(tabla del registro, listas de definición, formulario) que ya usaban las cinco dibujadas, para que
carro, pago o el aviso legal no se sientan de otra plantilla.

**Tipografías resueltas.** Bodoni Moda y Jost ya están en `html-mockup/assets/fonts/`, con su
`OFL.txt` y dadas de alta en `_fonts.php` — Bodoni Moda con sus dos caras, redonda y cursiva. La
maqueta las embebe como `data:` woff2 entre los marcadores `NM-FONTS`, igual que el resto de la
biblioteca.

**Sin veredicto todavía.** La geometría de origen está medida —108px = 7,5% de margen, cero raíles
por dentro, cero tinta al cristal, cero desborde— y ahora hay una maqueta completa de 14 páginas sobre
la que correr un barrido, pero no ha corrido: falta `blind-judges` (juez A contra la biblioteca, juez B
sobre esta maqueta) y `visual-verification` a 430, 768 y 1280 en cada página. Sin las dos, esta
plantilla no se ofrece a un cliente — lo dice `_indice.md`, que la sigue marcando «sin veredicto».
