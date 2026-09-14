---
slug: marzo
nombre: MARZO
tipo: ecommerce
sector: moda de fibra natural, prendas de telar con tirada corta
objetivo: tienda-talla
enfoque: materia
paginas: [portada, ficha]
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
| Fotografía a sangre | Contenedor rejilla de tres pistas con relleno izquierdo de 108 y cero a la derecha + Imagen | La sangre es el relleno asimétrico del contenedor, no un margen negativo |
| Ficha: galería de dos vistas | `woocommerce-product-images` | |
| Ficha: título, precio, compra | `woocommerce-product-title` + `woocommerce-product-price` + `woocommerce-product-add-to-cart` | El botón toma el color de tinta de los ajustes globales |
| Ficha: tabla de tallas | Contenedores flex por fila + Encabezado + Editor de texto | Una fila por talla |
| Ficha: costura y cuidado | Contenedor dos columnas + Editor de texto | |
| Migas | `woocommerce-breadcrumb` | |
| Pie | Plantilla de pie del Theme Builder | Enlaces legales incluidos |

## Páginas

Portada y ficha de producto, que son las dos que se diseñan. Carro, pago y cuenta son las páginas
nativas de WooCommerce con sus widgets (`woocommerce-cart`, `woocommerce-checkout-page`,
`woocommerce-my-account`), vestidas con los ajustes globales. Más las no negociables del framework:
aviso legal, privacidad, cookies, términos, 404.

## Procedencia y decisiones abiertas

El diseño es el canvas de `canvas/`, la autoridad. No hay maqueta todavía: `maqueta/` se deriva cuando
la plantilla se prepare para un cliente, con los dos puntos de ruptura que Elementor expresa, 1024 y 767.

**Tipografías fuera de la casa.** Ni Bodoni Moda ni Jost están en `html-mockup/assets/fonts/`. Al
derivar la maqueta hay que añadirlas con su procedencia y licencia, o sustituirlas por un par de la
casa que conserve el papel de cada una. Sin decidir.

**Sin veredicto.** La geometría está medida —108px = 7,5% de margen, cero raíles por dentro, cero
tinta al cristal, cero desborde— pero eso es la mitad medida. Falta la mitad de jueces.
