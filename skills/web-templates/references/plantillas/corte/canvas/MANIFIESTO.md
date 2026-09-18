# Manifiesto del lienzo · corte

Los artboards de CORTE. `Corte.dc.html` es la **autoridad del diseño**: todo cambio empieza en un
lienzo y baja después a la maqueta, nunca al revés. Las cuatro láminas nuevas de esta entrega
(`Categoria`, `Ficha`, `LaMarca`, `Contacto`) se dibujaron en el mismo idioma exacto — misma
cabecera, mismo suelo, misma tipografía, mismo margen de página, mismo acento — porque el lienzo
«seis portadas» del que sale la portada nunca las dibujó y no hay lienzo de Claude Design que
re-sembrar para ellas (ver «De dónde sale» más abajo).

| Artboard | Página | Tamaño | Alto declarado |
|---|---|---|---|
| `Corte.dc.html` | portada | 1440 × 4892 | **medido**, no tecleado |
| `Categoria.dc.html` | catálogo | 1440 × 1978 | **medido**, no tecleado |
| `Ficha.dc.html` | ficha del Recto 01 | 1440 × 2421 | **medido**, no tecleado |
| `LaMarca.dc.html` | el taller | 1440 × 2040 | **medido**, no tecleado |
| `Contacto.dc.html` | contacto | 1440 × 1325 | **medido**, no tecleado |

`canvas.json` coloca las cinco láminas en dos filas — la portada sola arriba, las cuatro nuevas
debajo — con 120px entre filas (4892 de alto de la portada + 120 = 5012, la `y` de la segunda fila)
y 80px entre láminas de la misma fila. **El alto de cada lámina sale de `alto-contenido.mjs`**
(Chrome headless por CDP, `getBoundingClientRect()` del envoltorio `[data-suelo]`, o de `x-dc`
cuando el envoltorio no lo lleva, con el `<helmet>` oculto), corrido una sola vez sobre las cinco
láminas con
`file:///C:/Users/Juan/temas/novamira-web-framework/skills/web-templates/references/plantillas/corte/canvas/`
como base:

```
Corte.dc.html        alto  4892  ancho 1440  imgs 9 (rotas 9)
Categoria.dc.html    alto  1978  ancho 1440  imgs 6 (rotas 6)
Ficha.dc.html         alto  2421  ancho 1440  imgs 4 (rotas 4)
LaMarca.dc.html       alto  2040  ancho 1440  imgs 0 (rotas 0)
Contacto.dc.html      alto  1325  ancho 1440  imgs 0 (rotas 0)
```

Las imágenes salen «rotas» al abrir el fichero suelto desde esta carpeta: es lo esperado (ver «Cómo
se vuelve a sembrar»), y no mueve la altura porque cada `<img>` lleva su alto fijo en línea. La
portada, tal y como llegó, medía 4892 en esta misma pasada — no se ha tocado ninguna de sus
secciones, sólo se ha vuelto a medir junto con las cuatro nuevas.

**Categoría y ficha llevan fotografía; la marca y contacto no.** Las nueve del manifiesto ya están
repartidas entre la portada, la categoría (`corte-v1` a `corte-v6`, rol «card 4:3» del manifiesto,
pensadas para tarjeta de catálogo) y la ficha (`corte-v1` de nuevo, más `corte-cuerpo1/2/3`, que son
el mismo Recto&nbsp;01 sobre tres cuerpos y por tanto el producto repitiéndose, no una foto de
ambiente — la distinción que hace `defectos-de-derivacion.md` § «Canvas y artboards»). El taller y
el contacto no tienen fotografía propia entre las nueve — ninguna de las nueve muestra la nave de
Béjar, una persona cortando ni el mostrador — así que, con presupuesto cerrado en nueve fotografías
para catorce páginas, ambas láminas se resuelven con tipo, cifra y tabla en vez de forzar una imagen
de vaquero sobre una página que habla del taller. Es el Enfoque `materia` resolviendo con lo que
tiene, no un recorte de presupuesto — ver «Una divergencia frente al Enfoque declarado» más abajo.

## De dónde sale

`Corte.dc.html` no viene de un artifact de Claude Design con URL propia. Salió del lienzo «seis
portadas», que dibujó de una sola vez seis marcas que ya vivían en la galería antigua del framework
(`feat(plantillas): the six brands of the sixth lienzo enter the library`, commit `b153c6a`): sólo
la portada de cada una, con sus fotografías y su manifiesto, dejando el resto de páginas como
trabajo pendiente — la misma procedencia que ya documentan `aranda/canvas/MANIFIESTO.md`,
`lumiere/canvas/MANIFIESTO.md` y `tueste/canvas/MANIFIESTO.md`, y que el propio `tueste/ficha.md`
nombra a `corte` entre los seis. Ese lienzo no registró una URL de artifact reutilizable por marca,
así que **`ficha.md` deja `canvas_url` sin rellenar en vez de inventar uno**: no hay lienzo de
Claude Design del que re-sembrar `Categoria.dc.html`, `Ficha.dc.html`, `LaMarca.dc.html` ni
`Contacto.dc.html`. Las cuatro se escribieron directamente como HTML en el idioma medido de
`Corte.dc.html` — mismos tokens, misma cabecera, mismo pie — sin pasar por Claude Design. Si algún
día se dibujan en un lienzo real, esa lámina pasa a ser la autoridad y esta nota se retira.

## Cómo se vuelve a sembrar

Los cinco artboards citan sus imágenes por nombre suelto (`src="corte-….webp"`), y las imágenes
viven en `../img/`, no junto a los artboards. Al sembrar un lienzo nuevo, cada una de las 9
fotografías de `img/` se pasa con `--image` y el runtime las resuelve por nombre. Abiertos
directamente desde esta carpeta, los artboards salen con las imágenes rotas: es lo esperado.

## Reglas que ya se pagaron en este lienzo

- **El suelo va en `html, body` además del envoltorio `[data-suelo]`.** `Corte.dc.html`, tal y como
  llegó del lienzo «seis portadas», sólo lo declaraba en `body` (sin el prefijo `html,`) y su
  envoltorio no llevaba el atributo `data-suelo` — no se ha tocado esa lámina porque no era parte de
  este encargo y el contenido llena el frame sin dejar papel al descubierto, pero las cuatro láminas
  nuevas sí llevan las dos cosas, la regla ya pagada en el resto de la biblioteca (`marzo`, `barro`,
  `escuadra`, `cadencia`, `tueste`): declarado sólo en el selector del canvas, el runtime lo pierde;
  declarado sólo en el envoltorio, el frame pinta blanco donde no llega el contenido.
- **El margen de página es 96px, no 108.** `Corte.dc.html` usa `96px` como valor izquierdo/derecho
  en quince apariciones de la cadena `96px` contadas por línea (`rg -o '96px' Corte.dc.html | wc -l`
  → 15: cabecera, hero, franja de medida, «tres cuerpos», la tabla completa, «seis prendas», la
  banda de garantía y el pie), nunca `108px`. A 1440 de ancho eso es 6,667&nbsp;%, no el 7,5&nbsp;%
  (`clamp(1140px, 85vw, 100vw)`) que `mockup-guide.md` documenta como estándar de la casa, ni el
  5&nbsp;% de `aranda` ni el 10&nbsp;% de `tueste` — coincide con el 6,667&nbsp;% de `lumiere`, por
  casualidad de aritmética, no porque compartan Enfoque. Las cuatro láminas nuevas copian el margen
  medido de `Corte.dc.html` (96px), porque la autoridad del diseño es el artboard ya aprobado.
  `ficha.md` lo declara con su razón.
- **La altura del frame es un número que se mide, no que se teclea.** Los cinco valores de
  `canvas.json` salen literalmente de la corrida de `alto-contenido.mjs` de arriba.
- **Ninguna rejilla entintada de las cuatro láminas nuevas usa `gap:1px`**, así que el defecto del
  delantal de color (`defectos-de-derivacion.md` § «Fondos y rejillas») no aplica aquí: los huecos
  entre tarjetas y filas son `gap` sobre fondo transparente, nunca una línea dibujada con el propio
  fondo del contenedor.

## Una divergencia frente al Enfoque declarado, y cómo se resolvió

CORTE entró en `recomendador.md` con `vitrina`, que `enfoques.md` describe con Fondo «tinta
neutra» (una sala a oscuras), Elevación «sombra suave», Acento «metálico», Chasis «tarjeta con
sombra al levantarse», Densidad «monumental» y Ornamento «ninguno». `Corte.dc.html`, tal y como
llegó dibujado, no resuelve así seis de esos ocho ejes:

- **Fondo.** `body{background:#EDEAE4}` (línea 12) y la banda alterna `#DFDAD1` (líneas 37, 66,
  372) son cálidos y claros en las nueve bandas de la portada. No hay una sola banda oscura ni
  «sala a oscuras»: es el fondo «cálido claro» de `materia`.
- **Elevación.** `rg -c 'box-shadow' Corte.dc.html` → 0 apariciones. Ninguna tarjeta ni panel se
  separa del suelo con sombra; la separación entre bandas es sólo color plano.
- **Acento.** El único color fuera de tinta y suelo es `#2C3E7A`, un azul sólido sin gradiente ni
  brillo (`rg -c 'gradient' Corte.dc.html` → 0): pinta paneles enteros de campo teñido («Tu talla
  en el Recto&nbsp;01», línea 52; la banda de garantía, línea 343) y una fila de tabla resaltada
  (línea 169). Es el «campo teñido» de `materia`, no el «metálico» de `vitrina`.
- **Chasis.** Los seis vaqueros de «Seis prendas, con sus centímetros» (líneas 234-340) son filas
  separadas por `border-top:1px solid rgba(28,26,23,0.2)`, sin tarjeta ni sombra: «dividido por
  filetes»/«enmarcado por filete», no «tarjeta con sombra al levantarse».
- **Densidad.** La tabla de tallas de nueve filas (líneas 119-223) y las seis filas de producto con
  tres columnas de dato cada una empaquetan mucha cifra por banda — «estándar», no «monumental».
- **Ornamento.** El icono de línea junto a «Si la talla no es la tuya…» (líneas 347-350) es una
  ilustración de línea activa, no «ninguno».

Frente a esto, `Corte.dc.html` coincide con la columna `materia` en seis ejes limpios (Densidad,
Fondo, Elevación, Composición — la rejilla de tres columnas de «tres cuerpos», línea 72, y la
propia tabla — Acento y Chasis), y sólo se aleja de ella en la Escala (titular en negrita de 74px,
más cercano al «alto contraste» de `editorial` que al «display fino» de `materia`) y en el
Ornamento (que `materia` deja sin fijar en su ficha). Contando así, `vitrina` puntúa 2 de 8 ejes y
`materia` puntúa 6 de 8: la misma proporción — «seis de ocho» — que corrigió el Enfoque de TUESTE.

**Se corrigió la etiqueta que mide la ficha, no el lienzo.** `ficha.md` declara `enfoque: materia`
en vez de `vitrina`, porque `enfoques.md` dice que manda la plantilla cuando las dos difieren y
porque el recomendador enruta por Enfoque: con `vitrina` ofrecería esta plantilla a una joyería que
pide una sala a oscuras, y CORTE es justo lo contrario — un taller con la luz encendida y una
tabla en la pared. `recomendador.md`, `enfoques.md` y `_indice.md` no se han tocado en este
encargo — otro agente trabaja en el mismo árbol — así que la fila de `recomendador.md` sigue
diciendo `vitrina` hasta que alguien la corrija con este mismo detalle; `ficha.md` § Procedencia
trae la cita completa, eje por eje, para esa corrección.

**Compartir Enfoque con MARZO no repite la plantilla.** `enfoques.md` prohíbe que dos plantillas
compartan tipo + Objetivo + Enfoque, no que compartan sólo Enfoque: MARZO es
`ecommerce · tienda-talla · materia` y CORTE mide `ecommerce · prenda-a-medida · materia` — el
Objetivo es distinto y es el que decide qué resuelve la página (una talla de percha con existencias
contra una talla que no existe hasta que se calcula). Qué se hizo para que CORTE no se lea como
MARZO, con el mismo Enfoque de fondo, se explica en `ficha.md` § Procedencia.
