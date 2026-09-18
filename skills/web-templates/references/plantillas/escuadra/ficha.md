---
slug: escuadra
nombre: ESCUADRA
tipo: ecommerce
sector: tienda de hogar de surtido amplio — muebles, cocina, textil, baño, iluminación, almacenaje
objetivo: catalogo-amplio
enfoque: directo
paginas: [portada, categoria, ficha, carro, pago, pedido-recibido, mi-cuenta, la-marca, contacto, condiciones-venta-envios, aviso-legal, privacidad, cookies, 404]
fuentes: [instrument-sans, martian-mono]
canvas_url: https://claude.ai/code/artifact/b3b0274e-6c0c-45c2-8a3e-5b8243e27032
variantes: {}
html_widgets_max: 0
css_custom_max: 0
---

# ESCUADRA · todo lleva su medida puesta

## Para qué sirve

Una tienda de hogar **de surtido amplio y precio visible**: mil setecientos artículos repartidos por
departamentos, de una mesa a una sartén. Quien compra aquí compara por medida y por precio, y quiere
saber si la pieza cabe por la puerta antes de pagarla.

Sirve para grandes superficies de hogar, decoración de precio medio, material de oficina o cualquier
catálogo ancho donde la navegación por departamento es la navegación principal.

## Para qué NO sirve

Una tienda de autor con pocas piezas y procedencia como argumento: eso es `barro`. Aquí no hay nombres
de artesanos; hay referencias, bultos y minutos de montaje.

## ADN — lo que no se toca al adaptarla

- **Abre con una habitación entera y su cuenta.** Seis referencias, seis precios y un total. No abre
  con una frase de marca.
- **Los departamentos entran antes del pliegue.** Tres miniaturas bajo la foto grande. Una sola
  habitación de un solo material en la primera pantalla hace parecer un taller, no una tienda.
- **Todo lleva su medida.** Ancho por fondo por alto en centímetros, también en una toalla. Bultos y
  minutos de montaje en lo que se monta; un guion en lo que no.
- **La cota es el único color.** Un corchete ocre de tres bordes sobre las cifras, nunca una letra. A
  2,44:1 no se veía; a 3,94:1 sí.
- **Dos rejillas y ninguna más.** Seis columnas de 204 en la cabecera y tres de 384 con canal de 36 en
  el cuerpo. Tres ejes en la misma zona se leen como descuido aunque cada medida sea correcta.

## Qué se cambia para un cliente

| Se cambia | Se conserva |
|---|---|
| Departamentos y recuentos | Que el primer bloque sea una habitación con su factura |
| Las dieciséis fotografías | Material y paleta coherentes en todo el surtido |
| Referencias, medidas y precios | Que todo artículo lleve medida |
| El par tipográfico | Grotesca neutra + monoespaciada para cifras |
| El ocre de cota (re-medido) | Que pase el 3:1 y no toque texto |

## Paleta medida

| Papel | Color | Sobre | Contraste |
|---|---|---|---|
| Suelo | `#EDEFEE` | — | — |
| Tinta | `#1E2A32` | `#EDEFEE` | 12,69:1 |
| Texto secundario | `#55636A` | `#EDEFEE` | 5,38:1 |
| Ocre de cota, sólo interfaz | `#9C6D12` | `#EDEFEE` | 3,94:1 — pasa el 3:1 de interfaz, no el 4,5:1 de texto |

## Carril declarado

**Margen de página 7,5 % topado en 108px; contenido topado en 1224px.** Es la medida del lienzo a
1440 (108 de margen, 1224 de caja) expresada como fracción para que aguante entre los dos puntos de
ruptura, y con TECHO para que por encima de 1440 el contenido no crezca y el sobrante se reparta a
los lados. Un solo valor gobierna cabecera, migas, rail de departamentos, menú móvil, secciones y
pie: `--carril: max(clamp(20px, 7,5 %, 108px), (100 % − 1224px) / 2)`.

Por qué con techo y no sólo con fracción: el registro de artículos es una tabla de seis columnas y
un contenido que sigue creciendo con la pantalla separa el nombre del artículo de su precio hasta
que dejan de leerse como la misma fila. Y por qué escrito como RELLENO y no como caja topada con
`margin:auto`: un relleno en porcentaje dentro de un `max-width` **suma** el centrado al margen y
resuelve el porcentaje contra la pantalla, no contra la caja topada. Medido antes del arreglo:
contenido de 1224px a 1440, **1188 a 1680, 1152 a 1920 y 1056 a 2560** —la página se estrechaba al
ensanchar la pantalla— mientras la cabecera, las migas y el rail de departamentos, que no llevaban
tope, se quedaban en el 7,5 % de la pantalla: **dos raíles, a 144 y a 384, a 1920**. En Elementor son
el ancho de contenido de la página y el relleno lateral del contenedor, ambos nativos.

## Mapeo nativo

**Propuesto, no verificado todavía contra el vocabulario nativo introspeccionado.**

| Sección | Elementor (nativo) | Nota |
|---|---|---|
| Cabecera de dos filas | Theme Builder: contenedor de marca y utilidades + contenedor rejilla de seis columnas con el Menú | Cada departamento en su columna de 204, que es exacta sobre el contenido topado de 1224 a 1440 y por encima; entre 1025 y 1440 las seis columnas se reparten el ancho disponible y por debajo de 1024 el rail se desplaza dentro de sí mismo |
| Salón con su factura | Imagen + Loop Grid de seis filas + contenedor con Encabezado de total + Botón | |
| «Añadir el salón al carro» | **Producto agrupado** de WooCommerce con las seis referencias | Nativo: añade varias al carro desde un formulario. **El total 598,00 € no lo calcula WooCommerce**: es texto escrito a mano y se desincroniza si cambia un precio |
| Miniaturas de departamento | Contenedor rejilla de tres + Imagen + Encabezado + etiqueta de recuento | El recuento es texto: WooCommerce no expone el número de productos de una categoría como etiqueta dinámica nativa, **no verificado** |
| Cota | Contenedor de 9px de alto con borde superior de 2px y bordes laterales de 1px | Bordes por lado del contenedor, nativos |
| Catálogo acotado | Loop Grid con Loop Item de una fila | Medidas, bultos y minutos como atributos. **No verificado** su lectura dinámica por fila. Columnas del lienzo, en fracción del contenido: 9,15 % · 32,03 % · 20,59 % · 10,62 % · 10,62 % · 16,99 % (112 · 392 · 252 · 130 · 130 · 208 sobre 1224). El rótulo de columna y su dato usan LAS MISMAS fracciones: por debajo de 767 la fila se apila y la cabecera se retira |
| Registro del salón, de departamentos y de pasos de montaje | El mismo Loop Item con otro juego de columnas | El lienzo les da repartos propios: 112·380·188·124 sobre 804 (salón), 612·306·306 sobre 1224 (departamentos de portada), 70·452·158 sobre 680 (pasos) y nombre/recuento a los extremos (departamentos de La marca) |
| Ficha: compra | `woocommerce-product-title` + `woocommerce-product-price` + `woocommerce-product-add-to-cart` | |
| Ficha: todo lo que mide | Contenedores flex por fila | |
| Escalones de titular y de precio | Tamaños globales de Site Settings | El lienzo dibuja CUATRO h2 (44 · 40 · 36 · 32) y CUATRO precios (38 total · 30 ficha · 22 pieza destacada · 19 fila de cota). Faltaban el h2 de 40 y el de 36 y los precios de 30 y 19, y se falseaban con un tamaño en línea en una dirección o en la otra |
| Pie | Theme Builder | |

## Páginas

Las 14 de `paginas-obligatorias.md` § Ecommerce. Cinco tienen artboard propio en el lienzo —
portada, categoría (departamento Cocina), ficha de artículo, la marca y contacto — porque su
composición es trabajo de diseño. Las nueve restantes se derivan del sistema de la plantilla sin
lámina propia: carro, pago, pedido recibido, mi cuenta, condiciones de venta y envíos, aviso legal,
privacidad, cookies y 404.

**Por qué Cocina y no otro departamento.** ESCUADRA nombra seis departamentos en la navegación, pero
sólo Cocina, Dormitorio y Baño llevan la pareja de fotografías que hace de un departamento algo más
que un nombre en un menú — un recorte «índice» en la portada y la misma foto entera más abajo. De
los tres, Cocina es el más grande (386 artículos, el recuento más alto del surtido) y el que mejor
sostiene un registro largo de medidas dispares frente a la gama más estrecha de Dormitorio o Baño.
Detalle en `canvas/MANIFIESTO.md`.

**Los seis departamentos de la cabecera enlazan al mismo `#categoria`.** Hoy sólo existe la lámina de
Cocina; romper la rejilla de seis columnas de 204 para dejar sólo un departamento clicable habría ido
contra el ADN de la plantilla («los departamentos entran antes del pliegue»), así que Salón,
Dormitorio, Baño, Almacenaje e Iluminación aterrizan también en la página de Cocina en vez de un
enlace muerto. Es un desajuste de contenido declarado, no un enlace roto: se documenta aquí y no se
disimula. Del mismo modo, todo registro de artículos —el salón entero, el catálogo acotado de la
portada, el listado de Cocina— enlaza cada fila a la única ficha dibujada, la Silla Aro, siguiendo la
misma convención que `barro/canvas/Categoria.dc.html` ya usaba para sus propias filas.

**Utilidades de cabecera sin página propia se retiraron de la maqueta.** El lienzo dibuja «Buscar»,
«Guías de montaje» y «Tiendas» en la cabecera; ninguna de las tres tiene página en el juego de 14, así
que la maqueta no las lleva — la cabecera real ofrece en su lugar La marca, Contacto y Mi cuenta, que
sí son páginas. Es la misma poda que hizo `barro/maqueta/index.html` con su propia cabecera.

## Procedencia y decisiones abiertas

Canvas en `canvas/`, cinco artboards. Maqueta en `maqueta/index.html`, un fichero autocontenido con
las 14 páginas y el mismo router por hash que `marzo` y `barro`. La plantilla empezó como un taller de
doce piezas de contrachapado y el usuario la reorientó a tienda de hogar completa; la ficha de
producto, la Silla Aro, sobrevive de esa primera versión y encaja en el surtido nuevo.

**Qué se tradujo del lienzo y qué se construyó desde los tokens.** Portada, categoría, ficha, la marca
y contacto derivan sección a sección de sus artboards, con las mismas fotografías y el mismo registro
de medidas. Carro, pago, pedido recibido, mi cuenta y las cuatro páginas legales no tenían artboard:
se construyeron directamente con el sistema medido en la Paleta — mismo par tipográfico, mismos
`--hair-*`, mismo `--c-border` ocre sólo en bordes — siguiendo la forma que `marzo` y `barro` ya
probaron para las suyas (tabla de pedidos, resumen de carro, formulario de pago, cuatro páginas
legales con la nota de que el texto real se escribe con `wordpress-legal`).

**Tipografías.** Instrument Sans y Martian Mono están ahora en `html-mockup/assets/fonts/`
(`instrument-sans-latin.woff2`, `martian-mono-latin.woff2`, registradas en `_fonts.php`); la nota
anterior de esta ficha, que decía lo contrario, estaba obsoleta y queda corregida aquí. La maqueta las
embebe como `data:font/woff2;base64` entre `NM-FONTS:BEGIN`/`END`, leídas de los bytes reales.

**El objetivo `catalogo-amplio` sigue siendo nuevo.** Hay que darlo de alta en el recomendador cuando
exista.

**Tres miniaturas son recortes** de fotos que salen enteras más abajo en la misma página. Es la
relación índice → departamento de cualquier tienda de hogar y se deja así a propósito.

**Sin veredicto.** Geometría medida con Chrome sin cabeza contra los cinco artboards a 1440 y la
maqueta a 430, 768, 1024, 1280, 1440, 1680, 1920 y 2560, más 1920 dentro de un `iframe` con barra de
desplazamiento: **un solo carril a todos los anchos**, contenido de 1224 desde 1440 en adelante, cero
raíles por dentro del margen dominante, cero tinta al cristal, cero desborde, cero solapes entre
elementos renderizados (`checkVisibility()`), cero precios partidos y consola limpia salvo el favicon.
Alto de página contra su artboard: portada +0,1 %, categoría −2,0 %, ficha +2,8 %, la marca +0,7 %,
contacto −4,1 %. La portada y la ficha existentes pasaron cuatro rondas de juez antes
de esta entrega; las tres láminas nuevas y la maqueta entera no las ha pasado — `blind-judges` y el
barrido a 430/768/1280 de `visual-verification` no se han ejecutado todavía, así que `_indice.md` debe
seguir marcando ESCUADRA «sin veredicto» y no se ofrece a un cliente. La geometría es correcta por
construcción y por las comprobaciones automatizadas (`empaquetar.php`, `color.php`, la cadena de
tests); nadie la ha mirado todavía.
