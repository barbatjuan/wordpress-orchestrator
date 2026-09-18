# Manifiesto del lienzo · escuadra

Los artboards de Claude Design de ESCUADRA. Son la **autoridad del diseño**: todo cambio empieza aquí y
baja después a la maqueta, nunca al revés.

| Artboard | Página | Tamaño |
|---|---|---|
| `Escuadra.dc.html` | portada | 1440 × 6820 |
| `EscuadraPieza.dc.html` | ficha de la silla Aro | 1440 × 2515 |
| `Categoria.dc.html` | categoría — departamento Cocina | 1440 × 2066 |
| `LaMarca.dc.html` | la marca | 1440 × 2854 |
| `Contacto.dc.html` | contacto | 1440 × 1986 |

`canvas.json` coloca los cinco artboards y guarda su alto. **El alto está medido, no tecleado**: sale
del alto real del contenido a 1440, porque un alto que sobra enseña papel y uno que falta corta la
última línea. Medido con `alto-contenido.mjs`: las tres láminas nuevas dieron 2066 / 2854 / 1986,
tecleadas tal cual en `canvas.json`. Las dos existentes se remidieron como control y devolvieron 6810
y 2506 contra los 6820 y 2515 declarados — diez y nueve píxeles de sobra, la dirección inofensiva, y
más cerca de variación de medición que de deriva: se anota aquí y no se persigue, igual que hizo BARRO
con sus ocho píxeles.

**Por qué Cocina y no otro departamento.** ESCUADRA tiene seis departamentos en la navegación, pero
sólo Cocina, Dormitorio y Baño llevan la pareja de fotografías que hace de un departamento algo más que
un nombre en un menú: un recorte «índice» en la portada y la misma foto entera más abajo, la relación
que `ficha.md` ya documentaba. De los tres, Cocina es el más grande (386 artículos, el recuento más
alto del surtido) y el que mejor sostiene un registro largo de medidas dispares — un plato, un tarro,
un carro de cocina — frente a la gama más estrecha de Dormitorio o Baño. La categoría dibujada es
Cocina; las otras cinco entradas de navegación no tienen lámina propia todavía.

## De dónde sale

Publicado en el lienzo «Cadencia y Escuadra», `https://claude.ai/code/artifact/b3b0274e-6c0c-45c2-8a3e-5b8243e27032`, que comparte con otra
tienda y lleva además una portada común. Esa portada no se copia aquí: no pertenece a ninguna de las
dos plantillas.

## Cómo se vuelve a sembrar

Los artboards citan sus imágenes por nombre suelto (`src="escuadra-….webp"`), y las imágenes viven en
`../img/`, no junto a los artboards. Al sembrar un lienzo nuevo, cada una de las 16 fotografías de
`img/` se pasa con `--image` y el runtime las resuelve por nombre. Abiertos directamente desde esta
carpeta, los artboards salen con las imágenes rotas: es lo esperado.

## Reglas que ya se pagaron en este lienzo

- **El suelo va en `html, body` además de en el envoltorio `[data-suelo]`.** Sólo en el selector del
  canvas, el runtime lo pierde; sólo en el envoltorio, el frame pinta blanco donde no llega el contenido.
- **Todo texto a 108 del borde, o a 108 de su columna** cuando la columna empieza en una banda a sangre.
- **Ningún acento como color de letra.** Los acentos de estas tiendas miden por debajo de 4,5:1 sobre
  su suelo y sólo se usan en piezas de interfaz que pasan el 3:1.
