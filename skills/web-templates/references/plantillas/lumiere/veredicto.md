---
hash: sha256:e82d1d9e06329ac06128ca6125f1074025ed0bcffe8b663f94026346a48c5554
fecha: 2026-09-15
juez_b: profesional
autojuzgado: sí
vistas: 10
saltadas: 0
---

# Veredicto · lumiere

El juez B vio únicamente la portada de esta plantilla, a 1280×860, sin ficha ni lienzo ni otra plantilla. El resto de páginas, los tres anchos y los hallazgos de la tabla siguiente son medición automática (`barrido.mjs`), no lectura visual del juez: el juez no vio ninguna celda salvo la portada a 1280.

Punto débil señalado por el juez B, como opinión del juez: ««franja blanca muerta» bajo la franja rosa de la tarjeta de precios». Contrastado aparte por medida directa, no por este barrido: el primer sello lo descartó como «30px de relleno simétrico», y se equivocó —el lienzo lleva la banda hasta el borde de la tarjeta (`Lumiere.dc.html`, `margin-bottom:-30px`)—. Corregido: la banda es el último hijo de la tarjeta, sin relleno inferior.

Revisión visual completa del 2026-09-15, a petición del usuario («algo se rompió»): capturas de las diez páginas a 430, 768 y 1280, en la maqueta y dentro del visor de la Mesa. Encontró lo que el barrido no mide y está corregido antes de este sello: bases de flex que se volvían alto al apilarse (195px entre un ritual y su precio a 430), la hamburguesa lejos del borde, etiquetas del formulario desalineadas, doble numeración del índice legal, interlineado 1,6 en los titulares, el equipo con un retrato solo a 768, una franja clara entre dos rosas, 238px sobre la foto de la ficha, el hero y la carta apartados del lienzo, y el documento sin `<!doctype html>` (se pintaba en modo de compatibilidad).

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
