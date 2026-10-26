<?php
/**
 * MODUL: Actualizarea temei din GitHub.
 *
 * CE FACE: WordPress află singur când pe GitHub există o versiune nouă a temei
 * și o oferă în Panou → Actualizări, la fel ca pentru orice altă temă.
 * Actualizarea se face cu un clic, fără să încărcați fișiere de mână.
 *
 * CUM: de câteva ori pe zi, site-ul citește numărul „Version” din fișierul style.css
 * de pe GitHub (ramura main). Dacă e mai mare decât versiunea instalată,
 * WordPress afișează actualizarea. La instalare, descarcă arhiva ramurii main.
 *
 * DE AICI REZULTĂ REGULA DE AUR: o modificare de cod ajunge pe site doar dacă
 * s-a mărit „Version” în style.css (ex. 0.1.2 → 0.1.3).
 *
 * Nu e nevoie de niciun plugin și de nicio parolă (repository-ul este public).
 *
 * @package apolloniatv
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Repository-ul GitHub al temei. */
const APTV_GITHUB = 'apolloniatv/apolloniatv-tema';

/** Folderul temei pe server (numele trebuie să rămână același după actualizare). */
const APTV_FOLDER = 'apolloniatv-tema';

/**
 * Versiunea temei de pe GitHub (ținută minte 6 ore, ca să nu întrebăm GitHub la fiecare pagină).
 *
 * @param bool $proaspat true = ignoră memoria și întreabă GitHub acum.
 * @return string Versiunea (ex. „0.1.2”) sau șir gol dacă GitHub nu răspunde.
 */
function aptv_versiune_github( $proaspat = false ) {
	$memorata = get_site_transient( 'aptv_versiune_github' );
	if ( ! $proaspat && false !== $memorata ) {
		return (string) $memorata;
	}
	$raspuns  = wp_remote_get( 'https://raw.githubusercontent.com/' . APTV_GITHUB . '/main/style.css', array( 'timeout' => 10 ) );
	$versiune = '';
	if ( ! is_wp_error( $raspuns ) && 200 === wp_remote_retrieve_response_code( $raspuns ) ) {
		if ( preg_match( '/^\s*Version:\s*([0-9][0-9A-Za-z.\-]*)/mi', wp_remote_retrieve_body( $raspuns ), $m ) ) {
			$versiune = $m[1];
		}
	}
	// Și un răspuns gol (GitHub indisponibil) se ține minte, dar doar o oră.
	set_site_transient( 'aptv_versiune_github', $versiune, $versiune ? 6 * HOUR_IN_SECONDS : HOUR_IN_SECONDS );
	return $versiune;
}

/**
 * Anunță WordPress-ul că există o versiune nouă (apare în Panou → Actualizări).
 */
add_filter(
	'pre_set_site_transient_update_themes',
	function ( $actualizari ) {
		if ( ! is_object( $actualizari ) ) {
			return $actualizari;
		}
		$tema = wp_get_theme( APTV_FOLDER );
		if ( ! $tema->exists() ) {
			return $actualizari;
		}
		// La „Verifică din nou” din pagina de actualizări, întrebăm GitHub imediat.
		$proaspat = is_admin() && isset( $_GET['force-check'] ); // phpcs:ignore WordPress.Security.NonceVerification
		$noua     = aptv_versiune_github( $proaspat );
		if ( $noua && version_compare( $noua, $tema->get( 'Version' ), '>' ) ) {
			$actualizari->response[ APTV_FOLDER ] = array(
				'theme'       => APTV_FOLDER,
				'new_version' => $noua,
				'url'         => 'https://github.com/' . APTV_GITHUB,
				'package'     => 'https://codeload.github.com/' . APTV_GITHUB . '/zip/refs/heads/main',
			);
		} else {
			unset( $actualizari->response[ APTV_FOLDER ] );
		}
		return $actualizari;
	}
);

/**
 * Arhiva de pe GitHub se dezarhivează într-un folder numit „apolloniatv-tema-main”.
 * Îl redenumim „apolloniatv-tema”, altfel WordPress ar crede că e altă temă.
 */
add_filter(
	'upgrader_source_selection',
	function ( $sursa, $sursa_initiala, $upgrader, $extra = array() ) {
		global $wp_filesystem;
		if ( empty( $extra['theme'] ) || APTV_FOLDER !== $extra['theme'] ) {
			return $sursa;
		}
		$corect = trailingslashit( $sursa_initiala ) . APTV_FOLDER . '/';
		if ( untrailingslashit( $sursa ) === untrailingslashit( $corect ) ) {
			return $sursa;
		}
		if ( $wp_filesystem && $wp_filesystem->move( $sursa, $corect, true ) ) {
			return $corect;
		}
		return new WP_Error( 'aptv_redenumire', 'Actualizarea temei nu a putut redenumi folderul descărcat din GitHub.' );
	},
	10,
	4
);

/**
 * După o actualizare reușită, uităm versiunea ținută minte.
 */
add_action(
	'upgrader_process_complete',
	function ( $upgrader, $extra ) {
		if ( isset( $extra['type'], $extra['themes'] ) && 'theme' === $extra['type'] && in_array( APTV_FOLDER, (array) $extra['themes'], true ) ) {
			delete_site_transient( 'aptv_versiune_github' );
		}
	},
	10,
	2
);
