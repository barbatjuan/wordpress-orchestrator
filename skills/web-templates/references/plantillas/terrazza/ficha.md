---
slug: terrazza
nombre: Casa Terrazza
tipo: corporate
sector: restauración, asador y arrocería con carta corta y brasa de encina
objetivo: reservar-mesa
enfoque: lujo-oscuro
paginas: [inicio, carta, plato, nosotros, contacto, gracias, aviso-legal, privacidad, cookies, 404]
fuentes: [fraunces, inter-tight]
variantes: {}
html_widgets_max: 0
css_custom_max: 0
---

# Casa Terrazza · la carta corta, a la vista, siempre

## Para qué sirve

Un restaurante con **brasa propia y una carta corta que cambia**: dieciocho platos como máximo,
arroces por encargo, un menú de mediodía y un menú de degustación cerrado. El acento no es la marca,
es la mesa — precios reales junto a cada plato, horarios de dos turnos y una reserva que se confirma
por SMS en menos de una hora. Sirve para asadores, arrocerías y restaurantes de temporada donde la
cocina decide la carta cada semana y la conversión real es que alguien llame o rellene el formulario
antes de venir, no que compre en línea.

## Para qué NO sirve

Un negocio de comida rápida o reparto a domicilio, donde la conversión es el pedido y no la mesa: ahí
el objetivo es `pedir-online`, no `reservar-mesa`, y la plantilla que le corresponde vende con un
carrito, no con un teléfono grande en la cabecera. Tampoco sirve para banquetes y eventos donde la
conversión real es un presupuesto por escrito — Casa Terrazza reserva mesas de dos a ocho comensales
por teléfono o formulario; los grupos mayores se cierran por teléfono, pero eso es una excepción
dentro del mismo flujo, no un negocio de otro tipo.

## ADN — lo que no se toca al adaptarla

- **La carta nunca pasa de dieciocho platos, y cada uno lleva su precio real al lado, con puntos de
  conducción — nunca una tarjeta con foto.** Es una lista que se lee, no una vitrina que se hojea:
  quitarle los puntos de conducción o meterla en tarjetas con imagen rompe el carácter.
- **El teléfono y la reserva están en todas las páginas, no sólo en contacto.** La franja de utilidad
  (horario, dirección, teléfono) vive fuera de las páginas y el CTA «Reservar mesa» cierra cada página
  de contenido. `reservar-mesa` significa que la reserva nunca está a más de un scroll.
- **La carta declara su propia inestabilidad en vez de esconderla.** «Actualizada el 8 de septiembre»,
  «cambia los martes», «se acaba pronto» no son adornos: son la prueba de que la cocina compra en
  mercado y no de almacén. Sustituir esas notas por una carta «fija» rompe el ADN.
- **El acento bronce se gasta como interfaz de verdad, no como filete.** Precios, CTA, subrayados de
  enlace y etiquetas — nunca sólo una línea decorativa. Es la misma decisión que ya tomó `lumiere` con
  su rosa (`ficha.md` § «Cómo se separa»), y aquí se declara igual de explícita.
- **Una fotografía de ambiente, nunca de producto sobre fondo neutro.** Las siete fotografías son sala,
  terraza, plato servido, cocina, mesa puesta — nunca un producto aislado sobre blanco. Es lo que
  separa esta plantilla de `barro`, la otra oscura de la casa (ver más abajo).

## Cómo se separa de `barro` (la otra oscura) y de `lumiere` (el otro par tipográfico)

Casa Terrazza es una de tres plantillas que emparejan Fraunces con Inter Tight (`terrazza`,
`tueste`, `lumiere`) y una de tres sobre fondo oscuro (`terrazza`, `barro`, `cadencia`). Compartir el
par tipográfico o el fondo no significa compartir la mano:

| Eje | `barro` (la otra oscura) | `lumiere` (el otro par tipográfico) | `terrazza` |
|---|---|---|---|
| Fotografía | Producto sobre blanco tiza, en banda continua borde con borde — el objeto es la foto | Enmarcada con `border-radius`, nunca a sangre | **A sangre, siempre ambiente** — sala, terraza, plato servido, nunca un objeto aislado |
| Acento | Verde esmalte, **nunca texto** (3,81:1, sólo subrayado e interfaz) | Rosa `#B03A5B`, gastado como color de interfaz activo | **Bronce `#D9A441`, gastado como color de interfaz activo** — como `lumiere`, pero en la familia cálida-ámbar, no rosa-magenta |
| Cómo se lee una lista | Rejilla de piezas con foto y taller antes que objeto | Filas con filete y aire, sin puntos de conducción | **Filas con puntos de conducción punteados entre nombre y precio** — convención de carta de restaurante, ninguna otra plantilla de la casa la usa |
| Margen medido | 108px = 7,5&nbsp;% (estándar) | 96px = 6,667&nbsp;% | **48px = 3,333&nbsp;%** — la más compacta de las tres |
| Tipografía de apoyo | Newsreader + Schibsted Grotesk | Fraunces + Inter Tight | Fraunces + Inter Tight |

El resultado: sobre el mismo fondo casi negro que `barro`, Casa Terrazza no enseña un estante de
producto, enseña una sala y una carta que se lee como un menú impreso — puntos de conducción
incluidos. Y con el mismo par tipográfico que `lumiere`, corre en polaridad opuesta (oscuro contra
casi blanco), con fotografía a sangre donde `lumiere` enmarca, y un margen un tercio más estrecho.

## Qué se cambia para un cliente

| Se cambia | Se conserva |
|---|---|
| Marca, dirección, horarios y teléfono | La franja de utilidad en todas las páginas, con el teléfono siempre visible |
| Los platos, precios y menús cerrados | Máximo dieciocho platos, precio real junto a cada uno, puntos de conducción |
| Las siete fotografías | Ambiente a sangre, nunca producto aislado sobre fondo neutro |
| El plato o menú de temporada | Que exista una página de detalle y que el listado entero enlace a ella |
| El equipo y su antigüedad | Que se nombre con años reales en la casa, no genéricos |
| El acento bronce (re-medido) | Que sea un color de interfaz de verdad: precios, CTA, enlaces |
| El par tipográfico | Fraunces (o un display de contraste similar) + Inter Tight (o un grotesco similar) |

## Paleta medida

Medida con `skills/html-mockup/assets/herramientas/color.php --contraste`, nunca copiada del lienzo.
**Dos colores del lienzo tal y como llegó fallaban AA y no estaban medidos.**

| Papel | Color | Sobre | Contraste |
|---|---|---|---|
| Suelo (`--c-bg`) | `#171310` | — | — |
| Suelo alterno (`--c-bg-alt`) | `#211B16` | — | — |
| Tinta (`--c-ink`) | `#F4EBDF` | `#171310` / `#211B16` | 15,65:1 / 14,43:1 |
| Texto (`--c-text`) | `#D6C8B8` | `#171310` / `#211B16` | 11,27:1 / 10,39:1 |
| Texto apagado (`--c-text-muted`) | `#A8998B` | `#171310` / `#211B16` | 6,68:1 / 6,16:1 |
| Texto tenue, **re-medido** (`--c-text-faint`) | `#948877` (antes `#7E7268`, 3,95:1 sobre el suelo — bajo AA) | `#171310` / `#211B16` | 5,32:1 / 4,90:1 |
| Acento (`--c-accent`) | `#D9A441` | `#171310` / `#211B16` | 8,21:1 / 7,57:1 |
| Acento, hover (`--c-accent-hover`) | `#F0C071` | `#171310` / `#211B16` | 10,98:1 / 10,12:1 |
| Texto fuerte sobre bronce (`--c-on-accent`) | `#171310` (= `--c-bg`) | `#D9A441` | 8,21:1 |
| Texto de cuerpo sobre bronce (`--c-bronce-cuerpo`) | `#2E2313` | `#D9A441` | 6,84:1 |
| Etiqueta, **re-medida**, sobre bronce (`--c-bronce-etiqueta`) | `#453516` (antes `#5A4622`, 4,00:1 sobre `#D9A441` — bajo AA) | `#D9A441` | 5,26:1 |

`#7E7268` era la línea legal del pie (una sola aparición, `color:`) y `#5A4622` las cuatro etiquetas
«Personas / Día y hora / Zona / Teléfono» del panel de reserva sobre la banda de bronce — contadas por
grep en `Terrazza.dc.html` antes de tocar nada. Los dos se **oscurecieron o aclararon** manteniendo el
matiz — nunca se les cambió el nombre para alcanzar un bar más blando — y el cambio se hizo **en el
lienzo** (`Terrazza.dc.html`, la autoridad) además de en la maqueta, para que las dos fuentes sigan de
acuerdo. `color.php --maqueta` re-mide diez pares del `:root` de `maqueta/index.html` contra las dos
`--*bg*` — `--c-text`, `--c-text-muted`, `--c-text-faint`, `--c-accent` y `--c-accent-hover`, los
únicos tokens cuyo nombre lleva «text»/«accent» y cuyo valor es un hex literal — y los diez salen
`OK`. `--c-ink` y los dos tonos sobre bronce se miden a mano, arriba, porque sus nombres evitan
`bg`/`text`/`accent`/`border` a propósito: sólo existen sobre la banda de bronce y emparejarlos contra
el suelo oscuro sería medir un uso que nunca ocurre — la misma razón que documenta
`aranda/ficha.md` § Paleta medida para `--c-border`. El filete `--c-border` de esta plantilla es un
`rgba()` sobre la tinta, no un hex plano, por el mismo motivo. **No existe veredicto todavía**: esto
es la medida de contraste, no el juez ciego ni el barrido visual, que son puertas aparte.

## El margen medido no es 7,5&nbsp;%, y el contenido se topa en 1344

`Terrazza.dc.html` usa `padding` lateral de **48px** en sus diez bandas de contenido — diez apariciones
de «48px» como margen de página, contadas por grep, cero de «108px». 48/1440 = 3,333&nbsp;%, no el
7,5&nbsp;% (`clamp(1140px, 85vw, 100vw)`, 108px) que usan `delao`, `marzo`, `barro`, `escuadra` y
`cadencia`, ni el 5&nbsp;%/6,667&nbsp;% de `aranda`/`lumiere`. Las cuatro láminas nuevas y la maqueta
siguen el margen medido de la propia portada, porque seguir el lienzo es la regla y el lienzo mide 48,
no 108. Es, de las siete plantillas con juego completo, la de margen más estrecho: encaja con una carta
que necesita caber dieciocho platos y dos menús cerrados sin que la página se alargue más de lo
necesario.

El margen estrecho pedía además un **tope**, que la primera derivación no llevaba. A 1440 el lienzo
compone **1344px** de contenido; sin tope, el 3,333&nbsp;% seguía resolviéndose contra el cristal y el
contenido crecía sin freno. Medido con `medir-geometria.mjs` sobre la maqueta anterior:

| ancho | margen dominante | fracción | contenido |
|---|---|---|---|
| 1440 | 48px | 3,3&nbsp;% | 1344px |
| 1920 | 48px | 2,5&nbsp;% | 1824px |
| 2563 | 48px | 1,9&nbsp;% | 2467px |
| 3418 | 48px | 1,4&nbsp;% | 3322px |

En pantalla eso era el panel de reserva estirado de lado a lado, las tres cifras del cocinero separadas
300px entre sí y las filas de la carta con el precio a un palmo de su plato. El carril pasa a ser

```
--contenido-max:1344px;
--page-margin:max(clamp(16px, 3.333%, 48px), calc((100% - var(--contenido-max)) / 2));
```

que a 1440 da los mismos 48px y por encima centra: 288px a 1920, 605 a 2554, 1037 a 3418, con el
contenido congelado en 1344. Va en **`%` y no en `vw`** porque la maqueta se mira dentro de un `iframe`
a pantalla completa: a 1920 con barra de desplazamiento el cristal mide 1905, y `vw` seguiría midiendo
1920 y descentraría el contenido 7,5px. Medido dentro del `iframe`: carril izquierdo y derecho
idénticos a 1440, 1680 y 1920, descentre 0px. Nativo: contenedor «en caja» de 1344px con relleno
lateral del 3,333&nbsp;%, que es exactamente lo que hace `marzo` con su 1224 y `lumiere` con su 1248.

## Mapeo nativo

**Propuesto, no verificado todavía contra el vocabulario nativo introspeccionado.** Elementor, sin un
solo widget HTML y sin CSS a medida.

| Sección | Elementor (nativo) | Nota |
|---|---|---|
| Franja de utilidad | Contenedor flex de fondo oscuro, ancho completo | Dos de sus tres datos se ocultan por debajo de 1024 con el control nativo de visibilidad responsive; el teléfono queda siempre visible |
| Cabecera con menú | Plantilla de cabecera del Theme Builder: Logotipo (texto) + Menú de navegación + Botón | El menú nativo trae el desplegable móvil |
| Hero de inicio | Contenedor de texto (Encabezado + Editor de texto) + contenedor flex de campos **de 1044px en caja** (Encabezado + Editor de texto por campo, de sólo lectura) + Botón | Sin formulario real en el hero — el hero nunca lleva el formulario de captura, va en la banda de cierre. El panel lleva el ancho medido del lienzo (168+220+148+192 de campos y 316 de botón): repartido en fracciones se estiraba con la pantalla |
| Banda de foto a sangre | Imagen a ancho completo | La del comedor recorta por **50 % 58 %**, no por el centro: control nativo «Posición del objeto» del widget Imagen, no CSS a medida |
| La carta (listado con precio) | Contenedor rejilla de 2 columnas; cada grupo, Encabezado + filas de Editor de texto/enlace + filete + Encabezado de precio | El filete punteado es el borde inferior de un contenedor vacío, control nativo |
| Menús cerrados | Contenedor flex de 2 columnas: Encabezado + Encabezado de precio + Editor de texto + lista | Columna única por debajo de 1024 con el control nativo de dirección |
| Tira de fotos | Contenedor flex de 3 Imagen sin hueco | |
| La barra | Contenedor flex SIN envolver: columna de 300px + rejilla de 4 columnas que encoge (Encabezado + Editor de texto) | 4 → 2 → 1 por los controles nativos de columnas responsive; la fila pasa a columna a 1024 con el control de dirección. Con envoltura la rejilla saltaba de línea ya a 1440 |
| Dos salas | Contenedor rejilla de 2 columnas: Imagen + Encabezado + Editor de texto | |
| Foto partida (la cocina, la casa) | Contenedor flex: Imagen + contenedor de texto con cifras | Dos variantes, no una: la cocina lleva la foto a la izquierda a **700×520** y la casa a la derecha a **620×480**, las dos medidas del lienzo. El texto crece y apoya su lado exterior en el carril; el interior es un hueco fijo de 72px. La banda no lleva el ritmo vertical de sección, sólo los 78px del contenedor de texto. Apila por debajo de 1024 con el control nativo de dirección |
| Cifras | Las de Nosotros, contenedor rejilla de 4 columnas; las tres de la cocina, contenedor **flex** con hueco de 48px | Nunca más columnas que cifras. En tercios iguales las tres se separaban hasta 300px entre sí al crecer la columna: el lienzo las pone en fila con el ancho que pide cada etiqueta |
| Teaser del plato (en la carta) | Contenedor flex: Imagen + contenedor de texto + Botón de texto | Misma foto que la ficha del plato — pieza única con lámina propia, como `barro` con su Cuenco hondo |
| La bodega | Mismo patrón que la carta: contenedor rejilla de filas con filete | |
| Ficha del plato: cabecera + panel de precio | Contenedor flex: contenedor de texto + contenedor con sombra (filas de precio + Botón) | |
| Pasos («cómo se hace», Gracias) | Contenedor flex de columna: Encabezado numérico + Encabezado + Editor de texto por fila | Mismo componente reutilizado en las dos páginas |
| Cita | Contenedor de fondo alterno: Editor de texto grande + Editor de texto (atribución) | |
| Equipo | Contenedor rejilla de 3 columnas: Imagen, o Encabezado de inicial sobre fondo cuando no hay fotografía | Nunca más columnas que personas — ver «Procedencia», la página más débil |
| Formulario de contacto | `Formulario` nativo de Elementor Pro: Nombre, Teléfono, Personas (número), Día (fecha), Hora, Zona (desplegable), Correo, Mensaje, Casilla de consentimiento | Acción «Redirigir» a Gracias; la casilla enlaza a Privacidad |
| Aside de contacto | Contenedor: Encabezado + lista clave-valor + Imagen | |
| Cierre de bronce | Contenedor de fondo de color a dos columnas + Botón | Reutilizado en las cinco páginas de contenido con distinto texto |
| Pie | Plantilla de pie del Theme Builder | Aviso legal, Privacidad y Cookies enlazan a sus tres páginas |
| Índice + columna de lectura (legales) | Contenedor rejilla: contenedor pegajoso (Efectos de movimiento › Sticky) con lista de anclas + columna de Encabezados y Editor de texto | Mismo patrón que `delao`/`lumiere` |
| Fichas de datos (identificación) | Contenedor con hueco de 1px sobre fondo del filete; cada fila, contenedor con dos Encabezados | Una tabla HTML pediría estilos a mano: por eso son filas |
| Tabla de cookies | Widget nativo **Tabla** de Elementor (desde 3.6) | Nombre, proveedor, finalidad, duración, tipo — única sección de la maqueta con un `<table>` HTML de verdad, con equivalente nativo directo |
| 404 | Plantilla «404» del Theme Builder: Encabezado + Editor de texto + tres Botones | Nunca la página desnuda del tema |

**Techo declarado: cero widgets HTML y cero reglas de CSS a medida.** Si al construir apareciera una
sección que no cabe en esta tabla, la sección se rediseña; no se abre una excepción sin escribirla
aquí con su razón.

## Páginas

Las diez de corporate (`paginas-obligatorias.md` § «Corporate · 10 páginas»): **inicio**, **carta**
(la carta — el «listado o servicios» del tipo), **plato** (el «detalle»: un plato completo, aquí
aterriza todo «ver el plato completo»), **nosotros**, **contacto**, y las cinco derivadas —
**gracias**, **aviso legal**, **privacidad**, **cookies** y **404**. Cinco llevan artboard propio en
`canvas/`: la portada (`Terrazza.dc.html`, ya existía) y las cuatro nuevas de este encargo —
`Carta.dc.html`, `Plato.dc.html`, `Nosotros.dc.html` y `Contacto.dc.html`.

**La página de detalle es un plato, no un menú, y aquí está la razón.** `paginas-obligatorias.md`
deja abierto si el «detalle» de `reservar-mesa` es un menú completo o un plato: de las siete
fotografías, sólo `terrazza-plato` («Plato de carne con salsa de cerezas servido en una mesa oscura»)
retrata un plato servido y montado — ninguna retrata una mesa puesta para un menú de varios pases sin
que sea la misma foto que ya usa la portada para «menús cerrados». Construir el detalle sobre un menú
habría obligado a repetir esa foto bajo un encabezado distinto, exactamente el defecto que el hard
budget de este encargo prohíbe. Un plato, en cambio, tiene una fotografía propia que nunca se usó en
la portada: se convirtió en «Presa ibérica a la brasa con salsa de cerezas», la ejecución de esta
semana de un plato que ya está en la carta fija («Presa ibérica de bellota», 24,00&nbsp;€) — nunca un
plato inventado sin relación con la carta.

**La carta entera enlaza al mismo plato, y no sólo la fila destacada.** Los dieciocho platos de la
carta y los dos menús cerrados no prometen individualmente una ficha propia — son una lista de precio
y descripción que ya se basta a sí misma, la misma convención que fija `lumiere/ficha.md`: «la carta se
lee como una carta, no como una rejilla de productos» — pero cada nombre de plato SÍ es un enlace a
`#plato`, con el mismo hover dorado que el resto de la interfaz, para que la maqueta demuestre que el
tipo de página existe y se alcanza desde cualquier fila del listado, como las doce filas de `marzo`
llevan a su único abrigo y las diez de `barro` a su único cuenco. En el sitio de un cliente real cada
plato con ficha propia tendría la suya; aquí, uno basta para probar la ruta.

Los textos legales de la maqueta describen la empresa ficticia Casa Terrazza S.L. En un encargo se
reescriben enteros con los datos reales del cliente mediante `wordpress-legal`; nunca se publican tal
cual — la propia página de aviso legal lo dice en su último párrafo.

## Techo de la fotografía — dos ampliaciones declaradas

Las siete fotografías se entregaron a **720×540**, salvo la del comedor (`terrazza-sala.webp`,
1440×810). El lienzo compone dos bandas a sangre por encima de ese tamaño, y las dos se dejan como el
artboard las dibuja — no se recortan ni se sustituyen — pero se declaran aquí con su número, medido
sobre el DOM renderizado:

| Banda | Origen | Caja a 1440 | Ampliación a 1440 | a 1920 |
|---|---|---|---|---|
| Ficha del plato, banda simple | `terrazza-plato.webp` 720×540 | 1440×560 | **×2,00** | ×2,67 |
| Portada, banda del comedor | `terrazza-sala.webp` 1440×810 | 1440×440 | ×1,00 | ×1,33 |

La primera es una decisión del lienzo (`Plato.dc.html` dibuja `width:1440px` sobre un original de 720)
y por eso no se toca; la segunda es exacta a 1440 y sólo se amplía por encima, porque una banda a
sangre llega al cristal por definición y el tope de 1344 no la gobierna. **Las dos se resuelven
reponiendo el original a 2×, no con CSS**: al sustituir las fotografías por las del cliente, estas dos
piden un original de 2880px de ancho como mínimo. Ninguna otra imagen de las cinco páginas se amplía a
ningún ancho entre 430 y 1920.

Para el juez: **no hay velo.** Ni una sola de las diez páginas pone texto encima de una fotografía a
ningún ancho — medido por solape de rectángulos sobre los elementos visibles (`checkVisibility()`) a
430, 768, 1024, 1280, 1440, 1680 y 1920: cero solapes texto-sobre-imagen. El tono desigual que se le
atribuye a esta plantilla es de los originales, no del encuadre ni de una capa: la luminancia media va
de **35,3** (`terrazza-plato`) a **176,9** (`terrazza-chef`) sobre 255, cinco veces, mientras el rango
dinámico de cinco de las siete pasa del 80&nbsp;% de la escala. No son fotografías planas: son
fotografías de exposición dispar, y una de ellas ampliada al doble.

## Procedencia y decisiones abiertas

**No hay `canvas_url`.** Igual que `aranda`, `Terrazza.dc.html` viene del lienzo interno «seis
portadas» (commit `b153c6a`), que dibujó seis marcas de la galería antigua sin registrar una URL de
artifact reutilizable por marca. No hay lienzo de Claude Design del que re-sembrar las cuatro láminas
nuevas de esta entrega, así que el frontmatter deja `canvas_url` sin escribir en vez de inventar uno.
`canvas/MANIFIESTO.md` § «De dónde sale» trae el detalle completo.

**Los siete `alt` de la portada no coincidían con el manifiesto, y aquí sí se corrigieron.** Tal y
como llegó, `Terrazza.dc.html` etiquetaba sus fotografías por lo que ilustran en el diseño
(`alt="Sala de Casa Terrazza"`, `alt="Álex Ibáñez, jefe de cocina"`…) en vez de por lo que describe
`manifiesto-imagenes.md` — el mismo defecto que ya se pagó cuatro veces en esta biblioteca según el
propio historial de `aranda`/`lumiere`, y que en esos dos encargos quedó anotado sin tocar la portada.
Este encargo pedía explícitamente corregirlo **incluso en la portada**, así que los siete `alt` de
`Terrazza.dc.html` se reescribieron verbatim contra el manifiesto y las cuatro láminas nuevas nacieron
ya correctas — nunca un nombre inventado para lo que la fotografía enseña.

**Falta una cabecera de navegación, y las cuatro láminas nuevas la añaden.** `Terrazza.dc.html` sólo
dibuja la franja de utilidad (horario, dirección, teléfono): nunca necesitó un menú porque era la
única página que existía. Con cinco páginas de contenido, el sitio necesita moverse entre ellas, así
que `Carta.dc.html`, `Plato.dc.html`, `Nosotros.dc.html` y `Contacto.dc.html` añaden una segunda
franja —cabecera con el nombre de la casa, los cuatro enlaces principales y «Reservar mesa»— en el
mismo idioma oscuro y dorado. `maqueta/index.html` reproduce esa cabecera de dos franjas en las diez
páginas, la portada incluida, porque la cabecera vive fuera de los contenedores de página y es una
sola para todo el sitio. Es una pieza estructural que faltaba, no una decisión de composición nueva.

**La página más débil es Nosotros, y es por la fotografía, no por el texto.** De las siete
fotografías, sólo `terrazza-chef` retrata a una persona con nombre — Álex Ibáñez, ya presente en la
portada. Marisol Peris y Nando Costa, los otros dos nombres del equipo, no tienen fotografía propia
dentro del presupuesto de siete imágenes: llevan una inicial sobre un panel oscuro en vez de un
retrato. La alternativa —reutilizar `terrazza-coctel` o `terrazza-sala` como si fueran su retrato— es
exactamente el defecto que este encargo prohíbe (una fotografía de ambiente puesta a hacer de
fotografía de persona), así que se prefirió la inicial honesta a una foto que miente sobre lo que
enseña. En un encargo real, esos dos retratos se generan o se fotografían antes de construir la
página.

**Sin veredicto todavía.** Geometría medida en las cinco láminas y en la maqueta: alturas de
`canvas.json` medidas con `alto-contenido.mjs`, margen en fracción, `empaquetar.php` empaqueta la
plantilla entera sin error (883.902 bytes, 7 imágenes, 12 referencias) y `color.php --maqueta` pasa
sus diez pares. Nadie la ha mirado todavía: falta `blind-judges` (juez A contra la biblioteca, juez B
sobre esta maqueta) y `visual-verification` a 430, 768 y 1280 en las diez páginas. Sin las dos, esta
plantilla no se ofrece a un cliente — lo dirá `_indice.md` cuando se actualice.
