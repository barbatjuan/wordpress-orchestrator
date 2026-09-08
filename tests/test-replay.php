<?php
/**
 * Behavioural assertions for REPLAY: building the same page twice must emit the same bytes.
 *
 * Run:  php tests/test-replay.php     (exit 0 = green)
 *
 * Why this suite exists. Migrating a finished site to production by re-executing its build script
 * — rather than by copying and search-replacing a database — only works if the script is
 * deterministic. `_elementor_data` is stored through `wp_slash( wp_json_encode( … ) )`, so URLs
 * inside it carry JSON's `\/` escaping; a serialization-aware search-replace does not see them
 * and fails silently and partially. Replay never meets that failure. But replay trades it for a
 * different one: a build that is NOT byte-stable produces a production site that merely resembles
 * the approved one, and nothing downstream would notice — every check would stay green.
 *
 * So determinism stops being a property somebody believes and becomes a property something reads.
 * That is this file.
 *
 * Two things have to hold, and they fail in opposite directions:
 *
 *   1. Same input, same output. Element ids come from `es_uid()` (`md5( $seed . '-' . $n )`,
 *      reset per page by `es_uid_reset()`), so they are stable BY CONSTRUCTION — as long as the
 *      reset actually happens. A build that forgets it emits different ids on the second run and
 *      the digest comparison at migration time reports a mismatch nobody can explain.
 *   2. Same LIBRARY. Two runs are only comparable if the same `es-builder.php` produced both.
 *      A `git pull` between the local build and the production replay changes the emitted bytes
 *      with nothing recording that it did, which is why `es_build_fingerprint()` exists and why
 *      the manifest has to carry it.
 *
 * The `es_tokens()` hazard is under test here too, and it is the subtle one: the function caches
 * in `static $t` and only recomputes when `$override` is truthy. A single `es_t()` reached before
 * the override is set returns defaults, caches them, and every later call agrees — producing a
 * site built on the tamest corner of the system with every check reporting green.
 *
 * The fixture is the shared one in lib/fake-wp.php, deliberately NOT a copy: two fake WordPresses
 * drift, and the day they differ is the day this suite proves determinism the other one lost.
 */
$GLOBALS['es_suite'] = 'replay';
require_once __DIR__ . '/lib/fake-wp.php';

echo "=== replay ===\n";

/* ---------------------------------------------------------------------------
 * El manifiesto tiene que poder guardar CON QUE se construyo.
 * ------------------------------------------------------------------------- */
echo "--- la seccion 'build' del manifiesto ---\n";

/* Sin una seccion declarada, la huella de la libreria no tiene donde vivir, y una garantia de
   determinismo que nadie anota no es una garantia: es una promesa. */
ok(
	in_array( 'build', es_manifest_sections(), true ),
	"es_manifest_sections() declara 'build': la huella de la libreria tiene donde vivir"
);

/* ---------------------------------------------------------------------------
 * La huella: CON QUE libreria se construyo.
 * ------------------------------------------------------------------------- */
echo "--- es_build_fingerprint() ---\n";

$fp = es_build_fingerprint();

/* El sha1 de la libreria es el unico campo que responde la pregunta que hunde un replay:
   "el build local y el replay a produccion, los corrio el MISMO es-builder.php?". Un `git pull`
   entre los dos cambia los bytes emitidos sin que nada lo registre. */
ok(
	isset( $fp['library_sha1'] ) && $fp['library_sha1'] === sha1_file( $GLOBALS['es_repo'] . '/skills/elementor-core/assets/es-builder.php' ),
	'library_sha1 es el sha1 del es-builder.php que esta corriendo, no una version declarada aparte'
);
/* La version de PHP importa porque json_encode() no formatea igual en todas: un digest que no
   coincide sobre la MISMA entrada deja de ser un misterio y pasa a ser diagnosticable. */
ok(
	isset( $fp['php'] ) && $fp['php'] === PHP_VERSION,
	'php es la version que esta corriendo ahora mismo'
);

/* LO IMPORTANTE. Este fixture no tiene ni WordPress ni Elementor cargados, asi que la huella no
   PUEDE leer sus versiones — y una huella que rellena ese hueco con un numero verosimil es peor
   que una que lo deja vacio: al comparar dos sitios, dos '3.0.0' inventados coinciden y declaran
   identico un par que nadie comprobo. es_save_page() si tiene una version de repuesto ('3.0.0')
   porque Elementor espera ese meta; una huella no espera nada, la LEE. */
ok(
	isset( $fp['elementor'] ) && 'unknown' === $fp['elementor'],
	'una version de Elementor que no se pudo leer se marca unknown, no se rellena con una verosimil'
);
ok(
	isset( $fp['elementor_pro'] ) && 'unknown' === $fp['elementor_pro'],
	'lo mismo para Elementor Pro'
);
ok(
	isset( $fp['wp'] ) && 'unknown' === $fp['wp'],
	'y lo mismo para WordPress'
);

/* ---------------------------------------------------------------------------
 * El peligro de la cache estatica de es_tokens().
 * ------------------------------------------------------------------------- */
echo "--- empezar un build desde un estado conocido ---\n";

/* MEDIDO, no supuesto: es_tokens() cachea en `static $t` y solo recalcula cuando $override es
   VERDADERO, y array() es falso. Asi que un segundo build en el mismo proceso que pide
   es_tokens( array() ) no recibe los defaults — recibe la paleta del build anterior, en silencio
   y con todos los chequeos en verde. Es la misma enfermedad que el resto de este repo persigue:
   un run que reporta exito sobre trabajo que no hizo.
   Da igual en el camino de hoy, donde cada build es un proceso. Deja de dar igual en cuanto una
   fabrica encadena sitios, y rompe la premisa del replay: un build que depende de lo que corrio
   ANTES que el, en el mismo proceso, no es reproducible por definicion. */
$es_accent_default = es_t( 'accent' );
es_tokens( array( 'accent' => '#123456' ) );
ok( '#123456' === es_t( 'accent' ), 'un override entra (control: si esto falla, el resto no significa nada)' );

es_tokens_reset();
ok(
	$es_accent_default === es_t( 'accent' ),
	'es_tokens_reset() devuelve la paleta a los defaults: el siguiente sitio no hereda al anterior'
);

/* ---------------------------------------------------------------------------
 * Determinismo: construir la MISMA pagina dos veces emite los MISMOS bytes.
 * ------------------------------------------------------------------------- */
echo "--- el mismo build dos veces ---\n";

/* Esto es lo que retira el "no verifier" que elementor-core/SKILL.md llevaba declarado sobre los
   ids deterministas: nada reconstruia una pagina dos veces para diferenciar los ids generados, asi
   que un id no determinista solo aparecia despues, como un diff espurio. Ahora aparece aqui.
   Es la premisa entera de migrar por replay: si el mismo script no emite los mismos bytes, el
   sitio de produccion se PARECE al aprobado y nada rio abajo lo nota. */
function replay_build( $slug ) {
	es_uid_reset( $slug );
	return array( es_split( array( es_h( 'uno' ), es_h( 'dos' ) ) ) );
}

/** Construye la pagina en un sitio limpio y devuelve el _elementor_data que quedo escrito. */
function replay_emit( $slug ) {
	wp_fake_reset();
	approve( $slug );
	$accion = null;
	grab(
		function () use ( $slug, &$accion ) {
			return es_save_page( $slug, 'Pagina', replay_build( $slug ), 'elementor_header_footer', $accion );
		}
	);
	foreach ( $GLOBALS['wp']['meta'] as $meta ) {
		if ( isset( $meta['_elementor_data'] ) ) {
			return $meta['_elementor_data'];
		}
	}
	return null;
}

$uno = replay_emit( 'servicios' );
$dos = replay_emit( 'servicios' );

ok( null !== $uno && '' !== $uno, 'el build escribio algo (control: comparar dos vacios pasaria sin probar nada)' );
ok( $uno === $dos, 'dos builds de la misma pagina emiten bytes identicos' );

/* CONTROL. Sin este, lo de arriba podria estar pasando porque la comparacion no mira nada.
   Sin es_uid_reset() el contador sigue donde lo dejo el build anterior y los ids se mueven —
   que es precisamente el fallo que el SKILL.md declaraba sin vigilante. */
wp_fake_reset();
approve( 'contacto' );
$accion = null;
grab(
	function () use ( &$accion ) {
		return es_save_page( 'contacto', 'Pagina', array( es_split( array( es_h( 'uno' ), es_h( 'dos' ) ) ) ), 'elementor_header_footer', $accion );
	}
);
$sin_reset = null;
foreach ( $GLOBALS['wp']['meta'] as $meta ) {
	if ( isset( $meta['_elementor_data'] ) ) {
		$sin_reset = $meta['_elementor_data'];
	}
}
ok(
	$sin_reset !== $uno,
	'y sin es_uid_reset() los ids se mueven: la comparacion de arriba SI puede fallar, no es vacua'
);

echo "\n$pass OK / $fail FAIL\n";
exit( $fail ? 1 : 0 );
