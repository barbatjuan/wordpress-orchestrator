# Manifiesto del lienzo · forja-fucsia

Los artboards de Claude Design de FORJA BOX en su modelo B, «fucsia». Son la **autoridad del
diseño**: todo cambio empieza aquí y baja después a la maqueta, nunca al revés.

| Artboard | Página | Tamaño |
|---|---|---|
| `ForjaFucsia.dc.html` | inicio | 1440 × 7422 |
| `Disciplinas.dc.html` | disciplinas | 1440 × 3265 |
| `ElBox.dc.html` | el box | 1440 × 2990 |
| `Coaches.dc.html` | coaches | 1440 × 2247 |
| `Planes.dc.html` | planes | 1440 × 2262 |
| `Contacto.dc.html` | contacto | 1440 × 2058 |

`canvas.json` coloca las seis láminas en una fila y guarda su alto. **El alto está medido, no
tecleado**: alto real de `#dc-root > .sc-host > div` a 1440 × 900 en Chrome sin cabeza, con el
runtime montado y los marcos de foto con su proporción fija. El inicio depende del alto de la
ventana: su primera pantalla es `min-height:92vh`.

## De dónde sale

Del paquete de exportación de FORJA BOX, que trae dos modelos del mismo encargo con el mismo
contenido. Esta Plantilla es el **modelo B**; el A es la Plantilla hermana `forja`.

| En el paquete | Aquí |
|---|---|
| `Gym Landing B.dc.html` | `ForjaFucsia.dc.html` — la lámina principal lleva el nombre de la Plantilla, como `AmaliaSalvia.dc.html` |
| `El Box B.dc.html` | `ElBox.dc.html` |
| `Disciplinas B.dc.html` | `Disciplinas.dc.html` |
| `Coaches B.dc.html` | `Coaches.dc.html` |
| `Planes B.dc.html` | `Planes.dc.html` |
| `Contacto B.dc.html` | `Contacto.dc.html` |
| `support.js` | no se copia: el runtime lo pone al sembrar el lienzo |

Las láminas sin « B» del paquete y `Gym Secciones.dc.html` son del modelo A y no se usan aquí.

## Límites que se heredan del paquete

- **No hay láminas móviles de 390.** Las láminas se apoyan en `vw`, `clamp()` y rejillas `auto-fit`
  sin una sola media query. El menú plegado, las rejillas de 2 y 1 columnas y la marquesina a 430 se
  diseñaron al derivar la maqueta. Es la deuda principal de este lienzo frente al glosario.
- **No hay lámina de detalle.** Las tarjetas de disciplina no enlazan a nada ni prometen ficha, así
  que no hay enlace muerto; pero el tipo corporate pide una página de detalle y este lienzo no la
  dibuja (ver `../ficha.md`, «Páginas»).
- **No hay horario de clases.** El lienzo da el horario de apertura del box y «64 clases por
  semana», no la parrilla semanal que el Objetivo `clase-de-prueba` promete llenar.
- **Las cabeceras de las páginas interiores son un rayado bajo velo, sin etiqueta de foto.** La
  maqueta las deriva como banda de tinta plana; no se les asignó fotografía.
- **Los marcos de foto siguen siendo marcos.** Las 11 fotografías viven en `../img/` y las pinta la
  maqueta; el lienzo conserva los marcos rayados con su etiqueta, tal como se exportaron.

## Cambios hechos en la copia del lienzo

Sólo tres clases de cambio: enlaces renombrados, color y copy. Todo lo demás queda como se
exportó —la marquesina animada en una fila, los rayados, el `overflow-x:hidden` del envoltorio, el
`backdrop-filter` de la cabecera, la monoespaciada de los números—, por decisión del usuario.
Cada sustitución se hizo con recuento de coincidencias: si una cadena no aparecía las veces
esperadas, no se escribía nada.

**Enlaces.** Los `href="X B.dc.html"` pasan a los nombres nuevos (12 en inicio, 15 en cada página
interior). «Prueba gratis» de la cabecera, «Reservar prueba gratis» (se enlazaba a sí mismo con
`#prueba`) y «Hablar con un coach» (llevaba a Planes o a `#precios`) llevan a `Contacto.dc.html`,
que es donde está el formulario de la clase de prueba. El correo y el teléfono de Contacto pasan a
`mailto:` y `tel:`.

**Color** (`color.php --contraste`, detalle en `../ficha.md` § Paleta medida):

- Fucsia sobre claro `#FF1F6B` → `#D71A5A` en la palabra «entrenar», los números de disciplina y
  los doce puntos de la marquesina. Medía 3,37:1 sobre el papel y 3,71:1 sobre blanco; ahora 4,55:1
  y 5,02:1.
- Meta de disciplina `rgba(8,8,8,.55)` → `.6`: 4,37:1 → 5,22:1 sobre papel.
- Rótulo y periodo del plan Ilimitado sobre fucsia: `rgba(8,8,8,.7)` y `opacity:.7` → tinta plena.
  Medían 3,89:1; ahora 5,40:1. El periodo de los tres planes toma el color de su rótulo.
- Borde de campo `rgba(8,8,8,.22)` → `#8A8A88` (5 campos): 1,65:1 → 3,46:1 sobre el blanco del campo.
- Correo de Contacto `#C4145A` → `#D71A5A`, el mismo fucsia de texto que el resto.
- Velo del hero, radial `.55 → .88`, y velo de «Nadie entrena solo», lineal `.7 → .88` → plano
  `rgba(8,8,8,.8)`. En el centro del radial el fucsia medía 1,45:1 sobre la foto real; medido con
  `scrim.php --peor-pixel` sobre las fotos enteras a `.8`: papel 10,44:1 y 10,62:1, fucsia 3,10:1 y
  3,15:1 (texto grande).
- Antetítulos sobre foto («Funcional y halterofilia · Madrid», «La regla de la casa») pasan de fucsia a
  papel, y el cuadro del hero de papel a fucsia: un antetítulo de 13px no llega a 4,5:1 en fucsia
  sobre la foto con ningún velo por debajo de `.9`.
- Cabecera `rgba(8,8,8,.86)` → `#080808`: el texto que pasaba por debajo se leía a través.
- Suelo de tinta también en `html`, no sólo en `body`.

**Copy.**

- Antetítulo del hero «CrossFit & funcional · Madrid» → «Funcional y halterofilia · Madrid», también en el
  `<title>` de la maqueta: una marca ficticia no se presenta como afiliada de una marca registrada (el
  mismo cambio que hizo `forja`).
- Teléfono de relleno `+34 910 000 000` → `910 38 62 47` en los seis pies y en Contacto; el
  marcador del campo de teléfono `+34 600 000 000` → `+34`.
- Instagram, YouTube y WhatsApp eran enlaces a `#prueba`: pasan a texto (`@forjabox`, `Forja Box`,
  `644 27 19 83`). No hay URL real que enlazar y un enlace falso es un enlace muerto.
- Precios `49€`, `79€`, `149€` → con espacio duro antes del euro. Rangos horarios con espacio duro
  alrededor del guion.
- Formulario: casilla obligatoria de privacidad antes del botón; la nota queda en «Te respondemos
  en menos de 24 h laborables».

## Cómo se vuelve a sembrar

Las láminas citan `./support.js` y las tres familias de Google Fonts; al abrirlas sueltas desde esta
carpeta salen sin runtime. Al sembrar un lienzo nuevo, las fotografías de `../img/` se pasan con
`--image` si se quiere ver la foto dentro del marco; el alto no cambia, porque cada marco fija su
proporción.
