# Barrido base del catálogo generado — 2026-09-13

Testigo del ANTES, tomado sobre `main` = `123a736` antes de tocar nada. Método: servidor
local (`php -S 127.0.0.1:8765 -t skills/html-mockup/assets`) y panel del navegador dentro
de un subagente; capturas a 1280 y a 430. Ocho páginas vistas, dieciocho tiras no vistas
(no pedidas). Cero errores de consola, cero imágenes rotas.

## Qué se miró

Índice de la galería · cinco tiras (Lumière TPL-C-14 · Motor Aranda TPL-C-07 · Bajura
TPL-E-07 · Casa Terrazza TPL-C-06 · Corte Nueve TPL-E-06) · los dos chasis generados.
Pase móvil a 430 en Lumière y en ambos chasis.

## Lo idéntico entre las cinco tiras

La **gramática de sección**: antetítulo en versalitas espaciadas + H2 + lede opcional + un
bloque de contenido, repetida de cuatro a seis veces. Un solo `.canvas` al que todas las
secciones se pegan. La misma cabecera oscura (logo izquierda, menú centro, CTA derecha).
Una rejilla de tarjetas como sección 2 en cuatro de las cinco. Alturas de sección de 500 a
900px, así que el ritmo de scroll es metronómico.

## Lo que sí cambia

Paleta y fondo, tipografía de display (serif o sans), tipo de hero (foto a sangre, foto
contenida con tarjeta flotante, hero de formulario oscuro, sin imagen), tono de acento y
los sustantivos del contenido.

**A simple vista se distinguen dos de cinco.** Bajura se separa al instante. Terrazza y
Lumière son casi gemelas (foto a sangre, H1 serif a la izquierda, par de CTA relleno +
contorno) y sólo las separa la paleta. Aranda y Corte leen las dos como «página blanca,
antetítulo centrado, rejilla de tarjetas».

## Defectos medidos

1. **`.items.cols-4` colapsa a una sola columna.** En Categorías del chasis de ecommerce
   la `grid-template-columns` computada es `173.078px`: una única pista de 173px sobre un
   canvas de 1076px, así que las cuatro tarjetas se apilan en una tira estrecha centrada a
   CUALQUIER ancho. En el mismo fichero, `.items.grid-prod` sí resuelve cuatro pistas de
   251px, o sea que el defecto es de esa clase y no del chasis entero.
2. **Pistas fantasma y tarjetas huérfanas.** `.items.bens` declara 4 pistas para 3 hijos
   (un cuarto de fila muerto); Servicios y Casos del chasis corporate son de 2 columnas con
   3 elementos; la rejilla de producto de Corte termina 2 de 4. Filas irregulares en tres
   de las páginas vistas.
3. **El móvil no está resuelto.** A 430 el hero de Lumière son ~2.800px de un recorte
   borroso con el H1 abajo del todo; **ninguna página tiene menú de hamburguesa**, el nav
   sigue en línea y el CTA salta a una segunda fila tanto en el chasis como en Lumière; la
   barra de anclas de la galería se sale de pantalla.
4. **Los dos chasis tienen el `<title>` vacío** y cero elementos `<img>` (27 y 30
   marcadores `.ph`).
5. **Los precios se escriben `€68,00`**, símbolo delante, incorrecto para es-ES.
6. **El copy de los chasis está en español rioplatense** («Contanos qué necesitás»,
   «Sumate y recibí», «Remeras», «sin costo») dentro de un catálogo por lo demás peninsular
   (Bilbao, prefijos 944/948, «coste»).

## Los chasis no son un punto de partida

Son maquetas rellenas: cajas grises con copy de aspecto terminado («Titular principal: el
problema que resolvemos») y ninguna imagen real. Quien diseña no recibe ni escala
tipográfica a la que reaccionar ni tratamiento de imagen, pero sí un orden de secciones ya
decidido y bastante acabado como para que cambiarlo se sienta editar en vez de diseñar.

## Lo que sí vale y se conserva

- **La tarjeta flotante del menú de precios de Lumière sobre foto a sangre**: la única
  composición con profundidad real y una razón para existir.
- **El fondo oscuro con acento naranja y el código postal en el hero de Bajura**: la única
  tira que parece una marca y no una plantilla.
- **El menú de dos columnas con precios sobre oscuro de Terrazza**: denso, tipográficamente
  seguro, sin necesidad de tarjetas.
- **La cromática de la propia ficha** (ADN, ESCALA/FONDO/DENSIDAD/COMPOSICIÓN/ELEVACIÓN por
  tira) es buena herramienta interna; se conserva, sólo deja de dominar la vista móvil.

## Consecuencias para el contrato nuevo

- Cada Canvas lleva **artboard móvil obligatorio**, y el menú de hamburguesa es parte del
  Mapeo nativo, no un detalle: hoy no existe en ninguna página del catálogo.
- El Veredicto revisa **la última fila de cada rejilla**: las pistas fantasma son el defecto
  más repetido y ninguna regla las ve.
- Locale: precios en formato es-ES (`68,00 €`) y copy peninsular; entra en el barrido.
- El `<title>` de cada página de una Maqueta es contenido, no adorno.

## Nota de método

Las capturas con `scrollY > 0` en el panel del navegador devuelven fotogramas en blanco o
recortes 1:1 de la esquina; lo que funciona es dejar `scrollY = 0` y subir la altura del
viewport emulado. Y `capture.mjs` resuelve un objetivo sin `http` con `pathToFileURL`, que
se traga el `#ancla`: para capturar una tira concreta hay que servir por http.
