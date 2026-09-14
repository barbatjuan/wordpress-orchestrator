---
slug: aranda
nombre: Motor Aranda
tipo: corporate
sector: compraventa de vehículos de ocasión, concesionario multimarca
objetivo: stock-ocasion
enfoque: tecnologico
paginas: [inicio, listado, detalle, nosotros, contacto, gracias, aviso-legal, privacidad, cookies, 404]
fuentes: [archivo, inter-tight]
variantes: {}
html_widgets_max: 0
css_custom_max: 0
---

# Motor Aranda · el patio revisado, unidad por unidad

## Para qué sirve

Un concesionario que mueve un **inventario que rota cada semana**: 34 coches en el patio hoy, otros
la semana que viene. Quien entra filtra por marca, precio, año, combustible y kilómetros antes de
mirar una sola foto, y cada unidad lleva su propia ficha con lo que se le hizo de verdad — no un
adjetivo («revisado») sino una lista («pastillas delanteras sustituidas, correa de distribución
cambiada en 2025»). El acento es el dato medido, no la frase de marca.

Sirve para concesionarios multimarca de ocasión, maquinaria, caravanas o cualquier stock de unidades
intercambiables entre sí que se compra y se vende por atributos filtrables.

## Para qué NO sirve

Una cartera corta de piezas caras que se eligen por fotografía y criterio: eso es `cartera-curada`
(`delao`). Aquí no hay «mandatos» ni «exclusivas»; hay 34 referencias con matrícula, y la semana que
viene son otras.

## ADN — lo que no se toca al adaptarla

- **La cifra del stock está en todas partes.** «34 coches», «137 puntos», «12 meses de garantía»,
  «48 h para la entrega»: la cabecera del hero es un panel de cifras tabulares, no una frase sola.
- **El listado es una tabla que se compara, no una vitrina que se hojea.** Referencia, año,
  kilómetros, combustible, cambio, estado de la revisión y precio en la misma fila — la comparación
  entre dos coches no pasa por abrir dos fichas.
- **La ficha demuestra la revisión, no la promete.** «Qué cubrió la revisión de entrega» nombra lo
  que se sustituyó y lo que no, con fecha y kilometraje. Cortar esta sección deja la garantía sin
  prueba.
- **Todo número clave lleva `font-variant-numeric: tabular-nums`.** Matrículas, años, kilómetros,
  precios y cuotas se alinean como cifras, nunca como texto corrido — es la expresión tipográfica del
  Enfoque `tecnologico` sin recurrir a una monoespaciada completa.
- **El acento teal (`--c-accent`) marca lo interactivo y lo medido — nunca decoración.** Enlaces,
  botones, la línea «137 puntos · fecha» de cada fila y las cuotas destacadas de financiación.

## Qué se cambia para un cliente

| Se cambia | Se conserva |
|---|---|
| Marca, logotipo y nombre | La estructura de las diez páginas |
| El acento teal (re-medido) y los dos fondos claros | El papel de cada color: fondo, alterno, tinta, texto secundario, acento |
| Las fotografías | Que el listado sea tabla, no vitrina; que la ficha demuestre la revisión |
| El copy y las cifras de la revisión | Que cada unidad muestre matrícula/ref., año, km, combustible, cambio y precio |
| Cuántas unidades hay en stock | Que el listado sea comparable en una sola fila por unidad |
| El par tipográfico | Que sea grotesca de trazo firme (titulares) + palo seco de texto, con cifras tabulares |

## Paleta medida

Medida con `skills/html-mockup/assets/herramientas/color.php --contraste`, nunca copiada de la
portada. **Ningún número de esta tabla está adivinado.**

| Papel | Color | Sobre | Contraste |
|---|---|---|---|
| Suelo | `#F3F6F7` | — | — |
| Suelo alterno | `#E4EAED` | — | — |
| Superficie (tarjetas, formularios) | `#FFFFFF` | — | — |
| Tinta primaria | `#111A1F` | `#F3F6F7` | 16,23:1 |
| Tinta primaria | `#111A1F` | `#E4EAED` | 14,51:1 |
| Tinta primaria | `#111A1F` | `#FFFFFF` | 17,63:1 |
| Texto secundario / etiquetas | `#55666E` | `#F3F6F7` | 5,51:1 |
| Texto secundario / etiquetas | `#55666E` | `#E4EAED` | 4,92:1 |
| Texto secundario / etiquetas | `#55666E` | `#FFFFFF` | 5,98:1 |
| Acento (teal) | `#0B5D6B` | `#F3F6F7` | 6,92:1 |
| Acento (teal) | `#0B5D6B` | `#E4EAED` | 6,19:1 |
| Acento (teal) | `#0B5D6B` | `#FFFFFF` | 7,52:1 |
| Acento hover | `#073F49` | `#F3F6F7` | 10,65:1 |
| Texto de botón sobre acento | `#FFFFFF` | `#0B5D6B` | 7,52:1 |
| Superficie oscura (utilidad, hero partido, pie, cierres) | `#111A1F` | — | — |
| Tinta sobre superficie oscura | `#F3F6F7` | `#111A1F` | 16,23:1 |
| Tinta secundaria sobre superficie oscura | `#B9C6CC` | `#111A1F` | 10,08:1 |

**Un gris que la portada usa y esta ficha descarta.** `canvas/Aranda.dc.html` pinta etiquetas
pequeñas (año, kilómetros, combustible, «unidades hoy», referencias) en `#7F929B`. Medido:

| Color | Sobre | Contraste |
|---|---|---|
| `#7F929B` | `#F3F6F7` | 2,98:1 — bajo el 4,5:1 de AA |
| `#7F929B` | `#FFFFFF` | 3,24:1 — bajo el 4,5:1 de AA |
| `#7F929B` | `#E4EAED` | 2,67:1 — bajo el 4,5:1 de AA |

Sólo pasa sobre fondo oscuro (`#111A1F`, 5,45:1). Es un fallo de contraste real, ya presente en la
portada committeada — no se ha tocado esa lámina porque no era parte de este encargo, pero ni las
cuatro láminas nuevas ni la maqueta reutilizan `#7F929B` para texto sobre fondo claro: usan
`#55666E` en su lugar, que sí pasa AA en los tres fondos de la casa. `color.php --maqueta` re-mide
`--c-text` y `--c-accent` contra las tres `--*bg*` de `:root` y las nueve combinaciones pasan
4,5:1 (ver «Verificación» más abajo).

**`--c-border` no se declara.** Los hairlines de la maqueta son `rgba()` sobre la tinta primaria
(`--hair`, `--hair-suave`, `--hair-inversa`), no un hex plano de «borde»: grep de esta maqueta
confirma que no hay ningún token literal `--c-border*` — un hueco deliberado, no una renombrada para
esquivar el gate de 3:1. `--c-accent` ya pasa 4,5:1 como texto en las tres superficies claras, así
que no hace falta degradarlo a un rol de «sólo interfaz».

## Mapeo nativo

Elementor, sin un solo widget HTML y sin CSS a medida. Divi queda declarado pero **no validado**.

| Sección | Elementor (nativo) | Divi | Nota |
|---|---|---|---|
| Franja de utilidad | Contenedor flex de fondo oscuro, ancho completo | Módulo de texto | Se oculta por debajo de 767 con el control nativo de visibilidad responsive |
| Cabecera con menú | Plantilla de cabecera del Theme Builder: Logotipo + Menú de navegación + Botón | Theme Builder | El menú nativo trae el desplegable móvil; no se dibuja a mano |
| Hero partido (inicio, nosotros) | Contenedor flex de dos mitades: texto sobre fondo oscuro + Imagen | Sección de dos columnas | Apila por debajo de 767 con el control nativo de dirección |
| Panel de cifras del hero | Contenedor flex con borde izquierdo por elemento | Módulo de texto | El filete es el borde del contenedor, no una imagen |
| Banda de filtros | Contenedor flex + seis campos de formulario nativo + Botón | Módulo de formulario | Envuelve por punto de ruptura con el control nativo de wrap |
| Rejilla de stock | Contenedor rejilla de 3 columnas; cada tarjeta un contenedor con Imagen + Encabezado + Loop Grid de datos | Fila + módulos | 3 → 2 → 1 columnas por los controles nativos de columnas responsive |
| Unidad destacada (mosaico + datos + precio) | Contenedor rejilla 2fr/1fr + Imagen + Lista de definición + Botón | Fila 2 columnas | Reutilizado igual en Detalle |
| Qué cubrió la revisión | Widget Lista de iconos, o filas de Encabezado + Editor de texto | Módulo de texto | Seis filas etiqueta/descripción; nunca una tabla HTML |
| Banda de garantías | Contenedor rejilla de 4 columnas + Icono + Encabezado + Editor de texto | Fila 4 columnas | 4 → 2 → 1 por los controles nativos |
| Banda de tasación / cierre oscuro | Contenedor de fondo oscuro a dos columnas + campos de formulario + Botón | Sección de dos columnas | Reutilizada en Inicio y en Nosotros con distinto CTA |
| Tabla de listado | Loop Grid de una fila por unidad, con Loop Item = la fila de datos | Fila + módulos | Colapsa a tres columnas (vehículo, precio, flecha) por debajo de 1024 con el control nativo; el resto de datos se oculta y aparece como una línea de meta dentro de la celda del vehículo, sin duplicar contenido en el DOM del build — dos Loop Item por punto de ruptura, uno con los campos sueltos y otro con la línea combinada |
| Panel de precio y financiación | Contenedor con fondo alterno + Encabezado + Botón + Contenedor rejilla 2×2 | Módulo de texto | Las cuatro cuotas son texto: no hay integrador financiero, se documenta como texto fijo |
| Pasos «cómo revisamos» | Contenedor rejilla de 5 columnas, cada paso un contenedor con Encabezado (número) + Encabezado + Editor de texto | Fila 5 columnas | Reutilizado en Gracias con 3 pasos |
| Banda de cifras | Contenedor rejilla de 4 columnas con borde izquierdo | Fila 4 columnas | Mismo componente que el panel de cifras del hero, sobre fondo alterno en vez de oscuro |
| Equipo | Contenedor rejilla de 3 columnas, cada persona un contenedor con Encabezado + Editor de texto | Fila 3 columnas | Nunca más columnas que personas |
| Formulario de contacto | Widget Formulario nativo (nombre, correo, teléfono, mensaje, casilla de consentimiento) | Módulo de formulario | Acción «Redirigir» a la página Gracias; la casilla enlaza a Privacidad |
| Páginas legales (aviso legal, privacidad, cookies) | Cabecera con fecha + Contenedor rejilla índice/cuerpo + por apartado: Ancla de menú + Encabezado (número) + Encabezado (título) + Editor de texto; fichas de datos como filas rejilla 11em/1fr | Página + módulos de texto | La tabla de cookies usa el widget nativo **Tabla** de Elementor (disponible desde 3.6): nombre, proveedor, finalidad, duración, tipo — es la única sección de la maqueta que usa un `<table>` HTML de verdad, y tiene equivalente nativo directo |
| 404 | Plantilla «404» del Theme Builder: Encabezado + Editor de texto + tres Botones | Theme Builder | Toda ruta inexistente cae aquí, nunca en Inicio |
| Pie | Plantilla de pie del Theme Builder | Theme Builder | Aviso legal, Privacidad y Cookies enlazan a sus tres páginas |

**Techo declarado: cero widgets HTML y cero reglas de CSS a medida.** Si al construir apareciera una
sección que no cabe en esta tabla, la sección se rediseña; no se abre una excepción sin escribirla
aquí con su razón.

## Variantes

Ninguna. La maqueta no implementa ningún conmutador todavía; `variantes: {}` en el frontmatter lo
dice explícitamente en vez de dejarlo en blanco.

## Páginas

Diez, el juego completo de un sitio corporativo (`paginas-obligatorias.md` § «Corporate · 10
páginas»): cinco de contenido —inicio, listado, detalle, nosotros y contacto— y cinco de sistema
—gracias, aviso legal, privacidad, cookies y 404—. Las de sistema no tienen lámina: se derivan en la
maqueta del sistema de las otras cinco (`canvas/MANIFIESTO.md`). No hay página de términos: un
concesionario que no contrata en línea no tiene condiciones que publicar; lo dice
`paginas-obligatorias.md` para corporate.

Los textos legales de la maqueta describen la empresa ficticia Motor Aranda S.L. En un encargo se
reescriben con los datos reales del cliente mediante `wordpress-legal`; nunca se publican tal cual.
La propia página de aviso legal lo dice en su último párrafo.

**El listado dibuja doce unidades y las 34 del stock llevan a la misma ficha.** Sólo el Hyundai
Tucson (Ref. A-2390) tiene lámina de detalle propia; las doce filas del listado —y las seis tarjetas
de la portada— enlazan a ella, siguiendo la misma convención que `barro/canvas/Categoria.dc.html`
(diez piezas de un catálogo de 31) y `escuadra` (el registro de Cocina) ya usaban. La maqueta
demuestra que la página de detalle existe y se alcanza desde el listado y desde la portada, sin un
solo enlace muerto ni una fila decorativa; en el sitio de un cliente real cada unidad tiene su propia
ficha, generada por la plantilla de producto único que corresponda.

## Procedencia y decisiones abiertas

**No hay `canvas_url`.** A diferencia de `delao`, `marzo`, `barro`, `cadencia` o `escuadra`, cuyo
lienzo vive en un artifact publicado de Claude Design, `canvas/Aranda.dc.html` viene del lienzo
interno «seis portadas» (commit `b153c6a`, `feat(plantillas): the six brands of the sixth lienzo
enter the library`), que dibujó seis marcas de la galería antigua sin registrar una URL de artifact
reutilizable por marca. No hay lienzo de Claude Design del que re-sembrar las cuatro láminas nuevas
de esta entrega, así que el frontmatter deja `canvas_url` sin escribir en vez de inventar uno.
`canvas/MANIFIESTO.md` § «De dónde sale» trae el detalle completo.

**El nombre de marca real es «Motor Aranda», no «Aranda Ocasión».** El propio artboard
(`canvas/Aranda.dc.html`) titula la cabecera «MOTOR ARANDA», y el commit de origen lo registra como
«Motor Aranda TPL-C-07» en la galería antigua. `plantillas/_indice.md` lista hoy el slug `aranda`
como «Aranda Ocasión», un nombre descriptivo que no aparece en ningún artboard ni en el commit de
procedencia. Esta ficha usa `nombre: Motor Aranda` porque es lo que dice la autoridad de diseño; la
fila de `_indice.md` es de quien encargó este trabajo, así que no se toca aquí (ver el informe de
esta entrega para la fila exacta).

**El margen de página medido es 5 % (72px a 1440), no el 7,5 % (108px) que usan otras plantillas de
la casa.** `canvas/Aranda.dc.html` usa `padding: 0 72px` en las nueve bandas de su portada, nunca
108px — diez apariciones de «72px», cero de «108px», contadas por grep. `mockup-guide.md` documenta
7,5 % como el estándar de la casa y así lo usa `marzo`, pero la autoridad de esta plantilla es su
propio artboard ya aprobado, no el estándar general cuando los dos discrepan («canvas manda, maqueta
deriva»). Las cuatro láminas nuevas y la maqueta entera usan 5 % — viaja como fracción (`--pad-x:5%`
en `:root`), nunca como píxel fijo, igual que exige el contrato de derivación.

**Los alt de las diez fotografías, tal y como los usa la portada committeada, no coinciden con el
manifiesto.** `manifiesto-imagenes.md` registra el `alt` real de cada fotografía (p. ej.
`aranda-v1`: «Todoterreno azul de perfil en una carretera de otoño»), pero `canvas/Aranda.dc.html`
las etiqueta por lo que representan en el diseño (`alt="BMW X1 sDrive18d"`) en las diez imágenes sin
excepción — el mismo defecto que el hard budget de esta entrega nombra explícitamente («esa defecto
se ha pagado ya cuatro veces en esta biblioteca»). No se ha corregido la portada porque no era parte
de este encargo, pero las cuatro láminas nuevas y la maqueta usan el `alt` **verbatim** del
manifiesto en las diez fotografías, sin una sola excepción. Queda como discrepancia documentada, no
silenciada.

**El eje Fondo de `enfoques.md` § `tecnologico` no coincide con esta plantilla.** La tabla de
`cadencia` fija «Fondo: tinta fría» (oscuro). ARANDA resuelve «frío claro»: `#F3F6F7` sobre
`#111A1F` en las bandas oscuras, no al revés. `enfoques.md` prevé exactamente este caso —«cuando las
dos no coinciden, manda la plantilla: la posición del catálogo anterior queda como el extremo del
enfoque, no como una obligación»— así que no es una contradicción del trío tipo/objetivo/enfoque, que
sí se comprobó contra el artboard: ARANDA vende «por capacidad y por dato medido» (137 puntos,
cifras tabulares, tabla comparable), que es la frase que abre `tecnologico`. `enfoques.md` no se ha
tocado; si alguna vez se quiere una columna «En `aranda`» junto a la de `cadencia`, es una decisión
de quien mantiene ese documento.

**Sin veredicto.** Geometría medida en las cinco láminas y en la maqueta: altos de
`canvas.json` medidos con `alto-contenido.mjs`, márgenes en fracción, cero desborde comprobado por
`empaquetar.php` (985.186 bytes empaquetados, muy por debajo del techo de 16 MB) y por
`color.php --maqueta` (nueve pares, los nueve OK). Nadie la ha mirado todavía: falta `blind-judges`
(juez A contra la biblioteca, juez B sobre esta maqueta) y `visual-verification` a 430, 768 y 1280 en
las diez páginas. Sin las dos, esta plantilla no se ofrece a un cliente — lo dice `_indice.md`, que
debe seguir marcando ARANDA «sin veredicto» hasta entonces.
