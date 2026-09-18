---
hash: sha256:a7f108aa008c4d29e5ba9713efde5b76e2bdd63968dd46b2139531431b557517
fecha: 2026-09-16
juez_b: profesional
autojuzgado: sí
vistas: 14
saltadas: 0
---

# Veredicto · barro

El juez B vio únicamente la portada de esta plantilla, a 1280×860, sin ficha ni lienzo ni otra plantilla. El resto de páginas, los tres anchos y los hallazgos de la tabla siguiente son medición automática (`barrido.mjs`), no lectura visual del juez: el juez no vio ninguna celda salvo la portada a 1280.

Punto débil señalado por el juez B, como opinión del juez: ««Añadir al carro» es un enlace crema subrayado sin peso de acción; cifras de «El taller» muy pegadas a la foto». Contrastado aparte por medida directa, no por este barrido: Solape: no se reproduce (0 cruces). Jerarquía del CTA: opinión de diseño, no medible. Sobre el CTA, el lienzo dibuja el mismo enlace subrayado en el estante (133×21) y un botón macizo de 400px en la ficha: la maqueta lo reproduce tal cual.

Revisión de anchura del 2026-09-16, la misma que corrigió las demás plantillas: comparación sección por sección con los lienzos renderizados a 1440 y revisión a 1680 y 1920. El margen en porcentaje se sumaba al centrado (contenido de 1224 a 1440, 1152 a 1920), el tope de 1440 no llegaba a la miga ni a las bandas a sangre (240px de desalineación a 1920) y `line-height:1.6` en el `body` inflaba 586 elementos. En portada faltaban el titular y el párrafo de «El taller», con la banda invertida, y cinco párrafos quedaban entre 160 y 280px fuera de su columna. Corregido antes de este sello: un raíl único `max(7,5 %, (100 % − 1224px)/2)` que cruza justo en 1440, interlínea normal, bandas y sangrados del lienzo, y a 430 un hueco de unos 2.170px en la categoría por una base de flex sin resetear. El documento tampoco llevaba `<!doctype html>`.

## Barrido

| Página | 430 | 768 | 1280 |
|---|---|---|---|
| portada | ✓ | ✓ | ✓ |
| categoria | ✓ | ✓ | ✓ |
| ficha | ✓ | ✓ | ✓ |
| carro | ✓ | ✓ | ✓ |
| pago | ✓ | ✓ | ✓ |
| pedido-recibido | ✓ | ✓ | ✓ |
| mi-cuenta | ✓ | ✓ | ✓ |
| la-marca | ✓ | ✓ | ✓ |
| contacto | ✓ | ✓ | ✓ |
| condiciones-venta-envios | ✓ | ✓ | ✓ |
| aviso-legal | ✓ | ✓ | ✓ |
| privacidad | ✓ | ✓ | ✓ |
| cookies | ✓ | ✓ | ✓ |
| 404 | ✓ | ✓ | ✓ |

## Hallazgos

ninguno
