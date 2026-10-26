<?php
/**
 * MODUL: Ordinea episoadelor.
 *
 * CE FACE: pe pagina unei emisiuni (ex. /category/emisiuni/cultura/stop-cadru/)
 * episoadele apar în ordine cronologică — primul episod sus, ca într-un serial.
 * Vizitatorul poate inversa ordinea cu butonul „Cele mai noi primele”
 * (adresa paginii primește atunci ?ordine=noi).
 *
 * La Știri ordinea rămâne cea obișnuită: cele mai noi sus.
 *
 * DATA EPISODULUI: importul YouTube setează data articolului = data publicării
 * videoclipului pe YouTube, deci ordinea e cea reală a difuzării.
 *
 * @package apolloniatv
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Câte episoade apar pe o pagină a emisiunii (restul — cu butonul „Pagina următoare”). */
const APTV_EPISOADE_PE_PAGINA = 24;

/**
 * Este cerută ordinea „cele mai noi primele”?
 */
function aptv_ordine_noi() {
	return isset( $_GET['ordine'] ) && 'noi' === sanitize_key( wp_unslash( $_GET['ordine'] ) );
}

add_action(
	'pre_get_posts',
	function ( $query ) {
		if ( is_admin() || ! $query->is_main_query() || ! $query->is_category() ) {
			return;
		}
		$categorie = $query->get_queried_object();
		if ( ! $categorie instanceof WP_Term ) {
			return;
		}
		if ( 'emisiune' === aptv_tip_categorie( $categorie ) ) {
			$query->set( 'orderby', 'date' );
			$query->set( 'order', aptv_ordine_noi() ? 'DESC' : 'ASC' );
			$query->set( 'posts_per_page', APTV_EPISOADE_PE_PAGINA );
		} else {
			$query->set( 'posts_per_page', APTV_EPISOADE_PE_PAGINA );
		}
	}
);

/**
 * Numărul episodului în emisiune (1 = cel mai vechi). Folosit pe pagina episodului
 * și în lista de episoade.
 *
 * @param WP_Post $post
 * @param WP_Term $emisiune
 * @return int
 */
function aptv_numar_episod( $post, $emisiune ) {
	$post = get_post( $post );
	if ( ! $post || ! $emisiune ) {
		return 0;
	}
	$mai_vechi = new WP_Query(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'cat'            => $emisiune->term_id,
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'date_query'     => array(
				array(
					'before'    => $post->post_date,
					'inclusive' => false,
				),
			),
		)
	);
	return (int) $mai_vechi->found_posts + 1;
}
