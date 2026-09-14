<?php
/**
 * veredicto.php — seals a Plantilla's veredicto against the bytes it judged, and checks the seal.
 *
 * WHY THIS FILE EXISTS. A veredicto (`plantillas/<slug>/veredicto.md`: judge B, the self-judged
 * declaration, the 430/768/1280 sweep, its counts and its findings) is only worth something while
 * it still describes the bytes on disk. `--sellar` stamps huella.php's fingerprint into the file's
 * `hash:` field; `--comprobar` recomputes it and says whether the stamp still answers for the
 * Plantilla. The format this file parses is `skills/web-templates/references/veredicto-formato.md`
 * (`openspec/changes/plantillas-reales/specs/veredicto-gate/spec.md` for the contract).
 *
 * THE FAILURE IT EXISTS TO PREVENT is a seal on a Plantilla nobody finished judging. So sealing
 * refuses, as a measured failure and without writing a byte, any veredicto that is incomplete (a
 * missing judged field, an empty sweep cell, counts that do not add up to the rows, a page the
 * Ficha declares and the sweep never visited, a cited finding nobody wrote down) or whose judge
 * said `no-profesional`. A sweep with open findings or skipped cells CAN be sealed — the veredicto
 * records what was seen — but `--comprobar` never reads it as current: a PARCIAL sweep is never a
 * pass (spec, "A Partial Sweep Records PARCIAL, Never PASS"), and a non-`✓` cell is a failure
 * (design.md, the barrido cell vocabulary).
 *
 * ONE FINGERPRINT DEFINITION. The hash is `huella_plantilla()`, required from huella.php, never
 * recomputed here: two implementations of one digest drift, and the day they disagree the
 * disagreement reads exactly like a stale veredicto. That also fixes what the hash covers —
 * `ficha.md`, `manifiesto-imagenes.md`, `canvas/**`, `maqueta/**`, `img/**`, LF-normalised text —
 * and keeps `veredicto.md` itself out of it, which is what makes sealing possible at all.
 *
 * ROOT. `--root` is the REPOSITORY root (the tree holding `skills/`), the same meaning
 * `framework-audit.php` gives it. huella.php's own `--root` is the `skills/` directory, so this
 * file appends `/skills` before calling it — one translation, in `veredicto_huella()`, nowhere else.
 *
 * EXIT CONTRACT, shared with the rest of the toolbox: `0` pass, `1` a measured failure (absent,
 * incomplete, not professional, unsealed, stale, partial, open findings), `2` usage or environment
 * (bad flags, an invalid slug, a Plantilla or library that does not exist). Library functions
 * throw `NmHerramientaMedida` / `NmHerramientaEntorno` (declared in color.php) and never exit();
 * only the CLI guard at the bottom maps them to codes.
 *
 * `--sellar-ruta <dir>` / `--comprobar-ruta <dir>` SEAL A CLIENT DELIVERY FOLDER THE SAME WAY, for a
 * folder that is not `skills/web-templates/references/plantillas/<slug>/` shaped — no `--root`, no
 * slug, just the folder itself, fingerprinted by `huella_directorio()` instead of `huella_plantilla()`.
 * The one real difference: a Plantilla always owns a `ficha.md` declaring `paginas:`, so an absent one
 * is a gap (`veredicto_faltas()` says so already); a client folder does not have to. Absent a
 * `ficha.md`, `veredicto_faltas_ruta()` takes the pages OWED to be exactly the pages the barrido
 * itself lists — so nothing can be missing or extra by construction — except that a barrido with NO
 * rows at all is not "everything it owed", it is nothing sweeping anything, which is its own gap.
 */

require_once __DIR__ . '/color.php';
require_once __DIR__ . '/huella.php';

/** The three sweep widths, in column order. A barrido table with any other header is incomplete. */
const NM_VEREDICTO_COLUMNAS = array( '430', '768', '1280' );

/** The judged header fields. `hash` and `fecha` are not here: they are the seal, written by
 *  `veredicto_sellar()`, and an unsealed veredicto is expected to lack them. */
const NM_VEREDICTO_CAMPOS = array( 'juez_b', 'autojuzgado', 'vistas', 'saltadas' );

/** A slug is one path segment of lowercase letters, digits and hyphens — the same shape the audit
 *  anchors a Plantilla unit on. Anything else (`..`, a slash) is refused before touching disk. */
const NM_VEREDICTO_SLUG = '/^[a-z0-9][a-z0-9-]*$/';

function veredicto_plantillas_dir( $root ) {
	return rtrim( str_replace( '\\', '/', $root ), '/' ) . '/skills/web-templates/references/plantillas';
}

/** The Plantilla folder for `$slug`, or `NmHerramientaEntorno` when the slug is malformed or the
 *  folder does not exist — a request for a Plantilla that is not there is usage, not a verdict. */
function veredicto_carpeta( $root, $slug ) {
	if ( ! is_string( $slug ) || 1 !== preg_match( NM_VEREDICTO_SLUG, $slug ) ) {
		throw new NmHerramientaEntorno( "slug no válido: «{$slug}» — solo minúsculas, dígitos y guiones" );
	}
	$dir = veredicto_plantillas_dir( $root ) . '/' . $slug;
	if ( ! is_dir( $dir ) ) {
		throw new NmHerramientaEntorno( "no existe la plantilla {$slug} en " . veredicto_plantillas_dir( $root ) );
	}
	return $dir;
}

/** The current fingerprint, as the `hash:` field spells it. The only call into huella.php. */
function veredicto_huella( $root, $slug ) {
	return 'sha256:' . huella_plantilla( rtrim( str_replace( '\\', '/', $root ), '/' ) . '/skills', $slug );
}

/** The `---` header block: `[ body, offset of body ]`, or null when the file does not open with one. */
function veredicto_bloque_cabecera( $texto ) {
	if ( 1 !== preg_match( '/\A---\n(.*?)^---[ \t]*$/ms', $texto, $m, PREG_OFFSET_CAPTURE ) ) {
		return null;
	}
	return array( $m[1][0], $m[1][1] );
}

/** Body lines under each `## ` heading, keyed by the lowercased heading text. */
function veredicto_secciones( $texto ) {
	$secciones = array();
	$actual    = null;
	foreach ( explode( "\n", $texto ) as $linea ) {
		if ( preg_match( '/^##[ \t]+(.+?)[ \t]*$/', $linea, $m ) ) {
			$actual               = mb_strtolower( $m[1] );
			$secciones[ $actual ] = array();
			continue;
		}
		if ( null !== $actual ) {
			$secciones[ $actual ][] = $linea;
		}
	}
	return $secciones;
}

/** One markdown table row's cells, trimmed, outer pipes removed. */
function veredicto_celdas( $linea ) {
	$linea = trim( $linea );
	$linea = preg_replace( '/^\||\|$/', '', $linea );
	return array_map( 'trim', explode( '|', $linea ) );
}

/**
 * Parse a veredicto.md into its parts, without judging them — `veredicto_faltas()` judges.
 *
 * Returns `cabecera` (field => value, or null with no header block), `repetidos` (header fields
 * written twice), `barrido` (null with no `## Barrido` section, else `columnas` — the table header
 * cells after the first, null with no table — and `filas`, a list of `[ pagina, celdas ]`), and
 * `hallazgos` (null with no `## Hallazgos` section, else `ninguno`, `ids` and `vacia`).
 */
function veredicto_leer( $texto ) {
	$texto = str_replace( "\r\n", "\n", $texto );
	$out   = array(
		'cabecera'  => null,
		'repetidos' => array(),
		'barrido'   => null,
		'hallazgos' => null,
	);

	$bloque = veredicto_bloque_cabecera( $texto );
	if ( null !== $bloque ) {
		$out['cabecera'] = array();
		foreach ( explode( "\n", $bloque[0] ) as $linea ) {
			if ( preg_match( '/^([a-z_]+):[ \t]*(.*?)[ \t]*$/', $linea, $m ) ) {
				if ( array_key_exists( $m[1], $out['cabecera'] ) ) {
					$out['repetidos'][] = $m[1];
				}
				$out['cabecera'][ $m[1] ] = $m[2];
			}
		}
		$texto = substr( $texto, $bloque[1] + strlen( $bloque[0] ) );
	}

	$secciones = veredicto_secciones( $texto );

	if ( isset( $secciones['barrido'] ) ) {
		$tabla = array();
		foreach ( $secciones['barrido'] as $linea ) {
			if ( 0 === strpos( ltrim( $linea ), '|' ) ) {
				$tabla[] = $linea;
			} elseif ( array() !== $tabla ) {
				break;
			}
		}
		$out['barrido'] = array( 'columnas' => null, 'filas' => array() );
		if ( count( $tabla ) >= 2 ) {
			$out['barrido']['columnas'] = array_slice( veredicto_celdas( $tabla[0] ), 1 );
			foreach ( array_slice( $tabla, 2 ) as $linea ) {
				$celdas                     = veredicto_celdas( $linea );
				$out['barrido']['filas'][] = array( trim( array_shift( $celdas ), " `" ), $celdas );
			}
		}
	}

	if ( isset( $secciones['hallazgos'] ) ) {
		$lineas = array_values( array_filter( array_map( 'trim', $secciones['hallazgos'] ), 'strlen' ) );
		$ids    = array();
		foreach ( $lineas as $linea ) {
			if ( preg_match( '/^[-*][ \t]+\**(H\d+)\b/', $linea, $m ) ) {
				$ids[] = $m[1];
			}
		}
		$out['hallazgos'] = array(
			'vacia'   => array() === $lineas,
			'ninguno' => 1 === count( $lineas ) && 1 === preg_match( '/^ninguno\.?$/i', $lineas[0] ),
			'ids'     => $ids,
		)
		;
	}
	return $out;
}

/** The pages `ficha.md` declares in its `paginas: [ … ]` header line, or null when the file, the
 *  line or any entry is missing — without it nobody can say which rows the sweep owed. */
function veredicto_paginas_ficha( $dir ) {
	$texto = @file_get_contents( $dir . '/ficha.md' );
	if ( false === $texto ) {
		return null;
	}
	$bloque = veredicto_bloque_cabecera( str_replace( "\r\n", "\n", $texto ) );
	if ( null === $bloque || 1 !== preg_match( '/^paginas:[ \t]*\[(.*)\][ \t]*$/m', $bloque[0], $m ) ) {
		return null;
	}
	$paginas = array_values( array_filter( array_map( function ( $p ) {
		return trim( $p, " \t'\"" );
	}, explode( ',', $m[1] ) ), 'strlen' ) );
	return array() === $paginas ? null : $paginas;
}

/** A sweep cell is `✓`, `no-disponible`, or one or more finding ids (`H1` / `H1, H3`). Returns the
 *  ids it cites, or null when the cell is none of the three. */
function veredicto_ids_celda( $celda ) {
	if ( '✓' === $celda || 'no-disponible' === $celda ) {
		return array();
	}
	if ( 1 === preg_match( '/^H\d+(\s*,\s*H\d+)*$/', $celda ) ) {
		return array_map( 'trim', explode( ',', $celda ) );
	}
	return null;
}

/**
 * Every way a parsed veredicto falls short of COMPLETE, as human-readable strings. Empty = complete.
 * Completeness is shape, not approval: `juez_b: no-profesional` is complete (and refused to seal
 * for its own reason), and a sweep with open findings is complete.
 */
function veredicto_faltas( array $v, $paginas ) {
	$faltas = array();

	if ( null === $v['cabecera'] ) {
		$faltas[] = 'no abre con una cabecera entre líneas ---, así que faltan juez_b, autojuzgado, vistas y saltadas';
	} else {
		foreach ( array_unique( $v['repetidos'] ) as $campo ) {
			$faltas[] = "$campo aparece dos veces en la cabecera";
		}
		foreach ( NM_VEREDICTO_CAMPOS as $campo ) {
			if ( ! isset( $v['cabecera'][ $campo ] ) || '' === $v['cabecera'][ $campo ] ) {
				$faltas[] = "falta $campo";
			}
		}
		$cab = $v['cabecera'];
		if ( isset( $cab['juez_b'] ) && '' !== $cab['juez_b'] && ! in_array( $cab['juez_b'], array( 'profesional', 'no-profesional' ), true ) ) {
			$faltas[] = "juez_b no reconocido: «{$cab['juez_b']}» (profesional | no-profesional)";
		}
		if ( isset( $cab['autojuzgado'] ) && '' !== $cab['autojuzgado'] && ! in_array( $cab['autojuzgado'], array( 'sí', 'no' ), true ) ) {
			$faltas[] = "autojuzgado no reconocido: «{$cab['autojuzgado']}» (sí | no)";
		}
		foreach ( array( 'vistas', 'saltadas' ) as $campo ) {
			if ( isset( $cab[ $campo ] ) && '' !== $cab[ $campo ] && 1 !== preg_match( '/^\d+$/', $cab[ $campo ] ) ) {
				$faltas[] = "$campo no es un número entero: «{$cab[ $campo ]}»";
			}
		}
	}

	$citados = array();
	if ( null === $v['barrido'] ) {
		$faltas[] = 'falta la sección ## Barrido';
	} elseif ( null === $v['barrido']['columnas'] ) {
		$faltas[] = '## Barrido no contiene una tabla';
	} elseif ( NM_VEREDICTO_COLUMNAS !== $v['barrido']['columnas'] ) {
		$faltas[] = 'la tabla de barrido no tiene exactamente las columnas 430 · 768 · 1280';
	} else {
		$vistas_filas = array();
		$saltadas     = 0;
		foreach ( $v['barrido']['filas'] as $fila ) {
			list( $pagina, $celdas ) = $fila;
			if ( '' === $pagina ) {
				$faltas[] = 'una fila del barrido no nombra su página';
				continue;
			}
			if ( isset( $vistas_filas[ $pagina ] ) ) {
				$faltas[] = "la página $pagina tiene dos filas en el barrido";
			}
			$vistas_filas[ $pagina ] = true;
			if ( count( $celdas ) !== count( NM_VEREDICTO_COLUMNAS ) ) {
				$faltas[] = "la fila $pagina tiene " . count( $celdas ) . ' celdas en vez de ' . count( NM_VEREDICTO_COLUMNAS );
				continue;
			}
			$saltada = false;
			foreach ( NM_VEREDICTO_COLUMNAS as $i => $ancho ) {
				$celda = $celdas[ $i ];
				if ( '' === $celda ) {
					$faltas[] = "celda vacía: $pagina@$ancho";
					continue;
				}
				$ids = veredicto_ids_celda( $celda );
				if ( null === $ids ) {
					$faltas[] = "celda no reconocida: $pagina@$ancho = «{$celda}» (✓ | no-disponible | H1)";
					continue;
				}
				foreach ( $ids as $id ) {
					$citados[ $id ][] = "$pagina@$ancho";
				}
				$saltada = $saltada || 'no-disponible' === $celda;
			}
			$saltadas += $saltada ? 1 : 0;
		}

		if ( null === $paginas ) {
			$faltas[] = 'ficha.md no declara paginas: — no se puede saber qué páginas debía barrer';
		} else {
			foreach ( array_diff( $paginas, array_keys( $vistas_filas ) ) as $p ) {
				$faltas[] = "la página $p de ficha.md no tiene fila en el barrido";
			}
			foreach ( array_diff( array_keys( $vistas_filas ), $paginas ) as $p ) {
				$faltas[] = "la fila $p no es una página que ficha.md declare";
			}
		}

		$cab = (array) $v['cabecera'];
		if ( isset( $cab['vistas'], $cab['saltadas'] ) && ctype_digit( $cab['vistas'] ) && ctype_digit( $cab['saltadas'] ) ) {
			$filas = count( $vistas_filas );
			if ( (int) $cab['vistas'] + (int) $cab['saltadas'] !== $filas ) {
				$faltas[] = "vistas + saltadas = {$cab['vistas']} + {$cab['saltadas']}, pero el barrido tiene $filas páginas";
			} elseif ( (int) $cab['saltadas'] !== $saltadas ) {
				$faltas[] = "saltadas = {$cab['saltadas']}, pero $saltadas páginas tienen alguna celda no-disponible";
			}
		}
	}

	if ( null === $v['hallazgos'] ) {
		$faltas[] = 'falta la sección ## Hallazgos (escribe los hallazgos, o «ninguno»)';
	} elseif ( $v['hallazgos']['vacia'] ) {
		$faltas[] = '## Hallazgos está vacía (escribe los hallazgos, o «ninguno»)';
	} else {
		foreach ( $citados as $id => $donde ) {
			if ( ! in_array( $id, $v['hallazgos']['ids'], true ) ) {
				$faltas[] = implode( ', ', $donde ) . " cita $id, que ## Hallazgos no lista";
			}
		}
	}

	return $faltas;
}

/** `$texto` with `hash:` and `fecha:` set in its header — replaced in place when present, inserted
 *  at the top of the header otherwise. Every other byte is kept; line endings are written LF. */
function veredicto_con_sello( $texto, $hash, $fecha ) {
	$texto  = str_replace( "\r\n", "\n", $texto );
	$bloque = veredicto_bloque_cabecera( $texto );
	if ( null === $bloque ) {
		throw new NmHerramientaMedida( 'incompleto: no abre con una cabecera entre líneas ---' );
	}
	$cuerpo = $bloque[0];
	foreach ( array( 'fecha' => $fecha, 'hash' => $hash ) as $campo => $valor ) {
		$patron = '/^' . $campo . ':.*$/m';
		if ( preg_match( $patron, $cuerpo ) ) {
			$cuerpo = preg_replace( $patron, $campo . ': ' . $valor, $cuerpo, 1 );
		} else {
			$cuerpo = $campo . ': ' . $valor . "\n" . $cuerpo;
		}
	}
	return substr( $texto, 0, $bloque[1] ) . $cuerpo . substr( $texto, $bloque[1] + strlen( $bloque[0] ) );
}

/**
 * Seal `<slug>/veredicto.md` with the current fingerprint and today's date.
 *
 * Throws `NmHerramientaMedida` — writing nothing — when the veredicto is absent, incomplete, or
 * says `juez_b: no-profesional`; `NmHerramientaEntorno` for an invalid slug, a missing Plantilla or
 * an unwritable file. Returns `[ 'hash' => …, 'fecha' => … ]`.
 */
function veredicto_sellar( $root, $slug ) {
	$dir  = veredicto_carpeta( $root, $slug );
	$ruta = $dir . '/veredicto.md';
	if ( ! is_file( $ruta ) ) {
		throw new NmHerramientaMedida( "ausente: $slug no tiene veredicto.md — los jueces lo escriben primero; no hay nada juzgado que sellar" );
	}
	$texto = file_get_contents( $ruta );
	if ( false === $texto ) {
		throw new NmHerramientaEntorno( "no se pudo leer $ruta" );
	}
	$v      = veredicto_leer( $texto );
	$faltas = veredicto_faltas( $v, veredicto_paginas_ficha( $dir ) );
	if ( array() !== $faltas ) {
		throw new NmHerramientaMedida( 'incompleto: ' . implode( '; ', $faltas ) );
	}
	if ( 'profesional' !== $v['cabecera']['juez_b'] ) {
		throw new NmHerramientaMedida( 'no-profesional: juez_b dice no-profesional — un veredicto que no aprueba la plantilla no se sella' );
	}
	$hash  = veredicto_huella( $root, $slug );
	$fecha = date( 'Y-m-d' );
	if ( false === file_put_contents( $ruta, veredicto_con_sello( $texto, $hash, $fecha ) ) ) {
		throw new NmHerramientaEntorno( "no se pudo escribir $ruta" );
	}
	return array( 'hash' => $hash, 'fecha' => $fecha );
}

/**
 * Whether `<slug>`'s veredicto is CURRENT: present, complete, `juez_b: profesional`, sealed, its
 * hash equal to the current fingerprint, no skipped cell and no open finding.
 *
 * A failed check is a result, not an exception: returns `[ 'ok' => bool, 'motivos' => string[],
 * 'hash' => current fingerprint, 'sellado' => recorded hash or null ]`, every reason collected so a
 * caller sees all of them at once. Each reason starts with its keyword (`ausente`, `incompleto`,
 * `no-profesional`, `sin sellar`, `caducado`, `PARCIAL`, `hallazgos abiertos`). Throws
 * `NmHerramientaEntorno` only for an invalid slug or a missing Plantilla.
 */
function veredicto_comprobar( $root, $slug ) {
	$dir     = veredicto_carpeta( $root, $slug );
	$actual  = veredicto_huella( $root, $slug );
	$res     = array( 'ok' => false, 'motivos' => array(), 'hash' => $actual, 'sellado' => null );
	$ruta    = $dir . '/veredicto.md';
	$texto   = is_file( $ruta ) ? file_get_contents( $ruta ) : false;
	if ( false === $texto ) {
		$res['motivos'][] = 'ausente: no hay veredicto.md';
		return $res;
	}

	$v      = veredicto_leer( $texto );
	$faltas = veredicto_faltas( $v, veredicto_paginas_ficha( $dir ) );
	if ( array() !== $faltas ) {
		$res['motivos'][] = 'incompleto: ' . implode( '; ', $faltas );
	}
	$cab = (array) $v['cabecera'];
	if ( isset( $cab['juez_b'] ) && 'no-profesional' === $cab['juez_b'] ) {
		$res['motivos'][] = 'no-profesional: juez_b dice no-profesional';
	}

	$sellado = isset( $cab['hash'] ) ? $cab['hash'] : '';
	if ( '' === $sellado ) {
		$res['motivos'][] = 'sin sellar: no hay hash — veredicto.php --sellar ' . $slug;
	} elseif ( 1 !== preg_match( '/^sha256:[0-9a-f]{64}$/', $sellado ) ) {
		$res['motivos'][] = "sin sellar: hash mal formado «{$sellado}» (sha256: y 64 hexadecimales en minúscula)";
	} else {
		$res['sellado'] = $sellado;
		if ( ! hash_equals( $sellado, $actual ) ) {
			$res['motivos'][] = 'caducado: sellado ' . substr( $sellado, 0, 19 ) . '…, actual ' . substr( $actual, 0, 19 )
				. '… — los bytes cambiaron después de juzgarlos; vuelve a juzgar y sella';
		}
	}
	if ( ! isset( $cab['fecha'] ) || 1 !== preg_match( '/^\d{4}-\d{2}-\d{2}$/', $cab['fecha'] ) ) {
		if ( '' !== $sellado ) {
			$res['motivos'][] = 'sin sellar: fecha ausente o mal formada (AAAA-MM-DD)';
		}
	}

	if ( null !== $v['barrido'] && NM_VEREDICTO_COLUMNAS === $v['barrido']['columnas'] ) {
		$saltadas = array();
		$abiertos = array();
		foreach ( $v['barrido']['filas'] as $fila ) {
			list( $pagina, $celdas ) = $fila;
			foreach ( NM_VEREDICTO_COLUMNAS as $i => $ancho ) {
				$celda = isset( $celdas[ $i ] ) ? $celdas[ $i ] : '';
				if ( 'no-disponible' === $celda ) {
					$saltadas[] = "$pagina@$ancho";
				} elseif ( '' !== $celda && '✓' !== $celda ) {
					$abiertos[] = "$celda ($pagina@$ancho)";
				}
			}
		}
		if ( array() !== $saltadas ) {
			$res['motivos'][] = 'PARCIAL: celdas no-disponible en ' . implode( ', ', $saltadas ) . ' — un barrido incompleto nunca es un aprobado';
		}
		if ( array() !== $abiertos ) {
			$res['motivos'][] = 'hallazgos abiertos: ' . implode( ', ', $abiertos );
		}
	}

	$res['ok'] = array() === $res['motivos'];
	return $res;
}

/**
 * `veredicto_comprobar()` over every Plantilla folder — each direct subdirectory of `plantillas/`
 * whose name does not start with `_` — keyed by slug, sorted. A folder whose name is not a valid
 * slug is a failing row, not an abort. Throws `NmHerramientaEntorno` when the library directory is
 * missing or holds no Plantilla: an empty check is could-not-measure, never a pass.
 */
function veredicto_biblioteca( $root ) {
	$dir = veredicto_plantillas_dir( $root );
	if ( ! is_dir( $dir ) ) {
		throw new NmHerramientaEntorno( "no hay biblioteca de plantillas en $dir" );
	}
	$filas = array();
	foreach ( scandir( $dir ) as $slug ) {
		if ( '.' === $slug[0] || '_' === $slug[0] || ! is_dir( $dir . '/' . $slug ) ) {
			continue;
		}
		try {
			$filas[ $slug ] = veredicto_comprobar( $root, $slug );
		} catch ( NmHerramientaEntorno $e ) {
			$filas[ $slug ] = array( 'ok' => false, 'motivos' => array( 'no medible: ' . $e->getMessage() ), 'hash' => null, 'sellado' => null );
		}
	}
	if ( array() === $filas ) {
		throw new NmHerramientaEntorno( "la biblioteca $dir no contiene ninguna plantilla" );
	}
	ksort( $filas, SORT_STRING );
	return $filas;
}

// ─────────────────────────────────────────── client delivery folder ──────────────────────────────

/** The client delivery folder at `$dir`, or `NmHerramientaEntorno` when it is not there or does not
 *  even have a `maqueta/` — a request to seal something that is not shaped like a delivery is usage,
 *  not a verdict, exactly like `veredicto_carpeta()` for an unknown Plantilla slug. */
function veredicto_carpeta_ruta( $dir ) {
	if ( ! is_string( $dir ) || '' === $dir ) {
		throw new NmHerramientaEntorno( 'no se indicó la carpeta de entrega del cliente' );
	}
	$dir = rtrim( str_replace( '\\', '/', $dir ), '/' );
	if ( ! is_dir( $dir ) ) {
		throw new NmHerramientaEntorno( "no es un directorio (o no existe): $dir" );
	}
	if ( ! is_dir( $dir . '/maqueta' ) ) {
		throw new NmHerramientaEntorno( "la carpeta de entrega no tiene maqueta/: $dir" );
	}
	return $dir;
}

/** The current fingerprint of a client folder, as the `hash:` field spells it. The only call into
 *  huella.php's folder half — mirrors `veredicto_huella()`, which calls the Plantilla half. */
function veredicto_huella_ruta( $dir ) {
	return 'sha256:' . huella_directorio( $dir );
}

/** The pages a client folder's sweep owed: `ficha.md`'s own `paginas:` when the folder has one,
 *  otherwise exactly the pages `$v`'s barrido already lists — so with no `ficha.md` nothing can be
 *  reported missing or extra, that check simply has nothing left to assert. */
function veredicto_paginas_ruta( $dir, array $v ) {
	$paginas = veredicto_paginas_ficha( $dir );
	if ( null !== $paginas ) {
		return $paginas;
	}
	if ( null === $v['barrido'] || null === $v['barrido']['columnas'] ) {
		return null;
	}
	$vistas = array();
	foreach ( $v['barrido']['filas'] as $fila ) {
		if ( '' !== $fila[0] ) {
			$vistas[ $fila[0] ] = true;
		}
	}
	return array_keys( $vistas );
}

/**
 * `veredicto_faltas()` for a client folder, plus the one gap that check cannot see on its own: with
 * no `ficha.md`, `veredicto_paginas_ruta()` derives the owed pages FROM the barrido's own rows, so a
 * barrido with zero rows trivially "matches" — nothing was owed because nothing said what was owed.
 * That is not complete, it is unmeasured, so it is called out here explicitly.
 */
function veredicto_faltas_ruta( $dir, array $v ) {
	$faltas = veredicto_faltas( $v, veredicto_paginas_ruta( $dir, $v ) );
	if ( null === veredicto_paginas_ficha( $dir ) ) {
		$filas = ( null !== $v['barrido'] ) ? $v['barrido']['filas'] : array();
		if ( array() === $filas ) {
			$faltas[] = 'sin ficha.md, las páginas debidas son las que el propio barrido liste, y el barrido no tiene ninguna fila — nada se barrió';
		}
	}
	return $faltas;
}

/**
 * Seal a client delivery folder's `veredicto.md` — same contract as `veredicto_sellar()`, computed
 * over `veredicto_carpeta_ruta()` / `veredicto_huella_ruta()` / `veredicto_faltas_ruta()` instead of
 * their Plantilla counterparts. Returns `[ 'hash' => …, 'fecha' => … ]`.
 */
function veredicto_sellar_ruta( $dir ) {
	$dir  = veredicto_carpeta_ruta( $dir );
	$ruta = $dir . '/veredicto.md';
	if ( ! is_file( $ruta ) ) {
		throw new NmHerramientaMedida( "ausente: la carpeta de entrega no tiene veredicto.md — los jueces lo escriben primero; no hay nada juzgado que sellar" );
	}
	$texto = file_get_contents( $ruta );
	if ( false === $texto ) {
		throw new NmHerramientaEntorno( "no se pudo leer $ruta" );
	}
	$v      = veredicto_leer( $texto );
	$faltas = veredicto_faltas_ruta( $dir, $v );
	if ( array() !== $faltas ) {
		throw new NmHerramientaMedida( 'incompleto: ' . implode( '; ', $faltas ) );
	}
	if ( 'profesional' !== $v['cabecera']['juez_b'] ) {
		throw new NmHerramientaMedida( 'no-profesional: juez_b dice no-profesional — un veredicto que no aprueba la entrega no se sella' );
	}
	$hash  = veredicto_huella_ruta( $dir );
	$fecha = date( 'Y-m-d' );
	if ( false === file_put_contents( $ruta, veredicto_con_sello( $texto, $hash, $fecha ) ) ) {
		throw new NmHerramientaEntorno( "no se pudo escribir $ruta" );
	}
	return array( 'hash' => $hash, 'fecha' => $fecha );
}

/**
 * Whether a client delivery folder's veredicto is CURRENT — same contract, and the same result
 * shape, as `veredicto_comprobar()`. Throws `NmHerramientaEntorno` only for a folder that is not
 * there or is not shaped like a delivery; every other gap comes back as a reason in `motivos`.
 */
function veredicto_comprobar_ruta( $dir ) {
	$dir    = veredicto_carpeta_ruta( $dir );
	$actual = veredicto_huella_ruta( $dir );
	$res    = array( 'ok' => false, 'motivos' => array(), 'hash' => $actual, 'sellado' => null );
	$ruta   = $dir . '/veredicto.md';
	$texto  = is_file( $ruta ) ? file_get_contents( $ruta ) : false;
	if ( false === $texto ) {
		$res['motivos'][] = 'ausente: no hay veredicto.md';
		return $res;
	}

	$v      = veredicto_leer( $texto );
	$faltas = veredicto_faltas_ruta( $dir, $v );
	if ( array() !== $faltas ) {
		$res['motivos'][] = 'incompleto: ' . implode( '; ', $faltas );
	}
	$cab = (array) $v['cabecera'];
	if ( isset( $cab['juez_b'] ) && 'no-profesional' === $cab['juez_b'] ) {
		$res['motivos'][] = 'no-profesional: juez_b dice no-profesional';
	}

	$sellado = isset( $cab['hash'] ) ? $cab['hash'] : '';
	if ( '' === $sellado ) {
		$res['motivos'][] = 'sin sellar: no hay hash — veredicto.php --sellar-ruta ' . $dir;
	} elseif ( 1 !== preg_match( '/^sha256:[0-9a-f]{64}$/', $sellado ) ) {
		$res['motivos'][] = "sin sellar: hash mal formado «{$sellado}» (sha256: y 64 hexadecimales en minúscula)";
	} else {
		$res['sellado'] = $sellado;
		if ( ! hash_equals( $sellado, $actual ) ) {
			$res['motivos'][] = 'caducado: sellado ' . substr( $sellado, 0, 19 ) . '…, actual ' . substr( $actual, 0, 19 )
				. '… — los bytes cambiaron después de juzgarlos; vuelve a juzgar y sella';
		}
	}
	if ( ! isset( $cab['fecha'] ) || 1 !== preg_match( '/^\d{4}-\d{2}-\d{2}$/', $cab['fecha'] ) ) {
		if ( '' !== $sellado ) {
			$res['motivos'][] = 'sin sellar: fecha ausente o mal formada (AAAA-MM-DD)';
		}
	}

	if ( null !== $v['barrido'] && NM_VEREDICTO_COLUMNAS === $v['barrido']['columnas'] ) {
		$saltadas = array();
		$abiertos = array();
		foreach ( $v['barrido']['filas'] as $fila ) {
			list( $pagina, $celdas ) = $fila;
			foreach ( NM_VEREDICTO_COLUMNAS as $i => $ancho ) {
				$celda = isset( $celdas[ $i ] ) ? $celdas[ $i ] : '';
				if ( 'no-disponible' === $celda ) {
					$saltadas[] = "$pagina@$ancho";
				} elseif ( '' !== $celda && '✓' !== $celda ) {
					$abiertos[] = "$celda ($pagina@$ancho)";
				}
			}
		}
		if ( array() !== $saltadas ) {
			$res['motivos'][] = 'PARCIAL: celdas no-disponible en ' . implode( ', ', $saltadas ) . ' — un barrido incompleto nunca es un aprobado';
		}
		if ( array() !== $abiertos ) {
			$res['motivos'][] = 'hallazgos abiertos: ' . implode( ', ', $abiertos );
		}
	}

	$res['ok'] = array() === $res['motivos'];
	return $res;
}

// ─────────────────────────────────────────── dual-mode CLI ───────────────────────────────────────

if ( 'cli' === PHP_SAPI && isset( $argv[0] ) && realpath( $argv[0] ) === __FILE__ ) {
	function veredicto_cli_usage() {
		fwrite( STDERR, "veredicto: usage:\n"
			. "  veredicto.php --sellar <slug> [--root=<dir>]\n"
			. "  veredicto.php --comprobar <slug> [--root=<dir>]\n"
			. "  veredicto.php --biblioteca [--root=<dir>]\n"
			. "  veredicto.php --sellar-ruta <carpeta>\n"
			. "  veredicto.php --comprobar-ruta <carpeta>\n" );
	}

	/* Default root: the repository this file's checkout lives in — four levels up from
	   `skills/html-mockup/assets/herramientas/`. */
	$root  = dirname( __DIR__, 4 );
	$resto = array();
	foreach ( array_slice( $argv, 1 ) as $a ) {
		if ( 0 === strpos( $a, '--root=' ) ) {
			$root = rtrim( substr( $a, 7 ), '/\\' );
		} else {
			$resto[] = $a;
		}
	}
	$accion = isset( $resto[0] ) ? $resto[0] : '';

	try {
		if ( ! is_dir( $root ) ) {
			throw new NmHerramientaEntorno( "--root no es un directorio: $root" );
		}

		if ( '--sellar' === $accion && 2 === count( $resto ) ) {
			try {
				$sello = veredicto_sellar( $root, $resto[1] );
			} catch ( NmHerramientaMedida $e ) {
				fwrite( STDERR, "veredicto: {$resto[1]} no se sella — " . $e->getMessage() . "\n" );
				exit( 1 );
			}
			echo "veredicto: {$resto[1]} sellado — hash {$sello['hash']} · fecha {$sello['fecha']}\n";
			exit( 0 );
		}

		if ( '--comprobar' === $accion && 2 === count( $resto ) ) {
			$res = veredicto_comprobar( $root, $resto[1] );
			if ( $res['ok'] ) {
				echo "veredicto: {$resto[1]} vigente ({$res['hash']})\n";
				exit( 0 );
			}
			fwrite( STDERR, "veredicto: {$resto[1]} no vigente — " . implode( '; ', $res['motivos'] ) . "\n" );
			exit( 1 );
		}

		if ( '--biblioteca' === $accion && 1 === count( $resto ) ) {
			$filas    = veredicto_biblioteca( $root );
			$vigentes = 0;
			foreach ( $filas as $slug => $res ) {
				if ( $res['ok'] ) {
					$vigentes++;
					printf( "OK    %s  vigente (%s)\n", $slug, substr( $res['hash'], 0, 19 ) );
				} else {
					printf( "FAIL  %s  %s\n", $slug, implode( '; ', $res['motivos'] ) );
				}
			}
			printf( "veredicto: %d de %d plantillas vigentes\n", $vigentes, count( $filas ) );
			exit( $vigentes === count( $filas ) ? 0 : 1 );
		}

		if ( '--sellar-ruta' === $accion && 2 === count( $resto ) ) {
			try {
				$sello = veredicto_sellar_ruta( $resto[1] );
			} catch ( NmHerramientaMedida $e ) {
				fwrite( STDERR, "veredicto: {$resto[1]} no se sella — " . $e->getMessage() . "\n" );
				exit( 1 );
			}
			echo "veredicto: {$resto[1]} sellado — hash {$sello['hash']} · fecha {$sello['fecha']}\n";
			exit( 0 );
		}

		if ( '--comprobar-ruta' === $accion && 2 === count( $resto ) ) {
			$res = veredicto_comprobar_ruta( $resto[1] );
			if ( $res['ok'] ) {
				echo "veredicto: {$resto[1]} vigente ({$res['hash']})\n";
				exit( 0 );
			}
			fwrite( STDERR, "veredicto: {$resto[1]} no vigente — " . implode( '; ', $res['motivos'] ) . "\n" );
			exit( 1 );
		}

		veredicto_cli_usage();
		exit( 2 );
	} catch ( NmHerramientaEntorno $e ) {
		fwrite( STDERR, 'veredicto: ' . $e->getMessage() . "\n" );
		exit( 2 );
	}
}
