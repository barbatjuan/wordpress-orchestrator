---
hash: sha256:64cf2297bfcb6b095e0832277e67c401e447aa0b67dcabdce8f389ee8ed7da29
fecha: 2026-09-16
juez_b: profesional
autojuzgado: sí
vistas: 14
saltadas: 0
---

# Veredicto · tueste

El juez B vio únicamente la portada de esta plantilla, a 1280×860, sin ficha ni lienzo ni otra plantilla. El resto de páginas, los tres anchos y los hallazgos de la tabla siguiente son medición automática (`barrido.mjs`), no lectura visual del juez: el juez no vio ninguna celda salvo la portada a 1280.

Punto débil señalado por el juez B, como opinión del juez: «la tarjeta de precio del hero se superpone a la foto sin sombra ni borde que justifique la capa». Contrastado aparte por medida directa, no por este barrido: el juez tenía razón, y el lienzo explica por qué. `Tueste.dc.html` dibuja esa tarjeta de 392px con `left:-128px` sobre una foto que empieza en x=632: queda 264px (67&nbsp;%) sobre la foto y 128 sobre el crema, y ese voladizo es lo que la convierte en una pieza que cose las dos mitades del hero. La maqueta la había encogido a 300px pegada al borde de la foto —100&nbsp;% encima, nada fuera—, así que el solape se leía como un parche. Restaurada a 392×156 con sus 128px fuera; por debajo de 1024 deja de ser capa y pasa a ser el bloque siguiente.

Revisión de anchura del 2026-09-16, la misma que corrigió `lumiere`, `marzo`, `delao`, `terrazza`, `aranda` y `amalia`: comparación sección por sección con los cinco lienzos renderizados a 1440 y revisión a 1680 y 1920. El tope vivía en la caja de la sección y el relleno en porcentaje se sumaba al centrado, así que la columna de las páginas legales salía **más estrecha cuanto más ancha la pantalla** (819px a 1024, 580 a 1440), y el carro y las legales tenían cada uno su raíl. El hero era la mitad de alto que en el lienzo (426 contra 616) y su foto se paraba 144px antes del borde. Corregido antes de este sello: un carril único a los siete anchos medidos, hero a sangre con su columna de texto de 488px, y geometría, tipos y medidas en los números del lienzo. Los cuatro `!important` que quedaban sobre anchos en línea pasan a clases. El documento tampoco llevaba `<!doctype html>`.

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
