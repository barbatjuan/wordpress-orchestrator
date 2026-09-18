# Formato del estado (`diseno/estado.md`)

El estado de un cliente vive en su carpeta, en `diseno/estado.md`, y es lo único que una fase le pasa
a la siguiente. El orquestador lee este fichero y no el trabajo; una sesión nueva o reanudada arranca
leyéndolo sólo a él. Lo crea el primer agente de fase, lo reescribe cada agente al cerrar su fase, y
el orquestador sólo anota lo que el usuario decide en una puerta. Rutas y URLs, nunca capturas,
fragmentos de maqueta ni JSON del builder. Es dato del cliente: no entra en el repositorio.

## Cabecera

Entre dos líneas `---`, un campo por línea con la forma `campo: valor`.

| Campo | Valor |
|---|---|
| `cliente` | el nombre, como figura en el encargo |
| `plantilla` | el slug de la Plantilla base, o `ruta-a-medida` |
| `fase_actual` | la última fase cerrada, o la que se cortó a medias |
| `fase_siguiente` | la próxima fase, o la puerta que la bloquea |
| `actualizado` | `AAAA-MM-DD HH:MM · quién` — el agente de qué fase, o el orquestador |

Fases, en orden: `lienzo`, `maqueta`, `veredicto`, `build`, `qa`, `entrega`. Puertas: `puerta-lienzo`
(lienzo dado por bueno), `puerta-cliente` (el cliente aprueba la maqueta con su veredicto) y
`puerta-build` (el sí explícito a escribir en WordPress). Una puerta en `fase_siguiente` quiere decir
que el siguiente movimiento es del usuario, y lo pregunta el orquestador.

## Cuerpo: cuatro secciones, en este orden

- `## Artefactos` — una línea por artefacto que ya existe, ruta relativa a la carpeta del cliente o
  URL: encargo, lienzo, maqueta (URL del Artifact y ruta local), veredicto (ruta, `hash` si está
  sellado y su estado: vigente, PARCIAL, SELF-JUDGED) y WordPress (URL del sitio, ruta directa o
  local, páginas escritas por slug). Los ids de página no se copian: son del manifiesto.
- `## Decisiones tomadas` — `AAAA-MM-DD · decisión · quién` (usuario, cliente o agente de fase). Sólo
  lo que ata a una fase posterior; el sí de `puerta-build`, con los slugs que cubre. Es la única
  sección que crece; las otras tres se reescriben enteras.
- `## Pendiente del usuario` — una pregunta por línea, o `nada`. Un agente de fase nunca la contesta;
  dos jueces que discrepan se copian aquí literales.
- `## Siguiente paso` — una sola línea que un agente nuevo pueda ejecutar con este fichero, la carpeta
  del cliente y la skill que nombra. Si `fase_siguiente` es una puerta, la línea es la pregunta.

## Ejemplo

```markdown
---
cliente: Estudio Ola
plantilla: amalia-salvia
fase_actual: veredicto
fase_siguiente: puerta-cliente
actualizado: 2026-09-18 17:40 · agente de fase veredicto
---
## Artefactos
- Encargo: diseno/encargo.md · Lienzo: https://claude.ai/artifact/…
- Maqueta: https://claude.ai/artifact/… · diseno/maqueta/index.html
- Veredicto: diseno/veredicto.md · sin sellar · SELF-JUDGED, barrido completo
## Decisiones tomadas
- 2026-09-17 · Lienzo dado por bueno · usuario
## Pendiente del usuario
- ¿Se enseña al cliente una maqueta con veredicto SELF-JUDGED?
## Siguiente paso
Preguntar la puerta-cliente; con el sí, un agente con project-context prepara la puerta-build.
```
