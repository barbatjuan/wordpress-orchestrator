# Manifiesto de imágenes · cadencia

8 fotografías. **El slug ES el nombre del fichero Y el slug del adjunto en WordPress**: nada
se renombra después, porque el nombre viaja al sitio del cliente.

Generado leyendo los ficheros de `img/` y los `alt` de los artboards de `canvas/`, no escrito a mano:
tamaño y peso son los del fichero, y el texto alternativo es el que lleva la imagen en el diseño. El
origen es el identificador de la generación, recuperado del registro de la sesión en que se hizo.

| Slug | Rol | Tamaño | Peso | Origen | Sesión | Licencia | `alt` |
|---|---|---|---|---|---|---|---|
| `cadencia-camiseta` | tarjeta 4:3 | 596×447 | 29 KB | 5436330363 | fp-5436330 | Freepik AI (Pikaso) | Camiseta técnica blanca tiza extendida en plano cenital sobre una superficie de pizarra oscura |
| `cadencia-cortavientos` | vertical 3:4 | 384×512 | 16 KB | 5436332122 | fp-5436332 | Freepik AI (Pikaso) | Cortavientos gris pizarra colgado de una percha de madera sobre una pared de hormigón |
| `cadencia-frio` | vertical 3:4 | 384×512 | 25 KB | 5436369608 | fp-5436369 | Freepik AI (Pikaso) | Persona de espaldas al aire libre en una mañana fría, subiéndose la cremallera de un cortavientos gris pizarra |
| `cadencia-hero` | banda apaisada | 1224×430 | 39 KB | 5436330048 | fp-5436330 | Freepik AI (Pikaso) | Corredor solitario de espaldas en una carretera mojada y vacía al amanecer, bajo un cielo encapotado |
| `cadencia-malla` | detalle 1:1 | 480×480 | 20 KB | 5436371132 | fp-5436371 | Freepik AI (Pikaso) | Macro de la costura y la cintura de una malla técnica gris pizarra, con el pespunte plano a la vista |
| `cadencia-remo` | tarjeta 4:3 | 596×447 | 16 KB | 5436329403 | fp-5436329 | Freepik AI (Pikaso) | Dos manos agarrando la empuñadura de una máquina de remo a media palada |
| `cadencia-sala` | banda apaisada | 1224×460 | 49 KB | 5436370503 | fp-5436370 | Freepik AI (Pikaso) | Sala de entrenamiento con paredes de yeso desnudas, suelo de madera gastada, una máquina de remo y una torre de discos |
| `cadencia-taller` | tarjeta 4:3 | 596×400 | 31 KB | 5436333527 | fp-5436333 | Freepik AI (Pikaso) | Banco de pruebas de tejidos: muestras prendidas en fila, una báscula, un pulverizador y un cuaderno bajo un flexo |

**8 filas, 8 ficheros.** Una fila sin fichero, o un fichero sin fila, es un defecto. Hoy
no lo comprueba ninguna regla de auditoría: lo comprueba quien revisa el PR, contando.

**Una sola tirada, dicho sin disimular.** Todas salen de la misma sesión de generación, con la misma
dirección de arte, porque así se fotografía de verdad una tienda: una sesión de producto. La columna
`Sesión` agrupa por el prefijo del identificador igual que en `delao`, y la más repetida lleva 2. Si
una regla futura exige diversificar sesiones, esta plantilla no la cumple y eso se decide ahí, no aquí.

**Comprobado a ojo, no sólo por nombre.** Cada fotografía se comparó con lo que su nombre dice antes de
entrar. Si la foto no coincidía se rehizo; si sólo difería un detalle, se corrigió el `alt` para describir
lo que se ve y no lo que se pidió.

**Marca ficticia.** Ni las fotografías ni el copy pertenecen a ningún cliente.
