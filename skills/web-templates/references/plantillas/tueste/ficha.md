---
slug: tueste
nombre: Tueste Norte
tipo: ecommerce
sector: tostadero de café por suscripción
objetivo: suscripcion
enfoque: editorial
paginas: [portada, categoria, ficha, carro, pago, pedido-recibido, mi-cuenta, la-marca, contacto, condiciones-venta-envios, aviso-legal, privacidad, cookies, 404]
fuentes: [fraunces, inter-tight]
variantes: {}
html_widgets_max: 0
css_custom_max: 0
---

# Tueste Norte · el café llega el día 3, todos los meses

## Para qué sirve

Un tostadero pequeño que vende **café por suscripción**: la compra suelta existe, pero el negocio se
sostiene con el envío que se repite solo — cuánto café y cada cuánto llega lo elige el cliente una vez,
y a partir de ahí no vuelve a pedirlo. Sirve para cualquier negocio que vende una entrega que no
termina y no se agota en una unidad: café, pienso, cosmética de reposición, vino, cajas de temporada —
el catálogo de `recomendador.md` bajo `suscripcion`.

## Para qué NO sirve

Un tostadero que vende sólo bolsas sueltas, sin cuota recurrente: eso es `catalogo-amplio` o
`tienda-lote`, según si el surtido es ancho o por lotes que se agotan. Tampoco sirve para un plan que
termina en un número fijo de entregas — un pack de tres cajas, una caja de bienvenida única — porque
ahí el Objetivo es `plan-fases`, no `suscripcion`: la frontera que fija `recomendador.md` es que el
plan termina y la suscripción no.

## ADN — lo que no se toca al adaptarla

- **La cadencia, la cuota y cómo se pausa están siempre a la vista, nunca a un clic de distancia.** El
  hero de la portada dice el precio al mes y cómo se pausa en la misma tarjeta que el precio; «Pausar
  es un botón» dedica una sección entera a las tres acciones (saltar, pausar, cancelar) antes de pedir
  ningún dato de pago.
- **El origen no es la promesa; la cadencia lo es.** La suscripción no fija un café para siempre — cada
  envío sale del lote que mejor está esa semana — así que ninguna página vende «suscríbete a Huila»:
  vende «suscríbete, y el primer envío puede ser Huila». `Categoria.dc.html` y `Ficha.dc.html` lo dicen
  con las mismas palabras.
- **Todo precio de bolsa suelta lleva su equivalente en bolsa mensual al lado**, igual que Lumière
  nunca separa el precio suelto del precio en bono: comparar es parte del argumento de compra, no un
  cálculo que el cliente tiene que hacer él mismo.
- **El calendario semanal es un dato, no una frase de marca.** «Tostamos el martes y sale el
  miércoles» aparece primero como frase en la portada y `LaMarca.dc.html` la convierte en tabla de tres
  filas — el mismo hecho, verificable, nunca inventado dos veces.
- **La fotografía se gasta una vez por rol.** Cinco fotografías para catorce páginas es el presupuesto
  más ajustado de la biblioteca; una página que pediría una sexta —`Categoria`, `LaMarca`, `Contacto`—
  se resuelve con tipo, filete y tabla en vez de repetir una foto de ambiente. Es el Enfoque
  `editorial` resolviendo con lo que tiene —filete y tipografía antes que fotografía—, no un recorte de
  presupuesto.

## Qué se cambia para un cliente

| Se cambia | Se conserva |
|---|---|
| El producto (café, pienso, cosmética…), sus planes y su cuota | Que la cadencia y cómo pausar estén en la primera pantalla, no enterradas en «mi cuenta» |
| Los orígenes o variantes del catálogo | Que ningún envío fije para siempre qué llega — la cadencia es la promesa, no el contenido |
| Las cinco fotografías | El presupuesto ajustado: una página sin foto se resuelve con tabla, no se inventa una imagen |
| El calendario de producción (martes/miércoles aquí) | Que el calendario sea un dato verificable, con tabla propia en «la marca» |
| El par tipográfico, si el cliente lo pide | Fraunces (o un display de contraste similar) + Inter Tight (o un grotesco similar) |

## Paleta medida

Medida con `color.php --contraste`, no copiada del lienzo. Los cinco tokens que ya traía
`Tueste.dc.html` pasaban AA antes de esta entrega, a diferencia de `aranda`, `lumiere` y `escuadra`,
que tuvieron que oscurecer un color de la portada — ninguno de esos cinco se ha tocado. Pero la
extensión del sistema a cuatro páginas más SÍ introdujo un color que falla, y se cuenta aquí en vez de
callarlo.

| Papel | Color | Sobre | Contraste |
|---|---|---|---|
| Suelo | `#E8DFD0` | — | — |
| Suelo alterno (bandas de sección) | `#DBD0BD` | — | — |
| Tinta | `#20180E` | `#E8DFD0` / `#DBD0BD` | 13,27:1 / 11,49:1 |
| Texto | `#3D3125` | `#E8DFD0` / `#DBD0BD` | 9,55:1 / 8,27:1 |
| Texto apagado | `#57493A` | `#E8DFD0` / `#DBD0BD` | 6,57:1 / 5,69:1 |
| Acento | `#0F5C28` | `#E8DFD0` / `#DBD0BD` | 6,16:1 / 5,33:1 |
| Acento, hover | `#0B4A1F` | `#E8DFD0` / `#DBD0BD` | 7,90:1 / 6,84:1 |
| Acento, texto sobre botón | `#FFFFFF` | `#0F5C28` | 8,13:1 |
| Pie: tinta invertida (reutiliza `--c-ink`, no un token nuevo) | `#E8DFD0` | `#20180E` | 13,27:1 |
| Pie: texto apagado | `#BFB3A0` | `#20180E` | 8,49:1 |
| Pie: etiqueta de columna, **re-medida** | `#9C8D77` (antes `#8A7C68`, 4,31:1 — bajo AA) | `#20180E` | 5,42:1 |

`color.php --maqueta` re-mide seis de estos pares directamente del `:root` de `maqueta/index.html` y
sale en `0`. Los otros cinco —los tres del pie— no entran en esa corrida porque `color_token_role()`
sólo clasifica un token por si su nombre contiene `bg`, `text`/`accent` o `border`; un nombre como
`--c-footer-muted` o `--c-footer-label` no contiene ninguna de esas cadenas y queda fuera de la
medición automática, el mismo hueco de cobertura que ya documentó `cadencia/ficha.md` para sus siete
colores derivados. Los cinco se han medido a mano con `--contraste` y están arriba.

**El pie no declara `--c-footer-bg` ni `--c-footer-text`.** Su fondo (`#20180E`) y su texto principal
(`#E8DFD0`) son EXACTAMENTE `--c-ink` y `--c-bg` ya existentes — el pie es la única banda invertida de
la plantilla, no una paleta nueva — así que la maqueta reutiliza esos dos tokens en `.band-ink` en vez
de declarar un segundo par. La primera versión sí declaraba `--c-footer-bg`/`--c-footer-text` con los
mismos hex, y eso hacía que `color_root_pairs()` los tratara como un fondo y un texto genéricos más,
cruzándolos contra `--c-text`/`--c-accent` (que nunca se pintan sobre el pie) y contra `--c-bg` (sobre
el que el texto del pie nunca se pinta): cuatro FAIL de matriz que ningún CSS real forma. Se corrigió
quitando el token duplicado, no ajustando `color.php` ni maquillando el nombre de un color que de
verdad falla — la distinción que pide `mockup-guide.md`: esto no era una medición discrepante del
render, era un par que el propio `:root` nunca declaraba usar junto.

**`--c-footer-label` sí fallaba de verdad, y se corrigió en el lienzo y en la maqueta.** Medido a mano
porque el nombre no entra en la matriz automática: `#8A7C68` sobre `#20180E` da 4,31:1, bajo el 4,5:1
de AA, y el token pinta texto real —las tres cabeceras de columna del pie («La tienda», «El
tostadero», «Condiciones») y la línea de dirección/teléfono— en las cinco láminas nuevas y en la
maqueta. Aclarado a `#9C8D77` (mismo matiz, más claro, porque el fondo es oscuro), 5,42:1. Corregido
en los cuatro `.dc.html` nuevos (`Categoria`, `Ficha`, `LaMarca`, `Contacto` — la portada no lo usa, no
tenía ese color) y en `maqueta/index.html`, nunca sólo en uno de los dos: la misma regla que ya pagaron
`aranda` y `lumiere` con sus propios colores.

**Margen de página: 144px, es decir 10&nbsp;%, no el 7,5&nbsp;% del estándar de la casa.**
`Tueste.dc.html` usa `144px` como relleno lateral en las nueve bandas de contenido de la portada —
cabecera, hero, planes, «lo que abres el día 3», «lo que se está tostando», «molido el mismo día»,
«pausar es un botón», «bolsas sueltas» y el pie — dieciséis apariciones de la cadena `144px` contadas
por línea (`rg -o '144px' Tueste.dc.html | wc -l` → 16), nunca `108px`. Es un margen más generoso que
el 7,5&nbsp;% de la casa y que el 5&nbsp;%/6,667&nbsp;% de `aranda`/`lumiere`: un negocio que vende
lo que se lee despacio respira más, no menos, y ese margen ancho es coherente con la densidad generosa
de `editorial` (ver más abajo, § Procedencia, para el Enfoque corregido y sus dos ejes que no casan). Las cuatro láminas nuevas y la maqueta lo expresan como fracción,
nunca en píxel fijo, siguiendo la misma regla que `aranda` y `lumiere` ya fijaron para el suyo.

**Y el 10&nbsp;% tiene techo: el contenido no pasa de 1152px (`--contenido-max`).** El raíl de la
maqueta es uno solo a todos los anchos:

```
--margen-pagina:clamp(20px, 10%, 144px);
--contenido-max:1152px;                       /* 1440 − 2×144, medido en Tueste.dc.html */
--carril:max(var(--margen-pagina), calc((100% - var(--contenido-max)) / 2));
```

Por debajo de 1440 manda la fracción del 10&nbsp;%; a partir de 1440 manda el tope y el raíl crece.
Las dos ramas valen 144px exactos a 1440, así que la curva no tiene escalón. Va en **`%` y no en
`vw`** porque la maqueta se mira dentro de un `iframe` a pantalla completa: a 1920 con barra de
desplazamiento el cristal mide 1905 y `vw` seguiría midiendo 1920. Medido dentro del `iframe`: raíl
único a 1280 (127), 1680 (257) y 1920 (377), desborde 0 en las catorce páginas.

El carril se escribe como **relleno**, no como caja topada. Con `max-width` en la propia sección, una
medida más estrecha centraba su caja y sumaba el relleno lateral encima: el carro llevaba su tabla
208px adentro del raíl de la miga y las cuatro legales 286px, y la columna legal salía **más estrecha
cuanto más ancha era la pantalla** (580px a 1440, 819 a 1024) — el relleno en porcentaje dentro de un
tope de `defectos-de-derivacion.md` § Documento y apilado. Una medida más estrecha se topa ahora en
los hijos de la sección (`.medida-ancha`, 1024px; `.medida-lectura`, 868px), que es un ancho de
contenedor interior en Elementor, no un margen del exterior.

Nativo, sin CSS a medida: contenedor «en caja» de 1152px en escritorio y relleno lateral del
10&nbsp;% en tableta y móvil, que es exactamente lo que hacen `terrazza` con su 1344 y `aranda` con
su 1296.

**Segundo escalón de h2 (`--fs-h2-sm`, 40px a 1440).** El lienzo no usa un solo tamaño de h2: 46 en
los planes de la portada, 44 en sus bandas, 40 en la ficha, 38 en El tostadero y 34 en contacto y
bolsas sueltas. Las páginas interiores llevaban el de 46 —un 15&nbsp;% de más frente a los 40 de
`Ficha.dc.html`— o un `clamp` cuyo máximo declarado (38px) **no se alcanzaba nunca**, porque su
término medio topaba en 34,4 a 1440. En Elementor son dos ajustes de fuente global, no dos reglas.

## Mapeo nativo

**Propuesto, no verificado todavía contra el vocabulario nativo introspeccionado
(`vocabulario-nativo.md`, sin generar aún).** Elementor, sin un solo widget HTML y sin CSS a medida.

| Sección | Elementor (nativo) | Nota |
|---|---|---|
| Cabecera | Theme Builder: contenedor flex + Logotipo (texto) + Menú de navegación + Botón «Entrar» | `woocommerce-menu-cart` sustituye al enlace «Entrar» cuando hay sesión, según `paginas-obligatorias.md` |
| Hero + precio a caballo | Contenedor de banda **a sangre** con relleno IZQUIERDO = el carril: contenedor de texto de ancho personalizado 488px (Encabezado + Editor de texto + dos Botones) + contenedor de Imagen que crece hasta el cristal, con el panel de precio superpuesto (precio + nota de pausa) | La banda no lleva relleno derecho: la foto llega al borde, como en `Tueste.dc.html:32`. Los 488px fijos (416 de texto + 72 de aire) mantienen la medida de lectura igual a 1280, 1440, 1680 y 1920; un 44&nbsp;% de pantalla la dejaba en 41 caracteres a 1280. El panel usa el desplazamiento horizontal **negativo** nativo de Elementor (−128px) para quedar a caballo de la junta: 264px sobre la foto y 128 sobre el crema, como los dibuja el lienzo. Apilado por debajo de 1024, el panel deja de ser capa y pasa a ser el bloque siguiente |
| Planes de suscripción (tres filas + cadencia + molienda) | Contenedor flex de filas repetidas: Encabezado + Editor de texto + Encabezado de precio + Botón; chips de cadencia/molienda como grupo de Botones | **No verificado**: elegir cadencia y molienda como estado interactivo de un Producto variable es terreno de una extensión de suscripciones — ver nota debajo de la tabla |
| Lo que abres el día 3 (lista numerada) | Contenedor flex de dos columnas: Imagen + lista de filas (Encabezado numérico + Editor de texto) | |
| Lo que se está tostando esta semana | Contenedor rejilla de 3 columnas: Encabezado + Editor de texto por tarjeta | Sin producto de WooCommerce detrás — es contenido editorial, no catálogo |
| Molido el mismo día / Pausar es un botón | Contenedor flex de dos columnas: contenedor de texto (+ iconos de línea en SVG) + Imagen | Los iconos de línea son SVG inline servidos como Icono nativo de Elementor, no HTML a medida |
| Bolsas sueltas (filas + CTA) | `woocommerce-products` en disposición de lista, o Loop Grid con Loop Item de una fila | |
| Categoría: tabla de orígenes | Plantilla de archivo de producto del Theme Builder: `woocommerce-breadcrumb` + Loop Grid de fila única (Encabezado + Editor de texto + Encabezado de precio + Botón) | Cada fila enlaza a `woocommerce-product-add-to-cart` de su propia ficha en el sitio del cliente; en la maqueta, las seis enlazan a la única ficha dibujada — ver § Procedencia |
| Ficha: compra (bolsa suelta) | `woocommerce-breadcrumb` + `woocommerce-product-images` + `woocommerce-product-title` + `woocommerce-product-price` + `woocommerce-product-add-to-cart` | Cubre la compra suelta de 250&nbsp;g; no cubre la suscripción — ver nota |
| Ficha: receta numerada + tabla de datos | `woocommerce-product-data-tabs` para el origen/altitud/proceso; contenedor flex de filas para la receta | |
| Ficha: planes de suscripción («o que Huila sea tu primer envío») | Igual que «Planes de suscripción» de la portada | **No verificado**, misma nota |
| La marca: tres cifras + tabla semanal | Contenedor rejilla de 3 columnas (Encabezado + Editor de texto) + contenedor de filas para la tabla lunes/martes/miércoles | Página de Elementor, sin widget de WooCommerce, según `paginas-obligatorias.md` |
| Contacto: datos + formulario + preguntas | `Formulario` nativo de Elementor Pro (Nombre, Correo, Motivo, Mensaje, Casilla de consentimiento) + contenedor de filas clave-valor + rejilla de 3 para las preguntas | La casilla de consentimiento es el campo nativo `Aceptación` |
| Carro | Página Carro de WooCommerce, `woocommerce-cart` | La tabla se topa en 1024px de ancho de contenido del contenedor interior, alineada al carril; nunca centrando la sección, que abría un segundo raíl |
| Pago | Página Finalizar compra, `woocommerce-checkout-page`, con la cuota y la cadencia elegidas visibles en el resumen | **No verificado**: WooCommerce core no muestra un resumen de recurrencia en el checkout — es lo que añade la extensión de suscripciones |
| Pedido recibido | Sin widget propio, vestida con los ajustes globales — a verificar, según `paginas-obligatorias.md` | |
| Mi cuenta: pedidos + control de suscripción | `woocommerce-my-account` para pedidos y direcciones; el bloque «Tu suscripción» (saltar, pausar, cambiar cantidad, cambiar molienda, cancelar) **no tiene equivalente en `woocommerce-my-account`** | Ver nota — es el hueco más grande de esta tabla |
| Legales (cuatro páginas) | Contenedor de sección con el carril + contenedor interior de 868px de ancho de contenido | La medida de lectura va al contenedor interior y arranca en el carril de la miga, no centrada |
| Pie | Theme Builder | Aviso legal, Privacidad, Cookies y Condiciones de venta y envíos enlazan a sus páginas |

**Lo que WooCommerce core no cubre, dicho sin adornar.** WooCommerce, tal y como lo introspecciona
`skills/woocommerce/`, no tiene un concepto nativo de pedido recurrente: un «Producto simple» o
«variable» se compra una vez, y `woocommerce-my-account` lista pedidos pasados, no una suscripción
activa con su próxima fecha de cobro. Todo lo que esta ficha marca **no verificado** — elegir cadencia
como parte de la compra, el resumen de recurrencia en el pago, y sobre todo el panel de «saltar,
pausar, cambiar cantidad, cambiar molienda, cancelar» de mi cuenta — es terreno de **WooCommerce
Subscriptions** (la extensión oficial) o equivalente, que este framework todavía no ha instalado ni
introspeccionado. La maqueta dibuja esos controles porque son el argumento de venta del negocio y
`paginas-obligatorias.md` los pide en texto plano para `suscripcion`; construirlos de verdad en un
sitio de cliente empieza por confirmar qué trae esa extensión en `woocommerce-menu-cart` y
`woocommerce-my-account` antes de prometer un botón que Elementor no tiene dónde enganchar.

## Páginas

Las 14 de `paginas-obligatorias.md` § Ecommerce. Cinco tienen artboard propio en el lienzo — portada,
categoría (orígenes), ficha de producto (Huila), la marca y contacto — porque su composición es
trabajo de diseño. Las nueve restantes se derivan del sistema de la plantilla sin lámina propia: carro,
pago, pedido recibido, mi cuenta, condiciones de venta y envíos, aviso legal, privacidad, cookies y
404.

**Por qué Huila y no otro origen.** De los seis orígenes que lista `Categoria.dc.html`, Huila es el que
la propia portada ya nombra tres veces — en «lo que se está tostando esta semana», en «bolsas
sueltas» y en el precio de ejemplo — así que dibujar su ficha no añade un séptimo dato a sostener: usa
el que ya existía. Es la misma lógica que llevó a `escuadra` a dibujar Cocina (el departamento más
grande del surtido) en vez de uno cualquiera.

**Las seis filas de la categoría enlazan todas a la única ficha dibujada.** Sólo Huila tiene lámina de
detalle propia; las seis filas de `Categoria.dc.html` —y las tres de «lo que se está tostando» y las
tres de «bolsas sueltas» de la portada— llevan a ella, siguiendo la misma convención que
`escuadra/canvas/Categoria.dc.html`, `cadencia/canvas/Categoria.dc.html` y la carta de quince rituales
de `lumiere` ya usan para su propia única ficha. Es un desajuste de contenido declarado, no un enlace
roto: en el sitio del cliente cada origen tiene la suya.

**Mi cuenta lleva controles reales, no una lista de pedidos vacía.** En una tienda de suscripción, «mi
cuenta» es donde alguien pausa, salta un envío o cancela — el ADN de la propia portada lo dice
(«Pausar es un botón») — así que la página derivada de la maqueta repite esos mismos tres controles
con los mismos verbos, no un genérico «gestionar mi cuenta».

**Las páginas legales de la maqueta describen una empresa ficticia.** En un encargo se reescriben
enteras con los datos reales del cliente con `wordpress-legal`; nunca se publican tal cual. Las
condiciones de la suscripción —cuándo se renueva, cómo se pausa, cómo se cancela, qué pasa con un mes
saltado— viven en «condiciones de venta y envíos», que es donde `paginas-obligatorias.md` las espera
para una tienda que vende por cuota.

## Procedencia y decisiones abiertas

`Tueste.dc.html` llegó a este repositorio ya dibujado, con las cinco fotografías y su manifiesto, sin
`canvas.json` ni el resto de páginas — el mismo origen («seis portadas», commit `b153c6a`) que
`aranda`, `lumiere`, `terrazza`, `bajura` y `corte`. **No se dispone de su URL de artifact** y
`canvas_url` se deja fuera del frontmatter en vez de inventarse — ver `canvas/MANIFIESTO.md` § «De
dónde sale» para el detalle completo.

Las cuatro láminas que faltaban —`Categoria.dc.html`, `Ficha.dc.html`, `LaMarca.dc.html` y
`Contacto.dc.html`— se escribieron en este encargo, en el idioma exacto de la portada (1440 de ancho,
suelo en `html, body` y en `[data-suelo]`, mismo margen de 144px medido, mismos tokens), con el mismo
método con el que `aranda`, `lumiere`, `marzo`, `barro`, `escuadra` y `cadencia` completaron su propio
juego de páginas. Sus alturas están medidas con `alto-contenido.mjs`, no tecleadas: 1992, 2200, 2113 y
1773 — ver `canvas/MANIFIESTO.md`. La portada se re-midió en la misma pasada: 4920, igual al valor que
ya llevaba.

`maqueta/index.html` se deriva de las cinco láminas para las cinco páginas de contenido y del sistema
que ellas fijan para las nueve de sistema, con los dos puntos de ruptura de la casa (1024 y 767) y el
margen de página como fracción (10&nbsp;%, medida — ver más arriba), nunca en píxel fijo. Fuentes
embebidas como `data:` woff2 leídas de `skills/html-mockup/assets/fonts/_fonts.php`, nunca tecleadas.

**El Enfoque se corrigió de `institucional` a `editorial`, y la plantilla no se tocó.** TUESTE entró en
la biblioteca con `institucional` porque era una casilla libre, no porque se leyera del lienzo, y el
lienzo lo desmiente en seis de ocho ejes. `institucional` es B2B y servicios profesionales: fondo frío
claro, composición centrada, acento reservado, tarjeta con relleno. `Tueste.dc.html`, tal y como llegó
dibujado, tiene fondo cálido (`#E8DFD0`/`#DBD0BD`), hero asimétrico de dos columnas y un chasis de fila
dividida por filete —planes, bolsas sueltas, tabla de orígenes—, que son la escala, el fondo, la
composición y el chasis de `editorial`. Cuatro ejes coinciden. El detalle, con cada línea contada, está
en `canvas/MANIFIESTO.md` § «Una divergencia frente al Enfoque declarado».

La etiqueta importa más que la taxonomía: el recomendador enruta por Enfoque, y con `institucional` le
habría ofrecido esta plantilla a un despacho que pide algo sobrio y frío. Se corrigió la etiqueta, no el
diseño, porque `enfoques.md` dice que manda la plantilla cuando las dos difieren.

**`editorial` tampoco encaja limpio, y se declara.** Dos ejes discrepan. El acento: `editorial` pide
«ninguno» y TUESTE usa el verde `#0F5C28` en más de una decena de usos activos —botón «Entrar», precio
flotante, CTA del hero, «la que más se pide», chips de cadencia y molienda, los cuatro números de «lo que
abres el día 3»—; en una tienda de suscripción el acento hace el trabajo de decir qué se pulsa, y
quitarlo sería peor tienda. Y el ornamento: iconos de línea en «pausar es un botón» donde `editorial`
pone filete. Es la lectura más cercana del catálogo, no una casilla hecha a medida, y así queda escrito
para quien juzgue la biblioteca.

**Sin veredicto.** Geometría medida en las cinco láminas — 144px = 10&nbsp;%, cero raíles por dentro,
cero tinta al cristal, cero desborde por construcción — y `color.php --maqueta` en cero. La portada y
las cuatro láminas nuevas no han pasado `blind-judges` ni el barrido a 430/768/1280 de
`visual-verification`: `_indice.md` debe seguir marcando TUESTE «sin veredicto» y no se ofrece a un
cliente. La geometría es correcta por construcción y por las comprobaciones automatizadas
(`empaquetar.php`, `color.php`, la cadena de tests); nadie la ha mirado todavía.
