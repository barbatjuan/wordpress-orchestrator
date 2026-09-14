# Índice de la biblioteca de plantillas

Una fila por plantilla. El veredicto es lo que separa una plantilla de una carpeta de ficheros:
sin veredicto vigente, la plantilla no se ofrece a un cliente.

| Slug | Objetivo | Enfoque | Tipo | Páginas | Veredicto |
|---|---|---|---|---|---|
| `delao` · Inmobiliaria de la O | `cartera-curada` | `editorial` | corporate | inicio · propiedades · ficha · nosotros · contacto | pendiente de firma |
| `marzo` · MARZO | `tienda-talla` | `materia` | ecommerce | portada · categoría · ficha de producto · carro · pago · pedido recibido · mi cuenta · la marca · contacto · condiciones de venta y envíos · aviso legal · privacidad · cookies · 404 | sin veredicto |
| `barro` · BARRO | `tienda-lote` | `lujo-oscuro` | ecommerce | portada · categoría · ficha de producto · carro · pago · pedido recibido · mi cuenta · la marca · contacto · condiciones de venta y envíos · aviso legal · privacidad · cookies · 404 | sin veredicto |
| `cadencia` · CADENCIA | `equipo-por-uso` ⚑ | `tecnologico` | ecommerce | portada · ficha | sin veredicto · sin maqueta |
| `escuadra` · ESCUADRA | `catalogo-amplio` ⚑ | `directo` | ecommerce | portada · categoría · ficha de producto · carro · pago · pedido recibido · mi cuenta · la marca · contacto · condiciones de venta y envíos · aviso legal · privacidad · cookies · 404 | sin veredicto |

⚑ Objetivo nuevo, que todavía no existe en ningún recomendador. Se da de alta cuando exista
`recomendador.md`, o la plantilla se reasigna a un objetivo que ya exista.

**Una de las cuatro tiendas es todavía lienzo y fotografías, no una plantilla completa.** `cadencia`
tiene ficha, manifiesto de imágenes y canvas; le falta la maqueta derivada. `marzo`, `barro` y
`escuadra` ya tienen su juego completo de 14 páginas derivado en `maqueta/index.html`; a las tres les
falta sólo el veredicto. Hasta tener las dos cosas, ninguna de las cuatro se ofrece a un cliente,
igual que `delao` hasta que firme el suyo.

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

## marzo

- `references/plantillas/marzo/ficha.md`
- `references/plantillas/marzo/manifiesto-imagenes.md`
- `references/plantillas/marzo/canvas/` — `Marzo`, `MarzoPieza`, `Categoria`, `LaMarca`, `Contacto`,
  `canvas.json` y su `MANIFIESTO.md`
- `references/plantillas/marzo/maqueta/` — `index.html`
- `references/plantillas/marzo/img/` — nueve `.webp`

## barro

- `references/plantillas/barro/ficha.md`
- `references/plantillas/barro/manifiesto-imagenes.md`
- `references/plantillas/barro/canvas/` — `Barro`, `BarroPieza`, `Categoria`, `LaMarca`, `Contacto`,
  `canvas.json` y su `MANIFIESTO.md`
- `references/plantillas/barro/maqueta/` — `index.html`
- `references/plantillas/barro/img/` — treinta `.webp`, de los que sólo diez están en el manifiesto y
  se usan; los otros veinte son huérfanos sin procedencia recuperable y no se tocan

## cadencia

- `references/plantillas/cadencia/ficha.md`
- `references/plantillas/cadencia/manifiesto-imagenes.md`
- `references/plantillas/cadencia/canvas/` — `Cadencia`, `CadenciaPieza`, `canvas.json` y su `MANIFIESTO.md`
- `references/plantillas/cadencia/img/` — ocho `.webp`

## escuadra

- `references/plantillas/escuadra/ficha.md`
- `references/plantillas/escuadra/manifiesto-imagenes.md`
- `references/plantillas/escuadra/canvas/` — `Escuadra`, `EscuadraPieza`, `Categoria`, `LaMarca`,
  `Contacto`, `canvas.json` y su `MANIFIESTO.md`
- `references/plantillas/escuadra/maqueta/` — `index.html`
- `references/plantillas/escuadra/img/` — dieciséis `.webp`
