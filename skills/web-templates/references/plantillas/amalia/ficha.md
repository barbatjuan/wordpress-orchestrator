---
slug: amalia
nombre: Amalia Yoga
tipo: corporate
sector: estudio de yoga con sala propia y clases en directo
objetivo: clase-de-prueba
enfoque: editorial
paginas: [inicio, filosofia, planes, clase, diario, entrada, contacto, gracias, aviso-legal, privacidad, cookies, 404]
fuentes: [instrument-serif, archivo]
canvas_url: ""
variantes: {}
html_widgets_max: 0
css_custom_max: 0
---

# Amalia Yoga · llenar un horario, empezando por una clase gratis

## Para qué sirve

Un estudio que **llena un horario semanal de clases en grupo y cobra una cuota mensual**, y que convierte
a quien llega con una **clase de prueba gratuita**: yoga, pilates de suelo, meditación, danza, barre,
escuelas pequeñas de artes marciales. Catorce clases a la semana repartidas en seis formatos, tres
profesoras con nombre, una sala física y la misma clase en directo. La conversión principal es reservar la
clase de prueba; la secundaria, dejar el correo a cambio de la guía de siete días, para quien todavía no
se atreve a venir.

## Para qué NO sirve

- **Un gimnasio de sala de máquinas o de acceso libre**: no hay horario que llenar ni profesora que
  presentar, y la clase de prueba no es la puerta.
- **Pilates o fisioterapia con diagnóstico**: si hay acto sanitario, es `pedir-cita`.
- **Una formación de profesores o un programa cerrado de meses** que termina: es `plan-fases`.
- **Un catálogo de clases grabadas sin sala**: la cuota por contenido que no termina es `suscripcion`, y
  aquí el horario y la sala son el argumento.

## ADN — lo que no se toca al adaptarla

- **La clase de prueba está a un clic en todas las páginas.** Cabecera, hero, planes, ficha de clase,
  entrada del diario y contacto llevan a reservarla. Ninguna llamada principal vende un plan en la primera
  visita.
- **El horario es contenido, no un PDF.** Cada clase aparece en su día y su hora y enlaza a su ficha, y la
  cifra del hero («14 clases semanales») cuadra con el horario. Un horario que no cuadra con la portada es
  el defecto que este ADN existe para impedir.
- **La aritmética del plan anual está a la vista y es la misma en todas partes.** El −15&nbsp;% del
  conmutador es el de los planes y el del bono de diez clases (160&nbsp;€ → 136&nbsp;€).
- **Retícula asimétrica, texto delante.** El hero y Filosofía reparten siete columnas de texto y cinco de
  imagen; la foto de portada va enmarcada bajo un arco, nunca a sangre ni detrás del titular.
- **Filetes, no sombras.** Los bloques se separan con aire, con el fondo alterno y con filetes de 1px; la
  única banda de tinta es la de los planes, y el pie.
- **Un titular, una palabra en acento.** El display va en redonda y el énfasis es una palabra en terracota
  («ritmo», «quedarse», «silencio», «sala»), no un subrayado ni una segunda familia.

## Cómo se separa de `delao`

`delao` y `amalia` comparten **par tipográfico (Instrument Serif + Archivo) y Enfoque (`editorial`)**, y
es el único par de la biblioteca en ese caso. Tipo y objetivo sí difieren (`cartera-curada` frente a
`clase-de-prueba`), así que la regla de `enfoques.md` se cumple, pero un juez de «misma mano» los va a
comparar. Lo que los separa, y no se toca al adaptarla:

- **Portada.** `delao` abre con una casa a sangre y un velo claro que sube desde el texto; `amalia` abre
  con un titular de tres líneas a escala monumental y un retrato enmarcado bajo un arco, sin velo.
- **Cómo se dibuja una rejilla.** `delao` dibuja la línea con el hueco de 1px sobre fondo entintado;
  `amalia` usa bordes por lado y tarjetas con filete sobre el fondo alterno. Nunca el hueco.
- **Acento.** En `delao` el acento se gasta sólo en las cuatro funciones del sistema; en `amalia` también
  en la palabra de énfasis de cada titular y en la llamada de la cabecera.
- **Margen.** `delao` usa el 7,5&nbsp;%; `amalia`, un carril de 1280&nbsp;px con 4&nbsp;vw de mínimo (ver
  abajo).
- **Banda oscura.** En `delao` es la valoración; en `amalia`, los precios.

## Enfoque, eje por eje

Leído del lienzo (`canvas/Amalia.dc.html`) y de la maqueta, no de la ranura libre. La tabla compara con la
posición de `editorial` y con su resolución en `delao`, y con `materia`, que es la candidata más cercana por
el fondo cálido.

| Eje | Lo que mide el lienzo | Posición | `editorial` | `materia` |
|---|---|---|---|---|
| Escala | H1 de portada `clamp(50px, 8,4vw, 148px)`: 121&nbsp;px a 1440 sobre cuerpo de 16–18&nbsp;px, tres líneas. Interiores `6vw` (86&nbsp;px), H2 `4,2vw` | monumental en portada, editorial en interiores | a medias: `delao` corta en 88&nbsp;px; la portada de `amalia` está en la escala de `barro` | no (clásica) |
| Densidad | Secciones de `clamp(64px, 12vh, 140px)`, grupos de ocho, huecos de 20–44&nbsp;px | generosa | sí | no (estándar) |
| Fondo | Papel crudo `#F6F2EA` con alterno `#EFE8DC` y una banda de tinta | cálido claro | sí contra `delao` («claro y cálido, con un fondo alterno»); no contra la posición del catálogo (papel blanco) | sí |
| Elevación | «Sombras: ninguna». Los bloques se separan por aire y por cambio de fondo | ninguna | sí | no (filete) |
| Composición | Hero `1,15fr / ,85fr` (57,5&nbsp;% de texto ≈ 7 de 12), Filosofía `,8fr / 1,2fr` | asimétrica | sí | no (rejilla estricta) |
| Acento | Terracota en la palabra de énfasis, llamadas de texto, numeración, precios de clase, capitular; nunca botón relleno en reposo | reservado | no: la posición es «ninguno» | no (campo teñido) |
| Chasis | Planes, cifras, horario, ficha y FAQ divididos por filetes; tarjetas de clase con filete | dividido por filetes | sí | no (enmarcado por filete) |
| Ornamento | Filete de 46&nbsp;px en los antetítulos, numeración «01 — », arco del retrato. El círculo animado se quitó | filete | sí | no (textura) |

**Veredicto del eje:** `editorial` coincide en seis ejes enteros (densidad, fondo según `delao`, elevación,
composición, chasis, ornamento) y medio más (escala); `materia` coincide en uno (fondo); `lujo-oscuro`, en
dos (escala y densidad) y lo descarta el fondo claro; `institucional`, en uno (acento). Es `editorial`. Las
dos divergencias se declaran en lugar de callarlas: **la escala de portada es monumental** y **el acento
es reservado, no ninguno**. Ninguna de las dos pide otro Enfoque; sí conviene que quien mantiene
`enfoques.md` añada la columna «En `amalia`».

**Par tipográfico:** display con serifa de alto contraste + palo seco de texto, Instrument Serif +
Archivo. **Dirección de imagen:** fotografía de práctica real en una sala de madera con luz cálida, una sola
sesión para portada, clases y diario; retratos de equipo sobre pared de cal.

## Tipografía que la maqueta no puede pintar

- **Instrument Serif itálica.** El lienzo marca el énfasis en cursiva. El registro de la casa
  (`html-mockup/assets/fonts/_fonts.md`) sólo tiene la redonda, y no se descarga nada: la maqueta pinta la
  palabra de énfasis en redonda y en acento, sin itálica sintética. En el build, la fuente global de
  Elementor carga la itálica de Google Fonts; queda como decisión abierta si el sitio la usa o se queda
  como la maqueta.
- **Archivo 300.** El lienzo escribe el cuerpo en 300; el registro tiene 400–700. La maqueta pinta 400. Se
  paga en el ancho de línea: el tercer párrafo de la entrada ocupa cuatro líneas en la maqueta y tres en el
  lienzo, y eso es todo lo que queda de diferencia en esa página (+40&nbsp;px sobre 2141).
- **Interlínea del documento: `normal`.** El lienzo no declara ninguna en el suelo y da la suya a cada bloque
  de lectura (1,6–1,8). En los ajustes globales de Elementor eso es **dejar vacío el campo de interlínea de
  la tipografía de cuerpo**, no escribir un número. Heredar 1,7 engordaba seis píxeles cada rótulo, cada
  elemento de lista y cada fila de horario, y bajaba las secciones enteras: visítanos +47&nbsp;px, planes de
  Inicio +33&nbsp;px, otras clases +20&nbsp;px, contacto +47&nbsp;px.
- **Monoespaciada.** Los números de orden del lienzo iban en `ui-monospace`; pasan a Archivo 500 con cifras
  tabulares, en el lienzo y en la maqueta, para que la Plantilla sean dos familias.

## Qué se cambia para un cliente

| Se cambia | Se conserva |
|---|---|
| Disciplinas, clases y su horario | Que el horario cuadre con la cifra del hero y cada clase enlace a su ficha |
| Los tres planes y sus precios | Que el anual y el bono lleven el mismo descuento publicado |
| Las diecinueve fotografías | Una sola sesión para portada, clases y diario; retratos con la misma luz |
| El equipo | Nombre, disciplina y formación real, nunca genérica |
| El diario y sus categorías | Que cada tarjeta abra su propia entrada y cada categoría tenga al menos una |
| El acento terracota (re-medido) | Que sea la palabra de énfasis y las llamadas de texto, no un fondo |
| El par tipográfico | Display de alto contraste + palo seco de texto, distinto del de `delao` si el cliente lo permite |

## Paleta medida

Medida con `color.php --contraste`, no copiada del lienzo. Cuatro colores del lienzo no llegaban al
4,5:1 y se corrigieron en el lienzo **y** en la maqueta, oscureciendo (o aclarando, sobre tinta) lo mínimo
sin cambiar el matiz:

| Papel | Color | Sobre | Contraste |
|---|---|---|---|
| Suelo | `#F6F2EA` | — | — |
| Suelo alterno | `#EFE8DC` | — | — |
| Tinta (titulares) | `#1E1B16` | `#F6F2EA` / `#EFE8DC` | 15,37:1 / 14,10:1 |
| Texto intenso (cuerpo de entrada) | `#332F28` | `#F6F2EA` / `#EFE8DC` | 11,92:1 / 10,93:1 |
| Texto | `#4A443A` | `#F6F2EA` / `#EFE8DC` | 8,63:1 / 7,91:1 |
| Texto secundario, **re-medido** | `#70685B` (antes `#8A8070`, 3,48:1 / 3,19:1; y `#7C7161`, 4,28:1) | `#F6F2EA` / `#EFE8DC` | 4,92:1 / 4,51:1 |
| Acento, **re-medido** | `#9E5535` (antes `#A65A38`, 4,54:1 / 4,16:1) | `#F6F2EA` / `#EFE8DC` | 4,94:1 / 4,53:1 |
| Papel sobre acento (botón al pasar) | `#F6F2EA` | `#9E5535` | 4,94:1 |
| Borde de campo (interfaz), **re-medido** | `#8A8377` (antes `#D6CCBB`, 1,42:1) | `#F6F2EA` / `#EFE8DC` | 3,36:1 / 3,08:1 |
| Sobre tinta: papel | `#F6F2EA` | `#1E1B16` | 15,37:1 |
| Sobre tinta: cuerpo | `#D8D0C4` | `#1E1B16` | 11,23:1 |
| Sobre tinta: apagado | `#A89E90` | `#1E1B16` | 6,50:1 |
| Sobre tinta: rótulo | `#9A9083` | `#1E1B16` | 5,47:1 |
| Sobre tinta: acento | `#C9825C` | `#1E1B16` | 5,58:1 |
| Sobre tinta: tenue, **re-medido** | `#8C8274` (antes `#6F675C`, 3,08:1) | `#1E1B16` | 4,54:1 |

`color.php --maqueta` re-mide los catorce pares del `:root` de `maqueta/index.html` y sale en `0`. Los tonos
sobre tinta llevan nombres sin «text» (`--c-inversa-*`) a propósito: la herramienta los cruzaría con el
papel, un par que ninguna regla forma; por eso están medidos uno a uno en esta tabla. Los filetes
(`#DED5C6`, `#D6CCBB`, `#E3DACB`, `#C9A88E`) son decorativos y no identifican ningún control: un botón con
texto se identifica por su texto. La barra «/» de las migas sí es texto, y pasó de `#C9A88E` (1,98:1) al
secundario.

## El margen medido

El lienzo centra el contenido con `max(clamp(20px, 4vw, 64px), calc((100% - 1280px) / 2))`: un carril de
1280&nbsp;px con 4&nbsp;vw de relleno lateral como mínimo. A 1440 son 80&nbsp;px (5,556&nbsp;%); a 1280,
51&nbsp;px (4&nbsp;%); a 430, 20&nbsp;px. No es un píxel fijo ni el 7,5&nbsp;% de la casa, y es a propósito:
es más estrecho que `delao`, la otra Plantilla con el mismo par y el mismo Enfoque. En Elementor es un
contenedor en caja de 1280&nbsp;px con relleno lateral de 4&nbsp;vw, dos controles nativos.

## Mapeo nativo

**Propuesto, no verificado todavía contra el vocabulario nativo introspeccionado.** Elementor Pro, sin un
solo widget HTML y sin CSS a medida. Los colores y las dos familias viven en los ajustes globales del sitio.
Clases y entradas son tipos de contenido con su plantilla de entrada individual del Theme Builder: la
maqueta tiene una sola página `clase` y una sola `entrada` que el script rellena con los datos de la tarjeta
pulsada, que es exactamente lo que hace esa plantilla con etiquetas dinámicas.

| Sección | Elementor (nativo) | Nota |
|---|---|---|
| Cabecera | Plantilla de cabecera del Theme Builder: contenedor flex + Logotipo del sitio (SVG, el punto en acento va dentro del archivo) + Menú de navegación + Botón con borde inferior | El desplegable del menú a partir de tableta es el conmutador nativo: uno solo. La llamada se retira bajo 767 y va dentro del menú. Cabecera opaca, pegajosa con Efectos de movimiento |
| `hero` | Contenedor rejilla de columnas personalizadas `1.15fr .85fr`: contenedor fila (Divisor de 46&nbsp;px + Encabezado) + Editor de texto para el H1 (la palabra en acento con el color global desde la barra del editor) + Editor de texto + dos Botones · Imagen con proporción `3/4,1`, ajuste «cubrir» y radio por esquina 240/240/12/12 + contenedor con borde superior (Encabezado + Editor de texto) | Una columna bajo 767 (proporción `4/4,4` y radio 180/180/10/10). **La imagen no lleva alto máximo**: el lienzo la dibuja entera en su columna (513×701 a 1440) y un tope en `vh` la recortaba 82&nbsp;px y movía el hero con el alto de la ventana. Animación de entrada nativa en lugar de `rise`. **A verificar contra el techo**: si `qa-review` cuenta el color de palabra del editor como CSS, el énfasis pasa a un Encabezado aparte |
| `disciplinas` | Contenedor fila con bordes superior e inferior: Lista de iconos en línea sin iconos + Encabezado en acento | Columna bajo 1024; lista en rejilla de tres bajo 767 |
| `filosofia-inicio` | Contenedor rejilla `.8fr 1.2fr`: contenedor pegajoso (Efectos de movimiento › Pegajoso, «permanecer en la columna», sólo escritorio y tableta) con Encabezado + Imagen 4/5 · Encabezado + rejilla de 2 Editores de texto + rejilla de 3 contenedores con borde izquierdo (Encabezado + Editor de texto) + Botón de enlace | Separadores con borde por lado, nunca con hueco sobre fondo |
| `clases` | Contenedor con fondo alterno: cabecera flex con borde inferior + Rejilla de bucle sobre el tipo `clase` (3 / 2 / 1 columnas); plantilla de elemento: contenedor-enlace con borde, Encabezados, Editor de texto y fila con borde superior | Al pasar: Transformar › Desplazar −6&nbsp;px y color de borde, controles nativos de estado |
| `equipo` | Contenedor fila (Encabezado + Divisor) + contenedor rejilla de 3 columnas: Imagen 3/4 + Encabezado + Encabezado (rol) + Editor de texto | Una columna bajo 767 |
| `planes-inicio` | Contenedor con fondo de tinta: Encabezados + Pestañas anidadas con dos pestañas («Mensual», «Anual · −15&nbsp;%») en píldora; cada pestaña, contenedor rejilla de 3 planes con borde izquierdo: Encabezado + Editor de texto + Encabezado de precio + Lista de iconos (SVG de guion en acento) + Botón | Así se resuelve el conmutador: dos rejillas y un conmutador de pestañas. «Suelta» es igual en las dos. El plan destacado cambia color de rótulo y botón. Las pestañas de Inicio y de Planes no comparten estado en el build |
| `diario-inicio` | Cabecera flex + Rejilla de bucle de entradas (3 últimas, 3 / 3 / 1): Imagen destacada 4/3 + Información de la entrada + Título de la entrada | |
| `guia` | Contenedor con fondo alterno y radio 6: rejilla de 2 columnas, texto + `Formulario` (Nombre, Correo, Aceptación obligatoria), acción «Redirigir» a Gracias con ancla `#gracias-guia` y la acción nativa de la plataforma de correo | Sin filete vertical animado |
| `visitanos` | Contenedor rejilla de 2 columnas: Encabezados + filas con borde superior (Encabezado + Editor de texto) + enlaces `mailto:`/`tel:` · Google Maps | La maqueta pinta un bloque estático con la dirección: un iframe remoto no cabe en un Artifact. El mapa se carga tras el consentimiento (ver Cookies) |
| `filosofia-cabecera` | Contenedor rejilla `1.4fr .6fr` con borde inferior: Encabezado + Editor de texto (H1 con palabra en acento) + Editor de texto con borde izquierdo | |
| `filosofia-sala` | Contenedor rejilla `7fr 5fr`: Encabezado (frase) + dos Editores de texto + Imagen 4/3 | Hoy repite `amalia-filosofia`: ver `manifiesto-imagenes.md` |
| `principios` | Contenedor con fondo alterno: Encabezado + rejilla de 4 contenedores con borde superior (Encabezado + Encabezado + Editor de texto) | 2 columnas bajo 1024, 1 bajo 767 |
| `filosofia-cierre` | Contenedor flex con ajuste de línea: Encabezado + Botón | |
| `planes-cabecera` | Contenedor rejilla de 2 columnas con borde inferior: Encabezado + Encabezado | Las pestañas del conmutador son el mismo widget que `planes-tarifas`: en el build quedan alineadas a la derecha justo debajo del filete, no en la fila del H1 |
| `planes-tarifas` | Pestañas anidadas, igual que `planes-inicio`, sobre fondo claro; el plan destacado con fondo alterno | **Bajo 1024 el botón del plan parte de línea** (tipografía por punto de ruptura, control nativo): entre 768 y 1024 la columna mide 190&nbsp;px y una etiqueta como «Empezar con Completa» en una sola línea se salía 23&nbsp;px de su píldora sin desbordar la página |
| `horario` | Cabecera flex + 7 contenedores rejilla `10rem 1fr` con borde superior; cada clase, contenedor-enlace con tres Encabezados (hora, clase, profesora) | Se escribe a mano o sale de campos de cada clase con etiquetas dinámicas |
| `preguntas` | Contenedor rejilla `.8fr 1.2fr` con fondo alterno: Encabezado + Acordeón anidado con la primera pregunta abierta | |
| `clase-cabecera` | Plantilla de entrada individual para `clase`: rejilla `1.2fr .8fr`; Editor de texto con etiqueta dinámica (migas) + Título de la entrada + Extracto + contenedor con borde y filas (Encabezado + Encabezado con campo personalizado) | |
| `clase-cuerpo` | Rejilla de 2 columnas: Contenido de la entrada + Editor de texto («Qué llevar», campo) · Imagen destacada 4/3,2 | |
| `clase-sesion` | Contenedor con fondo alterno: Encabezado + rejilla de 4 pasos con etiquetas dinámicas de cuatro campos fijos | |
| `clase-cta` | Contenedor flex: Encabezado + dos Botones | |
| `otras-clases` | Rejilla de bucle de `clase` con la consulta «excluir la entrada actual», 5 columnas en escritorio y 1 bajo 1024 | |
| `diario-cabecera` | Contenedor con borde inferior: Encabezado + Encabezado + Filtro de taxonomía enlazado a la rejilla, con desplazamiento horizontal bajo 1024 | En la maqueta el filtro oculta también la destacada; en el build filtra sólo su rejilla. **Suelo táctil**: los botones de filtro llevan 40&nbsp;px de alto mínimo frente a los 34 del lienzo, y el botón de cada plan 48; la sección queda 12&nbsp;px más alta que el lienzo a propósito |
| `diario-lista` | Rejilla de bucle de 1 entrada (destacada, plantilla de elemento a 2 columnas) + Rejilla de bucle con desplazamiento 1 (3 / 2 / 1) | |
| `entrada-cabecera` | Plantilla de entrada individual: rejilla de 2 columnas; Información de la entrada (categoría) + Título de la entrada + Extracto con borde izquierdo | |
| `entrada-imagen` | Imagen destacada 16/6,5 (3/2 bajo 767) | |
| `entrada-cuerpo` | Contenedor flex: contenedor pegajoso de 230&nbsp;px (sólo escritorio) con Caja de autor + Información de la entrada + Botón · contenedor de ancho máximo 68ch: Editor de texto con Letra capital en acento (primer párrafo, campo) + Contenido de la entrada (cita con el borde del bloque Cita) + dos Botones | Bajo 1024 el lateral pasa a fila encima del texto, no pegajoso. «Compartir» se quitó |
| `seguir-leyendo` | Contenedor con fondo alterno: Rejilla de bucle de 3 entradas excluyendo la actual; plantilla horizontal Imagen 1/1 + meta + título | 1 columna bajo 1024 |
| `contacto-cabecera` | Rejilla de 2 columnas con borde inferior | |
| `reservar` | Contenedor flex: `Formulario` nativo (Nombre, Correo, Teléfono, Selección «Motivo», Mensaje, Aceptación obligatoria) con acción «Redirigir» a Gracias · contenedor lateral de 300&nbsp;px con Encabezados, enlaces `mailto:`/`tel:` y filas de horario | Bajo 1024 el lateral pasa debajo, a dos columnas; bajo 767, a una |
| `como-llegar` | Google Maps (mín. 380&nbsp;px) + rejilla de 4 contenedores con borde superior | Mismo bloque estático que `visitanos` en la maqueta |
| `contacto-cierre` | Contenedor flex con fondo alterno: Encabezado + dos Botones | |
| `gracias-cuerpo` | Encabezados + rejilla de 3 pasos + contenedor con fondo alterno y ancla `gracias-guia` + Botones | Confirma los dos formularios: el de contacto y el de la guía |
| `aviso-legal-cuerpo`, `privacidad-cuerpo`, `cookies-cuerpo` | Contenedor rejilla: contenedor pegajoso con lista de anclas + columna de Encabezados y Editores de texto; fichas de datos y tabla de cookies como filas de contenedores con borde | Mismo patrón que `lumiere`. Textos ficticios: se reescriben con `wordpress-legal` |
| `error-cuerpo` | Plantilla «404» del Theme Builder: Encabezado + Editor de texto + tres Botones | |
| Pie | Plantilla de pie del Theme Builder con fondo de tinta: rejilla `1.4fr 1fr 1fr 1fr` (2 columnas bajo 1024) + fila inferior con borde | Instagram y Spotify van como texto: una Plantilla no enlaza redes que no existen |

**Techo declarado: cero widgets HTML y cero reglas de CSS a medida.** Si al construir apareciera una
sección que no cabe en esta tabla, la sección se rediseña; no se abre una excepción sin escribirla aquí.

**Quitado del lienzo por no tener expresión nativa:** el círculo radial animado `breathe`, el filete
vertical `pulseline`, la animación `drawline`, el `backdrop-filter` de la cabecera, los botones de
compartir y el mapa embebido como iframe. Se quedan porque sí la tienen: la animación de entrada, el
desplazamiento de −6&nbsp;px al pasar por una tarjeta, el pegajoso de columna y la letra capital.

## Páginas

Doce: las diez de corporate (`paginas-obligatorias.md`) con el nombre que les da el estudio, más el
diario. **inicio**; **planes** es el «listado o servicios» del tipo —tarifas, horario de las catorce clases
y preguntas—, y la rejilla de las seis clases vive en Inicio (`#clases`); **clase** es el «detalle», donde
aterriza toda tarjeta de clase y toda clase del horario; **filosofia** es el «nosotros»; **contacto** lleva
el formulario de reserva; **diario** y **entrada** son el blog; y las cinco derivadas **gracias**,
**aviso-legal**, **privacidad**, **cookies** y **404**. Todo el juego vive en un solo artboard,
`canvas/Amalia.dc.html`, conmutado por estado; las cinco derivadas no tienen artboard.

**Cada tarjeta abre lo suyo.** Las seis clases, las catorce entradas del horario y las siete entradas del
diario llevan a su propia ficha: la maqueta tiene una página `clase` y una `entrada`, y el script las rellena
con la clase o la entrada pulsada, incluida la lista de «Otras clases» sin la abierta y las tres de «Seguir
leyendo». Cero enlaces muertos y ningún enlace remoto: correo y teléfono son `mailto:` y `tel:`.

Los textos legales describen una empresa ficticia (Amalia Ferrer Yoga S.L.). Las condiciones de los planes,
de la clase de prueba y del bono viven en el aviso legal (`#al-condiciones`), enlazado desde el pie.

## Procedencia y decisiones abiertas

El lienzo llegó como paquete de traspaso de Claude Design con dos direcciones; esta es la A, «editorial
cálido». Antes de derivar se corrigió en el propio lienzo todo lo que era color, copy, estructura o enlace
—la lista completa está en `canvas/MANIFIESTO.md`—, y la maqueta se derivó después con los dos puntos de
ruptura de la casa (1024 y 767) y las fuentes embebidas de `_fonts.php`.

Las diecinueve fotografías llegaron de una sesión de Magnific el 15 de septiembre de 2026 (catálogo
Freepik y tres retratos generados); se recortaron a su hueco y están en `manifiesto-imagenes.md`.

Abiertas, para quien mantiene la biblioteca:

- **Objetivo nuevo.** `clase-de-prueba` no existe todavía en `recomendador.md`; la fila propuesta va en el
  informe de esta entrega.
- **Filosofía repite foto** hasta que llegue `amalia-sala`.
- **Itálica.** Decidir si el build usa la itálica de Instrument Serif o se queda en redonda como la maqueta.
- **`enfoques.md`** no tiene columna «En `amalia`» y su posición de escala y acento no cubre la de esta
  Plantilla.

**Sin veredicto todavía.** Contraste (`color.php --maqueta`, 0 fallos) y barrido (doce páginas a 430, 768 y
1280) medidos; faltan `blind-judges` —con el juez A mirando expresamente contra `delao`— y
`visual-verification`. Sin los dos, esta plantilla no se ofrece a un cliente.
