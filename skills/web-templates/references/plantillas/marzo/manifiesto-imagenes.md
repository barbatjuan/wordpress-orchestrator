# Manifiesto de imágenes · marzo

9 fotografías. **El slug ES el nombre del fichero Y el slug del adjunto en WordPress**: nada
se renombra después, porque el nombre viaja al sitio del cliente.

Generado leyendo los ficheros de `img/` y los `alt` de los artboards de `canvas/`, no escrito a mano:
tamaño y peso son los del fichero, y el texto alternativo es el que lleva la imagen en el diseño. El
origen es el identificador de la generación, recuperado del registro de la sesión en que se hizo.

| Slug | Rol | Tamaño | Peso | Origen | Sesión | Licencia | `alt` |
|---|---|---|---|---|---|---|---|
| `marzo-abrigo` | vertical 3:4 | 900×1350 | 37 KB | 5434824795 | fp-5434824 | Freepik AI (Pikaso) | Abrigo Sagra de lana merina sin teñir, largo hasta media pantorrilla, contra un muro de yeso al sol |
| `marzo-espalda` | vertical 3:4 | 800×1000 | 43 KB | 5434825269 | fp-5434825 | Freepik AI (Pikaso) | El abrigo visto por detrás: la costura de hombro y la caída del paño sobre la espalda |
| `marzo-manga` | vertical 3:4 | 392×490 | 15 KB | 5435601502 | fp-5435601 | Freepik AI (Pikaso) | Manga y puño del abrigo Sagra colgado en una barra de acero contra un muro de yeso |
| `marzo-pantalon` | vertical 3:4 | 800×1000 | 28 KB | 5434826395 | fp-5434826 | Freepik AI (Pikaso) | Pantalón ancho de algodón crudo fotografiado a media zancada |
| `marzo-patio` | banda apaisada | 1600×900 | 57 KB | 5434828444 | fp-5434828 | Freepik AI (Pikaso) | Mujer sentada en el borde de un poyo, en un patio encalado con un olivo, con el abrigo Sagra abierto |
| `marzo-plano` | vertical 3:4 | 800×1000 | 36 KB | 5434827882 | fp-5434827 | Freepik AI (Pikaso) | El abrigo doblado en plano sobre papel de embalar color hueso, visto desde arriba |
| `marzo-punto` | vertical 3:4 | 800×1000 | 57 KB | 5434827156 | fp-5434827 | Freepik AI (Pikaso) | Detalle del hombro y el cuello de un jersey de merino marfil |
| `marzo-taller` | banda apaisada | 1200×800 | 29 KB | 5434826817 | fp-5434826 | Freepik AI (Pikaso) | Patrón de papel prendido con alfileres sobre una mesa de corte de madera, junto a un rollo de tela cruda |
| `marzo-tejido` | banda apaisada | 1200×800 | 70 KB | 5434827100 | fp-5434827 | Freepik AI (Pikaso) | Primerísimo plano de una sarga de algodón crudo con una costura cargada, iluminada a contraluz rasante |

**9 filas, 9 ficheros.** Una fila sin fichero, o un fichero sin fila, es un defecto. Hoy
no lo comprueba ninguna regla de auditoría: lo comprueba quien revisa el PR, contando.

**Una sola tirada, dicho sin disimular.** Todas salen de la misma sesión de generación, con la misma
dirección de arte, porque así se fotografía de verdad una tienda: una sesión de producto. La columna
`Sesión` agrupa por el prefijo del identificador igual que en `delao`, y la más repetida lleva 3. Si
una regla futura exige diversificar sesiones, esta plantilla no la cumple y eso se decide ahí, no aquí.

**Comprobado a ojo, no sólo por nombre.** Cada fotografía se comparó con lo que su nombre dice antes de
entrar. Si la foto no coincidía se rehizo; si sólo difería un detalle, se corrigió el `alt` para describir
lo que se ve y no lo que se pidió.

**Marca ficticia.** Ni las fotografías ni el copy pertenecen a ningún cliente.
