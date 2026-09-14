---
slug: cadencia
nombre: CADENCIA
tipo: ecommerce
sector: ropa técnica para entrenar al aire libre en frío
objetivo: equipo-por-uso
enfoque: tecnologico
paginas: [portada, categoria, ficha, carro, pago, pedido-recibido, mi-cuenta, la-marca, contacto, condiciones-venta-envios, aviso-legal, privacidad, cookies, 404]
fuentes: [archivo, ibm-plex-mono]
canvas_url: https://claude.ai/code/artifact/b3b0274e-6c0c-45c2-8a3e-5b8243e27032
variantes: {}
html_widgets_max: 0
css_custom_max: 0
---

# CADENCIA · se compra la sesión, no la prenda

## Para qué sirve

Una marca que vende **equipos pensados para un uso medible**: noventa minutos entre −4 y 6 °C,
cuarenta minutos de remo a 18 °C. Cada equipo lleva su duración, su rango de temperatura y su
intensidad, y quien compra elige por la sesión que va a hacer, no por la prenda suelta.

Sirve para ropa técnica, material de montaña, ciclismo o cualquier catálogo donde el uso decide la
compra mejor que la estética.

## Para qué NO sirve

Moda deportiva de estilo de vida, donde se compra por la imagen. Aquí la portada abre con un dato de
temperatura y la primera pantalla no enseña ni un precio.

## ADN — lo que no se toca al adaptarla

- **Abre con una afirmación y una medida, no con una oferta.** La columna derecha de la portada lleva
  cómo se mide una sesión, no una tarjeta de producto. Si las dos tiendas de esta tanda abren con
  nombre + precio + botón, dejan de ser distintas.
- **Tres cifras bajo el titular.** Mínima registrada, altitud y sesiones de prueba, en monoespaciada.
- **El naranja mide, no decora.** Barras de intensidad y subrayado de enlaces. Nunca color de letra.
- **La lista de sesiones es una tabla con barras.** Duración, temperatura, intensidad y precio por
  fila, alineados en columnas.
- **El nav va pegado a la marca.** No finge un centro: un nav centrado por reparto de espacio cae en
  el hueco sobrante, a 50px del eje.

## Qué se cambia para un cliente

| Se cambia | Se conserva |
|---|---|
| Deporte, sesiones y prendas | Que se venda por uso medible |
| Las ocho fotografías | Luz fría, sin neón, sin gimnasio de catálogo |
| Las cifras de prueba | Que haya cifras y sean reales |
| El par tipográfico | Grotesca de trazo firme + monoespaciada para todo dato |
| El acento (re-medido) | Que sólo mida |

## Paleta medida

| Papel | Color | Sobre | Contraste |
|---|---|---|---|
| Suelo | `#141A21` | — | — |
| Tinta tiza | `#E9EEF2` | `#141A21` | 14,99:1 |
| Texto secundario | `#8C97A3` | `#141A21` | 5,89:1 |
| Naranja, sólo interfaz | `#D9542B` | `#141A21` | 4,38:1 — pasa el 3:1 de interfaz, no el 4,5:1 de texto |
| Barra apagada | `#2E3944` / `#3C4956` | `#141A21` | decorativa: sólo delimita el total de diez |

## Mapeo nativo

**Propuesto, no verificado todavía contra el vocabulario nativo introspeccionado.**

| Sección | Elementor (nativo) | Nota |
|---|---|---|
| Cabecera | Theme Builder: contenedor flex con un grupo marca + menú a la izquierda y utilidades + `woocommerce-menu-cart` a la derecha | |
| Tres cifras | Contenedor flex de tres contenedores + dos Encabezado cada uno | |
| Barras de intensidad | Contenedor flex de diez contenedores de 9 × 14 con color de fondo | Nativo pero verboso. Alternativa: una imagen por nivel |
| Lista de sesiones | Loop Grid con Loop Item de una fila | Duración, temperatura e intensidad como campos del producto. **No verificado** qué campo nativo las guarda |
| Equipo como producto | **Producto simple** de WooCommerce con su propio SKU y precio de equipo | Un producto agrupado nativo muestra las prendas con sus precios pero **no aplica descuento de conjunto**: el 214,00 € frente a 234,00 € sólo es nativo si el equipo es un producto propio |
| Ficha: galería | `woocommerce-product-images` | |
| Ficha: compra | `woocommerce-product-price` + `woocommerce-product-add-to-cart` | |
| Tabla de tallas | Contenedores flex por fila | |
| Pie | Theme Builder | |

## Páginas

Las 14 de ecommerce (`paginas-obligatorias.md`), completas. Cinco llevan artboard propio en
`canvas/`, porque su composición es trabajo de diseño: **portada** (`Cadencia.dc.html`), **categoría**
(`Categoria.dc.html`, las nueve sesiones agrupadas por lo que se va a hacer — fondo, sala, remo, alta
montaña — nunca por tipo de prenda), **ficha de equipo** (`CadenciaPieza.dc.html`, Fondo largo en
frío), **la marca** (`LaMarca.dc.html`, el banco de pruebas y quién está detrás) y **contacto**
(`Contacto.dc.html`, formulario más datos del banco). Las nueve restantes son derivadas directamente
en `maqueta/index.html` a partir del sistema — cabecera, pie, tipo, color, tablas y formularios — sin
lámina propia: **carro**, **pago**, **pedido recibido**, **mi cuenta**, **condiciones de venta y
envíos**, **aviso legal**, **privacidad**, **cookies** y **404**.

**Cada sesión del listado enlaza a la única ficha, sin excepción.** Las nueve filas de la categoría y
las tres del extracto de portada llevan todas a `CadenciaPieza.dc.html` / `#ficha`, no sólo «Fondo
largo en frío». La maqueta demuestra que el tipo de página existe y se alcanza desde cualquier
listado; en el sitio del cliente cada equipo tiene su propia ficha, generada por la plantilla de
producto único de WooCommerce. Una tabla donde una fila responde y ocho no se lee como rota, que es
precisamente el defecto que `paginas-obligatorias.md` vino a cerrar.

## Procedencia y decisiones abiertas

El diseño sigue siendo el canvas de `canvas/`, la autoridad — todo cambio empieza ahí y baja después a
la maqueta, nunca al revés. `maqueta/index.html` ya existe: un único fichero autocontenido, con los
dos puntos de ruptura que Elementor expresa, 1024 y 767, y el margen de página en fracción (7,5%) en
vez de píxel fijo para que aguante entre los dos.

**Qué se dibujó y qué se construyó desde los tokens.** Los cinco artboards se tradujeron sección por
sección — mismo orden, mismo texto, mismos tokens — a una escala tipográfica fluida entre 375 y
1440px en vez de los píxeles fijos del lienzo a 1440, porque una maqueta de cliente tiene que
sostenerse en cualquier ancho intermedio y el canvas sólo dibuja uno. Las nueve páginas de sistema no
tienen lámina que traducir: se construyeron con los mismos tokens de color, la misma familia y los
mismos componentes (fila de sesión con barras, listas de definición en monoespaciada, formulario,
tabla de tallas) que ya usaban las cinco dibujadas, para que el carro, el pago o el aviso legal no se
sientan de otra plantilla.

**Tipografías resueltas.** Archivo e IBM Plex Mono ya están en `html-mockup/assets/fonts/`, con su
`OFL.txt` y dadas de alta en `_fonts.php` — IBM Plex Mono con sus dos pesos, 400 y 500. La nota
anterior de esta ficha, que decía que IBM Plex Mono no estaba en el registro, quedó obsoleta: se
comprobó de nuevo antes de escribir esto y el fichero está. La maqueta embebe las dos como `data:`
woff2 entre los marcadores `NM-FONTS`, igual que el resto de la biblioteca.

**El naranja se nombra `--c-border`, no `--c-accent`, en `maqueta/index.html`.** Sus únicos dos usos
son un fondo de 9×14 (la barra encendida) y un `border-bottom` bajo texto que sigue siendo la tinta:
nunca `color:` sobre una letra, comprobado por grep antes de nombrarlo. `color.php` sólo exige 4,5:1 a
un token cuyo nombre contiene «accent», y el naranja mide 4,38:1 — pasa el 3:1 de interfaz que le
corresponde, no el 4,5:1 de texto. Es la misma decisión que BARRO ya registró para su verde esmalte, y
aquí no hace falta renombrar nada: el naranja nunca fue `--c-accent` en el canvas tampoco.

**El objetivo `equipo-por-uso` sigue siendo nuevo.** No existe en ningún recomendador todavía; hay que
darlo de alta cuando exista `recomendador.md`, o reasignar esta plantilla a un objetivo que ya exista.
Esto no cambia con la maqueta y sigue abierto.

**Sin veredicto.** Geometría medida con `alto-contenido.mjs`: 108px = 7,5% de margen, cero raíles por
dentro, cero tinta al cristal, cero desborde, y ahora hay una maqueta completa de 14 páginas sobre la
que correr un barrido — pero no ha corrido. Falta `blind-judges` (juez A contra la biblioteca, juez B
sobre esta maqueta) y `visual-verification` a 430, 768 y 1280 en cada página. Sin las dos, esta
plantilla no se ofrece a un cliente. La apertura de la portada ya pasó por tres rondas de juez hasta
dejar de parecerse a la de `escuadra`; las tres láminas nuevas y la maqueta entera no han pasado
ninguna.
