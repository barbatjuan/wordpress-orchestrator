# Formato del veredicto (`veredicto.md`)

Cada plantilla de `references/plantillas/<slug>/` lleva un `veredicto.md`: el resultado de la puerta
visual —juez B, barrido de cada página a 430, 768 y 1280, hallazgos— atado a una huella de los bytes
que se juzgaron. Este documento define exactamente lo que lee
`skills/html-mockup/assets/herramientas/veredicto.php`. Lo que no figura aquí, la herramienta no lo
lee; lo que figura, lo exige.

## Por qué la huella se guarda en el repositorio y normalizada a LF

Un veredicto sólo vale mientras describe los bytes que hay en disco. Por eso el campo `hash` guarda la
huella de `huella.php` sobre `ficha.md`, `manifiesto-imagenes.md`, `canvas/`, `maqueta/` e `img/`
(nunca sobre el propio `veredicto.md`, que entonces no se podría sellar), y se sube al repositorio
junto a esos bytes. Como la huella viaja entre máquinas, tiene que ser la misma en Windows y en Linux:
los ficheros de texto (`.md .html .json .css .js .svg .txt`) se normalizan a LF antes de calcularla y
`.gitattributes` ya fija `eol=lf` bajo `plantillas/**`; las imágenes y fuentes se calculan tal cual.
Si alguien cambia un solo byte cubierto después de sellar, la huella deja de coincidir y el veredicto
queda **caducado**. Una plantilla con el veredicto caducado no se ofrece a un cliente hasta que se
vuelva a juzgar y a sellar.

## Estructura

Tres partes, en este orden: la cabecera, la sección `## Barrido` y la sección `## Hallazgos`. Entre
ellas se admite texto libre (un título, una nota del juez); la herramienta lo ignora.

### 1. Cabecera

El fichero abre con una línea `---`, lleva un campo por línea con la forma `campo: valor` y cierra con
otra línea `---`. No se admiten comentarios al final de la línea. Un campo escrito dos veces deja el
veredicto incompleto. Los campos desconocidos se ignoran.

| Campo | Valores | Quién lo escribe |
|---|---|---|
| `hash` | `sha256:` seguido de 64 caracteres hexadecimales en minúscula | `veredicto.php --sellar`, nunca a mano |
| `fecha` | `AAAA-MM-DD` | `veredicto.php --sellar` |
| `juez_b` | `profesional` o `no-profesional` | quien registra la opinión del juez B |
| `autojuzgado` | `sí` (con tilde) o `no` | ídem; sin una familia de modelo externa, `sí` |
| `vistas` | número entero: páginas con sus tres celdas capturadas | quien registra el barrido |
| `saltadas` | número entero: páginas con al menos una celda `no-disponible` | quien registra el barrido |

Un veredicto recién escrito por los jueces no lleva `hash` ni `fecha`: los añade el sellado.

### 2. `## Barrido`

La primera tabla bajo el encabezado `## Barrido`. Su cabecera es `| Página | 430 | 768 | 1280 |`: la
primera columna puede llamarse de cualquier modo; las otras tres se llaman exactamente así y en ese
orden.

Lleva **una fila por cada página** que declara `paginas:` en `ficha.md`: ni una menos, ni una que la
ficha no declare, ni una repetida. El nombre de la página puede ir entre comillas invertidas.

Cada celda contiene uno de estos valores:

- `✓` — el carácter U+2713, y ningún otro signo parecido: a ese ancho se cumplen las cuatro
  mediciones del barrido (sin desbordamiento horizontal, rejillas con más de una pista en escritorio,
  sin pistas fantasma ni huérfanos, y en 430 un único control de menú desplegable).
- `no-disponible` — no se pudo capturar la página a ese ancho.
- Uno o varios identificadores de hallazgo: `H1`, o `H1, H3`. Cada uno tiene que figurar en
  `## Hallazgos`.

Una celda vacía, o con cualquier otro texto, deja el veredicto incompleto.

Los recuentos tienen que cuadrar con la tabla: `vistas + saltadas` es el número de filas, y
`saltadas` es el número de filas con alguna celda `no-disponible`.

Un barrido con alguna celda `no-disponible` es **PARCIAL**. No existe un campo para decirlo: se deduce
de las celdas, porque un campo escrito a mano podría contradecirlas.

### 3. `## Hallazgos`

O bien una única línea con la palabra `ninguno`, o bien una lista con un elemento por hallazgo:

```
- H1 · <página> · <elemento> · <breakpoint> · esperado: <…> · observado: <…>
```

La herramienta lee el identificador del principio de cada elemento (`H` y un número; puede ir en
negrita). El resto de la línea es para quien lo lea: elemento, ancho, lo esperado y lo observado. Una
sección vacía, o ausente, deja el veredicto incompleto.

## Sellar, comprobar, revisar la biblioteca

`--root=<dir>` es la raíz del repositorio; por defecto, la del propio checkout. Códigos de salida:
`0` aprobado, `1` fallo medido, `2` error de uso o de entorno (un slug no válido, una plantilla que no
existe, una biblioteca vacía). Nunca `0` para algo que no se pudo medir.

**`veredicto.php --sellar <slug>`** escribe `hash` con la huella actual y `fecha` con la de hoy: los
sustituye si ya estaban y los añade al principio de la cabecera si no. El resto del fichero queda
intacto y se escribe con finales de línea LF. Se niega a sellar, con salida `1` y sin escribir nada,
cuando:

- no existe `veredicto.md`: no hay nada juzgado que sellar;
- el veredicto está incompleto: falta `juez_b`, `autojuzgado`, `vistas` o `saltadas`; hay una celda
  vacía o no reconocida; falta una fila, sobra una o está repetida; los recuentos no cuadran; falta
  `## Hallazgos` o no lista un identificador citado en una celda; `ficha.md` no declara `paginas:`;
- `juez_b` dice `no-profesional`.

Un barrido con hallazgos o con celdas `no-disponible` **sí** se puede sellar: el veredicto deja
constancia de lo que se vio. Lo que no hace es aprobar.

**`veredicto.php --comprobar <slug>`** sale con `0` y dice «vigente» sólo si se cumple todo: el
fichero existe, está completo, `juez_b` es `profesional`, está sellado, su `hash` coincide con la
huella actual y todas las celdas son `✓`. Si no, sale con `1` y una sola línea con todos los motivos,
cada uno encabezado por su palabra clave: `ausente`, `incompleto`, `no-profesional`, `sin sellar`,
`caducado`, `PARCIAL` o `hallazgos abiertos`.

**`veredicto.php --biblioteca`** comprueba cada subcarpeta directa de `plantillas/` cuyo nombre no
empiece por `_`, escribe una línea por plantilla (`OK` o `FAIL` con sus motivos) y un resumen, y sale
con `1` si alguna no está vigente.

Volver a sellar sin volver a juzgar vacía el veredicto de sentido. La herramienta no puede
distinguirlo; por eso, cuando un veredicto caduca, se vuelven a barrer las páginas afectadas, se
actualizan la tabla y los hallazgos, y sólo entonces se sella.

## Ejemplo completo: plantilla corporativa de diez páginas

La ficha declara `paginas: [inicio, servicios, servicio, proyectos, proyecto, nosotros, blog, articulo,
contacto, legal]`. Veredicto sellado y vigente:

```markdown
---
hash: sha256:3f9a1c7e5b2d4a60c8e1f7b39d2a6c5e0b4f8d17a3c9e2b6f1d5a8c4e7b0d392
fecha: 2026-09-14
juez_b: profesional
autojuzgado: sí
vistas: 10
saltadas: 0
---

# Veredicto · plantilla corporativa de ejemplo

El juez B vio cada página por separado, sin la ficha ni el lienzo, y la describió con los ocho
atributos. Juzgado por la misma familia de modelo que la construyó.

## Barrido

| Página | 430 | 768 | 1280 |
|---|---|---|---|
| inicio | ✓ | ✓ | ✓ |
| servicios | ✓ | ✓ | ✓ |
| servicio | ✓ | ✓ | ✓ |
| proyectos | ✓ | ✓ | ✓ |
| proyecto | ✓ | ✓ | ✓ |
| nosotros | ✓ | ✓ | ✓ |
| blog | ✓ | ✓ | ✓ |
| articulo | ✓ | ✓ | ✓ |
| contacto | ✓ | ✓ | ✓ |
| legal | ✓ | ✓ | ✓ |

## Hallazgos

ninguno
```

La misma plantilla en una ronda anterior, antes de corregir la rejilla y con el navegador caído a
768 en el blog. Está completa y se pudo sellar, pero `--comprobar` responde `PARCIAL` y
`hallazgos abiertos: H1 (inicio@430), H2 (proyectos@768)`:

```markdown
---
hash: sha256:9b0e4d2a7c1f6e38a5d9c0b7e2f4a18d6c3b5e9f0a7d2c4b8e1f6a3d5c9b7e20
fecha: 2026-09-11
juez_b: profesional
autojuzgado: sí
vistas: 9
saltadas: 1
---

## Barrido

| Página | 430 | 768 | 1280 |
|---|---|---|---|
| inicio | H1 | ✓ | ✓ |
| servicios | ✓ | ✓ | ✓ |
| servicio | ✓ | ✓ | ✓ |
| proyectos | ✓ | H2 | ✓ |
| proyecto | ✓ | ✓ | ✓ |
| nosotros | ✓ | ✓ | ✓ |
| blog | ✓ | no-disponible | ✓ |
| articulo | ✓ | ✓ | ✓ |
| contacto | ✓ | ✓ | ✓ |
| legal | ✓ | ✓ | ✓ |

## Hallazgos

- H1 · inicio · rejilla de servicios · 430 · esperado: 1 columna · observado: 2 columnas de 173px
- H2 · proyectos · rejilla de proyectos · 768 · esperado: 6 tarjetas en 2 pistas · observado: 3 pistas y una tarjeta huérfana
```
