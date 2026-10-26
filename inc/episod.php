<?php
/**
 * MODUL: Ajutoare pentru paginile construite de temă.
 *
 * CE FACE:
 *  1. Marchează paginile construite de noi (clasa „aptv-sablon” pe <body>), ca stilurile
 *     temei să știe când să lase conținutul pe toată lățimea.
 *  2. Găsește episoadele care urmează (pentru „Urmează în ...” de pe pagina episodului).
 *
 * Paginile propriu-zise sunt fișierele din rădăcina temei:
 *   front-page.php  – prima pagină
 *   category.php    – paginile de secțiune, temă, emisiune și tip de știri
 *   single.php      – pagina unui episod / a unei știri
 *   search.php      – rezultatele căutării
 *
 * @package apolloniatv
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'body_class',
	function ( $clase ) {
		if ( is_front_page() || is_category() || is_singular( 'post' ) || is_search() ) {
			$clase[] = 'aptv-sablon';
		}
		return $clase;
	}
);

/**
 * Episoadele care urmează cronologic după $post în aceeași categorie.
 *
 * @param WP_Post $post
 * @param WP_Term $categorie
 * @param int     $cate
 * @return WP_Post[]
 */
function aptv_episoade_urmatoare( $post, $categorie, $cate = 3 ) {
	if ( ! $post || ! $categorie ) {
		return array();
	}
	return get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'cat'            => $categorie->term_id,
			'posts_per_page' => $cate,
			'orderby'        => 'date',
			'order'          => 'ASC',
			'no_found_rows'  => true,
			'date_query'     => array(
				array(
					'after'     => $post->post_date,
					'inclusive' => false,
				),
			),
		)
	);
}

/**
 * Episodul anterior (mai vechi) din aceeași categorie.
 */
function aptv_episod_anterior( $post, $categorie ) {
	if ( ! $post || ! $categorie ) {
		return null;
	}
	$gasite = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'cat'            => $categorie->term_id,
			'posts_per_page' => 1,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => true,
			'date_query'     => array(
				array(
					'before'    => $post->post_date,
					'inclusive' => false,
				),
			),
		)
	);
	return $gasite ? $gasite[0] : null;
}

/**
 * Cele mai noi articole dintr-o categorie (inclusiv subcategoriile ei).
 */
function aptv_cele_mai_noi( $slug_sau_id = 0, $cate = 6, $exclude = array() ) {
	$args = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => $cate,
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
		'post__not_in'        => $exclude,
	);
	if ( $slug_sau_id ) {
		$termen = is_numeric( $slug_sau_id ) ? get_term( (int) $slug_sau_id, 'category' ) : get_term_by( 'slug', $slug_sau_id, 'category' );
		if ( ! $termen || is_wp_error( $termen ) ) {
			return array();
		}
		$args['cat'] = $termen->term_id;
	}
	return get_posts( $args );
}
