<?php
/**
 * MODUL: Recomandarea de pe prima pagină.
 *
 * CE FACE: adaugă în ecranul de editare al fiecărui episod (în coloana din dreapta)
 * căsuța „Recomandat pe prima pagină”. Prima pagină afișează, în zona mare de sus,
 * episodul bifat CEL MAI RECENT.
 *
 * CUM SE FOLOSEȘTE (de către echipă): deschideți episodul → bifați căsuța → Actualizează.
 * Nu trebuie debifat episodul vechi: câștigă automat ultimul bifat.
 * Dacă nu e bifat niciun episod, se afișează cel mai nou episod publicat.
 *
 * TEHNIC: la bifare se salvează momentul bifării (în câmpul ascuns _aptv_recomandat),
 * iar prima pagină îl caută pe cel cu momentul cel mai recent.
 *
 * @package apolloniatv
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Caseta din ecranul de editare.
 */
add_action(
	'add_meta_boxes_post',
	function () {
		add_meta_box( 'aptv-recomandat', 'Prima pagină', 'aptv_caseta_recomandat', 'post', 'side', 'high' );
	}
);

function aptv_caseta_recomandat( $post ) {
	$bifat = (bool) get_post_meta( $post->ID, '_aptv_recomandat', true );
	wp_nonce_field( 'aptv_recomandat', 'aptv_recomandat_nonce' );
	?>
	<label style="display:flex;gap:8px;align-items:flex-start;font-size:14px">
		<input type="checkbox" name="aptv_recomandat" value="1" <?php checked( $bifat ); ?>>
		<span><strong>Recomandat pe prima pagină</strong><br>
		<span style="color:#555">Apare în zona mare de sus. Câștigă ultimul episod bifat.</span></span>
	</label>
	<?php
}

/**
 * Salvarea bifei.
 */
add_action(
	'save_post_post',
	function ( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! isset( $_POST['aptv_recomandat_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['aptv_recomandat_nonce'] ), 'aptv_recomandat' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$era_bifat = (bool) get_post_meta( $post_id, '_aptv_recomandat', true );
		if ( ! empty( $_POST['aptv_recomandat'] ) ) {
			// Se salvează momentul bifării doar la prima bifare, ca simpla „actualizare” a altui text să nu schimbe ordinea.
			if ( ! $era_bifat ) {
				update_post_meta( $post_id, '_aptv_recomandat', time() );
			}
		} else {
			delete_post_meta( $post_id, '_aptv_recomandat' );
		}
	}
);

/**
 * Episodul afișat în zona de sus a primei pagini.
 *
 * @return WP_Post|null
 */
function aptv_episod_recomandat() {
	$bifate = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'meta_key'       => '_aptv_recomandat',
			'orderby'        => 'meta_value_num',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		)
	);
	if ( $bifate ) {
		return $bifate[0];
	}
	// Rezervă: cel mai nou episod care NU e clip scurt (un clip vertical arată prost în zona mare).
	$cele_mai_noi = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'no_found_rows'  => true,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				'relation' => 'OR',
				array(
					'key'     => '_aptv_scurt',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'   => '_aptv_scurt',
					'value' => '0',
				),
			),
		)
	);
	return $cele_mai_noi ? $cele_mai_noi[0] : null;
}

/**
 * Bifa poate fi citită și schimbată și prin interfața REST a WordPress
 * (folosită de editorul de blocuri și de scripturile de administrare), doar de cine poate edita articole.
 */
add_action(
	'init',
	function () {
		register_post_meta(
			'post',
			'_aptv_recomandat',
			array(
				'type'          => 'integer',
				'single'        => true,
				'show_in_rest'  => true,
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}
);

/**
 * O coloană „★” în lista de articole din admin, ca să se vadă ce episoade sunt bifate.
 */
add_filter(
	'manage_post_posts_columns',
	function ( $coloane ) {
		$coloane['aptv_recomandat'] = '★ Prima pagină';
		return $coloane;
	}
);

add_action(
	'manage_post_posts_custom_column',
	function ( $coloana, $post_id ) {
		if ( 'aptv_recomandat' !== $coloana ) {
			return;
		}
		$moment = (int) get_post_meta( $post_id, '_aptv_recomandat', true );
		if ( $moment ) {
			echo '★ bifat ' . esc_html( wp_date( 'j M Y', $moment ) );
		}
	},
	10,
	2
);
