# Cosecha del canvas delao (Inmobiliaria de la O)

Extraído del artifact publicado «Sitio web inmobiliario premium»
(https://claude.ai/code/artifact/eab59854-20dc-4f8e-831e-ef51375aefbe) el 2026-09-13,
descomprimiendo su manifiesto gzip+base64. Es el diseño que el usuario aceptó; es la
autoridad de diseño del piloto de la Fase 1 (plantilla `delao`).

| Fichero | Artboard | Bytes | Nota |
|---|---|---|---|
| `Inicio.dc.html` | Inicio | 13.662 | H1 «Casas que no se anuncian solas», velo horizontal claro, banda de búsqueda inversa |
| `Propiedades.dc.html` | Propiedades | 9.686 | listado con barra de filtros y orden |
| `Ficha.dc.html` | Ficha Propiedad | 16.251 | Villa Alameda: mosaico, tabla de 10 filas, panel de visita |
| `Nosotros.dc.html` | Nosotros | 8.343 | «Pocas casas, mucho tiempo en cada una» |
| `Contacto.dc.html` | Contacto | 6.817 | «Hablemos de su casa» |
| `Nav.dc.html` | Nav | 2.564 | cabecera compartida |
| `Pie.dc.html` | Pie | 3.858 | pie con aviso legal y privacidad |
| `canvas.css` | — | 381.452 | CSS del canvas completo |
| `_template.html` | — | 5.822 | el documento que monta los 5 artboards con `<dc-import>` |
| `support-runtime.js`, `react.min.js`, `react-dom.min.js` | — | — | runtime de Claude Design; NO forma parte del diseño |

Tipografías del canvas: **Libre Caslon Display** + **Archivo**, cargadas de Google Fonts.
Libre Caslon Display NO está en `skills/html-mockup/assets/fonts/`. Decisión previa del
piloto (que se revisa al derivar la maqueta): sustituir por **Instrument Serif**, ya
embebida en la casa.

Acento del diseño: `#8A7B5C`. Medido, NO pasa 4,5:1 en ninguno de los dos fondos
(3,77 sobre `#F6F4F0`, 3,49 sobre el alterno). El piloto lo resolvió con `#8A5A2A`
(terracota), 5,35 y 4,94, que además supera la separación de canal ≥20 de la tinta.

## Páginas de sistema: derivadas, no dibujadas

El canvas trae cinco páginas. La plantilla lleva diez. Las cinco que faltan —**gracias**,
**aviso legal**, **privacidad**, **cookies** y **404**— NO tienen artboard aquí y no se
dibujaron: se derivaron directamente en `maqueta/index.html` (2026-09-14) a partir del sistema
que fijan las cinco láminas. Misma cabecera, mismo pie, mismos tokens, misma escala de tipo,
mismos márgenes y los mismos dos puntos de corte.

| Página | De dónde sale cada pieza |
|---|---|
| gracias | cabecera partida con filete de Nosotros/Contacto · pasos de «Cómo trabajamos» · cierre de Nosotros |
| aviso legal, privacidad, cookies | una sola maqueta legal: índice pegajoso con la proporción 1fr/1.5fr de «Cómo trabajamos», columna de lectura de 31em, fichas de datos con el filete de 1px de la tabla de características de Ficha |
| 404 | cabecera partida · banda de búsqueda y tarjetas de Inicio |

Por eso no hay nada que cosechar para ellas, y un cambio de diseño en estas cinco se hace en
la maqueta, no en el canvas. Si algún día se dibujan en Claude Design, la lámina pasa a ser la
autoridad y esta nota se retira.
