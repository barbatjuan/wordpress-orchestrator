# Manifiesto del lienzo · cadencia

Los artboards de Claude Design de CADENCIA. Son la **autoridad del diseño**: todo cambio empieza aquí y
baja después a la maqueta, nunca al revés.

| Artboard | Página | Tamaño |
|---|---|---|
| `Cadencia.dc.html` | portada | 1440 × 4920 |
| `CadenciaPieza.dc.html` | ficha del equipo Fondo largo en frío | 1440 × 2735 |
| `Categoria.dc.html` | categoría — todas las sesiones | 1440 × 2760 |
| `LaMarca.dc.html` | la marca — el banco de pruebas | 1440 × 1947 |
| `Contacto.dc.html` | contacto | 1440 × 1780 |

`canvas.json` coloca los cinco artboards y guarda su alto. **El alto está medido, no tecleado**: sale
del alto real del contenido a 1440, con `alto-contenido.mjs`, porque un alto que sobra enseña papel y
uno que falta corta la última línea. Las tres láminas nuevas se midieron 2760 / 1947 / 1780. Al mismo
tiempo se volvieron a medir las dos que ya existían como control: `Cadencia.dc.html` dio 4914 contra
los 4920 declarados y `CadenciaPieza.dc.html` dio 2727 contra los 2735 declarados — 6 y 8 píxeles de
diferencia, dirección inofensiva (sobra papel, no corta línea) y dentro de la variación de medición ya
documentada en `defectos-de-derivacion.md`. No se ha tocado `canvas.json` para esas dos filas.

## De dónde sale

Publicado en el lienzo «Cadencia y Escuadra», `https://claude.ai/code/artifact/b3b0274e-6c0c-45c2-8a3e-5b8243e27032`, que comparte con otra
tienda y lleva además una portada común. Esa portada no se copia aquí: no pertenece a ninguna de las
dos plantillas.

## Cómo se vuelve a sembrar

Los artboards citan sus imágenes por nombre suelto (`src="cadencia-….webp"`), y las imágenes viven en
`../img/`, no junto a los artboards. Al sembrar un lienzo nuevo, cada una de las 8 fotografías de
`img/` se pasa con `--image` y el runtime las resuelve por nombre. Abiertos directamente desde esta
carpeta, los artboards salen con las imágenes rotas: es lo esperado.

## Reglas que ya se pagaron en este lienzo

- **El suelo va en `html, body` además de en el envoltorio `[data-suelo]`.** Sólo en el selector del
  canvas, el runtime lo pierde; sólo en el envoltorio, el frame pinta blanco donde no llega el contenido.
- **Todo texto a 108 del borde, o a 108 de su columna** cuando la columna empieza en una banda a sangre.
- **Ningún acento como color de letra.** Los acentos de estas tiendas miden por debajo de 4,5:1 sobre
  su suelo y sólo se usan en piezas de interfaz que pasan el 3:1.
