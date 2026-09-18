---
hash: sha256:9c00414f141f91754b0fa357d6d24a595c2d7baa354457809421207758bf71ae
fecha: 2026-09-16
juez_b: profesional
autojuzgado: sí
vistas: 12
saltadas: 0
---

# Veredicto · amalia-salvia

El juez B vio únicamente la portada de esta plantilla —primera pantalla, una banda interior y el final— a 1280×860, sin ficha ni lienzo ni otra plantilla. El resto de páginas, los tres anchos y los hallazgos de la tabla siguiente son medición automática (`barrido.mjs`), no lectura visual del juez: el juez no vio ninguna celda salvo la portada a 1280.

Punto débil señalado por el juez B, como opinión del juez: «el panel del mapa es un rectángulo liso, sin chincheta ni calles, y parece sin terminar». Contrastado aparte por medida directa, no por este barrido: era cierto y se corrigió antes de sellar —el bloque es ahora un mapa esquemático con retícula y chincheta, y la chincheta no toca ningún texto a 430, 768 ni 1280—. En el sitio construido ese bloque es el widget nativo de Google Maps (ficha, mapeo nativo).

Revisión de anchura del 2026-09-16, la misma que corrigió `lumiere`, `marzo`, `delao`, `terrazza`, `aranda`, `amalia` y `tueste`. Aquí el defecto tenía una cara nueva: además de que el contenido no topaba en la composición de 1440 —se ensanchaba desde unos 1607px— **la escala tipográfica seguía creciendo con la pantalla aunque la columna ya estuviera fija**, así que a 1920 el H1 de una ficha de clase pasaba de 86 a 104px y partía en dos líneas lo que a 1440 entraba en una. Además, el ritmo vertical iba en `vw` donde el lienzo escribe `vh`, exactamente 1,2 veces más largo, y `line-height:1.7` en el `body` inflaba cada rótulo. Corregido antes de este sello: contenido topado en 1075, veinticinco `clamp()` topados en su valor a 1440, ritmo recalculado e interlínea normal. De 1440 a 1920 el documento de las siete páginas cambia tres píxeles como mucho y ninguna sección cambia de alto. El velo del pase mide 4,60–4,62:1 en el peor píxel en las dieciocho combinaciones de foto y ancho. El documento tampoco llevaba `<!doctype html>`.

## Barrido

| Página | 430 | 768 | 1280 |
|---|---|---|---|
| inicio | ✓ | ✓ | ✓ |
| filosofia | ✓ | ✓ | ✓ |
| planes | ✓ | ✓ | ✓ |
| clase | ✓ | ✓ | ✓ |
| diario | ✓ | ✓ | ✓ |
| entrada | ✓ | ✓ | ✓ |
| contacto | ✓ | ✓ | ✓ |
| gracias | ✓ | ✓ | ✓ |
| aviso-legal | ✓ | ✓ | ✓ |
| privacidad | ✓ | ✓ | ✓ |
| cookies | ✓ | ✓ | ✓ |
| 404 | ✓ | ✓ | ✓ |

## Hallazgos

ninguno
