---
slug: corte
nombre: CORTE NUEVE
tipo: ecommerce
sector: sastrería vaquera a medida — pantalones y prendas en denim, sarga y chambray, tallados por centímetros publicados
objetivo: prenda-a-medida
enfoque: materia
paginas: [portada, categoria, ficha, carro, pago, pedido-recibido, mi-cuenta, la-marca, contacto, condiciones-venta-envios, aviso-legal, privacidad, cookies, 404]
fuentes: [dm-sans, inter-tight]
variantes: {}
html_widgets_max: 0
css_custom_max: 0
---

# CORTE NUEVE · dinos tres medidas y te decimos la talla

## Para qué sirve

Un taller que corta **prendas que no tienen talla de percha**: el visitante da tres medidas —
cintura, cadera, entrepierna — y el sitio le dice a qué talla del modelo corresponde, antes de que
compre y no después de que devuelva. Sirve para sastrería, camisería o calzado de horma: cualquier
negocio del catálogo `prenda-a-medida` de `recomendador.md` donde la devolución por talla es
justo el coste que el sitio existe para evitar.

**No es `tienda-talla` (MARZO).** MARZO vende tirada corta con talla de percha (S/M/L o numérica) y
existencias que se agotan por talla: el visitante filtra y compara. CORTE no tiene «talla agotada»
en el sentido de MARZO — cada modelo se corta en trece tallas de la 28 a la 40 o de la S a la XXL —
lo que decide la compra es si la talla calculada a partir de las tres medidas es la correcta, no si
queda unidad. Confundir los dos Objetivos sería ofrecer esta plantilla a un cliente que en realidad
necesita un registro de existencias por talla, que es exactamente el problema que resuelve MARZO y
no éste.

## Para qué NO sirve

- **Tirada corta con existencias por talla y sin cálculo** → `tienda-talla` (MARZO). Si la talla del
  cliente ya se conoce por convención (S/M/L) y lo único que varía es el stock, sobra la
  calculadora entera que es el ADN de esta plantilla.
- **Precio que no se sabe hasta configurar** → `a-medida` (sin plantilla, ruta a medida). En CORTE
  el precio de cada modelo es fijo y está publicado (89,00&nbsp;€, 139,00&nbsp;€…); lo único que la
  medida decide es la talla, nunca el precio. Un negocio donde el precio cambia según lo que el
  cliente configura (cortinas, encimeras, mobiliario a medida) no es este Objetivo.
- **Catálogo ancho por departamentos** → `catalogo-amplio` (ESCUADRA). Seis modelos con trece tallas
  cada uno es un catálogo corto y profundo, no un surtido ancho por categorías.

## ADN — lo que no se toca al adaptarla

- **La calculadora de talla vive en la ficha, antes del botón de compra, nunca a un clic de
  distancia.** `Ficha.dc.html` la pone entre el precio y la tabla de tallas, con resultado
  inmediato («Talla 32», con la diferencia en centímetros explicada) — la misma posición que ya
  fijó `Corte.dc.html` en su hero.
- **El precio nunca lo decide la medida; la talla sí.** Cada modelo tiene un precio cerrado y
  publicado. Lo que las tres medidas cambian es qué fila de la tabla de tallas corresponde, nunca
  cuánto se cobra — la frontera exacta que separa `prenda-a-medida` de `a-medida` en
  `recomendador.md`.
- **Cada modelo publica su propia tabla de tallas, medida en plano sobre la prenda.** Nunca «talla
  M»: siempre centímetros de cintura, cadera, tiro y largo, con el encogimiento ya descontado —
  `Corte.dc.html` lo dice explícitamente («No hay «talla M». Hay 81 centímetros de cintura») y
  `Ficha.dc.html` lo repite con la tabla del Recto&nbsp;01.
- **El fallo de talla lo paga el taller, no el cliente.** Primer cambio gratis con recogida
  incluida, 30 días para decidir, y el ajuste de largo de pierna va incluido en el precio y se
  resuelve en 48&nbsp;h en el taller de Béjar — declarado en la portada, repetido en `Ficha.dc.html`
  y en `condiciones-venta-envios`, nunca sólo en la letra pequeña.
- **La fotografía de producto se repite entre portada, categoría y ficha; la de ambiente, no.**
  `corte-v1` aparece en la portada y en la ficha porque es el mismo Recto&nbsp;01, y las tres fotos
  de cuerpo (`corte-cuerpo1/2/3`) documentan el mismo modelo en tres tallas — es el producto
  repitiéndose, la distinción que hace `defectos-de-derivacion.md` § «Canvas y artboards». Con
  nueve fotografías para catorce páginas, una lámina que pediría una foto de taller o de mostrador
  que no existe en el manifiesto —`LaMarca`, `Contacto`— se resuelve con cifra, tipo y tabla en vez
  de inventar una imagen.

## Qué se cambia para un cliente

| Se cambia | Se conserva |
|---|---|
| La marca, el taller, los seis modelos y sus tejidos | Que la calculadora de tres medidas esté en la ficha, antes de comprar |
| Las nueve fotografías | Que el producto se repita entre páginas y la foto de ambiente no se invente |
| Las tallas y su tabla por modelo (cm reales) | Que cada modelo tenga su propia tabla medida en plano, nunca una talla de percha genérica |
| El par tipográfico, si el cliente lo pide | DM Sans (o un palo seco de alto contraste similar) + Inter Tight (o un grotesco de texto similar) |
| La cifra de devoluciones del encabezado | Que sea un dato medido y no una frase de marca — se quita si el negocio no la tiene, no se inventa |
| El precio de cada modelo | Que la medida decida sólo la talla, nunca el precio |

## Paleta medida

Medida con `color.php --contraste`, no copiada del lienzo.

| Papel | Color | Sobre | Contraste |
|---|---|---|---|
| Suelo | `#EDEAE4` | — | — |
| Suelo alterno (bandas de sección) | `#DFDAD1` | — | — |
| Tinta | `#1C1A17` | `#EDEAE4` / `#DFDAD1` | 14,46:1 / 12,47:1 |
| Texto | `#3B3730` | `#EDEAE4` / `#DFDAD1` | 9,85:1 / 8,50:1 |
| Texto apagado | `#5C574E` | `#EDEAE4` / `#DFDAD1` | 5,97:1 / 5,15:1 |
| Acento (enlaces, cifras, campo teñido) | `#2C3E7A` | `#EDEAE4` / `#DFDAD1` | 8,42:1 / 7,26:1 |
| Texto sobre el acento (botón, panel, banda) | `#FFFFFF` | `#2C3E7A` | 10,11:1 |
| Acento sobre blanco (`.btn-white`) | `#2C3E7A` | `#FFFFFF` | 10,11:1 |
| Etiqueta sobre el acento (`--c-panel-label`) | `#C3CBE4` | `#2C3E7A` | 6,25:1 |
| Cifra/cuerpo sobre el acento (`--c-panel-value`) | `#DCE1F0` | `#2C3E7A` | 7,74:1 |
| Párrafo de banda sobre el acento (`--c-panel-copy`) | `#D8DEEF` | `#2C3E7A` | 7,52:1 |
| Acento oscurecido sobre `--c-panel-value` (`.btn-white:hover`) | `#22305F` | `#DCE1F0` | 9,70:1 |
| Valor pendiente/placeholder (`--c-pending`), **re-medido** | `#6A655C` (antes `#8A857A`, 3,06:1 — bajo AA) | `#EDEAE4` | 4,82:1 |

`color.php --maqueta` re-mide seis pares directamente del `:root` de `maqueta/index.html` y sale en
`0`. Los otros diez de la tabla de arriba no entran en esa corrida por dos motivos distintos, los dos
comprobados a mano, nunca asumidos:

- **Tokens que el matcher no clasifica.** `--c-ink`, `--c-muted`, `--c-white` y `--c-pending` no
  contienen `bg`, `text`, `accent` ni `border` en su nombre, así que `color_token_role()` los deja
  fuera de la matriz automática — el mismo hueco de cobertura que ya documentaron
  `tueste/ficha.md` para `--c-footer-label` y `cadencia/ficha.md` para sus siete colores derivados.
- **Un token que el matcher clasificaba MAL, no que ignoraba.** La primera versión de la maqueta
  llamaba a las tres tintas que pintan texto sobre el panel de acento `--c-on-accent-label`,
  `--c-on-accent-body` y `--c-on-accent-copy`. Como el nombre SÍ contiene `accent`,
  `color_root_pairs()` las trataba como un color de acento genérico más y las cruzaba contra
  `--c-bg`/`--c-bg-alt` — pares que ningún CSS de esta maqueta forma jamás, porque las tres sólo
  pintan texto sobre `--c-accent`. Eso dio seis FAIL de matriz (`--c-on-accent-label`,
  `--c-on-accent-body` y `--c-on-accent-copy`, cada uno contra `--c-bg` y `--c-bg-alt`, de 1,03:1 a
  1,35:1). Es la distinción que pide `mockup-guide.md`: no era una medición discrepante del
  render, era un cruce que el propio `:root` nunca declaraba usar junto — la misma forma del defecto
  que ya pagó `tueste/ficha.md` con `--c-footer-bg`/`--c-footer-text`. Se corrigió renombrando los
  tres tokens a `--c-panel-label`, `--c-panel-value` y `--c-panel-copy` — mismos hex, mismo papel,
  fuera del rango de substrings que dispara el matcher — nunca ajustando `color.php` ni suavizando
  un contraste que de verdad fallaba: los tres pasan de sobra contra su fondo real, `--c-accent`
  (6,25:1 a 7,74:1, en la tabla de arriba).

**Un color sí falló de verdad, y se corrigió en el lienzo y en la maqueta.** `Corte.dc.html`, la
portada que llegó ya dibujada, usa `#8A857A` (línea 49) para el valor de entrepierna que el
visitante todavía no ha dado — una lectura visual legítima, «esto aún no se ha rellenado» — pero
mide 3,06:1 sobre `#EDEAE4`, bajo el 4,5:1 de AA. No se ha tocado la portada porque dibujarla no
era parte de este encargo (igual que `tueste/canvas/MANIFIESTO.md` no tocó la suya), pero
`Ficha.dc.html`, que sí es de esta entrega, reutilizaba el mismo color para el mismo papel en la
calculadora — «entrepierna, en cm» con un `placeholder` de «80» — y ahí sí se ha corregido a
`#6A655C`, 4,82:1, el mismo matiz cálido pero más oscuro. La regla que ya pagaron `aranda` y
`lumiere` con sus propios colores: se cambia el color, nunca el nombre del papel, y se cambia en
las dos superficies que lo usan — el lienzo (`Ficha.dc.html`) y la maqueta — nunca sólo en una.

**Margen de página: 96px, es decir 6,667&nbsp;%, no el 7,5&nbsp;% del estándar de la casa.**
`Corte.dc.html` usa `96px` como relleno lateral en quince apariciones de la cadena contadas por
línea (`rg -o '96px' Corte.dc.html | wc -l` → 15: cabecera, hero, franja de medida, «tres cuerpos»,
la tabla completa, «seis prendas», la banda de garantía y el pie), nunca `108px`. Es más estrecho
que el 7,5&nbsp;% de la casa y que el 10&nbsp;% de `tueste`, y coincide en aritmética (no en
Enfoque) con el 6,667&nbsp;% de `lumiere`. Las cuatro láminas nuevas y la maqueta lo expresan como
fracción (`--page-margin:clamp(20px, 6.667vw, 96px)`), nunca en píxel fijo.

## Mapeo nativo

**Propuesto, no verificado todavía contra el vocabulario nativo introspeccionado
(`vocabulario-nativo.md`, sin generar aún).** Elementor con WooCommerce, sin un solo widget HTML y
sin CSS a medida.

| Sección | Elementor (nativo) | Nota |
|---|---|---|
| Cabecera | Theme Builder: contenedor flex + Logotipo (texto) + Menú de navegación + `woocommerce-menu-cart` | La cifra de devoluciones es texto dinámico o estático, según si el cliente la mide de verdad |
| Hero + calculadora de talla | Contenedor de texto (Encabezado + Editor de texto) + panel de resultado superpuesto (offset nativo, no CSS a medida) | **No verificado**: los tres campos numéricos y el resultado («Talla 32», con la diferencia en cm) son cálculo en vivo, terreno de un formulario con lógica condicional — ver nota debajo de la tabla |
| Tres cuerpos, mismo modelo | Contenedor rejilla de 3 columnas: Imagen + lista de definición (Encabezado + filas clave-valor) | Sin producto de WooCommerce detrás — contenido editorial, no catálogo |
| Tabla de tallas completa | Contenedor de tabla nativo o `woocommerce-product-data-tabs` con una pestaña de tabla | Cada modelo lleva la suya; en el sitio del cliente vive en la pestaña de datos de su propia ficha |
| Seis prendas (listado de portada) | `woocommerce-products` en disposición de lista | Cada fila enlaza a su `woocommerce-product-add-to-cart` |
| Banda de garantía | Contenedor flex de dos columnas: contenedor de texto (+ icono de línea SVG nativo) + lista de filas | El icono SVG se sirve como Icono nativo de Elementor |
| Categoría: seis tarjetas | Plantilla de archivo de producto del Theme Builder: `woocommerce-breadcrumb` + Loop Grid de 3 columnas (Imagen + Encabezado + Encabezado de precio + Botón) | Cada tarjeta enlaza a su propia ficha en el sitio del cliente; en la maqueta, las seis enlazan a la única ficha dibujada — ver § Procedencia |
| Ficha: compra | `woocommerce-breadcrumb` + `woocommerce-product-images` + `woocommerce-product-title` + `woocommerce-product-price` + `woocommerce-product-add-to-cart` | |
| Ficha: calculadora de talla | Igual que la de portada | **No verificado**, misma nota — es el hueco más grande de esta tabla |
| Ficha: tabla de tallas + garantía | `woocommerce-product-data-tabs` para la tabla; contenedor de filas clave-valor para la garantía | |
| La marca: cifras + proceso + tabla de tejidos | Contenedor rejilla de 3 columnas (Encabezado + Editor de texto) × 2 + contenedor de tabla nativo | Página de Elementor, sin widget de WooCommerce |
| Contacto: formulario + datos + preguntas | `Formulario` nativo de Elementor Pro (Nombre, Correo, Motivo [Select], Mensaje, Casilla de consentimiento) + contenedor de filas clave-valor + rejilla de 3 para las preguntas | La casilla de consentimiento es el campo nativo `Aceptación` |
| Carro / Pago | Páginas de WooCommerce, `woocommerce-cart` / `woocommerce-checkout-page` | La talla calculada viaja como atributo de variación del producto |
| Pedido recibido | Sin widget propio, vestida con los ajustes globales — a verificar | |
| Mi cuenta | `woocommerce-my-account`, con un bloque «Tus medidas guardadas» que **no tiene equivalente nativo** | Ver nota — para que un cliente que vuelve no vuelva a medirse |
| Pie | Theme Builder | Aviso legal, Privacidad, Cookies y Condiciones de venta y envíos enlazan a sus páginas |

**Lo que WooCommerce core no cubre, dicho sin adornar.** WooCommerce, tal y como lo introspecciona
`skills/woocommerce/`, resuelve la variación de producto por **atributos que el cliente elige de una
lista** (un desplegable de tallas S/M/L, por ejemplo) — no por **un cálculo derivado de tres números
que el cliente escribe**. Convertir «cintura 81, cadera 103, entrepierna 80» en «Talla 32» y
preseleccionar esa variación es lógica condicional que WooCommerce core no trae: hace falta un
plugin de formularios con reglas (o JavaScript a medida, que rompe el techo nativo cero) que lea los
tres campos, los cruce contra la tabla de tallas del producto y marque la variación correspondiente
antes del botón «Añadir al carro». Lo mismo aplica al bloque de «Tus medidas guardadas» de mi
cuenta: `woocommerce-my-account` lista pedidos y direcciones, no un perfil de medidas reutilizable
entre compras. La maqueta dibuja los dos porque son el argumento de venta del negocio y
`paginas-obligatorias.md` los pide en texto plano; construirlos de verdad en un sitio de cliente
empieza por confirmar qué campo personalizado o extensión puede sostener ese cálculo antes de
prometer un resultado que Elementor no tiene dónde enganchar — la misma honestidad que
`tueste/ficha.md` ya declaró para el panel de pausar/cancelar de una suscripción.

## Páginas

Las 14 de `paginas-obligatorias.md` § Ecommerce. Cinco tienen artboard propio en el lienzo —
portada, catálogo, ficha de producto (Recto&nbsp;01), el taller y contacto— porque su composición
es trabajo de diseño. Las nueve restantes se derivan del sistema de la plantilla sin lámina propia:
carro, pago, pedido recibido, mi cuenta, condiciones de venta y envíos, aviso legal, privacidad,
cookies y 404.

**Por qué el Recto&nbsp;01 y no otro modelo.** De los seis que lista `Categoria.dc.html`, el
Recto&nbsp;01 es el que la propia portada ya nombra y mide tres veces — en el hero de la
calculadora, en la tabla completa y en la primera fila de «seis prendas»— y es además el modelo que
llevan las tres fotografías de cuerpo (`corte-cuerpo1/2/3`). Dibujar su ficha no añade un séptimo
dato que sostener: usa el que ya existía y las fotos que ya lo demuestran en tres tallas. Es la
misma lógica que llevó a `tueste` a dibujar Huila y a `escuadra` a dibujar Cocina.

**Las seis tarjetas del catálogo enlazan todas a la única ficha dibujada.** Sólo el Recto&nbsp;01
tiene lámina de detalle propia; las seis tarjetas de `Categoria.dc.html` llevan a ella, siguiendo la
misma convención que `marzo/canvas/Categoria.dc.html` y `tueste/canvas/Categoria.dc.html` ya usan
para su propia única ficha. Es un desajuste de contenido declarado, no un enlace roto: en el sitio
del cliente cada modelo tiene la suya.

**Mi cuenta guarda medidas, no sólo pedidos.** En una tienda de prenda a medida, volver a escribir
las mismas tres medidas en cada compra es fricción que el propio Objetivo existe para evitar —
`Categoria.dc.html` lo anuncia («¿No sabes tu talla en centímetros? La ficha la calcula») — así que
la página derivada de la maqueta añade un bloque «Tus medidas guardadas» (cintura, cadera,
entrepierna, con fecha de la última actualización) antes de la lista de pedidos, no un genérico
«mis pedidos» vacío.

**Las páginas legales de la maqueta describen una empresa ficticia.** En un encargo se reescriben
enteras con los datos reales del cliente con `wordpress-legal`; nunca se publican tal cual. Las
condiciones del corte a medida —qué cambia la devolución cuando la prenda se ajustó a una medida
dada por el cliente, cómo funciona el desistimiento cuando el bajo ya se ha cortado— viven en
«condiciones de venta y envíos», que es donde `paginas-obligatorias.md` las espera para una tienda
que vende por precio cerrado.

## Procedencia y decisiones abiertas

`Corte.dc.html` llegó a este repositorio ya dibujado, con las nueve fotografías y su manifiesto, sin
`canvas.json` ni el resto de páginas — el mismo origen («seis portadas», commit `b153c6a`) que
`aranda`, `lumiere`, `terrazza`, `bajura` y `tueste`. **No se dispone de su URL de artifact** y
`canvas_url` se deja fuera del frontmatter en vez de inventarse — ver `canvas/MANIFIESTO.md` §
«De dónde sale» para el detalle completo.

Las cuatro láminas que faltaban —`Categoria.dc.html`, `Ficha.dc.html`, `LaMarca.dc.html` y
`Contacto.dc.html`— se escribieron en este encargo, en el idioma exacto de la portada (1440 de
ancho, suelo en `html, body` y en `[data-suelo]`, mismo margen de 96px medido, mismos tokens), con
el mismo método con el que `aranda`, `lumiere`, `marzo`, `barro`, `escuadra`, `cadencia` y `tueste`
completaron su propio juego de páginas. Sus alturas están medidas con `alto-contenido.mjs`, no
tecleadas: 1978, 2421, 2040 y 1325 — ver `canvas/MANIFIESTO.md`. La portada se re-midió en la misma
pasada: 4892.

`maqueta/index.html` se deriva de las cinco láminas para las cinco páginas de contenido y del
sistema que ellas fijan para las nueve de sistema, con los dos puntos de ruptura de la casa (1024 y
767) y el margen de página como fracción (6,667&nbsp;%, medida — ver más arriba), nunca en píxel
fijo. Fuentes embebidas como `data:` woff2 leídas de `skills/html-mockup/assets/fonts/_fonts.php`,
nunca tecleadas.

**El Enfoque se corrigió de `vitrina` a `materia`, y la plantilla no se tocó.** CORTE entró en
`recomendador.md` con `vitrina` — una pieza sola sobre fondo oscuro con aire alrededor, «la sala a
oscuras y el objeto iluminado» — y el lienzo lo desmiente en seis de ocho ejes, contados con cita:

| Eje | Pide `vitrina` | Hace `Corte.dc.html` |
|---|---|---|
| Fondo | tinta neutra (sala a oscuras) | `#EDEAE4`/`#DFDAD1`, cálido claro, líneas 12, 20, 37, 66, 372 — nueve bandas, ninguna oscura |
| Elevación | sombra suave | `rg -c 'box-shadow' Corte.dc.html` → 0 |
| Acento | metálico | `#2C3E7A` plano, sin `gradient` (`rg -c 'gradient'` → 0), en paneles de campo teñido (línea 52, 343) |
| Chasis | tarjeta con sombra al levantarse | filas divididas por `border-top` (líneas 234-340), sin tarjeta ni sombra |
| Densidad | monumental | tabla de 9 filas (119-223) y 6 filas de producto con 3 columnas de dato cada una — estándar |
| Ornamento | ninguno | icono de línea activo junto a la banda de garantía (347-350) |

Dos ejes sí coinciden con `vitrina` — Escala (titular de alto contraste, línea 34) y Composición
(rejilla de tres columnas, línea 72, y la propia tabla) — pero esos mismos dos ejes también los
resuelve `materia`, así que no discriminan. Frente a esos seis ejes contradichos, la misma lectura
puntúa 6 de 8 contra la columna `materia` de `enfoques.md`: Densidad (estándar), Fondo (cálido
claro, coincidencia literal con «blanco roto cálido con un alterno»), Elevación (filete —
`border-bottom`/`border-top` en la tabla, en las filas de producto, en las filas de datos de cada
cuerpo), Composición (rejilla estricta), Acento (campo teñido, no reservado a existencias como en
MARZO pero sí un color sólido que tiñe paneles enteros) y Chasis (enmarcado por filete, el mismo
registro de fichas técnicas que dibuja MARZO). Es la misma proporción — seis de ocho — que corrigió
el Enfoque de TUESTE de `institucional` a `editorial`.

**Se corrigió la etiqueta, no el diseño.** `enfoques.md` dice que manda la plantilla cuando las dos
difieren, y el recomendador enruta por Enfoque: con `vitrina` ofrecería CORTE a una joyería o una
galería que pide una sala a oscuras, y lo que el lienzo de verdad dibuja es un taller con la luz
encendida y una tabla en la pared. `recomendador.md`, `enfoques.md` y `_indice.md` **no se han
tocado en este encargo** — otro agente trabaja en el mismo árbol — así que la fila de
`recomendador.md` sigue diciendo `vitrina` hasta que alguien la corrija con esta misma evidencia.
Las filas exactas a escribir están al final del informe de esta entrega, fuera de este repositorio.

**`materia` tampoco encaja limpio, y se declara.** La Escala: `materia` pide «display fino en el
titular, robusto en la cifra» (Bodoni Moda con su eje óptico en MARZO) y el H1 de CORTE es un DM
Sans en negrita de 74px — alto contraste, no fino. Y el Ornamento: `enfoques.md` deja «textura» sin
fijar en la ficha de MARZO, así que el icono de línea de CORTE no coincide ni contradice ese eje;
queda como dato sin comparación posible. Es la lectura más cercana del catálogo, no una casilla
hecha a medida.

**Compartir Enfoque con MARZO no repite la plantilla, pero exige distancia deliberada**, y esto es
lo que se hizo para que CORTE no se lea como MARZO:

- **Par tipográfico distinto de raíz.** MARZO usa Bodoni Moda (serifa de alto contraste con eje
  óptico) + Jost; CORTE usa DM Sans (palo seco) + Inter Tight. Ningún titular de CORTE tiene
  serifa.
- **Mecánica central distinta.** MARZO resuelve con una tabla de registro y estado de existencias;
  CORTE resuelve con una calculadora de tres medidas que responde con una talla — es la pieza que
  no existe en MARZO y es el motivo por el que esta plantilla existe.
- **La fotografía de producto se conserva, no se retira.** El ADN de MARZO es explícito: «la
  colección es una tabla, no una rejilla. Sin miniaturas». CORTE hace lo contrario a propósito:
  `Categoria.dc.html` es una rejilla de seis tarjetas CON fotografía, porque el rol «card 4:3» de
  `manifiesto-imagenes.md` para `corte-v1`…`v6` así lo pide y porque una prenda que se compra por
  medida también se compra mirándola.
- **El acento no es el mismo gesto.** MARZO declara «no hay color de acento»: su único color
  (`#8C4A3F`) marca sólo existencias y nunca aparece en un botón o un panel. El azul de CORTE
  (`#2C3E7A`) SÍ es un acento activo — tiñe paneles de resultado, la banda de garantía, una fila de
  tabla — y su papel es decir «esto es lo que se ha calculado para ti», no marcar stock.
- **La garantía es de talla, no de lote.** MARZO no promete cambio de talla porque no calcula
  ninguna; CORTE construye media plantilla alrededor de esa promesa (banda de garantía en portada,
  bloque de precios en la ficha, condiciones de venta dedicadas).

**Sin veredicto.** Geometría medida en las cinco láminas — 96px = 6,667&nbsp;%, cero raíles por
dentro, cero tinta al cristal, cero desborde por construcción — y `color.php --maqueta` en cero. La
portada y las cuatro láminas nuevas no han pasado `blind-judges` ni el barrido a 430/768/1280 de
`visual-verification`: `_indice.md` debe seguir marcando CORTE «sin veredicto» y no se ofrece a un
cliente. La geometría es correcta por construcción y por las comprobaciones automatizadas
(`empaquetar.php`, `color.php`, la cadena de tests); nadie la ha mirado todavía.
