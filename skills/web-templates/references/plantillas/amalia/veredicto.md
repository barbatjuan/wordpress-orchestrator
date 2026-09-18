---
hash: sha256:536ee679ad76bcb603e4a75a512c0d34d0e40f25befa562a29922c6e1df420e9
fecha: 2026-09-16
juez_b: profesional
autojuzgado: sí
vistas: 12
saltadas: 0
---

# Veredicto · amalia

El juez B vio únicamente la portada de esta plantilla —primera pantalla, una banda interior y el final— a 1280×860, sin ficha ni lienzo ni otra plantilla. El resto de páginas, los tres anchos y los hallazgos de la tabla siguiente son medición automática (`barrido.mjs`), no lectura visual del juez: el juez no vio ninguna celda salvo la portada a 1280.

Puntos débiles señalados por el juez B, como opinión del juez: «el bloque del mapa deja a la vista un texto de instrucciones» y «una fila del horario cortada, con texto superpuesto». Contrastado aparte por medida directa, no por este barrido: el primero era cierto y se corrigió antes de sellar —el bloque es ahora un mapa esquemático con retícula y chincheta, sin texto de instrucciones, y la chincheta no toca ningún texto a 430, 768 ni 1280—. El segundo no se reproduce: comparando las cajas de todo el texto pintado en las siete páginas de contenido a los tres anchos no hay ningún solape; lo que vio es una fila cortada por el borde superior de la captura, bajo la cabecera fija.

Revisión de anchura del 2026-09-16, la misma que corrigió `lumiere`, `marzo`, `delao`, `terrazza` y `aranda`: comparación sección por sección con el lienzo renderizado a 1440 y revisión a 1680 y 1920. Aquí el carril ya era el del lienzo —un solo raíl y contenido topado en 1280 a todos los anchos, 298px de margen dentro del visor con barra—, así que el defecto era otro: `line-height:1.7` en el `body` donde el lienzo deja `normal` inflaba cada rótulo, cada ítem de lista y cada fila de horario, y la foto del hero llevaba un tope en `vh` que el lienzo no dibuja. Corregido antes de este sello: interlínea normal con la del documento declarada donde hace falta, foto del hero en su caja de 513×702 y los rellenos del lienzo en las cabeceras de planes, diario y contacto. De veinte secciones fuera de umbral quedan tres, todas declaradas en la ficha. El documento tampoco llevaba `<!doctype html>`.

Un defecto que sólo vio el ojo: entre 768 y 1024 la columna del plan mide 190px y el texto de «Empezar con Completa» salía 23px fuera de su píldora sin desbordar la página, así que ni el barrido ni la medición de cajas lo señalaban.

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
