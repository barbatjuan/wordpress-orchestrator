# Manifiesto del lienzo · lumiere

Los artboards de Claude Design de LUMIÈRE. Son la **autoridad del diseño**: todo cambio empieza aquí y
baja después a la maqueta, nunca al revés.

| Artboard | Página | Tamaño |
|---|---|---|
| `Lumiere.dc.html` | portada | 1440 × 6412 |
| `Rituales.dc.html` | la carta de rituales | 1440 × 3835 |
| `Ritual.dc.html` | un ritual completo (Luz Fría) | 1440 × 2584 |
| `Nosotros.dc.html` | nosotros | 1440 × 2463 |
| `Contacto.dc.html` | contacto | 1440 × 1578 |

`canvas.json` coloca los cinco artboards y guarda su alto. **El alto está medido, no tecleado**: sale
del alto real del contenido a 1440 con `alto-contenido.mjs`, porque un alto que sobra enseña papel y
uno que falta corta la última línea. La portada se remidió en esta misma pasada — 6412, frente a
ningún valor previo declarado, porque `canvas.json` no existía todavía — y las cuatro láminas nuevas
se midieron al terminarlas, no se calcularon a ojo.

## De dónde sale

La portada, `Lumiere.dc.html`, viene del lienzo «seis portadas» que `plantillas/_indice.md` describe
—dibujó las seis marcas nuevas de esta semana una por una— y llegó a este repositorio sin
`canvas.json` ni el resto de sus páginas: sólo la portada y las diez fotografías con su manifiesto.
No se dispone de la URL de ese lienzo compartido; queda en blanco en la ficha en vez de inventarse.

Las cuatro láminas nuevas —`Rituales.dc.html`, `Ritual.dc.html`, `Nosotros.dc.html` y
`Contacto.dc.html`— no vienen de una sesión nueva de Claude Design: se escribieron directamente como
`.dc.html`, en el mismo idioma de marcado y con los mismos tokens que ya fija la portada, siguiendo el
mismo método con el que `marzo`, `barro`, `escuadra` y `cadencia` completaron su propio juego de
páginas esta semana (ver sus commits `db323e0`, `a45c857`, `fd41811`, `5321e0d`). Si algún día se
dibujan en una sesión real de Claude Design, la lámina resultante pasa a ser la autoridad y esta nota
se retira.

## Cómo se vuelve a sembrar

Los artboards citan sus imágenes por nombre suelto (`src="lumiere-….webp"`), y las imágenes viven en
`../img/`, no junto a los artboards. Al sembrar un lienzo nuevo, cada una de las 10 fotografías de
`img/` se pasa con `--image` y el runtime las resuelve por nombre. Abiertos directamente desde esta
carpeta, o medidos con `alto-contenido.mjs` como aquí, los artboards salen con las imágenes rotas: es
lo esperado, y no afecta a la altura porque cada `<img>` lleva ancho y alto fijados en su propio
`style`.

## Reglas que ya se pagaron en este lienzo

- **El suelo va en `html, body` además de en el envoltorio `[data-suelo]`.** Sólo en el selector del
  canvas, el runtime lo pierde; sólo en el envoltorio, el frame pinta blanco donde no llega el
  contenido. La portada ya declaraba el fondo en su `<div>` de contenido pero no lo tenía en
  `html, body` ni el atributo `data-suelo`; las cuatro láminas nuevas llevan las dos cosas, y es lo
  que mide `alto-contenido.mjs` (busca `[data-suelo]` antes que `x-dc`).
- **El margen de página medido en la portada es 96px, no 108px.** Los once usos de `padding` con
  margen lateral de `Lumiere.dc.html` son consistentemente `96px` (líneas 107, 122, 128, 184, 240,
  296, 343, 353, 377, 427, 459); las quince apariciones de `108px` del fichero son todas
  `width: 108px` de la columna de precio en las filas de ritual, no un margen de página. 96/1440 es
  6,67 %, no el 7,5 % que usan `delao`, `marzo`, `barro`, `escuadra` y `cadencia`. Las cuatro láminas
  nuevas siguen el margen medido de la propia portada — 96px de contenido, 64px en la cabecera, que
  es también lo que mide la portada — no el estándar de 108px de las otras plantillas de la
  biblioteca, para no introducir un margen que la portada no tiene.
- **Ninguna fotografía nueva.** Las cuatro láminas nuevas reutilizan las diez fotografías del
  manifiesto: las cuatro de zona (`lumiere-rostro`, `-cuerpo`, `-depilacion`, `-manos`) en la carta y
  en la ficha de Luz Fría, los tres retratos (`lumiere-noa`, `-pilar`, `-hugo`) en Nosotros y en el
  crédito de la ficha de ritual, y `lumiere-recepcion` en Contacto. Ninguna se repite entre dos
  páginas en el mismo papel — la carta usa las fotos de zona como marcador pequeño (128×96), la ficha
  de Luz Fría usa `lumiere-rostro` a tamaño de foto principal (1248×520): es el mismo fichero, no la
  misma composición.
