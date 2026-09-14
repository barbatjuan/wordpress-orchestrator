# Glosario

El vocabulario del dominio, en español. Cada término sustituye a uno o varios del catálogo anterior;
el término viejo no se usa en documentos nuevos. Donde el diseño del cambio (`openspec/changes/plantillas-reales/design.md`)
corrige al plan, este glosario sigue al diseño.

| Término | Qué es | Sustituye a |
|---|---|---|
| **Plantilla** | Un sitio real completo para una marca ficticia, usado como punto de partida: canvas, maqueta, fotografías con manifiesto, ficha y veredicto, todo en `plantillas/<slug>/`. El slug es el nombre de la marca (`delao`, `marzo`). Nunca es un cliente real y nunca se genera desde código | `TPL-*`, arquetipo, tira de galería, chasis |
| **Objetivo** | Lo que el sitio tiene que conseguir para el negocio, dicho en una frase y con un id (`cartera-curada`, `tienda-talla`). Es lo primero que se identifica en un brief y lo que elige la plantilla. Catálogo: `recomendador.md` | arquetipo, «qué publica», familia A/B, perfil |
| **Enfoque** | El planteamiento visual, descrito con ocho ejes (escala, densidad, fondo, elevación, composición, acento, chasis, ornamento), un par tipográfico y una dirección de imagen. Es vocabulario para hablar con el cliente, con los jueces y con el registro de entregas; no emite tokens ni repinta nada. Ids y descripción: `enfoques.md` | ancla, `PERS-*`, `STY-*`, personalidad, estilo |
| **Variante** | Un conmutador que la maqueta de una plantilla implementa de verdad, declarado en `variantes:` de su ficha (p. ej. `hero: retrato / buscador` en `delao`). Lo que la maqueta no implementa no es variante | toggle, `TGL-*`, precarga |
| **Ficha** | El `ficha.md` de una plantilla: frontmatter (slug, nombre, tipo, sector, objetivo, enfoque, páginas, fuentes, dirección del canvas, variantes, techo nativo) y un cuerpo corto: para qué sirve y para qué no, ADN, qué se cambia para un cliente, mapeo nativo. El copy no vive en la ficha: vive en el canvas y la maqueta | documento de arquetipo, `TPL-*.md`, wireframe `COMP-*` |
| **Canvas** (lienzo) | Los artboards `.dc.html` de Claude Design, con `canvas.json` cuando hay más de uno. Es la autoridad del diseño: todo cambio empieza aquí. Cada página con artboard de escritorio lleva su artboard móvil de 390 al lado, que es donde se diseña el menú plegado | artboards del handoff, chasis generado |
| **Maqueta** | Un único `maqueta/index.html` responsive, con navegación interna entre páginas, derivado a mano del canvas bajo el contrato de derivación de `html-mockup`. Es lo que el cliente aprueba. Se publica como un solo Artifact con fuentes e imágenes incrustadas | mockup, chasis re-apuntado, galería |
| **Mapeo nativo** | La tabla de la ficha que asigna cada sección de la maqueta a elementos nativos de Elementor (y de Divi, o «no validado»). La primera columna son los ids de sección de la maqueta. Una sección sin fila no existe en la plantilla | lista `COMP-*` (no existía como contrato con el constructor) |
| **Veredicto** | El resultado de la puerta visual de una plantilla o de una maqueta de cliente: juez B (profesional o no), declaración de autojuzgado, barrido de cada página a 430, 768 y 1280, recuento de vistas y saltadas, y hallazgos. Va atado a una huella de los bytes juzgados (ficha, manifiesto, canvas, maqueta, imágenes); si los bytes cambian, caduca. Un barrido incompleto se registra PARCIAL, nunca como aprobado. La biblioteca lleva además un veredicto de conjunto (juez A, «misma mano») | (no existía) |
| **Ruta a medida** | Lo que se hace cuando ningún Objetivo encaja o las referencias del cliente contradicen el enfoque de todas las candidatas: diseño desde cero en Claude Design, declarando antes Objetivo, Enfoque y lista de secciones, y bajo las mismas puertas (lienzo, maqueta, veredicto, build nativo). Un resultado que pasa veredicto y mapeo nativo puede promoverse a plantilla | bespoke, `ROUTE-BESPOKE`, `BSP-*` |
| **Página obligatoria** | Una página que toda plantilla de su tipo lleva, se pregunte o no: diez en corporate, catorce en ecommerce. Cada una se diseña en el lienzo o se deriva en la maqueta del sistema de la plantilla. Lista y razones: `paginas-obligatorias.md` | páginas «no negociables», `TPL-LEGAL-01`, `TPL-404-01`, `TPL-THANKS-01`, set de páginas |
| **Techo nativo** | El máximo de widgets HTML (`html_widgets_max`) y de reglas de CSS a medida (`css_custom_max`) que admite el build de una plantilla; por defecto cero y declarado en la ficha. Incluye la regla de derivación que lo hace alcanzable: la maqueta sólo rompe en 1024 y 767, los dos puntos que Elementor expresa de forma nativa, y colores y tipografías viven en los ajustes globales, no en CSS | (no existía; el CSS global de `:root` era la norma) |

## Relaciones que conviene no confundir

- **Objetivo elige, Enfoque comprueba.** La plantilla se elige por Objetivo; el Enfoque sirve para
  comprobar que las referencias del cliente no piden otra cosa. Ver `recomendador.md`.
- **Ruta a medida no es el Objetivo `a-medida`.** `a-medida` es un objetivo de negocio (vender por
  presupuesto algo que se fabrica a medida). La ruta a medida es un camino de diseño y lleva su propio
  Objetivo, que puede ser cualquiera del catálogo.
- **Canvas manda, maqueta deriva.** Un cambio hecho sólo en la maqueta se pierde en la siguiente
  derivación y deja caducado el veredicto.
- **Variante no es enfoque.** Una variante cambia una sección dentro de la misma plantilla; no la
  convierte en otro planteamiento visual.
