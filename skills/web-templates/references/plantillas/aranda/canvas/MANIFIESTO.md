# Manifiesto del lienzo · aranda

Los artboards de ARANDA. `Aranda.dc.html` es la **autoridad del diseño**: todo cambio empieza en un
lienzo y baja después a la maqueta, nunca al revés. Las cuatro láminas nuevas de esta entrega
(`Listado`, `Ficha`, `Nosotros`, `Contacto`) se dibujaron en el mismo idioma — misma cabecera, mismo
suelo, misma tipografía, mismo margen de página — porque el lienzo «seis portadas» del que sale la
portada nunca las dibujó y no hay lienzo de Claude Design que re-sembrar para ellas (ver «De dónde
sale» más abajo).

| Artboard | Página | Tamaño | Alto declarado |
|---|---|---|---|
| `Aranda.dc.html` | portada | 1440 × 3602 | **medido**, no tecleado |
| `Listado.dc.html` | listado del stock | 1440 × 1837 | **medido**, no tecleado |
| `Ficha.dc.html` | ficha del Hyundai Tucson A-2390 | 1440 × 1647 | **medido**, no tecleado |
| `Nosotros.dc.html` | nosotros | 1440 × 1842 | **medido**, no tecleado |
| `Contacto.dc.html` | contacto | 1440 × 1166 | **medido**, no tecleado |

`canvas.json` coloca los cinco artboards en dos filas — portada y listado arriba, ficha, nosotros y
contacto debajo — con 80px entre láminas de la misma fila y 124px entre filas. **El alto de cada
fila sale de una medida sobre el render** (Chrome headless por CDP, `getBoundingClientRect()` del
envoltorio de la lámina), corrida sobre las cinco láminas servidas desde esta carpeta. Los altos
bajaron al retirarse la franja de utilidad de las cinco cabeceras, y en portada y listado también al
sustituirse la banda de filtros por el buscador con fichas:

```
Aranda.dc.html       alto  3602  ancho 1440  imgs 10 (rotas 10)   (antes 3636)
Listado.dc.html      alto  1837  ancho 1440  imgs 0 (rotas 0)     (antes 1906)
Ficha.dc.html        alto  1647  ancho 1440  imgs 3 (rotas 3)     (antes 1687)
Nosotros.dc.html     alto  1842  ancho 1440  imgs 1 (rotas 1)     (antes 1882)
Contacto.dc.html     alto  1166  ancho 1440  imgs 0 (rotas 0)     (antes 1206)
```

Las imágenes salen «rotas» al abrir el fichero suelto desde esta carpeta: es lo esperado (ver «Cómo
se vuelve a sembrar»), y no mueve la altura porque cada `<img>` lleva `width`/`height` fijos en
línea.

## De dónde sale

`Aranda.dc.html` no viene de un artifact de Claude Design con URL propia. Salió del lienzo «seis
portadas», que dibujó de una sola vez seis marcas que ya vivían en la galería antigua del framework
(`feat(plantillas): the six brands of the sixth lienzo enter the library`, commit `b153c6a`): sólo
la portada de cada una, con sus fotografías y su manifiesto, dejando el resto de páginas como
trabajo pendiente. Ese lienzo no registró una URL de artifact reutilizable por marca — a diferencia
de `delao`, `marzo`, `barro`, `cadencia` o `escuadra`, cuyo `canvas_url` sí apunta a un artifact
publicado — así que **`ficha.md` deja `canvas_url` sin rellenar en vez de inventar uno**: no hay
lienzo de Claude Design del que re-sembrar `Listado.dc.html`, `Ficha.dc.html`, `Nosotros.dc.html` ni
`Contacto.dc.html`. Las cuatro se escribieron directamente como HTML en el idioma medido de
`Aranda.dc.html` — mismos tokens, misma cabecera, mismo pie — sin pasar por Claude Design. Si algún
día se dibujan en un lienzo real, esa lámina pasa a ser la autoridad y esta nota se retira, igual que
ya avisa `delao/canvas/MANIFIESTO.md` para sus cinco páginas derivadas.

## Cómo se vuelve a sembrar

Los cinco artboards citan sus imágenes por nombre suelto (`src="aranda-….webp"`), y las imágenes
viven en `../img/`, no junto a los artboards. Al sembrar un lienzo nuevo, cada una de las 10
fotografías de `img/` se pasa con `--image` y el runtime las resuelve por nombre. Abiertos
directamente desde esta carpeta, los artboards salen con las imágenes rotas: es lo esperado.

## Reglas que ya se pagaron en este lienzo

- **El suelo va en `html, body` además del envoltorio `[data-suelo]`.** `Aranda.dc.html`, tal y como
  llegó del lienzo «seis portadas», sólo lo declaraba en `body` y el envoltorio no llevaba el
  atributo `data-suelo` — no se ha tocado esa lámina porque no era parte de este encargo, pero las
  cuatro láminas nuevas sí llevan las dos cosas, que es la regla ya pagada en el resto de la
  biblioteca (`marzo`, `barro`, `escuadra`, `cadencia`): declarado sólo en el selector del canvas, el
  runtime lo pierde; declarado sólo en el envoltorio, el frame pinta blanco donde no llega el
  contenido.
- **El margen de página es 72px, no 108.** `Aranda.dc.html` usa `padding: 0 72px` en las nueve bandas
  de su portada — nunca 108px — lo que a 1440 es 5 %, no el 7,5 % (`clamp(1140px, 85vw, 100vw)`) que
  `mockup-guide.md` documenta como estándar de la casa y que sí usa `marzo` (108px). Las cuatro
  láminas nuevas copian el margen medido de `Aranda.dc.html` (72px), porque la autoridad del diseño
  es el artboard ya aprobado, no el estándar general cuando los dos discrepan. Se deja constancia
  aquí y en el informe de esta entrega en vez de reescribir la portada sin que se pidiera.
- **Todo texto de etiqueta pequeña se ha escrito en `#55666E`, nunca en `#7F929B`.** `#7F929B` mide
  2,98:1 sobre `#F3F6F7`, 3,24:1 sobre `#FFFFFF` y 2,67:1 sobre `#E4EAED` — bajo el 4,5:1 de AA en
  los tres fondos claros donde `Aranda.dc.html` lo usa (etiquetas de ficha técnica, cifras de la
  cabecera, referencias). Sólo pasa sobre fondo oscuro (`#111A1F`, 5,45:1). Las cuatro láminas nuevas
  no reutilizan `#7F929B` para texto sobre fondo claro; `ficha.md` § Paleta medida trae los números.
- **La altura del frame es un número que se mide, no que se teclea.** Los cinco valores de
  `canvas.json` salen literalmente de la corrida de `alto-contenido.mjs` de arriba.
