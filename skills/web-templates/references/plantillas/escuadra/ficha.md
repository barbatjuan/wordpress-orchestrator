---
slug: escuadra
nombre: ESCUADRA
tipo: ecommerce
sector: tienda de hogar de surtido amplio — muebles, cocina, textil, baño, iluminación, almacenaje
objetivo: catalogo-amplio
enfoque: directo
paginas: [portada, ficha]
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

## Mapeo nativo

**Propuesto, no verificado todavía contra el vocabulario nativo introspeccionado.**

| Sección | Elementor (nativo) | Nota |
|---|---|---|
| Cabecera de dos filas | Theme Builder: contenedor de marca y utilidades + contenedor rejilla de seis columnas con el Menú | Cada departamento en su columna de 204 |
| Salón con su factura | Imagen + Loop Grid de seis filas + contenedor con Encabezado de total + Botón | |
| «Añadir el salón al carro» | **Producto agrupado** de WooCommerce con las seis referencias | Nativo: añade varias al carro desde un formulario. **El total 598,00 € no lo calcula WooCommerce**: es texto escrito a mano y se desincroniza si cambia un precio |
| Miniaturas de departamento | Contenedor rejilla de tres + Imagen + Encabezado + etiqueta de recuento | El recuento es texto: WooCommerce no expone el número de productos de una categoría como etiqueta dinámica nativa, **no verificado** |
| Cota | Contenedor de 9px de alto con borde superior de 2px y bordes laterales de 1px | Bordes por lado del contenedor, nativos |
| Catálogo acotado | Loop Grid con Loop Item de una fila | Medidas, bultos y minutos como atributos. **No verificado** su lectura dinámica por fila |
| Ficha: compra | `woocommerce-product-title` + `woocommerce-product-price` + `woocommerce-product-add-to-cart` | |
| Ficha: todo lo que mide | Contenedores flex por fila | |
| Pie | Theme Builder | |

## Páginas

Portada y ficha de artículo. Carro, pago y cuenta nativas. Más aviso legal, privacidad, cookies,
términos y 404. Un catálogo de este tamaño necesita además la página de departamento con filtros,
que no está diseñada.

## Procedencia y decisiones abiertas

Canvas en `canvas/`. Sin maqueta. La plantilla empezó como un taller de doce piezas de contrachapado
y el usuario la reorientó a tienda de hogar completa; la ficha de producto, la Silla Aro, sobrevive de
esa primera versión y encaja en el surtido nuevo.

**Tipografías.** Ni Instrument Sans ni Martian Mono están en `html-mockup/assets/fonts/`.

**El objetivo `catalogo-amplio` es nuevo.** Hay que darlo de alta en el recomendador cuando exista.

**Tres miniaturas son recortes** de fotos que salen enteras más abajo en la misma página. Es la
relación índice → departamento de cualquier tienda de hogar y se deja así a propósito.

**Sin veredicto.** Geometría medida: 108px = 7,5%, cero raíles por dentro, cero tinta al cristal, cero
desborde. La portada pasó cuatro rondas de juez.
