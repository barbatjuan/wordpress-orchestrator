<?php
/**
 * Behavioural assertions for the container-hygiene helpers + audit.
 *
 * Run:  php tests/test-container-hygiene.php     (exit 0 = green)
 *
 * Why this exists: `es_container_audit()` is the thing that enforces the "fewest containers"
 * house rule on every build. Without a regression guard, the enforcer itself is unenforced —
 * which is exactly the failure mode it was written to prevent. Stubs the handful of WordPress
 * functions the builder touches, then asserts on real element trees. No WordPress needed.
 */
define( 'ABSPATH', __DIR__ );
define( 'ES_AUDIT_SILENT', true );

$GLOBALS['stub_img'] = array( 'foto' => 42 );
function get_posts( $a ) {
	if ( isset( $GLOBALS['stub_img'][ $a['name'] ] ) ) {
		$o     = new stdClass();
		$o->ID = $GLOBALS['stub_img'][ $a['name'] ];
		return array( $o );
	}
	return array();
}
function wp_get_attachment_url( $id ) { return 'https://x.test/' . $id . '.jpg'; }
/* Kept in a global so the report's TEXT is assertable. This file defines ES_AUDIT_SILENT for the
   whole process, so es_container_report()'s stdout copy is muted and error_log() is the only
   channel left — without this, the report could print anything at all and nothing would notice. */
$GLOBALS['es_log'] = tempnam( sys_get_temp_dir(), 'eslog' );
ini_set( 'error_log', $GLOBALS['es_log'] );
register_shutdown_function( function () { @unlink( $GLOBALS['es_log'] ); } );
function log_since( $offset ) {
	clearstatcache();
	return substr( (string) @file_get_contents( $GLOBALS['es_log'] ), $offset );
}
function log_mark() {
	clearstatcache();
	return strlen( (string) @file_get_contents( $GLOBALS['es_log'] ) );
}

require_once dirname( __DIR__ ) . '/skills/elementor-core/assets/es-builder.php';

$pass = 0; $fail = 0;
function ok( $cond, $label ) {
	global $pass, $fail;
	if ( $cond ) { $pass++; echo "  OK   $label\n"; }
	else { $fail++; echo "  FAIL $label\n"; }
}
function grab_out( $fn ) {
	ob_start();
	$ret = $fn();
	return array( 'out' => ob_get_clean(), 'ret' => $ret );
}
function has( array $list, $needle ) {
	foreach ( $list as $l ) { if ( false !== strpos( $l, $needle ) ) { return true; } }
	return false;
}

echo "--- es_split: la seccion ES la fila ---\n";
es_uid_reset( 't1' );
$split = es_split( array( es_h( 'A' ), es_p( 'B' ) ) );
ok( 'row' === $split['settings']['flex_direction'], 'flex_direction row en desktop' );
ok( 'column' === $split['settings']['flex_direction_mobile'], 'apila en mobile' );
ok( 2 === count( $split['elements'] ), 'los hijos son directos, sin fila intermedia' );
$a = es_container_audit( array( $split ) );
ok( 1 === $a['containers'] && 2 === $a['widgets'], 'un solo contenedor para dos columnas' );
ok( 1 === $a['max_depth'], 'profundidad 1' );
ok( ! $a['offenders'], 'sin offenders' );

echo "--- el patron viejo se detecta como offender ---\n";
es_uid_reset( 't2' );
$viejo = es_section( array( es_row( array( es_h( 'A' ), es_p( 'B' ) ) ) ) );
$a = es_container_audit( array( $viejo ) );
ok( 2 === $a['containers'], 'el patron viejo cuesta 2 contenedores' );
ok( has( $a['offenders'], 'la seccion ES la fila' ), 'seccion>fila flex marcada como offender' );

echo "--- seccion>grid sigue siendo solo fusionable, no error ---\n";
es_uid_reset( 't3' );
$grid = es_section( array( es_grid( 3, array( es_h( 'a' ), es_h( 'b' ), es_h( 'c' ) ) ) ) );
$a = es_container_audit( array( $grid ) );
ok( ! $a['offenders'], 'el idioma dominante del repo no dispara falsos positivos' );
ok( has( $a['optimizable'], 'unico hijo es un grid' ), 'reportado como fusionable' );

echo "--- un grid en la RAIZ: es_grid() con $inner = false, y la auditoria lo juzga bien ---\n";
es_uid_reset( 't3b' );
ok( true === es_grid( 2, array( es_p( 'a' ), es_p( 'b' ) ) )['isInner'], 'por defecto es_grid() sigue siendo interior: quien no opta no cambia' );
$raiz = es_grid( 3, array( es_h( 'a' ), es_h( 'b' ), es_h( 'c' ) ), 24, array( 'content_width' => 'boxed' ), false );
ok( false === $raiz['isInner'], 'con $inner false el grid es de la raiz (isInner false), sin tener que reescribirlo a mano' );
ok( 'boxed' === $raiz['settings']['content_width'], 'y un grid boxed en la raiz conserva su ancho: no necesita seccion envolvente' );
$a = es_container_audit( array( $raiz ) );
ok( 1 === $a['containers'] && ! $a['offenders'] && ! $a['optimizable'], 'la auditoria lo da por limpio: un contenedor, sin offenders ni fusionables' );

echo "--- es_wide: ancho sin envoltorio ---\n";
es_uid_reset( 't4' );
$w = es_wide( es_p( 'texto' ), 58 );
ok( 'initial' === $w['settings']['_element_width'], '_element_width initial' );
ok( 58 === $w['settings']['_element_custom_width']['size'], '_element_custom_width 58%' );
ok( 100 === $w['settings']['_element_custom_width_mobile']['size'], 'full width en mobile' );
$a = es_container_audit( array( es_split( array( $w, es_p( 'otro' ) ) ) ) );
ok( 1 === $a['containers'], 'dar un ancho no agrego ningun contenedor' );

echo "--- envoltorio que solo da un ancho: offender con el fix nombrado ---\n";
es_uid_reset( 't5' );
$wrap = es_c( array( 'width' => es_size( 58, '%' ) ), array( es_p( 'texto' ) ) );
$a = es_container_audit( array( $wrap ) );
ok( has( $a['offenders'], 'es_wide' ), 'el mensaje nombra es_wide()' );

echo "--- es_photo: la foto es un widget ---\n";
es_uid_reset( 't6' );
$ph = es_photo( 'foto', 400 );
ok( 'widget' === $ph['elType'] && 'image' === $ph['widgetType'], 'es un widget image' );
ok( 'cover' === $ph['settings']['object-fit'], 'object-fit cover' );
ok( ! empty( $ph['settings']['height']['size'] ), 'height presente (object-fit lo exige)' );
$a = es_container_audit( array( es_split( array( $ph, es_p( 'copy' ) ) ) ) );
ok( 1 === $a['containers'] && ! $a['offenders'], 'foto + copy = un contenedor, cero offenders' );

echo "--- contenedor vacio con imagen de fondo ---\n";
es_uid_reset( 't7' );
$bg = es_c( array( 'background_background' => 'classic', 'background_image' => es_img( 'foto' ) ), array() );
$a = es_container_audit( array( $bg ) );
ok( has( $a['offenders'], 'es_photo' ), 'el mensaje apunta a es_photo()' );

echo "--- imagen de fondo rota ya no compra coartada ---\n";
$roto = es_container_earns_its_place( array( 'background_image' => es_img( 'no-existe' ) ) );
ok( false === $roto, 'url vacia no justifica el contenedor' );
$bueno = es_container_earns_its_place( array( 'background_image' => es_img( 'foto' ) ) );
ok( true === $bueno, 'url real si lo justifica' );

echo "--- profundidad > 3 ---\n";
es_uid_reset( 't8' );
$deep = es_c( array(), array( es_c( array(), array( es_c( array(), array( es_c( array(), array( es_h( 'x' ) ) ) ) ) ) ) ) );
$a = es_container_audit( array( $deep ) );
ok( 4 === $a['max_depth'], 'profundidad 4 medida' );
ok( has( $a['offenders'], 'profundidad 4' ), 'profundidad excesiva reportada' );

echo "--- arbol legacy section>column>widget: no auditable, nunca invisible ---\n";
es_uid_reset( 't9' );
function legacy_el( $type, array $children ) {
	return array( 'elType' => $type, 'settings' => array(), 'elements' => $children );
}
$legacy = array( legacy_el( 'section', array( legacy_el( 'column', array( es_h( 'titulo' ) ) ) ) ) );
$a      = es_container_audit( $legacy );
ok( 0 === $a['containers'], 'no hay Containers que contar' );
ok( 1 === $a['widgets'], 'el widget bajo dos envoltorios legacy SI se cuenta' );
ok( isset( $a['unaudited']['section'] ) && 1 === $a['unaudited']['section']['count'], 'section queda registrado como no auditable' );
ok( isset( $a['unaudited']['column'] ) && 1 === $a['unaudited']['column']['count'], 'column queda registrado como no auditable' );
ok( '/0' === $a['unaudited']['section']['first'], 'guarda donde aparecio el primero' );
ok( 2 === $a['max_depth'], 'cada envoltorio legacy cuenta como un nivel' );
ok( ! $a['offenders'], 'un arbol importado no es culpa de quien llama: no es offender' );

echo "--- un Container culpable escondido bajo un envoltorio legacy ---\n";
es_uid_reset( 't10' );
$mixto = array( legacy_el( 'section', array( es_c( array(), array( es_p( 'solo' ) ) ) ) ) );
$a     = es_container_audit( $mixto );
ok( 1 === $a['containers'], 'el Container bajo el envoltorio legacy se ve' );
ok( has( $a['offenders'], 'no aporta nada' ), 'y su falta se reporta igual que en la superficie' );

echo "--- contenido dentro de un widget tampoco es invisible ---\n";
es_uid_reset( 't11' );
$loop             = es_w( 'loop-grid', array() );
$loop['elements'] = array( es_c( array(), array( es_p( 'dentro' ) ) ) );
$a                = es_container_audit( array( $loop ) );
ok( 1 === $a['containers'] && 2 === $a['widgets'], 'el arbol que cuelga de un widget se recorre' );
ok( has( $a['offenders'], 'no aporta nada' ), 'y sus offenders cuentan' );

echo "--- debajo de lo que no se puede juzgar, el audit no afirma nada ---\n";
es_uid_reset( 't10b' );
/* Un Container cuyo UNICO hijo es un elType que el walk no sabe juzgar. El remedio de "envoltorio
   de 1 widget" no se puede seguir aqui: el hijo no es un widget. */
$opaco = array( legacy_el( 'section', array( es_c( array(), array( legacy_el( 'column', array( es_h( 'a' ), es_h( 'b' ) ) ) ) ) ) ) );
$a     = es_container_audit( $opaco );
ok( ! $a['offenders'], 'un envoltorio cuyo unico hijo no es juzgable no recibe un remedio imposible' );
ok( 2 === $a['widgets'], 'y el arbol de abajo se sigue recorriendo entero' );
/* La profundidad se MIDE igual; lo que no se hace es cobrarsela a quien no la escribio. */
$hibrido = array( legacy_el( 'section', array( legacy_el( 'column', array( legacy_el( 'section', array( legacy_el( 'column', array( es_c( array(), array( es_h( 'a' ), es_h( 'b' ) ) ) ) ) ) ) ) ) ) ) );
$a       = es_container_audit( $hibrido );
ok( 5 === $a['max_depth'], 'la profundidad heredada se mide' );
ok( ! has( $a['offenders'], 'profundidad' ), 'pero no se cobra: 4 envoltorios importados no son una decision de anidado' );
$propio = es_c( array(), array( es_c( array(), array( es_c( array(), array( es_c( array(), array( es_h( 'x' ) ) ) ) ) ) ) ) );
ok( has( es_container_audit( array( $propio ) )['offenders'], 'profundidad 4' ), 'la profundidad que SI escribio quien llama se sigue cobrando' );
/* La seccion SI es boxed; el column de por medio no lo es nadie sabe. Si el ancho se heredara a
   traves de el, el contenedor de adentro seria un "boxed dentro de boxed" que nadie verifico. */
$boxeado = array( es_section( array( legacy_el( 'column', array( es_c( array( 'content_width' => 'boxed' ), array( es_p( 'x' ) ) ) ) ) ) ) );
ok( ! es_container_audit( $boxeado )['offenders'], 'debajo de un envoltorio opaco no se hereda un ancho boxed que nadie verifico' );

echo "--- unaudited agrega de verdad: cuenta y recuerda el PRIMERO ---\n";
$dos = array( legacy_el( 'section', array( legacy_el( 'column', array( legacy_el( 'section', array( es_h( 'x' ) ) ) ) ) ) ) );
$a   = es_container_audit( $dos );
ok( 2 === $a['unaudited']['section']['count'], 'dos section del mismo tipo suman, no se pisan' );
ok( '/0' === $a['unaudited']['section']['first'], 'y "first" guarda el mas superficial, no el ultimo visto' );

echo "--- un elemento sin elType se nombra, no se ignora ---\n";
$a = es_container_audit( array( array( 'settings' => array(), 'elements' => array() ) ) );
ok( isset( $a['unaudited']['(sin elType)'] ), 'sin elType tiene su propia entrada' );

echo "--- el remedio nombrado depende de la direccion del hijo ---\n";
es_uid_reset( 't12' );
$col = es_c( array(), array( es_c( array( 'flex_direction' => 'column' ), array( es_h( 'a' ), es_h( 'b' ) ) ) ) );
$a   = es_container_audit( array( $col ) );
ok( has( $a['offenders'], 'fusiona ambos' ), 'hijo en columna: fusionar los dos' );
ok( ! has( $a['offenders'], 'es_split()' ), 'es_split() NO se propone para un hijo en columna: cambiaria el eje' );
$rev = es_c( array(), array( es_c( array( 'flex_direction' => 'row-reverse' ), array( es_h( 'a' ), es_h( 'b' ) ) ) ) );
$a   = es_container_audit( array( $rev ) );
ok( has( $a['offenders'], 'es_split()' ), 'row-reverse sigue siendo una fila' );

echo "--- acotar un widget suelto al ancho boxed SI es un motivo ---\n";
es_uid_reset( 't13' );
$boxed = es_section( array( es_w( 'wc-archive-products', array() ) ) );
$a     = es_container_audit( array( $boxed ) );
ok( ! $a['offenders'], 'el unico modo de acotar un widget al ancho boxed no es un offender' );
$anidado = es_section( array( es_c( array( 'content_width' => 'boxed' ), array( es_p( 'x' ) ) ) ) );
$a       = es_container_audit( array( $anidado ) );
ok( has( $a['offenders'], 'acotar de nuevo no cambia el ancho' ), 'boxed dentro de boxed no aporta un segundo acotado' );
/* Un widget SI es un elemento que este walk juzga, asi que el contexto lo atraviesa: lo que cuelga
   de una plantilla de loop sigue estando dentro del ancho que acoto el contenedor de arriba. */
$enWidget             = es_w( 'loop-grid', array() );
$enWidget['elements'] = array( es_c( array( 'content_width' => 'boxed' ), array( es_p( 'x' ) ) ) );
$a                    = es_container_audit( array( es_section( array( $enWidget ) ) ) );
ok( has( $a['offenders'], 'acotar de nuevo no cambia el ancho' ), 'el ancho boxed se hereda a traves de un widget' );
ok( true === es_container_earns_its_place( array( 'content_width' => 'boxed' ), array( 'only_child' => 'widget' ) ), 'boxed + hijo widget + sin ancestro boxed: se gana el lugar' );
ok( false === es_container_earns_its_place( array( 'content_width' => 'boxed' ), array( 'only_child' => 'container' ) ), 'boxed no salva a un contenedor cuyo hijo es otro contenedor' );
ok( false === es_container_earns_its_place( array( 'content_width' => 'boxed' ), array( 'only_child' => 'widget', 'boxed_ancestor' => true ) ), 'con un ancestro boxed ya no aporta' );
ok( false === es_container_earns_its_place( array( 'content_width' => 'boxed' ) ), 'sin contexto no se regala el permiso' );

echo "--- cada remedio para un widget suelto tiene que poder hacerse ---\n";
$pad = es_container_audit( array( es_c( array( 'padding' => es_box( 40, 0, 40, 0 ) ), array( es_p( 'x' ) ) ) ) );
ok( has( $pad['offenders'], 'pasa el padding al widget' ), 'con padding, el remedio es pasarlo' );
$sin = es_container_audit( array( es_c( array(), array( es_p( 'x' ) ) ) ) );
ok( ! has( $sin['offenders'], 'padding' ), 'sin padding el mensaje ya no manda mover un padding que no existe' );

echo "--- el reporte por pagina dice lo que no pudo juzgar ---\n";
$mark = log_mark();
es_container_report( $legacy, 'heredada' );
$linea = log_since( $mark );
ok( false !== strpos( $linea, 'NO AUDITABLE' ), 'el reporte nombra la parte no auditada' );
ok( false !== strpos( $linea, 'elType "section" x1' ), 'con el elType y su conteo' );

echo "--- y el veredicto de corrida deja de llamarlo limpio ---\n";
/* Esta assertion nacio afirmando lo CONTRARIO, a proposito: mientras el contrato de senales no
   existia, decia que el veredicto devolvia 0 sobre un arbol no auditado. Al llegar ese contrato se
   puso roja sola y hubo que venir a cambiarla, que es justo lo que un comentario "pendiente" nunca
   hace. El detalle de los cuatro codigos vive en tests/test-audit-signals.php. */
$GLOBALS['es_audit_runs'] = array();
es_container_report( $legacy, 'heredada' );
ok( -2 === es_audit_summary(), 'un arbol que el audit no pudo juzgar devuelve -2, nunca 0' );

echo "--- una foto que no existe deja de pasar en silencio ---\n";
es_uid_reset( 't14' );
/* es_img() devolvia array('url'=>'','id'=>'') ante un slug inexistente y no decia nada, asi que una
   foto mal escrita entregaba un widget image SIN <img> y todas las verificaciones seguian verdes.
   Dos canales, porque el fallo ocurre al CONSTRUIR y se descubre al AUDITAR: el aviso ahora, y el
   offender cuando se recorre el arbol que se va a guardar. */
$r = grab_out( function () { return es_img( 'jamas-pedida' ); } );
ok( '' === $r['ret']['url'] && '' === $r['ret']['id'], 'sigue devolviendo url e id vacios a quien la pidio' );
ok( false !== strpos( $r['out'], 'jamas-pedida' ), 'pero AVISA, nombrando el slug que no encontro' );
ok( '' === grab_out( function () { return es_img( 'jamas-pedida' ); } )['out'], 'y avisa una sola vez por slug, no una por uso' );
ok( false !== strpos( grab_out( function () { return es_img( 'otro-que-falta' ); } )['out'], 'otro-que-falta' ), 'cada slug distinto si avisa' );

$rota = es_photo( 'jamas-pedida', 400 );
$a    = es_container_audit( array( es_split( array( $rota, es_p( 'copy' ) ) ) ) );
ok( has( $a['offenders'], 'sin imagen' ), 'y el arbol guardado la reporta como offender, no como limpio' );
ok( has( $a['offenders'], 'jamas-pedida' ), 'nombrando el slug para poder arreglarlo' );
$buena = es_photo( 'foto', 400 );
ok( ! es_container_audit( array( es_split( array( $buena, es_p( 'copy' ) ) ) ) )['offenders'], 'una foto real no dispara nada' );
/* El unico background_image lo pone un CONTENEDOR: en la rama de widgets no podia ejecutarse. */
$bgroto = es_c( array( 'background_background' => 'classic', 'background_image' => es_img( 'fondo-fantasma' ) ), array( es_h( 'a' ), es_h( 'b' ) ) );
ok( has( es_container_audit( array( $bgroto ) )['offenders'], 'fondo-fantasma' ), 'un fondo de CONTENEDOR que no existe tambien es offender' );
ok( has( es_container_audit( array( es_split( array( es_photo( '', 400 ), es_p( 'c' ) ) ) ) )['offenders'], 'sin imagen' ), 'un slug VACIO tambien, no solo uno falsy-distinto-de-vacio' );

echo "--- una clave en el elemento equivocado se guarda y no se aplica ---\n";
/* Elementor llama al mismo control distinto segun donde viva: un CONTENEDOR toma `padding`, un
   WIDGET toma `_padding`, porque ahi es un control de envoltorio. Escribir la forma de contenedor
   en un widget guarda bien, abre bien y no aplica nada. Es el fallo mas silencioso de la libreria:
   se ve en el codigo y no en la pantalla, asi que se vuelve a poner y a mirar con cara rara. */
$mal = es_w( 'heading', array( 'title' => 'x', 'padding' => es_box( 10, 10, 10, 10 ) ) );
$off = es_container_audit( array( es_split( array( $mal, es_p( 'y' ) ) ) ) )['offenders'];
ok( has( $off, 'sin guion bajo' ), 'padding sin guion bajo en un widget es offender' );
ok( has( $off, '_padding' ), 'y el aviso dice cual es la clave correcta' );

$bien = es_w( 'heading', array( 'title' => 'x', '_padding' => es_box( 10, 10, 10, 10 ) ) );
ok( ! es_container_audit( array( es_split( array( $bien, es_p( 'y' ) ) ) ) )['offenders'], 'con guion bajo no dispara nada' );

/* Y al reves: la forma de widget en un contenedor tampoco aplica. */
$cmal = es_c( array( '_padding' => es_box( 10, 10, 10, 10 ) ), array( es_h( 'a' ), es_h( 'b' ) ) );
ok( has( es_container_audit( array( $cmal ) )['offenders'], 'con guion bajo' ), 'un contenedor con _padding tambien es offender' );

/* La regla del envoltorio es cierta de los controles que TODO widget hereda, y falsa alli donde un
   widget define un control propio con el nombre pelado. Medido preguntandole a los 128 tipos que
   exponen controles en Elementor 4.2.2 + Pro 4.2.1, no leyendo documentacion: `padding` es un
   control real de tres widgets y `background_background` de siete. Sin esto el checker inventaba un
   offender en DIEZ tipos y le decia al autor que arreglara codigo que ya estaba bien, que cuesta
   mas que perderse uno — el mismo motivo por el que `width` no esta en la lista. */
$suyo = es_w( 'call-to-action', array( 'title' => 'x', 'padding' => es_box( 10, 10, 10, 10 ) ) );
ok( ! es_container_audit( array( es_split( array( $suyo, es_p( 'y' ) ) ) ) )['offenders'], 'un widget que posee "padding" de verdad no es offender' );
$suyo2 = es_w( 'button', array( 'text' => 'x', 'background_background' => 'classic' ) );
ok( ! es_container_audit( array( es_split( array( $suyo2, es_p( 'y' ) ) ) ) )['offenders'], 'ni uno que posee "background_background"' );
/* La excepcion es por TIPO, no por clave: heading no posee ninguna de las dos. */
$nosuyo = es_w( 'heading', array( 'title' => 'x', 'background_background' => 'classic' ) );
ok( has( es_container_audit( array( es_split( array( $nosuyo, es_p( 'y' ) ) ) ) )['offenders'], 'sin guion bajo' ), 'y el mismo ajuste en un widget que NO lo posee sigue siendo offender' );
/* Sin widgetType no hay a quien preguntar: se falla hacia REPORTAR, nunca hacia el silencio. */
$anon = array( 'elType' => 'widget', 'settings' => array( 'padding' => es_box( 10, 10, 10, 10 ) ) );
ok( has( es_container_audit( array( es_split( array( $anon, es_p( 'y' ) ) ) ) )['offenders'], 'sin guion bajo' ), 'un widget sin widgetType no compra la exencion: se falla hacia reportar' );

/* Una clave de layout de contenedor en un widget: no hay caja flex que configurar ahi. */
$flex = es_w( 'heading', array( 'title' => 'x', 'flex_direction' => 'row' ) );
ok( has( es_container_audit( array( es_split( array( $flex, es_p( 'y' ) ) ) ) )['offenders'], 'clave de CONTENEDOR' ), 'flex_direction en un widget es offender' );

/* El control de falsos positivos, que es lo que decide si este check sirve o estorba: `width` es
   clave de layout de contenedor Y control propio de varios widgets, asi que NO esta en la lista. */
$w = es_w( 'image', array( 'width' => es_size( 50, '%' ) ) );
ok( ! es_container_audit( array( es_split( array( $w, es_p( 'y' ) ) ) ) )['offenders'], 'width en un widget NO se marca: seria un offender inventado' );

/* Un widget que lleva las DOS formas ya tiene la buena puesta: avisar seria ruido. */
$dos = es_w( 'heading', array( 'title' => 'x', 'padding' => es_box( 1, 1, 1, 1 ), '_padding' => es_box( 2, 2, 2, 2 ) ) );
ok( ! es_container_audit( array( es_split( array( $dos, es_p( 'y' ) ) ) ) )['offenders'], 'llevando ambas, no se avisa: la correcta ya esta' );

echo "--- veredicto de corrida ---\n";
$GLOBALS['es_audit_runs'] = array();
es_container_report( array( es_split( array( es_h( 'a' ), es_h( 'b' ) ) ) ), 'limpia' );
ok( 0 === es_audit_summary(), 'build limpio devuelve 0 offenders' );
es_container_report( array( es_section( array( es_row( array( es_h( 'a' ), es_h( 'b' ) ) ) ) ) ), 'sucia' );
ok( 1 === es_audit_summary(), 'build sucio devuelve el conteo' );

echo "--- custom_css: el CSS de los ayudantes no cuenta, el escrito a mano si ---\n";
$mk_open  = ES_HELPER_CSS_OPEN;
$mk_close = ES_HELPER_CSS_CLOSE;
$helper_css = array(
	'es_btn primary'       => es_btn( 'x', '#', 'primary' )['settings']['custom_css'],
	'es_btn dark'          => es_btn( 'x', '#', 'dark' )['settings']['custom_css'],
	'es_btn outline'       => es_btn( 'x', '#', 'outline' )['settings']['custom_css'],
	'es_btn outline-light' => es_btn( 'x', '#', 'outline-light' )['settings']['custom_css'],
	'es_card_hover_css'    => es_card_hover_css(),
	'es_products_css'      => es_products_css(),
	'es_feature_card'      => es_feature_card( 'fas fa-check', 't', 'x' )['settings']['custom_css'],
	'es_grid'              => es_grid( 3, array( es_p( 'a' ) ) )['settings']['custom_css'],
);
foreach ( $helper_css as $who => $css ) {
	ok( 0 === strpos( $css, $mk_open ) && substr( $css, -strlen( $mk_close ) ) === $mk_close, "$who lleva el marcador al principio y al final" );
	ok( false !== strpos( $css, 'selector' ) && false === strpos( $mk_open . $mk_close, 'selector' ), "$who conserva `selector` y el marcador no lo contiene" );
	ok( substr_count( $css, '{' ) === substr_count( $css, '}' ), "$who sigue siendo CSS balanceado con el marcador" );
}
ok( '' === es_helper_css( '' ) && '' === es_helper_css( "  \n" ), 'es_helper_css no marca un CSS vacio' );

$limpia = array( es_section( array(
	es_btn( 'a', '#', 'primary' ), es_btn( 'b', '#', 'outline-light' ),
	es_feature_card( 'fas fa-check', 't', 'x' ),
	es_grid( 2, array( es_p( 'a' ) ) ),
	es_c( array( 'custom_css' => es_products_css() ), array( es_p( 'z' ) ), true ),
) ) );
$r = es_custom_css_audit( $limpia );
ok( 0 === $r['count'] && array() === $r['where'], 'una pagina solo con ayudantes cuenta 0 de CSS a mano' );

$mano = es_w( 'html', array( 'custom_css' => 'selector{color:red;}' ) );
$r = es_custom_css_audit( array( es_section( array( es_btn( 'a', '#', 'primary' ), $mano ) ) ) );
ok( 1 === $r['count'] && 1 === count( $r['where'] ), 'un custom_css a mano cuenta 1' );
ok( false !== strpos( $r['where'][0], $mano['id'] ) && false !== strpos( $r['where'][0], 'html' ), 'y dice el id y el widgetType: ' . $r['where'][0] );

$prof = es_c( array( 'custom_css' => 'selector{gap:1px;}' ), array(
	es_c( array(), array( es_w( 'heading', array( 'title' => 'h', 'custom_css' => '@media(max-width:767px){selector{color:red;}}' ) ), es_btn( 'a', '#', 'dark' ) ), true ),
), true );
$r = es_custom_css_audit( array( $prof ) );
ok( 2 === $r['count'], 'recorre contenedores anidados: el contenedor y el widget profundo, no el boton del ayudante' );
ok( 2 === count( $r['where'] ) && false !== strpos( $r['where'][0], '/0 container' ) && false !== strpos( $r['where'][1], '/0/0/0 widget heading' ), 'y cada ubicacion lleva su ruta y su tipo: ' . implode( ' | ', $r['where'] ) );

foreach ( array( '', '   ', "\n\t", '/* solo un comentario */', $mk_open . $mk_close ) as $vacio ) {
	ok( 0 === es_custom_css_audit( array( es_w( 'heading', array( 'title' => 'h', 'custom_css' => $vacio ) ) ) )['count'], 'CSS vacio o sin reglas cuenta 0: ' . json_encode( $vacio ) );
}
ok( 0 === es_custom_css_audit( array( es_w( 'heading', array( 'title' => 'h' ) ) ) )['count'], 'sin custom_css cuenta 0' );

/* Decision: el marcador envuelve el bloque del ayudante (apertura y cierre). Lo que un humano escribe
   FUERA del par cuenta como CSS a mano; con un solo prefijo, una regla anadida pasaba sin que nadie la viera. */
$anexo = es_btn( 'a', '#', 'primary' );
$anexo['settings']['custom_css'] .= 'selector{margin:0;}';
ok( 1 === es_custom_css_audit( array( $anexo ) )['count'], 'ayudante + regla anadida a mano despues del cierre: cuenta 1' );
$delante = es_btn( 'a', '#', 'primary' );
$delante['settings']['custom_css'] = 'selector{margin:0;}' . $delante['settings']['custom_css'];
ok( 1 === es_custom_css_audit( array( $delante ) )['count'], 'regla a mano delante del ayudante: cuenta 1' );
$extra = es_w( 'html', array( 'custom_css' => es_products_css( 'selector .x{margin:0;}' ) ) );
ok( 1 === es_custom_css_audit( array( $extra ) )['count'], 'es_products_css( $extra_css ): el extra a mano cuenta, la base no' );
ok( es_css_is_handwritten( 'a{b:c}' ) && ! es_css_is_handwritten( es_card_hover_css() ) && ! es_css_is_handwritten( '' ), 'es_css_is_handwritten sirve tambien para el CSS de pagina y del kit' );

echo "--- los ejemplos del framework escriben su CSS con el marcador ---\n";
foreach ( array( 'skills/elementor-theme-parts/assets/es-theme-parts.example.php', 'skills/woocommerce/assets/es-shop-template.example.php', 'skills/woocommerce/assets/es-product-single.example.php' ) as $rel ) {
	$src   = file_get_contents( dirname( __DIR__ ) . '/' . $rel );
	$todos = preg_match_all( "/'custom_css'\\s*=>/", $src );
	$vias  = preg_match_all( "/'custom_css'\\s*=>\\s*(es_helper_css|es_products_css|es_card_hover_css)\\(/", $src );
	ok( $todos > 0 && $todos === $vias, "$rel: los $todos custom_css pasan por un ayudante de marcado ($vias)" );
}

echo "\n$pass OK / $fail FAIL\n";
exit( $fail ? 1 : 0 );
