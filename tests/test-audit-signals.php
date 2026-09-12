<?php
/**
 * Behavioural assertions for the audit's SIGNALS: what each channel is allowed to mute, and what
 * each return value means.
 *
 * Run:  php tests/test-audit-signals.php     (exit 0 = green)
 *
 * Why a second test file. `tests/test-container-hygiene.php` defines ES_AUDIT_SILENT for its whole
 * process, and PHP cannot undefine a constant, so that file structurally CANNOT prove the constant
 * mutes the right things — it only ever observes the muted world. This one runs both worlds: the
 * parent defines the constant and asserts what stays silent, then spawns ITSELF with `--loud`,
 * which does not define it, and asserts the same calls do print. A child that fails takes the
 * parent down with it.
 *
 * Two rules are under test and both were broken:
 *
 *   1. ES_AUDIT_SILENT mutes the audit REPORT, never a warning. es_warn() gated its echo on the
 *      same constant, so anything that silenced the report to keep stdout clean for another
 *      consumer also silenced "this template will NOT appear on the front end" — and the other
 *      road, error_log(), is the server log nobody fetches.
 *   2. es_audit_summary() returned one integer for two different worlds. 0 meant "audited, clean"
 *      AND "es_container_report() never ran", so a build function that forgot to wire the audit in
 *      reported exactly what a passing build reports. A wiring bug is not a clean build.
 */
/* Falla CERRADO: adivinar la mitad hija hacia que el padre saliera 0 tras correr 3 de 28. */
if ( ! isset( $argv ) ) {
	fwrite( STDERR, "ENTORNO: sin \$argv no se puede elegir padre o hijo (register_argc_argv?)\n" ); exit( 2 );
}
$loud = in_array( '--loud', $argv, true );

define( 'ABSPATH', __DIR__ );
if ( ! $loud ) {
	define( 'ES_AUDIT_SILENT', true );
}

$GLOBALS['stub_img'] = array();
function get_posts( $a ) { return array(); }
function wp_get_attachment_url( $id ) { return 'https://x.test/' . $id . '.jpg'; }

$GLOBALS['es_log'] = tempnam( sys_get_temp_dir(), 'essig' );
if ( false === ini_set( 'error_log', $GLOBALS['es_log'] ) ) {
	fwrite( STDERR, "ENTORNO: ini_set('error_log') fue rechazado, el canal durable no se puede leer\n" ); exit( 2 );
}
register_shutdown_function( function () { @unlink( $GLOBALS['es_log'] ); } );

require_once dirname( __DIR__ ) . '/skills/elementor-core/assets/es-builder.php';

$pass = 0; $fail = 0;
function ok( $cond, $label ) {
	global $pass, $fail;
	if ( $cond ) { $pass++; echo "  OK   $label\n"; }
	else { $fail++; echo "  FAIL $label\n"; }
}

/** Run $fn with stdout captured, and hand back BOTH what it printed and what it returned. */
function grab( $fn ) {
	ob_start();
	$ret = $fn();
	return array( 'out' => ob_get_clean(), 'ret' => $ret );
}
/** error_log() is the durable channel and the ONLY one left when the report is muted. */
function log_mark() {
	clearstatcache();
	return strlen( (string) @file_get_contents( $GLOBALS['es_log'] ) );
}
function log_since( $offset ) {
	clearstatcache();
	return substr( (string) @file_get_contents( $GLOBALS['es_log'] ), $offset );
}
function legacy_el( $type, array $children ) {
	return array( 'elType' => $type, 'settings' => array(), 'elements' => $children );
}

$limpia   = array( es_split( array( es_h( 'a' ), es_h( 'b' ) ) ) );
$sucia    = array( es_section( array( es_row( array( es_h( 'a' ), es_h( 'b' ) ) ) ) ) );
$heredada = array( legacy_el( 'section', array( legacy_el( 'column', array( es_h( 'x' ) ) ) ) ) );

if ( $loud ) {
	echo "=== hijo --loud: SIN ES_AUDIT_SILENT ===\n";

	$r = grab( function () use ( $limpia ) { return es_container_report( $limpia, 'p' ); } );
	ok( false !== strpos( $r['out'], 'WordPress Orchestrator contenedores' ), 'sin la constante, el reporte por pagina SI se imprime' );

	$r = grab( 'es_audit_summary' );
	ok( false !== strpos( $r['out'], 'VEREDICTO' ), 'sin la constante, el veredicto SI se imprime' );

	$r = grab( function () { es_warn( 'algo se rompio' ); return null; } );
	ok( false !== strpos( $r['out'], 'WordPress Orchestrator AVISO: algo se rompio' ), 'y el aviso tambien' );

	echo "\nHIJO-LOUD $pass OK / $fail FAIL\n";
	exit( $fail ? 1 : 0 );
}

echo "=== padre: CON ES_AUDIT_SILENT ===\n";

echo "--- la constante silencia el REPORTE, nunca un aviso ---\n";
$r = grab( function () { es_warn( 'este template NO va a aparecer en el front' ); return null; } );
ok( '' !== $r['out'], 'es_warn() llega a stdout aunque el reporte este silenciado' );
ok( false !== strpos( $r['out'], 'WordPress Orchestrator AVISO: este template NO va a aparecer en el front' ), 'con el mensaje entero, no un resumen' );

$r = grab( function () use ( $limpia ) { return es_container_report( $limpia, 'p' ); } );
ok( '' === $r['out'], 'el reporte por pagina SI se silencia' );
ok( is_array( $r['ret'] ) && isset( $r['ret']['unaudited'] ), 'y sigue devolviendo el audit completo a quien lo llamo' );

$r = grab( 'es_audit_summary' );
ok( '' === $r['out'], 'el veredicto tambien se silencia' );

echo "--- 'nada auditado' dejo de parecerse a 'limpio' ---\n";
$GLOBALS['es_audit_runs'] = array();
$mark = log_mark();
$r    = grab( 'es_audit_summary' );
ok( -1 === $r['ret'], 'ninguna pagina auditada devuelve -1, no 0' );
/* Nacio afirmando lo CONTRARIO. Un aviso que se calla con el interruptor de la rutina no es aviso. */
ok( '' !== $r['out'], 'y NO se puede callar: silenciar el reporte no puede silenciar "no hubo reporte"' );
ok( false !== strpos( $r['out'], 'SIN AUDITAR' ), 'con el veredicto nombrado en el aviso' );
$linea = log_since( $mark );
ok( false !== strpos( $linea, 'SIN AUDITAR' ), 'y queda tambien en el log durable' );

echo "--- cada veredicto tiene su codigo y su linea ---\n";
$GLOBALS['es_audit_runs'] = array();
$mark = log_mark();
es_container_report( $limpia, 'limpia' );
ok( 0 === es_audit_summary(), 'un build auditado y limpio sigue devolviendo 0' );
ok( false !== strpos( log_since( $mark ), 'VEREDICTO LIMPIO' ), 'y su linea lo nombra' );

$GLOBALS['es_audit_runs'] = array();
$mark = log_mark();
es_container_report( $sucia, 'sucia' );
ok( 1 === es_audit_summary(), 'un build con offenders devuelve el conteo' );
$linea = log_since( $mark );
ok( false !== strpos( $linea, 'VEREDICTO A CORREGIR' ), 'A CORREGIR tiene su propia linea' );

$GLOBALS['es_audit_runs'] = array();
$mark = log_mark();
es_container_report( $heredada, 'heredada' );
ok( -2 === es_audit_summary(), 'un arbol que el audit no supo juzgar devuelve -2, no 0' );
$linea = log_since( $mark );
ok( false !== strpos( $linea, 'VEREDICTO NO AUDITABLE' ), 'NO AUDITABLE tiene su propia linea' );
ok( false !== strpos( $linea, '2 elementos' ), 'y cuenta cuantos elementos no pudo juzgar' );

echo "--- cuando hay de las dos, gana la que dice que no se pudo juzgar ---\n";
/* La heredada va PRIMERO a proposito: si fuera al reves, un acumulador que se pisara por pagina en
   vez de sumar seguiria dando el mismo numero y la assertion no lo notaria. */
$GLOBALS['es_audit_runs'] = array();
es_container_report( $heredada, 'heredada' );
es_container_report( $sucia, 'sucia' );
ok( -2 === es_audit_summary(), '-2 le gana al conteo de offenders: no se puede exigir arreglar lo que no se juzgo' );

echo "--- el conteo de no auditables suma elementos, no tipos ---\n";
/* Dos column bajo un mismo section: 3 elementos en 2 tipos. Con un contador que sumara 1 por tipo
   daria 2 y la fixture anterior no lo distinguia, porque alli cada tipo aparecia una sola vez. */
$GLOBALS['es_audit_runs'] = array();
$mark = log_mark();
es_container_report( array( legacy_el( 'section', array( legacy_el( 'column', array( es_h( 'a' ) ) ), legacy_el( 'column', array( es_h( 'b' ) ) ) ) ) ), 'importada' );
ok( -2 === es_audit_summary(), 'sigue siendo no auditable' );
ok( false !== strpos( log_since( $mark ), '3 elementos' ), 'y cuenta 3 elementos, no 2 tipos' );

echo "--- el hijo --loud corre el mundo opuesto ---\n";
/* $code arrancaba en 0 — el valor de exito — asi que un exec() que no ejecuta nada pasaba verde, y
   el codigo de salida no distingue un hijo que asserto de uno al que le borraron las assertions. */
if ( ! function_exists( 'exec' ) ) {
	ok( false, 'ENTORNO, no el cambio: exec() esta deshabilitado, asi que el mundo --loud no se pudo verificar en esta maquina' );
} else {
	$cmd  = escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( __FILE__ ) . ' --loud';
	$out  = array();
	$code = -1;                       /* NO 0: exec() solo lo escribe si el proceso corrio de verdad */
	$ran  = exec( $cmd . ' 2>&1', $out, $code );
	$txt  = implode( "\n", $out );
	ok( false !== $ran && -1 !== $code, 'el proceso hijo se pudo lanzar' );
	ok( 0 === $code, 'y termina en verde' );
	ok( false !== strpos( $txt, 'HIJO-LOUD 3 OK / 0 FAIL' ), 'habiendo corrido sus 3 assertions, no simplemente saliendo 0' );
	if ( 0 !== $code || false === strpos( $txt, 'HIJO-LOUD' ) ) {
		echo '    ' . str_replace( "\n", "\n    ", $txt ) . "\n";
	}
}

echo "\n$pass OK / $fail FAIL\n";
exit( $fail ? 1 : 0 );
