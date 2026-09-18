# Páginas obligatorias

Toda plantilla de un tipo lleva el mismo juego de páginas, y todo sitio de cliente que salga de ella
también. No se preguntan: un sitio sin páginas legales no se entrega, uno sin 404 propio devuelve la
página desnuda del tema, un formulario sin página de gracias no se puede medir como conversión y una
tienda sin página de pedido recibido deja al comprador sin saber si ha pagado.

El campo `paginas:` de la ficha lista el juego completo, con el nombre que la plantilla dé a cada página
(`propiedades` en lugar de «listado», `ficha` en lugar de «detalle»). El veredicto barre todas las
páginas de esa lista a 430, 768 y 1280, también las derivadas.

## Dos reglas que ya se pagaron

1. **Todo listado que dice «ver ficha» llega a una ficha real.** El catálogo anterior acumuló nueve
   enlaces muertos: rejillas que prometían una página que no existía. En la maqueta, cada tarjeta enlaza
   a una página de detalle presente en la navegación interna; en el build, cada unidad tiene su propia
   página. Una tarjeta que no enlaza a nada se quita, no se deja decorativa. Hoy no lo comprueba ninguna
   regla de auditoría: lo revisa una persona, sobre la maqueta y sobre el sitio construido. La fila 6 de
   `qa-review/references/house-rules.md` pide los enlaces del menú, no los de las tarjetas de un listado.
2. **Las páginas legales se construyen con los datos de identidad reales del cliente**, con la skill
   `wordpress-legal`: titular, NIF, domicilio, registro, responsable de datos y los servicios que el
   sitio carga de verdad. Nunca se inventan ni se copian de otro sitio, tampoco de la plantilla: la
   página legal de una plantilla sólo aporta estructura y aspecto, y en el sitio del cliente se escribe
   entera. En el sitio construido, la fila 20 de `qa-review/references/house-rules.md` comprueba que
   existen, que se enlazan desde todos los pies y que la política de cookies nombra lo que se carga.

## Artboard o derivada

- **Una página de contenido necesita su artboard en el lienzo**, de escritorio y móvil a 390: su
  composición es trabajo de diseño y decide cómo se ve la plantilla.
- **Una página de sistema se deriva directamente en la maqueta** a partir del sistema de la plantilla:
  cabecera, pie, tipografía, colores, estilo de formularios y tablas. No tiene composición propia que
  decidir. Son derivables las legales, la 404, gracias, carro, pago, pedido recibido y mi cuenta.
- Una plantilla puede dibujar una página derivable si quiere diseñarla (una 404 con carácter, un carro
  particular); derivarla es el mínimo, no el techo.

## Corporate · 10 páginas

inicio · listado o servicios · detalle · nosotros · contacto · gracias · aviso legal · privacidad ·
cookies · 404

| Página | Para qué sirve | Lienzo | Nota |
|---|---|---|---|
| inicio | Dice qué hace el negocio y lleva al visitante hacia el objetivo | Artboard | |
| listado o servicios | Índice de lo que el negocio publica: propiedades, unidades, áreas de práctica, tratamientos o la carta | Artboard | En `reservar-mesa` el listado es la carta |
| detalle | La página de una unidad: un inmueble, un coche, un servicio, un tratamiento | Artboard | Aquí aterriza todo «ver ficha». En el build hay una por unidad |
| nosotros | Quién está detrás y por qué fiarse | Artboard | |
| contacto | Lo que el visitante va a hacer: escribir, llamar o ir | Artboard | El formulario se construye con `wordpress-forms`, que prueba que el mensaje llega |
| gracias | Confirma el envío del formulario y permite medirlo como conversión | Derivada | Sin enlaces que devuelvan a un callejón: ofrece el siguiente paso |
| aviso legal | Identifica al titular del sitio | Derivada | `wordpress-legal` |
| privacidad | Explica qué datos se tratan, para qué y con qué derechos | Derivada | `wordpress-legal`. La casilla de consentimiento del formulario enlaza aquí |
| cookies | Lista lo que el sitio carga de verdad y cómo se acepta o rechaza | Derivada | `wordpress-legal` |
| 404 | Devuelve al visitante perdido a la navegación | Derivada | Nunca la página desnuda del tema |

Corporate no lleva página de términos: un sitio que no vende ni contrata en línea no tiene condiciones
que publicar. Donde una ficha todavía nombre «términos» en corporate, manda esta lista.

## Ecommerce · 14 páginas

portada · categoría · ficha de producto · carro · pago · pedido recibido · mi cuenta · la marca ·
contacto · condiciones de venta y envíos · aviso legal · privacidad · cookies · 404

**El mapeo a WooCommerce es propuesto.** Los ids de widget son los que ya usa `skills/woocommerce/` y se
comprobarán contra el vocabulario introspeccionado de una instalación real (`vocabulario-nativo.md`,
todavía por generar) antes de construir. En todas las páginas, la cabecera lleva
`woocommerce-menu-cart`.

| Página | Para qué sirve | Lienzo | WooCommerce nativo (propuesto) |
|---|---|---|---|
| portada | Presenta la tienda y lleva a comprar | Artboard | Página de Elementor; productos reales con `woocommerce-products`, cada uno enlazado a su ficha |
| categoría | Lista los productos de una categoría para elegir y comparar | Artboard | Plantilla de archivo de producto del Theme Builder con `woocommerce-products` y `woocommerce-breadcrumb` |
| ficha de producto | Resuelve la duda que bloquea la compra y añade al carro | Artboard | Plantilla de producto único del Theme Builder: `woocommerce-breadcrumb`, `woocommerce-product-images`, `woocommerce-product-title`, `woocommerce-product-price`, `woocommerce-product-add-to-cart`, `woocommerce-product-data-tabs`, `woocommerce-product-meta` |
| carro | Revisar y cambiar lo que se va a comprar | Derivada | Página Carro de WooCommerce con `woocommerce-cart` |
| pago | Datos, envío y pago, con las condiciones de venta enlazadas | Derivada | Página Finalizar compra de WooCommerce con `woocommerce-checkout-page` |
| pedido recibido | Confirma el pedido, su número y qué pasa ahora | Derivada | La pinta WooCommerce dentro de la página de pago al terminar el pedido; sin widget propio en esta lista, vestida con los ajustes globales. A verificar |
| mi cuenta | Pedidos, direcciones y datos del cliente | Derivada | Página Mi cuenta de WooCommerce con `woocommerce-my-account` |
| la marca | Quién hace lo que se vende y por qué fiarse | Artboard | Página de Elementor, sin widget de WooCommerce |
| contacto | Escribir o llamar antes o después de comprar | Artboard | Página de Elementor con el formulario de `wordpress-forms` |
| condiciones de venta y envíos | Precios, plazos, gastos de envío, devoluciones y desistimiento | Derivada | Página de Elementor redactada con `wordpress-legal`; es la página de términos que WooCommerce enlaza en el pago |
| aviso legal | Identifica al titular de la tienda | Derivada | Página de Elementor; `wordpress-legal` |
| privacidad | Qué datos se tratan, también los del pedido | Derivada | Página de Elementor; `wordpress-legal` |
| cookies | Lo que la tienda carga de verdad, pasarela de pago incluida | Derivada | Página de Elementor; `wordpress-legal` |
| 404 | Devuelve al comprador perdido al catálogo | Derivada | Plantilla 404 del Theme Builder; sin widget de WooCommerce |

En ecommerce la conversión que se mide es el pedido recibido, y por eso el juego no lleva página de
gracias: el formulario de contacto confirma en la propia página. Si el cliente quiere medir el contacto
como conversión, se añade una gracias derivada igual que la de corporate.
