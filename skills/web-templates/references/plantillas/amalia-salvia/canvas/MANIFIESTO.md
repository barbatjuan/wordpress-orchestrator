# Manifiesto del lienzo · amalia-salvia

Los artboards de Claude Design de AMALIA en su dirección B, «sala verde». Son la **autoridad del
diseño**: todo cambio empieza aquí y baja después a la maqueta, nunca al revés.

| Artboard | Página | Tamaño |
|---|---|---|
| `AmaliaSalvia.dc.html` | las siete vistas del sitio (inicio, filosofía, planes, clase, diario, entrada, contacto), conmutadas por estado; el artboard abre en inicio | 1440 × 6521 |
| `HeroCentrado.dc.html` | exploración inicial de un hero centrado; no es autoridad de ninguna página | 1440 × 1027 |

`canvas.json` coloca los dos artboards y guarda su alto. **El alto está medido, no tecleado**: alto
real del envoltorio `[data-suelo]` (o del primer hijo de `x-dc`) a 1440 × 900 en Chrome sin cabeza,
con las 22 fotografías resueltas junto al artboard y el runtime ya montado.

## De dónde sale

Del paquete de traspaso «Amalia Yoga — plantilla web premium (2 direcciones)», que trae dos
direcciones completas del mismo encargo con el mismo contenido. Esta Plantilla es la **dirección B**:

| En el paquete | Aquí | Por qué el nombre |
|---|---|---|
| `Amalia Yoga v2.dc.html` | `AmaliaSalvia.dc.html` | La lámina principal lleva el nombre de la marca, como `Lumiere.dc.html` o `Barro.dc.html`; «Salvia» la distingue de la dirección A |
| `Hero B.dc.html` | `HeroCentrado.dc.html` | Nombra lo que explora, sin espacios |
| `support.js` | no se copia | Ninguna Plantilla de la biblioteca lo versiona: sus láminas citan `./support.js` y el runtime lo pone al sembrar el lienzo |

La dirección A (`Amalia Yoga.dc.html`) es otra Plantilla, `amalia`, y no se usa aquí.

## Límites que se heredan del paquete

- **Una sola lámina para siete vistas.** El traspaso es una SPA: una lámina con estado `page`, no
  una lámina por página. El lienzo se siembra abierto en inicio; las otras seis vistas se ven
  cambiando el estado dentro del runtime. Queda como deuda dibujar una lámina por página si el
  lienzo de un cliente lo necesita.
- **No hay láminas móviles de 390.** El traspaso se apoya en `clamp()` y rejillas `auto-fit`, sin
  una sola media query. El menú plegado y la columna única se diseñaron al derivar la maqueta, no
  en el lienzo. Es la deuda principal de este lienzo frente al glosario.
- **`HeroCentrado.dc.html` es de otra paleta.** Es la exploración previa a la dirección: Newsreader
  + DM Sans sobre crudo `#EFE9DF`, acento tostado. Se guarda como registro de por qué el hero final
  es un pase de diapositivas a sangre y no un titular centrado sobre tres fotos. A 430 su bloque
  «Diario + Reservar» cae entero fuera de la pantalla, y a 1280 una imagen sube hasta 14px bajo un
  enlace con el hero cortado por el pliegue: defectos de la exploración, no de la Plantilla, y no se
  corrigen porque ninguna página se deriva de ella.

## Cambios hechos en la copia del lienzo

Todos se hicieron primero aquí y después en la maqueta, con recuento de coincidencias (si una
cadena no aparecía las veces esperadas, no se escribía nada).

**Color re-medido** (`color.php --contraste`, detalle en `../ficha.md` § Paleta medida):

- Texto secundario `#7B8A7A` → `#626D61` (43 usos). Medía 3,32:1 sobre el papel y 3,06:1 sobre el
  alterno.
- Tenue sobre tinta `#7F907F` → `#819281` (5 usos, pie). Medía 4,42:1.
- Etiqueta de marco `#6E7D6D` → `#667365` (sólo quedaba en las etiquetas de los mapas).
- Borde de campo `#C3CFBD` → `#81897D` (7 campos). Medía 1,47:1; es el único filete que identifica
  un control.
- Separador de migas `#93AE92` → `#626D61`: una barra de texto a 2,19:1.
- Velo de las diapositivas: degradado `rgba(24,36,28,.48)` → `.72` sustituido por un velo plano a
  `.72`, y filetes y puntos de los controles de `.45–.55` a `.6`. Medido sobre las tres fotografías
  reales con `scrim.php --peor-pixel`: las tres tienen blanco puro bajo el texto, y a 0,48 el
  antetítulo medía 2,18:1.

**Fuera por no tener mapeo nativo:** las animaciones `breathe`, `drawline` y `pulseline` (y el filete
vertical animado de la guía), los `transform` de `:hover` en tarjetas y botones, el contador «01 / 03»
del pase (la paginación nativa es puntos o fracción, no las dos) y la monoespaciada del sistema de
los números de orden, que pasan a Jost 500 con cifras tabulares. `rise` se queda: es la animación de
entrada nativa.

**Defectos del traspaso corregidos** (los mismos que el barrido de la dirección A encontró en su
lámina, comprobados uno a uno en ésta):

- `overflow-x:hidden` en `html, body` anulaba el `position:sticky` del raíl, del retrato de Filosofía
  y de la columna de la entrada. Retirado; el suelo va en `html, body` y en `[data-suelo]`.
- Los botones de los planes no llevaban a ningún sitio; ahora abren Contacto. «Otras clases» ya no
  incluye la clase abierta. Cada tarjeta del diario abre su propia entrada: se escribieron las cinco
  que faltaban, con cuerpo, cita y cierre, y «Seguir leyendo» enseña dos.
- Correo y teléfono pasan a `mailto:` y `tel:`, con un teléfono de marca ficticia que parece un
  teléfono (`910 47 23 58`) en lugar de `+34 910 00 00 00`.
- Formularios con `required` y casilla de privacidad obligatoria también en la guía.
- «Anual · −15 %»: la clase suelta pasaba a un bono de diez clases de 130 €, un 18,75 % sobre
  16 € × 10. Ahora cuesta 136 € (−15 %). Precios con el euro detrás y espacio duro.
- «Lo más elegido» dejaba de ser un texto gris: es una etiqueta en la fila del nombre del plan y su
  botón va relleno.
- «Ver planes y horarios» aterrizaba en una página sin horario: Planes lleva ahora el horario
  semanal (once clases), que además es lo que esta Plantilla existe para llenar.
- El conmutador de precios sale de la fila del titular y va encima de los planes, como los títulos
  de unas pestañas nativas.
- Copy coherente: «Seis formas de bajar el ritmo» sobre seis tarjetas; «10 años de sala» en lugar de
  «10 años de práctica»; Amalia se formó en Mysore (2009–2013) y en Lisboa (2014–2015) y da clase
  desde 2013; el restaurativo del miércoles pasa a las 20:30 y el viernes cierra a las 20:30, para
  que ninguna clase termine con la sala cerrada; «Profesora» pasa a «Imparte» (Dani Otero da nidra);
  los rangos horarios llevan espacio duro alrededor del guion.

**Fotografías.** Los marcos rayados con etiqueta pasan a `<img>` sobre un tono plano, citando las
fotografías por nombre suelto (`src="amalia-salvia-slide-1.webp"`) o por el campo `src` de sus datos.
Los dos mapas siguen siendo marcos: en el sitio son el widget de mapa, no una foto.

## Cómo se vuelve a sembrar

Las 22 fotografías viven en `../img/`, no junto a los artboards. Al sembrar un lienzo nuevo, cada una
se pasa con `--image` y el runtime las resuelve por nombre. Abierto directamente desde esta carpeta,
el artboard sale con las imágenes rotas: es lo esperado, y no cambia la altura, porque cada marco
fija su proporción.
