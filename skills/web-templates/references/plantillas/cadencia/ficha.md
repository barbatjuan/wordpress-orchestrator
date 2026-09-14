---
slug: cadencia
nombre: CADENCIA
tipo: ecommerce
sector: ropa técnica para entrenar al aire libre en frío
objetivo: equipo-por-uso
enfoque: tecnologico
paginas: [portada, ficha]
fuentes: [archivo, ibm-plex-mono]
canvas_url: https://claude.ai/code/artifact/b3b0274e-6c0c-45c2-8a3e-5b8243e27032
variantes: {}
html_widgets_max: 0
css_custom_max: 0
---

# CADENCIA · se compra la sesión, no la prenda

## Para qué sirve

Una marca que vende **equipos pensados para un uso medible**: noventa minutos entre −4 y 6 °C,
cuarenta minutos de remo a 18 °C. Cada equipo lleva su duración, su rango de temperatura y su
intensidad, y quien compra elige por la sesión que va a hacer, no por la prenda suelta.

Sirve para ropa técnica, material de montaña, ciclismo o cualquier catálogo donde el uso decide la
compra mejor que la estética.

## Para qué NO sirve

Moda deportiva de estilo de vida, donde se compra por la imagen. Aquí la portada abre con un dato de
temperatura y la primera pantalla no enseña ni un precio.

## ADN — lo que no se toca al adaptarla

- **Abre con una afirmación y una medida, no con una oferta.** La columna derecha de la portada lleva
  cómo se mide una sesión, no una tarjeta de producto. Si las dos tiendas de esta tanda abren con
  nombre + precio + botón, dejan de ser distintas.
- **Tres cifras bajo el titular.** Mínima registrada, altitud y sesiones de prueba, en monoespaciada.
- **El naranja mide, no decora.** Barras de intensidad y subrayado de enlaces. Nunca color de letra.
- **La lista de sesiones es una tabla con barras.** Duración, temperatura, intensidad y precio por
  fila, alineados en columnas.
- **El nav va pegado a la marca.** No finge un centro: un nav centrado por reparto de espacio cae en
  el hueco sobrante, a 50px del eje.

## Qué se cambia para un cliente

| Se cambia | Se conserva |
|---|---|
| Deporte, sesiones y prendas | Que se venda por uso medible |
| Las ocho fotografías | Luz fría, sin neón, sin gimnasio de catálogo |
| Las cifras de prueba | Que haya cifras y sean reales |
| El par tipográfico | Grotesca de trazo firme + monoespaciada para todo dato |
| El acento (re-medido) | Que sólo mida |

## Paleta medida

| Papel | Color | Sobre | Contraste |
|---|---|---|---|
| Suelo | `#141A21` | — | — |
| Tinta tiza | `#E9EEF2` | `#141A21` | 14,99:1 |
| Texto secundario | `#8C97A3` | `#141A21` | 5,89:1 |
| Naranja, sólo interfaz | `#D9542B` | `#141A21` | 4,38:1 — pasa el 3:1 de interfaz, no el 4,5:1 de texto |
| Barra apagada | `#2E3944` / `#3C4956` | `#141A21` | decorativa: sólo delimita el total de diez |

## Mapeo nativo

**Propuesto, no verificado todavía contra el vocabulario nativo introspeccionado.**

| Sección | Elementor (nativo) | Nota |
|---|---|---|
| Cabecera | Theme Builder: contenedor flex con un grupo marca + menú a la izquierda y utilidades + `woocommerce-menu-cart` a la derecha | |
| Tres cifras | Contenedor flex de tres contenedores + dos Encabezado cada uno | |
| Barras de intensidad | Contenedor flex de diez contenedores de 9 × 14 con color de fondo | Nativo pero verboso. Alternativa: una imagen por nivel |
| Lista de sesiones | Loop Grid con Loop Item de una fila | Duración, temperatura e intensidad como campos del producto. **No verificado** qué campo nativo las guarda |
| Equipo como producto | **Producto simple** de WooCommerce con su propio SKU y precio de equipo | Un producto agrupado nativo muestra las prendas con sus precios pero **no aplica descuento de conjunto**: el 214,00 € frente a 234,00 € sólo es nativo si el equipo es un producto propio |
| Ficha: galería | `woocommerce-product-images` | |
| Ficha: compra | `woocommerce-product-price` + `woocommerce-product-add-to-cart` | |
| Tabla de tallas | Contenedores flex por fila | |
| Pie | Theme Builder | |

## Páginas

Portada y ficha de equipo. Carro, pago y cuenta nativas. Más aviso legal, privacidad, cookies,
términos y 404.

## Procedencia y decisiones abiertas

Canvas en `canvas/`. Sin maqueta.

**Tipografías.** Archivo es de la casa. IBM Plex Mono no está en `html-mockup/assets/fonts/`.

**El objetivo `equipo-por-uso` es nuevo.** No existe en ningún recomendador todavía; hay que darlo de
alta cuando exista `recomendador.md`, o reasignar esta plantilla a un objetivo que ya exista.

**Sin veredicto.** Geometría medida: 108px = 7,5%, cero raíles por dentro, cero tinta al cristal, cero
desborde. La apertura pasó por tres rondas de juez hasta dejar de parecerse a la de `escuadra`.
