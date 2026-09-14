# Manifiesto del lienzo · marzo

Los artboards de Claude Design de MARZO. Son la **autoridad del diseño**: todo cambio empieza aquí y
baja después a la maqueta, nunca al revés.

| Artboard | Página | Tamaño |
|---|---|---|
| `Marzo.dc.html` | portada | 1440 × 6690 |
| `MarzoPieza.dc.html` | ficha del abrigo Sagra | 1440 × 5510 |

`canvas.json` coloca los dos artboards y guarda su alto. **El alto está medido, no tecleado**: sale del
alto real del contenido a 1440, porque un alto que sobra enseña papel y uno que falta corta la última
línea.

## De dónde sale

Publicado en el lienzo «Dos tiendas», `https://claude.ai/code/artifact/c52ec5bb-d146-464e-bc81-f32a1aa069f2`, que comparte con otra
tienda y lleva además una portada común. Esa portada no se copia aquí: no pertenece a ninguna de las
dos plantillas.

## Cómo se vuelve a sembrar

Los artboards citan sus imágenes por nombre suelto (`src="marzo-….webp"`), y las imágenes viven en
`../img/`, no junto a los artboards. Al sembrar un lienzo nuevo, cada una de las 9 fotografías de
`img/` se pasa con `--image` y el runtime las resuelve por nombre. Abiertos directamente desde esta
carpeta, los artboards salen con las imágenes rotas: es lo esperado.

## Reglas que ya se pagaron en este lienzo

- **El suelo va en `html, body` además de en el envoltorio `[data-suelo]`.** Sólo en el selector del
  canvas, el runtime lo pierde; sólo en el envoltorio, el frame pinta blanco donde no llega el contenido.
- **Todo texto a 108 del borde, o a 108 de su columna** cuando la columna empieza en una banda a sangre.
- **Ningún acento como color de letra.** Los acentos de estas tiendas miden por debajo de 4,5:1 sobre
  su suelo y sólo se usan en piezas de interfaz que pasan el 3:1.
