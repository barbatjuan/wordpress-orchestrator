# Manifiesto de imágenes · barro

10 fotografías. **El slug ES el nombre del fichero Y el slug del adjunto en WordPress**: nada
se renombra después, porque el nombre viaja al sitio del cliente.

Generado leyendo los ficheros de `img/` y los `alt` de los artboards de `canvas/`, no escrito a mano:
tamaño y peso son los del fichero, y el texto alternativo es el que lleva la imagen en el diseño. El
origen es el identificador de la generación, recuperado del registro de la sesión en que se hizo.

| Slug | Rol | Tamaño | Peso | Origen | Sesión | Licencia | `alt` |
|---|---|---|---|---|---|---|---|
| `barro-aceite` | banda apaisada | 1440×540 | 31 KB | 5435601402 | fp-5435601 | Freepik AI (Pikaso) | El cuenco hondo con aceite de oliva y un paño de lino crudo al lado, sobre fondo casi negro |
| `barro-cama` | banda apaisada | 1200×800 | 52 KB | 5434832976 | fp-5434832 | Freepik AI (Pikaso) | Cama deshecha con ropa de lino lavado, manta de lana umbría y una taza sobre un taburete de roble |
| `barro-candelero` | detalle 1:1 | 800×800 | 13 KB | 5434830711 | fp-5434830 | Freepik AI (Pikaso) | Candelero de roble torneado con una vela de cera de abeja sobre fondo blanco tiza |
| `barro-cuenco` | detalle 1:1 | 800×800 | 8 KB | 5434830719 | fp-5434830 | Freepik AI (Pikaso) | Cuenco de gres con esmalte mate color avena sobre fondo blanco tiza |
| `barro-esmalte` | banda apaisada | 1200×800 | 43 KB | 5434830830 | fp-5434830 | Freepik AI (Pikaso) | Detalle del esmalte encontrándose con el pie de barro crudo, con los anillos del torno a la vista |
| `barro-manos` | banda apaisada | 1440×620 | 26 KB | 5435601881 | fp-5435601 | Freepik AI (Pikaso) | Las manos de Marta Sedano levantando el cuenco terminado, con polvo de barro en los dedos |
| `barro-mesa` | banda apaisada | 1200×800 | 61 KB | 5434831060 | fp-5434831 | Freepik AI (Pikaso) | Mesa vestida de lino con platos de gres, jarra de barro y un cuenco de aceitunas |
| `barro-rincon` | banda apaisada | 1600×900 | 44 KB | 5434829165 | fp-5434829 | Freepik AI (Pikaso) | Vasija de barro y taza oscura sobre una repisa de travertino, con la sombra de la mañana en la pared encalada |
| `barro-servilletas` | detalle 1:1 | 800×800 | 15 KB | 5434830295 | fp-5434830 | Freepik AI (Pikaso) | Tres servilletas de lino crudo dobladas sobre fondo blanco tiza |
| `barro-torno` | banda apaisada | 1200×800 | 47 KB | 5434831432 | fp-5434831 | Freepik AI (Pikaso) | Las manos de la alfarera centrando el barro en el torno |

**10 filas, 10 ficheros.** Una fila sin fichero, o un fichero sin fila, es un defecto. Hoy
no lo comprueba ninguna regla de auditoría: lo comprueba quien revisa el PR, contando.

**Una sola tirada, dicho sin disimular.** Todas salen de la misma sesión de generación, con la misma
dirección de arte, porque así se fotografía de verdad una tienda: una sesión de producto. La columna
`Sesión` agrupa por el prefijo del identificador igual que en `delao`, y la más repetida lleva 4. Si
una regla futura exige diversificar sesiones, esta plantilla no la cumple y eso se decide ahí, no aquí.

**Comprobado a ojo, no sólo por nombre.** Cada fotografía se comparó con lo que su nombre dice antes de
entrar. Si la foto no coincidía se rehizo; si sólo difería un detalle, se corrigió el `alt` para describir
lo que se ve y no lo que se pidió.

**Marca ficticia.** Ni las fotografías ni el copy pertenecen a ningún cliente.
