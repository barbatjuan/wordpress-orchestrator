# Enfoques

Un **Enfoque** es el planteamiento visual de un sitio, dicho con palabras: ocho ejes, un par
tipográfico y una dirección de imagen. Sirve para tres cosas: comprobar que las referencias de un
cliente encajan con la plantilla elegida, hablar con los jueces y anotar cada entrega en el registro.

**No es un motor.** Este documento no contiene colores, tokens ni valores de CSS, y nada lo lee para
pintar una página. El catálogo anterior tenía ejes validados que cambiaban cuatro de ocho cosas en
pantalla y emitían las otras cuatro como comentarios; lo que corrige eso es que cada Enfoque lo
encarne un sitio real, no que sus ejes se validen mejor. Una ficha nombra su enfoque sólo por id; los
ejes viven aquí y en ningún otro sitio.

Los ocho ids: `editorial`, `directo`, `materia`, `vitrina`, `institucional`, `tecnologico`,
`lujo-oscuro`, `brutalista`.

## Resumen

| Enfoque | En una frase | Lo encarna | Tipo · Objetivo |
|---|---|---|---|
| `editorial` | Una historia que merece leerse despacio | `delao` | corporate · `cartera-curada` |
| `directo` | La cosa y su cifra, sin frase de marca delante | `escuadra` | ecommerce · `catalogo-amplio` |
| `materia` | La página se siente como la sustancia que se vende | `marzo` · `lumiere` (en preparación) | ecommerce · `tienda-talla`; corporate · `ritual-bono` |
| `vitrina` | La sala a oscuras y el objeto iluminado | `terrazza` (en preparación) | corporate · `reservar-mesa` |
| `institucional` | Credibilidad antes que emoción | `albaran` (en preparación) | corporate · `servicios-captacion` |
| `tecnologico` | Se vende por capacidad y por dato medido | `cadencia` · `aranda` (en preparación) | ecommerce · `equipo-por-uso`; corporate · `stock-ocasion` |
| `lujo-oscuro` | Una colección que cuesta lo que parece costar | `barro` | ecommerce · `tienda-lote` |
| `brutalista` | Se niega a ser de buen gusto, a propósito | `tueste` (en preparación) | ecommerce · `suscripcion` |

Los ocho enfoques quedan encarnados por al menos una plantilla y ningún par de plantillas comparte
tipo + objetivo + enfoque. El estado de cada plantilla (maqueta, veredicto) no se repite aquí: lo
lleva `plantillas/_indice.md`.

## Cómo leer los ejes

Las posiciones salen del catálogo de estilos anterior. Cada tabla de enfoque tiene la **posición**
del enfoque y una columna por plantilla que lo encarna, con **cómo la resuelve**, sacado de su ficha.
Cuando las dos no coinciden, **manda la plantilla**: es un sitio real diseñado y aprobado, y la
posición del catálogo anterior queda como el extremo del enfoque, no como una obligación. Donde la
ficha no fija un eje, la celda lo dice en lugar de inventarlo.

| Eje | Qué decide | Posiciones |
|---|---|---|
| Escala | Cuánto crece el titular respecto al texto | contenida · clásica · editorial · monumental |
| Densidad | Cuánto aire hay entre bloques y dentro de ellos | compacta · estándar · generosa · monumental |
| Fondo | La familia del suelo de la página | papel blanco · cálido claro · frío claro · crema · tierra · saturado · tinta neutra · tinta cálida · tinta fría |
| Elevación | Cómo se separa un bloque del suelo | ninguna (sólo aire) · filete · sombra suave · halo del acento |
| Composición | Cómo se reparte la página en la rejilla | centrada · asimétrica (texto en siete de doce columnas, una imagen sangra) · rejilla estricta · rejilla rota (un elemento por sección cruza la rejilla) |
| Acento | Cuánto y cómo se gasta el único color de acento | ninguno (sólo sus funciones) · reservado · campo teñido · duotono en la fotografía · degradado · metálico · policromo acotado |
| Chasis | Cómo se delimita cada bloque dentro de la rejilla | desnudo · tarjeta con relleno · tarjeta con sombra al levantarse · enmarcado por filete · dividido por filetes · sombra dura · rejilla estricta · capas superpuestas |
| Ornamento | La marca que no es texto, foto ni chasis | ninguno · filete · textura · patrón · ilustración de línea |

**Riesgo nativo.** Degradado, metálico, textura, patrón y capas superpuestas pueden no tener expresión
nativa en Elementor. Antes de dibujar una de esas posiciones en un lienzo se comprueba contra
`vocabulario-nativo.md`, que se generará por introspección de una instalación real y todavía no
existe. Lo que no tenga expresión nativa se rediseña; no abre CSS a medida.

**Cómo se describe una referencia.** Por cada referencia del cliente se anota una posición por eje con
estas mismas palabras, y se compara con la columna de la plantilla candidata. El procedimiento
completo, y qué hacer cuando no encaja, está en `recomendador.md`.

---

## `editorial`

Herencia, prestigio, algo que se vende despacio: galerías, editoriales, servicios de alto valor,
inmobiliaria de pocas piezas caras. Las referencias del cliente apuntan aquí cuando enseñan titulares
con serifa grande sobre fondo claro, mucho aire, fotografía encuadrada como en una revista, filetes
finos en lugar de tarjetas y casi ningún color fuera del texto. Si las referencias piden tarjetas con
sombra, un buscador como portada o botones de color vivo, no es este enfoque.

| Eje | Posición | En `delao` |
|---|---|---|
| Escala | editorial | Display de alto contraste; titular de dos líneas, no de cuatro |
| Densidad | generosa | La ficha no la fija |
| Fondo | papel blanco | Claro y cálido, con un fondo alterno |
| Elevación | ninguna | Sin sombras: pasar a tarjetas con sombra rompe el carácter |
| Composición | asimétrica | La portada es una casa: velo claro que sube desde el lado del texto |
| Acento | ninguno | Reservado a las cuatro funciones del sistema; la zona nunca va en acento |
| Chasis | dividido por filetes | La rejilla de propiedades dibuja la línea con el hueco sobre fondo entintado, no con un borde por tarjeta |
| Ornamento | filete | Panel de cifras con filete y una rejilla de reglas decorativa en portada |

**Par tipográfico:** display con serifa de alto contraste + palo seco de texto. En `delao`, Instrument
Serif + Archivo.

**Dirección de imagen:** fotografía protagonista con encuadre editorial; portada apaisada, mosaico en
la ficha, retratos de equipo. Velos con moderación y siempre claros.

**Lo encarna: `delao`**.

---

## `directo`

Marcas que ganan por ser inconfundibles y por enseñar la cosa antes que la frase: lanzamientos,
estudios, tiendas donde la medida y el precio deciden. Las referencias del cliente apuntan aquí cuando
abren con el producto y su cifra, compactan mucha información sin adorno, usan contraste alto, recortes
de foto apretados y una llamada a la acción fuerte. Si las referencias piden aire, serifa y una historia
antes que un precio, no es este enfoque.

| Eje | Posición | En `escuadra` |
|---|---|---|
| Escala | monumental | La ficha no la fija |
| Densidad | compacta | Todo lleva su medida: ancho por fondo por alto, bultos y minutos de montaje |
| Fondo | tinta neutra | Claro y neutro, no oscuro |
| Elevación | halo del acento | La ficha no la fija |
| Composición | rejilla rota | Dos rejillas estrictas y ninguna más: seis columnas en la cabecera, tres en el cuerpo |
| Acento | degradado | Un solo color, el de cota, sólo en interfaz y nunca en letra |
| Chasis | desnudo | La ficha no lo fija |
| Ornamento | ninguno | Ninguno fuera de la cota sobre las cifras |

`escuadra` es la plantilla que más se aparta de la posición anterior: conserva lo que hace directo al
enfoque (la cosa y su cifra por delante, densidad compacta, ningún adorno) y lo resuelve en claro y en
rejilla estricta.

**Par tipográfico:** grotesca neutra + monoespaciada para cifras. En `escuadra`, Instrument Sans +
Martian Mono.

**Dirección de imagen:** la habitación entera con su cuenta; producto con material y paleta coherentes
en todo el surtido; recortes apretados para las miniaturas de departamento.

**Lo encarna: `escuadra`**.

---

## `materia`

Negocios que venden un material o una cosa hecha, o un tratamiento que se nota en la piel: tejido,
madera, piedra, comida, cosmética. La página tiene que sentirse como la sustancia, no como software.
Las referencias del cliente apuntan aquí cuando enseñan fondos claros y cálidos, producto fotografiado
de frente con luz cálida, fichas técnicas y composición, filetes que enmarcan y ninguna prisa. Si las
referencias piden fondo oscuro o una interfaz de datos, no es este enfoque.

| Eje | Posición | En `marzo` | En `lumiere` |
|---|---|---|---|
| Escala | clásica | Display fino en el titular y robusto en la cifra | En preparación |
| Densidad | estándar | La ficha no la fija | En preparación |
| Fondo | cálido claro | Blanco roto cálido con un alterno | Claro, cálido y rosado en el material cosechado de la marca |
| Elevación | filete | La ficha no la fija | En preparación |
| Composición | rejilla estricta | La colección es una tabla de seis columnas; una sola fotografía por página rompe el margen | En preparación |
| Acento | campo teñido | No hay acento: el único color marca existencias | En preparación |
| Chasis | enmarcado por filete | Registro de fichas técnicas, sin miniaturas | En preparación |
| Ornamento | textura | La ficha no lo fija | En preparación |

**Por qué `lumiere` toma este enfoque:** una carta de rituales se elige por lo que se nota en la piel y
se vuelve cinco veces, y un fondo cálido, un campo teñido sobre la carta y la textura de superficie
cuentan esa sustancia mejor que una interfaz.

**Par tipográfico:** display de alto contraste + palo seco geométrico. En `marzo`, Bodoni Moda + Jost.

**Dirección de imagen:** el producto de frente, luz cálida, de borde a borde dentro de la rejilla.
Nunca la sonrisa de banco de imágenes.

**Lo encarna: `marzo`** · `lumiere` (en preparación).

---

## `vitrina`

Lo que se compra mirándolo de cerca y en orden: joyería, galerías, producto caro, obra fotografiada, un
plato servido. La sala a oscuras y el objeto iluminado. Las referencias del cliente apuntan aquí cuando
enseñan una pieza sola sobre fondo oscuro con aire alrededor, una rejilla ordenada, poco texto y un
brillo metálico reservado al precio o al botón. Si las referencias llevan la foto a sangre de borde a
borde, no es este enfoque: lo que lo define es el margen oscuro que rodea la pieza.

| Eje | Posición | En `terrazza` |
|---|---|---|
| Escala | editorial | En preparación |
| Densidad | monumental | En preparación |
| Fondo | tinta neutra | Oscuro y cálido en el material cosechado de la marca |
| Elevación | sombra suave | En preparación. Sobre tinta la sombra casi no se ve en reposo: separa el escalón de superficie |
| Composición | rejilla estricta | En preparación |
| Acento | metálico | Latón en el material cosechado: el metal de lámparas y cubiertos |
| Chasis | tarjeta con sombra al levantarse | En preparación |
| Ornamento | ninguno | En preparación |

**Por qué `terrazza` toma este enfoque:** un restaurante se reserva por el plato y por la sala de noche,
y el plato iluminado sobre fondo oscuro, la carta ordenada y el latón como único metal son exactamente
la vitrina trasladada a una mesa.

**Par tipográfico:** palo seco de peso alto para titulares + palo seco de texto; ninguna de las dos es
la display de otro enfoque.

**Dirección de imagen:** el objeto aislado e iluminado contra el fondo oscuro, con aire alrededor.
Nunca a sangre.

**Lo encarna: `terrazza`** (en preparación).

---

## `institucional`

B2B, servicios profesionales y todo lo que vende credibilidad antes que emoción: despachos, consultoras,
asesorías, ingenierías. Las referencias del cliente apuntan aquí cuando enseñan fondos claros y fríos,
tarjetas sobrias por área de servicio, iconos de línea, credenciales, cifras de trayectoria y
testimonios, con titulares contenidos. Si las referencias piden un titular enorme o una fotografía que
lo diga todo, no es este enfoque.

| Eje | Posición | En `albaran` |
|---|---|---|
| Escala | contenida | En preparación |
| Densidad | estándar | En preparación |
| Fondo | frío claro | En preparación |
| Elevación | sombra suave | En preparación |
| Composición | centrada | En preparación |
| Acento | reservado | En preparación |
| Chasis | tarjeta con relleno | En preparación |
| Ornamento | ilustración de línea | En preparación |

**Por qué `albaran` toma este enfoque:** un despacho de abogados vende credibilidad antes que emoción
—áreas de práctica sobrias, quién firma y su trayectoria, un acento reservado a pedir consulta—, que es
el territorio exacto de este enfoque.

**Par tipográfico:** una sola familia, con disciplina de pesos en lugar de contraste.

**Dirección de imagen:** fotografía sobria de contextos reales de trabajo; los procesos, guiados por
icono. La prueba social es parte del contenido, no decoración.

**Lo encarna: `albaran`** (en preparación).

---

## `tecnologico`

Lo que se vende por capacidad y por dato medido: software, equipo técnico, material que se elige por
uso, unidades que se comparan por ficha. Las referencias del cliente apuntan aquí cuando enseñan cifras
en monoespaciada, tablas alineadas, bloques sin tarjeta que caen en línea fija, luz fría y fotografía
real graduada. Si las referencias abren con una frase de marca y una foto de estilo de vida, no es este
enfoque.

| Eje | Posición | En `cadencia` | En `aranda` |
|---|---|---|---|
| Escala | contenida | La ficha no la fija | En preparación |
| Densidad | generosa | La ficha no la fija | En preparación |
| Fondo | tinta fría | Oscuro y frío | Claro y frío en el material cosechado de la marca, lo que la separa de `cadencia` |
| Elevación | halo del acento | La ficha no la fija | En preparación |
| Composición | rejilla estricta | Lista de sesiones como tabla con barras, en columnas | En preparación |
| Acento | duotono | El naranja sólo mide: barras de intensidad y subrayados, nunca letra | En preparación |
| Chasis | rejilla estricta | La ficha no lo fija | En preparación |
| Ornamento | ninguno | La ficha no lo fija | En preparación |

**Por qué `aranda` toma este enfoque:** un patio de coches de ocasión se decide comparando cifras
—año, kilómetros, potencia, precio— entre unidades que entran y salen cada semana, y ese dato alineado
en rejilla fija es la lectura que define el enfoque.

**Par tipográfico:** grotesca de trazo firme + monoespaciada para todo dato. En `cadencia`, Archivo +
IBM Plex Mono.

**Dirección de imagen:** fotografía real, luz fría, sin neón y sin gimnasio o exposición de catálogo.
Nunca una captura de interfaz en lugar de una foto.

**Lo encarna: `cadencia`** · `aranda` (en preparación).

---

## `lujo-oscuro`

Objetos que cuestan lo que parecen costar y se enseñan en colección: joyería, destilados, piezas de
autor, alta costura. Donde `vitrina` aísla una pieza, este enfoque pone varias juntas. Las referencias
del cliente apuntan aquí cuando enseñan fondo casi negro y cálido, texto crema, titulares grandes,
varias piezas en la misma imagen o en banda continua y ningún grito comercial. Si las referencias piden
fondo claro o descuentos visibles, no es este enfoque.

| Eje | Posición | En `barro` |
|---|---|---|
| Escala | monumental | La ficha no la fija |
| Densidad | generosa | La ficha no la fija |
| Fondo | tinta cálida | Casi negro cálido, texto crema |
| Elevación | halo del acento | La ficha no la fija |
| Composición | centrada | Bandas de producto a sangre, borde con borde; pies de foto en su propia rejilla |
| Acento | metálico | Verde esmalte sólo en interfaz, como subrayado de compra; nunca texto |
| Chasis | capas superpuestas | Rejilla de especificaciones con bordes en la ficha |
| Ornamento | ilustración de línea | La ficha no lo fija |

**Par tipográfico:** serifa de texto con eje óptico + grotesca sobria. En `barro`, Newsreader +
Schibsted Grotesk.

**Dirección de imagen:** la colección iluminada. En `barro`, producto sobre blanco tiza en banda
continua, que sobre el fondo oscuro se lee como un estante iluminado, y ambiente a sangre.

**Lo encarna: `barro`**.

---

## `brutalista`

Marcas que se niegan a ser de buen gusto: tiradas y lanzamientos, cultura joven, todo lo que se lee
como un reto. El ruido es la propuesta, no un riesgo que se gestiona. Las referencias del cliente
apuntan aquí cuando enseñan fondos saturados, bloques estampados con sombra dura en reposo, titulares
enormes sin ajustar, etiquetas de colores distintos y fotografía cruda con flash. Si las referencias
piden contención, serifa fina o sombras suaves, no es este enfoque.

| Eje | Posición | En `tueste` |
|---|---|---|
| Escala | monumental | En preparación |
| Densidad | compacta | En preparación |
| Fondo | saturado | En preparación; el material cosechado trae el papel kraft de la caja, más cerca de tierra |
| Elevación | ninguna | En preparación |
| Composición | asimétrica | En preparación |
| Acento | policromo acotado: la norma de un solo color se suspende sólo para un conjunto nombrado, como la fila de etiquetas | En preparación |
| Chasis | sombra dura, presente en reposo | En preparación |
| Ornamento | patrón, en una sola superficie nombrada y nunca detrás del texto | En preparación |

**Por qué `tueste` toma este enfoque:** el café de especialidad por suscripción vive de la tirada del
mes, y cada origen con su color de etiqueta es el conjunto acotado donde el policromo tiene sentido,
mientras el sello y la caja de envío piden el bloque estampado antes que la cafetería de lujo.

**Par tipográfico:** grotesca en peso alto a su ancho natural, sin apretar el espaciado + palo seco de
texto.

**Dirección de imagen:** cruda, sin retocar, flash duro: la foto que una marca prudente recortaría.

**Lo encarna: `tueste`** (en preparación).
