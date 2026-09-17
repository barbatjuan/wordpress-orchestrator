---
hash: sha256:02760c653241dbe02b283e255efe34c20b042b9c0c162820867a77f196cf84fe
fecha: 2026-09-17
juez_b: profesional
autojuzgado: sí
vistas: 15
saltadas: 0
---

# Veredicto · noir

El juez B vio únicamente la portada de esta plantilla —primera pantalla, una banda interior y el final— a 1280×860, sin ficha ni lienzo ni otra plantilla. El resto de páginas, los tres anchos y los hallazgos de la tabla siguiente son medición automática (`barrido.mjs`), no lectura visual del juez: el juez no vio ninguna celda salvo la portada a 1280.

Punto débil señalado por el juez B, como opinión del juez: «la franja "Compuesto y envasado…" corta la imagen del artesano justo a la mitad del encuadre». Contrastado aparte por medida directa, no por este barrido: no se reproduce. La foto `noir-taller.webp` mide 1200×900 y su caja lleva `aspect-ratio:4/3` con `object-fit:cover`, así que se dibuja entera, sin recorte; lo que vio el juez es el borde inferior de su propia captura de banda, que termina a mitad de la sección.

Hecha ya con las reglas de anchura aprendidas en la revisión del 2026-09-16: `<!doctype html>` en la primera línea, un raíl único que a 1920 cae en 456px con el contenido topado en 1008, sin interlínea en el `body` y sin `clamp()` que siga creciendo pasado 1440. Diferencias declaradas con el lienzo: el margen se detiene en 1440 donde el lienzo seguía creciendo, las rejillas `auto-fit` pasan a columnas fijas, los botones van en Jost donde el lienzo caía a Arial, el H1 de la ficha tenía un `clamp` roto, suben los grises y el borde de los campos por contraste, los enlaces `#` llevan a páginas reales y los formularios llevan su casilla de privacidad o condiciones. El único texto sobre foto, «Extrait 50 ml», mide 9,24:1 en el peor píxel con su velo.

## Barrido

| Página | 430 | 768 | 1280 |
|---|---|---|---|
| portada | ✓ | ✓ | ✓ |
| tienda | ✓ | ✓ | ✓ |
| ficha | ✓ | ✓ | ✓ |
| set-descubrimiento | ✓ | ✓ | ✓ |
| cesta | ✓ | ✓ | ✓ |
| pago | ✓ | ✓ | ✓ |
| pedido-recibido | ✓ | ✓ | ✓ |
| mi-cuenta | ✓ | ✓ | ✓ |
| la-maison | ✓ | ✓ | ✓ |
| contacto | ✓ | ✓ | ✓ |
| condiciones-venta-envios | ✓ | ✓ | ✓ |
| aviso-legal | ✓ | ✓ | ✓ |
| privacidad | ✓ | ✓ | ✓ |
| cookies | ✓ | ✓ | ✓ |
| 404 | ✓ | ✓ | ✓ |

## Hallazgos

ninguno
