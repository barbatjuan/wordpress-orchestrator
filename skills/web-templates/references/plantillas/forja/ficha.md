---
slug: forja
nombre: Forja Box
tipo: corporate
sector: box de entrenamiento funcional y halterofilia con clases guiadas en grupo
objetivo: clase-de-prueba
enfoque: directo
paginas: [inicio, el-box, disciplinas, coaches, planes, contacto, gracias, aviso-legal, privacidad, cookies, 404]
fuentes: [anton, barlow-condensed, barlow]
canvas_url: ""
variantes: {}
html_widgets_max: 0
css_custom_max: 0
---

# Forja Box · llenar un horario de clases duras, empezando por una gratis

## Para qué sirve

Un box o un centro de entrenamiento que **llena un horario semanal de clases guiadas en grupo y cobra una
cuota mensual sin permanencia**, y que convierte a quien llega con una **clase de prueba gratuita**:
entrenamiento funcional, halterofilia, fuerza, acondicionamiento, escuelas de boxeo o de artes marciales con
tono de exigencia. Ocho disciplinas, 61 clases a la semana con coach y hora, cuatro coaches con nombre y
certificación, 900&nbsp;m² de instalaciones y tres planes. La conversión es reservar la prueba en el
formulario de Contacto; la secundaria, escribir o llamar para hablar con un coach.

## Para qué NO sirve

- **Un gimnasio de sala de máquinas o de acceso libre**: sin clases con hora no hay horario que llenar, y el
  argumento es el precio de la cuota, no la prueba.
- **Entrenamiento personal sólo con cita** o **fisioterapia y nutrición clínica**: sin horario de grupo; si
  hay valoración o tratamiento individual, es `pedir-cita`.
- **Un reto o programa cerrado de semanas que termina**: es `plan-fases`.
- **Clases grabadas sin sala**: es `suscripcion`.
- **Un estudio que vende calma** (yoga, pilates de suelo, meditación): comparte Objetivo pero no carácter;
  su Plantilla es `amalia`.

## ADN — lo que no se toca al adaptarla

- **La prueba gratis está a un clic en todas las páginas y en todos los anchos.** Botón ácido de cabecera
  también en móvil, junto a la hamburguesa; banda de captación al final de cada página de contenido; los tres
  planes y todas las llamadas aterrizan en el formulario (`#reservar`), con la prueba seleccionada por defecto.
- **El horario es contenido y cuadra con la portada.** Cada franja tiene día, hora, disciplina, coach y
  minutos; la cifra de inicio («61 clases por semana») es la suma del horario y los minutos son los de las
  tarjetas de disciplina. Un contador que el resto del sitio desmiente es el defecto que el lienzo traía tres
  veces.
- **Tinta neutra y un solo ácido.** El ácido es suelo (banda de disciplinas, banda de captación, plan
  destacado, botón principal) o letra sobre tinta; **nunca letra sobre claro** (1,03:1 sobre papel). Sin
  sombras y sin degradados de interfaz.
- **Display condensado, monumental y en mayúsculas, con una palabra en ácido por titular** («tu fuerza»,
  «entrenar», «solo», «61»).
- **Rejilla estricta de columnas fijas separada por filetes de 1&nbsp;px.** Lo único que rompe el margen es la
  banda «Nadie entrena solo», a sangre bajo velo.
- **Fotografía dura, en blanco y negro o casi, de gente entrenando de verdad.** Nunca la sonrisa de catálogo.

## Cómo se separa de las Plantillas vecinas

`amalia` y `amalia-salvia` comparten **tipo y Objetivo** (`clase-de-prueba`); `escuadra` comparte **Enfoque**
(`directo`) pero es ecommerce. Ninguna comparte los tres, así que la regla de `enfoques.md` se cumple. Lo que
un juez de «misma mano» debería ver distinto, y no se toca:

| | `amalia` | `forja` |
|---|---|---|
| Suelo | papel crudo cálido, una banda de tinta | tinta neutra, dos bandas de ácido |
| Titular | serifa de alto contraste en redonda, minúsculas | grotesca condensada en mayúsculas |
| Acento | terracota en una palabra y en llamadas de texto | ácido como campo y como botón relleno |
| Primera pantalla | retrato bajo un arco | foto vertical en blanco y negro, recta, con filete |
| Horario | lista por día con las catorce clases visibles | pestañas por día, filas de cuatro datos |
| Margen | carril de 1280 con 4&nbsp;vw | 10&nbsp;% hasta un carril de 1440 |

Frente a `escuadra`: fondo oscuro en lugar de claro, el acento también en letra y como suelo, y fotografía de
personas en lugar de la habitación con su cuenta.

## Objetivo

`clase-de-prueba`, confirmado con las señales del catálogo: horario semanal de clases en grupo («61 clases
por semana»), cuota mensual («sin matrícula, sin permanencia»), la conversión es la prueba gratis
(«Primera clase gratis», «Reserva tu prueba gratis») y el equipo tiene nombre. Descartadas: `plan-fases` (la
cuota no termina), `suscripcion` (se acude a una sala, no llega a casa), `pedir-cita` (no hay diagnóstico ni
tratamiento individual; la fisioterapia es un servicio del plan). **Diferencia con el catálogo:** el Objetivo
nombra una conversión secundaria de guía gratuita por correo que este lienzo no tiene; aquí la secundaria es
«Hablar con un coach».

## Enfoque, eje por eje

Leído del lienzo (`canvas/Forja.dc.html` y las páginas interiores) y medido en la maqueta, no de la ranura
libre. Columnas: la posición del enfoque elegido y su resolución en `escuadra`, y las dos candidatas más
cercanas por recuento.

| Eje | Lo que mide el lienzo | Posición | `directo` (posición · `escuadra`) | `editorial` (`delao`) | `tecnologico` (`cadencia`) |
|---|---|---|---|---|---|
| Escala | H1 de inicio `clamp(52px, 9vw, 150px)`: 130&nbsp;px a 1440 sobre cuerpo de 16–18&nbsp;px; banda a sangre al mismo tamaño; H1 interiores `8vw`, H2 `5vw` | monumental | sí | no (editorial) | no (contenida) |
| Densidad | Secciones de `9vw` (130&nbsp;px a 1440), tarjetas de 40/32&nbsp;px, huecos de 20–64&nbsp;px | generosa | no (compacta) | sí | sí |
| Fondo | `#0A0A0A` con alterno `#0F0F0F`, sin matiz | tinta neutra | sí contra la posición; no contra `escuadra` (claro) | no | a medias (oscuro, pero no frío) |
| Elevación | Sin sombras; bloques separados por filete de 1&nbsp;px y cambio de suelo; al pasar, filete interior ácido de 3&nbsp;px | ninguna (filete) | a medias (el filete ácido es lo más cerca del halo) | a medias | no |
| Composición | Hero `1.15fr / .85fr` sin sangrar la foto; el resto, rejillas fijas de 4, 4, 4, 3 y 2 columnas; una banda a sangre | rejilla estricta | sí contra `escuadra` («dos rejillas estrictas»); no contra la posición (rota) | no (asimétrica) | sí |
| Acento | Ácido `#D9FF00` como suelo de dos bandas, plan destacado y botón; en una palabra por titular, antetítulos y horas | campo teñido | no: degradado en la posición y «nunca en letra» en `escuadra`; sí en «un solo color» | no (ninguno) | no (duotono, nunca letra) |
| Chasis | Disciplinas, servicios, datos y horario divididos por filetes; planes enmarcados; box y coaches desnudos | dividido por filetes | a medias (desnudo en box y coaches) | sí | a medias |
| Ornamento | Una regla ácida de 34&nbsp;px en el antetítulo del hero; nada más | ninguno | sí | no (filete) | sí |

**Recuento:** `directo` cinco ejes (escala, fondo según la posición, composición según `escuadra`,
ornamento, y medio de elevación y chasis); `editorial` y `tecnologico` cuatro cada uno; `lujo-oscuro` tres
(escala, densidad, medio de composición); `brutalista` dos y medio. **Es `directo`, por un eje.** Las frases
de exclusión deciden el empate a su favor: `editorial` se descarta con «botones de color vivo» y pide serifa;
`tecnologico`, con «abren con frase de marca y foto de estilo de vida» y su dirección de imagen «sin
gimnasio»; `lujo-oscuro`, con «ningún grito comercial»; `brutalista`, con «contención» (una sola tinta de
acento, rejilla estricta, espaciado apretado en los titulares). `directo` sí describe el lienzo: contraste
alto, la cifra justo debajo del hero, recortes apretados y una llamada fuerte.

**Divergencias declaradas:** densidad generosa, no compacta; el acento es campo y letra, no degradado ni
«sólo interfaz»; y el suelo es oscuro, al revés que `escuadra`. Conviene que quien mantiene `enfoques.md`
añada la columna «En `forja`».

**Par tipográfico:** grotesca condensada de peso alto para titulares y cifras (Anton) + grotesca condensada
para rótulos, horas y botones (Barlow Condensed 500/600) + grotesca de texto (Barlow 400–600). Diverge del par
de `directo` (neutra + monoespaciada): las cifras van en Anton y en Barlow Condensed con cifras tabulares.
**Dirección de imagen:** fotografía de catálogo en blanco y negro o desaturada, luz dura de nave, caucho,
acero y magnesio; retratos de equipo con brazos cruzados frente al rig.

## Qué se cambia para un cliente

| Se cambia | Se conserva |
|---|---|
| Disciplinas, horario y coaches de cada franja | Que la cifra de inicio sea la suma del horario y los minutos, los de las tarjetas |
| Los tres planes y sus precios | Sin matrícula ni permanencia a la vista; el destacado en ácido |
| Las once fotografías, y siempre los cuatro retratos | Blanco y negro o desaturado, luz dura; el equipo real del cliente |
| El ácido `#D9FF00` (re-medido) | Un solo color vivo sobre tinta neutra, nunca letra sobre claro |
| Las tres familias | Condensada de peso alto en mayúsculas + condensada de rótulos + texto |
| Las cifras de la banda de contadores | Que cada una se cuente en la página que la sostiene |

## Paleta medida

Medida con `color.php`, no copiada del lienzo. El lienzo escribe los grises como `rgba(242,242,240,α)` sobre
tinta; la maqueta los resuelve a hex para que `color.php --maqueta` los re-mida. Dos colores no llegaban y se
corrigieron en el lienzo **y** en la maqueta:

| Papel | Color | Sobre | Contraste |
|---|---|---|---|
| Suelo / alterno / tarjeta al pasar | `#0A0A0A` / `#0F0F0F` / `#141414` | — | — |
| Titulares y texto intenso | `#F2F2F0` | los tres suelos | 17,66 / 17,10 / 16,44:1 |
| Menú (α .72) | `#B1B1B0` | los tres suelos | 9,23 / 8,93 / 8,58:1 |
| Cuerpo (α .60–.66) | `#A3A3A2` | los tres suelos | 7,84 / 7,59 / 7,30:1 |
| Pies, rótulos, meta (α .50–.55) | `#8A8A89` | los tres suelos | 5,73 / 5,55 / 5,33:1 |
| Rótulos del pie, **re-medido** | `#8A8A89` (antes α .40 ≈ `#676766`, 3,50:1) | `#0A0A0A` | 5,73:1 |
| Acento como letra | `#D9FF00` | los tres suelos | 17,21 / 16,66 / 16,01:1 |
| Tinta sobre acento (botón, bandas, plan destacado) | `#0A0A0A` | `#D9FF00` / `#F2F2F0` al pasar | 17,21 / 17,66:1 |
| **Acento sobre papel: prohibido** | `#D9FF00` | `#F2F2F0` | 1,03:1 |
| Borde de campo (interfaz), **re-medido** | `#767674` (antes `rgba(255,255,255,.16)` ≈ `#353535`, 1,52:1) | `#0A0A0A` / `#0F0F0F` / `#141414` | 4,35 / 4,21 / 4,05:1 |
| Filete del botón sobre ácido (decorativo) | `#91A904` | `#D9FF00`; la letra del botón es tinta | — |
| Texto sobre la banda a sangre | `#F2F2F0` / `#D9FF00` | peor píxel de `forja-clase-completa` bajo velo `.72` | 7,35 / 7,17:1 |

`color.php --maqueta` re-mide los veintitrés pares del `:root` y sale en `0`. Los filetes (`#1E1E1E`,
`#232323`, `#2C2C2C`, `#505050`) separan y no identifican ningún control: un botón con texto se identifica por
su texto.

## El margen medido

El lienzo rellena cada banda con `10vw` a los lados hasta un contenido de 1440&nbsp;px. La maqueta lo expresa
como `max(10vw, calc((100% - 1440px) / 2))`: 144&nbsp;px a 1440 y 128 a 1280 (**10&nbsp;%**, más que el 7,5 de
la casa), y bajo 1024, `6vw` (46&nbsp;px a 768, 26 a 430). Es a propósito: el aire lateral ancho es lo que
deja respirar un titular de 130&nbsp;px en mayúsculas, y separa esta Plantilla de `amalia` (4&nbsp;vw) y de
`delao` (7,5&nbsp;%). En Elementor es un contenedor en caja de 1440&nbsp;px con relleno lateral en `vw` por
punto de ruptura, dos controles nativos.

## Mapeo nativo

**Propuesto, no verificado todavía contra el vocabulario nativo introspeccionado.** Elementor Pro, sin un
solo widget HTML y sin CSS a medida; la columna Divi es orientativa y **no validada**. Colores y las tres
familias viven en los ajustes globales. La aparición `data-reveal` del lienzo es la animación de entrada
nativa.

| Sección | Elementor (nativo) | Divi (no validado) | Nota |
|---|---|---|---|
| Cabecera | Plantilla de cabecera del Theme Builder: contenedor flex + Encabezado «Forja Box» (la palabra en ácido va en el logotipo SVG) + Menú de navegación + Botón | Cabecera global del Theme Builder: Menú + Botón | Opaca, pegajosa con Efectos de movimiento. Bajo 1024, el desplegable del menú es el único conmutador; el botón «Prueba gratis» sigue visible en todos los anchos |
| `hero` | Contenedor rejilla `1.15fr .85fr`: contenedor fila (Divisor 34×2&nbsp;px + Encabezado) + Encabezado H1 (la línea en ácido, Encabezado aparte si el color de palabra cuenta como CSS) + Editor de texto + dos Botones · Imagen 3/4 con borde | Fila 2 columnas: Texto + Botón ×2 · Imagen | Una columna bajo 767 |
| `banda-disciplinas` | Contenedor con fondo ácido: Encabezado centrado con las ocho disciplinas separadas por «/» | Sección con fondo: Texto | **La marquesina animada se quitó**: Elementor no tiene marquesina nativa (tampoco Pro; el Carrusel de bucle avanza por diapositivas, no en bucle continuo) y Divi tampoco. Queda estática |
| `cifras` | Contenedor rejilla de 4 (2 bajo 767): 4 Contadores con sufijo «+» en el primero | 4 Contadores numéricos; «420+» como Texto (el módulo sólo admite «%») | La animación del número es la del widget |
| `disciplinas-inicio`, `disciplinas-lista` | Cabecera flex (Encabezado + Editor de texto) + contenedor rejilla de 4 (2 bajo 1024, 1 bajo 767) con borde superior e izquierdo; cada tarjeta, contenedor con borde derecho e inferior: Encabezado (número) + Encabezado + Editor de texto + Encabezado (meta) | Blurbs en fila de 4 con borde | Al pasar: fondo `#141414` y Sombra de caja interior de 3&nbsp;px ácida, controles nativos de estado. Nunca el hueco de 1&nbsp;px sobre fondo |
| `regla-casa` | Contenedor con imagen de fondo `forja-clase-completa` + superposición de fondo en degradado (`.72 → .86`) + contenedor centrado: Encabezado + Encabezado H2 + Editor de texto | Sección con fondo y degradado: Texto | Alto mínimo `clamp(540, 64vw, 780)`; sin alto mínimo bajo 767 |
| `box-inicio`, `instalaciones` | Encabezado + contenedor rejilla de 4 (2 bajo 1024): Imagen 4/5 con borde + Encabezado + Editor de texto | Galería o 4 Imágenes + Texto | |
| `coaches-inicio`, `coaches-lista` | Cabecera flex + rejilla de 4 (2 bajo 1024): Imagen 3/4 + Encabezado + Encabezado (rol en ácido) + Editor de texto | Miembro del equipo ×4 | |
| `planes-inicio`, `planes-lista` | Encabezado + Editor de texto + contenedor rejilla de 3 (1 bajo 767); cada plan, contenedor con borde: Encabezados + Encabezado de precio + Lista de iconos (cuadrado de 6&nbsp;px) + Botón; el destacado con fondo ácido | Tablas de precios (3) | Precio con espacio duro antes del «€» |
| `recuperacion-inicio`, `recuperacion` | Contenedor rejilla de 2 (1 bajo 767): Imagen 1/1 · Encabezados + Editor de texto + 3 contenedores fila con borde superior (Encabezado + Editor de texto) | Fila 2 columnas: Imagen · Texto | |
| `prueba`, `prueba-el-box`, `prueba-disciplinas`, `prueba-coaches`, `prueba-planes` | Plantilla guardada (global): contenedor rejilla de 2 con fondo ácido: Encabezados · Editor de texto + Botón tinta + Botón con borde | Sección global | La misma banda en las cinco páginas de contenido |
| `el-box-cabecera`, `disciplinas-cabecera`, `coaches-cabecera`, `planes-cabecera`, `contacto-cabecera` | Contenedor con borde inferior: Encabezado (antetítulo) + Encabezado H1 + Editor de texto | Texto | |
| `horario` | Cabecera flex + **Pestañas anidadas** con 7 pestañas (una por día); cada pestaña, Encabezado + contenedores rejilla `7.5rem 1fr 15rem 7rem` con borde superior y cuatro Encabezados (hora, disciplina, coach, minutos) | Pestañas con 7 pestañas y texto por franja | Bajo 767 la fila pasa a hora + disciplina/minutos con el coach debajo. Se escribe a mano o sale de un tipo de contenido «clase» con etiquetas dinámicas |
| `reservar` | Contenedor rejilla de 2 (1 bajo 1024): `Formulario` (Nombre y Correo obligatorios, Teléfono, Selección «Qué te interesa», Mensaje, Aceptación obligatoria) con acción «Redirigir» a Gracias · Lista de datos (contenedores con borde superior) + Google Maps | Formulario de contacto + Texto + Mapa | La maqueta pinta un bloque estático en lugar del mapa: un iframe remoto no cabe en un Artifact, y el mapa se carga tras el consentimiento |
| `gracias-cuerpo` | Encabezados + rejilla de 3 pasos con bordes + dos Botones | Texto + Blurbs + Botones | |
| `aviso-legal-cuerpo`, `privacidad-cuerpo`, `cookies-cuerpo` | Contenedor rejilla: contenedor pegajoso (sólo escritorio) con lista de anclas + columna de Encabezados y Editores de texto; fichas de datos y tabla de cookies como filas de contenedores con borde | Texto con anclas | Bajo 767 cada fila de cookies se apila con su etiqueta visible (visibilidad por punto de ruptura). Textos ficticios: se reescriben con `wordpress-legal` |
| `error-cuerpo` | Plantilla «404» del Theme Builder: Encabezados + Editor de texto + tres Botones | Plantilla 404 | |
| Pie | Plantilla de pie del Theme Builder: rejilla `1.4fr 1fr 1fr 1fr` (2 bajo 1024) + fila inferior con borde y Menú legal | Pie global | Instagram y YouTube llevan a la banda de la prueba de inicio y WhatsApp a Contacto, como en el lienzo: ningún enlace sale a una red que no existe |

**Techo declarado: cero widgets HTML y cero reglas de CSS a medida.** Una sección que no quepa en esta tabla
se rediseña; no se abre una excepción sin escribirla aquí.

**Quitado del lienzo por no tener expresión nativa:** la marquesina en bucle `om-marquee`, el
`backdrop-filter` y la transparencia de la cabecera, el `overflow-x: hidden` del envoltorio, las rejillas
`auto-fit` y las dibujadas con hueco sobre fondo de línea, y la monoespaciada de sistema.
Se quedan porque sí la tienen: la animación de entrada, el filete interior al pasar por una disciplina, la
cabecera pegajosa, los contadores y las pestañas.

## Páginas

Once. Las diez de corporate (`paginas-obligatorias.md`) con el nombre del box **menos el detalle**, más dos
páginas de contenido: **inicio**; **disciplinas** es el «listado o servicios» —las ocho disciplinas y el
horario de las 61 clases—; **el-box** y **coaches** reparten el «nosotros» (instalaciones y equipo);
**planes** son las tarifas; **contacto** lleva el formulario de la prueba; y las derivadas **gracias**,
**aviso-legal**, **privacidad**, **cookies** y **404**.

**Sin página de detalle.** El lienzo no dibuja una ficha de disciplina y ninguna tarjeta ni franja promete
una («ver ficha» no aparece), así que no hay enlace que aterrice en ella. Si se añade, cada tarjeta de
disciplina y cada franja del horario enlazan a su ficha. Decisión abierta para quien mantiene la biblioteca.

Cero enlaces internos muertos y ningún enlace remoto: correo y teléfono son `mailto:` y `tel:`. Las
condiciones de los planes y de la clase de prueba viven en el aviso legal (`#al-condiciones`), enlazado desde
el pie. Los textos legales describen una empresa ficticia (Forja Box S.L.).

## Procedencia y decisiones abiertas

El lienzo llegó como exportación de Claude Design de siete láminas sin README. Antes de derivar se corrigió
en el propio lienzo lo que era color, copy, estructura o enlace —lista completa en `canvas/MANIFIESTO.md`—, y
la maqueta se derivó después con los dos puntos de ruptura de la casa (1024 y 767) y las fuentes embebidas
de `_fonts.php` (Anton 400, Barlow Condensed 500/600, Barlow 400/500/600; la maqueta no pide otro peso).

Las once fotografías llegaron de una sesión de Magnific el 15 de septiembre de 2026 (catálogo Freepik y cinco
imágenes generadas); se recortaron a su hueco y están en `manifiesto-imagenes.md`.

Abiertas:

- **Sin artboards móviles de 390**: 1024 y 767 se diseñaron al derivar.
- **Sin página de detalle** (arriba).
- **Retratos generados**: las cuatro caras son inventadas y se sustituyen siempre por el equipo real.
- **Fotos repetidas** entre Inicio y El box / Coaches / Planes, como en el lienzo, donde Inicio resume.
- **Marcas reales en el copy de equipamiento** (Rogue, Concept2, Assault Bike, SkiErg) y titulaciones CF-L:
  describen material y formación, no afiliación; revisar con el cliente.
- **`enfoques.md`** no tiene columna «En `forja`» y la posición de densidad y acento de `directo` no cubre esta
  Plantilla.

**Sin veredicto todavía.** Contraste (`color.php --maqueta`, 0 fallos), velo (`scrim.php`) y barrido (once
páginas a 430, 768 y 1280) medidos; faltan `blind-judges` —con el juez A mirando contra `amalia` y
`escuadra`— y `visual-verification`. Sin los dos, esta plantilla no se ofrece a un cliente.
