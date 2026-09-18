# Manifiesto de imágenes · forja-fucsia

11 fotografías. **El slug ES el nombre del fichero Y el slug del adjunto en WordPress**: nada se
renombra después, porque el nombre viaja al sitio del cliente.

Las filas vienen del registro de la sesión de fotografía de este encargo (15 de septiembre de 2026):
tres fotografías del catálogo de stock y ocho generaciones con Seedream 5 Pro en Magnific. Id de
origen, autor, tamaño original y texto alternativo salen de ese registro; el `alt` se copia aquí
literal y es el mismo que lleva cada `<img>` de la maqueta. El fichero de `img/` es un recorte ya
hecho a la proporción de su hueco y convertido con GD (`imagewebp`, calidad 80), no el original: los
originales (de 1728×2304 a 6408×4277) no se versionan.

La dirección de foto del modelo B es **color, flash duro y negros profundos**, con luz de recorte
fucsia en los retratos y en las escenas generadas y toques amarillos (trineo, medias, toalla,
camiseta). Ningún id de stock ni de generación se comparte con la Plantilla hermana `forja`.

| Slug | Rol | Tamaño | Peso | Origen | Sesión | Licencia | `alt` |
|---|---|---|---|---|---|---|---|
| `forja-fucsia-hero` | primera pantalla de inicio · a sangre bajo velo `.8` | 1440×900 | 51 KB | 112696321 | stock · nazariykarkhut | Freepik premium | Atleta salta a un cajón de madera con los brazos al frente en un box oscuro |
| `forja-fucsia-clase` | «Nadie entrena solo» · a sangre bajo velo `.8` | 1440×810 | 64 KB | dtHOUreXSL | seedream-5-pro · escenas B | Magnific AI (Seedream 5 Pro) | Clase de nueve atletas lanza balones medicinales mientras dos compañeros chocan las manos en primer plano |
| `forja-fucsia-racks` | el box 4:5 · zona de fuerza | 720×900 | 51 KB | ov54JaT829 | seedream-5-pro · escenas B | Magnific AI (Seedream 5 Pro) | Atleta con camiseta amarilla suelta la barra entre una nube de magnesio junto al rack |
| `forja-fucsia-pista` | el box 4:5 · rig y pista | 720×900 | 36 KB | 8681026 | stock · ufabizphoto | Freepik premium | Atleta de pelo rizado empuja un trineo amarillo cargado de discos sobre césped artificial |
| `forja-fucsia-cardio` | el box 4:5 · cardio | 720×900 | 58 KB | 40368887 | stock · PlaceboPill | Freepik premium | Atleta con medias amarillas rema con esfuerzo en un remoergómetro dentro de un box oscuro |
| `forja-fucsia-vestuarios` | el box 4:5 · vestuarios | 720×900 | 31 KB | hux39rAvqL | seedream-5-pro · escenas B | Magnific AI (Seedream 5 Pro) | Vestuario con taquillas fucsia, banco negro con toalla amarilla, comba y bolsa de deporte |
| `forja-fucsia-coach-marta` | retrato 3:4 · Marta Ferrer, head coach | 600×800 | 38 KB | w4zmBzw7EI | seedream-5-pro · retratos B | Magnific AI (Seedream 5 Pro) | Coach de unos cincuenta años con pelo corto canoso y camiseta gris sobre fondo negro |
| `forja-fucsia-coach-ivan` | retrato 3:4 · Iván Losada, fuerza | 600×800 | 40 KB | bxO0z3t5Y2 | seedream-5-pro · retratos B | Magnific AI (Seedream 5 Pro) | Coach de fuerza corpulento con la cabeza rapada y camiseta gris sobre fondo negro |
| `forja-fucsia-coach-nora` | retrato 3:4 · Nora Blanco, gimnástico | 600×800 | 34 KB | rgNp0MOxtc | seedream-5-pro · retratos B | Magnific AI (Seedream 5 Pro) | Coach de gimnástico con pelo corto oscuro y camiseta gris sin mangas sobre fondo negro |
| `forja-fucsia-coach-samuel` | retrato 3:4 · Samuel Ruiz, endurance | 600×800 | 35 KB | ov5473l829 | seedream-5-pro · retratos B | Magnific AI (Seedream 5 Pro) | Coach de resistencia joven con pelo rizado y barba corta, camiseta gris sobre fondo negro |
| `forja-fucsia-recuperacion` | nutrición y recuperación 1:1 · fisioterapia | 720×720 | 35 KB | YMmjKnfWeC | seedream-5-pro · escenas B | Magnific AI (Seedream 5 Pro) | Fisioterapeuta coloca vendaje neuromuscular rosa en el hombro de un atleta sentado con toalla |

## Recortes y focos

- **`hero` a 1440×900 (16:10)** desde 6245×4163: se quita la franja inferior de suelo (recorte
  `y 260`, alto 3903) y se conserva la cabeza de la atleta. La atleta ocupa el tercio izquierdo y el
  lienzo centra el texto sobre toda la anchura, así que el texto pisa a la atleta en cualquier
  recorte: no hay lado tranquilo que elegir con un texto centrado a 1100px. Lo resuelve el velo plano
  `rgba(8,8,8,.8)`, medido con `scrim.php --peor-pixel` sobre **la foto entera** (vale para cualquier
  `object-fit` a cualquier ancho): papel `#F4F4F1` 10,44:1, `#C0C0BE` 6,31:1 y fucsia `#FF1F6B`
  3,10:1. Los números pintados de la pared quedan en la franja superior y el velo los apaga: no se
  leen en las capturas a 430, 768 ni 1280.
- **`clase` a 1440×810 (16:9)** sin recorte desde 2560×1440. Mismo velo: papel 10,62:1, `#C0C0BE`
  6,42:1, fucsia 3,15:1 sobre la foto entera.
- **`pista` y `cardio`**, horizontales, en 4:5: recorte vertical completo con el centro al 35 % y al
  40 % del ancho, para conservar atleta y trineo enteros y dejar fuera casi toda la pizarra borrosa
  de la esquina superior izquierda de `cardio` (queda una tira de unos 40px en el borde).
- **`racks`, `vestuarios` y los cuatro retratos** vienen en 3:4 (Seedream 5 Pro no ofrece 4:5). Las
  instalaciones se recortan a 4:5 quitando 72px arriba y abajo del original; los retratos se quedan
  en 3:4 y sólo se reducen.
- **`recuperacion`** se reduce de 2048×2048 a 720×720, sin recorte.

## Lo que la sesión deja a la vista

- **Los cuatro coaches son personas inventadas, generadas** con un único prompt de estilo (fondo
  negro sin costura, flash desde la izquierda, contraluz fucsia, camiseta gris lisa) y sin parecido
  buscado con nadie real. **En un encargo se sustituyen por los retratos del equipo del cliente** y
  nunca se presentan como su equipo.
- **`racks`** lleva una pequeña marca gráfica borrosa en el pecho de la camiseta amarilla.
- **`clase`** es generada: hay más balones en el aire que lanzadores y algunos gestos parecen de
  recepción. Bajo el velo no se nota; si un juez lo señala, se sustituye por foto real de una clase.
- **`coach-marta` y `coach-nora` llevan camiseta sin mangas;** Iván y Samuel, manga corta del mismo
  gris.
- **`recuperacion` se repite en tres páginas** (inicio, el box y disciplinas) y las cuatro
  instalaciones en dos (inicio y el box), porque el lienzo repite esas secciones. En el box la foto
  de instalación es el producto de la página; la de recuperación es ambiente y es la primera que
  conviene duplicar con una segunda sesión.
- **El stock premium exige la licencia de la cuenta premium vigente** en el momento de publicar.

## Autoría

La licencia premium del catálogo no pide atribución y las generaciones de Magnific no tienen autor
externo. Autores de las tres fotografías de stock según el registro:

- `forja-fucsia-hero` (112696321): nazariykarkhut
- `forja-fucsia-pista` (8681026): ufabizphoto
- `forja-fucsia-cardio` (40368887): PlaceboPill
