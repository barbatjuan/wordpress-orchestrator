---
slug: lumiere
nombre: Lumière
tipo: corporate
sector: estética y belleza, estudio urbano con cabinas propias
objetivo: ritual-bono
enfoque: materia
paginas: [inicio, rituales, ritual, nosotros, contacto, gracias, aviso-legal, privacidad, cookies, 404]
fuentes: [fraunces, inter-tight]
canvas_url: ""
variantes: {}
html_widgets_max: 0
css_custom_max: 0
---

# Lumière · una carta, no una cita suelta

## Para qué sirve

Un centro de estética que vende **rituales por zona, cerrados en bono**: rostro, cuerpo, depilación,
manos y pies, quince rituales en total, cada uno con lo que hace, cuánto dura y cuánto cuesta suelto
y en bono. Sirve para cabinas de belleza, spas urbanos y centros de depilación que cobran por sesión
pero quieren vender constancia, no visitas sueltas — el negocio se sostiene con bonos de 5, 8 o 10
sesiones, no con la cita de hoy.

## Para qué NO sirve

Un centro con acto médico o colegiado (medicina estética, odontología, fisioterapia con diagnóstico):
eso es `pedir-cita`, donde la conversión es reservar hora, no comprar un paquete de sesiones. Lumière
no diagnostica ni prescribe; cuando un caso lo necesita, deriva antes de vender el bono, y eso está
escrito en la propia carta (`Ritual.dc.html`, «Para qué no sirve»).

## ADN — lo que no se toca al adaptarla

- **La carta se lee como una carta, no como una rejilla de productos.** Cada fila es texto, no una
  tarjeta con foto: nombre, qué hace, minutos, precio suelto y precio en bono, alineados en ese
  orden. Cambiarlo por tarjetas de fotografía rompe el carácter — leer varios servicios seguidos, no
  mirar uno.
- **La aritmética del bono está siempre a la vista.** Ningún precio de bono aparece sin su precio
  suelto al lado, y el porcentaje del descuento (−10&nbsp;%, −15&nbsp;%, −12&nbsp;%) es el mismo en
  la portada, en la carta y en la ficha de un ritual. Un ritual con precio de bono que no cuadra con
  el porcentaje publicado es el defecto que este ADN existe para impedir.
- **Tres tonos de fondo, nunca dos.** El suelo (`--c-bg`), el panel entintado de zona y de bono
  (`--c-bg-tint`) y el panel flotante casi blanco (`--c-bg-float`) alternan para separar bloques sin
  una sola sombra dura ni un borde de tarjeta. Es la firma tonal de la plantilla.
- **Dos fotografías llegan al borde, y sólo dos.** El hero de Inicio ocupa la banda entera y la foto
  del producto sangra por la derecha, como en el lienzo; el resto de fotos van dentro del carril, con
  margen de página o de tarjeta. El texto del hero se apoya en la zona clara de la foto, sin velo.
  Ver «Cómo se separa de las otras dos Fraunces + Inter Tight» más abajo.
- **Una lista se construye con espacio en blanco, no con líneas.** Las filas de la carta no llevan
  rejilla dibujada ni celdas con borde propio: un filete horizontal de separación y el aire entre
  filas hacen todo el trabajo. Sustituirlo por una tabla con bordes por celda no es la misma
  plantilla.

## Cómo se separa de las otras dos Fraunces + Inter Tight

Tres de las seis marcas que entran esta semana en la biblioteca emparejan Fraunces con Inter Tight:
`terrazza` (`lujo-oscuro`, fondo casi negro), `tueste` (`institucional`, fondo tostado `#E8DFD0`) y
`lumiere`. La tipografía no se cambia — el lienzo es la autoridad y el usuario ya la aprobó — pero
todo lo demás se ha decidido para que las tres no lean como la misma mano:

- **Ritmo de espaciado.** Lumière alterna banda a banda entre los tres tonos de fondo (arriba); no
  hay un único suelo constante con tarjetas blancas encima, que es el recurso más fácil y el más
  repetido en fondos claros.
- **Uso del suelo.** El acento `#B03A5B` se gasta como color real de interfaz y de texto — CTA,
  precios de bono, enlaces — no como un filete decorativo. Es una decisión de la propia portada
  (`Lumiere.dc.html`), documentada más abajo porque diverge de cómo `enfoques.md` describe el eje
  «acento» para `materia` en `marzo`.
- **Recorte y colocación de fotografía.** Sólo el hero de Inicio y la foto del producto llegan al
  borde; el resto va dentro del carril. En el hero el titular se apoya sobre la zona clara de la foto,
  sin velo (peor píxel medido 4,79:1 entre 1025 y 2560) y **nada se superpone al borde de la
  fotografía**: los cuatro rituales más pedidos viven en una banda entintada debajo, no en una tarjeta
  volada; por debajo de 1024 el texto sale de la foto y se apila. Es lo contrario de un tratamiento a
  sangre con velo oscuro a todo el ancho.
- **Cómo se construye una lista.** Las quince filas de la carta son espacio en blanco y un filete
  fino — nunca una tabla con celdas ni tarjetas de fotografía por fila.

## Qué se cambia para un cliente

| Se cambia | Se conserva |
|---|---|
| Zonas, rituales y sus precios | Que cada fila muestre precio suelto y precio en bono juntos |
| Las diez fotografías | Sólo el hero y el producto a sangre, el resto en el carril; una foto por rol, nunca repetida en el mismo papel |
| Los tres niveles de bono y su porcentaje | Que el bono sea la forma normal de comprar, no una promoción |
| El equipo y su formación | Que se nombre con formación real, no genérica |
| El par tipográfico | Fraunces (o un display de contraste similar) + Inter Tight (o un grotesco similar) |
| El acento rosa (re-medido) | Que sea un color de interfaz de verdad, no sólo decorativo |

## Paleta medida

Medida con `color.php --contraste`, no copiada del lienzo. `--c-text-faint` está **oscurecida**
respecto al hex que usa `Lumiere.dc.html`: el original, `#8A6C74`, mide 4,42:1 sobre el suelo — por
debajo del 4,5:1 de AA — y `color.php` marca de texto cualquier token cuyo nombre lleve «text». Se
comprobó el uso antes de tocar el color: en el lienzo es una etiqueta pequeña («60 min», el pie legal,
migas), texto real, así que el color estaba mal, no el nombre — la lección que ya pagó `escuadra` con
su ocre. Oscurecido a `#7A5D64` (mismo matiz, +12 % de peso), pasa 4,5:1 sobre los tres suelos.

| Papel | Color | Sobre | Contraste |
|---|---|---|---|
| Suelo | `#FDF7F4` | — | — |
| Suelo entintado (zona, bono) | `#F5E6E0` | — | — |
| Suelo flotante | `#FFFDFC` | — | — |
| Tinta | `#2A1B1F` | `#FDF7F4` | 15,52:1 |
| Texto | `#4A3238` | `#FDF7F4` / `#F5E6E0` / `#FFFDFC` | 10,97:1 / 9,57:1 / 11,48:1 |
| Texto apagado | `#6E5A5F` | `#FDF7F4` / `#F5E6E0` / `#FFFDFC` | 6,01:1 / 5,25:1 / 6,29:1 |
| Texto tenue, **re-medido** | `#7A5D64` (antes `#8A6C74`, 4,42:1 sobre el suelo — bajo AA) | `#FDF7F4` / `#F5E6E0` / `#FFFDFC` | 5,53:1 / 4,83:1 / 5,79:1 |
| Acento | `#B03A5B` | `#FDF7F4` / `#F5E6E0` / `#FFFDFC` | 5,49:1 / 4,80:1 / 5,75:1 |
| Acento, hover | `#7E2540` | `#FDF7F4` / `#F5E6E0` / `#FFFDFC` | 8,89:1 / 7,76:1 / 9,30:1 |

`color.php --maqueta` re-mide estos quince pares directamente del `:root` de `maqueta/index.html` y
sale en `0` — ver el informe de verificación. **No existe veredicto todavía**: esto es la medida de
contraste, no el juez ciego ni el barrido visual, que son puertas aparte.

## El margen medido no es 108px

`Lumiere.dc.html` usa `padding` lateral de **96px** en sus once bandas de contenido (líneas 107, 122,
128, 184, 240, 296, 343, 353, 377, 427, 459) y `padding: 30px 64px 26px` en la cabecera; los quince
usos de `108px` del fichero son todos `width: 108px` de la columna de precio en una fila de ritual, no
un margen de página. 96/1440 = 6,667&nbsp;%, no el 7,5&nbsp;% (108px) que usan `delao`, `marzo`,
`barro`, `escuadra` y `cadencia`. Las cuatro láminas nuevas y la maqueta siguen el margen medido de la
propia portada — `--page-margin:clamp(20px, 6.667vw, 96px)` — no el estándar de la biblioteca, porque
seguir el lienzo es la regla, y el lienzo mide 96, no 108.

## Mapeo nativo

**Propuesto, no verificado todavía contra el vocabulario nativo introspeccionado.** Elementor, sin un
solo widget HTML y sin CSS a medida.

Todo contenedor de sección va **en caja de 1248px** con relleno lateral del 6,667&nbsp;% (96px a 1440): por
encima de 1440 el contenido se centra y no crece. Los rellenos verticales son los de las láminas a 1440
(152, 116, 96, 84, 76…) y bajan con el ancho.

| Sección | Elementor (nativo) | Nota |
|---|---|---|
| Cabecera | Plantilla de cabecera del Theme Builder: contenedor flex en caja de 1312px, relleno lateral 4,444&nbsp;% + Logotipo (texto) + Menú de navegación + Botón | El menú nativo trae el desplegable móvil. Sin teléfono en la barra de escritorio, como las láminas; el teléfono vive en el menú móvil |
| Hero de Inicio | Imagen a ancho completo (640px de alto) + contenedor siguiente con margen superior −640px: sólo el contenedor de texto (Encabezado + Editor de texto) | Texto sobre la foto como `Lumiere.dc.html`, SIN velo: relleno izquierdo = carril + 192px (13,333&nbsp;% hasta 1440), así el titular cae siempre sobre la pared, a la derecha de la escalera. Entradilla y datos en tinta `#2A1B1F`. Peor píxel 4,79:1 de 1025 a 2560 (`scrim.php`, línea a línea); el titular, la entradilla y sus márgenes bajan con el ancho por debajo de 1440 para no llegar al rulo de la camilla. Por debajo de 1024, margen 0: se apila bajo la foto. **Sin panel flotante**: nada se superpone al borde de la fotografía |
| Los cuatro más pedidos (bajo el hero) | Contenedor de fondo entintado a ancho completo, en caja de 1248px: fila de cabecera (Encabezado + Editor de texto) con filete inferior + una fila por ritual (Encabezado + Editor de texto + dos Encabezados alineados a la derecha, columnas de 300 / libre / 84 / 108px) + Editor de texto de cierre con el enlace subrayado | Se lee como la carta de la casa: filete fino y aire, nunca celdas con borde. Bajo 1024 el nombre se lleva su línea y efecto, minutos y precio bajan debajo. Ni sombra, ni esquina redondeada, ni solape con la foto — ver «Procedencia» |
| Manifiesto | Contenedor flex de dos columnas (620px y 500px bajada 54px, hueco 80): Encabezado + Editor de texto con lista | Pasa a columna bajo 1024 |
| Banda de zona (Inicio) | Contenedor flex: Imagen (460×400) + contenedor de filas; cada fila, Encabezado + Editor de texto + dos Encabezados de precio (columna de 108px de ancho) | Filas separadas por 26px de hueco, sin filete, como la lámina. Bajo 1024 pasa a columna con la imagen primero (`order`) y los hijos a tamaño automático |
| Zona de La carta | Contenedor por zona: fila de cabecera (Imagen 128×96 + Encabezado + Editor de texto, filete inferior) + lista de filas a todo el ancho; cada fila, contenedor flex con texto (ancho máx. 660px) y columna de precio de 140px de ancho | Como `Rituales.dc.html`: lista, no panel de dos columnas. Bajo 767 la fila pasa a columna y la miniatura a 96×72 |
| Leyenda del bono | Contenedor con fondo entintado + iconos de texto en línea | Una columna bajo 767 |
| Recapitulación del bono (rejilla de 3) | Contenedor a todo el ancho con fondo entintado (La carta) o dentro del panel del bono (Inicio); rejilla de 3 columnas; cada tarjeta, Encabezado + Editor de texto + Encabezado (porcentaje) + Editor de texto (ejemplo: precio en bono y precio suelto) | Columnas a 1 por debajo de 767 con el control nativo |
| Banda de producto | Contenedor flex con relleno sólo a la izquierda (el carril): contenedor de texto (652px) + Imagen (620×460) que llega al borde derecho de la pantalla | A sangre por la derecha, como `Lumiere.dc.html:342`; por encima de 1440 la imagen crece hacia la derecha y el hueco de 72px no cambia. Bajo 1024 se apila con la imagen encima y recupera el relleno derecho |
| Equipo (Inicio y Nosotros) | Contenedor rejilla, 3 columnas de hasta 380px con hueco de 44 a 1280 y a 768, 1 bajo 767; desfase de la lámina (0 / 76 / 32px) como relleno superior de cada retrato, a 0 bajo 767 | Nunca más columnas que retratos. Relleno y no margen: las tres cajas empiezan a la misma altura |
| Panel del bono + CTA | Contenedor de fondo entintado (margen superior 140px) y, dentro, contenedor con sombra con margen superior NEGATIVO de 84px que cabalga el borde de la banda: contenedor flex de dos columnas (texto 312px + rejilla 768px, tarjetas sin fondo ni relleno) + fila de cierre (Botón + Botón + Editor de texto) | El desfase −84 escala con el margen de la banda para no pisar los retratos en móvil. Pasa a columna bajo 1024 |
| Visita / dónde estamos | Contenedor flex con ajuste de línea: Imagen (hasta 540×380) + lista de definición | Bajo 1024 la imagen pierde el tope de ancho y, al no caber, baja a su propia fila |
| Cabecera de ficha de ritual + panel de precio | Contenedor flex con ajuste de línea: contenedor de texto (hasta 640px) + contenedor con sombra de 380px pegado a él con 64px de hueco (filas de precio + dos Botones) | Como `Ritual.dc.html`: el panel no va contra el borde derecho |
| Fases de la sesión | Contenedor flex: intro + lista de fases (Encabezado numérico + Encabezado + Editor de texto por fila) | |
| Para qué no sirve | Contenedor con fondo entintado: intro + lista con icono de texto | |
| Quién te atiende | Contenedor flex, repetido: Imagen circular + Encabezado + Editor de texto | |
| Promesas (Nosotros) | Contenedor rejilla de 3 columnas: Encabezado + Editor de texto por tarjeta | |
| Formación | Contenedor con fondo entintado: intro + filas clave-valor | |
| Formulario de contacto | `Formulario` nativo de Elementor Pro: Nombre, Teléfono, Correo, Mensaje, Casilla de consentimiento, acción «Redirigir» a Gracias | La casilla de consentimiento es el campo nativo `Aceptación`, no HTML a medida |
| Aside de contacto | Contenedor de 360px: Encabezado + lista clave-valor + Imagen (260px de alto) | Entre 768 y 1024, rejilla de dos columnas (tarjeta y foto) encima del formulario; una columna bajo 767 |
| Pasos de Gracias | Igual que «Fases de la sesión» | |
| Índice + columna de lectura (legales) | Contenedor rejilla: contenedor pegajoso (Efectos de movimiento › Sticky) con lista de anclas + columna de Encabezados y Editor de texto por apartado | Mismo patrón que `delao` |
| Fichas de datos (identificación, cookies) | Contenedor con hueco de 1px sobre fondo del filete; cada fila, contenedor con dos Encabezados | Una tabla HTML pediría estilos a mano: por eso son filas |
| 404 | Plantilla «404» del Theme Builder: Encabezado + Editor de texto + tres Botones | Nunca la página desnuda del tema |
| Pie | Plantilla de pie del Theme Builder, sobre el suelo de página y sin margen superior, en caja de 1248px como el contenido (96px a 1440): marca de 340px + rejilla de 4 columnas en 760px con la de Contacto más ancha (260px, mín. 11rem) | Aviso legal, Privacidad y Cookies enlazan a sus tres páginas. Como las cinco láminas: la banda rosa final de Inicio y de La carta llega al pie. Bajo 1024 la marca va encima |

**Techo declarado: cero widgets HTML y cero reglas de CSS a medida.** Si al construir apareciera una
sección que no cabe en esta tabla, la sección se rediseña; no se abre una excepción sin escribirla
aquí con su razón.

## Páginas

Las diez de corporate (`paginas-obligatorias.md`): **inicio**, **rituales** (la carta — es el
«listado o servicios» del tipo), **ritual** (el «detalle»: un ritual completo, aquí aterriza todo
«ver ficha»), **nosotros**, **contacto**, y las cinco derivadas — **gracias**, **aviso legal**,
**privacidad**, **cookies** y **404**. Cinco llevan artboard propio en `canvas/`: la portada
(`Lumiere.dc.html`, ya existía) y las cuatro nuevas de este encargo — `Rituales.dc.html`,
`Ritual.dc.html`, `Nosotros.dc.html` y `Contacto.dc.html`.

**La carta dibuja quince rituales y todos enlazan a la misma ficha.** Sólo Luz Fría (zona Rostro)
tiene lámina de detalle propia, y las quince filas de la carta —y las quince de la portada— llevan a
ella, como las doce filas del registro de `marzo` llevan a su único abrigo y las diez de la categoría
de `barro` a su único cuenco. La maqueta demuestra que el tipo de página existe y se alcanza desde
cualquier listado; en el sitio del cliente cada ritual tiene la suya.

Los textos legales de la maqueta describen una empresa ficticia. En un encargo se reescriben enteros
con los datos reales del cliente mediante `wordpress-legal`; nunca se publican tal cual. Las
condiciones de los bonos —caducidad, cómo se ceden, cómo se cancela una sesión— viven en el aviso
legal, no inventadas en la ficha de un ritual: es donde `paginas-obligatorias.md` las espera para un
negocio que vende bonos.

## Procedencia y decisiones abiertas

La portada, `canvas/Lumiere.dc.html`, llegó a este repositorio ya dibujada, con las diez fotografías y
su manifiesto, sin `canvas.json` ni el resto de páginas. Viene del lienzo «seis portadas» que
`plantillas/_indice.md` describe; **no se dispone de su URL** y se deja en blanco en el frontmatter en
vez de inventarse.

Las cuatro láminas que faltaban —`Rituales.dc.html`, `Ritual.dc.html`, `Nosotros.dc.html` y
`Contacto.dc.html`— se escribieron en este encargo, en el idioma exacto de la portada (1440 de ancho,
suelo en `html, body` y en `[data-suelo]`, mismo margen de 96px medido, mismos tokens), con el mismo
método con el que `marzo`, `barro`, `escuadra` y `cadencia` completaron su propio juego de páginas
esta semana. Sus alturas están medidas con `alto-contenido.mjs`, no tecleadas: 6412 (portada,
remedida en esta misma pasada), 3835, 2584, 2463 y 1578 — ver `canvas/MANIFIESTO.md`.

`maqueta/index.html` se derivó de las cinco láminas para las cinco páginas de contenido y del sistema
que ellas fijan para las cinco de sistema, con los dos puntos de ruptura de la casa (1024 y 767) y el
margen de página como fracción (6,667&nbsp;%, medida — ver más arriba), nunca en píxel fijo. Fuentes
embebidas como `data:` woff2 leídas de `skills/html-mockup/assets/fonts/_fonts.php`, nunca tecleadas.

**La tarjeta flotante de precio del hero se desmontó: era la pieza que delataba la misma mano.** Un
juez ciego, mirando portadas sueltas sin saber que formaban una biblioteca, emparejó `lumiere` con
`tueste` por compartir una tarjeta de precio de esquinas redondeadas volada sobre el borde de la
fotografía, con botón sólido y enlace de texto secundario al lado. En `tueste` esa tarjeta se queda —
allí sostiene el hero y su veredicto la justifica—. Aquí los mismos cuatro rituales bajan a **una
banda entintada a todo el ancho, bajo la foto, leída como la carta de la casa** (nombre, efecto,
minutos, precio; filete fino y aire, nunca celdas), y «Ver la carta» pasa a ser un enlace subrayado
dentro de una frase, no un enlace pegado a un botón — porque el ADN ya dice que una lista se
construye con espacio en blanco y que la carta se lee como una carta, y esta era la única lista de la
plantilla que no obedecía. Cambió el lienzo (`Lumiere.dc.html`, la autoridad) y la maqueta a la vez;
ni la paleta, ni el par tipográfico, ni las demás bandas se tocaron. El panel del bono, que cabalga
el borde de su banda entintada y no el de una fotografía, no entra en el cambio.

**Sin veredicto todavía.** La geometría está derivada del margen medido de la portada, el contraste
de los quince pares de `:root` pasa `color.php --maqueta` (0 fallos) y `empaquetar.php` empaqueta la
plantilla entera sin error, pero falta `blind-judges` (juez A contra la biblioteca, juez B sobre esta
maqueta) y `visual-verification` a 430, 768 y 1280 en cada página. Sin las dos, esta plantilla no se
ofrece a un cliente — lo dirá `_indice.md` cuando se actualice.

**Una divergencia frente a `enfoques.md`, para que la corrija quien mantiene el catálogo, no yo.** La
tabla de `materia` describe el eje «acento» en su columna «En `marzo`» como *«ninguno — el único color
marca existencias»* (campo teñido, sin acento de verdad). `Lumiere.dc.html` no resuelve así ese eje:
`#B03A5B` es un acento activo y con función múltiple — fondo del botón «Comprar un bono» (línea 33),
color de «Ver la carta» (línea 100), de los tres porcentajes del bono (líneas 388, 394, 400) y del
CTA final (línea 406) — no un campo teñido decorativo. Es una lectura legítima de `materia` (los ejes
de la ficha mandan sobre el catálogo cuando difieren, dice el propio `enfoques.md`), pero la fila
«En `marzo`» de la tabla no cubre esta segunda resolución del eje. Se deja anotado aquí en vez de
editar `enfoques.md` — no es mío tocarlo.
