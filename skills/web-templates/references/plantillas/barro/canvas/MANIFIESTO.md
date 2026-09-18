# Manifiesto del lienzo · barro

Los artboards de Claude Design de BARRO. Son la **autoridad del diseño**: todo cambio empieza aquí y
baja después a la maqueta, nunca al revés.

| Artboard | Página | Tamaño |
|---|---|---|
| `Barro.dc.html` | portada | 1440 × 4675 |
| `BarroPieza.dc.html` | ficha del cuenco hondo | 1440 × 4840 |
| `Categoria.dc.html` | categoría · Mesa | 1440 × 2827 |
| `LaMarca.dc.html` | la marca | 1440 × 3342 |
| `Contacto.dc.html` | contacto | 1440 × 2104 |

`canvas.json` coloca los cinco artboards y guarda su alto. **El alto está medido, no tecleado**: sale del
alto real del contenido a 1440, porque un alto que sobra enseña papel y uno que falta corta la última
línea. Medido con `alto-contenido.mjs`: las tres láminas nuevas dan 2827 / 3342 / 2104, y las dos que
ya existían re-miden 4667 y 4832 — 8px por debajo de lo declarado en ambas, una cola de papel pequeña
que no se ha tocado porque esas dos láminas no forman parte de este encargo.

## De dónde sale

Publicado en el lienzo «Dos tiendas», `https://claude.ai/code/artifact/c52ec5bb-d146-464e-bc81-f32a1aa069f2`, que comparte con otra
tienda y lleva además una portada común. Esa portada no se copia aquí: no pertenece a ninguna de las
dos plantillas.

## Cómo se vuelve a sembrar

Los artboards citan sus imágenes por nombre suelto (`src="barro-….webp"`), y las imágenes viven en
`../img/`, no junto a los artboards. Al sembrar un lienzo nuevo, cada una de las 10 fotografías de
`img/` se pasa con `--image` y el runtime las resuelve por nombre. Abiertos directamente desde esta
carpeta, los artboards salen con las imágenes rotas: es lo esperado.

## Reglas que ya se pagaron en este lienzo

- **El suelo va en `html, body` además de en el envoltorio `[data-suelo]`.** Sólo en el selector del
  canvas, el runtime lo pierde; sólo en el envoltorio, el frame pinta blanco donde no llega el contenido.
- **Todo texto a 108 del borde, o a 108 de su columna** cuando la columna empieza en una banda a sangre.
- **Ningún acento como color de letra.** Los acentos de estas tiendas miden por debajo de 4,5:1 sobre
  su suelo y sólo se usan en piezas de interfaz que pasan el 3:1.
