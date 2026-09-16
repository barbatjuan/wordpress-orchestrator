---
hash: sha256:ab680a78d283e55b78627e6121b0eb772c96f7f030506b9df2f94be4b7b60957
fecha: 2026-09-16
juez_b: profesional
autojuzgado: sí
vistas: 10
saltadas: 0
---

# Veredicto · lumiere

El juez B vio únicamente la portada de esta plantilla, a 1280×860, sin ficha ni lienzo ni otra plantilla. El resto de páginas, los tres anchos y los hallazgos de la tabla siguiente son medición automática (`barrido.mjs`), no lectura visual del juez: el juez no vio ninguna celda salvo la portada a 1280.

Punto débil señalado por el juez B, como opinión del juez: ««franja blanca muerta» bajo la franja rosa de la tarjeta de precios». Contrastado aparte por medida directa, no por este barrido: el primer sello lo descartó como «30px de relleno simétrico», y se equivocó —el lienzo lleva la banda hasta el borde de la tarjeta (`Lumiere.dc.html`, `margin-bottom:-30px`)—. Corregido: la banda es el último hijo de la tarjeta, sin relleno inferior.

Revisión visual completa del 2026-09-15, a petición del usuario («algo se rompió»): capturas de las diez páginas a 430, 768 y 1280, en la maqueta y dentro del visor de la Mesa. Encontró lo que el barrido no mide y está corregido antes de este sello: bases de flex que se volvían alto al apilarse (195px entre un ritual y su precio a 430), la hamburguesa lejos del borde, etiquetas del formulario desalineadas, doble numeración del índice legal, interlineado 1,6 en los titulares, el equipo con un retrato solo a 768, una franja clara entre dos rosas, 238px sobre la foto de la ficha, el hero y la carta apartados del lienzo, y el documento sin `<!doctype html>` (se pintaba en modo de compatibilidad).

Segunda revisión del mismo día, porque el usuario la seguía viendo «muy mal»: la maqueta se comparó sección por sección con los cinco lienzos renderizados a 1440, y además a 1680 y 1920, los anchos del visor a pantalla completa. No tenía tope de ancho —a 1920 el contenido medía 1728px, el precio de una fila de la carta quedaba a unos 1000px de su descripción y la foto de la ficha salía ampliada 2,4 veces con la cara cortada— y el primer arreglo se había apartado del lienzo: velo crema lavando la foto del hero, texto en x=96 en vez de x=288, 208px entre secciones donde el lienzo pone 116 o 152, el producto enmarcado en vez de a sangre, el panel del bono sin cabalgar la banda, y cabecera y pie en otro carril. Corregido antes de este sello: carril único topado en 1248 (96px a 1440, 336 a 1920, simétrico dentro de un iframe con barra), hero sin velo con el peor píxel a 4,79:1 entre 1025 y 2560, ritmo vertical del lienzo, y las alturas de página a menos de 70px de las del lienzo a 1440.

## Barrido

| Página | 430 | 768 | 1280 |
|---|---|---|---|
| inicio | ✓ | ✓ | ✓ |
| rituales | ✓ | ✓ | ✓ |
| ritual | ✓ | ✓ | ✓ |
| nosotros | ✓ | ✓ | ✓ |
| contacto | ✓ | ✓ | ✓ |
| gracias | ✓ | ✓ | ✓ |
| aviso-legal | ✓ | ✓ | ✓ |
| privacidad | ✓ | ✓ | ✓ |
| cookies | ✓ | ✓ | ✓ |
| 404 | ✓ | ✓ | ✓ |

## Hallazgos

ninguno
