---
slug: noir
nombre: Maison Noir
tipo: ecommerce
sector: perfumería de autor — extractos en partidas numeradas, vendidos por set de muestras antes que por frasco
objetivo: muestra-primero
enfoque: vitrina
paginas: [portada, tienda, ficha, set-descubrimiento, cesta, pago, pedido-recibido, mi-cuenta, la-maison, contacto, condiciones-venta-envios, aviso-legal, privacidad, cookies, 404]
fuentes: [bodoni-moda, jost]
variantes: {}
html_widgets_max: 0
css_custom_max: 0
---

# Maison Noir · que lo pruebe en casa antes de pagar el frasco

## Para qué sirve

Una tienda que vende **algo caro que no se puede elegir por la foto**: un perfume, y por extensión un té de
origen, una cosmética de alta gama o un destilado en miniatura. El visitante no compra el frasco de
285&nbsp;€ la primera vez; compra un **set de descubrimiento** de nueve viales por 35&nbsp;€, lo vive una
semana y **el importe se descuenta íntegro del primer frasco**. La portada vende la casa y la colección, pero
el camino que la Plantilla existe para sostener es set → crédito en la cuenta → frasco. Las tres muestras de
regalo en cada pedido y la sesión olfativa en la boutique son el mismo argumento en otras dos formas.

## Para qué NO sirve

- **La duda es la talla o el material, y se resuelve con una tabla** → `tienda-talla` (MARZO). Aquí no hay
  dato que resuelva la duda: sólo probarlo.
- **La pieza sale una vez y se agota, y quién la hizo es el argumento** → `tienda-lote` (BARRO). MAISON NOIR
  numera partidas, pero cada fórmula se repone; lo que vende es la prueba, no la escasez.
- **La entrega se repite con cadencia** → `suscripcion` (TUESTE). El set es una compra única que lleva a una
  compra suelta grande; si las muestras llegan cada mes, es suscripción.
- **Un catálogo ancho que se navega por categoría** → `catalogo-amplio`. Nueve fórmulas en cuatro familias
  es un catálogo corto que se elige oliendo.

## ADN — lo que no se toca al adaptarla

- **El set de descubrimiento tiene página propia y precio cerrado, y se descuenta del primer frasco.** La
  banda «Prueba antes de decidir» de la portada lo explica en tres pasos y lleva a su ficha; el pie lo enlaza;
  Mi cuenta enseña el crédito pendiente con fecha de caducidad; Condiciones de venta lo regula.
- **El producto sobre fondo oscuro, solo y con aire alrededor.** Frasco de frente con luz lateral cálida,
  nunca a sangre: la foto va enmarcada por un filete dentro de su columna. Un frasco por foto.
- **Champán como brillo, no como campo.** El acento pinta precio, antetítulo, la palabra en itálica del
  titular y el botón principal; nunca un fondo de sección.
- **Serifa de alto contraste con itálica en champán** en el titular, y todo lo demás en palo seco fino en
  versalitas espaciadas.
- **Filetes, no sombras.** Tarjetas, reseñas, resumen y filas de la pirámide olfativa se separan con filete
  de 1&nbsp;px en tono de champán apagado.

## Qué se cambia para un cliente

| Se cambia | Se conserva |
|---|---|
| La casa, las nueve fórmulas, sus notas y precios | Que el set tenga ficha, precio cerrado y se descuente del primer frasco |
| Las catorce fotografías | Un frasco por foto, oscuro, luz lateral, sin sangrar |
| Número de viales, precio y validez del crédito | Que la cifra sea la misma en portada, ficha del set, cuenta y condiciones |
| Champán `#E8C9A0` (re-medido) | Un solo acento cálido reservado a precio, antetítulo y botón |
| Bodoni Moda + Jost | Serifa de alto contraste con itálica + palo seco fino en versalitas |
| La boutique y la sesión olfativa | Que «Reservar cita» aterrice en contacto con su motivo ya en el selector |

## Paleta medida

Medida con `color.php`, no copiada del lienzo. `color.php --maqueta` re-mide los veintiséis pares del
`:root` y sale en `0`.

| Papel | Color | Sobre | Contraste |
|---|---|---|---|
| Suelo / alterno / marco de foto | `#14110F` / `#17130F` / `#1B1715` | — | — |
| Titulares | `#F6F1EA` | los tres suelos | 16,73 / 16,44 / 15,84:1 |
| Texto intenso | `#F2ECE4` | los tres suelos | 16,02 / 15,75 / 15,16:1 |
| Menú, resumen (α .72) | `#B4AFA8` | los tres suelos | 8,63 / 8,48 / 8,17:1 |
| Cuerpo (α .6–.66) | `#A7A29C` | los tres suelos | 7,42 / 7,30 / 7,03:1 |
| Rótulos, notas, pies (α .45–.55), **re-medido** | `#8E8984` (antes .45 ≈ `#78746F`, 3,84:1 sobre el marco) | los tres suelos | 5,43 / 5,33 / 5,14:1 |
| Champán como letra | `#E8C9A0` / al pasar `#F6E4CB` | los tres suelos | 11,90 / 11,70 / 11,27:1 |
| Tinta sobre champán (botón) | `#14110F` | `#E8C9A0` / `#F6E4CB` | 11,90 / 15,11:1 |
| Borde de campo, **re-medido** | `#6D6964` (antes `rgba(242,236,228,.22)` ≈ `#45413E`, 1,86:1) | los tres suelos | 3,45 / 3,39 / 3,27:1 |
| Seleccionado (chip, envío, crédito) | champán / `#B4AFA8` sobre `#2D2720` | — | 9,34 / 6,77:1 (a mano: `--c-sel` no lleva `bg` para que el matcher no lo cruce con todo) |
| Rótulo sobre el hero | `#F2ECE4` bajo velo `rgba(20,17,15,.72)` | peor píxel de `noir-bodegon-extrait` | 9,24:1 (1,90:1 sin velo) |

Filetes decorativos (`#362E26`, `#3A3229`, `#43392F`) y el del botón con borde (`#A89275`, cuyo texto es
champán): separan, no identifican un control. **Todo color y toda familia viven en `:root`**, incluidos los
velos translúcidos (`--c-halo-*`, `--c-trama`, `--c-velo-rotulo`): ninguna regla lleva un literal, para que
la Plantilla hermana en claro sea un cambio de tokens.

## Techo de composición

**Un solo raíl a cualquier ancho.** El lienzo rellena cada banda con `max(22px, min(15vw, 50vw − 420px))`:
contenido de 840&nbsp;px hasta 1200 y 15&nbsp;% después, sin tope. La maqueta conserva la fórmula hasta 1440 y
la detiene ahí: `--pad-x: max(min(15%, calc(50% - 420px)), calc((100% - 1008px) / 2))`. El cruce con el
centrado de 1008&nbsp;px cae **exactamente en 1440**. Bajo 1024, `max(32px, calc(50% - 420px))` (92 a 1024, 32
a 768, continuo con escritorio); bajo 767, 22&nbsp;px como el lienzo. Medido con `medir-geometria.mjs`:
margen dominante 192&nbsp;px a 1280 (15&nbsp;%), 216 a 1440 (15&nbsp;%) y 456 a 1920 (contenido 1008), cero
raíles por dentro del margen y nada al cristal. Es el margen más ancho de la biblioteca junto a `forja`, y
va con el Enfoque: el aire oscuro alrededor de la pieza.

Sin `line-height` en `body` (el lienzo deja `normal`); cada texto corrido lleva el suyo. Todos los
`clamp()` llegan a su valor a 1440 o antes y se detienen. Los bloques estrechos (`.medida`, `.legal`,
`.sistema-cab`) se topan dentro del raíl, pegados a su izquierda.

## Objetivo

**`muestra-primero`, nuevo.** Ningún Objetivo del catálogo nombra una conversión de entrada de pago que se
descuenta de la compra grande. Fronteras escritas en `recomendador.md`: frente a `tienda-talla`, la duda no
la resuelve un dato; frente a `tienda-lote`, la fórmula se repone y el argumento no es la escasez; frente a
`suscripcion`, el set se compra una vez y termina en un frasco suelto.

## Enfoque, eje por eje

Leído de `canvas/Noir.dc.html` y medido en la maqueta. `vitrina` no tenía Plantilla: `noir` es la primera, así
que las columnas comparan con la posición y con la candidata más cercana, `lujo-oscuro` (`barro`).

| Eje | Lo que mide el lienzo | `vitrina` (posición) | `lujo-oscuro` (posición · `barro`) |
|---|---|---|---|
| Escala | H1 `clamp(44px, 6,4vw, 92px)`: 92&nbsp;px sobre cuerpo de 15&nbsp;px; H2 de 46–49 | sí (editorial) | no (monumental) |
| Densidad | Secciones de 81&nbsp;px, huecos de 22–36&nbsp;px | no (monumental) | sí (generosa) |
| Fondo | `#14110F` / `#17130F`, casi negro cálido, texto crema | a medias (tinta, pero cálida) | sí |
| Elevación | Sin sombras; filete de 1&nbsp;px en champán apagado | no (sombra suave) | no (halo) |
| Composición | Hero de dos columnas con la foto enmarcada sin sangrar; rejillas de 3 y 4; un cierre centrado | sí (rejilla estricta) | a medias (centrada sólo al cierre) |
| Acento | Champán en precio, antetítulo, itálica y botón; nunca campo | sí («brillo metálico reservado al precio o al botón») | a medias (metálico, pero `barro` nunca en letra) |
| Chasis | Tarjetas de familia y reseña enmarcadas; pasos y pirámide divididos por filete | no (tarjeta con sombra) | no (capas superpuestas) |
| Ornamento | Regla de 46&nbsp;px antes del antetítulo y un halo radial tenue en el hero | a medias (casi ninguno) | no (ilustración de línea) |

**Recuento:** `vitrina` cuatro (escala, composición, acento y medio de fondo y ornamento); `lujo-oscuro`
tres y medio. **Es `vitrina`, por medio eje**, y deciden las frases: `vitrina` es «una pieza sola sobre
fondo oscuro con aire alrededor, una rejilla ordenada, poco texto y un brillo metálico reservado al precio o
al botón», y «si las referencias llevan la foto a sangre, no es este enfoque» (el lienzo no sangra nada).
`lujo-oscuro` pide «varias piezas en la misma imagen o en banda continua»: aquí cada foto es un frasco.

**Divergencias declaradas:** fondo cálido y no neutro, filete y no sombra, densidad generosa y no
monumental, y **par tipográfico contrario**: `vitrina` pide palo seco de peso alto en titulares y el lienzo
usa Bodoni Moda con itálica. Conviene que quien mantiene `enfoques.md` añada la columna «En `noir`» y quite
«sin plantilla todavía» de `vitrina`; este encargo no toca `enfoques.md`.

**Frente a `marzo`, que usa el mismo par** (Bodoni Moda + Jost): MARZO es claro, sin foto en la colección
(una tabla) y sin acento; NOIR es oscuro, fotografía un frasco por tarjeta y gasta champán en precio y botón.
Un juez de «misma mano» debería mirarlas juntas.

**Dirección de imagen:** el frasco aislado sobre piedra, madera o terciopelo, luz lateral cálida y mucho
negro alrededor; materias de la fórmula junto al frasco; taller y boutique en la misma luz.

## Mapeo nativo

**Propuesto, no verificado todavía contra el vocabulario nativo introspeccionado.** Elementor Pro con
WooCommerce, sin widget HTML y sin CSS a medida; la columna Divi es orientativa y **no validada**. Colores y
las dos familias en los ajustes globales; la entrada `entra` del carrusel es la animación de entrada nativa.

| Sección | Elementor + WooCommerce (nativo) | Divi (no validado) | Nota |
|---|---|---|---|
| Cabecera | Theme Builder: contenedor flex + Encabezado (logotipo en texto) + Menú de navegación + `woocommerce-menu-cart` en modo texto «Cesta (n)» | Cabecera global: Menú + Carrito | Opaca y pegajosa. Bajo 1024, el desplegable del menú es el único conmutador; la cesta sigue visible |
| `hero` | Contenedor rejilla 2 columnas con fondo en degradado radial (halo) · **Carrusel anidado** con tres diapositivas (Divisor 46×1 + Encabezado + Encabezado H1/H2 con la palabra en itálica como segundo Encabezado + Editor de texto), flechas y paginación numérica, autoplay 6,5&nbsp;s · Botón con borde · Imagen 4/5 con borde + Encabezado «Extrait 50 ml» con fondo translúcido en posición absoluta | Slider de ancho completo + Imagen | Una columna bajo 767, foto después del texto |
| `servicios` | Contenedor rejilla de 4 (2 bajo 767), cada celda con borde izquierdo: Encabezado (cifra) + Encabezado | Blurbs ×4 | |
| `seleccion` | Cabecera flex (Encabezado + Botón de texto subrayado) + `woocommerce-products` (3 columnas, 3 productos destacados) con su plantilla de tarjeta: Imagen 3/4 con borde + título + atributo «notas» + precio | Tienda con 3 productos | Bajo 767, tarjeta en fila (imagen 42&nbsp;%) |
| `familias` | Divisor + Encabezados + rejilla de 4 (2 bajo 1024) de contenedores enlazados con borde: Encabezado + Encabezado (recuento) | Blurbs ×4 con borde | Cada familia enlaza a su categoría de producto |
| `taller`, La Maison (`perfumista`, `boutique`) | Contenedor rejilla de 2: Imagen · Encabezados + Editor de texto + Botón (y Lista de datos con borde superior en la boutique) | Fila 2 columnas: Imagen · Texto | |
| `muestras` | Contenedor con fondo alterno: cabecera flex + Lista de 3 pasos (contenedores con borde superior: Encabezado cifra romana + Encabezado + Editor de texto) + Botón con borde a la ficha del set | Blurbs ×3 + Botón | La banda es el ADN: se guarda como plantilla global |
| `opiniones` | Cabecera flex + rejilla de 3 (1 bajo 1024): **Testimonio** con borde, valoración con Estrellas | Testimonios ×3 | En el sitio, reseñas verificadas del plugin de valoraciones de WooCommerce |
| `cartas` | Contenedor rejilla de 2: Encabezados · `Formulario` (Correo + Aceptación obligatoria) con mensaje de éxito en línea | Opt-in de correo | Conectado al proveedor de correo del cliente |
| `encuentra` | Contenedor centrado: Encabezado + Editor de texto + dos Botones | Llamada a la acción | |
| Tienda | Plantilla de archivo de producto: Encabezados + **Filtro de taxonomía** (chips) + ordenación de `woocommerce-products` + rejilla de 3 (2 bajo 767) + paginación nativa | Tienda con filtros | La insignia «300 uds.» es la etiqueta de oferta/atributo de la plantilla de tarjeta |
| Ficha | Plantilla de producto único: `woocommerce-breadcrumb` + `woocommerce-product-images` (galería con 3 miniaturas) + `woocommerce-product-title` + `woocommerce-product-price` + `woocommerce-product-add-to-cart` (variación 50/100&nbsp;ml como botones de atributo y cantidad) + Lista de datos (pirámide olfativa desde atributos) + Lista de iconos en línea | Plantilla de producto de Divi | |
| Set de descubrimiento | La misma plantilla de producto único, con la pirámide sustituida por la lista de fórmulas por familia | Plantilla de producto | **El descuento del set en el primer frasco no es WooCommerce core**: un cupón de un solo uso generado al comprar el set (extensión de cupones o de «store credit») y mostrado en Mi cuenta. Ver nota |
| Cesta | Página Carro con `woocommerce-cart` en dos columnas; las tres muestras, como campo de selección de producto de regalo | Carrito | Las muestras de regalo necesitan una extensión de «free gift» o campos de pedido |
| Pago | Página Finalizar compra con `woocommerce-checkout-page` (dos columnas, cupón, casilla de condiciones nativa) | Checkout | El pago con tarjeta lo pinta la pasarela; la maqueta dibuja los campos |
| Pedido recibido | La pinta WooCommerce en la página de pago, vestida con los ajustes globales | — | A verificar |
| Mi cuenta | `woocommerce-my-account` (pedidos, direcciones, datos) + aviso del crédito del set | Mi cuenta | El aviso del crédito sale de la extensión de «store credit» |
| Contacto | Contenedor rejilla de 2 (1 bajo 1024): `Formulario` (Nombre, Correo, Selección de motivo, Mensaje, Aceptación) con mensaje de éxito en línea · Google Maps tras el consentimiento + Lista de datos | Formulario + Mapa + Texto | La maqueta pinta un mapa estático: un iframe remoto no cabe en un Artifact |
| Condiciones, aviso legal, privacidad, cookies | Página de Elementor: Encabezados + Editor de texto; tabla de cookies en Editor de texto | Texto | Textos ficticios; se reescriben con `wordpress-legal`. La tabla se desplaza dentro de su caja bajo 768 |
| 404 | Plantilla 404 del Theme Builder: Encabezados + Editor de texto + tres Botones | Plantilla 404 | |
| Pie | Plantilla de pie: rejilla de 4 (2 bajo 1024) + fila inferior con borde y Menú legal | Pie global | |

**Techo declarado: cero widgets HTML y cero reglas de CSS a medida.** Lo que WooCommerce core **no** cubre
se declara aquí y no se esconde en CSS: el crédito del set que se descuenta del primer frasco y las tres
muestras de regalo a elegir en la cesta son lógica de pedido, no de maquetación. Antes de prometerlos a un
cliente hay que confirmar la extensión que los sostiene (cupón de un solo uso emitido al comprar el set, o
saldo de tienda; regalo seleccionable en la cesta).

**Quitado del lienzo por no tener expresión nativa:** las rejillas `auto-fit`, la línea dibujada con hueco
sobre fondo de línea, las medidas en `vh` de los rellenos y la monoespaciada de sistema de las etiquetas de
hueco. Se quedan porque sí la tienen: el carrusel con autoplay, el halo radial del hero (degradado radial
de fondo), la cabecera pegajosa y la entrada animada.

## Páginas

Quince: las catorce de ecommerce (`paginas-obligatorias.md`) con el nombre de la casa, más
**set-descubrimiento**, que es la conversión del Objetivo. Del lienzo salen **portada**, **tienda**, **ficha**
(Nuit Absolue), **cesta**, **pago**, **la-maison** y **contacto**; se derivan del sistema **set-descubrimiento**
(con la composición de la ficha), **pedido-recibido**, **mi-cuenta**, **condiciones-venta-envios**,
**aviso-legal**, **privacidad**, **cookies** y **404**.

**Todas las tarjetas de producto llevan a la única ficha dibujada**, Nuit Absolue, como en `corte` y
`marzo`: desajuste de contenido declarado, no enlace roto. **La paginación de la tienda es texto**: el
lienzo dibuja «01 · 02 · Siguiente» con seis de nueve fórmulas y no dibuja la segunda página; en el sitio es
la paginación nativa. **Los filtros y los selectores** (formato, muestras, cantidad) cambian su estado en la
maqueta pero no filtran ni recalculan.

Cero enlaces internos muertos y ningún recurso remoto; correo y teléfono son `mailto:` y `tel:`. Los textos
legales describen una empresa ficticia (Maison Noir Parfums SAS).

## Procedencia y decisiones abiertas

El lienzo llegó como una sola exportación de Claude Design con siete estados. Se copió sin tocar; las
correcciones viven en la maqueta y están listadas en `canvas/MANIFIESTO.md`. Fuentes embebidas de
`_fonts.php`: Bodoni Moda 400 redonda e itálica y Jost 300–500, ya registradas por `marzo`; no se añadió
ninguna. Catorce fotografías generadas el 17 de septiembre de 2026, en `manifiesto-imagenes.md`.

Abiertas:

- **Sin artboards móviles**: 1024 y 767 se diseñaron al derivar.
- **Una sola ficha dibujada** para seis tarjetas, y la segunda página de la tienda sin dibujar.
- **Plantilla hermana en claro** (`noir-claro`) prevista sobre esta maqueta: mismas imágenes y textos
  alternativos, otros tokens.
- **Dominio y teléfono del lienzo** (`maisonnoir.com`, `+33 1 42 60 18 04`) se conservan; pueden coincidir
  con datos reales y se revisan antes de publicar nada.
- **`enfoques.md`** no tiene columna «En `noir`».

**Sin veredicto.** Contraste (`color.php --maqueta`, 0 fallos), velo (`scrim.php`), barrido (quince páginas a
430, 768 y 1280) y geometría (1280, 1440, 1920) medidos; faltan `blind-judges` —con el juez A mirando contra
`barro` y `marzo`— y `visual-verification`. Sin los dos, esta Plantilla no se ofrece a un cliente.
