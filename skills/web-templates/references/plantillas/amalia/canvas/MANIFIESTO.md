# Manifiesto del lienzo · amalia

Los artboards de Claude Design de AMALIA YOGA. Son la **autoridad del diseño**: todo cambio empieza aquí y
baja después a la maqueta, nunca al revés.

| Artboard | Página | Tamaño |
|---|---|---|
| `Amalia.dc.html` | el sitio entero: Inicio, Filosofía, Planes, Clase, Diario, Entrada y Contacto, conmutadas por estado | 1440 × 6499 (vista Inicio) |
| `HeroA.dc.html` | exploración inicial del hero de portada | 1440 × 1086 |

`canvas.json` coloca los dos artboards y guarda su alto. **El alto está medido, no tecleado**: Chrome sin
cabeza a 1440 con `support.js` del paquete de diseño, alto de `[data-suelo]` tras pintar. `Amalia.dc.html`
es una aplicación de una sola lámina; su alto es el de la vista que abre (Inicio). Las otras seis vistas se
midieron pulsando la navegación: Filosofía 2051, Planes 2604, Clase 2151, Diario 2276, Entrada 2379 y
Contacto 2155.

## De dónde sale

Llegó como paquete de traspaso de Claude Design con dos direcciones paralelas. Esta Plantilla es la
**dirección A, editorial cálido** (`Amalia Yoga.dc.html` y `Hero A.dc.html` en el paquete, renombrados sin
espacios como el resto de la biblioteca). La dirección B, «sereno verde», es otra Plantilla y no vive aquí.
No se dispone de la URL del lienzo compartido; queda en blanco en la ficha.

`support.js` no se copia: ninguna Plantilla de la biblioteca lo guarda junto a sus láminas. Para abrirlas en
el navegador se pone al lado el `support.js` del paquete de diseño; para sembrar un lienzo nuevo lo aporta
el runtime.

`HeroA.dc.html` es la exploración que precedió al hero de `Amalia.dc.html`. Se guarda como registro, lleva
las mismas correcciones de color y **no se deriva**: su cabecera a 430 empuja «Clase de prueba» fuera de
la pantalla y su círculo animado no tiene expresión nativa. Manda el hero de `Amalia.dc.html`.

## Cambios hechos al lienzo antes de derivar

El lienzo se corrigió aquí primero y la maqueta se derivó después, para que la siguiente derivación no
pierda nada. Todo lo que sigue está en `Amalia.dc.html`:

- **Color.** `#8A8070` → `#70685B` y `#7C7161` → `#70685B` (texto secundario y etiquetas), `#A65A38` →
  `#9E5535` (acento), `#6F675C` → `#8C8274` (rótulos sobre tinta), la barra de las migas `#C9A88E` →
  `#70685B`, y el borde de los campos `#D6CCBB` → `#8A8377`. Ratios en la paleta de `ficha.md`.
- **Sin expresión nativa, quitado.** El círculo radial `breathe`, el filete vertical `pulseline` (pasa a
  filete horizontal estático, como el del hero), la animación `drawline`, el `backdrop-filter` y la
  transparencia de la cabecera (opaca), y el `overflow-x: hidden` del envoltorio, que convertía el
  envoltorio en contenedor de desplazamiento y dejaba sin pegar la cabecera, el retrato de Filosofía y el
  lateral de la entrada. La entrada `rise` se queda: es la animación de entrada nativa.
- **Rejillas.** Las `repeat(auto-fit, minmax(…))` pasan a un número fijo de columnas por sección, y los
  separadores dibujados con un hueco de 1px sobre un fondo de línea pasan a bordes (cifras, planes, horario,
  ficha de clase). Así ninguna celda vacía se pinta y las columnas por punto de ruptura son controles
  nativos.
- **Enlaces.** Correo y teléfono a `mailto:`/`tel:`; las llamadas a reservar llevan al formulario de
  Contacto (`#reservar`); «Clases» en las migas y «Ver todas las clases» bajan a la sección de clases; los
  tres planes tienen destino; «Otras clases» ya no incluye la clase abierta; cada tarjeta del diario abre su
  propia entrada; los chips de categoría filtran; «Compartir» se retira del lateral de la entrada (enlaces a
  redes externas sin destino).
- **Formularios.** Nombre, correo y casilla de privacidad obligatorios; la guía lleva ya su casilla.
- **Planes.** «Suelta» no cambia con el conmutador (antes pasaba a un bono de 130 €, un −18,75 % frente al
  −15 % anunciado): su bono de 10 cuesta 136 €, el mismo −15 %. «Lo más elegido» se distingue con el
  rótulo en acento, fondo alterno y botón relleno. Precios con la cifra delante del «€».
- **Contenido nuevo.** Horario semanal en Planes (catorce clases, las mismas horas que cada ficha y que el
  «14» del hero), entradilla en la cabecera de Filosofía, y el cuerpo de las cinco entradas del diario que
  sólo tenían título.
- **Copy.** «Cinco formas» → «Seis formas» (hay seis tarjetas); «10 años de práctica» → «10 años de sala»
  y «India y Lisboa» → «Mysore y Lisboa», para que cuadre con 2009–2013 en Mysore, clase desde 2011 y sala
  desde 2016; la formación de Amalia ya no cita a una profesora real; teléfono de relleno sustituido; la
  sala abre hasta las 22:00 de lunes a jueves para que quepa la clase de restaurativo de las 21:00.
- **Composición.** Filosofía: la foto pasa de 4/5 a 4/3 junto a un texto de 7 columnas (medía
  ~430px de hueco vertical a 1280); lateral de contacto con los horarios en dos líneas; medida de lectura
  de la entrada a 68ch; «Seguir leyendo» con tres tarjetas.
- **Números en monoespaciada** (`ui-monospace`) pasan a Archivo con cifras tabulares: la pareja de la
  Plantilla son dos familias, no tres.

## Cómo se vuelve a sembrar

Los huecos de foto del lienzo siguen siendo rayados y cada etiqueta nombra el slug que le toca
(`amalia-hero · práctica en la sala`). Las diecinueve fotografías viven en `../img/`; al sembrar un lienzo
nuevo se pasan con `--image` y se sustituye cada hueco por su `<img>`.

## Reglas que ya se pagaron en este lienzo

- **El suelo va en `html, body` además de en el envoltorio `[data-suelo]`**, en las dos láminas.
- **Una lámina que es una aplicación mide lo que abre.** El alto de `canvas.json` es el de Inicio; si la
  vista inicial cambia, se vuelve a medir.
