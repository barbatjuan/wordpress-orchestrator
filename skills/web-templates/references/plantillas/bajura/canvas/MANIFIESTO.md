# Manifiesto del lienzo · bajura

Los artboards de BAJURA. `Bajura.dc.html` es la **autoridad del diseño**: todo cambio empieza en un
lienzo y baja después a la maqueta, nunca al revés. Las cuatro láminas nuevas de esta entrega
(`Categoria`, `Ficha`, `LaMarca`, `Contacto`) se dibujaron en el mismo idioma — misma cabecera, misma
franja de código postal, mismo suelo, misma tipografía, mismo margen de página — porque el lienzo
«seis portadas» del que sale la portada nunca las dibujó y no hay lienzo de Claude Design que
re-sembrar para ellas (ver «De dónde sale» más abajo).

| Artboard | Página | Tamaño | Alto declarado |
|---|---|---|---|
| `Bajura.dc.html` | portada | 1440 × 3581 | **medido**, no tecleado |
| `Categoria.dc.html` | todo lo de hoy | 1440 × 1770 | **medido**, no tecleado |
| `Ficha.dc.html` | ficha (lubina salvaje) | 1440 × 1508 | **medido**, no tecleado |
| `LaMarca.dc.html` | la lonja | 1440 × 1536 | **medido**, no tecleado |
| `Contacto.dc.html` | contacto | 1440 × 1193 | **medido**, no tecleado |

`canvas.json` coloca los cinco artboards en dos filas — la portada sola arriba, las cuatro láminas
nuevas debajo — con 160px entre láminas de la misma fila y 160px entre filas (3581 de alto de la
portada + 160 = 3741, la `y` de la segunda fila). **El alto de cada lámina sale de
`alto-contenido.mjs`** (Chrome headless por CDP, `getBoundingClientRect()` del envoltorio
`[data-suelo]`, o de `x-dc` cuando el envoltorio no lleva el atributo, con el `<helmet>` oculto),
corrido una sola vez sobre las cinco láminas con
`file:///C:/Users/Juan/temas/novamira-web-framework/skills/web-templates/references/plantillas/bajura/canvas/`
como base:

```
Bajura.dc.html        alto  3581  ancho 1440  imgs 7 (rotas 7)
Categoria.dc.html     alto  1770  ancho 1440  imgs 0 (rotas 0)
Ficha.dc.html         alto  1508  ancho 1440  imgs 1 (rotas 1)
LaMarca.dc.html        alto  1536  ancho 1440  imgs 0 (rotas 0)
Contacto.dc.html      alto  1193  ancho 1440  imgs 0 (rotas 0)
```

Las imágenes salen «rotas» al abrir el fichero suelto desde esta carpeta: es lo esperado (ver «Cómo
se vuelve a sembrar»), y no mueve la altura porque cada `<img>` lleva su alto fijo en línea (por
ejemplo, `height:560px` en la foto de `Ficha.dc.html`).

**Categoría, la lonja y contacto no llevan fotografía.** Las siete del manifiesto ya están habladas
en la portada — es el presupuesto entero de la plantilla, repartido en cuatro bandas de la propia
lámina de origen — y `bajura-pieza.webp` se reutiliza en `Ficha.dc.html` porque es el producto mismo
que compra el cliente (la lubina), no una foto de ambiente repetida: la distinción que ya fija
`defectos-de-derivacion.md` § «Canvas y artboards» («el producto sí se repite, el taller o la mesa
puesta no») y que `tueste/canvas/MANIFIESTO.md` documentó primero con `tueste-bolsa.webp`. Reutilizar
`bajura-lonja`, `bajura-puerto` o `bajura-corte` en una lámina nueva habría sido exactamente el
defecto contrario — una foto de ambiente repetida entre la portada y la página siguiente — así que
`Categoria`, `LaMarca` y `Contacto` se resuelven con tipo, filete y tabla. Con presupuesto de siete
fotografías para catorce páginas, una lámina que pediría una octava se resuelve así, no inventando
una imagen nueva.

## De dónde sale

`Bajura.dc.html` no viene de un artifact de Claude Design con URL propia. Salió del lienzo «seis
portadas», que dibujó de una sola vez seis marcas que ya vivían en la galería antigua del framework
(`feat(plantillas): the six brands of the sixth lienzo enter the library`, commit `b153c6a`): sólo la
portada de cada una, con sus fotografías y su manifiesto, dejando el resto de páginas como trabajo
pendiente — la misma procedencia que ya documentan `aranda/canvas/MANIFIESTO.md`,
`lumiere/canvas/MANIFIESTO.md`, `terrazza/canvas/MANIFIESTO.md` y `tueste/canvas/MANIFIESTO.md`. Ese
lienzo no registró una URL de artifact reutilizable por marca, así que **`ficha.md` deja `canvas_url`
sin rellenar en vez de inventar uno**: no hay lienzo de Claude Design del que re-sembrar
`Categoria.dc.html`, `Ficha.dc.html`, `LaMarca.dc.html` ni `Contacto.dc.html`. Las cuatro se
escribieron directamente como HTML en el idioma medido de `Bajura.dc.html` — mismos tokens, misma
cabecera, misma franja de código postal — sin pasar por Claude Design. Si algún día se dibujan en un
lienzo real, esa lámina pasa a ser la autoridad y esta nota se retira.

## Cómo se vuelve a sembrar

Los cinco artboards citan sus imágenes por nombre suelto (`src="bajura-….webp"`), y las imágenes
viven en `../img/`, no junto a los artboards. Al sembrar un lienzo nuevo, cada una de las 7
fotografías de `img/` se pasa con `--image` y el runtime las resuelve por nombre. Abiertos
directamente desde esta carpeta, los artboards salen con las imágenes rotas: es lo esperado.

## Reglas que ya se pagaron en este lienzo

- **Los siete `alt` de la portada no coincidían con el manifiesto, y aquí sí se corrigieron.**
  `Bajura.dc.html`, tal y como llegó, etiquetaba sus siete fotografías por lo que ilustran en el
  diseño (`alt="La lonja de Burela durante la subasta"`, `alt="Lubina de volanta sobre hielo"`…) en
  vez de por lo que describe `manifiesto-imagenes.md` — el mismo defecto que ya se pagó en `aranda`,
  `lumiere` y `terrazza` según su propio historial, y la tercera vez que este encargo lo pide corregir
  **incluso en la portada**. Los siete `alt` se reescribieron verbatim contra el manifiesto y las
  cuatro láminas nuevas nacieron ya correctas.
- **Uno de los siete no era sólo una etiqueta distinta: era una especie distinta.** El manifiesto
  describe `bajura-lomo.webp` como «Lomo de atún rojo cortado grueso sobre hielo picado»;
  `Bajura.dc.html` la usaba para ilustrar un ejemplo de precio de **bonito**, una especie que sí vive
  en la tabla de la subasta pero que la fotografía no retrata. Corregir sólo el `alt` habría dejado un
  párrafo hablando de bonito junto a una foto ya etiquetada como atún rojo — la misma clase de
  discrepancia que este encargo prohíbe. Se cambió el ejemplo entero a atún rojo (tramo de 4 a 6 kg,
  32,00&nbsp;€/kg, pieza de 4,860&nbsp;kg → 155,52&nbsp;€), sin tocar la fila de «Bonito del norte» de
  la tabla, que sigue siendo una especie de hoy con su propia foto no retratada.
- **El suelo va en `html, body` además del envoltorio `[data-suelo]`.** `Bajura.dc.html`, tal y como
  llegó del lienzo «seis portadas», sólo lo declaraba en `body` (sin el prefijo `html,`) y el
  envoltorio no llevaba el atributo `data-suelo` — no se ha tocado esa lámina porque no era parte de
  este encargo y el contenido llena el frame sin dejar papel al descubierto, pero las cuatro láminas
  nuevas sí llevan las dos cosas, que es la regla ya pagada en el resto de la biblioteca (`marzo`,
  `barro`, `escuadra`, `cadencia`, `aranda`, `tueste`): declarado sólo en el selector del canvas, el
  runtime lo pierde; declarado sólo en el envoltorio, el frame pinta blanco donde no llega el
  contenido.
- **El margen de página es 64px, no 108.** `Bajura.dc.html` usa `64px` como valor izquierdo/derecho en
  sus nueve bandas de contenido — cabecera, franja de código postal, hero, tabla de la subasta, «el
  peso es aproximado», la foto del puerto, «hasta dónde llegamos», «cómo viaja» y el pie — dieciséis
  apariciones de la cadena `64px` contadas por línea (`rg -o '64px' Bajura.dc.html | wc -l` → 16),
  cero de `108px`. A 1440 de ancho eso es 64/1440 = 4,444&nbsp;% (2/45), un valor que no coincide con
  ninguno de los otros cinco medidos en la biblioteca (48 de `terrazza`, 72 de `aranda`, 96 de
  `lumiere`, 108 estándar de `marzo`/`barro`/`escuadra`/`cadencia`/`delao`, 144 de `tueste`) ni con el
  7,5&nbsp;% que documenta `mockup-guide.md` como estándar de la casa. Las cuatro láminas nuevas copian
  el margen medido de `Bajura.dc.html` (64px), porque la autoridad del diseño es el artboard ya
  aprobado, no el estándar general cuando los dos discrepan. `ficha.md` lo declara con su razón.

## Una divergencia frente al Enfoque declarado, y cómo se resolvió

BAJURA entró en `recomendador.md` con `brutalista`, la única casilla de las tres sin plantilla que
`subasta-diaria` podía ocupar cuando se escribió la tabla. `Bajura.dc.html`, tal y como llegó dibujado,
no resuelve así seis de los ocho ejes de `brutalista`: su fondo (`#0F1714`/`#18211D`) es un casi negro
frío, no saturado; su escala (h1 a 62px) no llega a monumental y no hay un solo titular sin ajustar;
su densidad es generosa (46–66px de relleno de sección), no compacta; su acento es un único naranja
funcional en todas partes (`#FF8A3D`, 16 usos activos sólo en precios, horas de corte y CTA), nunca el
«policromo acotado» de una fila de etiquetas de colores distintos; su chasis no lleva sombra dura —
`box-shadow` no aparece ni una vez en el fichero — sino filetes finos (`border-top:3px`, bordes
`rgba` de 1px); y su ornamento es filete o ninguno, nunca un patrón. Sólo dos ejes coinciden:
Elevación «ninguna» (cero sombras, igual que pide `brutalista`) y Composición «asimétrica» (el hero de
dos columnas desiguales de la línea 41, la banda «el peso es aproximado» de la línea 168, «cómo viaja»
de la línea 224). Dos de ocho.

**`tecnologico` encaja mejor, con la misma evidencia.** Densidad «generosa»: coincide (mismo relleno
de 46–66px que ya se midió arriba). Fondo «tinta fría»: `#0F1714` es un casi negro de matiz verdoso-
azulado (R15 G23 B20, canal verde el más alto), frío y no cálido, el mismo registro que `cadencia`
declara para su propio fondo oscuro. Composición «rejilla estricta»: la tabla de la subasta (líneas
50–143, seis columnas de ancho fijo con filete de acento bajo la cabecera) y la rejilla de cuatro
fotografías a hueco cero (línea 147) son la pieza central de la página, aunque conviven con los
splits asimétricos ya citados. Chasis «rejilla estricta»: la misma tabla y la misma rejilla de fotos,
sin sombra en ningún borde. Ornamento «ninguno»: no hay patrón, textura ni ilustración en todo el
fichero. Y una pista que `enfoques.md` no pide pero que corrobora la lectura: todo precio y toda
cantidad de la tabla lleva `font-variant-numeric:tabular-nums` (líneas 66, 67, 74, 75… hasta la 138),
la misma disciplina de «cifras alineadas» que describe la prosa de `tecnologico`, aunque BAJURA no use
una tipografía monoespaciada literal.

**Tampoco `tecnologico` encaja limpio, y se declara.** Escala «contenida»: el h1 de 62px es más grande
que el de `aranda` (50px, la plantilla que encarna hoy este Enfoque) aunque muy por debajo de las
plantillas monumentales de la casa (94px en `terrazza`, 74px en `corte`) — un encaje razonable, no
exacto. Elevación «halo del acento»: BAJURA no tiene ningún halo ni resplandor; su elevación real es
«ninguna», la misma que comparte con `brutalista` y que `cadencia` no fija en su propia ficha. Y el
acento: `tecnologico` pide que el naranja «sólo mida… nunca letra», y en BAJURA el naranja SÍ es color
de letra en más de media docena de sitios — los precios de la tabla, las horas de corte, el copy del
tramo de peso —, porque en una lonja el precio y la hora de cierre son justo el dato que hay que leer,
no sólo medir con una barra. Es la lectura más cercana del catálogo, no una casilla hecha a medida, y
así queda escrita para quien juzgue la biblioteca — el mismo formato de divergencia declarada que
`tueste/canvas/MANIFIESTO.md` § «Una divergencia frente al Enfoque declarado» ya dejó para `editorial`.

**Se corrige la etiqueta, no el lienzo.** `ficha.md` declara `enfoque: tecnologico`, con el detalle
completo en su § Procedencia. `recomendador.md` y `_indice.md` no se tocan en este encargo — ver el
informe final para las filas exactas que hay que escribir allí.
