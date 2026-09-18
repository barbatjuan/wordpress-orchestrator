# Manifiesto del lienzo · forja

Los artboards de Claude Design de FORJA BOX. Son la **autoridad del diseño**: todo cambio empieza aquí y
baja después a la maqueta, nunca al revés.

| Artboard | Página | Tamaño |
|---|---|---|
| `Forja.dc.html` | inicio | 1440 × 7576 |
| `ForjaSecciones.dc.html` | biblioteca de las secciones de inicio, numeradas S01–S12 con su nota de construcción | 1440 × 9595 |
| `ElBox.dc.html` | el box (instalaciones y recuperación) | 1440 × 2889 |
| `Disciplinas.dc.html` | disciplinas y horario semanal (abre en lunes) | 1440 × 3683 |
| `Coaches.dc.html` | coaches | 1440 × 2146 |
| `Planes.dc.html` | planes | 1440 × 2224 |
| `Contacto.dc.html` | contacto con el formulario de la clase de prueba | 1440 × 1960 |

`canvas.json` coloca los siete artboards y guarda su alto. **El alto está medido, no tecleado**: Chrome sin
cabeza a 1440 × 900 con el `support.js` del paquete de diseño al lado, alto del envoltorio `[data-suelo]`
tras pintar, con las correcciones de abajo ya aplicadas. El hero de inicio mide `88vh`, así que el alto de
`Forja.dc.html` y de `ForjaSecciones.dc.html` es el de una ventana de 900 px de alto.

## De dónde sale

Llegó como exportación de Claude Design sin README, con una segunda exportación que sólo corrigió enlaces del
pie en cinco láminas (ya incorporada): siete láminas (`Gym Landing`, `Gym Secciones`, `El Box`,
`Disciplinas`, `Coaches`, `Planes`, `Contacto`) y el runtime. Se renombraron sin espacios como el resto de la
biblioteca y se corrigieron los `href` internos. No se dispone de la URL del lienzo compartido; queda en
blanco en la ficha.

`ForjaSecciones.dc.html` **no es una página**: repite las secciones de inicio una por una con un rótulo
(«S04 Contadores · Fila 4 col · counter widget») y una nota de constructor. Se guarda como registro de la
intención de construcción y **no se deriva**; manda `Forja.dc.html`. Lleva las mismas correcciones.

`support.js` no se copia: ninguna Plantilla de la biblioteca lo guarda junto a sus láminas.

**No hay artboards móviles de 390.** El paquete trae sólo escritorio. La maqueta diseñó 1024 y 767 al
derivar (menú plegado, rejillas, horario en una columna); si se dibujan las láminas móviles, pasan a ser la
autoridad de esos anchos.

## Cambios hechos al lienzo antes de derivar

- **Suelo.** `html, body` con el fondo de tinta además del envoltorio, que ahora lleva `data-suelo`.
- **Marquesina, conservada.** La marquesina `om-marquee` sigue en una fila y en bucle, como se exportó: el
  usuario la pidió así. Es la única regla de CSS a medida de la plantilla (ver la ficha).
- **Sin expresión nativa, quitado.** El `backdrop-filter` y la
  transparencia de la cabecera (opaca). El `overflow-x: hidden` del envoltorio, que dejaba sin pegar la
  cabecera. La regla de impresión con `!important`.
- **Rejillas.** Las `repeat(auto-fit, minmax(…))` pasan a columnas fijas por sección (cifras 4, box 4,
  coaches 4, planes 3, recuperación 2, captación 2, contacto 2, pie `1.4fr + 3`). La rejilla de disciplinas y
  las listas de recuperación y de datos de contacto dibujaban la línea con un hueco de 2 px sobre fondo de
  línea: pasan a bordes por lado, sin celdas pintadas.
- **Color.** Rótulos del pie `rgba(242,242,240,.4)` (3,50:1) → `.55` (5,73:1). Borde de los campos
  `rgba(255,255,255,.16)` (1,52:1, y es lo único que dibuja el campo) → `#767674` (4,21:1 sobre `#0F0F0F`).
  Ratios en la paleta de `ficha.md`.
- **Tipografía.** Los números de las disciplinas en `ui-monospace` pasan a Barlow Condensed 600 con cifras
  tabulares: la Plantilla son tres familias, no cuatro. El título de tarjeta pasa a `clamp(24px, 2,2vw, 32px)`
  para que «Halterofilia» quepa en cuatro columnas entre 1025 y 1280.
- **Copy.** «Seis formas de entrenar» → «Ocho» (hay ocho tarjetas, como en la página Disciplinas). «90 atletas
  por sesión máximo» → «12 atletas por sesión como máximo». Contadores: «64 clases por semana» → 61 (las del
  horario) y «9 coaches certificados» → 4 (los que se presentan); la entradilla de Coaches, que hablaba de
  nueve y presentaba cuatro, se reescribe. El antetítulo «CrossFit & entrenamiento funcional» →
  «Entrenamiento funcional y halterofilia»: una marca ficticia no se presenta como afiliada de una marca
  registrada. Teléfono de relleno `+34 910 000 000` sustituido. Precios con espacio duro antes del «€».
- **Enlaces.** Todas las llamadas a la prueba y las de los planes llevan al formulario de Contacto
  (`Contacto.dc.html#reservar`); la banda final apuntaba a sí misma. «Hablar con un coach» iba a Planes y va a
  Contacto. «Ver planes» a Planes. Correo y teléfono a `mailto:`/`tel:`. La segunda exportación del lienzo llevó
  Instagram y YouTube del pie a la banda de la prueba; en la maqueta son texto con el nombre de la cuenta y
  WhatsApp lleva a Contacto, para que un enlace llamado como una red no lleve a otra parte de la página.
- **Formulario.** Nombre y correo obligatorios, teléfono opcional, casilla de privacidad obligatoria antes del
  botón (antes, una frase «al enviar aceptas»).
- **Contenido nuevo: horario semanal** en `Disciplinas.dc.html`, en lugar del bloque de recuperación que
  repetía el de El box. Siete pestañas por día y 61 franjas (hora, disciplina, coach, minutos), las mismas
  duraciones que las tarjetas. Es lo que pide el Objetivo `clase-de-prueba`: llenar un horario semanal; la
  cifra de inicio sale de aquí.
- **Retratos.** La etiqueta de los cuatro huecos de coach nombra a la persona (`[ retrato — Marta Ferrer, 900×1200 ]`).

## Cómo se vuelve a sembrar

Los huecos de foto del lienzo siguen rayados y cada etiqueta describe la foto. Las once fotografías viven en
`../img/` con el slug de `manifiesto-imagenes.md`; al sembrar un lienzo nuevo se pasan con `--image` y se
sustituye cada hueco por su `<img>`.

## Reglas que ya se pagaron en este lienzo

- **Un contador es un dato.** 64 clases, 9 coaches y «seis formas» con ocho tarjetas eran tres cifras que el
  resto del lienzo desmentía. Cada cifra de inicio se cuenta en la página que la sostiene.
- **Una biblioteca de secciones no es una página.** `ForjaSecciones.dc.html` se conserva, pero la maqueta se
  deriva de `Forja.dc.html`.
