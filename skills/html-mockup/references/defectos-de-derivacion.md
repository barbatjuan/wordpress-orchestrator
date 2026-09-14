# Defectos de derivación

Lo que sale mal al convertir un canvas en una maqueta. Cada entrada se pagó una vez, en la
plantilla piloto, con cuatro rondas de juez y dos barridos completos. **Se comprueban ANTES de
pedir el barrido, no después**: el barrido cuesta veinte minutos y el juez otra ronda, y todos
estos se ven en el fichero.

Ninguno de ellos dispara una regla de auditoría, ni desborda, ni falla contraste. Ese es
exactamente el motivo de que exista esta lista.

## Controles

**`appearance:none` escrito para los campos de texto mata las casillas del mismo selector.**
Medido: `input[type=checkbox]` a `0×12px`, sin borde y transparente, en la línea de consentimiento
RGPD. Sin error, sin desborde, contraste correcto, y una casilla legal que no se puede marcar.
Enumera los tipos, o devuelve `appearance:auto` a `checkbox` y `radio` justo después.

**Una regla de campo puede estar muerta por especificidad y parecer viva.** Las reglas que debían
dar forma a esa casilla perdían contra el selector de etiqueta del formulario, así que el texto
legal se renderizaba como una etiqueta de campo de 9px en versalitas. Comprueba el valor
COMPUTADO, no la existencia de la regla.

**Un `<select>` con `appearance:none` y sin flecha no se lee como un control.** Un juez ciego lo
describió como texto fijo. Y si al quitar `appearance` se pierde `background-color:transparent`,
el texto claro cae sobre el blanco del navegador y deja de leerse: ese fue un arreglo que
introdujo un defecto peor que el original.

**El objetivo táctil mínimo es 44px, y se mide en el que está al lado.** El botón de cabecera
medía 33,5px junto a una hamburguesa de 42. Dos controles adyacentes de tamaños distintos delatan
que nadie los midió.

## Tipografía

**Una interlínea menor que 1 pone la línea base por debajo de su propia caja.** Un titular de 88px
a `line-height:0.95` metía 19px de las colas de la «p» dentro de la banda oscura siguiente.
**Subir la interlínea no lo arregla**: el interlineado se reparte mitad arriba y mitad abajo, así
que cada píxel compra medio píxel de holgura. Lo que lo arregla es relleno inferior del contenedor,
que además es un control nativo.

**`overflow-wrap:anywhere` parte palabras a mitad sin producir ni un píxel de desborde.** Hace
falta por debajo de 1024 para que el móvil reflúya, y a 768 convirtió un logotipo en una letra por
línea. La exención no es sólo para el display: la necesita toda etiqueta de chrome — logotipo,
menú, botón, referencia, precio — porque son cadenas cortas en cajas estrechas.

**El barrido de palabra partida se hace sobre el documento, no sobre la página.** Cabecera y pie
viven FUERA de los contenedores de página; un barrido acotado a la página los deja sin mirar.

## Fondos y rejillas

**Una rejilla que dibuja sus líneas con `gap:1px` sobre un fondo entintado pinta también el relleno
del contenedor.** Si el elemento que lleva el fondo es además el que lleva la medida y su relleno,
salen dos delantales de color de línea a los lados: 63px a 1280, 32px a 768, 20px a 430. Se
arregla con `background-clip:content-box`, no con un div más. **Y se aplica a TODAS las rejillas
entintadas de la maqueta**: la primera vez se arregló en unas y no en otras, y la inconsistencia
es más visible que el defecto.

**Una rejilla nunca lleva más pistas que elementos.** Con la técnica del hueco entintado, una
celda que falta en la última fila no es un blanco: es una losa maciza del color de la línea.

## Pegajosos y primera pantalla

**Una cabecera pegajosa translúcida deja pasar el texto que corre por debajo.** A 94% de opacidad
se leía el fantasma. Y `backdrop-filter` **no es un control nativo de Elementor**: sería CSS a
medida contra el techo cero, así que el defecto visual y el de construcción son el mismo. Cabecera
opaca.

**Una banda de filtros pegajosa crece al estrecharse la pantalla.** Medido: 95px a 1280, 231px a
768, 376px a 430. Con la cabecera, ocupaba el 47% de un móvil de 932px de alto y dejaba sitio para
una tarjeta. La fracción de pantalla que se come va en dirección contraria al sitio disponible:
por debajo de 768, colapsa la banda o suéltala del pegado.

**Un panel lateral pegajoso pierde su función al colapsar a una columna.** La única llamada a la
acción de la ficha quedaba al 67% de profundidad. Reordenar el contenedor por punto de ruptura es
nativo (`order`), y sirve; pero comprueba dónde acaba: reordenado seguía a dos pantallas de
distancia. La pregunta no es «¿está antes?», es «¿se ve sin bajar?».

## Contenido

**Un teléfono de relleno delata la maqueta entera.** `+34 952 00 00 00` aparecía cinco veces. Un
juez ciego lo llamó lo único que impedía publicarla. Una marca ficticia lleva números ficticios que
parecen números.

**El `<title>` es contenido.** Los dos chasis viejos lo llevaban vacío. En un documento con
navegación interna sólo hay un elemento `<title>`: la ruta lo reescribe por página.

**Locale es-ES: el símbolo va detrás.** `68,00 €`, no `€68,00`. Y el copy es peninsular; el
generador viejo tenía copy rioplatense dentro de un catálogo con prefijos de Bilbao.

## Canvas y artboards

**El suelo de una lámina va en `body`, y además en su propio envoltorio.** Dos mitades del mismo
defecto, pagadas por separado en la misma pieza:

- Declarado en el selector `x-dc`, el fondo se ve al abrir el fichero suelto en el navegador y
  **desaparece dentro del runtime del canvas**. La lámina oscura salió crema sobre casi blanco,
  1,23:1, ilegible. La lámina hermana no lo sufrió porque pintaba su suelo en su primer div.
- Movido al envoltorio del contenido, el fondo cubre el contenido y nada más: **allí donde el
  contenido no llega, el frame pinta su propio blanco**. Medido: 406px de papel blanco al pie de una
  lámina oscura, y 184px en su ficha.

La regla ya estaba escrita en las normas de artefactos —«un body transparente toma prestado el suelo
del anfitrión»— y aun así se incumplió dos veces seguidas. `html, body { background: <suelo> }` más
el envoltorio. Las dos cosas.

**La altura del frame es un número que teclea una persona; mídela.** `canvas.json` declara `h` por
lámina y nadie la recalcula cuando el contenido cambia. Medido contra el alto real a 1440:

| lámina | `h` declarado | alto real | resultado |
|---|---|---|---|
| portada | 1420 | 1554 | último párrafo **cortado** por el borde |
| tienda A | 6733 | 6689 | 44px de cola |
| ficha A | 5561 | 5507 | 54px de cola |
| tienda B | 5400 | 4653 | **747px de cola** |
| ficha B | 5400 | 4781 | **619px de cola** |

Se mide con `alto-contenido.mjs` (Chrome headless, `getBoundingClientRect()` del envoltorio con el
`<helmet>` oculto) y se copia a `canvas.json`. Sobrar clipa igual de mal que faltar: sobrar enseña
papel, faltar corta una línea a media altura.

**Una banda a sangre crea un segundo raíl de texto.** Tres fotos de 480px pegadas borde con borde
piden pies alineados a cada foto —32px— mientras el rótulo de la sección va al margen de página
—108px—. El resultado son dos raíles compitiendo en la misma columna. Lo que lo resuelve sin perder
la rejilla: **los pies exteriores obedecen al margen de página, los interiores cuelgan de su foto**.
Con `box-sizing:border-box` y anchos fijos, subir el relleno no desborda nada.

**Una fotografía de ambiente no se repite entre la portada y la ficha.** El producto sí —la ficha de
un cuenco enseña ese cuenco—, pero el taller, la mesa puesta y las manos del alfarero repetidos en
dos láminas contiguas delatan que el catálogo de fotos se quedó corto y se rellenó. Se genera lo que
falte; son baratas comparadas con la impresión que dejan.

## Método

**Dos puntos de ruptura, 1024 y 767, y ningún tercero.** `es-builder.php` acepta exactamente
`_tablet` y `_mobile`; el chasis viejo emitía trece anchos. Un corte en cualquier otro ancho es
CSS a medida autorado dos pasos antes de que el QA lo cuente.

**El juez ciego mide a ojo sobre un JPEG escalado.** Un hallazgo suyo que dependa de unos pocos
píxeles se comprueba antes de creerlo: uno de ellos, un relleno de campo supuestamente desigual,
no se reprodujo — el CSS tenía un solo valor.

**Un arreglo se verifica con una medición, no con una afirmación.** Dos de los arreglos del piloto
introdujeron defectos nuevos, y los dos se cazaron volviendo a mirar, no releyendo el diff.

**Una discrepancia entre lo que mide la herramienta y lo que se ve se resuelve MIRANDO.** La primera
pasada de contraste sobre la tienda oscura reportó `#EDE7DA sobre #FFFFFF · 1,23:1`. Lo descarté como
bug del intérprete de CSS porque el fichero declaraba el fondo oscuro, «arreglé» el intérprete para
que leyera ese selector, y la medición se calló. La captura del usuario mostró la página pálida:
el hallazgo era cierto y me costó tres pasadas más. Una medición que discrepa del código es una
hipótesis sobre el RENDER, no sobre la fuente. Nunca se resuelve ajustando la herramienta hasta que
coincida con lo que dice el CSS.

**Una herramienta nueva está equivocada hasta que un caso de control diga lo contrario.** De los
cinco defectos que el medidor de geometría reportó la primera vez, **tres eran bugs suyos**: leer el
suelo de `body{}` cuando estaba declarado en otro sitio, medir la caja del elemento en vez de la
tinta del texto, y calcular la medida de lectura como `ancho / (px × 0,5)`. El cuarto apareció
después: un `Page.navigate` a una url que sólo cambia en su `#fragmento` es una navegación dentro del
mismo documento y **no emite evento de carga**, así que la herramienta se colgaba treinta segundos
por página en cualquier maqueta enrutada por hash. El quinto: un enlace de salto aparcado en
`left:-9999px` no es tinta al cristal, es el patrón de accesibilidad de manual.

**Un juez que no puede leer el texto pequeño acaba juzgando el número de filas.** Sobre una captura
reescalada, una rejilla de doce especificaciones se describió como «relleno para llenar altura».
Leídas en el fichero, las doce filas eran porcentaje de chamota, temperaturas de bizcocho y esmalte,
tolerancias de ±0,4 cm y el comportamiento en microondas: en cerámica hecha a mano, esa tabla ES el
producto. Un hallazgo que el propio juez marca como «no lo puedo resolver a este zoom» se comprueba
en la fuente antes de recortar nada.

**Un lienzo de cinco láminas escalado a 1680px no sirve para juzgar nada pequeño**, y el juez que
lo mira de todas formas devuelve hallazgos fantasma con la misma seguridad que los reales. En una
sola ronda produjo tres: una tabla de doce especificaciones descrita como relleno, un pie «pegado al
borde inferior, se lee cortado», y una barra de cierre «que no está» en una lámina cuyo fichero la
tiene idéntica a la de su hermana. Medidos después en la captura a tamaño real: **72px y 73px de
aire bajo el último píxel de contenido, cero filas casi blancas**, y las dos barras presentes.

De ahí dos reglas. **Al juez se le da la región a tamaño real, no el lienzo entero**: un recorte de
los últimos 900px pesa lo mismo que la vista general y responde la pregunta que la vista general no
puede. Y **lo que es aritmética se mide, no se pregunta**: cuánto aire hay bajo el contenido, si
existe una banda blanca, de qué color es el borde — eso son veinte líneas leyendo píxeles y una
respuesta exacta. El ojo se reserva para lo que sólo el ojo ve: composición, jerarquía, si la foto
enseña lo que dice su `alt`.
