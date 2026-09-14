# Manifiesto de imágenes · escuadra

16 fotografías. **El slug ES el nombre del fichero Y el slug del adjunto en WordPress**: nada
se renombra después, porque el nombre viaja al sitio del cliente.

Generado leyendo los ficheros de `img/` y los `alt` de los artboards de `canvas/`, no escrito a mano:
tamaño y peso son los del fichero, y el texto alternativo es el que lleva la imagen en el diseño. El
origen es el identificador de la generación, recuperado del registro de la sesión en que se hizo.

| Slug | Rol | Tamaño | Peso | Origen | Sesión | Licencia | `alt` |
|---|---|---|---|---|---|---|---|
| `escuadra-banco` | banda apaisada | 1224×460 | 25 KB | 5436370276 | fp-5436370 | Freepik AI (Pikaso) | Banco bajo de contrachapado de abedul contra una pared de yeso, con la tapa entreabierta y unas zapatillas al lado |
| `escuadra-bano` | vertical 3:4 | 384×512 | 16 KB | 5436755362 | fp-5436755 | Freepik AI (Pikaso) | Rincón de baño con estantería de contrachapado, toallas de rizo dobladas, cuatro cestas de fibra y un espejo redondo |
| `escuadra-caja` | tarjeta 4:3 | 596×447 | 13 KB | 5436333408 | fp-5436333 | Freepik AI (Pikaso) | Tres cajas planas de cartón marrón sin imprimir apiladas en el suelo junto a una pared de yeso |
| `escuadra-cocina` | tarjeta 4:3 | 596×447 | 14 KB | 5436755817 | fp-5436755 | Freepik AI (Pikaso) | Rincón de cocina con balda de contrachapado, vajilla blanca apilada, tarros de vidrio, una tabla de madera y una sartén de acero colgada |
| `escuadra-dormitorio` | tarjeta 4:3 | 596×447 | 14 KB | 5436754292 | fp-5436754 | Freepik AI (Pikaso) | Dormitorio sencillo con cama baja de contrachapado, ropa de cama de algodón lavado azul grisáceo y una mesilla con lámpara |
| `escuadra-estante` | vertical 3:4 | 384×512 | 9 KB | 5436333148 | fp-5436333 | Freepik AI (Pikaso) | Mueble abierto de contrachapado de abedul contra una pared de yeso, con libros, una jarra de cerámica y una manta doblada |
| `escuadra-hero` | banda apaisada | 1224×430 | 20 KB | 5436370515 | fp-5436370 | Freepik AI (Pikaso) | Piso pequeño y luminoso amueblado en contrachapado de abedul: mesa, dos sillas y un mueble bajo contra la pared |
| `escuadra-idx-bano` | banda apaisada | 384×176 | 7 KB | 5436755362 (recorte de `escuadra-bano`) | fp-5436755 | Freepik AI (Pikaso) | Estantería de baño con toallas de rizo dobladas y cestas de fibra |
| `escuadra-idx-cocina` | banda apaisada | 384×176 | 5 KB | 5436755817 (recorte de `escuadra-cocina`) | fp-5436755 | Freepik AI (Pikaso) | Balda de cocina con vajilla blanca apilada y una sartén de acero colgada |
| `escuadra-idx-dormitorio` | banda apaisada | 384×176 | 5 KB | 5436754292 (recorte de `escuadra-dormitorio`) | fp-5436754 | Freepik AI (Pikaso) | Cama baja de contrachapado con ropa de cama de algodón lavado azul grisáceo |
| `escuadra-lampara` | vertical 3:4 | 384×512 | 3 KB | 5436757034 | fp-5436757 | Freepik AI (Pikaso) | Lámpara de techo con pantalla de papel blanco y suspensión de acero mate sobre una pared de yeso |
| `escuadra-mesa` | banda apaisada | 596×337 | 4 KB | 5436332942 | fp-5436332 | Freepik AI (Pikaso) | Mesa rectangular lisa de contrachapado de abedul fotografiada casi de alzado sobre fondo hueso |
| `escuadra-silla` | vertical 3:4 | 384×512 | 5 KB | 5436333655 | fp-5436333 | Freepik AI (Pikaso) | Silla de contrachapado de abedul vista de tres cuartos sobre fondo infinito hueso |
| `escuadra-taller` | banda apaisada | 1224×460 | 48 KB | 5436334977 | fp-5436334 | Freepik AI (Pikaso) | Banco de montaje visto desde arriba con paneles de contrachapado claro dispuestos en orden y una bolsa de herrajes volcada |
| `escuadra-textil` | vertical 3:4 | 384×512 | 22 KB | 5436756646 | fp-5436756 | Freepik AI (Pikaso) | Pila de textiles doblados sobre una superficie de contrachapado: toallas de rizo, una sábana azul grisácea y fundas de cojín de lino |
| `escuadra-union` | detalle 1:1 | 480×480 | 10 KB | 5436334879 | fp-5436334 | Freepik AI (Pikaso) | Macro de la esquina de un panel de contrachapado de abedul con el canto laminado a la vista, un herraje de acero embutido y una espiga de madera |

**16 filas, 16 ficheros.** Una fila sin fichero, o un fichero sin fila, es un defecto. Hoy
no lo comprueba ninguna regla de auditoría: lo comprueba quien revisa el PR, contando.

**Una sola tirada, dicho sin disimular.** Todas salen de la misma sesión de generación, con la misma
dirección de arte, porque así se fotografía de verdad una tienda: una sesión de producto. La columna
`Sesión` agrupa por el prefijo del identificador igual que en `delao`, y la más repetida lleva 4. Si
una regla futura exige diversificar sesiones, esta plantilla no la cumple y eso se decide ahí, no aquí.

**Comprobado a ojo, no sólo por nombre.** Cada fotografía se comparó con lo que su nombre dice antes de
entrar. Si la foto no coincidía se rehizo; si sólo difería un detalle, se corrigió el `alt` para describir
lo que se ve y no lo que se pidió.

**Marca ficticia.** Ni las fotografías ni el copy pertenecen a ningún cliente.
