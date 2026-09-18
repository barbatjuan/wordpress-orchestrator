# Manifiesto del lienzo · noir-claro

La lámina de Claude Design de MAISON NOIR en su versión clara. Es la **autoridad del diseño**: todo cambio
empieza aquí y baja después a la maqueta, nunca al revés.

| Artboard | Página | Tamaño |
|---|---|---|
| `NoirClaro.dc.html` | tienda completa en una sola lámina: portada, y como estados de la misma lámina tienda, ficha (Nuit Absolue), cesta, pago, la Maison y contacto | 1440 × 4763 (portada) |
| `TestTipografico.dc.html` | **no es una página.** Lámina de referencia: tres pares tipográficos (Bodoni Moda 500 + Jost, Prata + Jost, Marcellus + Inter Tight) a los tamaños reales de la Plantilla sobre fondo claro. Explica por qué la versión clara usa Prata | 1440 × 991 |

`canvas.json` coloca las dos láminas y guarda su alto. **El alto está medido, no tecleado**: Chrome a
1440 × 900 con el `support.js` del paquete al lado, alto del envoltorio de la lámina tras pintar, en el
estado inicial (portada). Las demás páginas son estados del mismo componente y no tienen alto propio.
La maqueta no deriva nada de `TestTipografico.dc.html`.

## De dónde sale

Llegó como exportación de Claude Design junto con la lámina oscura («Maison Noir Tienda Claro.dc.html»,
«Test tipografico.dc.html», `support.js` y una miniatura), sin README. Se renombraron sin espacios como el
resto de la biblioteca. **Se copiaron tal cual, byte a byte**: no se ha corregido nada dentro de las
láminas; las correcciones viven en la maqueta y se listan abajo. No se dispone de la URL del lienzo
compartido. `support.js` no se copia; para renderizar se sirve desde fuera del repositorio con su
`support.js` al lado.

**Mismo contenido y estructura que la lámina de `noir`.** Comparadas línea a línea, con los colores
normalizados, las dos láminas sólo difieren en:

- **Tipografía.** Prata (redonda, un solo peso) en lugar de Bodoni Moda; ninguna itálica (la palabra
  final del titular pasa de `<em>` en cursiva a bronce en redonda); Jost a 400 en lugar de 300. El H1 del
  hero baja de `clamp(44px, 6.4vw, 92px)` a `clamp(40px, 5.4vw, 78px)` con interlínea 0,98.
- **Hero.** La lámina oscura era texto a la izquierda y frasco 4:5 a la derecha con halo radial; la clara
  es una **imagen de fondo a sangre** («2400×1400 · frasco sobre mármol») de `min(88vh, 820px)` con un
  **panel claro enmarcado** (620 px, relleno `clamp(28px, 4vw, 52px)`, filete bronce .22) que lleva todo el
  texto. El botón «Ver colección» pasa de filete a relleno bronce.
- **Franja de ventajas.** De cuatro celdas numeradas con separadores a **una banda bronce** con los cuatro
  rótulos en papel separados por rombos de 4 px.
- **Color.** Un mapa uno a uno: tinta `#14110F` → papel `#FAF6F0`, alterno `#17130F` → `#F1EAE0`, marco
  `#1B1715` → `#ECE4D8`, champán `#E8C9A0` → bronce `#6F5326` (hover `#4F3A1A`), grises
  `rgba(242,236,228,α)` → `rgba(28,22,18,α)` con α subida (.45–.62 → .72–.75). Sin sombras en ninguna.

**Recursos remotos, sólo en el lienzo.** `NoirClaro.dc.html` pide a Google Fonts Prata y Jost (300, 400,
500); `TestTipografico.dc.html` pide además Bodoni Moda, Marcellus, Libre Caslon Display e Inter Tight.
La maqueta no carga ninguna: embebe Prata 400 y Jost 300–500 desde `_fonts.php`.

**No hay artboards móviles de 390.** La maqueta diseñó 1024 y 767 al derivar, heredando los de `noir`.

## Lo que la maqueta corrige del lienzo

Todo lo que la maqueta de `noir` corrige de su lámina (huecos de foto, rejillas fijas, carril topado en
1440, `vh` a su valor a 900, botones en Jost, H1 de la ficha roto, enlaces muertos, formularios, páginas
nuevas) vale igual aquí, porque la lámina clara repite esos mismos defectos. Además:

- **Hero.** El «frasco sobre mármol» es `noir-claro-bodegon-extrait.webp` (1800 × 831, 21:9 recortado) con
  `object-fit: cover` anclado a la derecha, para que el frasco no quede bajo el panel desde 1280. La lámina no pone texto sobre la foto: el panel es
  opaco, así que no lleva velo. `min(88vh, 820px)` pasa a 792 px.
- **Franja de ventajas.** El lienzo la centra con 22 px de relleno y 40 px de hueco, lo que abría un
  segundo raíl por dentro del margen (202 px a 1440). La maqueta la ciñe al raíl con `space-between`
  (35 px de hueco a 1440, 16 px de mínimo a 1280); a 1024 y menos pasa a dos columnas sin rombos.
- **Color.** Los grises se resuelven a hex: .78 → `#4D4743`, .75 → `#544E4A`, .72 → `#5A5550`. El borde de
  campo `rgba(28,22,18,.22–.28)` (1,36–1,85:1) sube a .54 → `#827D78` (3,23–3,78:1).
- **Números del carrusel.** Heredaban la familia de los botones y se pintaban en Jost; van en Prata.

## Reglas que ya se pagaron en este lienzo

- **Una lámina de referencia no es una página.** `TestTipografico.dc.html` se guarda para que se entienda
  la elección de Prata; no se deriva ni se cuenta en las páginas de la ficha.
- **Una versión clara no es la oscura con los colores invertidos.** El hero y la franja cambian de
  composición; quien derive de aquí compara sección por sección, no sólo los tokens.
