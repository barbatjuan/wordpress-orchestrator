# Manifiesto del lienzo · tueste

Los artboards de TUESTE. `Tueste.dc.html` es la **autoridad del diseño**: todo cambio empieza en un
lienzo y baja después a la maqueta, nunca al revés. Las cuatro láminas nuevas de esta entrega
(`Categoria`, `Ficha`, `LaMarca`, `Contacto`) se dibujaron en el mismo idioma — misma cabecera, mismo
suelo, misma tipografía, mismo margen de página — porque el lienzo «seis portadas» del que sale la
portada nunca las dibujó y no hay lienzo de Claude Design que re-sembrar para ellas (ver «De dónde
sale» más abajo).

| Artboard | Página | Tamaño | Alto declarado |
|---|---|---|---|
| `Tueste.dc.html` | portada | 1440 × 4920 | **medido**, no tecleado |
| `Categoria.dc.html` | orígenes | 1440 × 1992 | **medido**, no tecleado |
| `Ficha.dc.html` | ficha del origen Huila | 1440 × 2200 | **medido**, no tecleado |
| `LaMarca.dc.html` | el tostadero | 1440 × 2113 | **medido**, no tecleado |
| `Contacto.dc.html` | contacto | 1440 × 1773 | **medido**, no tecleado |

`canvas.json` coloca los cinco artboards en dos filas — la portada sola arriba, las cuatro láminas
nuevas debajo — con 160px entre láminas de la misma fila y 160px entre filas (4920 de alto de la
portada + 160 = 5080, la `y` de la segunda fila). **El alto de cada lámina sale de
`alto-contenido.mjs`** (Chrome headless por CDP, `getBoundingClientRect()` del envoltorio
`[data-suelo]`, o de `x-dc` cuando el envoltorio no lleva el atributo, con el `<helmet>` oculto),
corrido una sola vez sobre las cinco láminas con
`file:///C:/Users/Juan/temas/novamira-web-framework/skills/web-templates/references/plantillas/tueste/canvas/`
como base:

```
Tueste.dc.html        alto  4920  ancho 1440  imgs 5 (rotas 5)
Categoria.dc.html     alto  1992  ancho 1440  imgs 0 (rotas 0)
Ficha.dc.html         alto  2200  ancho 1440  imgs 1 (rotas 1)
LaMarca.dc.html       alto  2113  ancho 1440  imgs 0 (rotas 0)
Contacto.dc.html      alto  1773  ancho 1440  imgs 0 (rotas 0)
```

Las imágenes salen «rotas» al abrir el fichero suelto desde esta carpeta: es lo esperado (ver «Cómo
se vuelve a sembrar»), y no mueve la altura porque cada `<img>` lleva su alto fijo en línea (por
ejemplo, `height:560px` en la foto de `Ficha.dc.html`).

**Categoría, la marca y contacto no llevan fotografía.** Las cinco del manifiesto ya están habladas
en la portada, y `tueste-bolsa.webp` se reutiliza en `Ficha.dc.html` porque es el producto mismo —
la bolsa — no una foto de ambiente repetida (la distinción que hace
`defectos-de-derivacion.md` § «Canvas y artboards»: el producto sí se repite, el taller o la mesa
puesta no). Con presupuesto de cinco fotografías para catorce páginas, una lámina que pediría una
sexta se resuelve con tipo, filete y tabla — el Enfoque `editorial` la pide así, no como
compromiso.

## De dónde sale

`Tueste.dc.html` no viene de un artifact de Claude Design con URL propia. Salió del lienzo «seis
portadas», que dibujó de una sola vez seis marcas que ya vivían en la galería antigua del framework
(`feat(plantillas): the six brands of the sixth lienzo enter the library`, commit `b153c6a`): sólo la
portada de cada una, con sus fotografías y su manifiesto, dejando el resto de páginas como trabajo
pendiente — la misma procedencia que ya documentan `aranda/canvas/MANIFIESTO.md` y
`lumiere/canvas/MANIFIESTO.md`. Ese lienzo no registró una URL de artifact reutilizable por marca, así
que **`ficha.md` deja `canvas_url` sin rellenar en vez de inventar uno**: no hay lienzo de Claude
Design del que re-sembrar `Categoria.dc.html`, `Ficha.dc.html`, `LaMarca.dc.html` ni `Contacto.dc.html`.
Las cuatro se escribieron directamente como HTML en el idioma medido de `Tueste.dc.html` — mismos
tokens, misma cabecera — sin pasar por Claude Design. Si algún día se dibujan en un lienzo real, esa
lámina pasa a ser la autoridad y esta nota se retira.

## Cómo se vuelve a sembrar

Los cinco artboards citan sus imágenes por nombre suelto (`src="tueste-….webp"`), y las imágenes
viven en `../img/`, no junto a los artboards. Al sembrar un lienzo nuevo, cada una de las 5
fotografías de `img/` se pasa con `--image` y el runtime las resuelve por nombre. Abiertos
directamente desde esta carpeta, los artboards salen con las imágenes rotas: es lo esperado.

## Reglas que ya se pagaron en este lienzo

- **El suelo va en `html, body` además del envoltorio `[data-suelo]`.** `Tueste.dc.html`, tal y como
  llegó del lienzo «seis portadas», sólo lo declaraba en `body` (sin el prefijo `html,`) y el
  envoltorio no llevaba el atributo `data-suelo` — no se ha tocado esa lámina porque no era parte de
  este encargo y el contenido llena el frame sin dejar papel al descubierto, pero las cuatro láminas
  nuevas sí llevan las dos cosas, que es la regla ya pagada en el resto de la biblioteca (`marzo`,
  `barro`, `escuadra`, `cadencia`, `aranda`): declarado sólo en el selector del canvas, el runtime lo
  pierde; declarado sólo en el envoltorio, el frame pinta blanco donde no llega el contenido.
- **El margen de página es 144px, no 108.** `Tueste.dc.html` usa `144px` como valor izquierdo/derecho
  en nueve bandas de contenido — cabecera, hero, planes, «lo que abres», «lo que se está tostando»,
  «molido el mismo día», «pausar es un botón», «bolsa suelta» y el pie — dieciséis apariciones de la
  cadena `144px` contadas por línea (`rg -o '144px' Tueste.dc.html | wc -l`), nunca `108px`. A 1440 de
  ancho eso es 10&nbsp;%, no el 7,5&nbsp;% (`clamp(1140px, 85vw, 100vw)`) que `mockup-guide.md`
  documenta como estándar de la casa, ni el 5&nbsp;% de `aranda` ni el 6,667&nbsp;% de `lumiere`. Las
  cuatro láminas nuevas copian el margen medido de `Tueste.dc.html` (144px), porque la autoridad del
  diseño es el artboard ya aprobado, no el estándar general cuando los dos discrepan. `ficha.md` lo
  declara con su razón.
- **Ningún color de la portada falla contraste.** A diferencia de `aranda` y `lumiere`, los cinco
  tokens de `Tueste.dc.html` — `#0F5C28`, `#3D3125`, `#57493A`, `#20180E` sobre `#E8DFD0` y `#DBD0BD`,
  y `#FFFFFF`/`#E8DFD0`/`#BFB3A0` sobre `#20180E` — miden entre 5,33:1 y 13,27:1, todos por encima del
  4,5:1 de texto. Ninguna paleta se ha tocado; `ficha.md` § Paleta medida trae los números completos,
  medidos con `color.php --contraste`, no copiados del lienzo.
- **La altura del frame es un número que se mide, no que se teclea.** Los cinco valores de
  `canvas.json` salen literalmente de la corrida de `alto-contenido.mjs` de arriba.

## Una divergencia frente al Enfoque declarado, y cómo se resolvió

TUESTE entró en `_indice.md` y `recomendador.md` con `institucional`, que `enfoques.md` describe con
Fondo «frío claro», Composición «centrada», Acento «reservado» y Chasis «tarjeta con relleno».
`Tueste.dc.html`, tal y como llegó dibujado, no resuelve así esos cuatro ejes: su fondo
(`#E8DFD0`/`#DBD0BD`) es cálido, no frío; su hero es una composición asimétrica de dos columnas, no
centrada; y el chasis de fila dividida por filete (los planes, «bolsas sueltas», la tabla de orígenes)
es el chasis «dividido por filetes» de `editorial`, no «tarjeta con relleno».

**Se corrigió la etiqueta, no el lienzo.** El Enfoque pasa a `editorial` en la ficha, el índice y el
recomendador, porque coincide en escala, fondo, composición y chasis, y porque el recomendador enruta
por Enfoque: con `institucional` le habría ofrecido esta plantilla a un despacho que pide algo sobrio y
frío. `enfoques.md` dice que manda la plantilla cuando las dos difieren, así que lo que cambia es la
lectura del catálogo, no el diseño aprobado.

`editorial` tampoco encaja limpio. El acento: pide «ninguno» y el verde `#0F5C28` aparece en más de una
decena de usos activos —botón de «Entrar», precio flotante, CTA del hero, etiqueta «La que más se
pide», chips de cadencia y molienda, los cuatro números de «Lo que abres el día 3»—, porque en una
tienda de suscripción el acento dice qué se pulsa. Y el ornamento: iconos de línea en «Pausar es un
botón» y en `LaMarca.dc.html` donde `editorial` pone filete. Son dos ejes de ocho, declarados en
`ficha.md` § Procedencia, igual que la divergencia de acento de `lumiere` frente a `materia`.
