# Manifiesto del lienzo · terrazza

Los artboards de Claude Design de CASA TERRAZZA. `Terrazza.dc.html` es la **autoridad del diseño**:
todo cambio empieza en un lienzo y baja después a la maqueta, nunca al revés. Las cuatro láminas
nuevas de este encargo (`Carta`, `Plato`, `Nosotros`, `Contacto`) se dibujaron en el mismo idioma —
mismo suelo, misma tipografía, mismo margen de página — porque el lienzo «seis portadas» del que
sale la portada nunca las dibujó y no hay lienzo de Claude Design que re-sembrar para ellas (ver «De
dónde sale» más abajo).

| Artboard | Página | Tamaño | Alto declarado |
|---|---|---|---|
| `Terrazza.dc.html` | inicio (portada) | 1440 × 5428 | **medido**, no tecleado |
| `Carta.dc.html` | la carta (listado) | 1440 × 3813 | **medido**, no tecleado |
| `Plato.dc.html` | el plato de la semana (detalle) | 1440 × 2710 | **medido**, no tecleado |
| `Nosotros.dc.html` | nosotros | 1440 × 2891 | **medido**, no tecleado |
| `Contacto.dc.html` | contacto | 1440 × 1729 | **medido**, no tecleado |

`canvas.json` coloca los cinco artboards en dos filas — la portada sola arriba, las cuatro láminas
nuevas debajo — con 160px entre láminas de la misma fila y 160px entre filas (ambos por encima de
los mínimos de 80px y 120px). **El alto de cada lámina sale de `alto-contenido.mjs`** (Chrome
headless por CDP, `getBoundingClientRect()` del envoltorio `[data-suelo]` con el `<helmet>` oculto),
corrido una sola vez sobre las cinco láminas con
`file:///C:/Users/Juan/temas/novamira-web-framework/skills/web-templates/references/plantillas/terrazza/canvas/`
como base:

```
Terrazza.dc.html     alto  5428  ancho 1440  imgs 7 (rotas 7)   (antes 5438: se retiró la
                                                                 etiqueta sobre el titular)
Carta.dc.html        alto  3813  ancho 1440  imgs 1 (rotas 1)
Plato.dc.html        alto  2710  ancho 1440  imgs 1 (rotas 1)
Nosotros.dc.html     alto  2891  ancho 1440  imgs 2 (rotas 2)
Contacto.dc.html     alto  1729  ancho 1440  imgs 1 (rotas 1)
```

Las imágenes salen «rotas» al abrir el fichero suelto desde esta carpeta: es lo esperado (ver «Cómo
se vuelve a sembrar»), y no mueve la altura porque cada `<img>` lleva `width`/`height` fijos en línea
o vía CSS.

## De dónde sale

`Terrazza.dc.html` no viene de un artifact de Claude Design con URL propia. Salió del lienzo «seis
portadas», que dibujó de una sola vez seis marcas (commit `b153c6a`,
`feat(plantillas): the six brands of the sixth lienzo enter the library`): sólo la portada de cada
una, con sus siete fotografías y su manifiesto, dejando el resto de páginas como trabajo pendiente.
Ese lienzo no registró una URL de artifact reutilizable por marca — a diferencia de `delao`, `marzo`,
`barro`, `cadencia` o `escuadra` — así que **`ficha.md` deja `canvas_url` sin rellenar en vez de
inventar uno**: no hay lienzo de Claude Design del que re-sembrar `Carta.dc.html`, `Plato.dc.html`,
`Nosotros.dc.html` ni `Contacto.dc.html`. Las cuatro se escribieron directamente como HTML en el
idioma medido de `Terrazza.dc.html` — mismos tokens, misma cabecera, mismo pie — sin pasar por Claude
Design.

## Cómo se vuelve a sembrar

Las cinco láminas citan sus imágenes por nombre suelto (`src="terrazza-….webp"`), y las imágenes
viven en `../img/`, no junto a los artboards. Al sembrar un lienzo nuevo, cada una de las 7
fotografías de `img/` se pasa con `--image` y el runtime las resuelve por nombre. Abiertos
directamente desde esta carpeta, los artboards salen con las imágenes rotas: es lo esperado.

## Reglas que ya se pagaron en este lienzo

- **El suelo va en `html, body` además del envoltorio `[data-suelo]`.** `Terrazza.dc.html`, tal y
  como llegó del lienzo «seis portadas», sólo lo declaraba en `body` y el envoltorio no llevaba el
  atributo `data-suelo` — no se ha tocado esa lámina porque el defecto no impide que el suelo se
  pinte (el `body` sin `html` explícito sigue heredando el fondo del canvas en el `<div>` de
  contenido, que ya declaraba `background:#171310` en línea) y no era parte de este encargo, pero
  las cuatro láminas nuevas sí llevan las dos cosas, que es la regla ya pagada en el resto de la
  biblioteca (`marzo`, `barro`, `aranda`, `escuadra`, `cadencia`): declarado sólo en el selector del
  canvas, el runtime lo pierde; declarado sólo en el envoltorio, el frame pinta blanco donde no llega
  el contenido.
- **El margen de página es 48px, no 108.** `Terrazza.dc.html` usa `padding` lateral de **48px** en
  sus diez bandas de contenido — diez apariciones de «48px» como margen de página, contadas por
  grep, cero de «108px» — lo que a 1440 es 3,333&nbsp;%, no el 7,5&nbsp;% (`clamp(1140px, 85vw,
  100vw)`) que `mockup-guide.md` documenta como estándar de la casa. Las cuatro láminas nuevas
  copian el margen medido de `Terrazza.dc.html` (48px), porque la autoridad del diseño es el
  artboard ya aprobado, no el estándar general cuando los dos discrepan.
- **Los siete `alt` de la portada no coincidían con `manifiesto-imagenes.md`.** `Terrazza.dc.html`
  tal y como llegó etiquetaba sus fotografías por lo que ilustran en el diseño (`alt="Sala de Casa
  Terrazza"`, `alt="Álex Ibáñez, jefe de cocina"`…), no por lo que el manifiesto describe. A
  diferencia de `aranda` y `lumiere`, donde este mismo defecto se dejó anotado sin tocar la portada,
  aquí el encargo pedía explícitamente corregirlo **incluso en la portada** — así que los siete `alt`
  de `Terrazza.dc.html` se reescribieron verbatim contra el manifiesto, y las cuatro láminas nuevas
  nacieron ya correctas.
- **Dos colores de la portada fallaban AA y no se habían medido.** `#5A4622` (etiquetas «Personas»,
  «Día y hora», «Zona», «Teléfono» del panel de reserva, sobre el acento `#D9A441`) medía 4,00:1, y
  `#7E7268` (la línea legal del pie, sobre el suelo `#171310`) medía 3,95:1 — los dos por debajo del
  4,5:1 de AA. Se oscureció el primero a `#453516` (5,26:1) y se aclaró el segundo a `#948877`
  (5,32:1): mismo matiz, mismo papel, contraste real. Corregidos en `Terrazza.dc.html` — la
  autoridad — y no sólo en la maqueta; `ficha.md` § Paleta medida trae los números completos.
- **La altura del frame es un número que se mide, no que se teclea.** Los cinco valores de
  `canvas.json` salen literalmente de la corrida de `alto-contenido.mjs` de arriba.

## Cabecera de navegación

`Terrazza.dc.html` sólo dibuja una franja de utilidad (horario, dirección, teléfono) y no un menú:
la portada nunca necesitó enlazar a otras páginas porque era la única que existía. Con cinco páginas
de contenido, el sitio necesita una forma de moverse entre ellas, así que las cuatro láminas nuevas
añaden una segunda franja — cabecera con el nombre de la casa, los cuatro enlaces principales y el
botón «Reservar mesa» — debajo de la franja de utilidad, en el mismo idioma oscuro y dorado. Es una
pieza estructural que faltaba, no un cambio de composición: `maqueta/index.html` reproduce esta
cabecera de dos franjas en las diez páginas, `Terrazza.dc.html` incluida, porque el encabezado vive
fuera de los contenedores de página y es uno solo para todo el sitio.
