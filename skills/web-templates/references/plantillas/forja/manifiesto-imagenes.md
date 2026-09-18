# Manifiesto de imágenes · forja

11 fotografías. **El slug ES el nombre del fichero Y el slug del adjunto en WordPress**: nada se renombra
después, porque el nombre viaja al sitio del cliente. El slug nombra lo que la foto es (`forja-racks`), no el
nombre del fichero de la sesión (`zona-de-racks.jpg`, `retrato-2.png`).

Las filas vienen del registro de la sesión de fotos de FORJA (Magnific: catálogo Freepik más generación, 15 de
septiembre de 2026): id de origen, autor, licencia y texto alternativo, copiado tal cual. Cada fichero se
recortó a la proporción de su hueco con un foco elegido mirando la foto y se guardó en webp con PHP GD; el
original a resolución completa no se copia aquí.

Dirección: blanco y negro o casi, luz dura de nave industrial, caucho y acero. Las tres fotos de catálogo en
blanco y negro (hero, banda y cardio) y las generadas comparten suelo oscuro y camiseta negra, así que el box
se lee como un solo sitio. Las generadas (`gen-seedream`) salen de un prompt de estilo común.

| Slug | Rol | Tamaño | Peso | Origen | Sesión | Licencia | `alt` |
|---|---|---|---|---|---|---|---|
| `forja-atleta-en-barra` | hero de Inicio 3/4, recorte desde arriba (conserva la ventana y la cabeza) | 900×1200 | 27 KB | 25743497 · Drazen Zigic | fp-25743 | Freepik free | Atleta a contraluz se pone magnesio junto a una barra cargada, en blanco y negro |
| `forja-clase-completa` | banda «Nadie entrena solo» a sangre, bajo velo `rgba(10,10,10,.72 → .86)`, texto centrado encima | 1440×960 | 124 KB | 2757062 · rawpixel.com | fp-27570 | Freepik free | Fila de atletas agarrando kettlebells sobre suelo de caucho en plena serie, blanco y negro |
| `forja-recuperacion` | nutrición y recuperación 1/1 (Inicio y El box) | 800×800 | 36 KB | creación dtHJg21XSL | gen-seedream | Magnific AI (Seedream 5 Pro) | Fisioterapeuta trata el tobillo de un atleta sentado en camilla dentro del box |
| `forja-racks` | instalaciones 4/5 · zona de fuerza | 720×900 | 66 KB | 63572061 · wirestock_creators | fp-63572 | Freepik premium | Racks de acero y discos en contrapicado sobre el suelo de una sala de fuerza |
| `forja-pista-rig` | instalaciones 4/5 · rig y pista (original apaisado, recorte central sobre el trineo) | 720×900 | 94 KB | 430989563 · offpage91 | fp-43098 | Freepik premium | Trineo de empuje cargado con discos sobre una pista oscura de entrenamiento |
| `forja-cardio` | instalaciones 4/5 · cardio (original apaisado, foco al 30 % desde la izquierda: el atleta delantero entero, el segundo fuera de cuadro) | 720×900 | 39 KB | 25743645 · Drazen Zigic | fp-25743 | Freepik free | Dos atletas pedalean con esfuerzo en bicicletas de aire en un box industrial |
| `forja-vestuarios` | instalaciones 4/5 · vestuarios | 720×900 | 43 KB | creación mEr6J5HhJQ | gen-seedream | Magnific AI (Seedream 5 Pro) | Vestuario vacío con taquillas metálicas negras, banco de madera y paredes de hormigón |
| `forja-coach-marta` | coach 3/4 · Marta Ferrer, head coach | 600×800 | 17 KB | creación cpvLQF30eP | gen-seedream | Magnific AI (Seedream 5 Pro) | Coach de unos cuarenta años con camiseta negra y brazos cruzados frente al rig |
| `forja-coach-ivan` | coach 3/4 · Iván Losada, fuerza | 600×800 | 21 KB | creación N2MuBuw6D9 | gen-seedream | Magnific AI (Seedream 5 Pro) | Coach de fuerza corpulento con barba, camiseta negra y brazos cruzados en el box |
| `forja-coach-nora` | coach 3/4 · Nora Blanco, gimnástico | 600×800 | 18 KB | creación 8aK4LZ3IrU | gen-seedream | Magnific AI (Seedream 5 Pro) | Coach joven con moño y magnesio en los brazos, de brazos cruzados frente al rig |
| `forja-coach-samuel` | coach 3/4 · Samuel Ruiz, endurance | 600×800 | 22 KB | creación 9ZVEXL2NYZ | gen-seedream | Magnific AI (Seedream 5 Pro) | Coach de pelo canoso y complexión fibrosa con camiseta negra y brazos cruzados |

## Lo que hay que saber antes de adaptarla

- **Las cuatro caras de los coaches son inventadas.** Son personas generadas, sin parecido buscado con nadie
  real, y existen sólo para que la Plantilla se pueda juzgar. En el sitio de un cliente se sustituyen siempre
  por fotos reales de su equipo, con la misma luz y el mismo encuadre de brazos cruzados frente al rig: nunca
  se publica un equipo que no existe.
- **Texto sobre foto medido.** `forja-clase-completa` lleva texto encima: `scrim.php --peor-pixel` sobre la
  región del texto (x 300–1140, y 100–860 del fichero, que cubre el recorte a 1280 y a 430) con el velo al
  `.72` en toda la región da **7,35:1** en papel `#F2F2F0` y **7,17:1** en ácido `#D9FF00`. El hero no lleva
  texto encima: la foto va en su columna.
- **`forja-recuperacion` se repite** en Inicio y en El box, como en el lienzo, donde Inicio resume las demás
  páginas. Lo mismo las cuatro de instalaciones y las cuatro de coaches. Si un juez lo marca, la salida es una
  segunda foto de recuperación para El box, no quitar la sección.
- **`forja-racks`** tiene una dominante violeta leve en las sombras; no se corrigió.
- **Licencia free.** La licencia gratuita de Freepik pide atribución salvo cuenta premium: en el sitio de un
  cliente estas tres fotos se sustituyen o se atribuyen.
