---
slug: delao
nombre: Inmobiliaria de la O
tipo: corporate
sector: inmobiliaria residencial de alto valor
objetivo: cartera-curada
enfoque: editorial
paginas: [inicio, propiedades, ficha, nosotros, contacto]
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
| Marca, logotipo y nombre | La estructura de las cinco páginas |
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

| Sección | Elementor (nativo) | Divi | Nota |
|---|---|---|---|
| Cabecera con menú | Plantilla de cabecera del Theme Builder: contenedor flex + Logotipo + Menú de navegación + Botón | Theme Builder | El menú nativo ya trae el desplegable móvil; no se dibuja a mano |
| Portada con velo | Contenedor con imagen de fondo + superposición + contenedor flex interior | Sección con fondo + superposición | El velo es la superposición nativa del contenedor, con su ángulo |
| Rejilla de reglas de la portada | Contenedor rejilla de 4 columnas, sin contenido, con bordes laterales | Fila de 4 columnas | Decorativa; se oculta por debajo de 1024 |
| Panel de cifras | Contenedor flex con borde izquierdo + dos contenedores de texto | Módulo de texto | El filete es el borde del contenedor |
| Banda de búsqueda | Contenedor de fondo invertido + campos del formulario nativo | Módulo de formulario | Es el conmutador `hero: buscador` cuando se activa |
| Rejilla de propiedades | Contenedor rejilla, hueco 1px, fondo entintado; cada tarjeta un contenedor con Imagen + Encabezado + Texto | Fila + módulos | El hueco dibuja la línea. Columnas por punto de ruptura con los controles nativos |
| Banda oscura de valoración | Contenedor a dos columnas 1.15fr/1fr con fondo de tinta + Botón + enlace de teléfono | Sección de dos columnas | El segundo destino es un enlace `tel:`, no un segundo botón |
| Cabecera partida de página interior | Contenedor rejilla 1.5fr/1fr | Fila 2 columnas | Compartida por las cuatro internas |
| Barra de filtros | Contenedor flex + campos nativos, pegajosa arriba | Módulo de formulario | Apila por debajo de 900 con el control nativo |
| Mosaico de la ficha | Contenedor rejilla 2fr/1fr + Imagen | Galería | Colapsa a una columna por debajo de 767 |
| Tabla de características | Widget Lista de iconos o Tabla | Módulo de texto | Diez filas clave-valor; incluye certificado energético y gastos |
| Panel de visita | Contenedor pegajoso + Formulario | Módulo de formulario | Pegajoso sólo por encima de 1024 |
| Equipo | Contenedor rejilla, columnas = número de retratos | Fila | Nunca más columnas que personas |
| Pie | Plantilla de pie del Theme Builder | Theme Builder | Enlaces legales incluidos |

**Techo declarado: cero widgets HTML y cero reglas de CSS a medida.** Si al construir
apareciera una sección que no cabe en esta tabla, la sección se rediseña; no se abre una
excepción sin escribirla aquí con su razón.

## Variantes

| Variante | Valores | Por defecto | Qué cambia |
|---|---|---|---|
| `hero` | `retrato`, `buscador` | `retrato` | Con `buscador`, la banda de búsqueda sube a la portada y el panel de cifras baja a la segunda sección. Es para una cartera urbana de volumen medio |

## Páginas

Cinco propias más las no negociables del framework: aviso legal, privacidad, cookies,
términos, error 404, y gracias si hay formulario.

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
