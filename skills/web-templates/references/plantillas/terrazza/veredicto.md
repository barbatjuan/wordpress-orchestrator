---
hash: sha256:709ec986d1ee1dbad8c454b6df1f18deea0e5f8acbcff6ad087ddb7c3594168f
fecha: 2026-09-16
juez_b: profesional
autojuzgado: sí
vistas: 10
saltadas: 0
---

# Veredicto · terrazza

El juez B vio únicamente la portada de esta plantilla, a 1280×860, sin ficha ni lienzo ni otra plantilla. El resto de páginas, los tres anchos y los hallazgos de la tabla siguiente son medición automática (`barrido.mjs`), no lectura visual del juez: el juez no vio ninguna celda salvo la portada a 1280.

Punto débil señalado por el juez B, como opinión del juez: «fotografía de tono inconsistente (una foto de evento fría entre fotos cálidas); tratamiento plano». Contrastado aparte por medida directa, no por este barrido: el juez tenía razón y ahora hay número. No es el velo —no hay velo, y no hay un solo texto sobre imagen en las diez páginas a siete anchos— ni el encuadre, salvo 29,6px de la foto de sala, ya corregidos con su `object-position`. Es la fotografía: la luminancia media va de 35,3 (`terrazza-plato`) a 176,9 (`terrazza-chef`) sobre 255, cinco veces más clara una que otra. Y lo «plano» es ampliación: `terrazza-plato.webp` es un original de 720×540 dibujado a 1440×560, ×2,00 a 1440 y ×2,67 a 1920. Se arregla reponiendo originales de 2880px, no con CSS; queda declarado en la ficha.

Revisión de anchura del 2026-09-16, la misma que ya corrigió `lumiere`, `marzo` y `delao`: comparación sección por sección con los cinco lienzos renderizados a 1440 y revisión a 1680 y 1920. Nada topaba el contenido —1824px de ancho a 1920 y 3322 a 3418— y el margen se medía en `vw`, así que la barra del visor lo descentraba 7,5px. Además la maqueta declaraba `line-height:1.6` en `body` donde el lienzo deja `normal`, y eso inflaba cada titular y cada etiqueta; ocho tamaños de display no llegaban al valor del lienzo a 1440; la foto partida de portada era 620×480 en vez de 700×520; «La barra» se partía en dos filas ya a 1440 y el panel de reserva no tenía ancho. Corregido antes de este sello: un carril con tope de 1344, interlínea propia para display y etiquetas, y las cifras del lienzo en foto partida, barra y panel. Inicio queda a 23px de la altura de su lámina, y las páginas con más deriva (Plato +116, Contacto +74) sólo acumulan 2–4px por fila.

## Barrido

| Página | 430 | 768 | 1280 |
|---|---|---|---|
| inicio | ✓ | ✓ | ✓ |
| carta | ✓ | ✓ | ✓ |
| plato | ✓ | ✓ | ✓ |
| nosotros | ✓ | ✓ | ✓ |
| contacto | ✓ | ✓ | ✓ |
| gracias | ✓ | ✓ | ✓ |
| aviso-legal | ✓ | ✓ | ✓ |
| privacidad | ✓ | ✓ | ✓ |
| cookies | ✓ | ✓ | ✓ |
| 404 | ✓ | ✓ | ✓ |

## Hallazgos

ninguno
