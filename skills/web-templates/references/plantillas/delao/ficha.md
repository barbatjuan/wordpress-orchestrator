---
slug: delao
nombre: Inmobiliaria de la O
tipo: corporate
sector: inmobiliaria residencial de alto valor
objetivo: cartera-curada
enfoque: editorial
paginas: [inicio, propiedades, ficha, nosotros, contacto, gracias, aviso-legal, privacidad, cookies, 404]
fuentes: [instrument-serif, archivo]
canvas_url: https://claude.ai/code/artifact/eab59854-20dc-4f8e-831e-ef51375aefbe
variantes:
  hero: [retrato, buscador]
html_widgets_max: 0
css_custom_max: 0
---

# Inmobiliaria de la O · cartera curada

## Para qué sirve

Un negocio que publica **una cartera corta de piezas caras** y vende por la fotografía y
por el criterio de quien las elige, no por el volumen ni por el filtro. Diecisiete mandatos
activos, no mil anuncios. La conversión es doble y las dos importan: quien compra pide
visita, quien tiene una casa pide valoración.

Sirve igual para promotora boutique, galería, náutica de ocasión de gama alta o cualquier
cartera donde el catálogo entero cabe en una página y cada pieza merece la suya.

## Para qué NO sirve

Un inventario volátil que se filtra antes de mirarse: eso es `stock-ocasion`, donde los
filtros van arriba porque filtrar es la primera intención. Aquí el buscador es una banda
secundaria y la portada la manda una fotografía.

## ADN — lo que no se toca al adaptarla

- **La portada es una casa, no un buscador.** El velo es horizontal y claro, sube desde el
  lado del texto, y la tipografía se lee encima sin invertir el color. Un hero oscuro con
  texto blanco y una tarjeta flotante redondeada es otra plantilla.
- **La rejilla dibuja con el hueco.** Las tarjetas de propiedad se separan con `gap: 1px`
  sobre un fondo entintado: la línea es el hueco, no un borde por tarjeta. Cambiarlo por
  tarjetas con sombra rompe el carácter.
- **La cifra de la cartera está en portada.** Mandatos activos y precio medio de cierre,
  en un panel con filete, sobre la propia fotografía. Es la prueba de que la cartera es
  corta a propósito.
- **La zona es texto apagado, nunca acento.** El acento se gasta en las cuatro funciones
  del sistema; una etiqueta de zona es una ayuda de lectura, no una llamada.
- **Doble conversión, dos destinos distintos.** Solicitar visita va a contacto; valorar mi
  casa va a contacto con otro asunto. Ninguna de las dos vuelve a la portada.

## Qué se cambia para un cliente

| Se cambia | Se conserva |
|---|---|
| Marca, logotipo y nombre | La estructura de las diez páginas |
| Los dos colores de fondo y el acento (re-medido) | El papel de cada color: fondo, alterno, tinta, acento |
| Las doce fotografías | Los encuadres: portada apaisada, mosaico de ficha, retratos de equipo |
| El copy entero | La longitud: un titular de dos líneas, no de cuatro |
| Cuántas propiedades hay | Que la rejilla nunca deje una pista vacía |
| El par tipográfico | Que sea display de alto contraste + palo seco de texto |

Si el cliente tiene más de unas cuarenta propiedades y se eligen por zona antes que por
fotografía, esta plantilla no es la suya: es la señal de cambiar de objetivo.

## Mapeo nativo

Elementor, sin un solo widget HTML y sin CSS a medida. Los colores y las tipografías viven
en los ajustes globales del sitio, no en reglas: eso es lo que hace alcanzable el techo de
cero. Divi queda declarado pero **no validado**, igual que en el resto del framework.

### El raíl: 2,778 % con tope en 1360 px

**Margen de página declarado: `clamp(20px, 2,778 %, 40px)`, con el contenido topado en 1360 px.**
Es la ley de composición de la lámina, no una preferencia: las siete láminas escriben TODAS sus
bandas —cabecera, secciones y pie— como `max-width:1440px; margin:0 auto; padding:0 40px`, o sea
40 px de margen y 1360 px de contenido sobre un artboard de 1440. El 2,778 % da exactamente esos
40 px a 1440 y sigue siendo fracción entre los dos puntos de corte (28,4 px a 1024, 21,3 px a 768,
20 px de suelo a 430), que es lo que impide que aparezca un tercer margen donde no hay punto de
corte. Por encima de 1440 manda el tope y el margen crece solo: 160 px a 1680, 280 px a 1920.

Es un margen MÁS ESTRECHO que el 7,5 % de la casa, y a propósito: el objetivo es una cartera corta
en la que la fotografía llega casi al cristal y la rejilla de tres piezas mide 453 px por tarjeta.
Un 7,5 % dejaría la misma página con 1224 px de contenido y tarjetas de 407, que es otra plantilla.
El barrido lo confirma a tres anchos y no señala nada al cristal.

En nativo son dos controles del contenedor y ninguna regla: **Diseño › Ancho de contenido: En caja,
1360 px** (el tope) y **Relleno lateral del contenedor** (el margen), con su valor por punto de
corte. No hay `max-width` en una clase ni margen automático que un item de rejilla pueda desactivar.

| Sección | Elementor (nativo) | Divi | Nota |
|---|---|---|---|
| Cabecera con menú | Plantilla de cabecera del Theme Builder: contenedor flex + Logotipo + Menú de navegación + Botón | Theme Builder | El menú nativo ya trae el desplegable móvil; no se dibuja a mano |
| Portada con velo | Contenedor con imagen de fondo + superposición + contenedor flex interior | Sección con fondo + superposición | El velo es la superposición nativa del contenedor, con su ángulo |
| Rejilla de reglas de la portada | Contenedor rejilla de 4 columnas, sin contenido, con bordes laterales | Fila de 4 columnas | Decorativa; se oculta por debajo de 1024 |
| Panel de cifras | Contenedor flex con borde izquierdo + dos contenedores de texto | Módulo de texto | El filete es el borde del contenedor |
| Banda de búsqueda | Contenedor de fondo invertido + campos del formulario nativo | Módulo de formulario | Es el conmutador `hero: buscador` cuando se activa |
| Rejilla de propiedades | Contenedor rejilla, hueco 1px, fondo entintado; cada tarjeta un contenedor con Imagen + Encabezado + Texto | Fila + módulos | El hueco dibuja la línea. Columnas por punto de ruptura con los controles nativos |
| Banda oscura de valoración | Contenedor a dos columnas 1.15fr/1fr con fondo de tinta + Botón + enlace de teléfono; la foto es un widget Imagen con Ajuste del objeto «Cubrir» dentro de la segunda columna | Sección de dos columnas | El segundo destino es un enlace `tel:`, no un segundo botón. **Quien fija el alto de la banda es la columna de texto, nunca la foto**: `delao-cta.webp` es vertical (800×1067) y en la maqueta llegó a estirar la banda a 843,7 px contra los 536,1 de la lámina. En Elementor no hay nada que escribir —el widget vive en su propio contenedor, que se estira solo—; en la maqueta, que no lleva ese contenedor, la imagen se escribe `height:0;min-height:100%` para no aportar su alto intrínseco al medir la fila |
| Cabecera partida de página interior | Contenedor rejilla 1.5fr/1fr con 44 px de relleno inferior y borde inferior | Fila 2 columnas | Compartida por todas las internas, las de sistema incluidas. **Dos variantes, y las dos las dibuja la lámina**: con filete (1.5fr/1fr) en /nosotros, /contacto y las cinco derivadas; sin filete y a 1.4fr/1fr en /propiedades y el 404, porque debajo entra la banda oscura de búsqueda y un filete sobre una banda a sangre son dos cierres seguidos |
| Barra de filtros | Contenedor flex + campos nativos, pegajosa arriba | Módulo de formulario | Apila por debajo de 900 con el control nativo |
| Mosaico de la ficha | Contenedor rejilla 2fr/1fr + Imagen | Galería | Colapsa a una columna por debajo de 767 |
| Tabla de características | Widget Lista de iconos o Tabla | Módulo de texto | Diez filas clave-valor; incluye certificado energético y gastos |
| Panel de visita | Contenedor pegajoso + Formulario | Módulo de formulario | Pegajoso sólo por encima de 1024 |
| Equipo | Contenedor rejilla, columnas = número de retratos | Fila | Nunca más columnas que personas |
| Pie | Plantilla de pie del Theme Builder | Theme Builder | Aviso legal, Privacidad y Cookies enlazan a sus tres páginas |
| Gracias | Página: cabecera partida (contenedor rejilla + Encabezado + Editor de texto) + contenedor rejilla 1fr/1.5fr con Encabezado pegajoso y tres contenedores de paso (Encabezado con el número + Encabezado + Editor de texto, separados por hueco de 1px sobre fondo entintado) + contenedor de cierre con fondo alterno, Botón y Botón con borde inferior como único borde | Página + módulos de texto y botón | Destino de la acción «Redirigir» del widget Formulario nativo en contacto, visita y valoración. Nombra el plazo: 24 horas laborables. Sin salida muerta: propiedades e inicio |
| Aviso legal · Privacidad · Cookies | Una maqueta, tres páginas. Cabecera partida con la fecha de actualización en un Editor de texto; debajo, contenedor rejilla 1fr/1.5fr: a la izquierda contenedor pegajoso (Efectos de movimiento › Sticky, «permanecer en columna») con Encabezado + una entrada por apartado: contenedor rejilla 30px/1fr con etiqueta HTML `a` enlazado a su ancla, borde inferior y dos Encabezados (número en mono apagado y título); a la derecha contenedor de ancho máximo 31em con, por apartado, Ancla de menú + Encabezado (número) + Encabezado (título) + Editor de texto, y el filete entre apartados como borde superior del contenedor | Página + módulos de texto | La medida de 31em a 16px da 64–67 caracteres de media por línea, contados en el render. El salto de ancla de Elementor descuenta la cabecera pegajosa activa: se comprueba en el build, y si no lo hiciera se quita el pegado de la cabecera en estas tres, no se añade CSS |
| Fichas de datos legales | Contenedor con fondo del color del filete y hueco de 1px; cada fila un contenedor rejilla 10.5em/1fr con dos Encabezados (etiqueta con etiqueta HTML `span`, valor con `p`); columnas a 1 por debajo de 767 | Módulo de texto | Titular del aviso legal, los seis tratamientos de privacidad y la tabla de cookies (nombre en Encabezado de tipografía mono; proveedor, finalidad, duración y tipo en filas). Una tabla HTML pediría estilos a mano: por eso son filas |
| 404 | Plantilla «404» del Theme Builder: cabecera partida con Encabezado, Editor de texto, Botón y Botón con borde inferior + la banda de búsqueda y la rejilla de propiedades de la portada insertadas con el widget Plantilla | Theme Builder, plantilla 404 | Toda ruta inexistente cae aquí, nunca en la portada. Ofrece búsqueda, cartera e inicio, con cabecera y pie |

**Techo declarado: cero widgets HTML y cero reglas de CSS a medida.** Si al construir
apareciera una sección que no cabe en esta tabla, la sección se rediseña; no se abre una
excepción sin escribirla aquí con su razón.

## Variantes

| Variante | Valores | Por defecto | Qué cambia |
|---|---|---|---|
| `hero` | `retrato`, `buscador` | `retrato` | Con `buscador`, la banda de búsqueda sube a la portada y el panel de cifras baja a la segunda sección. Es para una cartera urbana de volumen medio |

## Páginas

Diez, el juego completo de un sitio corporativo: cinco de contenido —inicio, propiedades,
ficha, nosotros y contacto— y cinco de sistema —gracias, aviso legal, privacidad, cookies y
404—. Las de sistema no tienen lámina: se derivan en la maqueta del sistema de las otras cinco
(`canvas/MANIFIESTO.md`). No hay términos y condiciones porque el sitio no contrata en línea;
las condiciones de uso viven en el aviso legal.

Los textos legales de la maqueta describen una empresa ficticia. En un encargo se reescriben
con los datos reales del cliente mediante `wordpress-legal`; nunca se publican tal cual.

Cada propiedad de la cartera arrastra **su propia ficha**: la rejilla dice «ver ficha» y un
botón que no lleva a ninguna parte es el defecto que ya costó nueve enlaces muertos en el
catálogo anterior.

## Procedencia

El diseño no nace aquí: viene de un canvas de Claude Design que el usuario aprobó, y esa es
la autoridad. `canvas/` guarda sus siete artboards y `canvas/MANIFIESTO.md` explica de dónde
salieron y las dos decisiones ya tomadas sobre ellos: Instrument Serif sustituye a Libre
Caslon Display, que la casa no tiene, y el acento pasa de `#8A7B5C` a `#8A5A2A` porque el
primero no supera 4,5:1 sobre ninguno de los dos fondos.

La maqueta de `maqueta/` se DERIVA del canvas. El canvas no trae ni una media query: el
comportamiento estrecho es trabajo de diseño hecho al derivar, con dos puntos de ruptura,
1024 y 767, que son los dos que Elementor sabe expresar de forma nativa.
