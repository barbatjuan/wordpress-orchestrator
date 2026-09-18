# Manifiesto del lienzo · noir

La lámina de Claude Design de MAISON NOIR. Es la **autoridad del diseño**: todo cambio empieza aquí y baja
después a la maqueta, nunca al revés.

| Artboard | Página | Tamaño |
|---|---|---|
| `Noir.dc.html` | tienda completa en una sola lámina: portada, y como estados de la misma lámina tienda, ficha (Nuit Absolue), cesta, pago, la Maison y contacto | 1440 × 4722 (portada) |

`canvas.json` coloca la lámina y guarda su alto. **El alto está medido, no tecleado**: Chrome a 1440 × 900
con el `support.js` del paquete al lado, alto del envoltorio de la lámina tras pintar, en el estado inicial
(portada). Las demás páginas son estados del mismo componente (`page` en su script) y no tienen alto propio.

## De dónde sale

Llegó como exportación de Claude Design («Maison Noir Tienda.dc.html») con su `support.js` y una miniatura,
sin README. Se renombró sin espacios como el resto de la biblioteca. **Se copió tal cual, byte a byte**: no
se ha corregido nada dentro del lienzo; las correcciones viven en la maqueta y se listan abajo. No se
dispone de la URL del lienzo compartido.

`support.js` no se copia: ninguna Plantilla de la biblioteca lo guarda junto a sus láminas. Para renderizar
el lienzo se sirve desde fuera del repositorio con su `support.js` al lado.

**Recursos remotos, sólo en el lienzo.** Tres URL, las tres de Google Fonts: dos `preconnect`
(`fonts.googleapis.com`, `fonts.gstatic.com`) y la hoja de Bodoni Moda (redonda 400 y 500, itálica 400) y
Jost (300, 400, 500). La maqueta no carga ninguna: embebe Bodoni Moda 400 redonda e itálica y Jost 300–500
desde `_fonts.php`. El 500 de Bodoni que pide la hoja no lo usa ningún elemento del lienzo.

**No hay artboards móviles de 390.** La maqueta diseñó 1024 y 767 al derivar.

## Lo que la maqueta corrige del lienzo

- **Huecos de foto.** Los diecinueve rayados con etiqueta pasan a catorce fotografías (el producto se repite entre portada, tienda, ficha y cesta) (`manifiesto-imagenes.md`). El de
  contacto («fachada de la boutique · 4:3 o mapa estático») queda como mapa estático, que el propio lienzo
  admite: no hay foto de fachada.
- **Rejillas.** Todas las `repeat(auto-fit, minmax(…))` pasan a columnas fijas por sección con sus dos
  puntos de ruptura. La franja de ventajas dibujaba sus separadores con `gap:1px` sobre fondo de línea en una
  sección con relleno lateral: dos delantales de color de línea a los lados a 1440. Pasa a borde por celda.
- **Margen.** El lienzo usa `max(22px, min(15vw, 50vw − 420px))`, que sigue creciendo pasado 1440. La maqueta
  lo detiene: carril único con el cruce en 1440 (ver la ficha).
- **Medidas en `vh`.** Los rellenos de sección en `vh` pasan a su valor a 900 de alto (9vh → 81&nbsp;px).
- **Tipografía.** Los botones del lienzo no heredan familia y se pintan en Arial; en la maqueta van en Jost.
  El H1 de la ficha tiene un `clamp(36px,4.max(…),64px)` roto (queda a 32&nbsp;px): pasa a
  `clamp(36px, 4.4vw, 63px)`, la escala de sus hermanos. Los `clamp()` que seguían creciendo pasado 1440
  (H2 `3.4vw` hasta 50, entradilla `1.15vw` hasta 18) se topan en su valor a 1440.
- **Color.** Los grises `rgba(242,236,228,α)` se resuelven a hex. `.45` y `.5` (4,05 y 4,48:1 sobre el marco
  de foto) suben a `.55` (`#8E8984`, 5,14:1). El borde de campo `rgba(242,236,228,.22)` (1,86:1) sube a
  `#6D6964` (3,27–3,45:1). El rótulo «Extrait 50 ml» sobre la foto del hero llevaba texto sin velo: peor píxel
  1,90:1; con un velo `.72` detrás, 9,24:1.
- **Enlaces.** Los `href="#"` del pie («Envíos y plazos», «Devoluciones», «Preguntas frecuentes», legales),
  «Eliminar» y «Siguiente» no llevaban a nada. Van a secciones reales de Condiciones de venta y a las páginas
  legales; «Eliminar» es un botón; la paginación de la tienda es texto (la segunda página no se dibuja).
  «Pedir el set de muestras» y «Set de muestras» iban a la tienda y van a la ficha del set, que es la
  conversión del Objetivo. «Confirmar pedido» lleva a Pedido recibido.
- **Formularios.** Cartas de la Maison, contacto y pago llevan casilla obligatoria de privacidad o de
  condiciones, y etiqueta accesible en cada campo. El motivo «sesión privada en la boutique» se añade al
  selector de contacto, porque «Reservar cita» aterriza ahí.
- **Páginas nuevas** (derivadas del sistema, sin lámina): set de descubrimiento, pedido recibido, mi cuenta,
  condiciones de venta y envíos, aviso legal, privacidad, cookies y 404.

## Reglas que ya se pagaron en este lienzo

- **Una lámina con estados es varias páginas.** La maqueta deriva siete páginas de un solo fichero; el alto
  de `canvas.json` es sólo el de la portada.
- **Un vínculo que la conversión necesita no puede ir a la tienda genérica.** El set de descubrimiento es
  el eje del Objetivo y tenía que tener página propia.
