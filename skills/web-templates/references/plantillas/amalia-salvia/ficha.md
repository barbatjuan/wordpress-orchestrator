---
slug: amalia-salvia
nombre: Amalia · Sala verde
tipo: corporate
sector: estudio de yoga con sala propia y clases en directo por vídeo
objetivo: clase-de-prueba
enfoque: editorial
paginas: [inicio, filosofia, planes, clase, diario, entrada, contacto, gracias, aviso-legal, privacidad, cookies, 404]
fuentes: [cormorant-garamond, jost]
canvas_url: ""
variantes: {}
html_widgets_max: 0
css_custom_max: 0
---

# Amalia · Sala verde · una sala tranquila que se llena por horario

Segunda dirección del mismo encargo que `amalia`. El paquete de traspaso trae dos direcciones
completas con el mismo contenido y las mismas páginas; ésta es la **B, «sereno verde»**: papel verde
claro, tinta bosque, acento salvia, un raíl lateral fijo en lugar de cabecera y un pase de
diapositivas a sangre en lugar de un titular asimétrico. La marca, ficticia, es la misma: Amalia.

## Para qué sirve

Un estudio que **llena un horario de clases en grupo pagado por cuota mensual**: yoga, pilates,
danza, respiración, cualquier sala con grupos reducidos y un horario semanal fijo. La conversión
principal es la **clase de prueba gratuita**; la secundaria, el correo de quien descarga la guía de
siete días. El sitio lleva al visitante de «esto es la sala y así se trabaja» a «esta es la clase que
me encaja, este es su horario y la primera no la pago», y sostiene la cuota con tres planes y un
horario semanal a la vista.

Objetivo nuevo, `clase-de-prueba`: llenar un horario de clases que se paga por cuota mensual, con la
clase de prueba gratuita como conversión y un recurso gratuito (la guía) como lead. Ver la fila que
se propone para `recomendador.md` en «Procedencia y decisiones abiertas».

## Para qué NO sirve

- **Un centro que vende sesiones de tratamiento en bono** (estética, masaje): eso es `ritual-bono`,
  donde se elige por zona del cuerpo y no por horario. `lumiere` lo cubre.
- **Un programa que termina** (reto de 8 semanas, formación de profesores de 200 horas, preparación
  al parto): eso es `plan-fases`, con fases y cuota que acaba.
- **Una consulta con acto sanitario** (fisioterapia, pilates terapéutico con diagnóstico): eso es
  `pedir-cita`. Amalia avisa en el aviso legal de que sus clases no son tratamiento.
- **Un gimnasio de cien actividades.** El horario de Planes son once clases en siete filas; un
  horario de cuarenta clases diarias pide una rejilla con filtros, no esta lista.

## ADN — lo que no se toca al adaptarla

- **El raíl lateral fijo.** Marca, cinco enlaces, «Clase de prueba» y los datos de la sala viven en
  una columna de 250px que acompaña todo el recorrido en escritorio. Una cabecera horizontal
  convierte esta Plantilla en la dirección A. Por debajo de 1024 es una barra con un único botón de
  menú.
- **La primera pantalla es la sala, no un titular.** Tres diapositivas a sangre bajo un velo oscuro
  plano, con el texto centrado. Cada una trae su propio llamado y la guía de siete días como segundo
  botón.
- **Un solo acento, gastado en poco.** El salvia `#4F6F52` marca la palabra en cursiva de un
  titular, los números de orden, los roles y «Ver clase →». Los botones principales son tinta; el
  acento sólo aparece en su `:hover`. Pintar los botones de salvia es otra Plantilla.
- **Filetes, no tarjetas con sombra.** Los bloques se separan por filetes de 1px y por el fondo
  alterno. La única tarjeta con contorno es la de clase. Ninguna sombra en ningún sitio.
- **La cuota y el horario siempre juntos.** Planes enseña los tres planes y, justo debajo, el
  horario semanal. Un plan sin horario al lado es el defecto que este ADN existe para impedir.
- **La clase de prueba es gratis en todas partes.** La tira de disciplinas, la tercera diapositiva,
  el cierre de cada clase y las preguntas frecuentes lo dicen con las mismas palabras.

## Cómo se separa de la dirección A

Las dos direcciones comparten contenido y páginas, así que lo que las distingue es todo lo demás, y
está decidido para que no lean como la misma mano (datos de la dirección A tomados del README del
traspaso, no de su carpeta):

| | `amalia` (A · editorial cálido) | `amalia-salvia` (B · sala verde) |
|---|---|---|
| Suelo | papel crudo `#F6F2EA`, tinta cálida | papel verde claro `#F2F5F0`, tinta bosque |
| Acento | terracota | salvia |
| Navegación | cabecera horizontal | raíl lateral fijo |
| Primera pantalla | titular de 148px asimétrico + foto vertical en arco | pase de tres fotos a sangre con velo y texto centrado |
| Composición | retícula asimétrica | rejillas estrictas de tres |
| Par tipográfico | Instrument Serif + Archivo | Cormorant Garamond + Jost |
| Fotografía | estudio cálido, luz ámbar | luz de día, blanco y verde, plantas |

## Qué se cambia para un cliente

| Se cambia | Se conserva |
|---|---|
| Disciplinas, clases, profesoras y sus horarios | Una ficha por clase con duración, nivel, quién la imparte y horario |
| Los tres planes y sus precios | Tres planes, uno señalado, pago anual con el mismo porcentaje en todos |
| Las 22 fotografías | Luz de día; la foto de fondo del pase siempre bajo velo re-medido con `scrim.php` |
| La guía gratuita | Un recurso gratuito como segunda conversión, nunca en la primera pantalla sola |
| El par tipográfico | Una serifa de display fina con cursiva + un palo seco geométrico ligero |
| El verde (re-medido) | Un solo acento, reservado a énfasis y etiquetas |

## Paleta medida

Medida con `color.php`, no copiada del lienzo. Cuatro colores y el velo están **cambiados** respecto al
traspaso, en el lienzo y en la maqueta a la vez. Cada cambio es el menor oscurecimiento (o
aclarado, sobre tinta) que conserva el matiz: los canales RGB se escalan juntos hasta cruzar el
umbral.

| Papel | Color | Sobre | Contraste |
|---|---|---|---|
| Suelo · papel | `#F2F5F0` | — | — |
| Suelo · alterno (raíl, bandas) | `#E7EDE4` | — | — |
| Tinta (y suelo de las bandas oscuras) | `#1C2A21` | papel / alterno | 13,61:1 / 12,57:1 |
| Texto intenso (artículo) | `#2A352C` | papel / alterno | 11,61:1 / 10,72:1 |
| Texto | `#3C4A3F` | papel / alterno | 8,51:1 / 7,86:1 |
| Texto secundario, **re-medido** | `#626D61` (antes `#7B8A7A`, 3,32:1 / 3,06:1) | papel / alterno | 4,92:1 / 4,54:1 |
| Acento salvia | `#4F6F52` | papel / alterno | 5,12:1 / 4,73:1 |
| Borde de campo, **re-medido** (interfaz, 3:1) | `#81897D` (antes `#C3CFBD`, 1,47:1) | papel / alterno | 3,29:1 / 3,04:1 |
| Papel sobre acento (etiqueta, hover de botón) | `#F2F5F0` | `#4F6F52` | 5,12:1 |
| Sobre tinta · texto | `#D4DED2` | `#1C2A21` | 10,82:1 |
| Sobre tinta · apagado | `#A3B4A4` | `#1C2A21` | 6,86:1 |
| Sobre tinta · rótulo | `#94A695` | `#1C2A21` | 5,81:1 |
| Sobre tinta · tenue, **re-medido** | `#819281` (antes `#7F907F`, 4,42:1) | `#1C2A21` | 4,54:1 |
| Salvia sobre tinta | `#8FB48C` | `#1C2A21` | 6,47:1 |
| Tinta sobre salvia clara (etiqueta en banda oscura) | `#1C2A21` | `#8FB48C` | 6,47:1 |
| Velo de diapositiva, **re-medido** | `rgba(24,36,28,.72)` plano (antes degradado `.48 → .72`) | peor píxel de las tres fotos | papel 5,79:1 · `#E3EADF` 5,19:1 · `#D4DED2` 4,60:1 |
| Filete y puntos de los controles del pase | `rgba(242,245,240,.6)` (antes `.45–.55`) | blanco bajo el velo | 3,2:1 |

`color.php --maqueta` re-mide los doce pares claros directamente del `:root` de `maqueta/index.html`
y sale en `0`. Los tokens que sólo existen sobre tinta (`--c-sobre-tinta-*`, `--c-salvia-sobre-tinta`)
no llevan `text`, `accent`, `bg` ni `border` en el nombre a propósito, igual que en `aranda` y
`terrazza`: el barrido automático los mediría contra los suelos claros, donde nunca se pintan. Por
eso se miden aquí con `--contraste`. El velo se midió con `scrim.php --peor-pixel` sobre la región
del texto a 1280 y a 430: las tres fotos tienen blanco puro bajo el titular, y a 0,48 el antetítulo
medía 2,18:1. Los filetes decorativos (`#D3DCCF`, `#C3CFBD`, `#DCE4D7`) separan y no identifican
ningún control, así que no llevan umbral.

## El margen medido no es el 7,5 %

El lienzo centra el contenido con `max(clamp(20px, 4vw, 64px), calc((100% - 1120px) / 2))` dentro de
la columna que deja el raíl. A 1440, la columna mide 1190px y el margen es el `4vw`: **4 % del ancho
por lado, junto a un raíl de 250px**, con la medida topada en 1120px. La maqueta usa exactamente esa
expresión como `--page-margin`, así que viaja como fracción entre los dos puntos de ruptura. El raíl
ya ocupa el aire que en otras Plantillas pone el margen izquierdo; sumar el 7,5 % dejaría una columna
de lectura de 900px en un escritorio de 1280.

## Enfoque, eje por eje

Leído en el lienzo (`canvas/AmaliaSalvia.dc.html`), no en el nombre de la dirección:

| Eje | Posición | Evidencia en el lienzo |
|---|---|---|
| Escala | editorial | H1 del pase `clamp(46px, 6.2vw, 104px)` en Cormorant 400, unas 5,5 veces el cuerpo de 16px; titulares de dos líneas, no de cuatro. No es monumental: la dirección A es la de 148px |
| Densidad | generosa | Relleno de sección `clamp(64px, 12vh, 140px)`, huecos de 20–44px, medida topada en 1120px |
| Fondo | frío claro | Papel `#F2F5F0` y alterno `#E7EDE4`, verde grisáceo; no es papel blanco neutro ni cálido |
| Elevación | filete | «Sombras: ninguna; los contornos se hacen con border 1px»: tarjetas de clase con borde, «Seguir leyendo» con contorno |
| Composición | rejilla estricta | Clases 3×2, equipo 3, planes 3, diario 3, «Otras clases» en fila; sólo el pase va centrado y Filosofía a `.8fr / 1.2fr` |
| Acento | reservado | Salvia en una palabra en cursiva por titular, números, roles y «Ver clase →»; botones en tinta |
| Chasis | dividido por filetes | Cifras, planes, horario, ficha de clase, principios, preguntas y «Cómo llegar», todos separados por filetes de 1px |
| Ornamento | filete | La línea que sigue a «03 — Equipo» y a «Otras clases»; el filete vertical animado de la guía salió por no ser nativo |

Coincide con `editorial` en **cuatro de ocho ejes** (escala, densidad, chasis, ornamento) y en su par
tipográfico («display con serifa de alto contraste + palo seco»); ningún otro enfoque pasa de dos
(`institucional` y `tecnologico` coinciden en fondo, acento o composición, pero no en escala ni en
chasis). Los cuatro que no coinciden (fondo frío en vez de papel blanco, elevación y ornamento por
filete, rejilla estricta en vez de asimétrica) son justo lo que la separa de `delao`.

**Conflicto abierto, no resuelto aquí.** La dirección A (`amalia`) comparte tipo y Objetivo con esta
Plantilla y, según el encargo, casi seguro también `editorial`. Dos Plantillas con el mismo tipo +
Objetivo + Enfoque están prohibidas por `enfoques.md`. Los ejes de esta lámina dicen `editorial`, así
que se escribe `editorial` y el conflicto lo decide el coordinador, en lugar de forzar un id que el
lienzo no sostiene. Si hubiera que moverla, el eje más discutible es la composición: la Plantilla se
podría leer como `centrada` por el pase de diapositivas, pero sólo la primera pantalla lo es.

## Mapeo nativo

**Propuesto, no verificado todavía contra el vocabulario nativo introspeccionado.** Elementor Pro,
sin un solo widget HTML y sin CSS a medida. La primera columna son los ids de sección de
`maqueta/index.html` (`NN` = número de clase o de entrada).

| Sección | Elementor (nativo) | Nota |
|---|---|---|
| `#rail` (raíl → barra) | Plantilla de página única del Theme Builder: contenedor de dos columnas, raíl de 250px con Efectos de movimiento › Fijo (arriba, «permanecer en la columna») + widget «Contenido de la entrada». En el raíl: Encabezado (marca) + Menú de navegación vertical + Botón + Editor de texto | Por debajo de 1024 el contenedor pasa a fila y el botón de menú abre el widget **Off-Canvas** con el menú, el botón y los datos. Ningún `overflow` en el padre: rompe el fijo |
| `#inicio-diapositivas` | **Carrusel anidado**: tres diapositivas, cada una un contenedor con imagen de fondo + Superposición de fondo `rgba(24,36,28,.72)` + Encabezado (antetítulo) + Encabezado (H1 en la primera, H2 en las demás) + Editor de texto + dos Botones. Flechas, paginación por puntos, reproducción automática 7000 ms, pausa al pasar el ratón | El widget **Diapositivas** sólo da título, descripción y **un** botón por diapositiva: perdería el antetítulo y el botón de la guía. El contador «01 / 03» sale: la paginación nativa es puntos **o** fracción. La maqueta no avanza sola con `prefers-reduced-motion`; si el widget no lee esa preferencia, en el build la reproducción automática va apagada, no se añade código |
| `#inicio-disciplinas` | Contenedor fila con envoltura y filete arriba y abajo: cinco Encabezados + Botón de texto «Primera clase gratuita» | |
| `#inicio-filosofia` | Contenedor rejilla `.8fr / 1.2fr`: columna izquierda con Efectos de movimiento › Fijo (sólo escritorio y tableta) con Encabezado + Imagen; derecha Encabezado H2 (la cursiva es `<em>` en el propio texto) + contenedor de dos Editores + contenedor de tres cifras (dos Encabezados cada una, borde izquierdo) + Botón de texto | Cifras en columna por debajo de 767 |
| `#inicio-clases` | Contenedor con fondo alterno: cabecera (Encabezado + H2 + Editor, borde inferior) + **Loop Grid** del tipo de contenido Clase, 3 / 2 / 1 columnas; plantilla de tarjeta: contenedor enlazado con borde 1px + Encabezados dinámicos (número, nivel, nombre) + Extracto + fila de pie | Hover sólo de color de borde (control nativo); el `translateY` del traspaso salió |
| `#inicio-equipo` | Contenedor rejilla 3 / 3 / 1: Imagen + Encabezado + Encabezado (rol) + Editor | Nunca más columnas que personas |
| `#inicio-planes`, `#planes-tabla` | **Pestañas** (widget nativo anidado): «Mensual» y «Anual · −15 %», cada pestaña un contenedor rejilla 3 / 3 / 1 con bordes internos; plan = contenedor (Encabezado + etiqueta «Lo más elegido» como Encabezado con fondo) + Editor + Encabezado de precio + Editor con lista + Botón | Elementor Pro no tiene un conmutador de precios; las pestañas son el control nativo y la maqueta las implementa con `role="tab"`. «Tabla de precios» obligaría a su estructura fija de cabecera |
| `#inicio-diario` | Cabecera + Loop Grid de entradas, 3 / 3 / 1, tarjeta = Imagen destacada + Info de la entrada (categoría, minutos) + Título | |
| `#inicio-guia` | Contenedor con fondo alterno y radio 6px, dos columnas: Encabezado + H2 + Editor; **Formulario**: Nombre, Correo, Aceptación (obligatoria, enlaza a Privacidad), acción Redirigir a Gracias + la integración de correo del cliente | |
| `#inicio-visita` | Dos columnas: Encabezado + H2 + filas de horario (contenedor por fila con dos Encabezados y borde inferior) + Lista de iconos; **Mapa de Google** | En la maqueta el mapa es un bloque estático con la dirección: ni iframe ni recurso remoto. En el build se carga tras consentimiento (ver Cookies) |
| `#filosofia-cabecera`, `#planes-cabecera`, `#diario-cabecera`, `#contacto-cabecera` | Contenedor con borde inferior: Encabezado + H1 (+ Editor con borde izquierdo en Contacto; + Filtro de taxonomía en Diario) | |
| `#filosofia-sala` | Dos columnas: Encabezado (frase en Cormorant) + dos Editores + Imagen | |
| `#filosofia-principios`, `#clase-NN-sesion` | Contenedor con fondo alterno: H2 + rejilla 4 / 2 / 1 de pasos (Encabezado número + H3 + Editor, borde superior) | |
| `#filosofia-cierre`, `#clase-NN-reserva`, `#contacto-cierre` | Contenedor fila con envoltura: H2 + uno o dos Botones | |
| `#planes-horario` | Dos columnas: Encabezado + H2 + Editor; filas por día (contenedor con dos Encabezados, borde inferior) | Una tabla HTML pediría estilos a mano: por eso son filas |
| `#planes-preguntas` | Dos columnas: H2 + **Acordeón** nativo con la primera pregunta abierta | |
| `#clase-NN-cabecera` | Plantilla de entrada individual del tipo Clase (Theme Builder): Encabezado con enlace «Clases / NN» + Título + Extracto + ficha de cuatro filas con Campos dinámicos (duración, nivel, quién imparte, horario) | Las migas son un Encabezado con enlace: el widget de migas depende de un plugin de SEO |
| `#clase-NN-cuerpo` | Dos columnas: Contenido de la entrada + «Qué llevar» (Encabezado + Campo dinámico) + Imagen destacada 5:4 | |
| `#clase-NN-otras` | Loop Grid de 5 columnas (1 por debajo de 1024) con la consulta «excluir la entrada actual» | Nunca incluye la clase abierta |
| `#diario-listado` | Loop Grid de 1 elemento en dos columnas (destacada) + Loop Grid 3 / 2 / 1 conectado al Filtro de taxonomía | Los chips de categoría filtran en la maqueta, como el filtro nativo |
| `#entrada-NN-cabecera` | Plantilla de entrada individual (Theme Builder): Encabezado con enlace a Diario y a la categoría + Título + Extracto con borde izquierdo | |
| `#entrada-NN-imagen` | Imagen destacada con proporción 16:6,5 | |
| `#entrada-NN-cuerpo` | Contenedor fila: columna de 230px con Efectos de movimiento › Fijo (Caja de autor + Info de la entrada + **Botones para compartir** + Botón) + Contenido de la entrada con ancho máximo de 42rem; capitular con la opción nativa «Letra capital» del Editor de texto; cita con el widget Cita | Columna fija sólo en escritorio y tableta; en móvil va después del texto |
| `#entrada-NN-seguir` | Loop Grid de 2 columnas (1 en móvil) de otras entradas, plantilla horizontal miniatura + texto | Dos entradas, nunca una estirada a todo el ancho |
| `#contacto-formulario` | **Formulario**: Nombre, Correo, Teléfono, Motivo (Seleccionar, «Reservar clase de prueba» primero), Mensaje, Aceptación obligatoria; acción Redirigir a Gracias. Aside: Encabezado + Encabezado (dirección) + Lista de iconos + filas de horario | Las columnas del aside apilan por debajo de 1024 |
| `#contacto-mapa` | Mapa de Google + rejilla 4 / 4 / 1 «Cómo llegar» (Encabezado + Editor, borde superior) | Mismo bloque estático en la maqueta |
| `#gracias-cabecera`, `#gracias-pasos` | Encabezado + H1 + Editor; rejilla de tres pasos + dos Botones | Siguiente paso: el horario, no un callejón |
| `#aviso-legal-texto`, `#privacidad-texto`, `#cookies-texto` | Contenedor rejilla: índice con Efectos de movimiento › Fijo + columna de apartados (Encabezado número + H2 + Editor); fichas de datos y lista de cookies como filas de contenedor | Mismo patrón que `lumiere` |
| `#error-404` | Plantilla 404 del Theme Builder: Encabezado + H1 + Editor + tres Botones | |
| Pie | Plantilla de pie del Theme Builder: rejilla 4 / 2 / 1 sobre tinta + fila legal con Aviso legal, Privacidad y Cookies | |
| Revelado al hacer scroll | Animación de entrada nativa (Fundido hacia arriba) por sección | La maqueta no la dibuja: el `IntersectionObserver` del traspaso no es nativo |

**Techo declarado: cero widgets HTML y cero reglas de CSS a medida.** Si al construir apareciera una
sección que no cabe en esta tabla, la sección se rediseña en el lienzo; no se abre una excepción sin
escribirla aquí con su razón.

## Páginas

Las diez de corporate (`paginas-obligatorias.md`) y dos más: **inicio**; **planes** es el «listado o
servicios» del tipo (los tres planes, el horario semanal y las preguntas), con el índice de las seis
clases en `#inicio-clases`; **clase** es el «detalle», donde aterriza cada tarjeta de clase;
**filosofía** es el «nosotros»; **contacto** lleva el formulario de la clase de prueba; **diario** y
**entrada** son las dos páginas que este negocio publica además (lecturas que sostienen la guía y el
posicionamiento); y las cinco derivadas: **gracias**, **aviso legal**, **privacidad**, **cookies** y
**404**.

**Cada tarjeta abre la suya.** `clase` guarda las seis clases y `entrada` las siete entradas como
variantes de una misma plantilla (`#clase-hatha`, `#entrada-savasana`…): el enrutador enseña sólo
la pedida y escribe su título. Es la forma en que la maqueta demuestra la plantilla de entrada
individual del Theme Builder sin que una tarjeta abra el contenido de otra. El barrido mide la
primera variante de cada una.

Las condiciones de los planes (clase de prueba, cuota, pago anual, bono, anulaciones) viven en el
aviso legal (`#aviso-legal-planes`), no inventadas en Planes. Los textos legales describen una
empresa ficticia; en un encargo se reescriben enteros con `wordpress-legal`.

## Procedencia y decisiones abiertas

**De dónde sale.** El lienzo es la dirección B del paquete de traspaso «Amalia Yoga — plantilla web
premium» (`canvas/MANIFIESTO.md`). Las fotografías son de una sesión hecha para este encargo
(`manifiesto-imagenes.md`). No se dispone de la URL del lienzo compartido; queda en blanco.

**Decisiones de derivación que no están en el lienzo tal cual:**

- Los llamados a la clase de prueba llevan al formulario de Contacto (`#contacto-formulario`, motivo
  «Reservar clase de prueba» primero). En el traspaso llevaban a Planes, que no tiene formulario.
- El conmutador de precios son pestañas; la etiqueta «Lo más elegido» va en la fila del nombre para
  que las filas de precio de los tres planes queden alineadas.
- La tarjeta de clase deja caer «Ver clase →» a su propia línea cuando la duración no cabe al lado.
- El formulario de la guía confirma en Gracias igual que el de contacto, para medir las dos
  conversiones.
- Redes sociales y botones de compartir son texto en la maqueta: no hay URL real que enlazar y un
  enlace a `#` es un enlace muerto.

**Lo que salió por no tener mapeo nativo:** `pulseline`, `breathe` y `drawline`; los `transform` de
`:hover`; el contador del pase; el degradado del velo (ahora plano, re-medido); la monoespaciada de
sistema de los números; el revelado por `IntersectionObserver` (queda como animación de entrada
nativa); el mapa embebido (bloque estático). El traspaso no usaba `backdrop-filter`.

**Filas que se proponen para los ficheros del coordinador:**

- `recomendador.md`, catálogo corporate: `clase-de-prueba` · «Llenar un horario de clases en grupo
  pagado por cuota mensual; la conversión es la clase de prueba gratuita y la secundaria, el correo
  de quien pide un recurso gratuito» · «Estudio de yoga, pilates, danza, barre, escalada; "primera
  clase gratis", horario semanal, cuota mensual, grupos reducidos, clases en directo; se vive de la
  recurrencia, no de la sesión suelta». Frontera: frente a `ritual-bono`, se elige por horario y no
  por zona del cuerpo; frente a `plan-fases`, la cuota no termina.
- `recomendador.md`, Objetivo → Plantilla: `clase-de-prueba` · `amalia-salvia` · corporate ·
  `editorial` (conflicto con `amalia` pendiente) · en la biblioteca.

**Sin veredicto.** El contraste de `:root` pasa `color.php --maqueta` (0 fallos), el velo pasa
`scrim.php` sobre las tres fotos, y el barrido de las doce páginas a 430, 768 y 1280 sale limpio; falta
`blind-judges` (juez A contra la biblioteca, en particular contra `amalia`, y juez B sobre esta
maqueta) y `visual-verification` completa. Sin las dos, esta Plantilla no se ofrece a un cliente.
