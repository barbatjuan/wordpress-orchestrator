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
- **La fotografía va enmarcada, nunca a sangre.** Todas las fotos de Lumière llevan un margen de
  página o de tarjeta a su alrededor — nunca tocan el borde del viewport. Es lo contrario del
  tratamiento de otras plantillas de la biblioteca con fondo claro, y es deliberado: ver «Cómo se
  separa de las otras dos Fraunces + Inter Tight» más abajo.
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
- **Recorte y colocación de fotografía.** Ninguna foto llega al borde del viewport: todas están
  enmarcadas con `border-radius:2px` y relleno alrededor, incluso el hero de portada (banda completa,
  pero dentro de su propia sección, nunca detrás del texto). Es lo contrario de un tratamiento a
  sangre con velo oscuro.
- **Cómo se construye una lista.** Las quince filas de la carta son espacio en blanco y un filete
  fino — nunca una tabla con celdas ni tarjetas de fotografía por fila.

## Qué se cambia para un cliente

| Se cambia | Se conserva |
|---|---|
| Zonas, rituales y sus precios | Que cada fila muestre precio suelto y precio en bono juntos |
| Los diez fotografías | Encuadre enmarcado, nunca a sangre; una foto por rol, nunca repetida en el mismo papel |
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

| Sección | Elementor (nativo) | Nota |
|---|---|---|
| Cabecera | Plantilla de cabecera del Theme Builder: contenedor flex + Logotipo (texto) + Menú de navegación + Botón | El menú nativo trae el desplegable móvil |
| Hero de Inicio | Contenedor con Imagen a ancho completo + contenedor de texto (Encabezado + Editor de texto) + contenedor con sombra (panel de precios) | Sin velo ni texto superpuesto a la foto — ver ADN, fotografía enmarcada |
| Manifiesto | Contenedor flex de dos columnas: Encabezado + Editor de texto con lista | |
| Banda de zona (Inicio y La carta) | Contenedor flex: Imagen + contenedor de filas; cada fila, Encabezado + Editor de texto + dos Encabezados de precio | El hueco entre filas y el filete superior son controles nativos de espaciado y borde |
| Leyenda del bono | Contenedor con fondo entintado + iconos de texto en línea | |
| Recapitulación del bono (rejilla de 3) | Contenedor rejilla de 3 columnas; cada tarjeta, Encabezado + Editor de texto + Encabezado (porcentaje) | Columnas a 1 por debajo de 767 con el control nativo |
| Banda de producto | Contenedor flex: Imagen + contenedor de texto | |
| Equipo (Inicio y Nosotros) | Contenedor rejilla, columnas = número de personas (3) | Nunca más columnas que retratos |
| Panel del bono + CTA | Contenedor con sombra: contenedor flex de dos columnas (texto + rejilla) + fila de cierre (Botón + Botón + Editor de texto) | |
| Visita / dónde estamos | Contenedor flex: Imagen + lista de definición | |
| Cabecera de ficha de ritual + panel de precio | Contenedor flex: contenedor de texto + contenedor con sombra (filas de precio + dos Botones) | |
| Fases de la sesión | Contenedor flex: intro + lista de fases (Encabezado numérico + Encabezado + Editor de texto por fila) | |
| Para qué no sirve | Contenedor con fondo entintado: intro + lista con icono de texto | |
| Quién te atiende | Contenedor flex, repetido: Imagen circular + Encabezado + Editor de texto | |
| Promesas (Nosotros) | Contenedor rejilla de 3 columnas: Encabezado + Editor de texto por tarjeta | |
| Formación | Contenedor con fondo entintado: intro + filas clave-valor | |
| Formulario de contacto | `Formulario` nativo de Elementor Pro: Nombre, Teléfono, Correo, Mensaje, Casilla de consentimiento, acción «Redirigir» a Gracias | La casilla de consentimiento es el campo nativo `Aceptación`, no HTML a medida |
| Aside de contacto | Contenedor: Encabezado + lista clave-valor + Imagen | |
| Pasos de Gracias | Igual que «Fases de la sesión» | |
| Índice + columna de lectura (legales) | Contenedor rejilla: contenedor pegajoso (Efectos de movimiento › Sticky) con lista de anclas + columna de Encabezados y Editor de texto por apartado | Mismo patrón que `delao` |
| Fichas de datos (identificación, cookies) | Contenedor con hueco de 1px sobre fondo del filete; cada fila, contenedor con dos Encabezados | Una tabla HTML pediría estilos a mano: por eso son filas |
| 404 | Plantilla «404» del Theme Builder: Encabezado + Editor de texto + tres Botones | Nunca la página desnuda del tema |
| Pie | Plantilla de pie del Theme Builder | Aviso legal, Privacidad y Cookies enlazan a sus tres páginas |

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
