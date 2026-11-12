<?php
/**
 * MODUL: Descrieri curate.
 *
 * CE FACE: scoate din descrierile venite de pe YouTube rândurile care au sens doar pe YouTube
 * sau care sunt învechite: „abonați-vă”, „like/dislike”, „dați un comment”, vechiul bloc
 * de contact (apolloniatv2015@gmail.com, telefonul și adresa vechi), „Urmărește @apolloniatvro
 * pentru mai mult conținut științific”, „Link-ul sursei îl găsiți în primul comentariu”.
 *
 * Se aplică:
 *  - la import (articolele noi vin deja curate);
 *  - la afișare (plasă de siguranță);
 *  - o singură dată, pe articolele existente (Apollonia TV → Import YouTube → „Curăță descrierile”).
 *    Textul original se păstrează în câmpul ascuns _aptv_descriere_yt, deci operația e reversibilă.
 *
 * Un rând nou de eliminat se adaugă în aptv_tipare_descriere().
 *
 * @package apolloniatv
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tiparele rândurilor care se elimină. Se compară cu rândul scris fără diacritice, cu litere mici.
 *
 * @return string[] Expresii regulate.
 */
function aptv_tipare_descriere() {
	return array(
		'/pentru a vedea mai multe stiri apollonia tv/',
		'/like\s*\/\s*dislike/',
		'/sa dati un comment sub video/',
		'/apasati butonul de abonare/',
		'/^da-ne un like/',
		'/^contact\s*:?\s*$/',
		'/^adresa\s*:\s*bulevardul stefan cel mare/',
		'/^adresa de e-?mail\s*:/',
		'/^(facebook|instagram|youtube|tiktok)\s*:/',
		'/^telefon\s*:\s*0332/',
		'/apolloniatv2015@gmail\.com/',
		'/urmareste\b.*\bmai mult continut/',            // „Urmărește @ApolloniatvRo (pentru) mai mult conținut științific”
		'/\b(spune|scrie|lasa)-ne\b.*\bin comentarii\b/', // pe site nu există comentarii
		'/\bin comentarii\s*[!.?]*\s*$/',
		'/link-ul sursei il gasiti in primul comentariu/',
		'/^aboneaza-te la apollonia tv/',
		'/^hashtag(uri|s)?\s*:?\s*$/',
	);
}

/**
 * Este rândul unul de eliminat?
 *
 * @param string $rand Text simplu (fără HTML).
 */
function aptv_rand_de_eliminat( $rand ) {
	$t = strtolower( remove_accents( html_entity_decode( wp_strip_all_tags( $rand ), ENT_QUOTES, 'UTF-8' ) ) );
	$t = preg_replace( '/[\x{2066}-\x{2069}\x{200B}-\x{200F}\x{FE0F}]/u', '', $t ); // caractere invizibile
	$t = trim( preg_replace( '/\s+/u', ' ', $t ), " \t\x{00A0}➡️→•-" );
	if ( '' === $t ) {
		return false;
	}
	foreach ( aptv_tipare_descriere() as $tipar ) {
		if ( preg_match( $tipar, $t ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Curăță o descriere în text simplu (folosită la import, înainte de a fi transformată în HTML).
 */
function aptv_curata_descriere( $text ) {
	$randuri = preg_split( '/\R/u', (string) $text );
	$pastrate = array_filter(
		$randuri,
		function ( $r ) {
			return ! aptv_rand_de_eliminat( $r );
		}
	);
	$curat = implode( "\n", $pastrate );
	return trim( preg_replace( "/\n{3,}/", "\n\n", $curat ) );
}

/**
 * Curăță conținutul HTML al unui articol (paragrafe <p> cu rânduri despărțite de <br>).
 */
function aptv_curata_continut( $html ) {
	if ( '' === trim( (string) $html ) ) {
		return $html;
	}
	// Împărțim pe rânduri vizuale: sfârșit de paragraf sau <br>.
	$bucati = preg_split( '#(<br\s*/?>\s*|</p>\s*)#i', $html, -1, PREG_SPLIT_DELIM_CAPTURE );
	$iesire = '';
	$sarit  = false;
	foreach ( $bucati as $i => $bucata ) {
		if ( 1 === $i % 2 ) { // separator
			if ( $sarit && stripos( $bucata, '</p>' ) === false ) {
				continue; // <br> de după un rând eliminat
			}
			$iesire .= $bucata;
			continue;
		}
		$text = preg_replace( '#^\s*<p[^>]*>#i', '', $bucata );
		if ( aptv_rand_de_eliminat( $text ) ) {
			$sarit   = true;
			$iesire .= preg_match( '#^\s*<p[^>]*>#i', $bucata, $m ) ? $m[0] : '';
			continue;
		}
		$sarit   = false;
		$iesire .= $bucata;
	}
	// Paragrafe rămase goale sau doar cu <br>.
	$iesire = preg_replace( '#<p[^>]*>(\s|<br\s*/?>|&nbsp;)*</p>#i', '', $iesire );
	$iesire = preg_replace( '#(<br\s*/?>\s*)+</p>#i', '</p>', $iesire );
	$iesire = preg_replace( '#(<p[^>]*>)\s*(<br\s*/?>\s*)+#i', '$1', $iesire );
	return trim( $iesire );
}

// Plasă de siguranță la afișare.
add_filter( 'the_content', 'aptv_curata_continut', 8 );

/**
 * Curăță descrierile articolelor existente, câte 100 deodată (apelat din pagina Import YouTube).
 * Textul original rămâne în _aptv_descriere_yt.
 *
 * @param int $de_la ID-ul de la care se continuă (0 = de la început).
 * @return array [ 'verificate' => int, 'curatate' => int, 'urmator' => int (0 = gata) ]
 */
function aptv_curata_descrieri_pas( $de_la = 0 ) {
	global $wpdb;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$randuri = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT ID, post_content FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status IN ('publish','draft','future','private') AND ID > %d ORDER BY ID ASC LIMIT 100",
			$de_la
		)
	);
	$curatate = 0;
	$ultim    = 0;
	foreach ( $randuri as $r ) {
		$ultim = (int) $r->ID;
		$nou   = aptv_curata_continut( $r->post_content );
		if ( $nou === $r->post_content ) {
			continue;
		}
		if ( '' === (string) get_post_meta( $r->ID, '_aptv_descriere_yt', true ) ) {
			update_post_meta( $r->ID, '_aptv_descriere_yt', wp_slash( $r->post_content ) );
		}
		// Actualizare directă: nu creăm revizii și nu schimbăm data modificării pentru 1.200 de articole.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update( $wpdb->posts, array( 'post_content' => $nou ), array( 'ID' => $r->ID ) );
		clean_post_cache( $r->ID );
		++$curatate;
	}
	return array(
		'verificate' => count( $randuri ),
		'curatate'   => $curatate,
		'urmator'    => count( $randuri ) < 100 ? 0 : $ultim,
	);
}

add_action(
	'wp_ajax_aptv_curata_descrieri',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Fără drepturi.', 403 );
		}
		check_ajax_referer( 'aptv_import', 'nonce' );
		wp_send_json_success( aptv_curata_descrieri_pas( isset( $_POST['de_la'] ) ? absint( $_POST['de_la'] ) : 0 ) );
	}
);
