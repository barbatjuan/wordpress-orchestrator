---
slug: bajura
nombre: BAJURA
tipo: ecommerce
sector: pescadería online — pescado y marisco fresco de la subasta diaria de una lonja de bajura
objetivo: subasta-diaria
enfoque: tecnologico
paginas: [portada, categoria, ficha, carro, pago, pedido-recibido, mi-cuenta, la-marca, contacto, condiciones-venta-envios, aviso-legal, privacidad, cookies, 404]
fuentes: [archivo, source-sans-3]
variantes: {}
html_widgets_max: 0
css_custom_max: 0
---

# BAJURA · la subasta de hoy, lo que queda y a qué hora cierra

## Para qué sirve

Un pescado y marisco que se vende **desde la lonja, el mismo día en que se subasta**: la captura de
esta mañana, pesada en la propia lonja, con un cierre de pedidos a una hora fija y sin stock que pase
de un día para otro. Sirve para cualquier negocio cuyo catálogo entero cambia cada jornada y se agota
—lonja, pescado, marisco, mercado, producto fresco de temporada— el catálogo de `recomendador.md` bajo
`subasta-diaria`. La página que en otras tiendas sería «categoría por departamento» aquí es «todo lo
que hay hoy, ordenado por lo que se acaba antes»: no hay filtro por especie porque mañana la lista
entera es otra, y filtrar algo que dura un día no ahorra tiempo, lo hace perder.

## Para qué NO sirve

Un catálogo que se repone y no cambia de un día para otro —congelado, conservas, una pescadería con
referencia fija todo el año— es `catalogo-amplio`, no `subasta-diaria`: ahí sí tiene sentido navegar
por departamento, porque lo que hay hoy es lo mismo que habrá la semana que viene. Tampoco sirve para
piezas que se agotan por LOTE y no se renuevan —un taller de cerámica, una tirada de ropa— porque ahí
el argumento es la procedencia de ese lote concreto (`tienda-lote`, que encarna `barro`), no la hora a
la que cierra hoy. La frontera que fija `recomendador.md`: el género no se repone, se renueva.

## ADN — lo que no se toca al adaptarla

- **Lo que queda y la hora de cierre están siempre a la vista, nunca a un clic.** La franja
  «¿Llegamos a tu casa?» con su nota «Pides antes de las 17:00, lo tienes mañana» es global, no sólo
  de portada, y toda página de venta —portada, categoría, ficha— repite el cierre de las 17:00 y los
  kilos que quedan. Es la misma decisión que TUESTE tomó con la cadencia de su suscripción, trasladada
  al dato que de verdad importa aquí: la hora, no la cuota.
- **La categoría se organiza por urgencia de cierre, no por especie.** `Categoria.dc.html` y su
  derivada en la maqueta dividen la lista en «Queda poco» y «Queda para todo el día», nunca en
  pestañas de pescado blanco / marisco / azul. Añadir un filtro por departamento sería resolver el
  Objetivo equivocado: el que importa aquí es el tiempo, no la taxonomía.
- **El peso que se cobra es el real de la pieza, nunca el tramo redondeado.** «El peso es aproximado.
  El precio, exacto.» no es un eslogan: el margen de ±10&nbsp;% sobre el tramo, con devolución de la
  diferencia el mismo día si la pieza sale por debajo, aparece en la portada, en la ficha y en
  condiciones de venta con las mismas cifras.
- **La cadena de frío se demuestra, no se promete.** Hielo en gel (nunca cubitos), termómetro dentro
  de la caja y devolución del pedido entero si llega por encima de 4&nbsp;°C son un hecho verificable
  que se repite en portada, la lonja y condiciones de venta — nunca una frase de marca sin cifra
  detrás.
- **La fotografía es dato, no ambiente.** Cada una de las siete fotografías lleva una etiqueta
  funcional (la hora, el peso, el gesto) y ninguna se repite entre láminas salvo el producto mismo
  (`bajura-pieza.webp`, reutilizado en la ficha porque es la lubina que se vende, no una foto de
  ambiente rellenando presupuesto) — la misma distinción que ya fija
  `defectos-de-derivacion.md` § «Canvas y artboards».

## Qué se cambia para un cliente

| Se cambia | Se conserva |
|---|---|
| La especie, el arte de pesca, el precio y los kilos de cada fila | Que la lista se organice por urgencia de cierre, no por categoría |
| La hora de subasta, la hora de corte y las zonas de reparto | Que ambas horas estén siempre a la vista, en la franja global y en cada página que vende |
| El barco, el patrón, el puerto y la hora de la ficha destacada | Que la ficha diga barco, puerto, hora de llegada y qué pasa con un pedido después del corte |
| Las siete fotografías | El presupuesto ajustado: una página que pediría una octava se resuelve con tipo, filete y tabla |
| El nombre, el registro sanitario y los datos legales | Que la cadena de frío y el peso real se demuestren con cifra, no con adjetivo |
| El par tipográfico, si el cliente lo pide | Archivo (o una grotesca de peso alto similar) + Source Sans 3 (o un palo seco de texto similar) |

## Paleta medida

Medida con `skills/html-mockup/assets/herramientas/color.php --contraste`, no copiada del lienzo.
BAJURA es la tercera plantilla de la biblioteca sobre un suelo casi negro, después de BARRO y
TERRAZZA — el lugar más fácil para fallar un umbral, según advierte el propio encargo.

| Papel | Color | Sobre | Contraste |
|---|---|---|---|
| Suelo (`--c-bg`) | `#0F1714` | — | — |
| Suelo alterno (`--c-bg-alt`) | `#18211D` | — | — |
| Texto (`--c-text`) | `#C7D4CC` | `#0F1714` / `#18211D` | 11,90:1 / 10,77:1 |
| Tinta (`--c-ink`), medido a mano | `#E9F1EC` | `#0F1714` / `#18211D` | 15,84:1 / 14,33:1 |
| Texto apagado (`--c-muted`), medido a mano | `#8FA398` | `#0F1714` / `#18211D` | 6,82:1 / 6,17:1 |
| Texto de fila agotada (`--c-dim`), **re-medido** | `#7E9088` (antes `#6F8078`, 4,37:1 sobre el suelo — bajo AA) | `#0F1714` / `#18211D` | 5,41:1 / 4,89:1 |
| Acento (`--c-accent`) | `#FF8A3D` | `#0F1714` / `#18211D` | 7,77:1 / 7,03:1 |
| Acento, hover (`--c-accent-hover`) | `#FFA666` | `#0F1714` / `#18211D` | 9,45:1 / 8,55:1 |
| Texto sobre acento (`--c-on-accent`), medido a mano | `#0F1714` (= `--c-bg`) | `#FF8A3D` / `#FFA666` | 7,77:1 / 9,45:1 |

`color.php --maqueta` re-mide los cinco pares cuyo nombre contiene `bg`, `text` o `accent` y saca
`OK` en `--c-text` y en los dos `--c-accent*` — cuatro filas en verde. **`--c-ink`, `--c-muted` y
`--c-dim` no entran en esa corrida** porque `color_token_role()` sólo clasifica un token por si su
nombre contiene `bg`, `text`/`accent` o `border`; ninguno de los tres lo lleva, el mismo hueco de
cobertura que ya documentaron `tueste/ficha.md` y `cadencia/ficha.md` para sus propios colores
derivados. Se han medido a mano y están arriba.

**`--c-on-accent` sí entra en la corrida automática —su nombre contiene «accent»— y sale `FAIL` dos
veces, contra `--c-bg` y `--c-bg-alt`: 1,00:1 y 1,11:1.** Es un artefacto de la matriz, no un color
que falle de verdad: `--c-on-accent` (`#0F1714`) nunca se pinta sobre el suelo. Se busca por
`grep -c 'c-on-accent'` en la maqueta y aparece siete veces, las siete como `color` de un elemento
cuyo propio `background` es `--c-accent` o `--c-accent-hover` en la misma regla (`.btn`, `.chip[aria-
pressed="true"]`, `.account-pill`, los dos chips de aviso de especie en mi cuenta) — nunca junto a
`--c-bg`. Medido a mano contra el fondo real que sí pinta (arriba): 7,77:1 y 9,45:1, muy por encima
del 4,5:1 de AA. Es la misma clase de par que `color.php` nunca forma que ya documentaron
`aranda/ficha.md` para `--c-border` y `terrazza/ficha.md` para su pie invertido.

**Un color sí fallaba de verdad, y se corrigió en el lienzo y en la maqueta.** El texto de la fila
agotada (Xarda, «vuelve el lunes») medía `#6F8078` sobre `#0F1714`: 4,37:1, bajo el 4,5:1 de AA — un
fallo real, no una discrepancia de la herramienta, porque ese color SÍ se pinta sobre el suelo cada
vez que la subasta agota una especie. Aclarado a `#7E9088` (mismo matiz, más claro), 5,41:1 sobre el
suelo y 4,89:1 sobre el suelo alterno. Corregido en `Bajura.dc.html`, en `Categoria.dc.html` y en
`maqueta/index.html` (`--c-dim`), nunca sólo en uno de los tres — la misma regla que ya pagaron
`aranda`, `lumiere`, `tueste` y `terrazza` con sus propios colores.

**Margen de página: 64px, es decir 4,444&nbsp;% (2/45), no el 7,5&nbsp;% del estándar de la casa.**
`Bajura.dc.html` usa `64px` como relleno lateral en sus nueve bandas de contenido — cabecera, franja
de código postal, hero, tabla de la subasta, «el peso es aproximado», la foto del puerto, «hasta
dónde llegamos», «cómo viaja» y el pie — dieciséis apariciones de la cadena `64px` contadas por línea
(`rg -o '64px' Bajura.dc.html | wc -l` → 16), cero de `108px`. No coincide con ninguno de los otros
cinco valores ya medidos en la biblioteca (48 de `terrazza`, 72 de `aranda`, 96 de `lumiere`, 108
estándar de `marzo`/`barro`/`escuadra`/`cadencia`/`delao`, 144 de `tueste`). Es la segunda plantilla
más compacta de la casa después de `terrazza`. Las cuatro láminas nuevas y la maqueta lo expresan como
fracción (`--page-margin:clamp(20px, 4.444vw, 64px)`), nunca en píxel fijo, siguiendo la regla que
`aranda`, `lumiere`, `terrazza` y `tueste` ya fijaron para el suyo.

## Mapeo nativo

**Propuesto, no verificado todavía contra el vocabulario nativo introspeccionado
(`vocabulario-nativo.md`, sin generar aún).** Elementor, sin un solo widget HTML y sin CSS a medida.

| Sección | Elementor (nativo) | Nota |
|---|---|---|
| Cabecera + franja de código postal | Theme Builder: contenedor flex + Logotipo + Menú de navegación + `woocommerce-menu-cart`, más un segundo contenedor de ancho completo con un campo de texto y un Botón | El comprobador de código postal es una demostración estática; validar una zona real por CP es terreno de una extensión de zonas de envío — ver nota debajo de la tabla |
| Hero + listado de la subasta | Contenedor de texto (Encabezado + Editor de texto) + Loop Grid de una columna, fila por fila (Encabezado + Editor de texto + Encabezado de precio + Botón) | **No verificado**: que el número de «Quedan» baje en vivo según entran pedidos es stock por variación de WooCommerce, no un contador de kilos — ver nota |
| Rejilla de cuatro fotografías | Contenedor rejilla de 4 columnas, hueco cero, con Imagen + superposición de texto | El pie de foto usa el control nativo de posición superpuesta de Elementor |
| El peso es aproximado (ejemplo de facturación) | Contenedor flex de dos columnas: Imagen + contenedor de texto (Encabezado + Editor de texto + contenedor con filas clave-valor) | El panel «un pedido real» es contenido editorial fijo, no un cálculo en vivo |
| Foto a sangre del puerto | Imagen a ancho completo | |
| Hasta dónde llegamos (zonas) | Contenedor rejilla de 3 columnas: Encabezado + Editor de texto por zona | Página de Elementor; las zonas de envío reales las gestiona `skills/woocommerce/` en el sitio del cliente |
| Cómo viaja | Contenedor flex de dos columnas: Imagen + rejilla de 3 columnas de texto | |
| Categoría: «queda poco» / «queda para todo el día» | Plantilla de archivo de producto del Theme Builder: `woocommerce-breadcrumb` + dos Loop Grid de fila única, uno por grupo de urgencia | El orden por «kilos que quedan» es una vista guardada por stock, no una categoría de WooCommerce — ver nota |
| Ficha: compra | `woocommerce-breadcrumb` + `woocommerce-product-images` + `woocommerce-product-title` + `woocommerce-product-price` + `woocommerce-product-add-to-cart` | Cubre el «añadir a la cesta»; no cubre el peso real facturado tras pesar — ver nota |
| Ficha: cómo la quieres (chips de limpieza) | Grupo de Botones ligado a los atributos de un Producto variable | |
| Ficha: de dónde sale / cómo viaja / después de las 17:00 | `woocommerce-product-data-tabs` para la procedencia; contenedor rejilla de 4 columnas para barco/puerto/subasta/ticket; contenedor de texto para el corte | |
| La lonja: cifras + pasos + tabla de confianza | Contenedor rejilla (3 columnas) + contenedor flex de pasos numerados + Tabla nativa o filas clave-valor | Página de Elementor, sin widget de WooCommerce, según `paginas-obligatorias.md` |
| Contacto: datos + preguntas + formulario | `Formulario` nativo de Elementor Pro (Nombre, Correo, Motivo, Mensaje, Casilla de consentimiento) + contenedor de filas clave-valor + rejilla de 3 para las preguntas | |
| Carro / Pago | Página Carro (`woocommerce-cart`) / Página Finalizar compra (`woocommerce-checkout-page`) | |
| Pedido recibido | Sin widget propio, vestida con los ajustes globales — a verificar, según `paginas-obligatorias.md` | |
| Mi cuenta: pedidos + direcciones | `woocommerce-my-account` | |
| Mi cuenta: avisos de especie | **No tiene equivalente en `woocommerce-my-account`** | Ver nota — es el hueco más grande de esta tabla |
| Pie | Theme Builder | Aviso legal, Privacidad, Cookies y Condiciones de venta y envíos enlazan a sus páginas |

**Lo que WooCommerce core no cubre, dicho sin adornar.** El Objetivo `subasta-diaria` pide tres cosas
que WooCommerce, tal y como lo introspecciona `skills/woocommerce/`, no resuelve de fábrica:

1. **Un catálogo que se vacía y se rellena entero cada mañana, a la misma hora, sin intervención
   manual.** Un «Producto simple» o «variable» de WooCommerce no sabe qué hora es ni se reordena solo
   por urgencia de stock; agrupar por «queda poco» frente a «queda para todo el día» hoy es una vista
   guardada o una taxonomía que alguien reordena a mano cada día, no un comportamiento nativo.
2. **Facturar el peso real de la pieza, no el tramo comprado.** WooCommerce cobra el precio del
   producto o de su variación en el momento de la compra; pesar después y ajustar el cargo (o
   devolver la diferencia dentro del margen de ±10&nbsp;%) es terreno de una extensión de venta por
   peso, que este framework todavía no ha instalado ni introspeccionado.
3. **Avisos de especie en «mi cuenta».** El panel que la maqueta dibuja —«avísame cuando haya
   rodaballo»— no tiene hueco en `woocommerce-my-account`: sería una extensión de listas de espera de
   stock, o un campo de metadatos de usuario con su propio formulario, construido aparte.

Todo lo que esta ficha marca **no verificado** en la tabla depende de una de estas tres piezas.
Construirlas de verdad en un sitio de cliente empieza por confirmar qué trae cada extensión antes de
prometer un control que Elementor no tiene dónde enganchar — la misma disciplina que
`tueste/ficha.md` ya aplicó a su suscripción.

## Páginas

Las 14 de `paginas-obligatorias.md` § Ecommerce. Cinco tienen artboard propio en el lienzo — portada,
categoría, ficha de producto (lubina salvaje), la lonja y contacto — porque su composición es trabajo
de diseño. Las nueve restantes se derivan del sistema de la plantilla sin lámina propia: carro, pago,
pedido recibido, mi cuenta, condiciones de venta y envíos, aviso legal, privacidad, cookies y 404.

**Por qué la lubina salvaje y no otra especie.** De las diez especies que lista `Categoria.dc.html`,
la lubina salvaje es la que la propia portada ya nombra dos veces — en la fila de la tabla y en el pie
de foto «Lubina de volanta, 1,140&nbsp;kg» sobre `bajura-pieza.webp` — así que dibujar su ficha no
añade un undécimo dato a sostener: usa el que ya existía y reutiliza la fotografía del producto mismo,
nunca una nueva. Es la misma lógica que llevó a `tueste` a dibujar Huila y a `escuadra` a dibujar
Cocina.

**Las dieciocho filas de especie de portada y categoría enlazan todas a la única ficha dibujada.**
Sólo la lubina tiene lámina de detalle propia; el resto —diez filas en portada, ocho en categoría—
llevan a ella, siguiendo la misma convención que `tueste/canvas/MANIFIESTO.md`,
`escuadra/canvas/Categoria.dc.html` y `cadencia/canvas/Categoria.dc.html` ya usan para su propia única
ficha. Es un desajuste de contenido declarado, no un enlace roto: en el sitio del cliente cada especie
tiene la suya. La única fila que NO enlaza a ningún sitio es la de la especie agotada (Xarda, «vuelve
el lunes»), porque no hay ficha que mostrar de algo que no está a la venta — el mismo criterio que ya
usa `Bajura.dc.html` en su propia tabla.

**Mi cuenta lleva avisos de especie, no una lista de pedidos vacía.** En una tienda cuyo catálogo
cambia entero cada día, «mi cuenta» es donde alguien pide que le avisen cuando vuelva a haber
rodaballo o percebe — el ADN de la propia portada lo pide («esto es lo que hay hoy y no lo hay
mañana») — así que la página derivada añade ese control junto al histórico de pedidos, en vez de un
genérico «gestionar mi cuenta».

**Las páginas legales de la maqueta describen una empresa ficticia**, Bajura Distribución de Pesca,
S.&nbsp;L. En un encargo se reescriben enteras con los datos reales del cliente con
`wordpress-legal`; nunca se publican tal cual. Las condiciones de venta y envíos llevan lo que exige
un producto perecedero: la hora de corte, la cadena de frío y qué no se puede devolver y por qué (art.
103, letras d y e, del Real Decreto Legislativo 1/2007) — donde `paginas-obligatorias.md` las espera
para una tienda que vende alimentación fresca.

## Procedencia y decisiones abiertas

`Bajura.dc.html` llegó a este repositorio ya dibujado, con las siete fotografías y su manifiesto, sin
`canvas.json` ni el resto de páginas — el mismo origen («seis portadas», commit `b153c6a`) que
`aranda`, `lumiere`, `terrazza`, `tueste` y `corte`. **No se dispone de su URL de artifact** y
`canvas_url` se deja fuera del frontmatter en vez de inventarse — ver `canvas/MANIFIESTO.md` § «De
dónde sale» para el detalle completo.

**Los siete `alt` de la portada no coincidían con el manifiesto, y se corrigieron, incluso en la
portada.** `Bajura.dc.html` etiquetaba sus fotografías por lo que ilustran en el diseño en vez de por
lo que describe `manifiesto-imagenes.md` — el defecto que ya pagaron `aranda`, `lumiere` y `terrazza`.
Uno de los siete no era sólo una etiqueta distinta: `bajura-lomo.webp` retrata «lomo de atún rojo», y
`Bajura.dc.html` la usaba para un ejemplo de precio de **bonito**, una especie real de la tabla que esa
fotografía no enseña. Se corrigió el ejemplo entero a atún rojo (32,00&nbsp;€/kg, pieza de 4,860&nbsp;kg
→ 155,52&nbsp;€) en vez de dejar un párrafo hablando de una especie junto a una foto ya etiquetada como
otra. El detalle completo está en `canvas/MANIFIESTO.md` § «Reglas que ya se pagaron en este lienzo».

Las cuatro láminas que faltaban —`Categoria.dc.html`, `Ficha.dc.html`, `LaMarca.dc.html` y
`Contacto.dc.html`— se escribieron en este encargo, en el idioma exacto de la portada (1440 de ancho,
mismos tokens, mismo margen de 64px medido), con el mismo método con el que `tueste` y `terrazza`
completaron su propio juego de páginas. Sus alturas están medidas con `alto-contenido.mjs`, no
tecleadas: 1770, 1508, 1536 y 1193 — ver `canvas/MANIFIESTO.md`. La portada se re-midió en la misma
pasada: 3581, distinto del alto que no llevaba declarado ningún `canvas.json` previo (la plantilla no
tenía uno).

`maqueta/index.html` se deriva de las cinco láminas para las cinco páginas de contenido y del sistema
que ellas fijan para las nueve de sistema, con los dos puntos de ruptura de la casa (1024 y 767) y el
margen de página como fracción (4,444&nbsp;%, medida — ver más arriba), nunca en píxel fijo. Fuentes
embebidas como `data:` woff2 leídas de `skills/html-mockup/assets/fonts/_fonts.php` (Archivo + Source
Sans 3, 82,9&nbsp;KB en base64), nunca tecleadas.

**Dos defectos que `medir-geometria.mjs` encontró y nada más vio, corridos sin que el encargo lo
pidiera.** Primero: dos bandas de la portada («el peso es aproximado» y «cómo viaja») llevaban la
clase `split` sin la clase `container`, así que su texto se pegaba al cristal del navegador a 375px de
ancho — cero relleno lateral donde el resto de la página tenía 20px — medido como `al cristal sin ser
media` antes del arreglo y en cero después. Segundo: la tabla de cookies llevaba `white-space:nowrap`
en sus celdas, que infla el ancho natural de la tabla dentro de su contenedor con scroll y empuja
columnas enteras más allá del borde del viewport aunque la PÁGINA no desborde — se comparó contra
`terrazza/maqueta/index.html#cookies`, que no lleva ese `white-space` y no dispara el aviso, como caso
de control antes de tocar nada. Quitar la propiedad (dejar que el texto envuelva, igual que
`terrazza`) lo corrigió sin tocar el patrón de scroll en sí. Los dos, con las catorce páginas barridas
a 375 y a 1440, salen ahora en cero.

**El Enfoque se corrigió de `brutalista` a `tecnologico`, y la plantilla no se tocó.** BAJURA entró en
`recomendador.md` con `brutalista` porque era la única casilla libre de las tres sin plantilla que
`subasta-diaria` podía ocupar cuando se escribió la tabla, no porque se leyera del lienzo. Contra los
ocho ejes de `enfoques.md`, `Bajura.dc.html` sólo coincide en dos: Elevación «ninguna» (cero
`box-shadow` en todo el fichero) y Composición «asimétrica» (el hero de dos columnas desiguales, «el
peso es aproximado» y «cómo viaja»). Los otros seis lo contradicen con evidencia citada por línea:
Escala «monumental» — el h1 mide 62px, muy por debajo de las plantillas realmente monumentales de la
casa (94px `terrazza`, 74px `corte`) y ningún titular sale sin ajustar. Densidad «compacta» — el
relleno de sección va de 46 a 66px, generoso, no compacto. Fondo «saturado» — `#0F1714`/`#18211D` es
un casi negro frío, no un color vivo. Acento «policromo acotado» — un único naranja funcional en
dieciséis usos, nunca un conjunto de colores distintos. Chasis «sombra dura» — cero `box-shadow`,
sólo filetes finos. Ornamento «patrón» — no hay ninguno. Dos de ocho.

`tecnologico` encaja mejor con la misma evidencia: Densidad «generosa» coincide con el mismo relleno
de 46–66px. Fondo «tinta fría»: `#0F1714` (R15 G23 B20, canal verde el más alto) es frío, el mismo
registro que declara `cadencia`. Composición «rejilla estricta» y Chasis «rejilla estricta»: la tabla
de la subasta (seis columnas de ancho fijo con filete de acento bajo la cabecera) y la rejilla de
cuatro fotografías a hueco cero son la pieza central de la página. Ornamento «ninguno»: no hay patrón
ni textura en todo el fichero. Y una pista que el catálogo no pide pero corrobora la lectura: todo
precio y toda cantidad lleva `font-variant-numeric:tabular-nums`, la misma disciplina de cifras
alineadas que describe `tecnologico`, aunque BAJURA no use una tipografía monoespaciada literal.
Tampoco `tecnologico` encaja limpio: la Escala «contenida» es un encaje razonable, no exacto (62px es
mayor que los 50px de `aranda`, la plantilla que hoy encarna este Enfoque), la Elevación «halo del
acento» no aparece en ningún sitio (BAJURA no tiene halos), y el Acento contradice la cláusula «nunca
letra» de `tecnologico`: el naranja de BAJURA SÍ es color de letra en los precios y en las horas de
corte, porque en una lonja el precio y el cierre son justo el dato que hay que leer, no sólo medir con
una barra. El detalle completo, con cada línea citada, está en `canvas/MANIFIESTO.md` § «Una
divergencia frente al Enfoque declarado».

**Se corrige la etiqueta, no el lienzo**, porque `enfoques.md` dice que manda la plantilla cuando las
dos difieren, y porque el recomendador enruta por Enfoque: con `brutalista` le habría ofrecido esta
plantilla a un cliente que pide ruido, fondo saturado y tipografía sin ajustar, y BAJURA no dibuja
ninguna de las tres cosas.

**BAJURA es la tercera plantilla de la biblioteca sobre un suelo casi negro, después de BARRO y
TERRAZZA, y aquí es donde deja de leerse como cualquiera de las dos dos.** Frente a `barro`
(`lujo-oscuro`, fondo casi negro CÁLIDO, acento verde esmalte que nunca es letra, composición centrada
con bandas de producto a sangre borde con borde, chasis de capas superpuestas): BAJURA es fría, no
cálida; su acento SÍ es letra, en los precios; y su composición no es una vitrina centrada de piezas
sueltas, es una tabla de seis columnas con filas activas. Frente a `terrazza` (`lujo-oscuro` también,
fondo casi negro, acento bronce que se lee como interfaz activa igual que aquí, pero con fotografía de
ambiente a sangre siempre y filas con puntos de conducción de carta de restaurante): BAJURA reemplaza
la carta por una tabla de datos con columnas alineadas y cifras tabulares, y su fotografía se usa
funcionalmente —cada foto lleva su dato encima, nunca decora sin cifra— donde `terrazza` la deja
respirar como escena. Las tres comparten el mismo fondo casi negro; ninguna comparte la mano que la
dibuja.

**La página más débil es «La lonja».** Es la única de las cuatro nuevas que no lleva fotografía
—decisión deliberada para no repetir una de las siete ya habladas en la portada—, así que su primera
pantalla depende enteramente de tres cifras y texto; frente a `Ficha.dc.html`, que tiene la fotografía
del producto y una rejilla de datos de procedencia, «La lonja» es la lámina con menos anclaje visual
de las cuatro, y la primera candidata a llevar una fotografía real si algún día el presupuesto de
imágenes crece más allá de siete.

**Sin veredicto.** Geometría medida en las cinco láminas y en las catorce páginas de la maqueta a 375
y a 1440 — cero tinta al cristal, cero desborde, margen en fracción — y `color.php --maqueta` con
cuatro pares automáticos en verde más los tres que su matriz no clasifica, medidos a mano y también en
verde (el `--c-dim` que sí fallaba, corregido). `empaquetar.php --plantilla bajura` empaqueta la
plantilla entera sin error (1.135.369 bytes, 7 imágenes, 8 referencias). La cadena completa de tests
del framework —`test-container-hygiene`, `test-framework-audit`, `test-audit-signals`,
`test-write-path`, `test-replay`, `test-herramientas`— sigue en cero fallos. Nadie ha mirado la
plantilla todavía: falta `blind-judges` (juez A contra la biblioteca, juez B sobre esta maqueta) y
`visual-verification` a 430, 768 y 1280 en las catorce páginas. Sin las dos, esta plantilla no se
ofrece a un cliente — lo dirá `_indice.md` cuando se actualice.
