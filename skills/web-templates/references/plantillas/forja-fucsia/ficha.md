---
slug: forja-fucsia
nombre: FORJA BOX · Fucsia
tipo: corporate
sector: box de entrenamiento funcional y halterofilia con clases en grupo y coaches certificados
objetivo: clase-de-prueba
enfoque: directo
paginas: [inicio, el-box, disciplinas, coaches, planes, contacto, gracias, aviso-legal, privacidad, cookies, 404]
fuentes: [archivo-black, archivo, barlow-condensed]
canvas_url: ""
variantes: {}
html_widgets_max: 0
css_custom_max: 1
---

# FORJA BOX · Fucsia · un box que se llena con la primera clase gratis

Segunda dirección del encargo FORJA BOX: es el **modelo B, «fucsia»**, del paquete de exportación,
que trae dos modelos con el mismo contenido y las mismas seis páginas. El modelo A es la Plantilla
hermana `forja`. La marca, ficticia, es la misma: Forja Box, un box de 900&nbsp;m² en Madrid. Este
modelo es tinta casi negra alternada con bandas de papel, un fucsia que se gasta en campos enteros
(la banda de cierre, el plan señalado, el botón) y una tipografía de palo seco en peso máximo en
mayúsculas.

## Para qué sirve

Un centro que **llena un horario de clases en grupo pagado por cuota mensual** y convierte con la
**clase de prueba gratuita**: box de CrossFit, entrenamiento funcional, halterofilia, calistenia,
escuela de artes marciales o de escalada con grupos reducidos y coaches con nombre. El sitio lleva al
visitante de «así se entrena aquí» (disciplinas, box, coaches) a «esto cuesta y la primera no la
pago», con los tres planes y el cierre fucsia repetido al pie de cada página.

**Objetivo `clase-de-prueba`, confirmado contra `recomendador.md`.** Señales en el lienzo:
«Primera clase gratis» y «Reserva tu prueba gratis» como llamada principal, cuota «/mes», «Sin
matrícula. Sin permanencia», cuatro coaches con nombre y certificación, una sala (el box) a la que se
acude y «64 clases por semana». Fronteras: frente a `plan-fases`, los bloques de ocho semanas y la
Iniciación de cuatro son programación dentro de una cuota que no termina; frente a `pedir-cita`, la
fisioterapia es un extra del plan Ilimitado y no la conversión; frente a `suscripcion`, se va a una
sala, no llega nada a casa.

## Para qué NO sirve

- **Un gimnasio de sala de musculación con cuota de acceso libre**, donde no hay clases que llenar
  ni coach por grupo: la clase de prueba no es su conversión.
- **Un programa que termina** (reto de 12 semanas, preparación de una oposición física): eso es
  `plan-fases`.
- **Fisioterapia o readaptación de lesiones** con diagnóstico: eso es `pedir-cita`. Forja Box avisa en
  el aviso legal de que el entrenamiento no es tratamiento sanitario.
- **Un estudio que vende por calma y respiración** (yoga, pilates de suelo): la paleta de tinta y
  fucsia y el titular a 124px contradicen lo que ese cliente enseña; `amalia` o `amalia-salvia`.

## ADN — lo que no se toca al adaptarla

- **Tinta y papel alternados, sección a sección.** Primera pantalla, coaches, recuperación y pie en
  `#080808`; marquesina, cifras, disciplinas, box y planes en papel `#F4F4F1`. Pasar todo a claro o
  todo a oscuro es otra Plantilla.
- **El fucsia se gasta en campos, no en detalles.** La banda de cierre «Ven y prueba» entera, el plan
  señalado entero, los botones principales y la palabra final de los titulares. Sobre claro, la
  palabra va en el fucsia de texto re-medido.
- **Titulares en mayúsculas en palo seco de peso máximo, apretados.** Archivo Black a -0,035em, con
  una palabra o una línea en fucsia. Rótulos en Barlow Condensed espaciado.
- **La marquesina de disciplinas en una fila y en bucle.** Decisión del usuario: no se convierte en
  una banda estática ni se parte en varias líneas (ver techo nativo).
- **El cierre fucsia al pie de cada página de contenido**, con la clase de prueba primero.
- **Fotografía de color, flash duro y negros profundos**, siempre bajo un velo de tinta cuando lleva
  texto encima.

## Qué se cambia para un cliente

| Se cambia | Se conserva |
|---|---|
| Disciplinas, duraciones y niveles | Ocho tarjetas o menos, en rejilla de cuatro, con número, nombre, texto y meta |
| Los tres planes y sus precios | Tres planes, el central en fucsia; sin matrícula ni permanencia dichos arriba |
| Coaches, retratos y certificaciones | Retrato 3:4 sobre negro, nombre, rol en fucsia, certificación |
| Las 11 fotografías | Flash y negros profundos; el velo re-medido con `scrim.php` sobre la foto nueva |
| El fucsia (re-medido) | Un solo acento, gastado en campos; su versión de texto sobre claro medida aparte |
| El par tipográfico | Grotesca de peso máximo en mayúsculas + grotesca de texto + condensada de rótulos |

## Paleta medida

Medida con `color.php`, no copiada del lienzo. El amarillo `#FFD11A` venía declarado como propiedad
del lienzo pero no se pinta en ninguna lámina (1,32:1 sobre papel: nunca serviría para texto sobre
claro); no es un token de la Plantilla.

| Papel | Color | Sobre | Contraste |
|---|---|---|---|
| Suelo claro · papel | `#F4F4F1` | — | — |
| Suelo claro · tarjeta y campo | `#FFFFFF` | — | — |
| Tinta (suelo oscuro y texto sobre claro) | `#080808` | papel / blanco | 18,17:1 / 20,03:1 |
| Texto secundario | `#545453` (`rgba(8,8,8,.68)` del lienzo) | papel / blanco | 6,88:1 / 7,58:1 |
| Rótulos y meta, **re-medido** | `#666665` (antes `rgba(8,8,8,.55)`, 4,37:1) | papel / blanco | 5,22:1 / 5,75:1 |
| Fucsia de texto sobre claro, **re-medido** | `#D71A5A` (antes `#FF1F6B`, 3,37:1 / 3,71:1) | papel / blanco | 4,55:1 / 5,02:1 |
| Borde de campo, **re-medido** (interfaz, 3:1) | `#8A8A88` (antes `rgba(8,8,8,.22)`, 1,65:1) | papel / blanco | 3,14:1 / 3,46:1 |
| Papel sobre tinta | `#F4F4F1` | `#080808` | 18,17:1 |
| Tinta sobre fucsia (botón, cierre, plan señalado) | `#080808` | `#FF1F6B` | 5,40:1 |
| Rótulo y periodo del plan sobre fucsia, **re-medido** | `#080808` (antes `rgba(8,8,8,.7)` y `opacity:.7`, 3,89:1) | `#FF1F6B` | 5,40:1 |
| Fucsia sobre tinta (antetítulos, roles, «BOX») | `#FF1F6B` | `#080808` | 5,40:1 |
| Sobre tinta · entradilla | `#C0C0BE` (papel a .78) | `#080808` | 10,99:1 |
| Sobre tinta · texto y menú | `#B2B2B0` (papel a .72; reúne .65, .7 y .72) | `#080808` | 9,43:1 |
| Sobre tinta · rótulo y pie legal | `#8A8A88` (papel a .55; reúne .5, .55 y .6) | `#080808` | 5,79:1 |
| Texto del mapa | `#545453` / `#080808` | marco `#E6E6E1` | 6,05:1 / 15,99:1 |
| Velo de foto, **re-medido** | `rgba(8,8,8,.8)` plano (antes radial `.55 → .88` y lineal `.7 → .88`) | peor píxel de la foto entera | hero: papel 10,44:1 · `#C0C0BE` 6,31:1 · fucsia 3,10:1 — clase: 10,62:1 · 6,42:1 · 3,15:1 |

`color.php --maqueta` re-mide los doce pares del `:root` de `maqueta/index.html` y sale en `0`. Los
tokens que sólo se pintan sobre tinta (`--c-sobre-tinta-*`) no llevan `text`, `accent`, `bg` ni
`border` en el nombre a propósito, igual que en `amalia-salvia`: el barrido automático los mediría
contra el papel, donde nunca se pintan; se miden aquí con `--contraste`. Tinta y fucsia como
superficie van como `--c-surface-inverse`/`--c-on-inverse` y `--c-fucsia`/`--c-on-fucsia`, que
`color.php` mide sólo contra su superficie.

**Fucsia sobre foto: barra de texto grande, dicha en voz alta.** Con el velo del lienzo, en el
centro del radial, el fucsia medía 1,45:1 sobre la foto real. Para llegar a 4,5:1 el velo tendría que
pasar de `.9` y la foto desaparecería. Se aplica un velo plano de `.8` medido sobre **la foto entera**
(así vale para cualquier recorte a cualquier ancho): el papel y la entradilla pasan de 4,5:1 con
holgura, y el fucsia, que sólo se usa ahí en «Porque importa.» y «solo» —Archivo Black de 42px o más,
texto grande según WCAG 1.4.3—, mide 3,10:1 y 3,15:1 contra la barra de 3:1. Los dos antetítulos que
iban en fucsia sobre la foto (13px) pasan a papel, con el cuadro del hero en fucsia. Si el
coordinador exige 4,5:1 también al titular, la alternativa medida es velo `.62` con todo el texto
sobre foto en papel (5,24:1 y 5,42:1) y el fucsia fuera de la foto.

## El margen medido no es el 7,5 %

El lienzo pone `padding: … 8vw` en cada banda y topa el contenido con `max-width:1440px`: **8 % por
lado**, medido en las seis láminas, y la medida topada a partir de 1714px de ventana. La maqueta lo
escribe una vez como `--page-margin: max(8vw, calc((100% - 1440px) / 2))`, así que viaja como
fracción entre los dos puntos de ruptura (34px a 430, 61px a 768, 102px a 1280). Es medio punto más
que el estándar de la casa: un box que se lee a golpes de titular pide un poco más de aire a los
lados que un catálogo, y las rejillas de cuatro siguen cabiendo a 1025px.

## Enfoque, eje por eje

Leído en las láminas (`canvas/*.dc.html`), no en el nombre del modelo:

| Eje | Posición | Evidencia en el lienzo |
|---|---|---|
| Escala | monumental | H1 `clamp(42px, 7.4vw, 124px)` y «Nadie entrena solo» `clamp(44px, 8vw, 132px)` en Archivo Black mayúsculas sobre cuerpo de 17–19px: de 6,5 a 7 veces |
| Densidad | generosa | Bandas con `8vw`–`10vw` de relleno (115–144px a 1440) y la primera pantalla a `92vh`; dentro, huecos de 20px en las rejillas |
| Fondo | tinta neutra | `body { background:#080808 }`, cabecera, primera pantalla, coaches, recuperación y pie en tinta; papel `#F4F4F1` en bandas alternas |
| Elevación | filete | Tarjetas blancas con borde de 1px en reposo; la sombra `0 12px 32px rgba(8,8,8,.09)` sólo aparece al pasar el ratón |
| Composición | rejilla estricta | Disciplinas 4×2, box 4, coaches 4, planes 3, pie 4; sólo la primera pantalla, la regla y el cierre van centrados |
| Acento | campo teñido | La banda «Ven y prueba» y el plan Ilimitado son campos fucsia enteros; además botones, palabra final de titular, antetítulos y roles |
| Chasis | tarjeta con relleno | Disciplinas y planes son tarjetas con relleno de 26–34px y borde; box y coaches, foto y pie desnudos; recuperación dividida por filetes |
| Ornamento | filete | Filete de 3px sobre cada cifra (el primero en fucsia), el cuadro de 8px del antetítulo y los puntos de la marquesina |

**Ningún Enfoque pasa de dos ejes, y `directo` es el único que el lienzo no excluye.** Recuento
contra la columna de cada Plantilla en `enfoques.md` (o la posición donde la ficha no fija el eje):
`directo` 2 (escala; fondo por posición, o composición por la columna de `escuadra`), `editorial` 2
(densidad, ornamento), `materia` 2 (elevación, composición), `tecnologico` 2 (densidad, composición),
`lujo-oscuro` 2 (escala, densidad), `vitrina` 2 (fondo, composición), `institucional` 1 (chasis),
`brutalista` 1 (escala). Con el empate se leen las exclusiones que cada Enfoque escribe de sí mismo:

- `vitrina`: «si las referencias llevan la foto a sangre de borde a borde, no es este enfoque» — el
  hero y la regla son fotos a sangre.
- `materia`: «si piden fondo oscuro … no es este enfoque».
- `tecnologico`: «si abren con una frase de marca y una foto de estilo de vida, no es este enfoque», y
  su dirección de imagen dice «sin gimnasio».
- `lujo-oscuro`: «ningún grito comercial»; «si piden … descuentos visibles, no es este» — «Prueba
  gratis» está en la cabecera, en el hero y en cada cierre.
- `editorial`: «si piden … botones de color vivo, no es este enfoque».
- `institucional`: «si piden un titular enorme … no es este enfoque».
- `brutalista` no se excluye del todo (la sombra al pasar el ratón es suave, que su texto rechaza),
  pero sólo coincide en escala: ni sombra dura en reposo, ni fondo saturado de base, ni etiquetas
  policromas, y aprieta el espaciado que su par tipográfico pide no apretar.

Queda `directo`, que además se lee en su descripción: contraste alto, llamada a la acción fuerte,
ningún adorno, las cifras (420+, 64, 900, 9) y los precios en la portada. Lo que no comparte con
`escuadra` —fondo oscuro y no claro, densidad generosa y no compacta, acento en campo y en letra— es
justo lo que separa esta Plantilla de un catálogo. **El ajuste es débil (2 de 8)** y se escribe
`directo` porque es el único que sobrevive a las exclusiones, no porque lo sostenga una mayoría.

**Conflicto a vigilar, no resuelto aquí.** Hoy ninguna Plantilla de la biblioteca es corporate +
`clase-de-prueba` + `directo` (`amalia` y `amalia-salvia` son `editorial`). La Plantilla hermana
`forja` comparte tipo y Objetivo y se está decidiendo ahora; si también cae en `directo`, las dos
direcciones de FORJA BOX quedan como `amalia` y `amalia-salvia`: alternativa entre sí para un mismo
cliente, y lo decide el coordinador.

## Mapeo nativo

**Propuesto, no verificado todavía contra el vocabulario nativo introspeccionado.** Elementor Pro,
sin un solo widget HTML y con **una** regla de CSS a medida, la de la marquesina. La primera columna
son los ids de sección de `maqueta/index.html`.

| Sección | Elementor (nativo) | Nota |
|---|---|---|
| Cabecera (`#cabecera`) | Plantilla de cabecera del Theme Builder: contenedor fila con fondo `#080808` y borde inferior + Encabezado con enlace (marca, «BOX» coloreado desde el editor) + **Menú de navegación** horizontal + Botón «Prueba gratis». Efectos de movimiento › Fijo arriba | El Menú de navegación despliega su propio botón por debajo de 1024: un único conmutador. Cabecera opaca: el `backdrop-filter` y la transparencia al 86 % del lienzo no tienen control nativo y dejaban leer el texto de debajo. Ningún `overflow` en los padres: rompe el fijo |
| `#inicio-hero` | Contenedor de alto mínimo `92vh`, contenido centrado, **imagen de fondo** (`forja-fucsia-hero`, cubrir) + **Superposición de fondo** clásica `rgba(8,8,8,.8)`. Dentro: Lista de iconos de un elemento (cuadro fucsia de 8px + antetítulo) + **Editor de texto** con formato H1 (la última línea con el color de texto del propio editor) + Editor de texto + dos Botones | El velo radial del lienzo pasa a plano porque sólo así se mide el peor píxel (ver Paleta). La maqueta pinta la foto como `<img>`: es el mismo adjunto |
| `#inicio-marquesina` | Contenedor con Desbordamiento › Oculto + contenedor pista en fila sin envoltura con dos contenedores de grupo; cada grupo, doce Encabezados en línea (seis nombres en Archivo Black y seis puntos en `#D71A5A`). El segundo grupo lleva el atributo `aria-hidden="true"` (Avanzado › Atributos) | **La única regla de CSS a medida de la Plantilla**, en el CSS personalizado del contenedor pista: `width:max-content`, la animación `translateX(0 → -50%)` de 28&nbsp;s lineal e infinita y `animation:none` bajo `prefers-reduced-motion:reduce`. Elementor no tiene un widget de marquesina dentro de la página: el «Ticker» de Pro es una barra flotante, no una sección. Decisión del usuario: la marquesina se queda en una fila y en bucle, como en el lienzo |
| `#inicio-cifras` | Contenedor rejilla 4 / 2 / 2: cuatro contenedores con borde superior de 3px (el primero `#FF1F6B`) + widget **Contador** (número, sufijo «+» en el primero, título como rótulo) | El Contador anima de 0 al número, igual que el lienzo |
| `#inicio-disciplinas`, `#disciplinas-rejilla` | Contenedor fila con envoltura (Editor de texto H2 con «entrenar» en `#D71A5A` + Editor de texto) + contenedor rejilla 4 / 2 / 1 de contenedores: fondo blanco, borde 1px, alto mínimo 280px con distribución entre extremos, y en su estado Hover color de borde `#FF1F6B` + sombra `0 12px 32px rgba(8,8,8,.09)`; dentro Encabezado (número) + Encabezado H3 + Editor de texto + Encabezado (meta) | Tamaño del H3 en escritorio con unidad personalizada `min(21px, calc(2.6vw - 8px))`: a 1025px la tarjeta deja 150px y HALTEROFILIA mide 7,795em; 21px en tableta y móvil. En tableta y móvil, alto mínimo 0 y contenido arriba. Los números van en Barlow Condensed 600 con cifras tabulares: la monoespaciada de sistema del lienzo no es una fuente embebible |
| `#inicio-regla` | Contenedor de alto mínimo `clamp(520px, 60vw, 740px)` (unidad personalizada), imagen de fondo `forja-fucsia-clase` + Superposición `rgba(8,8,8,.8)`: Encabezado (antetítulo en papel) + Editor de texto H2 con «solo» en `#FF1F6B` + Editor de texto | |
| `#inicio-box`, `#el-box-instalaciones` | Encabezado H2 (sólo en inicio) + contenedor rejilla 4 / 2 / 2: **Imagen** (alto fijo con Ajuste de objeto › Cubrir, proporción 4:5) + Encabezado (título) + Editor de texto (detalle) | Nunca más columnas que instalaciones |
| `#inicio-coaches`, `#coaches-equipo` | Contenedor sobre tinta con borde superior: fila de cabecera (H2 + Editor de texto) + rejilla 4 / 2 / 2 de contenedores: Imagen 3:4 + Encabezado H3 + Encabezado (rol en `#FF1F6B`) + Editor de texto | |
| `#inicio-planes`, `#planes-tarifas` | Encabezado H2 + Editor de texto + contenedor rejilla 3 / 1 / 1 (ancho máximo 40rem centrado en tableta): tres contenedores (blanco con borde, fucsia, tinta) con Encabezado (rótulo) + Encabezado H3 + contenedor fila con envoltura (Encabezado precio + Encabezado periodo) + **Lista de iconos** (cuadrado) + Botón a todo el ancho | Contenedores y no «Tabla de precios»: el widget impone su cabecera y no deja el plan entero en fucsia con botón de tinta |
| `#inicio-recuperacion`, `#el-box-recuperacion`, `#disciplinas-recuperacion` | Contenedor rejilla 2 / 1 / 1 sobre tinta: columna (Encabezado antetítulo + Encabezado H2 + Editor de texto + tres contenedores fila con borde inferior, Encabezado + Editor de texto) + Imagen 1:1 | Plantilla guardada, la misma en las tres páginas. Por debajo de 767 cada fila apila título y detalle |
| `#inicio-prueba`, `#el-box-prueba`, `#disciplinas-prueba`, `#coaches-prueba`, `#planes-prueba` | Contenedor columna centrado con fondo `#FF1F6B`: Encabezado + Encabezado H2 + Editor de texto + Botón papel «Reservar prueba gratis» (a `#contacto-formulario`) + Botón contorno «Hablar con un coach» (a Contacto) | Plantilla guardada, la misma en las cinco páginas |
| `#el-box-cabecera`, `#disciplinas-cabecera`, `#coaches-cabecera`, `#planes-cabecera`, `#contacto-cabecera` y las de gracias y legales | Contenedor columna centrado sobre `#080808`: Encabezado (antetítulo `#FF1F6B`) + Encabezado H1 + Editor de texto | El rayado bajo velo del lienzo es un marcador sin foto asignada: se deriva como tinta plana |
| `#contacto-formulario` | Contenedor rejilla 2 / 1 / 1: **Formulario** (Nombre, Correo, Teléfono, Seleccionar «Qué te interesa» con «Clase de prueba gratis» primero, Mensaje, Aceptación obligatoria que enlaza a Privacidad; acción Redirigir a Gracias) + columna con tres contenedores de dato (Encabezado + Editor de texto con `tel:` y `mailto:`) + **Mapa de Google** | En la maqueta el mapa es un bloque estático con la dirección: ni iframe ni recurso remoto. En el build se carga tras el consentimiento (ver Cookies) |
| `#gracias-pasos` | Contenedor rejilla 3 / 3 / 1 de pasos (Encabezado número + Encabezado H2 + Editor de texto, borde superior de 3px) + dos Botones | Siguiente paso: las disciplinas, no un callejón |
| `#aviso-legal-texto`, `#privacidad-texto`, `#cookies-texto` | Contenedor rejilla: índice con Efectos de movimiento › Fijo (sólo escritorio) + columna de apartados (Encabezado número + Encabezado H2 + Editor de texto); fichas de datos y cookies como filas de contenedor | Mismo patrón que `amalia-salvia` |
| `#error-404` | Plantilla 404 del Theme Builder: Encabezado + H1 + Editor de texto + tres Botones | |
| Pie | Plantilla de pie del Theme Builder: rejilla 4 / 2 / 1 sobre tinta (marca y dirección, horario, Menú de navegación vertical, redes como texto) + fila base con el aviso legal, la privacidad y las cookies | |
| Revelado al hacer scroll | Animación de entrada nativa (Fundido hacia arriba) por bloque | La maqueta no la dibuja: el `data-reveal` del lienzo es código del runtime |

**Techo declarado: cero widgets HTML y una regla de CSS a medida.** La regla es la marquesina de
disciplinas: su animación en bucle sobre un contenedor nativo, con la guarda de movimiento reducido,
porque Elementor no tiene marquesina dentro de la página (el «Ticker» de Pro es una barra flotante)
y el usuario decidió que no se convierta en una banda estática. `qa-review` la cuenta como la única
permitida. Si al construir apareciera otra sección que no cabe en esta tabla, se rediseña en el
lienzo; no se abre una segunda excepción sin escribirla aquí con su razón.

## Páginas

Once. Las seis láminas del lienzo —**inicio**; **disciplinas** es el «listado o servicios» del tipo;
**el box** y **coaches** reparten el «nosotros»; **planes** es la cuota; **contacto** lleva el
formulario de la clase de prueba— y las cinco derivadas: **gracias**, **aviso legal**,
**privacidad**, **cookies** y **404**.

**Falta el «detalle» del tipo corporate.** `paginas-obligatorias.md` pide una página de detalle y el
lienzo no la dibuja: las tarjetas de disciplina no enlazan a nada ni dicen «ver ficha», así que no
hay enlace muerto, pero tampoco hay ficha de disciplina. No se deriva sin lámina, porque es una
página de contenido y su composición es trabajo de diseño. Si el coordinador la exige, se dibuja
primero en el lienzo (duración, nivel, coach, horario de esa clase y cierre) y se deriva después.

Las condiciones de los planes (clase de prueba, cuota, baja, reservas, fisioterapia) viven en el
aviso legal (`#aviso-legal-planes`), no inventadas en Planes. Los textos legales describen una
empresa ficticia; en un encargo se reescriben enteros con `wordpress-legal`.

## Procedencia y decisiones abiertas

**De dónde sale.** El lienzo es el modelo B del paquete de exportación de FORJA BOX
(`canvas/MANIFIESTO.md`). Las fotografías son de una sesión hecha para este encargo, tres de stock y
ocho generadas (`manifiesto-imagenes.md`). No se dispone de la URL del lienzo compartido; queda en
blanco.

**Decisiones de derivación que no están en el lienzo tal cual:**

- Todas las llamadas a la clase de prueba llevan al formulario de Contacto
  (`#contacto-formulario`, «Clase de prueba gratis» primero). En el lienzo iban a `#prueba` (la propia
  banda de cierre, que no tiene formulario) o a Planes.
- «Ver planes» del hero baja a `#inicio-planes`, como el `#precios` del lienzo; «Hablar con un coach»
  abre Contacto.
- La cabecera es la misma en las seis páginas y lleva «Inicio»: la lámina de inicio lo omitía y las
  interiores lo tenían.
- Por debajo de 1024: menú plegado, cifras, disciplinas, box y coaches a dos columnas, planes y
  recuperación a una. Por debajo de 767: disciplinas a una columna y botones apilados. Box y coaches
  se quedan a dos columnas a 430 para no apilar cuatro fotos de alto completo.
- La tarjeta de disciplina pierde el alto mínimo y el reparto entre extremos por debajo de 1024: en
  dos columnas anchas dejaba un hueco vacío entre el número y el nombre.

**Lo que salió por no tener mapeo nativo:** el `backdrop-filter` y la transparencia de la cabecera;
el rayado de las cabeceras interiores; los velos en degradado (ahora planos, re-medidos); la
monoespaciada de sistema de los números; `text-wrap:balance` y `hyphens:auto` de los titulares; el
`overflow-x:hidden` del envoltorio, que anula el fijo; el script de revelado (queda como animación de
entrada nativa); el mapa embebido (bloque estático). **Se queda, como única regla a medida, la
marquesina en bucle.**

**Lo que el lienzo no trae y el Objetivo pide.** `clase-de-prueba` promete llenar un horario semanal;
este lienzo da el horario de apertura y «64 clases por semana», pero ninguna parrilla de clases. No
se inventa en la maqueta: si se quiere, se dibuja en Planes o en Disciplinas en el lienzo primero.

**Filas que se proponen para los ficheros del coordinador:**

- `_indice.md`: `forja-fucsia` · FORJA BOX · Fucsia | `clase-de-prueba` | `directo` | corporate |
  inicio · el box · disciplinas · coaches · planes · contacto · gracias · aviso legal · privacidad ·
  cookies · 404 | sin veredicto. Y en el párrafo de márgenes: `forja-fucsia` 8&nbsp;% por lado con la
  medida topada en 1440&nbsp;px.
- `recomendador.md`, Objetivo → Plantilla: `clase-de-prueba` · `forja-fucsia` (modelo B de FORJA BOX,
  hermana de `forja`) · corporate · `directo` · en la biblioteca.
- `recomendador.md`, señales de `clase-de-prueba`: añadir «box de CrossFit o de entrenamiento
  funcional, "prueba gratis", coaches certificados, sin matrícula».
- `enfoques.md`, tabla de `directo`: una columna «En `forja-fucsia`» con las evidencias de arriba.

**Sin veredicto.** El contraste de `:root` pasa `color.php --maqueta` (0 fallos), los velos pasan
`scrim.php` sobre las dos fotos y el barrido de las once páginas a 430, 768 y 1280 sale limpio; falta
`blind-judges` (juez A contra la biblioteca, en particular contra `forja`, y juez B sobre esta
maqueta) y `visual-verification` completa. Sin las dos, esta Plantilla no se ofrece a un cliente.
