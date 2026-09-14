# Índice de la biblioteca de plantillas

Una fila por plantilla. El veredicto es lo que separa una plantilla de una carpeta de ficheros:
sin veredicto vigente, la plantilla no se ofrece a un cliente.

| Slug | Objetivo | Enfoque | Tipo | Páginas | Veredicto |
|---|---|---|---|---|---|
| `delao` · Inmobiliaria de la O | `cartera-curada` | `editorial` | corporate | inicio · propiedades · ficha · nosotros · contacto | pendiente de firma |

## Qué contiene cada plantilla

Una carpeta por slug, y dentro siempre lo mismo:

- `ficha.md` — sector, objetivo, enfoque, páginas, variantes, techo nativo y el mapeo a Elementor.
- `canvas/` — los artboards de Claude Design. Es la AUTORIDAD del diseño: todo cambio empieza ahí.
- `maqueta/` — un `index.html` autocontenido, responsive, con navegación interna. Se DERIVA del
  canvas; no se genera.
- `img/` — las fotografías. El nombre del fichero es el slug del adjunto en WordPress y no se
  renombra nunca.
- `manifiesto-imagenes.md` — una fila por fotografía, con su rol, su origen, su sesión, su
  licencia y su texto alternativo.
- `veredicto.md` — el resultado de la puerta visual, atado a una huella de los bytes de la
  plantilla. Si la huella no coincide, el veredicto está caducado.

## delao

- `references/plantillas/delao/ficha.md`
- `references/plantillas/delao/manifiesto-imagenes.md`
- `references/plantillas/delao/canvas/` — `Inicio`, `Propiedades`, `Ficha`, `Nosotros`, `Contacto`,
  `Nav`, `Pie` y su `MANIFIESTO.md`
- `references/plantillas/delao/maqueta/` — `index.html`
- `references/plantillas/delao/img/` — doce `.webp`
