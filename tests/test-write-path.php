<?php
/**
 * Behavioural assertions for the DESTRUCTIVE write path: what the save functions say when the
 * write did NOT do what the caller asked for.
 *
 * Run:  php tests/test-write-path.php     (exit 0 = green)
 *
 * Why a third test file. `test-container-hygiene.php` stubs two read-only WordPress functions and
 * audits trees in memory; nothing there ever reaches `es_save_page()`. Proving what a REJECTED
 * write reports needs a WordPress that can be told to fail on demand, which is a different fixture
 * altogether, so it lives here rather than being bolted onto a suite about container nesting.
 *
 * Three findings from `docs/auditoria-2026-08-11.md` are under test, and all three are the same
 * disease this branch exists to remove — a run reporting success over work it never did:
 *
 *   14. The failure branch returned 0 and said NOTHING. A page WordPress refused to create left no
 *       trace on either channel: the build script carried on to the next page, the run ended with a
 *       clean audit verdict (the audit only ever sees the tree it was HANDED, never the write), and
 *       the missing page was discovered by a human loading the site.
 *   14b. Worse on the update path: `wp_update_post()`'s return value was discarded outright, so a
 *       post WordPress refused to touch still had its `_elementor_data` overwritten and still
 *       reported `updated`. The one branch that could not fail was the one being checked.
 *   15. `wp_insert_post()` does not promise you the slug you asked for. When one is taken —— by an
 *       attachment, by a post, by a reserved term —— WordPress silently appends a suffix, so asking
 *       for `contacto` yields a published page at `contacto-2` while `$action` says `created`. The
 *       page the caller believes it just built is somebody else's.
 *
 * The suite runs with ES_AUDIT_SILENT defined on purpose: it mutes the per-page container report,
 * so anything left on stdout is a WARNING, and a warning that shows up here is one the routine
 * silence switch could not suppress.
 */
$GLOBALS['es_suite'] = 'writepath';
require_once __DIR__ . '/lib/fake-wp.php';

$els = array( es_split( array( es_h( 'a' ), es_h( 'b' ) ) ) );

echo "=== el camino de escritura ===\n";

/* ---------------------------------------------------------------------------
 * 14. Crear una pagina que WordPress rechaza.
 * ------------------------------------------------------------------------- */
echo "--- una pagina que WordPress se niega a crear deja de irse en silencio ---\n";

wp_fake_reset();
$GLOBALS['wp']['insert_ret'] = 0;
approve( 'contacto' );
$action                      = null;
$r                           = grab(
	function () use ( $els, &$action ) {
		return es_save_page( 'contacto', 'Contacto', $els, 'elementor_header_footer', $action );
	}
);
ok( 0 === $r['ret'], 'devuelve 0 cuando la insercion falla' );
ok( 'failed' === $action, "y \$action dice 'failed'" );
ok( '' !== $r['out'], 'y NO se calla: ES_AUDIT_SILENT silencia el reporte, nunca un fallo de escritura' );
ok( has( $r['out'], 'AVISO' ), 'lo dice como aviso' );
ok( has( $r['out'], 'contacto' ), 'nombrando el slug, que es lo unico con lo que el humano puede buscar' );
ok( ! any_layout_written(), 'y no escribio ningun layout' );

wp_fake_reset();
$GLOBALS['wp']['insert_ret'] = new WP_Error( 'invalid_page_template', 'plantilla desconocida' );
approve( 'servicios' );
$action                      = null;
$mark                        = log_mark();
$r                           = grab(
	function () use ( $els, &$action ) {
		return es_save_page( 'servicios', 'Servicios', $els, 'elementor_header_footer', $action );
	}
);
ok( 0 === $r['ret'], 'un WP_Error tambien devuelve 0' );
ok( 'failed' === $action, "y \$action tambien dice 'failed'" );
ok( has( $r['out'], 'servicios' ), 'el aviso nombra el slug' );
/* Sin esto el aviso dice "fallo" y el humano se queda sin saber POR QUE fallo, que es lo unico
   que WordPress si le habia contado a alguien. */
ok( has( $r['out'], 'plantilla desconocida' ), 'y arrastra el motivo que dio WordPress, no solo que fallo' );
/* stdout se pierde en cuanto el operador scrollea. El log durable es el unico que sigue ahi cuando
   alguien pregunta por que falta una pagina tres dias despues. */
ok( has( log_since( $mark ), 'servicios' ), 'y queda tambien en el log durable, no solo en pantalla' );

/* ---------------------------------------------------------------------------
 * 14b. Actualizar una pagina que WordPress rechaza.
 * ------------------------------------------------------------------------- */
echo "--- una actualizacion rechazada deja de pisar el diseño igual ---\n";

wp_fake_reset();
wp_fake_page( 'inicio' );
$GLOBALS['wp']['update_ret'] = new WP_Error( 'db_update_error', 'no se pudo actualizar la fila' );
approve( 'inicio' );
$action                      = null;
$r                           = grab(
	function () use ( $els, &$action ) {
		return es_save_page( 'inicio', 'Inicio', $els, 'elementor_header_footer', $action );
	}
);
/* El retorno de wp_update_post() se descartaba entero, asi que esta era la unica rama que no podia
   fallar: WordPress se negaba a tocar el post y el layout se sobrescribia igual, reportando
   'updated'. Falla CERRADO: si no se pudo actualizar la fila, no hay nada que autorice reescribir
   su diseño. */
ok( 0 === $r['ret'], 'una actualizacion rechazada devuelve 0, no el id' );
ok( 'failed' === $action, "y \$action dice 'failed', no 'updated'" );
ok( has( $r['out'], 'inicio' ), 'el aviso nombra el slug' );
ok( has( $r['out'], 'no se pudo actualizar la fila' ), 'y el motivo de WordPress' );
ok( ! any_layout_written(), 'y sobre todo: NO sobrescribio el layout de una pagina que no pudo actualizar' );

/* ---------------------------------------------------------------------------
 * 15. Colision de slug.
 * ------------------------------------------------------------------------- */
echo "--- una colision de slug deja de reportarse como si nada ---\n";

wp_fake_reset();
$GLOBALS['wp']['rename_to'] = 'contacto-2';
approve( 'contacto' );
$action                     = null;
$r                          = grab(
	function () use ( $els, &$action ) {
		return es_save_page( 'contacto', 'Contacto', $els, 'elementor_header_footer', $action );
	}
);
ok( $r['ret'] > 0, 'la pagina SI se creo, asi que devuelve su id' );
ok( has( $r['out'], 'contacto-2' ), 'pero avisa, nombrando el slug que WordPress asigno de verdad' );
ok( has( $r['out'], '"contacto"' ), 'y el que se habia pedido, porque la diferencia ES el hallazgo' );
ok( 'created-renamed' === $action, "y \$action deja de decir 'created' a secas: la pagina pedida no existe" );

wp_fake_reset();
approve( 'contacto' );
$action = null;
$r      = grab(
	function () use ( $els, &$action ) {
		return es_save_page( 'contacto', 'Contacto', $els, 'elementor_header_footer', $action );
	}
);
ok( 'created' === $action, 'sin colision sigue diciendo created' );
ok( '' === $r['out'], 'y no avisa de nada: el aviso tiene que doler solo cuando hay algo que mirar' );

wp_fake_reset();
wp_fake_page( 'inicio', 'draft' );
approve( 'inicio' );
$action = null;
$r      = grab(
	function () use ( $els, &$action ) {
		return es_save_page( 'inicio', 'Inicio', $els, 'elementor_header_footer', $action );
	}
);
ok( 'updated' === $action, 'y una actualizacion normal sigue diciendo updated' );
ok( '' === $r['out'], 'sin avisos' );
ok( any_layout_written(), 'habiendo escrito el layout de verdad' );

/* ---------------------------------------------------------------------------
 * El gemelo: es_save_theme_part() tiene la misma forma, y falla igual.
 * ------------------------------------------------------------------------- */
echo "--- el gemelo de theme parts falla del mismo modo, asi que se arregla igual ---\n";

/* Un header que no se creo es la peor de todas: no falta UNA pagina, falta el encabezado de TODAS.
   Este asset se sube al sitio y se ejecuta alli, asi que su rama de fallo es la que menos ojos
   tiene encima. */
wp_fake_reset();
$GLOBALS['wp']['insert_ret'] = 0;
$action                      = null;
$r                           = grab(
	function () use ( $els, &$action ) {
		return es_save_theme_part( 'site-header', 'Header', 'header', $els, array( 'include/general' ), $action );
	}
);
ok( 0 === $r['ret'], 'un theme part que no se pudo crear devuelve 0' );
ok( 'failed' === $action, "y \$action dice 'failed'" );
ok( has( $r['out'], 'site-header' ), 'avisando y nombrando el slug' );
ok( ! any_layout_written(), 'sin escribir layout' );

wp_fake_reset();
$GLOBALS['wp']['rename_to'] = 'site-header-2';
$action                     = null;
$r                          = grab(
	function () use ( $els, &$action ) {
		return es_save_theme_part( 'site-header', 'Header', 'header', $els, array( 'include/general' ), $action );
	}
);
ok( has( $r['out'], 'site-header-2' ), 'y una colision de slug tambien avisa aqui' );
ok( 'created-renamed' === $action, "con el mismo \$action que es_save_page()" );

/* Esta rama la destapo una mutacion, no el plan: revertir el gemelo a comprobar $id en vez del
   retorno de wp_update_post() SOBREVIVIA a la suite entera, porque el fake devolvia array() a toda
   busqueda y la rama de actualizacion era inalcanzable. Un arreglo que ningun test puede distinguir
   de su propio bug es exactamente lo que esta rama existe para eliminar. */
wp_fake_reset();
wp_fake_page( 'site-header' );
$GLOBALS['wp']['update_ret'] = new WP_Error( 'db_update_error', 'no se pudo actualizar la fila' );
$action                      = null;
$r                           = grab(
	function () use ( $els, &$action ) {
		return es_save_theme_part( 'site-header', 'Header', 'header', $els, array( 'include/general' ), $action );
	}
);
ok( 0 === $r['ret'], 'un theme part que no se pudo actualizar devuelve 0' );
ok( 'failed' === $action, "y \$action dice 'failed', no 'updated'" );
ok( has( $r['out'], 'no se pudo actualizar la fila' ), 'con el motivo de WordPress' );
ok( ! any_layout_written(), 'y NO piso el header que ya estaba puesto' );

wp_fake_reset();
wp_fake_page( 'site-header' );
$action = null;
$r      = grab(
	function () use ( $els, &$action ) {
		return es_save_theme_part( 'site-header', 'Header', 'header', $els, array( 'include/general' ), $action );
	}
);
ok( 'updated' === $action, 'y una actualizacion normal de theme part sigue diciendo updated' );
ok( any_layout_written(), 'habiendo escrito el layout de verdad' );

/* ---------------------------------------------------------------------------
 * 2 (BLOCKER). La portada.
 * ------------------------------------------------------------------------- */
echo "--- la portada se establece, y se comprueba que quedo establecida ---\n";

/* Nada en el arbol tocaba `show_on_front` ni `page_on_front` — cero coincidencias en todo el repo.
   Una home construida, guardada y auditada limpia seguia sin ser la portada del sitio: WordPress
   mostraba el blog en `/`, el build reportaba exito, y quien lo descubria era el cliente. */

wp_fake_reset();
ok( 'posts' === es_front_page()['mode'], 'un sitio recien instalado muestra entradas, no una pagina' );
ok( 0 === es_front_page()['id'], 'y no hay ningun id de portada' );

/* La mitad de la opcion puesta NO es una portada: WordPress cae de vuelta al blog. Un lector que
   solo mirara `page_on_front` daria por buena una portada que nadie ve. */
wp_fake_reset();
$hid                                     = wp_fake_page( 'inicio' );
$GLOBALS['wp']['options']['page_on_front'] = $hid;
ok( 'posts' === es_front_page()['mode'], "page_on_front solo, sin show_on_front='page', sigue siendo el blog" );

wp_fake_reset();
$hid                                       = wp_fake_page( 'inicio' );
$GLOBALS['wp']['options']['show_on_front']  = 'page';
$GLOBALS['wp']['options']['page_on_front']  = $hid;
$fp                                        = es_front_page();
ok( 'page' === $fp['mode'] && $hid === $fp['id'], 'con las dos opciones puestas, resuelve la pagina' );
ok( 'inicio' === $fp['slug'], 'y da su slug, que es como se resuelve la home sin adivinarla' );

wp_fake_reset();
$hid    = wp_fake_page( 'inicio' );
$action = null;
$r      = grab(
	function () {
		return es_set_front_page( 'inicio' );
	}
);
ok( $hid === $r['ret'], 'establecer la portada devuelve el id de la pagina' );
ok( 'page' === get_option( 'show_on_front' ), "y escribe show_on_front='page'" );
ok( $hid === (int) get_option( 'page_on_front' ), 'y page_on_front' );
ok( '' === $r['out'], 'sin avisos: en un sitio que mostraba el blog no hay nada que reclamar' );

wp_fake_reset();
$r = grab(
	function () {
		return es_set_front_page( 'inicio' );
	}
);
ok( 0 === $r['ret'], 'pedir de portada una pagina que no existe devuelve 0' );
ok( has( $r['out'], 'inicio' ), 'avisando y nombrando el slug' );
ok( ! array_key_exists( 'show_on_front', $GLOBALS['wp']['options'] ), 'y NO deja el sitio a medio configurar' );

/* El corazon del hallazgo: escribir no es haber escrito. update_option() devuelve false tambien
   cuando el valor no cambio, asi que su booleano no sirve de prueba en ninguna direccion. */
wp_fake_reset();
$hid                          = wp_fake_page( 'inicio' );
$GLOBALS['wp']['option_ro']   = array( 'page_on_front' );
$r                            = grab(
	function () {
		return es_set_front_page( 'inicio' );
	}
);
ok( 0 === $r['ret'], 'una escritura aceptada que no aterrizo devuelve 0, no el id' );
ok( '' !== $r['out'], 'y avisa: escribir la opcion no es haberla escrito' );
ok( has( $r['out'], 'inicio' ), 'nombrando la pagina que se pidio' );

/* Repuntar la portada de un sitio existente es destructivo y silencioso por naturaleza: la home
   anterior sigue publicada, solo deja de ser la que se ve al entrar. */
wp_fake_reset();
$vieja                                     = wp_fake_page( 'home-antigua' );
$nueva                                     = wp_fake_page( 'inicio' );
$GLOBALS['wp']['options']['show_on_front'] = 'page';
$GLOBALS['wp']['options']['page_on_front'] = $vieja;
$r                                         = grab(
	function () {
		return es_set_front_page( 'inicio' );
	}
);
ok( $nueva === $r['ret'], 'repuntar la portada funciona' );
ok( has( $r['out'], 'home-antigua' ), 'pero avisa nombrando la portada que se deja de mostrar' );
ok( has( $r['out'], 'inicio' ), 'y la que pasa a mostrarse' );

wp_fake_reset();
$hid                                       = wp_fake_page( 'inicio' );
$GLOBALS['wp']['options']['show_on_front'] = 'page';
$GLOBALS['wp']['options']['page_on_front'] = $hid;
$r                                         = grab(
	function () {
		return es_set_front_page( 'inicio' );
	}
);
ok( $hid === $r['ret'], 'reestablecer la MISMA portada sigue devolviendo el id' );
ok( '' === $r['out'], 'y no avisa de nada: no se dejo de mostrar ninguna pagina' );

/* ---------------------------------------------------------------------------
 * 17. La copia de seguridad prometia mas de lo que guardaba.
 * ------------------------------------------------------------------------- */
echo "--- el respaldo cubre todo lo que la escritura desplaza, no solo el layout ---\n";

/* El docblock decia que "cada sobrescritura aparca el layout anterior". Cierto y estrecho:
   es_save_page() tambien pisa _wp_page_template, y restaurar el layout sobre una plantilla
   cambiada no devuelve la pagina a como estaba. */
wp_fake_reset();
$pid = wp_fake_page(
	'servicios',
	'publish',
	'Servicios',
	'',
	array(
		'_elementor_data'      => '[{"viejo":1}]',
		'_elementor_edit_mode' => 'builder',
		'_wp_page_template'    => 'plantilla-del-tema.php',
	)
);
/* El titulo NUEVO es distinto del viejo A PROPOSITO. La version anterior de esta assertion usaba el
   mismo en los dos lados, asi que no podia fallar: el respaldo guardaba el titulo YA PISADO por
   wp_update_post() y la comprobacion pasaba igual. Lo destapo una corrida end-to-end contra un sitio
   real, donde el titulo si cambiaba. Un test que no puede distinguir el arreglo de su propio bug es
   exactamente lo que esta rama existe para eliminar. */
$r = grab(
	function () use ( $els ) {
		$a = null;
		return es_save_page( 'servicios', 'Servicios v2', $els, 'elementor_header_footer', $a );
	}
);
$bk = backup_of( $pid );
ok( is_array( $bk ), 'el respaldo es un conjunto, no un unico blob' );
ok( isset( $bk['_elementor_data'] ) && has( $bk['_elementor_data'], 'viejo' ), 'con el layout anterior' );
ok( isset( $bk['_wp_page_template'] ) && 'plantilla-del-tema.php' === $bk['_wp_page_template'], 'Y la plantilla anterior, que la escritura tambien pisa' );
ok( isset( $bk['post_title'] ) && 'Servicios' === $bk['post_title'], 'y el titulo VIEJO, no el que wp_update_post acaba de escribir' );
ok( 'Servicios v2' === get_post_field( 'post_title', $pid ), 'mientras la pagina si lleva el nuevo' );
ok( isset( $bk['post_status'] ) && 'publish' === $bk['post_status'], 'y el estado' );
ok( 'elementor_header_footer' === get_post_meta( $pid, '_wp_page_template' ), 'la escritura si cambio la plantilla, que es lo que hace falta respaldar' );

/* Convertir una pagina clasica es la sobrescritura mas destructiva del repertorio y era la unica
   que NO dejaba respaldo: sin _elementor_data que copiar, el respaldo antiguo devolvia '' y se iba
   en silencio, mientras el post_content que ERA la pagina dejaba de renderizarse para siempre. */
wp_fake_reset();
$pid = wp_fake_page( 'quienes-somos', 'publish', 'Quienes somos', '<p>Texto que lleva ahi ocho anios.</p>' );
approve( 'quienes-somos' );
$r   = grab(
	function () use ( $els ) {
		$a = null;
		return es_save_page( 'quienes-somos', 'Quienes somos', $els, 'elementor_header_footer', $a );
	}
);
ok( '' !== $r['out'], 'convertir una pagina que no era de Elementor avisa' );
ok( has( $r['out'], 'quienes-somos' ), 'nombrando el slug' );
$bk = backup_of( $pid );
ok( is_array( $bk ), 'y deja respaldo aunque no hubiera layout que copiar' );
ok( isset( $bk['post_content'] ) && has( $bk['post_content'], 'ocho anios' ), 'con el contenido clasico que deja de renderizarse' );

/* Una pagina nueva y vacia no tiene nada que perder: respaldarla es ruido, y un respaldo por
   reconstruccion en una pagina que nunca tuvo nada llena la tabla de meta sin motivo. */
wp_fake_reset();
approve( 'nueva' );
$r = grab(
	function () use ( $els ) {
		$a = null;
		return es_save_page( 'nueva', 'Nueva', $els, 'elementor_header_footer', $a );
	}
);
ok( '' === $r['out'], 'crear una pagina nueva no avisa de ninguna conversion' );
$ninguno = true;
foreach ( array_keys( $GLOBALS['wp']['meta'] ) as $any ) {
	if ( '' !== backup_of( $any ) ) {
		$ninguno = false;
	}
}
ok( $ninguno, 'ni deja respaldo en ninguna parte: no habia nada que desplazar' );

/* ---------------------------------------------------------------------------
 * 30. Nadie cruzaba el inventario con los slugs a escribir.
 * ------------------------------------------------------------------------- */
echo "--- se puede saber QUE se va a pisar antes de pisarlo ---\n";

wp_fake_reset();
$home  = wp_fake_page( 'inicio', 'publish', 'Inicio', '', array( '_elementor_data' => '[1]', '_elementor_edit_mode' => 'builder' ) );
$clas  = wp_fake_page( 'quienes-somos', 'publish', 'Quienes somos', '<p>clasica</p>' );
$borr  = wp_fake_page( 'servicios', 'draft', 'Servicios' );
$GLOBALS['wp']['options']['show_on_front'] = 'page';
$GLOBALS['wp']['options']['page_on_front'] = $home;

$r = grab(
	function () {
		return es_overwrite_preflight( array( 'inicio', 'quienes-somos', 'servicios', 'contacto' ) );
	}
);
$p = $r['ret'];
ok( is_array( $p ) && isset( $p['rows'] ), 'el preflight devuelve filas' );
ok( 4 === count( $p['rows'] ), 'una por slug pedido, incluida la que no existe' );
ok( 3 === $p['overwrites'] && 1 === $p['creates'], 'y separa lo que se pisa de lo que se crea' );

$by = array();
foreach ( $p['rows'] as $row ) {
	$by[ $row['slug'] ] = $row;
}
ok( 'overwrite' === $by['inicio']['action'] && $home === $by['inicio']['id'], 'una pagina existente sale como sobrescritura, con su id' );
ok( true === $by['inicio']['is_front_page'], 'y marca cual es la PORTADA: pisarla es lo que ve el cliente al entrar' );
ok( true === $by['inicio']['is_elementor'], 'dice si ya era de Elementor' );
ok( false === $by['quienes-somos']['is_elementor'], 'y si no lo era' );
ok( true === $by['quienes-somos']['converts'], 'porque eso es una CONVERSION, no una reconstruccion' );
ok( 'draft' === $by['servicios']['status'], 'da el estado, para no publicar un borrador sin querer' );
ok( 'create' === $by['contacto']['action'] && 0 === $by['contacto']['id'], 'y lo que no existe se va a crear' );

ok( has( $r['out'], 'inicio' ), 'lo imprime, porque esto es lo que el usuario aprueba' );
ok( has( $r['out'], 'PORTADA' ), 'destacando la portada' );
ok( has( $r['out'], 'quienes-somos' ), 'y la conversion' );

wp_fake_reset();
$r = grab(
	function () {
		return es_overwrite_preflight( array( 'contacto' ) );
	}
);
ok( 0 === $r['ret']['overwrites'], 'un sitio vacio no pisa nada' );
ok( '' !== $r['out'], 'y aun asi informa: "no se pisa nada" tambien es una respuesta que hay que ver' );

/* ---------------------------------------------------------------------------
 * 16. Higiene de slugs: mover, no duplicar; y decir que la redireccion no existe.
 * ------------------------------------------------------------------------- */
echo "--- un slug que cambia mueve la pagina, y la URL vieja queda anotada ---\n";

wp_fake_reset();
$pid = wp_fake_page( 'servicios-web', 'publish', 'Servicios' );
$r   = grab(
	function () {
		return es_migrate_slug( 'servicios-web', 'servicios' );
	}
);
ok( $pid === $r['ret'], 'mueve la MISMA pagina, no crea una segunda' );
ok( 'servicios' === get_post_field( 'post_name', $pid ), 'y el slug quedo cambiado de verdad' );
$mapa = get_option( 'es_slug_redirects' );
ok( is_array( $mapa ) && isset( $mapa['servicios-web'] ) && 'servicios' === $mapa['servicios-web'], 'anotando de donde a donde' );
/* La parte incomoda, y la que hace que esto no sea una mentira: nada sirve ese mapa. */
ok( has( $r['out'], '404' ), 'y AVISA de que la URL vieja sigue devolviendo 404' );
ok( has( $r['out'], 'es_slug_redirects' ), 'nombrando la opcion donde quedo el registro' );

wp_fake_reset();
$r = grab(
	function () {
		return es_migrate_slug( 'no-existe', 'servicios' );
	}
);
ok( 0 === $r['ret'], 'mover algo que no existe no mueve nada' );
ok( has( $r['out'], 'no-existe' ), 'y avisa, porque un slug de origen mal escrito no falla: no hace nada' );

/* Mover encima de una pagina ocupada dejaria dos peleando por la misma URL y WordPress renombrando
   una a lo que le pareciera — la colision del hallazgo 15 otra vez, por la puerta de atras. */
wp_fake_reset();
$viejo = wp_fake_page( 'servicios-web' );
$otro  = wp_fake_page( 'servicios' );
$r     = grab(
	function () {
		return es_migrate_slug( 'servicios-web', 'servicios' );
	}
);
ok( 0 === $r['ret'], 'mover a un slug ocupado no mueve nada' );
ok( 'servicios-web' === get_post_field( 'post_name', $viejo ), 'la pagina se queda donde estaba' );
ok( has( $r['out'], '#' . $otro ), 'y el aviso nombra a quien ocupa el destino' );
ok( ! is_array( get_option( 'es_slug_redirects' ) ), 'sin anotar una redireccion que no ocurrio' );

wp_fake_reset();
wp_fake_page( 'servicios' );
$r = grab(
	function () {
		return es_migrate_slug( 'servicios', 'servicios' );
	}
);
ok( 0 === $r['ret'] && '' === $r['out'], 'mover un slug a si mismo no hace nada y no dice nada' );

/* Igual que en es_save_page: escribir no es haber escrito. */
wp_fake_reset();
$pid                         = wp_fake_page( 'servicios-web' );
$GLOBALS['wp']['update_ret'] = new WP_Error( 'db', 'la fila no se pudo tocar' );
$r                           = grab(
	function () {
		return es_migrate_slug( 'servicios-web', 'servicios' );
	}
);
ok( 0 === $r['ret'], 'un movimiento rechazado devuelve 0' );
ok( has( $r['out'], 'la fila no se pudo tocar' ), 'con el motivo de WordPress' );
ok( ! is_array( get_option( 'es_slug_redirects' ) ), 'y no anota una redireccion hacia una pagina que no se movio' );

/* Este bloque lo destapo una mutacion: quitar la relectura del slug SOBREVIVIA a la suite entera.
   El caso que faltaba es el hallazgo 15 por la puerta de atras — wp_unique_post_slug() corre
   tambien al ACTUALIZAR, y el espacio de slugs incluye adjuntos y entradas, que get_page_by_path()
   no ve. Asi que un destino que ninguna PAGINA ocupa puede volver sufijado, y la actualizacion
   reporta exito igual. Anotar la redireccion ahi apuntaria a una URL que no existe. */
wp_fake_reset();
$pid                        = wp_fake_page( 'servicios-web' );
$GLOBALS['wp']['rename_to'] = 'servicios-2';
$r                          = grab(
	function () {
		return es_migrate_slug( 'servicios-web', 'servicios' );
	}
);
ok( 0 === $r['ret'], 'si WordPress sufija el destino, el movimiento NO cuenta como hecho' );
ok( has( $r['out'], 'servicios-2' ), 'y el aviso nombra el slug que quedo de verdad' );
ok( ! is_array( get_option( 'es_slug_redirects' ) ), 'sin anotar una redireccion hacia una URL que no existe' );

/* ---------------------------------------------------------------------------
 * 27. Registrado no es lo mismo que visible.
 * ------------------------------------------------------------------------- */
echo "--- una plantilla registrada que compite por su ubicacion deja de dar verde ---\n";

$GLOBALS['es_pro'] = true;   /* a partir de aqui existe Elementor Pro: la rama es alcanzable */

wp_fake_reset();
$mio = wp_fake_page( 'site-header' );
$GLOBALS['wp']['options']['elementor_pro_theme_builder_conditions'] = array( 'header' => array( $mio => array( 'include/general' ) ) );
$r = grab(
	function () use ( $els ) {
		$a = null;
		return es_save_theme_part( 'site-header', 'Header', 'header', $els, array( 'include/general' ), $a );
	}
);
ok( array() === es_theme_location_rivals( $mio ), 'sin rivales, la lista viene vacia' );
ok( ! has( $r['out'], 'NO es la unica' ), 'y no avisa de nada' );

/* Lo que el informe llama secuestro de plantilla: una plantilla ajena —de la agencia anterior, del
   tema, o del build de ayer— ya reclamaba `header`. La nuestra se guarda, se condiciona, se
   cachea, la comprobacion de registro dice que si, y el sitio sigue enseñando la otra. */
wp_fake_reset();
$mio   = wp_fake_page( 'site-header' );
$ajena = 777;
$GLOBALS['wp']['options']['elementor_pro_theme_builder_conditions'] = array(
	'header' => array(
		$ajena => array( 'include/general' ),
		$mio   => array( 'include/general' ),
	),
);
ok( true === es_theme_conditions_registered( $mio ), 'la comprobacion de registro sigue diciendo que si, y tiene razon' );
$riv = es_theme_location_rivals( $mio );
ok( isset( $riv['header'] ) && array( 777 ) === $riv['header'], 'pero ahora se puede preguntar quien mas esta en esa ubicacion' );
$r = grab(
	function () use ( $els ) {
		$a = null;
		return es_save_theme_part( 'site-header', 'Header', 'header', $els, array( 'include/general' ), $a );
	}
);
ok( has( $r['out'], 'NO es la unica' ), 'y el guardado avisa: registrado no es visible' );
ok( has( $r['out'], '#777' ), 'nombrando la rival por id, que es con lo que se la busca' );
ok( has( $r['out'], 'header' ), 'y la ubicacion en disputa' );

/* Un sitio puede tener dos plantillas en una ubicacion a proposito, con condiciones distintas.
   Sin forma de decirlo, este aviso saltaba en cada build de un sitio CORRECTO, que es como un
   aviso se convierte en paisaje — el mismo argumento que el umbral de palabras que nadie cumplia. */
$r = grab(
	function () use ( $els, $ajena ) {
		$a = null;
		return es_save_theme_part( 'site-header', 'Header', 'header', $els, array( 'include/general' ), $a, array( $ajena ) );
	}
);
ok( ! has( $r['out'], 'NO es la unica' ), 'una rival RECONOCIDA por quien llama no vuelve a avisar' );

/* Pero reconocer es por ID, nunca por ubicacion: la que aparece DESPUES es un hecho nuevo. */
$GLOBALS['wp']['options']['elementor_pro_theme_builder_conditions']['header'][999] = array( 'include/general' );
$r = grab(
	function () use ( $els, $ajena ) {
		$a = null;
		return es_save_theme_part( 'site-header', 'Header', 'header', $els, array( 'include/general' ), $a, array( $ajena ) );
	}
);
ok( has( $r['out'], '#999' ), 'una rival NUEVA sigue avisando aunque otra este reconocida' );
ok( ! has( $r['out'], '#777' ), 'y no vuelve a nombrar la que ya se miro' );
ok( has( $r['out'], '1 rival' ), 'pero dice cuantas hay silenciadas: reconocida no es invisible' );

/* Una plantilla en OTRA ubicacion no compite: avisar de ella seria ruido, y un aviso que salta
   siempre es un aviso que se aprende a ignorar. */
wp_fake_reset();
$mio = wp_fake_page( 'site-header' );
$GLOBALS['wp']['options']['elementor_pro_theme_builder_conditions'] = array(
	'header' => array( $mio => array( 'include/general' ) ),
	'footer' => array( 888 => array( 'include/general' ) ),
);
ok( array() === es_theme_location_rivals( $mio ), 'una plantilla en otra ubicacion no es rival' );

$GLOBALS['es_pro'] = false;

/* ---------------------------------------------------------------------------
 * 28. El fichero abortaba con CERO salida.
 * ------------------------------------------------------------------------- */
echo "--- si falta la dependencia, se dice; no se muere en silencio ---\n";

/* No se puede probar dentro de este proceso: el fichero ya esta cargado y su guarda corre al
   cargarse. Se prueba en uno nuevo, con un WP_CONTENT_DIR vacio, que es exactamente el caso real
   —subir los theme parts antes que el builder—. Antes de esta guarda eso era un fatal con la
   salida vacia, indistinguible de un build que corrio y no hizo nada. */
if ( ! function_exists( 'exec' ) ) {
	ok( false, 'ENTORNO, no el cambio: exec() esta deshabilitado, la guarda no se pudo verificar aqui' );
} else {
	$vacio = sys_get_temp_dir() . '/es-nodep-' . getmypid();
	@mkdir( $vacio, 0777, true );
	$probe = $vacio . '/probe.php';
	file_put_contents(
		$probe,
		'<?php' . "\n"
		. "define( 'ABSPATH', __DIR__ );\n"
		. 'define( ' . var_export( 'WP_CONTENT_DIR', true ) . ', ' . var_export( $vacio, true ) . " );\n"
		. 'require ' . var_export( dirname( __DIR__ ) . '/skills/elementor-theme-parts/assets/es-theme-parts.example.php', true ) . ";\n"
		. "echo \"SOBREVIVIO\\n\";\n"
	);
	$out  = array();
	$code = -1;
	$ran  = exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( $probe ) . ' 2>&1', $out, $code );
	$txt  = implode( "\n", $out );
	@unlink( $probe );
	@rmdir( $vacio );
	ok( false !== $ran && -1 !== $code, 'el proceso de prueba se pudo lanzar' );
	ok( '' !== trim( $txt ), 'falta la dependencia y la salida NO esta vacia' );
	ok( has( $txt, 'es-builder.php' ), 'nombra el fichero que falta' );
	ok( has( $txt, 'NO SE CONSTRUYO NADA' ), 'y dice explicitamente que no se construyo nada' );
	ok( has( $txt, 'SOBREVIVIO' ), 'y devuelve el control en vez de fatalar, que es lo que tumbaba el sitio al subirlo' );
	ok( ! has( $txt, 'Fatal error' ), 'sin fatal' );
}

/* ---------------------------------------------------------------------------
 * Entrega: el sandbox se vacia, y "vacio" se comprueba leyendo.
 * ------------------------------------------------------------------------- */
echo "--- la entrega borra el sandbox y RELEE para saber si quedo vacio ---\n";

/* El unico hallazgo de seguridad del informe: este directorio no se limpia nunca. Todo .php que
   queda ahi sigue siendo ejecutable y alcanzable por URL en el sitio del cliente, para siempre. */
$sb = es_sandbox_dir();
foreach ( array( 'es-page-home.php', 'debug.log', 'notas.txt' ) as $f ) {
	file_put_contents( $sb . '/' . $f, 'x' );
}
$antes = es_sandbox_report();
ok( in_array( 'es-page-home.php', $antes, true ), 'el informe ve lo que hay antes de borrar' );
ok( count( $antes ) >= 4, 'incluyendo el shim que este propio test dejo ahi' );

$r    = grab( 'es_sandbox_purge' );
$left = $r['ret'];
ok( array() === $left, 'tras purgar, la lista de lo que queda viene vacia' );
ok( array() === es_sandbox_report(), 'y releer el directorio lo confirma' );
ok( '' === $r['out'], 'sin avisos, porque no quedo nada' );

/* Un borrado que falla en silencio y uno que funciona son indistinguibles por el retorno de
   unlink(). Por eso lo que se devuelve es lo que SIGUE ahi, no lo que se borro. */
mkdir( $sb . '/subdir' );
file_put_contents( $sb . '/subdir/dentro.php', 'x' );
file_put_contents( $sb . '/imagen.png', 'x' );
$r    = grab( 'es_sandbox_purge' );
$left = $r['ret'];
ok( in_array( 'imagen.png', $left, true ), 'una extension que este framework no sube no se toca' );
ok( in_array( 'subdir', $left, true ), 'ni se entra en subdirectorios' );
ok( file_exists( $sb . '/subdir/dentro.php' ), 'asi que un .php dentro de un subdirectorio sobrevive' );
ok( '' !== $r['out'], 'y AVISA de que el sandbox no quedo vacio' );
ok( has( $r['out'], 'imagen.png' ), 'nombrando lo que queda' );

unlink( $sb . '/subdir/dentro.php' );
rmdir( $sb . '/subdir' );
unlink( $sb . '/imagen.png' );

/* Encontrado limpiando el sandbox de un cliente REAL: `es-dlo-a11y.php` registraba
   template_redirect y envolvia cada pagina en un landmark <main> porque el tema no imprime
   ninguno. No es andamio: es la accesibilidad del sitio, viviendo en el unico directorio cuyo
   trabajo es vaciarse. La entrega lo habria borrado el dia de la entrega, en silencio y con todos
   los checks en verde. */
file_put_contents( $sb . '/es-page-otra.php', "<?php\nfunction es_build_otra() { return 1; }\n" );
/* Un hook citado SOLO en un comentario no mantiene vivo un fichero muerto: fichero aparte, para
   que la asercion pueda fallar. La primera version metia el comentario dentro del fichero que SI
   engancha, asi que decia "no se borro" por el motivo equivocado y no podia distinguir nada. */
file_put_contents( $sb . '/es-muerto.php', "<?php\n/* add_action( 'init', 'viejo' ); ya no se usa */\nfunction viejo() { return 1; }\n" );
file_put_contents( $sb . '/es-a11y.php', "<?php\nfunction wrap( \$h ) { return \$h; }\nadd_action( 'template_redirect', 'wrap', 1 );\n" );
$r    = grab( 'es_sandbox_purge' );
$left = $r['ret'];
ok( ! in_array( 'es-page-otra.php', $left, true ), 'un script de build se borra como siempre' );
ok( ! in_array( 'es-muerto.php', $left, true ), 'un hook citado solo en un COMENTARIO no mantiene vivo un fichero muerto' );
ok( in_array( 'es-a11y.php', $left, true ), 'pero uno que registra hooks NO se borra: corre en cada visita, no es andamio' );
ok( has( $r['out'], 'template_redirect' ), 'y el aviso NOMBRA el hook, que es lo que lo delata' );
ok( has( $r['out'], 'tema hijo' ), 'diciendo adonde tiene que mudarse' );
ok( array( 'template_redirect' ) === es_sandbox_runtime_hooks( $sb . '/es-a11y.php' ), 'el detector devuelve el hook, no un booleano: por eso el aviso puede nombrarlo' );
ok( array() === es_sandbox_runtime_hooks( $sb . '/no-existe.php' ), 'y un fichero que no existe no tiene hooks, no revienta' );
unlink( $sb . '/es-a11y.php' );

echo "--- las claves de respaldo se entregan, no se prometen ---\n";
wp_fake_reset();
$p1 = wp_fake_page( 'inicio' );
$p2 = wp_fake_page( 'contacto' );
$p3 = wp_fake_page( 'nueva' );
$GLOBALS['wp']['meta'][ $p1 ]['_es_page_backup_20260101-101010'] = array( 'post_title' => 'a' );
$GLOBALS['wp']['meta'][ $p1 ]['_es_page_backup_20260102-101010'] = array( 'post_title' => 'b' );
$GLOBALS['wp']['meta'][ $p1 ]['_elementor_data']                 = '[]';
$GLOBALS['wp']['meta'][ $p2 ]['_es_page_backup_20260103-101010'] = array( 'post_title' => 'c' );

$k = es_backup_keys( array( $p1, $p2, $p3 ) );
ok( isset( $k[ $p1 ] ) && 2 === count( $k[ $p1 ] ), 'una pagina reconstruida dos veces entrega sus dos claves' );
ok( $k[ $p1 ][0] < $k[ $p1 ][1], 'ordenadas, asi que la ultima es la mas reciente' );
ok( isset( $k[ $p2 ] ) && 1 === count( $k[ $p2 ] ), 'y cada pagina las suyas' );
ok( ! isset( $k[ $p3 ] ), 'una pagina sin respaldos no aparece: una lista vacia no es una entrega' );
ok( ! in_array( '_elementor_data', $k[ $p1 ], true ), 'y no cuela meta que no es un respaldo' );

echo "--- el estado de indexacion se declara, no se supone ---\n";
wp_fake_reset();
$GLOBALS['wp']['options']['blog_public'] = '0';
$st                                      = es_indexing_state();
ok( false === $st['indexable'], "blog_public=0 es 'disuadir a los buscadores': el sitio NO es indexable" );
ok( '0' === $st['blog_public'], 'y se devuelve el valor crudo, no solo el veredicto' );

wp_fake_reset();
$GLOBALS['wp']['options']['blog_public'] = '1';
ok( true === es_indexing_state()['indexable'], 'blog_public=1 si lo es' );

wp_fake_reset();
$st = es_indexing_state();
ok( false === $st['indexable'], 'sin la opcion puesta se falla CERRADO: no se declara indexable lo que no se leyo' );
ok( null === $st['robots_file'], 'y sin robots.txt fisico se dice null, no se inventa un permiso' );

/* ---------------------------------------------------------------------------
 * Manifiesto: estado entre sesiones que se puede CONTRASTAR.
 * ------------------------------------------------------------------------- */
echo "--- el manifiesto guarda por secciones, y se relee ---\n";

wp_fake_reset();
ok( array() === es_manifest_read()['sections'], 'sin manifiesto, se devuelve la forma vacia y no null' );

ok( true === es_manifest_record( 'site', array( 'builder' => 'elementor' ) ), 'guardar una seccion devuelve true' );
ok( true === es_manifest_record( 'design', array( 'personality' => 'PERS-EDITORIAL' ) ), 'y otra tambien' );
$m = es_manifest_read();
ok( 'elementor' === $m['sections']['site']['data']['builder'], 'la primera seccion sigue ahi' );
ok( 'PERS-EDITORIAL' === $m['sections']['design']['data']['personality'], 'y la segunda no la piso' );
ok( '' !== $m['sections']['site']['at'], 'cada seccion lleva su propia marca de tiempo' );

/* La lista de secciones deja de ser prosa repetida y pasa a ser un hecho invocable. */
$secciones = es_manifest_sections();
ok( array( 'site', 'design', 'pages', 'delivery', 'build' ) === $secciones, 'es_manifest_sections() devuelve las CINCO secciones, en ese orden, sin una sexta' );

$mal = array();
$n   = 0;
foreach ( $secciones as $s ) {
	$n++;
	wp_fake_reset();
	es_manifest_record( $s, array( 'probe' => $s ) );
	$leido = es_manifest_read();
	if ( ! isset( $leido['sections'][ $s ]['data']['probe'] ) || $s !== $leido['sections'][ $s ]['data']['probe'] ) {
		$mal[] = $s;
	}
}
ok( 0 < $n && array() === $mal, 'cada nombre que es_manifest_sections() declara acepta un es_manifest_record() y se relee igual' );

/* art-direction-ledger (style-catalog Slice 5a): la resolucion de estilo — id, brief negativo,
   tono rechazado — es EL llamado que 'design' no tenia (manifest-section-contract, delta). */
echo "--- la resolucion de estilo se graba con es_record_style_resolution() ---\n";

wp_fake_reset();
ok(
	true === es_record_style_resolution( 'STY-EDITORIAL', 'sin foto de stock, sin hero con gradiente', 'frio' ),
	'las tres respuestas completas se graban'
);
$m = es_manifest_read();
ok( 'STY-EDITORIAL' === $m['sections']['design']['data']['style'], 'el id resuelto queda en la seccion design' );
ok( '' !== $m['sections']['design']['data']['negative_brief'], 'con su brief negativo' );
ok( '' !== $m['sections']['design']['data']['rejected_tone'], 'y su tono rechazado' );

/* Una resolucion incompleta no es progreso parcial: es exactamente el default silencioso que esta
   funcion existe para dejar de reproducir — falla CERRADO, no graba una resolucion a medias. */
wp_fake_reset();
$r = grab(
	function () {
		return es_record_style_resolution( 'STY-EDITORIAL', '', 'frio' );
	}
);
ok( false === $r['ret'], 'un brief negativo vacio no se graba' );
ok( has( $r['out'], 'resolucion de estilo incompleta' ), 'y avisa por que' );
ok( array() === es_manifest_read()['sections'], 'nada quedo escrito: ni siquiera el id, que si llego completo' );

/* Re-resolver en la misma sesion (un cambio de diseno a mitad de build) PISA la seccion 'design',
   nunca la acumula — el historial de entregas es shipped-log.md (Slice 5b), no esta seccion. */
wp_fake_reset();
es_record_style_resolution( 'STY-EDITORIAL', 'sin foto de stock', 'frio' );
es_record_style_resolution( 'STY-MATTER', 'sin tipografia script', 'calido' );
$m = es_manifest_read();
ok( 'STY-MATTER' === $m['sections']['design']['data']['style'], 'la segunda resolucion reemplaza a la primera, no la acumula' );
ok(
	'sin tipografia script' === $m['sections']['design']['data']['negative_brief'],
	'con TODOS sus campos pisados, no solo el id'
);

/* Escribir no es haber escrito, igual que en la portada y en el camino de escritura. */
wp_fake_reset();
$GLOBALS['wp']['option_ro'] = array( 'es_novamira_manifest' );
$r                          = grab(
	function () {
		return es_manifest_record( 'site', array( 'builder' => 'divi' ) );
	}
);
ok( false === $r['ret'], 'una escritura aceptada que no aterriza devuelve false' );
ok( has( $r['out'], 'manifiesto' ), 'y avisa' );
ok( has( $r['out'], 'sin saber nada' ), 'diciendo lo que se pierde: la proxima sesion empieza a ciegas' );

echo "--- y se puede contrastar contra el sitio que dice describir ---\n";

wp_fake_reset();
$h = wp_fake_page( 'inicio' );
$c = wp_fake_page( 'contacto' );
$GLOBALS['wp']['options']['show_on_front'] = 'page';
$GLOBALS['wp']['options']['page_on_front'] = $h;
es_manifest_record( 'pages', array( 'inicio' => $h, 'contacto' => $c ) );
es_manifest_record( 'site', array( 'front_page_id' => $h ) );
$r = grab( 'es_manifest_verify' );
ok( array() === $r['ret'], 'un manifiesto que coincide no reporta nada' );
ok( '' === $r['out'], 'ni avisa' );

/* Una pagina borrada a mano entre sesiones. */
wp_fake_reset();
$h = wp_fake_page( 'inicio' );
es_manifest_record( 'pages', array( 'inicio' => $h, 'contacto' => 999 ) );
$r = grab( 'es_manifest_verify' );
ok( 1 === count( $r['ret'] ), 'una pagina que ya no existe es una desviacion' );
ok( has( $r['ret'][0], 'ya no existe' ), 'nombrada como tal' );
ok( has( $r['ret'][0], 'contacto' ), 'con su slug' );
ok( has( $r['out'], 'solo un humano' ), 'y el aviso dice por que NO se corrige solo' );

/* Renombrada fuera del framework: el id sigue vivo, el slug ya no es el que era. */
wp_fake_reset();
$c = wp_fake_page( 'contacto-nuevo' );
es_manifest_record( 'pages', array( 'contacto' => $c ) );
$d = grab( 'es_manifest_verify' )['ret'];
ok( 1 === count( $d ) && has( $d[0], 'contacto-nuevo' ), 'una pagina movida a mano se detecta por el slug real' );
ok( has( $d[0], 'contacto' ) && has( $d[0], '#' . $c ), 'la fila da los DOS hechos: lo que el manifiesto llama y lo que el sitio dice' );
ok( ! has( $d[0], 'alguien la movio' ), 'y NO afirma quien lo hizo: eso es una causa, y esta fila solo puede observar' );

/* La misma fila, otra causa entera. Encontrado probando contra WordPress de verdad: se anoto
   `front => 2` DENTRO del mapa `pages`, que es slug->id, asi que la clave no era un slug y nadie
   habia movido nada. La version anterior de esta fila diagnosticaba "alguien la movio fuera de este
   framework" con total seguridad, y mandaba al lector a buscar una edicion que nunca existio. Un
   informe cuya regla es no afirmar lo que no leyo no puede permitirse un porque inventado. El home
   correcto del id de portada es la seccion `site` (paso 8 de elementor-core), no `pages`: esta fila
   sigue probando justo ese error. */
wp_fake_reset();
$h = wp_fake_page( 'inicio' );
es_manifest_record( 'pages', array( 'front' => $h ) );
$d = grab( 'es_manifest_verify' )['ret'];
ok( 1 === count( $d ) && has( $d[0], 'inicio' ), 'una clave que no es un slug produce la misma fila' );
ok( has( $d[0], 'nunca fuera un slug' ), 'y la fila admite ESA lectura tambien, en vez de elegir una' );

/* Borrada y recreada: mismo slug, otro id. Es la peor, porque todo "parece" bien. */
wp_fake_reset();
$otro = wp_fake_page( 'inicio' );
es_manifest_record( 'pages', array( 'inicio' => 555 ) );
$d = grab( 'es_manifest_verify' )['ret'];
ok( 1 === count( $d ) && has( $d[0], '#' . $otro ), 'mismo slug con otro id tambien es desviacion' );
ok( has( $d[0], '#555' ), 'nombrando el id que el manifiesto creia' );

/* La portada repuntada entre sesiones. */
wp_fake_reset();
$h = wp_fake_page( 'inicio' );
$o = wp_fake_page( 'otra' );
$GLOBALS['wp']['options']['show_on_front'] = 'page';
$GLOBALS['wp']['options']['page_on_front'] = $o;
es_manifest_record( 'site', array( 'front_page_id' => $h ) );
$d = grab( 'es_manifest_verify' )['ret'];
ok( 1 === count( $d ) && has( $d[0], 'portada' ), 'una portada repuntada se detecta' );

wp_fake_reset();
$h = wp_fake_page( 'inicio' );
es_manifest_record( 'site', array( 'front_page_id' => $h ) );
$d = grab( 'es_manifest_verify' )['ret'];
ok( 1 === count( $d ) && has( $d[0], 'el blog' ), 'y volver a mostrar el blog tambien, que es lo que nadie mira' );

/* ---------------------------------------------------------------------------
 * Un ADJUNTO ocupando el slug. Encontrado probando contra WordPress de verdad.
 * ------------------------------------------------------------------------- */
echo "--- un adjunto con el slug de una pagina deja de pasar por pagina ---\n";

/* `get_page_by_path($slug, OBJECT, 'page')` NO hace lo que dice su tercer argumento: WordPress mete
   los adjuntos en esa busqueda. Medido en una instalacion viva — un adjunto en "nvm-solo-adjunto"
   volvio de una consulta de tipo 'page', y una pagina pidiendo ese mismo slug quedo renombrada
   "-2". Sin filtrar, es_save_page() entraba en la rama de ACTUALIZAR, renombraba el adjunto, le
   escribia _elementor_data encima y reportaba 'updated': ninguna pagina creada, un adjunto roto, y
   todos los checks en verde. */
wp_fake_reset();
$adj = wp_fake_page( 'contacto', 'inherit', 'foto.jpg', '', array(), 'attachment' );
ok( null === es_page_by_slug( 'contacto' ), 'un adjunto NO es una pagina, aunque get_page_by_path lo devuelva' );
ok( null !== get_page_by_path( 'contacto', OBJECT, 'page' ), 'y el doble reproduce que la funcion de WordPress SI lo devuelve' );

$action = null;
$r      = grab(
	function () use ( $els, &$action ) {
		return es_save_page( 'contacto', 'Contacto', $els, 'elementor_header_footer', $action );
	}
);
ok( 'updated' !== $action, "no se ACTUALIZA un adjunto creyendo que es una pagina" );
ok( 'created' === $action || 'created-renamed' === $action, 'se crea una pagina de verdad' );
ok( $r['ret'] !== $adj, 'y el id devuelto no es el del adjunto' );
ok( ! isset( $GLOBALS['wp']['meta'][ $adj ]['_elementor_data'] ), 'el adjunto NO recibio un layout encima' );

/* El preflight lo listaba como una pagina a punto de ser pisada. */
wp_fake_reset();
wp_fake_page( 'contacto', 'inherit', 'foto.jpg', '', array(), 'attachment' );
$p = grab(
	function () {
		return es_overwrite_preflight( array( 'contacto' ) );
	}
)['ret'];
ok( 0 === $p['overwrites'] && 1 === $p['creates'], 'el preflight no cuenta un adjunto como pagina a sobrescribir' );

/* Y el manifiesto verificaba contra el. */
wp_fake_reset();
$adj = wp_fake_page( 'contacto', 'inherit', 'foto.jpg', '', array(), 'attachment' );
es_manifest_record( 'pages', array( 'contacto' => 999 ) );
$d = grab( 'es_manifest_verify' )['ret'];
ok( 1 === count( $d ), 'el manifiesto reporta desviacion: la pagina no existe' );
ok( ! has( $d[0], '#' . $adj ), 'sin confundirla con el adjunto que ocupa ese slug' );

/* Mover a un slug que ocupa un adjunto SI se bloquea: WordPress sufija contra todo el espacio de
   slugs, asi que un adjunto estorba igual que una pagina. Lo que cambia es como se nombra. */
wp_fake_reset();
wp_fake_page( 'servicios-web' );
$adj = wp_fake_page( 'servicios', 'inherit', 'foto.jpg', '', array(), 'attachment' );
$r   = grab(
	function () {
		return es_migrate_slug( 'servicios-web', 'servicios' );
	}
);
ok( 0 === $r['ret'], 'un adjunto en el destino tambien bloquea el movimiento' );
ok( has( $r['out'], 'attachment' ), 'y el aviso dice QUE lo ocupa, no lo llama pagina' );

/* ---------------------------------------------------------------------------
 * Modo seguro del sandbox. Leido del cargador de Novamira, no supuesto.
 * ------------------------------------------------------------------------- */
echo "--- un .crashed apaga el sandbox entero, y eso deja de ser invisible ---\n";

$sb = es_sandbox_dir();

$st = es_sandbox_state();
ok( false === $st['safe_mode'], 'sin .crashed, el sandbox esta operativo' );
ok( null === $st['reason'], 'y no hay causa que reportar' );

/* El cargador hace `if (file_exists($crashed_file)) { return; }` ANTES de recorrer los ficheros:
   uno solo apaga los diez. El unico aviso es un banner de wp-admin, que un agente por conector no
   ve nunca. Medido en un sitio vivo que llevaba en modo seguro desde un fatal: ni una sola funcion
   es_* estaba definida, asi que cualquier build habria muerto con "undefined function". */
file_put_contents( $sb . '/.crashed', json_encode( array( 'sandbox_file' => '/ruta/es-theme-parts.php', 'message' => 'Uncaught Error: Call to undefined function is_user_logged_in()' ) ) );
$r  = grab( 'es_sandbox_state' );
$st = $r['ret'];
ok( true === $st['safe_mode'], 'con .crashed, se reporta MODO SEGURO' );
ok( has( $st['reason'], 'es-theme-parts.php' ), 'nombrando el fichero que lo provoco' );
ok( has( $st['reason'], 'is_user_logged_in' ), 'y el error registrado' );
ok( '' !== $r['out'], 'y AVISA: nada de lo que subas se va a ejecutar' );
ok( has( $r['out'], 'MODO SEGURO' ), 'diciendo que es modo seguro' );
/* El aviso tiene que desaconsejar el atajo evidente, que vuelve a tumbar el sitio. */
ok( has( $r['out'], 'ANTES de borrar' ), 'y avisa de no borrar .crashed sin arreglar la causa' );

/* Un .crashed ilegible sigue siendo modo seguro: el cargador solo mira que EXISTA. */
file_put_contents( $sb . '/.crashed', 'no es json' );
$st = grab( 'es_sandbox_state' )['ret'];
ok( true === $st['safe_mode'], 'un .crashed que no es json sigue siendo modo seguro' );
ok( has( $st['reason'], 'no es json' ), 'y se reporta su contenido crudo' );

file_put_contents( $sb . '/.crashed', '' );
$st = grab( 'es_sandbox_state' )['ret'];
ok( true === $st['safe_mode'], 'y uno VACIO tambien: el cargador solo comprueba que exista' );

unlink( $sb . '/.crashed' );
ok( false === es_sandbox_state()['safe_mode'], 'borrado el fichero, vuelve a estar operativo' );

/* ---------------------------------------------------------------------------
 * Restaurar y podar. Un respaldo que nadie puede restaurar no es un respaldo.
 * ------------------------------------------------------------------------- */
echo "--- un respaldo se puede DESHACER, y cada pieza se comprueba ---\n";

wp_fake_reset();
$pid = wp_fake_page(
	'servicios',
	'publish',
	'Titulo VIEJO',
	'',
	array(
		'_elementor_data'   => '[{"viejo":1}]',
		'_wp_page_template' => 'plantilla-vieja.php',
	)
);
$a = null;
grab(
	function () use ( $els, &$a ) {
		return es_save_page( 'servicios', 'Titulo NUEVO', $els, 'elementor_header_footer', $a );
	}
);
ok( 'Titulo NUEVO' === get_post_field( 'post_title', $pid ), 'tras reconstruir, la pagina lleva el titulo nuevo' );
ok( 'elementor_header_footer' === get_post_meta( $pid, '_wp_page_template' ), 'y la plantilla nueva' );

$r   = grab(
	function () use ( $pid ) {
		return es_restore_page_state( $pid );
	}
);
$res = $r['ret'];
ok( '' !== $res['key'], 'restaurar sin nombrar clave coge la mas reciente' );
ok( array() === $res['failed'], 'y no falla ninguna pieza' );
ok( 'Titulo VIEJO' === get_post_field( 'post_title', $pid ), 'el titulo vuelve al viejo' );
ok( 'plantilla-vieja.php' === get_post_meta( $pid, '_wp_page_template' ), 'la plantilla tambien' );
ok( has( (string) get_post_meta( $pid, '_elementor_data' ), 'viejo' ), 'y el layout anterior' );
ok( '' === $r['out'], 'sin avisos cuando todo cuadra' );

/* Restaurar tambien pisa: tiene que dejar su propia red antes. */
ok( '' !== $res['safety'], 'la restauracion guarda el estado que ella misma va a pisar' );
$r2 = grab(
	function () use ( $pid, $res ) {
		return es_restore_page_state( $pid, $res['safety'] );
	}
);
ok( 'Titulo NUEVO' === get_post_field( 'post_title', $pid ), 'asi que deshacer el deshacer devuelve al estado nuevo' );

/* Una clave que no existe no restaura nada a medias. */
wp_fake_reset();
$pid = wp_fake_page( 'x', 'publish', 'T', '', array( '_es_page_backup_20260101-000000' => array( 'post_title' => 'V' ) ) );
$r   = grab(
	function () use ( $pid ) {
		return es_restore_page_state( $pid, '_es_page_backup_NO_EXISTE' );
	}
);
ok( array() === $r['ret']['restored'], 'una clave inexistente no restaura nada' );
ok( has( $r['out'], '_es_page_backup_20260101-000000' ), 'y el aviso lista las que SI existen' );
ok( 'T' === get_post_field( 'post_title', $pid ), 'la pagina se queda como estaba' );

wp_fake_reset();
$pid = wp_fake_page( 'y' );
$r   = grab(
	function () use ( $pid ) {
		return es_restore_page_state( $pid );
	}
);
ok( array() === $r['ret']['restored'] && has( $r['out'], 'no tiene ningun respaldo' ), 'sin respaldos, se dice y no se toca nada' );

echo "--- y no se acumulan para siempre ---\n";
wp_fake_reset();
$pid = wp_fake_page( 'z' );
foreach ( array( '20260101-000001', '20260102-000002', '20260103-000003', '20260104-000004' ) as $s ) {
	$GLOBALS['wp']['meta'][ $pid ][ '_es_page_backup_' . $s ] = array( 'post_title' => $s );
}
$p = es_prune_backups( $pid, 2 );
ok( 2 === count( $p['kept'] ), 'podar a 2 deja 2' );
ok( 2 === count( $p['deleted'] ), 'y borra los otros 2' );
ok( has( $p['kept'][1], '20260104' ), 'los que quedan son los MAS RECIENTES' );
ok( has( $p['deleted'][0], '20260101' ), 'y los borrados los mas viejos' );
ok( array() === $p['still_there'], 'sin restos' );
ok( 2 === count( es_backup_keys( array( $pid ) )[ $pid ] ), 'y al releer, quedan 2 de verdad' );

$p = es_prune_backups( $pid, 5 );
ok( array() === $p['deleted'], 'podar por encima de lo que hay no borra nada' );
$p = es_prune_backups( $pid, 0 );
ok( 1 === count( $p['kept'] ), 'y podar a 0 conserva 1: eso no seria podar, seria borrar los respaldos' );

/* Un borrado ACEPTADO que no aterriza. delete_post_meta() devuelve false al fallar Y cuando no
   habia nada, asi que la prueba es la relectura, igual que en la purga del sandbox. */
wp_fake_reset();
$pid = wp_fake_page( 'w' );
foreach ( array( '20260101-000001', '20260102-000002', '20260103-000003' ) as $s ) {
	$GLOBALS['wp']['meta'][ $pid ][ '_es_page_backup_' . $s ] = array( 'post_title' => $s );
}
$GLOBALS['wp']['meta_ro'] = array( '_es_page_backup_20260101-000001' );
$r = grab(
	function () use ( $pid ) {
		return es_prune_backups( $pid, 1 );
	}
);
ok( array( '_es_page_backup_20260101-000001' ) === $r['ret']['still_there'], 'un borrado que no aterriza se reporta como SIGUE AHI' );
ok( ! in_array( '_es_page_backup_20260101-000001', $r['ret']['deleted'], true ), 'y NO se cuenta como borrado' );
ok( in_array( '_es_page_backup_20260102-000002', $r['ret']['deleted'], true ), 'mientras el que si se borro cuenta' );
ok( has( $r['out'], 'no se pudieron borrar' ), 'y avisa' );

echo "--- restaurar informa de lo VERIFICADO, no de lo intentado ---\n";
wp_fake_reset();
$pid = wp_fake_page( 'v', 'publish', 'Titulo VIEJO', '', array( '_wp_page_template' => 'vieja.php', '_elementor_data' => '[{"a":"b"}]' ) );
approve( 'v' );
$a   = null;
grab(
	function () use ( $els, &$a ) {
		return es_save_page( 'v', 'Titulo NUEVO', $els, 'elementor_header_footer', $a );
	}
);
/* La plantilla se niega a aceptar la restauracion: la pagina queda MEZCLADA y hay que decirlo. */
$GLOBALS['wp']['meta_ro'] = array( '_wp_page_template' );
$r = grab(
	function () use ( $pid ) {
		return es_restore_page_state( $pid );
	}
);
ok( in_array( '_wp_page_template', $r['ret']['failed'], true ), 'una pieza que no aterriza sale en failed' );
ok( in_array( 'post_title', $r['ret']['restored'], true ), 'y la que si aterrizo, en restored' );
ok( has( $r['out'], 'A MEDIAS' ), 'con un aviso de restauracion parcial' );
ok( has( $r['out'], '_wp_page_template' ), 'nombrando la pieza que falta' );

/* `_elementor_data` se guarda deslizado: sin wp_slash() la relectura no coincide. */
$GLOBALS['wp']['meta_ro'] = array();
wp_fake_reset();
$pid = wp_fake_page( 'u', 'publish', 'T', '', array( '_elementor_data' => '[{"txt":"con \\\\\"comillas\\\\\""}]' ) );
$a   = null;
grab(
	function () use ( $els, &$a ) {
		return es_save_page( 'u', 'T2', $els, 'elementor_header_footer', $a );
	}
);
$r = grab(
	function () use ( $pid ) {
		return es_restore_page_state( $pid );
	}
);
ok( in_array( '_elementor_data', $r['ret']['restored'], true ), 'un layout con comillas se restaura deslizado y la relectura cuadra' );

/* ---------------------------------------------------------------------------
 * The two rules that had a helper and no runtime.
 *
 * Both existed as prose: "nobody approves a write they have not been shown" was a house rule with
 * an explicit `(no verifier: …)` marker, and `es_set_front_page()` was a tested, documented
 * function nothing in the repo ever called. Prose is enforced on the builds that read it. These
 * assertions are about the build that does not.
 * ------------------------------------------------------------------------- */

echo "--- un build con el sandbox APAGADO deja de irse en silencio ---\n";

/* `.crashed` apaga el sandbox entero, pero `execute-php` requiere el builder a mano y un require
   explicito no pasa por el cargador: el build corre igual, escribe todas las paginas y reporta
   exito sobre un sitio que sigue degradado. project-context REPORTABA el modo seguro y nada
   actuaba: reportar un bloqueante que el paso siguiente se salta es la forma que esta rama borra. */
$sb = es_sandbox_dir();
@unlink( $sb . '/.crashed' );
wp_fake_reset();
approve( 'con-sandbox-ok' );
$r = grab(
	function () use ( $els ) {
		$a = null;
		return es_save_page( 'con-sandbox-ok', 'P', $els, 'elementor_header_footer', $a );
	}
);
ok( '' === $r['out'], 'sin .crashed no dice nada: el aviso tiene que doler solo cuando hay algo que mirar' );
ok( '' === es_safe_mode_check(), 'y el veredicto viene vacio' );

file_put_contents( $sb . '/.crashed', '{"sandbox_file":"/ruta/es-roto.php","message":"undefined function"}' );
wp_fake_reset();
approve( 'con-sandbox-roto' );
$r = grab(
	function () use ( $els ) {
		$a = null;
		return es_save_page( 'con-sandbox-roto', 'P', $els, 'elementor_header_footer', $a );
	}
);
ok( $r['ret'] > 0, 'el modo seguro NO bloquea la escritura: la salida de un sandbox tumbado es ejecutar algo' );
ok( has( $r['out'], 'SANDBOX APAGADO' ), 'pero avisa, y fuerte' );
ok( has( $r['out'], 'es-roto.php' ), 'nombrando el fichero culpable, que es lo unico accionable' );
ok( 'es-roto.php' === es_safe_mode_check(), 'y devuelve el motivo, no un booleano' );

/* Una vez por peticion, al reves que la aprobacion: un aviso sin aprobar es un hecho sobre UNA
   pagina y callarse esconderia las demas; el modo seguro es un hecho sobre el SITIO, y repetirlo
   por pagina enterraria las paginas debajo. */
$out2 = '';
foreach ( array( 'a', 'b', 'c' ) as $s ) {
	approve( $s );
	$rr    = grab(
		function () use ( $els, $s ) {
			$a = null;
			return es_save_page( $s, 'P', $els, 'elementor_header_footer', $a );
		}
	);
	$out2 .= $rr['out'];
}
ok( '' === $out2, 'y no lo repite en cada pagina: es un hecho del sitio, no de la pagina' );

/* Un .crashed que no es JSON tampoco se ignora: fallar cerrado es no llamar sano a lo que no se
   pudo leer. */
@unlink( $sb . '/.crashed' );
file_put_contents( $sb . '/.crashed', 'algo se rompio y nadie escribio json' );
ok( '' !== es_safe_mode_check(), 'un .crashed ilegible sigue siendo modo seguro' );
@unlink( $sb . '/.crashed' );

echo "--- aprobacion y portada: las dos reglas que solo vivian en la prosa ---\n";

wp_fake_reset();
$r = grab(
	function () use ( $els ) {
		$a = null;
		return es_save_page( 'sin-preflight', 'Sin preflight', $els, 'elementor_header_footer', $a );
	}
);
ok( $r['ret'] > 0, 'escribir sin preflight NO bloquea: un build interrumpido tiene que poder reanudarse' );
ok( has( $r['out'], 'es_overwrite_preflight' ), 'pero avisa, nombrando la funcion que faltaba' );
ok( has( $r['out'], 'sin-preflight' ), 'y el slug que se escribio sin que nadie lo viera' );

wp_fake_reset();
$r = grab(
	function () {
		return es_approval_check( 'nadie' );
	}
);
ok( false === $r['ret'], 'es_approval_check() devuelve el veredicto, no solo lo imprime' );
approve( 'aprobado' );
ok( true === es_approval_check( 'aprobado' ), 'y un slug que paso por el bloque impreso da true' );

/* El caso que un flag por peticion no ve: se aprueban cinco y se escriben seis. */
wp_fake_reset();
approve( 'uno', 'dos', 'tres', 'cuatro', 'cinco' );
$out_aprobadas = '';
foreach ( array( 'uno', 'dos', 'tres', 'cuatro', 'cinco' ) as $slug_ok ) {
	$r              = grab(
		function () use ( $els, $slug_ok ) {
			$a = null;
			return es_save_page( $slug_ok, 'P', $els, 'elementor_header_footer', $a );
		}
	);
	$out_aprobadas .= $r['out'];
}
ok( '' === $out_aprobadas, 'las cinco aprobadas se escriben en silencio' );
$r = grab(
	function () use ( $els ) {
		$a = null;
		return es_save_page( 'seis', 'La sexta', $els, 'elementor_header_footer', $a );
	}
);
ok( has( $r['out'], 'seis' ), 'y la sexta, que nadie aprobo, avisa nombrandose — un flag por peticion ya estaria callado' );

/* La portada. */
wp_fake_reset();
ok( 'nothing-built' === es_front_page_check(), 'sin paginas guardadas no hay veredicto: "/" no es asunto de este build' );
$r = grab( 'es_front_page_check' );
ok( '' === $r['out'], 'y no dice nada, porque no hay nada que reclamar' );

wp_fake_reset();
approve( 'inicio' );
grab(
	function () use ( $els ) {
		$a = null;
		return es_save_page( 'inicio', 'Inicio', $els, 'elementor_header_footer', $a );
	}
);
$r = grab( 'es_front_page_check' );
ok( 'posts' === $r['ret'], 'con paginas guardadas y el blog en "/", el veredicto es posts' );
ok( has( $r['out'], 'es_set_front_page' ), 'y avisa nombrando la funcion que cierra el hueco' );
ok( has( $r['out'], 'BLOG' ), 'diciendo lo que el visitante ve de verdad al entrar por la raiz' );

grab(
	function () {
		return es_set_front_page( 'inicio' );
	}
);
$r = grab( 'es_front_page_check' );
ok( 'page' === $r['ret'], 'puesta la portada, el veredicto pasa a page' );
ok( '' === $r['out'], 'y se calla: un sitio correcto no tiene que oir nada' );

/* Que la comprobacion este CABLEADA, que es el hallazgo entero. Con ES_AUDIT_SILENT el veredicto
   de contenedores no imprime, asi que lo unico que puede quedar en stdout es este aviso. */
wp_fake_reset();
approve( 'servicios' );
grab(
	function () use ( $els ) {
		$a = null;
		return es_save_page( 'servicios', 'Servicios', $els, 'elementor_header_footer', $a );
	}
);
$r = grab( 'es_audit_summary' );
ok( has( $r['out'], 'BLOG' ), 'es_audit_summary() lo dice: la linea que el operador tiene orden de leer antes de desplegar' );
ok( 0 === $r['ret'], 'y su entero no cambia — los llamadores ya ramifican sobre el, un quinto significado romperia uno' );

/* Lo que se guarda se anota por el slug donde ATERRIZO, no por el que se pidio. */
wp_fake_reset();
$GLOBALS['wp']['rename_to'] = 'contacto-2';
approve( 'contacto' );
grab(
	function () use ( $els ) {
		$a = null;
		return es_save_page( 'contacto', 'Contacto', $els, 'elementor_header_footer', $a );
	}
);
ok( isset( $GLOBALS['es_saved_pages']['contacto-2'] ), 'una pagina renombrada se anota en su slug real' );
ok( ! isset( $GLOBALS['es_saved_pages']['contacto'] ), 'y NO en el que se pidio: ahi contesta otra cosa' );

wp_fake_reset();
$GLOBALS['wp']['insert_ret'] = 0;
approve( 'fantasma' );
grab(
	function () use ( $els ) {
		$a = null;
		return es_save_page( 'fantasma', 'Fantasma', $els, 'elementor_header_footer', $a );
	}
);
ok( array() === $GLOBALS['es_saved_pages'], 'una escritura rechazada no anota nada: la lista es lo que sobrevivio, no lo que se intento' );

/* ---------------------------------------------------------------------------
 * La capa de tokens.
 *
 * SKILL.md lleva desde siempre diciendole al operador que "cambie las constantes
 * de paleta y tipografia" de este fichero. Nunca hubo constantes: habia 51
 * colores, 9 familias y 5 sombras escritos a mano por el medio. Estas
 * afirmaciones son lo que convierte es_tokens() en una capa de verdad y no en un
 * array decorativo que nadie lee.
 * ------------------------------------------------------------------------- */
echo "--- los tokens llegan a los datos emitidos ---\n";

/* Todo lo que hay debajo compara contra ESTO, nunca contra un color escrito a
   mano en el test. Afirmar "#0FA968" aqui seria clavar el verde por defecto en
   dos sitios, y la tarea siguiente tiene permiso para moverlo. */
$es_defaults = es_tokens();

/* ---------------------------------------------------------------------------
 * El volcado dorado.
 *
 * Nada de lo que hay comprometido protegia la identidad byte a byte que esta
 * tarea existe para establecer: la prueba vivia en un script de usar y tirar
 * que se borro. Lo que eso cuesta esta medido, no supuesto —— con las cuatro
 * suites en verde se podia cambiar el valor por defecto del acento, poner
 * Comic Sans MS de familia de titulares, colapsar border_panel dentro de
 * border (justo el colapso que la Tarea 1 prohibe), sustituir elev_hover
 * entero y cambiar el fondo de la tarjeta de caracteristica de bg a
 * surface_inverse, y TODO seguia diciendo OK. Una tarjeta blanca que se vuelve
 * casi negra pasaba todas las comprobaciones que teniamos.
 *
 * El fichero dorado convierte "fue identico una vez" en una propiedad que se
 * vuelve a comprobar en cada ejecucion. No lo congela para siempre: el paso
 * "APUNTA EL DESPLAZAMIENTO" de la Tarea 2 es exactamente regenerarlo y
 * ensenar el diff. Un cambio que aparece en el diff es una decision; uno que
 * no aparece en ningun sitio es el fallo.
 *
 * Se ejecuta en un PROCESO APARTE a proposito: el volcado tiene que ver los
 * tokens tal y como los ve un build de verdad, no como los deja este fichero
 * despues de sobrescribirlos treinta veces mas abajo.
 * ------------------------------------------------------------------------- */
$dump_php = dirname( __DIR__ ) . '/tests/tools/dump-emitted.php';
$oro_ruta = dirname( __DIR__ ) . '/tests/fixtures/emitted-golden.txt';
$salida   = null;
if ( function_exists( 'shell_exec' ) && is_file( $dump_php ) && is_file( $oro_ruta ) ) {
	$salida = shell_exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( $dump_php ) );
}
if ( null === $salida ) {
	/* Ni verde ni rojo: no se pudo comprobar. Un "no se pudo" contado como OK
	   es la misma enfermedad que este fichero entero existe para quitar. */
	fwrite( STDERR, "ENTORNO: no se pudo ejecutar tests/tools/dump-emitted.php (¿shell_exec deshabilitado?); el volcado dorado queda SIN COMPROBAR\n" );
	exit( 2 );
}
/* El arbol de trabajo es CRLF y el repositorio guarda LF (core.autocrlf=true),
   asi que el fichero dorado leido de disco y el volcado recien emitido no
   tienen por que traer el mismo salto de linea. Se normalizan los dos. */
$sin_cr  = function ( $s ) {
	return str_replace( "\r\n", "\n", (string) $s );
};
$salida  = $sin_cr( $salida );
$oro_txt = $sin_cr( file_get_contents( $oro_ruta ) );

/* Las tres trampas del volcado, afirmadas antes de compararlo con nada.
   Sin ABSPATH, es-builder.php:6-8 sale por la puerta y el volcado es de 0
   bytes —— y `cmp` de dos ficheros VACIOS pasa. */
ok( '' !== trim( $salida ), 'el volcado trae contenido: dos ficheros vacios tambien son identicos, y eso se reporta como aprobado' );
ok( substr_count( $salida, "\n" ) > 2000, 'y trae un volcado entero, no un fragmento: ' . substr_count( $salida, "\n" ) . ' lineas' );
ok( has( $salida, "===== FIN DEL VOLCADO =====\n" ), 'y termina en el centinela, asi que un volcado cortado no puede hacerse pasar por una coincidencia' );
/* El umbral de lineas de arriba dejo de significar nada en cuanto el volcado
   paso a cubrir tambien los tres ficheros hermanos: es-builder.php solo ya trae
   2913 lineas, asi que los tres podian caerse enteros y el ">2000" seguia en
   verde. Lo que hay que afirmar es que ESTAN, no que hay muchas lineas. */
ok( 2 === substr_count( $salida, '----- hermanos:' ), 'y las dos secciones cubren tambien header, footer, tienda y ficha: si los hermanos se caen, un umbral de lineas no se entera' );
ok( $oro_txt === $salida, 'lo que emite el constructor es, byte a byte, lo que dice tests/fixtures/emitted-golden.txt' );

/* ---------------------------------------------------------------------------
 * Un ayudante puede irse de la cobertura sin que nadie lo note.
 *
 * Medido: borrando `es_feature_card` de es_dump_visuals() y regenerando el
 * dorado, las cuatro suites y la auditoria seguian en verde. La lista de
 * ayudantes del volcado se mantenia A MANO, asi que el ayudante que se olvida es
 * el que deja de estar cubierto —— y, combinado con lo que la fila de literales
 * NO cazaba entonces, un ayudante nuevo no estaba protegido en ninguna de las
 * dos capas.
 *
 * Asi que "todos" se MIDE: se leen las funciones declaradas entre los dos
 * marcadores de region de es-builder.php y se exige que cada una este nombrada
 * en es_dump_visuals(). Sin lista de exentos, que es donde se escondería el
 * siguiente.
 *
 * Los tres hermanos no se enumeran aqui porque no hace falta: el volcado los
 * ejecuta ENTEROS (es_build_theme_parts / _shop_template / _product_single) y ya
 * afirma que las cuatro plantillas y el nav llegaron a _elementor_data.
 * ------------------------------------------------------------------------- */
$eb_src   = file_get_contents( dirname( __DIR__ ) . '/skills/elementor-core/assets/es-builder.php' );
$eb_lin   = explode( "\n", $eb_src );
$eb_ini   = -1;
$eb_fin   = -1;
foreach ( $eb_lin as $i => $l ) {
	if ( false !== strpos( $l, 'end of the visual layer' ) ) {
		$eb_fin = $i;   /* el ULTIMO, igual que framework-audit.php */
	}
}
foreach ( $eb_lin as $i => $l ) {
	if ( preg_match( '/^function\s+es_tokens\s*\(/', $l ) ) {
		for ( $j = $i + 1; $j < count( $eb_lin ); $j++ ) {
			if ( '}' === rtrim( $eb_lin[ $j ] ) ) {
				$eb_ini = $j;
				break;
			}
		}
		break;
	}
}
$region_fn = array();
for ( $i = $eb_ini + 1; $i < $eb_fin; $i++ ) {
	if ( preg_match( '/^function\s+(es_\w+)\s*\(/', $eb_lin[ $i ], $m ) ) {
		$region_fn[] = $m[1];
	}
}
/* Si los marcadores dejan de encontrarse, la region sale vacia y el bucle de
   abajo no comprueba NADA mientras dice OK. Eso es el fallo, no un detalle. */
ok( $eb_ini > 0 && $eb_fin > $eb_ini, 'los dos marcadores de region de es-builder.php se localizan: sin ellos lo de abajo recorreria una lista vacia' );
ok( count( $region_fn ) >= 20, 'y la region declara ' . count( $region_fn ) . ' funciones es_*, no un puñado por un regex que dejo de casar' );
$dump_src = (string) file_get_contents( $dump_php );
$sin_dump = array();
if ( preg_match( '/function es_dump_visuals\(\).*?\n}/s', $dump_src, $dvm ) ) {
	foreach ( $region_fn as $f ) {
		if ( false === strpos( $dvm[0], $f . '(' ) ) {
			$sin_dump[] = $f;
		}
	}
} else {
	$sin_dump[] = '(no se pudo leer es_dump_visuals() del volcado)';
}
ok( array() === $sin_dump, 'el volcado ejercita TODAS las funciones de la region visual: ' . ( $sin_dump ? 'fuera de cobertura -> ' . implode( ', ', $sin_dump ) : count( $region_fn ) . ' comprobadas' ) );

/* ---------------------------------------------------------------------------
 * La mascara del año, clavada estrecha.
 *
 * gmdate('Y') en el copyright del pie es el unico valor de los tres hermanos que
 * se mueve sin que nadie edite nada, asi que el volcado lo enmascara. Hoy la
 * mascara es estrecha y segura —— dos apariciones, las dos del copyright. Pero
 * nada la obligaba a serlo: ensanchandola a preg_replace('/[0-9]{2,}/','N') y
 * regenerando, todo seguia en verde y CUALQUIER numero de la seccion de hermanos
 * quedaba libre de moverse. Una mascara es un agujero autorizado en el dorado; su
 * tamaño tiene que estar afirmado, no ser una costumbre.
 * ------------------------------------------------------------------------- */
ok( 2 === substr_count( $oro_txt, '© AAAA ' ), 'la mascara del año tapa exactamente las dos lineas de copyright, ni una mas' );
ok( ! has( $oro_txt, '© ' . gmdate( 'Y' ) . ' ' ), 'y no queda ningun año sin enmascarar, que es para lo que existe' );
/* Y lo que de verdad mata al mutante que la ensancha: el dorado sigue lleno de
   numeros. Una mascara numerica general los colapsaria todos y esto se hundiria.
   El suelo es 1500 sobre las ~2020 que hay: holgado para que un cambio de valor
   normal no lo roce, y a la vez a un abismo del 0 al que caeria el mutante. No
   es un umbral que mida calidad —— mide que la mascara sigue siendo un agujero
   del tamaño de un año y no del tamaño de la seccion entera. */
$digitos = preg_match_all( '/[0-9]{2,}/', $oro_txt );
ok( $digitos > 1500, 'y el dorado sigue clavando ' . $digitos . ' numeros de dos o mas cifras: una mascara numerica general los colapsaria a N y no clavaria ninguno' );

/* ---------------------------------------------------------------------------
 * es_rgba(): el color de un token con un velo por encima.
 *
 * Siete valores metian el color de OTRO token dentro de un rgba() sin
 * tokenizar, asi que cambiar el acento no quitaba el verde: la marca se volvia
 * azul marino y el boton conservaba el halo verde. El formato es parte del
 * contrato —— este fichero lleva `0.10` y `0.5` a la vez, que NO son la misma
 * forma, y un alfa numerico reescribiria uno de los dos en silencio.
 * ------------------------------------------------------------------------- */
ok( 'rgba(15,169,104,0.55)' === es_rgba( '#0FA968', '0.55' ), 'es_rgba() escribe el triplete con el espaciado y el alfa exactos' );
ok( 'rgba(15,169,104,0.10)' === es_rgba( '#0FA968', '0.10' ), 'y respeta 0.10, que como numero seria 0.1 y moveria bytes' );
ok( 'rgba(15,169,104,0.5)' === es_rgba( '#0fa968', '0.5' ), 'y le da igual la caja del hex' );
ok( 'rgba(255,255,255,0.75)' === es_rgba( '#fff', '0.75' ), 'y entiende la forma corta de tres digitos' );
$r = grab(
	function () {
		return es_rgba( 'verde', '0.5' );
	}
);
ok( 'rgba(0,0,0,0)' === $r['ret'], 'un valor que no es hex no se convierte en un color plausible: se queda sin pintar' );
ok( has( $r['out'], 'verde' ), 'y lo dice en voz alta nombrando el valor que no supo leer' );

/* ---------------------------------------------------------------------------
 * Un unico sitio que ejercite todas las claves.
 *
 * es_card y es_cta_banner pasan por es_img(), que aqui no encuentra nada y
 * AVISA: por eso va dentro de grab(), que ademas es como el resto del fichero
 * mira lo que se imprime.
 *
 * es_uid_reset() al entrar porque los ids son un contador global: sin el, dos
 * llamadas seguidas devuelven cadenas distintas aunque no haya cambiado ni un
 * token, y la comparacion numerica de mas abajo no podria distinguir "esta
 * clave la lee alguien" de "los ids han avanzado".
 * ------------------------------------------------------------------------- */
function es_token_probe() {
	es_uid_reset( 'probe' );
	return es_card_hover_css() . es_products_css() . json_encode(
		array(
			es_eyebrow( 'etiqueta' ),
			es_p( 'texto' ),
			es_h( 'titulo' ),
			es_btn( 'Comprar', '#', 'primary' ),
			es_btn( 'Comprar', '#', 'dark' ),
			es_btn( 'Comprar', '#', 'outline' ),
			es_btn( 'Comprar', '#', 'outline-light' ),
			es_card( 'foto', 'titulo', 'texto' ),
			es_cta_banner( 'foto', 'titulo', 'texto', 'Ir', '#' ),
			es_iconbox( 'fas fa-check', 'titulo', 'texto' ),
			es_feature_card( 'fas fa-check', 'titulo', 'texto' ),
		)
	);
}

/* La capa solo es real si cambiar un token cambia lo que se emite. Un token que
   nadie lee devuelve todos los proyectos al mismo aspecto. */
es_tokens( array( 'accent' => '#B4001F' ) );
$json = json_encode( es_btn( 'Comprar', '#', 'primary' ) );
ok( has( $json, '#B4001F' ), 'el acento del token llega al boton primario' );
ok( ! has( $json, $es_defaults['accent'] ), 'el acento por defecto ya no aparece tras el override' );

/* Y la MISMA pregunta en la otra forma en la que se escribe un color. Afirmar
   solo el hex es la razon de que esto se colara: siete tokens llevaban el verde
   dentro de un rgba() —— accent_wash, elev_accent, elev_accent_cart —— y
   sobrevivian intactos a cambiar el acento. El hex desaparecia, el halo no.
   El triplete se calcula del valor por defecto, nunca se escribe a mano aqui. */
$triple_defecto = substr( es_rgba( $es_defaults['accent'], 'X' ), 5, -3 );
ok( '15,169,104' === $triple_defecto, 'el triplete de referencia sale del token, no de un numero escrito en el test' );
es_tokens( array( 'accent' => '#B4001F' ) );
$todo = grab( 'es_token_probe' );
ok( ! has( $todo['ret'], $es_defaults['accent'] ), 'cambiar el acento no deja ni una vez el hex por defecto en toda la pagina' );
ok( ! has( $todo['ret'], $triple_defecto ), 'ni una sola vez el mismo verde escrito como triplete rgba dentro de una sombra' );

/* La familia es el otro literal que se repite por todo el fichero. */
es_tokens( array( 'font_body' => 'Inter' ) );
$body = json_encode( array( es_p( 'texto' ), es_btn( 'Comprar', '#', 'outline' ) ) );
ok( has( $body, 'Inter' ), 'la familia de cuerpo sale del token, no de cada funcion' );
ok( ! has( $body, $es_defaults['font_body'] ), 'y la de por defecto ya no aparece en ningun sitio' );

/* Una clave mal escrita AVISA en vez de devolver algo plausible: un color vacio
   en Elementor se ve como "el tema decidio", no como un error. */
$r = grab(
	function () {
		return es_t( 'acento' );
	}
);
ok( '' === $r['ret'], 'una clave inexistente devuelve cadena vacia, no null ni la clave' );
ok( has( $r['out'], 'acento' ), 'y lo dice en voz alta nombrando la clave que se escribio mal' );

/* El otro lado, y el mas probable de los dos: la clave mal escrita en el
   OVERRIDE. es_t() solo vigila la LECTURA, asi que es_tokens(['acento'=>...])
   se aceptaba entero, no cambiaba nada y no avisaba de nada —— en el unico
   punto de edicion que toda esta capa existe para crear. */
$r = grab(
	function () {
		es_tokens( array( 'acento' => '#001133' ) );
		return es_t( 'accent' );
	}
);
ok( has( $r['out'], 'acento' ), 'un override con una clave que no es token avisa nombrandola' );
ok( '#001133' !== $r['ret'], 'y deja claro por que hace falta el aviso: el override no ha cambiado el acento' );

/* ---------------------------------------------------------------------------
 * La comprobacion estructural, y la razon de que exista.
 *
 * Una afirmacion por clave escrita a mano solo cubre las claves que alguien se
 * acordo de escribir, y el literal que sobrevive es siempre el del sitio en el
 * que nadie penso. Esto sustituye TODAS las claves a la vez por centinelas y
 * pregunta dos cosas de cada una: que su centinela llegue a los datos (la clave
 * se lee en algun sitio) y que su valor por defecto desaparezca (ningun sitio
 * de llamada se quedo el literal escrito a mano). Crece sola con las claves que
 * anadan las tareas siguientes.
 *
 * Los centinelas van nombrados por la clave, no numerados: anadir un token
 * renumeraba —— y por tanto ensuciaba —— todas las demas lineas del volcado
 * dorado. El `_Z` final es lo que impide que `accent` case dentro de
 * `ZTOK_accent_hover_Z`.
 * ------------------------------------------------------------------------- */
$sentinelas = array();
$numericas  = array();
$raras      = array();
foreach ( $es_defaults as $clave => $valor ) {
	if ( is_string( $valor ) ) {
		$sentinelas[ $clave ] = 'ZTOK_' . $clave . '_Z';
	} elseif ( is_int( $valor ) || is_float( $valor ) ) {
		$numericas[ $clave ] = $valor;
	} else {
		$raras[] = $clave;
	}
}
/* Un token que no es ni texto ni numero se caia por el `is_string()` de antes y
   se quedaba SIN cubrir en silencio. Aqui no se salta nada: o esta en uno de
   los dos grupos, o esto se pone rojo. */
ok( array() === $raras, 'ningun token se escapa de la comprobacion por no ser ni texto ni numero: ' . ( $raras ? implode( ', ', $raras ) : 'ninguno' ) );
ok( count( $sentinelas ) + count( $numericas ) === count( $es_defaults ), 'y los dos grupos suman las ' . count( $es_defaults ) . ' claves que hay' );

es_tokens( $sentinelas + $es_defaults );
$probe    = grab( 'es_token_probe' );
$emitido  = $probe['ret'];
$sin_leer = array();
$literal  = array();

/* Un valor de token puede coincidir, byte a byte, con gramatica CSS que no
   tiene nada que ver con el. En cuanto la Tarea 2 anada 'elev_rest' => 'none',
   `display:none!important` de es_products_css() (es-builder.php:337) ya esta en
   la salida, y la comprobacion de literales se pone ROJA sobre codigo CORRECTO.
   La salida barata para el siguiente implementador seria aflojar la afirmacion,
   que es justo lo que prohibe la Restriccion Global 1.
   La misma exposicion existe hoy para `auto`, `cover`, `center`, `solid`,
   `hidden`, `column`, `full`, `boxed`, `classic` y `custom`: todas estan ya en
   la salida sin ser el valor de ningun token.
   Asi que el residuo se DECLARA: cuantas veces aparecen esos bytes sin que
   ningun token los haya puesto, y por que. Declararlo es una decision visible;
   no declararlo es el fallo. Y se compara con ===, no con <=, para que un sitio
   de llamada que se quede el literal escrito a mano suba el contador por encima
   del residuo y esto se ponga rojo igual.
   LIMITE HONESTO: una busqueda de subcadenas no puede distinguir `display:none`
   de un 'none' escrito a mano dentro del mismo blob de CSS. Esta tabla es el
   limite real de la comprobacion, no un adorno; lo que la sostiene es que cada
   linea obliga a escribir POR QUE. */
$residuo = array(
	/* valor => array( cuantas veces, de donde salen ) */
	'none' => array( 1, 'display:none!important sobre a.added_to_cart en es_products_css(): esconde el enlace "Ver carrito" redundante, y no tiene nada que ver con elev_rest, que es la sombra en reposo' ),
);
/* "Lo lee alguien" cambio de significado en cuanto el build dejo de ser un solo
   fichero. es_token_probe() solo ejercita los ayudantes de es-builder.php, asi
   que un token que solo leen la cabecera, el pie, la tienda o la ficha de
   producto salia aqui como SIN LEER —— y la salida barata era eximirlo, que es
   exactamente lo que prohibe la Restriccion Global 1.
   El volcado dorado ya trae la respuesta: su segunda seccion es los CUATRO
   ficheros con todos los tokens sustituidos por centinelas. Si el centinela de
   una clave aparece ahi, alguien la lee. Esto ENSANCHA la comprobacion a la
   superficie real del build; no afloja nada, porque una clave que no lee nadie
   en ninguno de los cuatro sigue poniendo esto rojo. */
$oro_centinelas = strstr( $oro_txt, 'TOKENS SUSTITUIDOS POR CENTINELAS' );
ok( '' !== (string) $oro_centinelas, 'el volcado dorado trae su seccion de centinelas: sin ella lo de abajo no comprueba nada y pasaria igual' );
foreach ( $sentinelas as $clave => $centinela ) {
	if ( ! has( $emitido, $centinela ) && ! has( (string) $oro_centinelas, $centinela ) ) {
		$sin_leer[] = $clave;
	}
	$valor    = $es_defaults[ $clave ];
	$esperado = isset( $residuo[ $valor ] ) ? $residuo[ $valor ][0] : 0;
	$veces    = substr_count( $emitido, $valor );
	if ( $veces !== $esperado ) {
		$literal[] = $clave . ' (' . $veces . ' apariciones, ' . $esperado . ' declaradas)';
	}
}
/* Y una declaracion de residuo que ya no corresponde a ningun token es basura
   que solo puede tapar el proximo fallo. */
$residuo_muerto = array();
foreach ( $residuo as $valor => $porque ) {
	if ( ! in_array( $valor, $es_defaults, true ) ) {
		$residuo_muerto[] = $valor;
	}
}
ok( array() === $sin_leer, 'todas las claves de texto las lee alguien: ' . ( $sin_leer ? 'sin leer -> ' . implode( ', ', $sin_leer ) : 'ninguna sobra' ) );
ok( array() === $literal, 'ningun sitio de llamada se quedo el literal: ' . ( $literal ? 'todavia escrito a mano -> ' . implode( ', ', $literal ) . ' | si son bytes de gramatica CSS y no del token, declaralo en $residuo con el motivo' : 'ninguno' ) );
ok( array() === $residuo_muerto, 'no hay residuos declarados que ya no correspondan a ningun token: ' . ( $residuo_muerto ? implode( ', ', $residuo_muerto ) : 'ninguno' ) );

/* Las claves NUMERICAS no admiten un centinela de texto: alimentan aritmetica,
   y `16 * 1.333` no deja los bytes de ningun centinela en ningun sitio. Por eso
   el `is_string()` que habia aqui antes cubria CERO de las claves que trae la
   Tarea 2 —— anadir `radius => 10` y `sp_scale => 1.0` que no lee nadie dejaba
   la suite en verde, mientras que el equivalente de texto moria bien.
   La pregunta que importa es la misma —— ¿la lee alguien? —— y se responde
   moviendo la clave a un valor fuera de rango y exigiendo que la salida CAMBIE.
   Eso vale igual para un numero que se emite tal cual y para uno del que se
   deriva otro, que es lo que un centinela literal no aguantaria.
   Y se mueve en las DOS direcciones, que es lo que la primera version de esto
   no hacia. Solo subia a 9973, y hay una forma de token para la que subir no
   dice nada: un TOPE. `fs_h1_max` recorta por arriba, este fichero no emite
   ningun paso que llegue al tope, y subirlo a 9973 deja la salida intacta ——
   asi que una clave que SI lee es_fs() se reportaba como que no la lee nadie.
   Bajarla a 1 recorta todos los tamanos y la salida se mueve entera.
   Esto no afloja nada: la pregunta sigue siendo "¿la lee alguien?", y una clave
   que no lee nadie no mueve la salida en NINGUNA de las dos direcciones. Lo que
   cambia es que ahora la pregunta se responde bien tambien para los topes. */
es_tokens( $es_defaults );
$referencia         = grab( 'es_token_probe' );
$numericas_sin_leer = array();
foreach ( $numericas as $clave => $valor ) {
	$movio = false;
	foreach ( is_int( $valor ) ? array( 9973, 1 ) : array( 9973.0, 0.5 ) as $fuera ) {
		$movido           = $es_defaults;
		$movido[ $clave ] = $fuera;
		es_tokens( $movido );
		$m = grab( 'es_token_probe' );
		if ( $m['ret'] !== $referencia['ret'] ) {
			$movio = true;
		}
	}
	if ( ! $movio ) {
		$numericas_sin_leer[] = $clave;
	}
}
ok( array() === $numericas_sin_leer, 'mover una clave numerica fuera de rango mueve los datos emitidos: ' . ( $numericas_sin_leer ? 'no la lee nadie -> ' . implode( ', ', $numericas_sin_leer ) : count( $numericas ) . ' claves numericas comprobadas' ) );

/* Y el otro lado de lo mismo: ninguna funcion pide una clave que no existe.
   Sin esto, borrar una entrada de es_tokens() manda un color VACIO a Elementor
   —— que se ve como "asi lo quiso el tema", no como un fallo —— y las dos
   comprobaciones de arriba ni se enteran, porque la clave ya no esta en la lista
   que recorren. es_img() tambien avisa aqui (no hay imagenes en este fixture),
   asi que se mira el aviso concreto y no el buffer entero. */
ok( ! has( $probe['out'], 'es_t(' ), 'construir una pagina no pide ninguna clave inexistente' );

/* ---------------------------------------------------------------------------
 * Roles, no apariencias.
 *
 * El boton fantasma vive sobre un heroe OSCURO. Su relleno de hover leia
 * es_t('bg') —— el color de PAGINA —— y era invisible porque hoy bg y la tinta
 * inversa son los dos #FFFFFF. Un cliente con fondo crema recibia un relleno
 * crema sobre un heroe casi negro.
 *
 * Esta comprobacion no generaliza: es la pregunta concreta "¿depende este
 * elemento de un token cuyo rol no le toca?", y hay que escribirla por sitio.
 * Lo que si escala es la seccion de centinelas del volcado dorado, donde cada
 * ajuste aparece con el NOMBRE del rol que lo alimenta y una confusion de roles
 * deja de ser invisible en la revision.
 * ------------------------------------------------------------------------- */
/* Este par se escribia antes moviendo SOLO bg y exigiendo que el boton no lo
   tocase. Ya no se puede preguntar asi, y el motivo es una decision, no un
   estorbo: `on_inverse` es ahora DERIVADO de `bg` —— la tinta que va sobre la
   superficie inversa es el suelo de la propia pagina —— asi que mover bg mueve
   los dos y "no toma nada del color de pagina" es literalmente imposible de
   cumplir. Esa igualdad es CORRECTA: sobre el ground `warm`, la tinta del heroe
   oscuro tiene que ser la crema de la pagina (#FFF3E3, 15.3:1 sobre la
   superficie inversa), no un blanco puro que no esta en la paleta.
   Lo que el fallo original rompia sigue siendo cierto y sigue afirmado aqui: el
   boton lee el ROL. Se separan los dos valores a mano —— un override explicito
   gana a la derivacion —— y se pregunta en las DOS direcciones a la vez, que es
   mas fuerte que las dos afirmaciones sueltas de antes. */
es_tokens( array( 'bg' => '#FCF7EE', 'on_inverse' => '#E3F1FF' ) );
$fantasma = json_encode( es_btn( 'Ir', '#', 'outline-light' ) );
ok( ! has( $fantasma, '#FCF7EE' ), 'el boton fantasma sobre heroe oscuro no toma NADA del color de pagina cuando los dos roles se separan' );
ok( has( $fantasma, '#E3F1FF' ), 'lo toma de la tinta inversa, que es el rol que le corresponde' );

/* Los tokens derivados existen para que UN punto de edicion sea de verdad uno:
   cambiar el acento tiene que mover tambien todo lo que lo lleva dentro. */
es_tokens( array( 'accent' => '#123456' ) );
ok( 'rgba(18,52,86,0.10)' === es_t( 'accent_wash' ), 'cambiar el acento mueve el tinte derivado' );
ok( '0 12px 26px -10px rgba(18,52,86,0.55)' === es_t( 'elev_accent' ), 'y el halo, conservando su geometria' );
/* El estado HOVER es la otra mitad de lo mismo, y la que faltaba: accent_hover
   era un verde oscuro elegido a mano al lado de un acento que el cliente SI
   cambia, asi que una marca azul marino recibia un boton azul y un hover verde.
   Es la enfermedad de es_rgba() un nivel mas arriba —— un punto de edicion que
   solo es uno si te acuerdas tambien del otro.
   Sin esta afirmacion el unico que cazaba una es_shade() rota era el volcado
   dorado, y un volcado se regenera; una propiedad, no. */
ok( '#0F2A46' === es_t( 'accent_hover' ), 'el hover del acento se deriva del acento: cambiarlo a azul da un azul mas oscuro, no el verde de la casa' );
es_tokens( array( 'border' => '#123456' ) );
ok( '#113150' === es_t( 'border_hover' ), 'y el filete hace lo mismo con el suyo' );
/* Y el precio de haber hecho esto derivado: CERO en la marca por defecto. El
   factor 0.815 reproduce exactamente el #0C8A55 que estaba escrito a mano, asi
   que la derivacion no movio ni un byte del verde de la casa —— solo arreglo a
   todas las demas marcas. Clavado aqui para que se note si alguien lo toca. */
es_tokens( $es_defaults );
ok( '#0C8A55' === es_t( 'accent_hover' ), 'y con la marca por defecto sale el mismo #0C8A55 de siempre: derivarlo no costo bytes' );
/* La entrada ilegible avisa en vez de devolver un color plausible, igual que
   es_rgba(): un hover vacio en Elementor se ve como "asi lo quiso el tema". */
$r = grab(
	function () {
		return es_shade( 'azul', 0.8 );
	}
);
ok( '' === $r['ret'], 'un valor que no es hex no se convierte en un color plausible al oscurecerlo' );
ok( has( $r['out'], 'azul' ), 'y lo dice en voz alta nombrando el valor que no supo leer' );
/* Y el que ELIGE la direccion tiene que avisar UNA vez, no dos. Construye dos
   candidatos con es_shade() y es_mix(), y los dos avisan por su cuenta: si no
   leyera el valor antes con es_lum() —— el unico que devuelve null callado, y su
   docblock dice que existe para esto —— una sola errata de tecleo saldria por
   pantalla dos veces nombrando lo mismo. */
$r = grab(
	function () {
		return es_hover_of( 'azul', 0.815, '#FFFFFF' );
	}
);
ok( '' === $r['ret'], 'un valor que no es hex tampoco se convierte en un hover plausible' );
ok( 1 === substr_count( $r['out'], 'azul' ), 'y lo avisa UNA sola vez, no una por cada candidato que iba a construir' );
/* El otro lado del mismo guardado: cuando lo ilegible es el SUELO, quien avisa
   es la pasada de mezclas que lo leyo antes. Aqui se cae al oscurecido de
   siempre —— la mitad de la tabla que seguia siendo correcta —— en vez de dejar
   el estado sin pintar por un fondo que ya tiene quien lo denuncie. */
$r = grab(
	function () {
		return es_hover_of( '#0FA968', 0.815, 'azul' );
	}
);
ok( '#0C8A55' === $r['ret'], 'con un fondo ilegible sigue oscureciendo, que es lo que hacia antes de saber medir' );
ok( '' === $r['out'], 'y se calla: el aviso por ese fondo lo da la pasada de mezclas, que lo lee primero' );
es_tokens( array( 'accent' => '#123456' ) );

/* ...y quien tenga un halo que NO es su acento tiene que poder decirlo. */
es_tokens( array( 'accent' => '#123456', 'elev_accent' => '0 1px 2px rgba(0,0,0,0.9)' ) );
ok( '0 1px 2px rgba(0,0,0,0.9)' === es_t( 'elev_accent' ), 'un override explicito de una clave derivada gana a la derivacion' );

/* ---------------------------------------------------------------------------
 * Los ejes se mueven.
 *
 * La afirmacion falsable de todo este esfuerzo: cambia el ratio y se mueve la
 * jerarquia entera; cambia el multiplicador y se mueve el ritmo entero. Si esto
 * pasara con las posiciones de eje intercambiadas y la salida no cambiase, el
 * eje seria decorativo —— que es exactamente lo que era antes de esta tarea.
 * ------------------------------------------------------------------------- */
echo "--- los ejes mueven de verdad los datos emitidos ---\n";

es_tokens( array( 'type_ratio' => 1.618, 'fs_h1_max' => 120 ) );
$monumental = es_fs( 3 );
es_tokens( array( 'type_ratio' => 1.200, 'fs_h1_max' => 48 ) );
$contenido = es_fs( 3 );
ok( $monumental > $contenido, 'un ratio monumental produce un display mayor que uno contenido' );
/* Y con los numeros clavados, porque `$a > $b` tambien lo cumple una funcion que
   devuelva `$step * 2`: eso mide el orden, no la escala. */
ok( 67.8 === $monumental && 27.6 === $contenido, 'y los pasos son los que dicta el ratio, no solo el orden: 67.8 contra 27.6' );

/* El tope, afirmado donde MUERDE. La version obvia —— `$monumental <= 120 &&
   $contenido <= 48` —— es vacua: los dos pasos valen 67.8 y 27.6, o sea que
   pasa igual de verde con el min() borrado, y el mutante que quita el tope
   sobrevive sin que nadie se entere. En la posicion `classic` el tope no entra
   hasta el paso 5 y este fichero no emite nada por encima del 3, asi que hay
   que preguntarselo a un paso que si lo alcance. */
es_tokens( array( 'type_ratio' => 1.618, 'fs_h1_max' => 120 ) );
ok( 120.0 === es_fs( 8 ), 'un paso que se sale por arriba se recorta en el tope de su propia posicion (120)' );
ok( 67.8 === es_fs( 3 ), 'y un paso que cabe NO se toca: el tope recorta, no aplana' );
es_tokens( array( 'type_ratio' => 1.200, 'fs_h1_max' => 48 ) );
ok( 48.0 === es_fs( 8 ), 'y cada posicion respeta su propio tope, no uno compartido (48)' );

es_tokens( array( 'sp_scale' => 1.7 ) );
$aireado = es_sp( 88 );
$caja_aireada = es_box( 88, 24, 88, 24 );
es_tokens( array( 'sp_scale' => 0.8 ) );
$apretado = es_sp( 88 );
ok( $aireado > $apretado, 'la densidad generosa deja mas aire que la compacta' );
ok( 150 === $aireado && 70 === $apretado, 'y los valores derivados son los esperados, no solo el orden' );
ok( '150' === $caja_aireada['top'], 'y la densidad entra DENTRO de es_box(), asi que ningun sitio de llamada puede olvidarse' );

/* Las dos guardas de es_box(), que son la diferencia entre escalar el ritmo y
   escalar cosas que no son ritmo. */
es_tokens( array( 'sp_scale' => 1.7 ) );
$pct = es_box( 5, 0, 5, 0, '%' );
ok( '5' === $pct['top'], 'un porcentaje no se multiplica por la densidad: ya es relativo a otra cosa, y multiplicarlo no es un hueco menor sino otro layout' );
$borde = es_box_unscaled( 1, 1, 1, 1 );
ok( '1' === $borde['top'], 'y una anchura de borde tampoco: a densidad 1.7 un filete de 1px se redondearia a 2px, que es un borde mas gordo, no mas aire —— y dejaria la posicion `hairline` sin poder expresarse' );

/* La sombra en reposo, que antes NO EXISTIA: solo habia cinco sombras de hover
   y nada debajo, y por eso `hairline` y `soft-shadow` no se podian expresar. */
es_tokens( array( 'elev_rest' => '0 0 0 1px #E5E7E5' ) );
$reposo = es_card_hover_css() . es_products_css() . json_encode( es_feature_card( 'fas fa-check', 'titulo', 'texto' ) );
ok( 3 === substr_count( $reposo, 'box-shadow:0 0 0 1px #E5E7E5' ), 'las tres recetas de tarjeta —— image-box, rejilla de productos y feature card —— cogen la sombra en reposo' );
es_tokens( array( 'elev_rest' => 'none' ) );
$sin_reposo = es_card_hover_css() . es_products_css() . json_encode( es_feature_card( 'fas fa-check', 'titulo', 'texto' ) );
ok( ! has( $sin_reposo, '0 0 0 1px #E5E7E5' ), 'y volver a `none` las deja planas: la sombra en reposo sale del token, no esta clavada' );

/* ---------------------------------------------------------------------------
 * Los titulares llevan la escala.
 *
 * es_h() emitia `title`, `header_size` y `_margin`, y nada mas. Medido antes de
 * arreglarlo: es_h('T','h1') se diferenciaba entre PERS-EDITORIAL y PERS-DIRECT
 * SOLO en `_margin.bottom`; el titular mas grande que sabia emitir el build
 * entero era el h2 del banner CTA; y `display_lh` —— un token que existe para
 * llevar el eje de escala —— tenia exactamente UN lector en todo el arbol.
 * Mientras tanto la cadena promete que el build reproduce una maqueta aprobada
 * que pinta el h1 a 88px. El elemento mas visible de la pagina era el unico que
 * no podia cumplir su propio contrato.
 * ------------------------------------------------------------------------- */
echo "--- los titulares llevan la escala hasta el tope de su posicion ---\n";

/* La comprobacion que lo ancla todo, y NO contra si misma: los numeros de la
   derecha son la tabla "MEASURED in a browser at a 16px root" de
   design-system.md, copiada tal cual. Si es_fs_at() y esa tabla se separan, o el
   build dejo de reproducir la maqueta o la maqueta dejo de medir lo que dice. */
es_tokens( array( 'type_ratio' => 1.500, 'display_lh' => 0.95, 'fs_h1_max' => 88 ) );
ok( 54.0 === es_fs_at( 3, 430 ) && 67.5 === es_fs_at( 3, 768 ) && 88.0 === es_fs_at( 3, 1280 ), 'editorial h1 reproduce la tabla medida de design-system.md: 54 / 67.5 / 88' );
es_tokens( array( 'type_ratio' => 1.618, 'display_lh' => 0.82, 'fs_h1_max' => 120 ) );
ok( 67.8 === es_fs_at( 3, 430 ) && 88.5 === es_fs_at( 3, 768 ) && 120.0 === es_fs_at( 3, 1280 ), 'y monumental tambien: 67.8 / 88.5 / 120' );
/* El tope se alcanza a 1280 y se queda ahi. Sin esto, un es_fs_at() que
   devolviese siempre el SUELO pasaria las dos afirmaciones de arriba en su
   columna de 430 y fallaria callando en la unica que se ve en un portatil. */
ok( 120.0 === es_fs_at( 3, 1920 ), 'y por encima de 1280 se queda en el tope, no sigue creciendo' );
ok( 67.8 === es_fs_at( 3, 100 ), 'y por debajo de 430 se queda en el suelo, no sigue encogiendo' );

/* La afirmacion falsable de todo el Grupo C. Dos anclas, el MISMO h1, y la
   pregunta es si se distinguen en el TAMANO. Antes se distinguian solo en el
   margen inferior, que es lo mismo que no distinguirse. */
es_tokens( array( 'type_ratio' => 1.500, 'display_lh' => 0.95, 'fs_h1_max' => 88 ) );
$h1_editorial = es_h( 'Titular', 'h1' );
es_tokens( array( 'type_ratio' => 1.200, 'display_lh' => 1.25, 'fs_h1_max' => 48 ) );
$h1_directo   = es_h( 'Titular', 'h1' );
ok( 88.0 === $h1_editorial['settings']['typography_font_size']['size'], 'un h1 editorial sale a 88px en escritorio, que es lo que pinta la maqueta aprobada' );
ok( 48.0 === $h1_directo['settings']['typography_font_size']['size'], 'y uno contenido a 48px: la misma llamada, dos sitios que no se confunden' );
ok( $h1_editorial['settings']['typography_line_height'] !== $h1_directo['settings']['typography_line_height'], 'y el leading del display tambien se mueve, que es la otra mitad del eje' );

/* La jerarquia, en los tres puntos de ruptura. Un h3 mas grande que un h2 en
   tablet es exactamente el fallo que produce sobrescribir solo el tamano de
   escritorio, y es un fallo que solo se ve en una tablet. */
es_tokens( $es_defaults );
foreach ( array( '', '_tablet', '_mobile' ) as $bp ) {
	$t1 = es_h( 'a', 'h1' )['settings'][ 'typography_font_size' . $bp ]['size'];
	$t2 = es_h( 'a', 'h2' )['settings'][ 'typography_font_size' . $bp ]['size'];
	$t3 = es_h( 'a', 'h3' )['settings'][ 'typography_font_size' . $bp ]['size'];
	ok( $t1 > $t2 && $t2 > $t3, 'h1 > h2 > h3 en ' . ( '' === $bp ? 'escritorio' : trim( $bp, '_' ) ) . ": $t1 / $t2 / $t3" );
}
/* Y la jerarquia entre puntos de ruptura, que es la que se rompe sola: cada
   titular tiene que ser MAS grande cuanto mas ancha la pantalla. */
$h2 = es_h( 'a' )['settings'];
ok( $h2['typography_font_size']['size'] > $h2['typography_font_size_tablet']['size'] && $h2['typography_font_size_tablet']['size'] > $h2['typography_font_size_mobile']['size'], 'y cada titular crece de movil a tablet a escritorio, nunca al reves' );

/* El leading: display_lh en los tags de display, plano en h3. */
ok( es_t( 'display_lh' ) === es_h( 'a', 'h1' )['settings']['typography_line_height']['size'], 'h1 toma el leading del eje' );
ok( es_t( 'display_lh' ) === es_h( 'a', 'h2' )['settings']['typography_line_height']['size'], 'y h2 tambien' );
ok( 1.25 === es_h( 'a', 'h3' )['settings']['typography_line_height']['size'], 'y h3 se queda en el 1.25 plano que design-system.md le fija: no es un eje' );
ok( es_t( 'font_head' ) === es_h( 'a' )['settings']['typography_font_family'], 'y la familia de titulares sale del token' );

/* $extra gana, y gana ENTERO. Sobrescribir solo el tamano de escritorio y
   heredar los derivados de tablet/movil deja una tarjeta MAS grande en tablet
   que en escritorio —— que es lo que pasaria con es_feature_card(), el unico
   sitio del arbol que sobrescribe el tamano de un h3. */
$tarjeta = es_h( 'a', 'h3', array( 'typography_font_size' => es_size( 19 ) ) );
ok( 19 === $tarjeta['settings']['typography_font_size']['size'], 'un tamano explicito gana al derivado' );
ok( ! isset( $tarjeta['settings']['typography_font_size_tablet'] ), 'y retira el derivado de tablet, que si no saldria MAS grande que el explicito de escritorio' );
ok( ! isset( $tarjeta['settings']['typography_font_size_mobile'] ), 'y el de movil' );
/* Pero quien SI dice los tres se queda con los tres. */
$tres = es_h( 'a', 'h3', array( 'typography_font_size' => es_size( 19 ), 'typography_font_size_tablet' => es_size( 18 ), 'typography_font_size_mobile' => es_size( 17 ) ) );
ok( 18 === $tres['settings']['typography_font_size_tablet']['size'], 'y quien declara los tres puntos de ruptura se queda con los tres' );
/* Y el resto de la tipografia sigue llegando: retirar el tamano no es retirar
   la familia ni el leading. */
ok( es_t( 'font_head' ) === $tarjeta['settings']['typography_font_family'], 'y sobrescribir el tamano no descuelga la familia del token' );

/* Un tag fuera de la escala AVISA en vez de emitir un titular sin tamano, que
   es el fallo original de esta funcion con otra etiqueta. */
$r = grab(
	function () {
		return es_h( 'a', 'h4' );
	}
);
ok( has( $r['out'], 'h4' ), 'un tag que la escala no define avisa nombrandolo' );
ok( ! isset( $r['ret']['settings']['typography_font_size'] ), 'y no se inventa un tamano: emitir un paso a ojo seria el mismo fallo con otra cara' );
es_tokens( $es_defaults );

/* ---------------------------------------------------------------------------
 * El eje de ground, medido en contraste y no en "el valor se ha movido".
 *
 * El eje documentaba TRES tokens —— bg, bg_alt y text —— y el build tenia al
 * menos SEIS colores que dependen del ground. Los otros tres se quedaban con su
 * valor de pagina blanca en todas las posiciones. Medido sobre los tokens
 * emitidos con el ground `ink` que design-system.md documenta (bg #0E1113,
 * text #F4F6F7):
 *
 *   muted           #6A6F6C   3.70:1  por debajo de AA —— y es_p() pinta con el
 *                                      TODO el texto de cuerpo
 *   text_soft       #4A4F4C   2.27:1  por debajo de AA
 *   surface_inverse #15181A   1.06:1  el boton `dark` invisible en su pagina
 *   border          #E5E7E5  15.24:1  un filete casi BLANCO sobre casi negro
 *
 * design-system.md:263 dice la regla que eso rompe con sus propias palabras ——
 * "each pair was contrast-checked against its OWN --c-bg, not against white" ——
 * y estaba puesta en practica para --c-text y para nada mas.
 *
 * Lo que se afirma aqui es el CONTRASTE, no que el valor cambie. `muted !==
 * '#6A6F6C'` lo cumple un valor que sigue sin leerse; 4.5:1 no.
 * ------------------------------------------------------------------------- */
echo "--- el ground mueve TODOS los colores que dependen de el, con contraste medido ---\n";

/* WCAG 2.x relative luminance y ratio de contraste, escritos aqui y no en el
   build: es una herramienta de afirmacion, no algo que se emita. */
function wcag_lum( $hex ) {
	$h = ltrim( (string) $hex, '#' );
	$s = 0.0;
	$k = array( 0.2126, 0.7152, 0.0722 );
	for ( $i = 0; $i < 3; $i++ ) {
		$c  = hexdec( substr( $h, $i * 2, 2 ) ) / 255;
		$c  = $c <= 0.03928 ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 );
		$s += $k[ $i ] * $c;
	}
	return $s;
}
function wcag_ratio( $a, $b ) {
	$x = wcag_lum( $a );
	$y = wcag_lum( $b );
	return round( ( max( $x, $y ) + 0.05 ) / ( min( $x, $y ) + 0.05 ), 2 );
}

/* Las nueve posiciones se LEEN de design-system.md, no se copian aqui. Copiarlas
   seria clavar los mismos numeros en dos sitios y perder justo la pregunta que
   importa: ¿sigue el build en la posicion que la referencia documenta? Asi es
   como bg_alt se habia ido a #F4F5F3, que no esta en ninguna fila de esa tabla. */
$ds_ruta = dirname( __DIR__ ) . '/skills/web-templates/references/design-system.md';
$suelos  = array();
foreach ( explode( "\n", (string) file_get_contents( $ds_ruta ) ) as $linea ) {
	$linea = trim( $linea );
	if ( '' === $linea || '|' !== $linea[0] ) {
		continue;
	}
	$celdas = array_map(
		function ( $c ) {
			return trim( trim( $c ), '`' );
		},
		explode( '|', trim( $linea, '|' ) )
	);
	/* LAS TRES celdas de color, no solo la primera. Esto pedia un hex en la
	   columna de `--c-bg` y se creia lo que hubiera en las otras dos, asi que
	   CUALQUIER tabla del fichero cuya primera celda sea el nombre de un ground
	   redefinia el ground entero —— y la ultima que apareciera ganaba. Medido: al
	   documentar `--c-on-accent` con una tabla de forma
	   `| ground | acento | tinta | ratio |` este bucle se quedo con
	   bg=#0FA968 / text=5.86:1 y quince filas se pusieron rojas. Se pusieron rojas
	   porque los valores intrusos no contrastaban; una tabla con hexes plausibles
	   en las tres columnas habria redefinido el ground EN SILENCIO y el bucle de
	   abajo habria medido contra una referencia que no es la del eje.
	   La tabla del eje tiene tres colores seguidos; ninguna otra del fichero los
	   tiene, y esa es la forma que se exige aqui.
	   NUEVE posiciones, no cuatro —— style-catalog PR 3a: `paper`/`warm`/`cool` se
	   quedan donde estaban, `ink` tambien, y se suman `cream`/`earth`/`saturated`
	   (claras) y `ink-warm`/`ink-cool` (oscuras). El hueco que este axis tapaba —
	   tres de cuatro fondos casi blancos — se cierra con posiciones nuevas, no
	   renombrando las viejas: `ink` sigue siendo `ink` porque framework-audit.php's
	   `nm_axes()` y los cinco anchors de design-personalities.md ya lo declaraban por
	   ese nombre cuando se escribio este comentario, y tocarlo era trabajo de Slice 4,
	   no de PR 3a. style-catalog PR 4c ya hizo ese trabajo: `nm_axes()` ahora declara
	   estas mismas nueve posiciones y el catalogo (ocho `STY-*.md`, design-personalities.md
	   retirado) las usa por el mismo nombre, asi que renombrar `ink` seguiria sin tener
	   beneficio -- esa es la parte de este razonamiento que no caduca. */
	if ( count( $celdas ) >= 4
		&& in_array( $celdas[0], array( 'paper', 'warm', 'cool', 'cream', 'earth', 'saturated', 'ink', 'ink-warm', 'ink-cool' ), true )
		&& preg_match( '/^#[0-9A-Fa-f]{6}$/', $celdas[1] )
		&& preg_match( '/^#[0-9A-Fa-f]{6}$/', $celdas[2] )
		&& preg_match( '/^#[0-9A-Fa-f]{6}$/', $celdas[3] ) ) {
		$suelos[ $celdas[0] ] = array( 'bg' => $celdas[1], 'bg_alt' => $celdas[2], 'text' => $celdas[3] );
	}
}
/* Sin esto, un cambio de formato en la tabla dejaria $suelos vacio y TODO el
   bucle de abajo pasaria sin comprobar ni una posicion.
   NO basta con contar nueve: la tabla intrusa de arriba dejaba las cuatro
   claves puestas y esta linea seguia verde con el whitelist viejo. Por eso lo
   que se comprueba debajo es que las celdas `paper` son las del build, y
   despues el CONTRASTE. */
ok( 9 === count( $suelos ), 'las nueve posiciones de ground se leen de design-system.md: ' . implode( ', ', array_keys( $suelos ) ) );

/* ---------------------------------------------------------------------------
 * DERIVA: $GROUND en _build-gallery.php es un espejo A MANO de esta misma
 * tabla (su propio comentario en el array lo dice), y nada comprobaba que los
 * dos siguieran de acuerdo. style-catalog PR 2a encontro exactamente este
 * hueco en $ANCHORS; $GROUND tiene la misma forma y el mismo riesgo, y a
 * nueve familias en vez de cuatro el coste de que se desincronicen crece.
 * ------------------------------------------------------------------------- */
$bg_ruta = dirname( __DIR__ ) . '/skills/html-mockup/assets/gallery/_build-gallery.php';
$bg_src  = (string) file_get_contents( $bg_ruta );
$gr_literal = array();
if ( preg_match( '/\$GROUND\s*=\s*array\s*\(\s*\n(.*?)\n\);/s', $bg_src, $gm )
	&& preg_match_all(
		"/'([a-z-]+)'\s*=>\s*array\(\s*'bg'\s*=>\s*'(#[0-9A-Fa-f]{6})',\s*'alt'\s*=>\s*'(#[0-9A-Fa-f]{6})',\s*'text'\s*=>\s*'(#[0-9A-Fa-f]{6})'\s*\)/",
		$gm[1],
		$glm,
		PREG_SET_ORDER
	) ) {
	foreach ( $glm as $g ) {
		$gr_literal[ $g[1] ] = array( 'bg' => strtoupper( $g[2] ), 'alt' => strtoupper( $g[3] ), 'text' => strtoupper( $g[4] ) );
	}
}
ok( array() !== $gr_literal, '_build-gallery.php tiene un array $GROUND reconocible para comparar contra design-system.md' );
ok(
	count( $gr_literal ) === count( $suelos ),
	'$GROUND en _build-gallery.php declara el mismo numero de posiciones que design-system.md: '
		. count( $gr_literal ) . ' vs ' . count( $suelos )
);
foreach ( $suelos as $sp => $sv ) {
	if ( ! isset( $gr_literal[ $sp ] ) ) {
		ok( false, "design-system.md documenta el ground `$sp` y \$GROUND en _build-gallery.php no lo tiene — el espejo se desincronizo" );
		continue;
	}
	ok( $sv['bg'] === $gr_literal[ $sp ]['bg'], "\$GROUND['$sp']['bg'] coincide con design-system.md: " . $gr_literal[ $sp ]['bg'] );
	ok( $sv['bg_alt'] === $gr_literal[ $sp ]['alt'], "\$GROUND['$sp']['alt'] coincide con design-system.md: " . $gr_literal[ $sp ]['alt'] );
	ok( $sv['text'] === $gr_literal[ $sp ]['text'], "\$GROUND['$sp']['text'] coincide con design-system.md: " . $gr_literal[ $sp ]['text'] );
}

/* Los tres tokens que el eje DOCUMENTA tienen que ser, en el build, los de la
   fila `paper`. Nada comprobaba esto, y por eso bg_alt llevaba un cuarto ground
   inventado. */
es_tokens( $es_defaults );
foreach ( array( 'bg', 'bg_alt', 'text' ) as $documentado ) {
	ok(
		strtoupper( $suelos['paper'][ $documentado ] ) === strtoupper( es_t( $documentado ) ),
		'el valor por defecto de "' . $documentado . '" es la celda `paper` de design-system.md (' . $suelos['paper'][ $documentado ] . '), no un ground inventado',
		es_t( $documentado )
	);
}

foreach ( $suelos as $posicion => $celdas ) {
	es_tokens( array( 'bg' => $celdas['bg'], 'bg_alt' => $celdas['bg_alt'], 'text' => $celdas['text'] ) );
	$fondo = es_t( 'bg' );
	/* TEXTO DE CUERPO: 4.5:1. `muted` es el que pinta es_p(), asi que es el que
	   se lee en cada parrafo del sitio; `text_soft` pinta la prosa de la ficha
	   de producto. Los dos son texto normal, no texto grande. */
	foreach ( array( 'text', 'muted', 'text_soft' ) as $tinta ) {
		$r = wcag_ratio( es_t( $tinta ), $fondo );
		ok( $r >= 4.5, 'ground `' . $posicion . '`: "' . $tinta . '" (' . es_t( $tinta ) . ') contra su PROPIO fondo llega a AA —— ' . $r . ':1' );
	}
	/* UI NO TEXTUAL: 3:1. La superficie inversa tiene que leerse como OTRA
	   superficie sobre la pagina, que es lo que a 1.06:1 no pasaba: el boton
	   `dark` era invisible sobre su propia pagina. */
	$r = wcag_ratio( es_t( 'surface_inverse' ), $fondo );
	ok( $r >= 3.0, 'ground `' . $posicion . '`: la superficie inversa (' . es_t( 'surface_inverse' ) . ') se distingue de la pagina —— ' . $r . ':1' );
	/* Y la tinta que va ENCIMA de esa superficie, contra la superficie, no
	   contra la pagina. */
	$r = wcag_ratio( es_t( 'on_inverse' ), es_t( 'surface_inverse' ) );
	ok( $r >= 4.5, 'ground `' . $posicion . '`: la tinta sobre la superficie inversa llega a AA contra ELLA —— ' . $r . ':1' );
	/* EL FILETE, y por que NO se le pide 3:1. WCAG 1.4.11 pide 3:1 a un control;
	   esto es un divisor, y no ha llegado a 3:1 en NINGUN ground, incluido el
	   blanco para el que se dibujo (1.24:1). Pedirle 3:1 seria o bien oscurecer
	   todos los divisores de todos los sitios sin que nadie lo haya pedido, o
	   bien escribir una comprobacion que se aprueba mirando a otro lado. Lo que
	   si se le puede pedir —— y es lo que fallaba —— es que siga siendo un
	   filete: a 15.24:1 sobre `ink` era un tajo casi blanco. */
	$r = wcag_ratio( es_t( 'border' ), $fondo );
	ok( $r >= 1.05 && $r <= 2.5, 'ground `' . $posicion . '`: el filete sigue siendo un filete sobre su propio fondo —— ' . $r . ':1' );
}

/* ---------------------------------------------------------------------------
 * El estado HOVER sigue al ground, y lo que se afirma es el SIGNO.
 *
 * Los cuatro neutros que este eje arreglo —— muted, text_soft, border,
 * surface_inverse —— pasaron a mezclarse HACIA el ground. `accent_hover` y
 * `border_hover` se quedaron con una sombra ABSOLUTA (0.815 y 0.935, oscurecer
 * y punto) y son los dos supervivientes de esa misma clase de fallo.
 *
 * Oscurecer sube el contraste sobre una pagina clara y lo BAJA sobre una
 * oscura. Medido sobre el build, reposo contra hover, cada uno contra su propio
 * `bg`:
 *
 *   paper    #FFFFFF  3.05 -> 4.39  +1.34   el hover AVANZA
 *   ink      #0E1113  5.67 -> 3.95  -1.72   el hover RETROCEDE
 *   ink-warm #171008  5.64 -> 3.92  -1.72   idem
 *   ink-cool #0B0F1C  5.72 -> 3.98  -1.74   idem
 *
 * (acento #FF3D8A en las filas oscuras.) Un hover que retrocede se lee como
 * DESACTIVADO: es un fallo de affordance, no de accesibilidad —— 3.95:1 sigue
 * pasando AA-large, asi que ninguna fila de qa-review lo caza y por eso llevaba
 * aqui sin verse. El build real de BSP-VITRINA lo parcheo a mano a #FF4D93
 * (5.67 -> 6.08, ACLARANDO), que es la prueba de que un humano ya se lo comio.
 *
 * Se afirma el SIGNO y no un hex a proposito, y es la misma razon por la que
 * arriba se afirma el contraste y no "el valor se ha movido": un hex clavado se
 * cumple con un color que sigue retrocediendo, y el signo no.
 *
 * DOS acentos, no uno. El verde de la casa solo prueba su propio caso; el rosa
 * es el que se midio en un sitio vivo. Si la direccion se eligiera por el color
 * en vez de por el suelo, uno de los dos lo diria.
 * ------------------------------------------------------------------------- */
echo "--- el hover AVANZA sobre su propio fondo, en las nueve posiciones ---\n";

foreach ( $suelos as $posicion => $celdas ) {
	foreach ( array( '#0FA968', '#FF3D8A' ) as $acento ) {
		es_tokens(
			array(
				'bg'     => $celdas['bg'],
				'bg_alt' => $celdas['bg_alt'],
				'text'   => $celdas['text'],
				'accent' => $acento,
			)
		);
		$fondo   = es_t( 'bg' );
		$reposo  = wcag_ratio( es_t( 'accent' ), $fondo );
		$encima  = wcag_ratio( es_t( 'accent_hover' ), $fondo );
		ok(
			$encima > $reposo,
			'ground `' . $posicion . '`, acento ' . $acento . ': el hover (' . es_t( 'accent_hover' )
				. ') se separa MAS del fondo que el reposo —— ' . $reposo . ':1 -> ' . $encima . ':1'
		);
		/* El filete hace el mismo trabajo con su hint: a 6.5% no tiene que
		   gritar, pero tiene que firmarse, no borrarse. */
		$reposo_f = wcag_ratio( es_t( 'border' ), $fondo );
		$encima_f = wcag_ratio( es_t( 'border_hover' ), $fondo );
		ok(
			$encima_f > $reposo_f,
			'ground `' . $posicion . '`, acento ' . $acento . ': el filete al pasar por encima ('
				. es_t( 'border_hover' ) . ') se firma en vez de borrarse —— ' . $reposo_f . ':1 -> ' . $encima_f . ':1'
		);
	}
}
es_tokens( $es_defaults );

/* La afirmacion es del CONTRASTE, no del cambio, y esto es lo que lo demuestra:
   un `muted` que se ha movido de su valor por defecto pero sigue sin leerse
   tiene que suspender. Sin esta linea, "muted !== #6A6F6C" pasaria por una
   comprobacion de accesibilidad. */
es_tokens( array( 'bg' => $suelos['ink']['bg'], 'text' => $suelos['ink']['text'], 'muted' => '#5A5F5C' ) );
ok( wcag_ratio( es_t( 'muted' ), es_t( 'bg' ) ) < 4.5, 'un muted que SE MUEVE pero sigue ilegible da menos de 4.5:1: lo que se afirma arriba es el contraste, no el cambio' );

/* Y el otro lado: un override explicito de un derivado gana, igual que en las
   otras dos pasadas. Una marca cuyo filete no es una mezcla de su tinta tiene
   que poder decirlo. */
es_tokens( array( 'border' => '#123456' ) );
ok( '#123456' === es_t( 'border' ), 'un override explicito de un neutro derivado gana a la mezcla' );
$r = grab(
	function () {
		return es_mix( 'azul', '#FFFFFF', 0.5 );
	}
);
ok( '' === $r['ret'], 'un valor que no es hex no se convierte en un neutro plausible al mezclarlo' );
ok( has( $r['out'], 'azul' ), 'y lo dice en voz alta nombrando el valor que no supo leer' );
es_tokens( $es_defaults );

/* ---------------------------------------------------------------------------
 * style-catalog PR 3b: tinta por estilo, la posicion `none`, y el piso de
 * spread=20 -- tres fixtures de comportamiento REAL, no reimplementados.
 *
 * ink_tint()/ink_quant_bound()/ink_ends()/ink_of() reciben cada entrada por
 * parametro -- ni un global -- asi que se pueden extraer del propio fichero
 * y ejecutar de verdad, en vez de reescribir su formula aqui (exactamente el
 * riesgo de "dos llamadas de acuerdo" que el propio docblock de ink_of() ya
 * retracto una vez). fail() sale con exit(1) dentro de esos gates; por eso
 * el extracto corre en un PROCESO HIJO, la misma aislacion que ya usa
 * "si falta la dependencia, se dice; no se muere en silencio" mas arriba --
 * un exit(1) alli abajo no puede tumbar esta suite.
 * ------------------------------------------------------------------------- */
function ink_fn_src( $src, $name ) {
	if ( ! preg_match( '/\bfunction\s+' . preg_quote( $name, '/' ) . '\s*\(/', $src, $m, PREG_OFFSET_CAPTURE ) ) {
		return '';
	}
	$start = $m[0][1];
	$brace = strpos( $src, '{', $start );
	if ( false === $brace ) {
		return '';
	}
	$depth = 0;
	$i     = $brace;
	$len   = strlen( $src );
	for ( ; $i < $len; $i++ ) {
		if ( '{' === $src[ $i ] ) {
			$depth++;
		} elseif ( '}' === $src[ $i ] ) {
			$depth--;
			if ( 0 === $depth ) {
				++$i;
				break;
			}
		}
	}
	return substr( $src, $start, $i - $start );
}

function ink_probe_run( $php_body, $driver ) {
	$dir = sys_get_temp_dir() . '/nm-ink-probe-' . getmypid() . '-' . mt_rand( 1000, 9999 );
	@mkdir( $dir, 0777, true );
	$probe = $dir . '/probe.php';
	file_put_contents( $probe, "<?php\n" . $php_body . "\n" . $driver . "\n" );
	$out  = array();
	$code = -1;
	exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( $probe ) . ' 2>&1', $out, $code );
	@unlink( $probe );
	@rmdir( $dir );
	return array( 'out' => implode( "\n", $out ), 'code' => $code );
}

/* `srgb_lum`/`srgb_lum_rgb`/`css_mix`/`ink_tint`/`ink_quant_bound`/`ink_ends` moved to
   `herramientas/color.php` (openspec/changes/plantillas-reales PR 1a) -- one contrast/ink engine,
   not a text-extracted copy of it. The probe now REQUIREs the real file directly, which is
   strictly more faithful than the text-extraction this comment used to describe: it is the exact
   bytes that ship, not a regex's idea of them. `fail()` and `ink_of()` stay gallery-specific and
   are still pulled from $bg_src exactly as before. Because the moved functions now throw
   `NmHerramientaEntorno`/`NmHerramientaMedida` instead of calling `fail()` directly (they are also
   a dual-mode CLI now, see color.php's own header), the probe re-installs the SAME
   exception-to-fail() bridge `_build-gallery.php` itself installs, so this probe's exit-code and
   message contract stays exactly what it was before the extraction. */
$color_ruta = dirname( __DIR__ ) . '/skills/html-mockup/assets/herramientas/color.php';
ok( is_file( $color_ruta ), 'herramientas/color.php existe para que el probe de tinta lo requiera' );
$ink_fn_names = array( 'fail', 'ink_of' );
$ink_fn_body  = "require_once " . var_export( $color_ruta, true ) . ";\n"
	. "set_exception_handler( function ( \$e ) {\n"
	. "\tif ( \$e instanceof NmHerramientaEntorno || \$e instanceof NmHerramientaMedida ) {\n"
	. "\t\tfail( \$e->getMessage() );\n"
	. "\t}\n"
	. "\tthrow \$e;\n"
	. "} );\n";
foreach ( $ink_fn_names as $ink_fn_name ) {
	$ink_fn_one = ink_fn_src( $bg_src, $ink_fn_name );
	ok( '' !== $ink_fn_one, "_build-gallery.php todavia define \`$ink_fn_name()\` para que el probe de tinta lo extraiga" );
	$ink_fn_body .= $ink_fn_one . "\n";
}

/* La MATEMATICA de ink_ends() ya aceptaba un tint por parametro antes de esta PR -- lo que NO
   existia era el CABLEADO: un solo $INK_TINT alimentaba los dos call sites, asi que ningun anchor
   real podia divergir de otro. Esto comprueba el cableado en si, no la formula. */
ok( false !== strpos( $bg_src, '$INK_TINT_BY_STYLE = array(' ), '_build-gallery.php declara $INK_TINT_BY_STYLE, la tabla por estilo -- style-catalog PR 3b' );
ok(
	1 === preg_match( '/\$INK\[\s*\$ink_ak\s*\]\s*=\s*ink_of\(\s*\$ink_ak,\s*\$ANCHORS,\s*\$GROUND,\s*\$ACCENT_BY_GROUND,\s*\$INK_GRADE,\s*\$ink_tint_v\s*\);/', $bg_src ),
	'el call site de ink_of() para anchors ya no pasa el $INK_TINT global fijo: pasa un valor resuelto POR ESTILO',
	$bg_src
);
ok(
	1 === preg_match( '/\$ink_bends\s*=\s*ink_ends\(\s*\$GROUND\[\s*\$ink_bg\s*\],\s*\$ACCENT_BY_GROUND\[\s*\$ink_bg\s*\],\s*\$ink_tint_v\s*\);/', $bg_src ),
	'y el call site de brands tambien lee su propio $ink_tint_v, no el $INK_TINT compartido',
	$bg_src
);

if ( ! function_exists( 'exec' ) ) {
	ok( false, 'ENTORNO, no el cambio: exec() esta deshabilitado, los fixtures de tinta no se pudieron correr aqui' );
} else {
	echo "--- 3b.1: dos tintes reales por estilo (0.30/0.60) dan hues distintos, los dos dentro de la propia convergencia de ink_ends() ---\n";
	$r3b1 = ink_probe_run(
		$ink_fn_body,
		'$a = ink_ends(' . var_export( array( 'bg' => $suelos['warm']['bg'], 'text' => $suelos['warm']['text'] ), true ) . ", '#0F5C1A', 0.30);\n"
			. '$b = ink_ends(' . var_export( array( 'bg' => $suelos['cool']['bg'], 'text' => $suelos['cool']['text'] ), true ) . ", '#8C1A28', 0.60);\n"
			. "echo 'A=' . \$a['dark'] . \"\\n\";\n"
			. "echo 'B=' . \$b['dark'] . \"\\n\";\n"
			. "echo \"LLEGO\\n\";\n"
	);
	ok( 0 === $r3b1['code'], 'los dos tintes conservan las tres comprobaciones internas de ink_ends() (convergencia, peso, colision de endpoint) sin FAIL', $r3b1['out'] );
	ok( has( $r3b1['out'], 'LLEGO' ), 'y el proceso llega al final, no se corta a mitad', $r3b1['out'] );
	preg_match( '/A=(#[0-9A-F]{6})/', $r3b1['out'], $ink_ma );
	preg_match( '/B=(#[0-9A-F]{6})/', $r3b1['out'], $ink_mb );
	ok( isset( $ink_ma[1] ) && isset( $ink_mb[1] ), 'los dos tintes devuelven una tinta legible', $r3b1['out'] );
	if ( isset( $ink_ma[1] ) && isset( $ink_mb[1] ) ) {
		ok( $ink_ma[1] !== $ink_mb[1], "0.30 y 0.60 dan tintas de sombra DISTINTAS sobre su propio fondo+acento real: {$ink_ma[1]} vs {$ink_mb[1]} -- la variedad tonal ya no la fija un solo \$INK_TINT compartido" );
	}

	echo "--- 3b.2: un grade de tinta `none` es una identidad, no un null -- ink_ends() nunca corre, ningun filtro se llegaria a emitir ---\n";
	$r3b2 = ink_probe_run(
		$ink_fn_body,
		"\$grades = array( 'default' => array( 'sat' => 0.72, 'gamma' => 0.12 ), 'x' => 'none' );\n"
			. '$anchors = array( "x" => array( "ground" => "paper" ) );' . "\n"
			. '$grounds = array( "paper" => ' . var_export( array( 'bg' => $suelos['paper']['bg'], 'text' => $suelos['paper']['text'] ), true ) . " );\n"
			. '$accents = array( "paper" => "#8C3A1F" );' . "\n"
			. "\$o = ink_of( 'x', \$anchors, \$grounds, \$accents, \$grades, 0.45 );\n"
			. "echo 'ENDS=' . var_export( \$o['ends'], true ) . \"\\n\";\n"
			. "echo 'SAT=' . \$o['sat'] . \"\\n\";\n"
			. "echo 'TABLE0=' . \$o['table'][0] . \"\\n\";\n"
			. "echo \"LLEGO\\n\";\n"
	);
	ok( 0 === $r3b2['code'], 'un grade `none` no dispara fail() -- convergencia, spread y colision de endpoints nunca se evaluan para el', $r3b2['out'] );
	ok( has( $r3b2['out'], 'ENDS=NULL' ), "'ends' es NULL, no un array -- ink_ends() nunca se invoco para este estilo", $r3b2['out'] );
	ok( has( $r3b2['out'], 'SAT=1' ), "'sat' es la identidad de feColorMatrix (a s=1 la matriz devuelve r,g,b sin tocar)", $r3b2['out'] );
	ok( has( $r3b2['out'], 'TABLE0=0 0.25 0.5 0.75 1' ), 'la tabla es la identidad de feComponentTransfer, no una tabla vacia', $r3b2['out'] );

	echo "--- 3b.3: un spread de canal de 14 (bajo el piso de 20) SIGUE fallando -- `none` no ablando el gate para nadie mas ---\n";
	$r3b3 = ink_probe_run(
		$ink_fn_body,
		'$e = ink_ends(' . var_export( array( 'bg' => $suelos['paper']['bg'], 'text' => $suelos['paper']['text'] ), true ) . ", '#20203A', 0.45);\n"
			. "echo \"INALCANZABLE\\n\";\n"
	);
	ok( 1 === $r3b3['code'], 'un spread de 14 SI corta el build -- exit 1', $r3b3['out'] );
	ok( has( $r3b3['out'], 'channel spread of 14' ), 'y el mensaje nombra el spread medido, 14, por debajo del piso de 20', $r3b3['out'] );
	ok( ! has( $r3b3['out'], 'INALCANZABLE' ), 'y el proceso NUNCA llega a la linea de despues del fail()', $r3b3['out'] );
}

/* ---------------------------------------------------------------------------
 * La etiqueta del boton primario: `on_accent`, medida y no fijada.
 *
 * Era un #FFFFFF literal. Sobre el acento propio del framework (#0FA968) eso son
 * 3.05:1 —— por debajo del 4.5:1 que WCAG AA pide al texto normal, en la
 * etiqueta de TODOS los botones primarios que el framework emite. La tinta
 * oscura sobre ese mismo verde da 5.86:1.
 *
 * Lo que NO se afirma aqui es `on_accent === '#15181A'`. Esa comprobacion pasa
 * para una marca cuyo acento es azul marino, donde el negro es justo el que no
 * se lee, asi que seria clavar el mismo defecto con el color contrario: la
 * siguiente tarea de ejes tendria que debilitarla para poder avanzar. Se afirma
 * el CONTRASTE, sobre acentos elegidos por incomodos.
 *
 * Y el caso en que NINGUNO de los dos candidatos llega a 4.5:1 no es una
 * hipotesis remota, es geometria: en el acento donde los dos candidatos se
 * cruzan, ambos miden exactamente sqrt(contraste del propio ground). Sobre
 * `paper` (17.84:1) eso es 4.22:1, asi que existe una BANDA de acentos donde
 * ninguna de las dos tintas cumple AA, y solo un ground de negro puro sobre
 * blanco puro (21:1 -> 4.58) la cerraria. Ahi el build pinta la mejor de las dos
 * y avisa; lo que sigue lo demuestra en vez de suponerlo.
 * ------------------------------------------------------------------------- */
echo "--- la etiqueta sobre el acento se ELIGE midiendo, no se fija ---\n";

/* Antes que nada, la falsabilidad de todo el bloque: el valor que estaba escrito
   a mano SUSPENDE sobre el acento por defecto. Sin esta linea, "on_accent llega
   a 4.5" podria estar pasando por casualidad sobre un acento facil. */
ok( wcag_ratio( '#FFFFFF', '#0FA968' ) < 4.5, 'el #FFFFFF que estaba fijado a mano da ' . wcag_ratio( '#FFFFFF', '#0FA968' ) . ':1 sobre el acento por defecto: por eso se deriva' );

/* Cuatro acentos elegidos por incomodos: el de la casa (donde gana la tinta),
   uno palido, uno oscuro (donde tiene que ganar el BLANCO —— si estuviera
   clavado en oscuro, esta fila seria roja) y uno de tono medio en la banda del
   cruce, donde no gana ninguno. */
$acentos = array(
	'#0FA968' => array( 'casa',   true ),
	'#F4D03F' => array( 'palido', true ),
	'#1B2A4A' => array( 'oscuro', true ),
	'#008899' => array( 'medio',  false ),
);
foreach ( $acentos as $acento => $caso ) {
	list( $mote, $cumple ) = $caso;
	$r      = grab(
		function () use ( $acento ) {
			es_tokens( array( 'accent' => $acento ) );
			return es_t( 'on_accent' );
		}
	);
	$tinta  = $r['ret'];
	$medido = wcag_ratio( $tinta, $acento );
	/* La REGLA: la etiqueta es uno de los dos extremos del ground, nunca un
	   color nuevo que no esta en la paleta. */
	ok(
		in_array( strtoupper( $tinta ), array( strtoupper( es_t( 'text' ) ), strtoupper( es_t( 'bg' ) ) ), true ),
		'acento ' . $mote . ' ' . $acento . ': la etiqueta es uno de los dos extremos del ground (' . $tinta . '), no un color inventado'
	);
	/* Y que sea el MEJOR de los dos, que es lo que "derivado" significa aqui. */
	$otro = strtoupper( $tinta ) === strtoupper( es_t( 'text' ) ) ? es_t( 'bg' ) : es_t( 'text' );
	ok(
		$medido >= wcag_ratio( $otro, $acento ),
		'acento ' . $mote . ' ' . $acento . ': se elige el que MAS contrasta —— ' . $tinta . ' ' . $medido . ':1 contra ' . $otro . ' ' . wcag_ratio( $otro, $acento ) . ':1'
	);
	if ( $cumple ) {
		ok( $medido >= 4.5, 'acento ' . $mote . ' ' . $acento . ': la etiqueta llega a AA —— ' . $medido . ':1' );
		ok( '' === trim( $r['out'] ), 'acento ' . $mote . ' ' . $acento . ': y no avisa de nada, porque no hay nada que avisar' );
	} else {
		/* El caso que no se puede resolver: ninguno llega, se pinta el mejor y se
		   DICE. Un build que se callara aqui pintaria una etiqueta ilegible con
		   la misma cara que una legible. */
		ok( $medido < 4.5, 'acento ' . $mote . ' ' . $acento . ': ninguna de las dos tintas llega a AA —— la mejor da ' . $medido . ':1' );
		ok( has( $r['out'], $acento ) && has( $r['out'], '4.5' ), 'acento ' . $mote . ' ' . $acento . ': y el aviso nombra el acento y el umbral que no se alcanza' );
		ok( has( $r['out'], es_t( 'text' ) ) && has( $r['out'], es_t( 'bg' ) ), 'acento ' . $mote . ' ' . $acento . ': y las DOS medidas, para que se pueda decidir sin volver a medir' );
	}
}

/* EL EMPATE, que no es un caso de laboratorio. Los dos candidatos empatan a dos
   decimales justo en el cruce —— o sea a sqrt(17.84) = 4.22:1 —— y ahi caen
   azules de marca de lo mas corrientes: #0076FA, #007AEA, #0081C8. Cuando eso
   pasa NO hay diferencia legible que decidir, pero la eleccion tiene que salir
   IGUAL en cada maquina y en cada ejecucion o los bytes emitidos dejan de ser
   reproducibles. Gana el primer candidato de la receta, que es `text`.
   Esta afirmacion existe porque sin ella invertir el orden de la receta
   (`text`,`bg` -> `bg`,`text`) no ponia roja ni una sola linea de la suite. */
es_tokens( array( 'accent' => '#0076FA' ) );
ok( 4.22 === wcag_ratio( es_t( 'text' ), '#0076FA' ) && 4.22 === wcag_ratio( es_t( 'bg' ), '#0076FA' ), 'el acento #0076FA empata de verdad: las dos tintas dan 4.22:1 sobre el, asi que el desempate se ejecuta' );
ok( strtoupper( es_t( 'text' ) ) === strtoupper( es_t( 'on_accent' ) ), 'y el empate se resuelve SIEMPRE al primer candidato de la receta (`text`, ' . es_t( 'on_accent' ) . '): sin regla fija, dos maquinas emitirian bytes distintos' );

/* Que el oscuro y el claro salgan de la misma regla es lo que separa esto de
   fijar un color: el mismo codigo devuelve tintas OPUESTAS. */
es_tokens( array( 'accent' => '#0FA968' ) );
$casa = es_t( 'on_accent' );
es_tokens( array( 'accent' => '#1B2A4A' ) );
$navy = es_t( 'on_accent' );
ok( strtoupper( $casa ) !== strtoupper( $navy ), 'el verde de la casa y un azul marino reciben etiquetas OPUESTAS (' . $casa . ' / ' . $navy . '): la regla es la medida, no un valor' );

/* La banda del cruce, medida en vez de argumentada: sobre `paper`, el mejor
   caso posible en el punto donde los dos candidatos empatan es sqrt(17.84) =
   4.22, o sea que la rama del aviso es alcanzable por construccion. */
es_tokens( $es_defaults );
$cruce = sqrt( wcag_ratio( es_t( 'text' ), es_t( 'bg' ) ) );
ok( $cruce < 4.5, 'en el cruce los dos candidatos miden sqrt(contraste del ground) = ' . round( $cruce, 2 ) . ':1 sobre `paper`: la banda sin salida existe, no es una rama defensiva' );
ok( max( wcag_ratio( '#9966BB', es_t( 'text' ) ), wcag_ratio( '#9966BB', es_t( 'bg' ) ) ) <= round( $cruce, 2 ) + 0.01, 'y el peor acento de todo el cubo RGB sobre `paper` (#9966BB) cae justo ahi: ' . max( wcag_ratio( '#9966BB', es_t( 'text' ) ), wcag_ratio( '#9966BB', es_t( 'bg' ) ) ) . ':1' );

/* Los cuatro grounds documentados, con el acento por defecto. En `ink` la
   eleccion se DA LA VUELTA —— gana el fondo casi negro, no la tinta casi
   blanca —— que es exactamente lo que un valor fijado no puede hacer. */
$elegido = array();
foreach ( $suelos as $posicion => $celdas ) {
	es_tokens( array( 'bg' => $celdas['bg'], 'bg_alt' => $celdas['bg_alt'], 'text' => $celdas['text'] ) );
	$m                     = wcag_ratio( es_t( 'on_accent' ), es_t( 'accent' ) );
	$elegido[ $posicion ] = strtoupper( es_t( 'on_accent' ) ) === strtoupper( es_t( 'text' ) ) ? 'text' : 'bg';
	ok( $m >= 4.5, 'ground `' . $posicion . '`: la etiqueta sobre el acento llega a AA contra EL —— ' . es_t( 'on_accent' ) . ' ' . $m . ':1' );
}
ok( 'text' === $elegido['paper'] && 'bg' === $elegido['ink'], 'y el candidato ganador cambia con el ground: `paper` elige text, `ink` elige bg' );

/* El override explicito gana, igual que en las otras tres pasadas. Una marca
   cuya etiqueta es una crema que no esta en su ground tiene que poder decirlo. */
es_tokens( array( 'on_accent' => '#123456' ) );
ok( '#123456' === es_t( 'on_accent' ), 'un override explicito de la etiqueta gana a la medida' );

/* Y un acento ilegible no produce una etiqueta plausible: se queda sin pintar y
   lo dice, igual que es_mix() y es_shade(). */
$r = grab(
	function () {
		return es_contrast( 'azul', '#FFFFFF' );
	}
);
ok( 0.0 === $r['ret'], 'un valor que no es hex no produce un ratio plausible: 0.0 es imposible en una escala que empieza en 1:1' );
ok( has( $r['out'], 'azul' ), 'y lo dice en voz alta nombrando el valor que no supo leer' );
$r = grab(
	function () {
		es_tokens( array( 'accent' => 'azul' ) );
		return es_t( 'on_accent' );
	}
);
ok( '' === $r['ret'], 'y con un acento ilegible la etiqueta se queda SIN pintar en vez de inventarse un blanco' );
es_tokens( $es_defaults );

/* Cualquier override reconstruye el juego DESDE los valores por defecto, asi que
   devolverlos entero restaura todos. Esa es la via de reset, y esta afirmada
   aqui para que nadie la cambie por acumulacion sin darse cuenta. */
es_tokens( $es_defaults );
ok( $es_defaults['accent'] === es_t( 'accent' ), 'el reset devuelve el acento por defecto' );
ok( $es_defaults['font_body'] === es_t( 'font_body' ), 'y tambien la familia tocada antes: un override reconstruye desde los valores por defecto' );
ok( $es_defaults['elev_accent'] === es_t( 'elev_accent' ), 'y los derivados vuelven a derivarse del acento por defecto' );
ok( ! array_key_exists( 'acento', es_tokens() ), 'y la clave inventada de antes no se queda pegada al juego de tokens' );

/* ---------------------------------------------------------------------------
 * La familia tipografica que se escribe y que nada instala.
 *
 * es_tokens() lleva font_head/font_body y los escribe en cada titular y cada
 * parrafo como typography_font_family. NADA en este framework hace que esa
 * familia exista en el sitio: ni @font-face, ni encolado, ni registro. El eje de
 * escala movia todos los TAMANOS bien y el tipo de letra podia no llegar nunca,
 * con las cuatro suites y el audit en verde.
 *
 * POR QUE WORDPRESS APARECE AQUI ABAJO Y NO ARRIBA CON LOS DEMAS DOBLES. PHP iza
 * las declaraciones de funcion de nivel superior a tiempo de compilacion, asi
 * que declarar get_post_types() junto a get_posts() pondria un WordPress debajo
 * de CADA es_save_page() de este fichero — y las assertions de arriba que
 * afirman `'' === $r['out']` pasarian a leer un aviso de tipografia en vez de lo
 * que dicen afirmar. Eso es exactamente lavar una assertion: sigue verde y ya no
 * prueba lo mismo. Dentro de un bloque la declaracion NO se iza, se define
 * cuando la ejecucion llega hasta aqui, y todo lo de arriba corre con el
 * veredicto 'sin-wordpress' — que es lo que un arbol sin sitio tiene que dar.
 * ------------------------------------------------------------------------- */
echo "--- la familia que es_tokens() nombra y nada instala ---\n";

wp_fake_reset();
$GLOBALS['es_font_said'] = false;
unset( $GLOBALS['wp_styles'] );

/* Sin WordPress el veredicto es "no hay sitio al que preguntar", NO "falta la
   fuente". Son dos hechos distintos y solo uno de ellos es un aviso; confundir
   los dos es la enfermedad que este fichero entero existe para quitar. */
$r = grab( 'es_font_serving_check' );
ok( 'sin-wordpress' === $r['ret'], 'sin WordPress no hay sitio que juzgar: el veredicto lo dice y no se inventa un hallazgo' );
ok( '' === $r['out'], 'y no dice nada: avisar de una fuente que falta en un arbol que no es un sitio seria ruido puro' );

if ( ! function_exists( 'get_post_types' ) ) {
	function get_post_types( $args = array(), $output = 'names' ) {
		return $GLOBALS['wp']['post_types'];
	}
}

/* 1. El sitio de verdad: hay un tipo de contenido de fuentes y no hay ninguna
      instalada. Un "no lo he podido confirmar" NO es un aprobado. */
wp_fake_reset();
$GLOBALS['wp']['post_types'] = array( 'post', 'page', 'elementor_font' );
$GLOBALS['es_font_said']     = false;
$r = grab( 'es_font_serving_check' );
ok( 'sin-confirmar' === $r['ret'], 'sin ninguna familia instalada el veredicto es sin-confirmar, que no es limpio' );
ok( has( $r['out'], es_t( 'font_head' ) ), 'y avisa nombrando la familia de titulares' );
ok( has( $r['out'], es_t( 'font_body' ) ), 'y tambien la de texto: las dos se escriben, las dos tienen que llegar' );
ok( has( $r['out'], 'no lo he podido confirmar' ), 'y dice que es un "no he podido", no un "no esta": desde un build no se ven los encolados del front' );
ok( has( $r['out'], 'AUTOALOJADA' ), 'y da el arreglo concreto: autoalojarla' );
ok( has( $r['out'], 'CDN de Google' ), 'nombrando ademas el camino que NO se toma' );
ok( has( $r['out'], 'knowledge.md' ), 'y donde esta el procedimiento, porque un aviso sin procedimiento detras es una linea mas que saltarse' );

/* 2. Una vez por build, como el modo seguro: es un hecho del SITIO, y repetirlo
      en cada pagina enterraria las paginas debajo. */
$r = grab( 'es_font_serving_check' );
ok( 'sin-confirmar' === $r['ret'], 'el veredicto sigue siendo el mismo en la segunda llamada' );
ok( '' === $r['out'], 'pero no se repite: una vez por build' );

/* 3. Instaladas las dos Y con los encolados a la vista, se calla. Un check que
      avisa siempre se apaga igual que uno que aprueba siempre, solo que mas
      despacio. El estilo encolado no es decoracion del fixture: sin UNA hoja
      encolada o impresa no hay peticion del front que mirar, y dar el sitio por
      limpio dejaria de ser honesto (escenario 7). */
wp_fake_reset();
$GLOBALS['wp']['post_types'] = array( 'post', 'page', 'elementor_font' );
$GLOBALS['wp']['font_posts'] = array( 'elementor_font' => array( es_t( 'font_head' ), es_t( 'font_body' ) ) );
$GLOBALS['es_font_said']     = false;
wp_fake_style( 'tema-estilo', 'https://sitio.test/wp-content/themes/x/style.css', 'enqueued' );
$r = grab( 'es_font_serving_check' );
ok( 'alojada' === $r['ret'], 'con las dos familias instaladas el veredicto es alojada' );
ok( '' === $r['out'], 'y se calla: un sitio correcto no tiene que oir nada' );

/* 4. Media instalacion es una instalacion que falta, y el aviso nombra SOLO la
      que falta — nombrar las dos manda al operador a revisar una que ya esta. */
wp_fake_reset();
$GLOBALS['wp']['post_types'] = array( 'post', 'page', 'elementor_font' );
$GLOBALS['wp']['font_posts'] = array( 'elementor_font' => array( es_t( 'font_head' ) ) );
$GLOBALS['es_font_said']     = false;
$r = grab( 'es_font_serving_check' );
ok( 'sin-confirmar' === $r['ret'], 'con una de las dos instalada sigue sin poder confirmarse' );
ok( has( $r['out'], es_t( 'font_body' ) ), 'y nombra la que falta' );
ok( ! has( $r['out'], es_t( 'font_head' ) ), 'y NO la que ya esta: mandar a revisar lo que ya funciona es como se apaga un aviso' );

/* 5. EL TIPO DE CONTENIDO SE DERIVA, NO SE ESCRIBE. Una constante copiada del
      codigo de un plugin es falsa para el siguiente y falsa despues de cualquier
      renombrado — y una sonda que no encuentra nada reporta un sitio limpio. Con
      'elementor_font' escrito a mano, este escenario daria sin-confirmar. */
wp_fake_reset();
$GLOBALS['wp']['post_types'] = array( 'post', 'page', 'bsf_custom_fonts' );
$GLOBALS['wp']['font_posts'] = array( 'bsf_custom_fonts' => array( es_t( 'font_head' ), es_t( 'font_body' ) ) );
$GLOBALS['es_font_said']     = false;
wp_fake_style( 'tema-estilo', 'https://sitio.test/wp-content/themes/x/style.css', 'enqueued' );
$r = grab( 'es_font_serving_check' );
ok( 'alojada' === $r['ret'], 'otro plugin de fuentes con otro nombre de tipo tambien cuenta: el tipo se deriva de get_post_types()' );

/* 6. EL DEFECTO MEDIDO: una hoja REGISTRADA no prueba nada. WordPress core
      registra `open-sans` apuntando a fonts.googleapis.com en TODAS las
      instalaciones y no lo encola en ninguna, asi que leyendo `registered` este
      check acusaba a todos los sitios del mundo. Medido en un Hostinger real:
      open-sans registrado con enqueued false y done false, wp-editor-font
      igual, elementor_google_fonts "0", CERO apariciones de googleapis o
      gstatic en el HTML servido — y el aviso de RGPD impreso igual. Un aviso
      que salta siempre se apaga igual que uno que aprueba siempre, y este
      saltaba nombrando una exposicion legal, que es peor: entrena al operador a
      no creerse la unica linea que tenia orden de leer. */
wp_fake_reset();
$GLOBALS['wp']['post_types'] = array( 'post', 'page', 'elementor_font' );
$GLOBALS['wp']['font_posts'] = array( 'elementor_font' => array( es_t( 'font_head' ), es_t( 'font_body' ) ) );
$GLOBALS['es_font_said']     = false;
wp_fake_core_styles();
wp_fake_style( 'tema-estilo', 'https://sitio.test/wp-content/themes/x/style.css', 'enqueued' );
$r = grab( 'es_font_serving_check' );
ok( 'google' !== $r['ret'], 'lo que core registra y nadie encola NO es una prueba: el veredicto no es google' );
ok( 'alojada' === $r['ret'], 'con las familias instaladas y los encolados vistos sin Google, el sitio esta limpio' );
ok( '' === $r['out'], 'y no se le lee la cartilla de RGPD a un sitio que no le pide nada a Google' );

/* 6b. El hallazgo legal, con la prueba que si lo es: la hoja ENCOLADA. Es una
       prueba (al reves que no encontrarla, que no prueba nada) y gana al resto
       del veredicto aunque las familias esten instaladas, porque el problema
       que nombra no es tipografico sino legal. */
wp_fake_reset();
$GLOBALS['wp']['post_types'] = array( 'post', 'page', 'elementor_font' );
$GLOBALS['wp']['font_posts'] = array( 'elementor_font' => array( es_t( 'font_head' ), es_t( 'font_body' ) ) );
$GLOBALS['es_font_said']     = false;
wp_fake_core_styles();
wp_fake_style( 'tema-estilo', 'https://sitio.test/wp-content/themes/x/style.css', 'enqueued' );
wp_fake_style( 'elementor-gfonts', 'https://fonts.googleapis.com/css?family=Manrope', 'enqueued' );
$r = grab( 'es_font_serving_check' );
ok( 'google' === $r['ret'], 'una hoja ENCOLADA apuntando a fonts.googleapis.com es prueba, y gana aunque las familias esten instaladas' );
ok( has( $r['out'], 'elementor-gfonts' ), 'y nombra el estilo culpable, que es lo unico accionable' );
ok( ! has( $r['out'], 'open-sans' ), 'y NO el de core, que esta registrado y no lo pide nadie: mandar a quitar ese encolado es mandar a buscar lo que no existe' );
ok( has( $r['out'], 'IP' ), 'diciendo que lo que se filtra es la IP del visitante' );
ok( has( $r['out'], 'sentencias' ), 'y que ya hay sentencias contra el titular de la web, no contra Google' );

/* 6c. Impresa cuenta igual que encolada. Cuando los estilos ya se han impreso la
       cola puede estar vaciada y `done` es lo unico que recuerda lo que salio:
       un check que leyera SOLO `queue` se quedaria ciego justo donde la prueba
       es mas fuerte, porque ahi ya esta en el HTML del visitante. */
wp_fake_reset();
$GLOBALS['wp']['post_types'] = array( 'post', 'page', 'elementor_font' );
$GLOBALS['wp']['font_posts'] = array( 'elementor_font' => array( es_t( 'font_head' ), es_t( 'font_body' ) ) );
$GLOBALS['es_font_said']     = false;
wp_fake_core_styles();
wp_fake_style( 'tema-gfonts', 'https://fonts.gstatic.com/s/manrope/v13/x.woff2', 'done' );
$r = grab( 'es_font_serving_check' );
ok( 'google' === $r['ret'], 'una hoja ya IMPRESA tambien es prueba: gstatic en done es una peticion que el visitante ya ha hecho' );
ok( has( $r['out'], 'tema-gfonts' ), 'y nombra ese estilo' );

/* 6d. UNA DEPENDENCIA ES UNA PETICION TAMBIEN, y es el agujero que deja leer
       solo la cola. WordPress imprime las deps de un handle encolado SIN
       meterlas nunca en `queue`: un tema que encola su hoja y arrastra detras
       una de Google le pide la tipografia a Google en cada visita, y la cola no
       la menciona. La cadena es de dos saltos a proposito — tema-estilo ->
       tema-fuentes -> tema-gfonts — porque con un solo salto un recorrido que
       no sea transitivo pasaria el test y el agujero seguiria abierto un nivel
       mas abajo. Esto es un falso LIMPIO, que es la unica clase de fallo peor
       que el falso positivo que este commit vino a quitar. */
wp_fake_reset();
$GLOBALS['wp']['post_types'] = array( 'post', 'page', 'elementor_font' );
$GLOBALS['wp']['font_posts'] = array( 'elementor_font' => array( es_t( 'font_head' ), es_t( 'font_body' ) ) );
$GLOBALS['es_font_said']     = false;
wp_fake_core_styles();
wp_fake_style( 'tema-gfonts', 'https://fonts.googleapis.com/css?family=Inter' );
wp_fake_style( 'tema-fuentes', 'https://sitio.test/wp-content/themes/x/fuentes.css', 'registered', array( 'tema-gfonts' ) );
wp_fake_style( 'tema-estilo', 'https://sitio.test/wp-content/themes/x/style.css', 'enqueued', array( 'tema-fuentes' ) );
$r = grab( 'es_font_serving_check' );
ok( 'google' === $r['ret'], 'una hoja de Google que entra como DEPENDENCIA de una encolada es una peticion del visitante igual' );
ok( has( $r['out'], 'tema-gfonts' ), 'y nombra la hoja de Google, no la del tema que la arrastra: lo accionable es la que hay que quitar' );

/* 6e. Un ciclo en las dependencias no cuelga el informe. WordPress no lo
       produciria, pero un plugin con una lista escrita a mano si, y un informe
       que se queda colgado es peor que uno equivocado: el build entero se para
       en la linea que existe para avisar. Que este escenario TERMINE es la
       assertion; el veredicto es lo de menos. */
wp_fake_reset();
$GLOBALS['wp']['post_types'] = array( 'post', 'page', 'elementor_font' );
$GLOBALS['wp']['font_posts'] = array( 'elementor_font' => array( es_t( 'font_head' ), es_t( 'font_body' ) ) );
$GLOBALS['es_font_said']     = false;
wp_fake_style( 'a', 'https://sitio.test/a.css', 'enqueued', array( 'b' ) );
wp_fake_style( 'b', 'https://sitio.test/b.css', 'registered', array( 'a' ) );
$r = grab( 'es_font_serving_check' );
ok( 'alojada' === $r['ret'], 'un ciclo de dependencias termina, y sin Google en el ciclo el sitio sigue limpio' );

/* 7. LA OTRA MITAD, y es la que un cambio ingenuo rompe en silencio: en una
      peticion de build no hay NADA encolado (medido: queue_size 0), asi que
      leer solo `queue` haria que el check no salte nunca. No saltar es peor que
      saltar siempre, porque es silencioso. Con las dos familias instaladas y
      los encolados invisibles el veredicto honesto no es ni aprobado ni
      suspenso: es "no lo he podido confirmar", igual que 'sin-wordpress' es "no
      hay sitio al que preguntar". */
wp_fake_reset();
$GLOBALS['wp']['post_types'] = array( 'post', 'page', 'elementor_font' );
$GLOBALS['wp']['font_posts'] = array( 'elementor_font' => array( es_t( 'font_head' ), es_t( 'font_body' ) ) );
$GLOBALS['es_font_said']     = false;
wp_fake_core_styles();
$r = grab( 'es_font_serving_check' );
ok( 'sin-confirmar' === $r['ret'], 'sin un solo encolado ni impreso no se puede saber que carga el front: eso no es un aprobado' );
ok( has( $r['out'], 'googleapis' ), 'y el aviso dice que la pregunta abierta es si algo le pide la tipografia a Google' );
ok( has( $r['out'], 'no lo he podido confirmar' ), 'en los terminos de siempre: un "no he podido", no un "no esta"' );
ok( ! has( $r['out'], es_t( 'font_head' ) ), 'y NO acusa de que falte una familia que esta instalada: eso manda a revisar lo que ya funciona' );

/* 7b. Un registro EJERCITADO y sin Google cierra la parte legal, pero no tapa
       una familia que falta. Sin este escenario la sonda podria estar leyendo
       la cola al reves sin que nada se enterase. */
wp_fake_reset();
$GLOBALS['wp']['post_types'] = array( 'post', 'page', 'elementor_font' );
$GLOBALS['es_font_said']     = false;
wp_fake_core_styles();
wp_fake_style( 'tema-estilo', 'https://sitio.test/wp-content/themes/x/style.css', 'enqueued' );
$r = grab( 'es_font_serving_check' );
ok( 'sin-confirmar' === $r['ret'], 'los encolados vistos y sin Google no instalan la familia que falta' );
ok( has( $r['out'], es_t( 'font_head' ) ), 'y el aviso nombra la familia, que es lo que falta de verdad' );
ok( ! has( $r['out'], 'sentencias' ), 'sin prueba de Google no se le lee la cartilla de RGPD a nadie' );

/* 8. Una cara web-safe no necesita servirse. Sin esta salida el check avisaria
      de Georgia en cada build y el operador aprenderia a saltarselo. */
wp_fake_reset();
$GLOBALS['wp']['post_types'] = array( 'post', 'page', 'elementor_font' );
$GLOBALS['es_font_said']     = false;
es_tokens(
	array(
		'font_head' => 'Georgia, serif',
		'font_body' => 'system-ui',
	)
);
$r = grab( 'es_font_serving_check' );
ok( 'sin-familias' === $r['ret'], 'una pila generica o web-safe no necesita servirse: no hay familias que instalar' );
ok( '' === $r['out'], 'y no avisa de nada' );
es_tokens( $es_defaults );

/* 9. QUE ESTE CABLEADO, que es el hallazgo entero — la misma forma que
      es_front_page_check(). Con ES_AUDIT_SILENT el veredicto de contenedores no
      imprime, asi que lo que quede en stdout son avisos. */
wp_fake_reset();
$GLOBALS['wp']['post_types'] = array( 'post', 'page', 'elementor_font' );
$GLOBALS['es_font_said']     = false;
$r = grab( 'es_audit_summary' );
ok( has( $r['out'], es_t( 'font_head' ) ), 'es_audit_summary() lo dice: la linea que el operador tiene orden de leer antes de desplegar' );

/* ---------------------------------------------------------------------------
 * EL OTRO EXTREMO: leer lo que el front SIRVE de verdad.
 *
 * es_font_serving_check() lee el registro de estilos, y desde un build ese
 * registro no tiene nada encolado — asi que su veredicto honesto ahi es
 * 'sin-confirmar' PARA SIEMPRE, y un aviso que nadie puede limpiar nunca se
 * acaba saltando igual que uno que aprueba siempre. es_front_font_probe() es la
 * otra punta: lee el HTML SERVIDO, que es donde vive la respuesta, y es lo unico
 * que puede limpiar honestamente ese "no lo he podido confirmar".
 *
 * LAS DECLARACIONES VAN AQUI DENTRO, NO ARRIBA, por la misma razon que
 * get_post_types(): la API HTTP de WordPress no existe en este arbol, y
 * declararla en la cabecera pondria un home_url() debajo de TODO lo de arriba
 * — incluido es-theme-parts.example.php, que lo llama en tres sitios. Dentro de
 * un bloque la declaracion NO se iza: se define cuando la ejecucion llega aqui,
 * y todo lo anterior corre en el mundo sin HTTP, que es el mundo de un build.
 * ------------------------------------------------------------------------- */
echo "--- lo que el front sirve de verdad ---\n";

/* A. Sin API HTTP no hay nada a lo que llamar. No es "no encontre Google": es
      "no hay sitio al que preguntar", el mismo hecho que 'sin-wordpress' y por
      eso el mismo tipo de respuesta. Este escenario tiene que ir ANTES de las
      declaraciones o deja de poder existir. */
$r = grab( 'es_front_font_probe' );
ok( 'sin-http' === $r['ret'], 'sin la API HTTP de WordPress el veredicto es sin-http, no un hallazgo inventado' );
ok( '' === $r['out'], 'y no dice nada: no hay nada que contar de una peticion que no se puede hacer' );

if ( ! function_exists( 'home_url' ) ) {
	function home_url( $path = '/' ) {
		return 'https://sitio.test' . $path;
	}
	/* El doble devuelve EXACTAMENTE lo que la de verdad devuelve: un array con
	   response.code y body, o un WP_Error. Un doble que devolviese siempre el
	   cuerpo esconderia justo el fallo que este bloque existe para cazar — que
	   un 401 tampoco contiene "googleapis" y se leeria como limpio. */
	function wp_remote_get( $url, $args = array() ) {
		$GLOBALS['wp']['http_pedidas'][] = $url;
		$m = isset( $GLOBALS['wp']['http'] ) ? $GLOBALS['wp']['http'] : array();
		if ( isset( $m[ $url ] ) ) {
			return $m[ $url ];
		}
		return new WP_Error( 'http_request_failed', 'nadie contesto en ' . $url );
	}
	function wp_remote_retrieve_response_code( $res ) {
		return ( is_array( $res ) && isset( $res['response']['code'] ) ) ? $res['response']['code'] : '';
	}
	function wp_remote_retrieve_body( $res ) {
		return ( is_array( $res ) && isset( $res['body'] ) ) ? $res['body'] : '';
	}
}

/** Una respuesta HTTP con la forma de las de verdad. */
function respuesta( $codigo, $cuerpo ) {
	return array(
		'response' => array( 'code' => $codigo ),
		'body'     => $cuerpo,
	);
}
/** Una pagina servida creible: lo que importa es que se cierre como un documento. */
function pagina( $extra = '' ) {
	return "<!DOCTYPE html>\n<html lang=\"es\">\n<head>\n<title>x</title>\n" . $extra
		. "\n</head>\n<body><h1>hola</h1></body>\n</html>\n";
}

/* B. La peticion falla. Un WP_Error no es un sitio limpio: es un sitio que no
      contesto, y el aviso tiene que decir cual y por que. */
wp_fake_reset();
$r = grab( 'es_front_font_probe' );
ok( 'sin-confirmar' === $r['ret'], 'una peticion que falla no confirma nada' );
ok( has( $r['out'], 'https://sitio.test/' ), 'y el aviso dice a que URL fue' );
ok( has( $r['out'], 'nadie contesto' ), 'y arrastra el mensaje del error, que es lo unico que dice por que' );

/* C. EL FALSO LIMPIO, que es la dificultad entera de esta funcion: un 401, un
      500 o una pagina de mantenimiento tampoco contienen "googleapis". "No lo
      encontre" no vale nada hasta saber que los bytes mirados eran los de la
      pagina. Sin este escenario la funcion aprobaria un sitio detras de un
      candado. */
wp_fake_reset();
$GLOBALS['wp']['http'] = array( 'https://sitio.test/' => respuesta( 401, 'Authorization Required' ) );
$r = grab( 'es_front_font_probe' );
ok( 'sin-confirmar' === $r['ret'], 'un 401 no es un sitio sin Google: es un sitio que no se ha visto' );
ok( has( $r['out'], '401' ), 'y el aviso dice el codigo, que es lo que manda a mirar la causa' );

wp_fake_reset();
$GLOBALS['wp']['http'] = array( 'https://sitio.test/' => respuesta( 500, pagina() ) );
$r = grab( 'es_front_font_probe' );
ok( 'sin-confirmar' === $r['ret'], 'un 500 con cuerpo de pagina tampoco: el codigo manda sobre la forma' );

/* D. Un 200 cuyo cuerpo no es un documento — vacio, JSON, un trozo de cache a
      medias — es la misma trampa con otra cara. */
wp_fake_reset();
$GLOBALS['wp']['http'] = array( 'https://sitio.test/' => respuesta( 200, '' ) );
$r = grab( 'es_front_font_probe' );
ok( 'sin-confirmar' === $r['ret'], 'un 200 con el cuerpo vacio no es la pagina' );

wp_fake_reset();
$GLOBALS['wp']['http'] = array( 'https://sitio.test/' => respuesta( 200, '{"error":"nope"}' ) );
$r = grab( 'es_front_font_probe' );
ok( 'sin-confirmar' === $r['ret'], 'ni un 200 que devuelve JSON: sin documento cerrado no se ha mirado una pagina' );

/* E. El hallazgo, y aqui la prueba es del todo: esta en los bytes que recibe el
      visitante. No hay registro que interpretar ni encolado que deducir. */
wp_fake_reset();
$GLOBALS['wp']['http'] = array(
	'https://sitio.test/' => respuesta( 200, pagina( '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter&display=swap">' ) ),
);
$r = grab( 'es_front_font_probe' );
ok( 'google' === $r['ret'], 'googleapis en el HTML servido es la prueba definitiva: eso lo pide el navegador del visitante' );
ok( has( $r['out'], 'IP' ), 'y el aviso dice que lo que se filtra es la IP' );
ok( has( $r['out'], 'sentencias' ), 'y que hay sentencias contra el titular de la web' );

/* F. gstatic sin googleapis: es el caso del @font-face autoalojado mal, o de un
      preconnect. Son DOS agujas y buscar solo una deja pasar la mitad. */
wp_fake_reset();
$GLOBALS['wp']['http'] = array(
	'https://sitio.test/' => respuesta( 200, pagina( '<link rel="preload" as="font" href="https://fonts.gstatic.com/s/inter/v13/x.woff2" crossorigin>' ) ),
);
$r = grab( 'es_front_font_probe' );
ok( 'google' === $r['ret'], 'gstatic tambien: el fichero de la fuente sale del mismo tercer pais que la hoja' );

/* G. Y EL ESTADO LIMPIO, que es la razon de que esta funcion exista: es lo unico
      en todo el framework que puede decir honestamente que este sitio no le pide
      nada a Google. Se calla, porque un sitio correcto no tiene que oir nada. */
wp_fake_reset();
$GLOBALS['wp']['http'] = array(
	'https://sitio.test/' => respuesta( 200, pagina( '<link rel="stylesheet" href="https://sitio.test/wp-content/themes/x/fuentes.css">' ) ),
);
$r = grab( 'es_front_font_probe' );
ok( 'limpio' === $r['ret'], 'una pagina de verdad con cero peticiones a Google es lo unico que limpia el sin-confirmar del build' );
ok( '' === $r['out'], 'y no avisa de nada' );

/* H. La URL se puede pedir, y es lo que hace util la sonda: 'limpio' habla SOLO
      de la URL mirada, asi que qa-review tiene que poder recorrer las paginas
      construidas y no solo la raiz. */
wp_fake_reset();
$GLOBALS['wp']['http'] = array(
	'https://sitio.test/'         => respuesta( 200, pagina() ),
	'https://sitio.test/contacto/' => respuesta( 200, pagina( '<link href="https://fonts.googleapis.com/css?family=Manrope" rel="stylesheet">' ) ),
);
$r = grab(
	function () {
		return es_front_font_probe( 'https://sitio.test/contacto/' );
	}
);
ok( 'google' === $r['ret'], 'con una URL explicita mira ESA pagina: la raiz limpia no absuelve a las demas' );
ok( in_array( 'https://sitio.test/contacto/', $GLOBALS['wp']['http_pedidas'], true ), 'y la peticion fue a esa URL, no a home_url()' );

/* I. EL LIMITE CONOCIDO, escrito como assertion para que nadie lo descubra como
      bug ni asuma que esta cubierto. Una fuente que carga JAVASCRIPT en tiempo
      de ejecucion no deja la URL de Google en el HTML servido, asi que esta
      sonda dice 'limpio' de una pagina que si le pide la fuente a Google en
      cuanto el navegador ejecuta el script. No es un fallo arreglable aqui: un
      fetch del servidor no puede ver lo que el navegador pide DESPUES. Lo que si
      lo ve es la fila 21 de house-rules.md, que abre la pagina en un contexto
      limpio y lista las peticiones salientes de verdad. Escrito en el docblock y
      en la fila; aqui queda clavado para que un cambio futuro no lo borre sin
      enterarse. */
wp_fake_reset();
$GLOBALS['wp']['http'] = array(
	'https://sitio.test/' => respuesta( 200, pagina( '<script src="https://ajax.googleapis.com/ajax/libs/webfont/1.6.26/webfont.js"></script><script>WebFont.load({google:{families:["Inter:400,700"]}});</script>' ) ),
);
$r = grab( 'es_front_font_probe' );
ok( 'limpio' === $r['ret'], 'LIMITE: una fuente cargada por JS no esta en el HTML servido y esta sonda la da por limpia — lo mira la fila 21, no esta' );

/* ---------------------------------------------------------------------------
 * EL KIT GLOBAL: donde es_tokens() se convierte en el sitio.
 *
 * Hallazgo del primer build real (LocalWP `prueba1`, 2026-09-09). es_tokens() pinta SOLO donde un
 * helper escribe un color explicito. El fondo de la pagina, un titular sin `title_color` y los
 * enlaces heredan del KIT de Elementor — y nada en esta libreria lo escribia.
 * MEDIDO en el sitio construido: el h1 salio `rgb(110,193,228)` — el azul de fabrica de Elementor —
 * sobre un body BLANCO, con la escala tipografica perfectamente correcta encima. Cinco paginas con
 * VEREDICTO LIMPIO sobre un sitio que no tenia ni uno de los colores resueltos.
 * knowledge.md ya lo decia en una linea ("set global colors there so the whole site inherits"), y
 * escribir la explicacion no es instalar el helper. Esta es la mitad que faltaba.
 * ------------------------------------------------------------------------- */
echo "--- el kit global lleva los tokens al sitio, y se relee ---\n";

wp_fake_reset();
$r = grab( 'es_kit_apply' );
ok( 0 === $r['ret'], 'sin kit activo no se escribe nada y se devuelve 0' );
ok( has( $r['out'], 'kit' ), 'y se DICE, en vez de devolver 0 en silencio' );

wp_fake_reset();
$GLOBALS['wp']['options']['elementor_active_kit'] = 5;
$kit_id = es_kit_apply();
ok( 5 === $kit_id, 'con kit activo se devuelve su id, que es la prueba de que la RELECTURA cuadro' );

$ks = get_post_meta( 5, '_elementor_page_settings', true );
ok( is_array( $ks ) && isset( $ks['system_colors'] ), 'se escriben los colores de sistema' );

$por_id = array();
foreach ( (array) $ks['system_colors'] as $c ) {
	$por_id[ $c['_id'] ] = $c['color'];
}
ok(
	array( 'primary', 'secondary', 'text', 'accent' ) === array_keys( $por_id ),
	'los CUATRO ids de Elementor, en ese orden: cada widget resuelve Global Colors POR ID, nunca por titulo'
);
ok( es_t( 'text' ) === $por_id['primary'], 'primary es la tinta de titulares — el rol exacto que pintaba el azul de fabrica' );
ok( es_t( 'accent' ) === $por_id['accent'], 'y accent es el acento resuelto' );
ok( es_t( 'bg' ) === $ks['body_background_color'], 'el fondo de pagina sale del token ground, que es lo que faltaba entero' );
ok( 'classic' === $ks['body_background_background'], 'con su tipo de fondo, o Elementor ignora el color' );

/* Un kit real llega con ajustes que no son nuestros — un ancho de contenedor, una tipografia
   global. Pisarlos seria cambiar el sitio por la espalda. */
wp_fake_reset();
$GLOBALS['wp']['options']['elementor_active_kit'] = 5;
update_post_meta( 5, '_elementor_page_settings', array( 'container_width' => array( 'size' => 1140 ) ) );
es_kit_apply();
$ks = get_post_meta( 5, '_elementor_page_settings', true );
ok( isset( $ks['container_width']['size'] ) && 1140 === $ks['container_width']['size'], 'un ajuste ajeno del kit sobrevive: esto FUSIONA, no reemplaza' );
ok( isset( $ks['system_colors'] ), 'y los colores entran igual' );

/* Y lo que escribe son los tokens del PROYECTO, no los defaults de este archivo. */
wp_fake_reset();
$GLOBALS['wp']['options']['elementor_active_kit'] = 5;
es_tokens_reset();
es_tokens( array( 'bg' => '#0E1113', 'accent' => '#FF3D8A' ) );
es_kit_apply();
$ks     = get_post_meta( 5, '_elementor_page_settings', true );
$por_id = array();
foreach ( (array) $ks['system_colors'] as $c ) {
	$por_id[ $c['_id'] ] = $c['color'];
}
ok( '#0E1113' === $ks['body_background_color'], 'el override del proyecto llega al kit' );
ok( '#FF3D8A' === $por_id['accent'], 'y el acento del proyecto tambien' );
es_tokens_reset();

echo "\n$pass OK / $fail FAIL\n";
exit( $fail ? 1 : 0 );
