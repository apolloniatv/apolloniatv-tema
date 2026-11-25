<?php
/**
 * MODUL: Transmisiunea în direct (24/24, pe YouTube, canalul @apolloniatvcultural).
 *
 * CE FACE:
 *  - pagina www.apolloniatv.ro/in-direct/ (șablonul live.php): playerul transmisiunii,
 *    „Acum” / „Urmează” și programul săptămânii;
 *  - „● În direct” în meniu și o bandă pe prima pagină (doar când transmisiunea e pornită);
 *  - în admin, Apollonia TV → În direct: pornit/oprit și grila săptămânală.
 *
 * CUM SE FOLOSEȘTE (echipa):
 *  1. Apollonia TV → În direct → bifați „Transmisiunea e pornită” când începe transmisiunea.
 *  2. Completați grila: zi, oră de început, titlu (și, opțional, emisiunea de pe site).
 *     Fiecare program ține până la ora de început a următorului; grila se repetă săptămânal.
 *
 * Playerul arată ce transmite canalul în acel moment (adresa „live_stream?channel=…”),
 * deci nu trebuie schimbat nimic când YouTube pornește o transmisiune nouă.
 * Dacă vreodată nu merge, se poate scrie ID-ul exact al transmisiunii în câmpul „Videoclip anume”.
 *
 * @package apolloniatv
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Canalul de pe care se transmite (@apolloniatvcultural). */
const APTV_LIVE_CANAL = 'UCnepxkvIxSBNhR_QQ6BwPcw';

/** Adresa paginii (fără bare). */
const APTV_LIVE_SLUG = 'in-direct';

/** Zilele săptămânii, ca în PHP: 1 = luni ... 7 = duminică. */
function aptv_zile() {
	return array( 1 => 'Luni', 2 => 'Marți', 3 => 'Miercuri', 4 => 'Joi', 5 => 'Vineri', 6 => 'Sâmbătă', 7 => 'Duminică' );
}

/**
 * Setările transmisiunii.
 *
 * @return array{activ:bool, video:string, grila:array}
 */
function aptv_live() {
	$s = get_option( 'aptv_live', array() );
	$s = is_array( $s ) ? $s : array();
	return array(
		'activ' => ! empty( $s['activ'] ),
		'video' => isset( $s['video'] ) ? (string) $s['video'] : '',
		'grila' => isset( $s['grila'] ) && is_array( $s['grila'] ) ? $s['grila'] : array(),
	);
}

function aptv_live_activ() {
	return aptv_live()['activ'];
}

function aptv_live_url() {
	return home_url( '/' . APTV_LIVE_SLUG . '/' );
}

/**
 * Adresa playerului YouTube: transmisiunea curentă a canalului, sau un videoclip anume.
 */
function aptv_live_embed() {
	$video = aptv_live()['video'];
	if ( preg_match( '/^[A-Za-z0-9_-]{11}$/', $video ) ) {
		return 'https://www.youtube.com/embed/' . $video . '?autoplay=1&rel=0&playsinline=1';
	}
	return 'https://www.youtube.com/embed/live_stream?channel=' . APTV_LIVE_CANAL . '&autoplay=1&rel=0&playsinline=1';
}

/**
 * Grila, ordonată după zi și oră. Fiecare element: zi (1–7), ora („HH:MM”), titlu, emisiune (ID categorie sau 0).
 */
function aptv_grila() {
	$grila = array_values(
		array_filter(
			aptv_live()['grila'],
			function ( $r ) {
				return ! empty( $r['titlu'] ) && preg_match( '/^\d{2}:\d{2}$/', $r['ora'] ?? '' ) && (int) ( $r['zi'] ?? 0 ) >= 1 && (int) $r['zi'] <= 7;
			}
		)
	);
	usort(
		$grila,
		function ( $a, $b ) {
			return ( (int) $a['zi'] * 1440 + aptv_minute( $a['ora'] ) ) - ( (int) $b['zi'] * 1440 + aptv_minute( $b['ora'] ) );
		}
	);
	return $grila;
}

function aptv_minute( $ora ) {
	list( $h, $m ) = array_map( 'intval', explode( ':', $ora ) );
	return $h * 60 + $m;
}

/**
 * Ce rulează acum și ce urmează, după ora României (fusul orar al site-ului).
 *
 * @return array{0:?array,1:?array} [ programul curent, programul următor ] (null dacă grila e goală)
 */
function aptv_acum_si_urmeaza() {
	$grila = aptv_grila();
	if ( ! $grila ) {
		return array( null, null );
	}
	$acum = current_datetime();
	$pozitie = (int) $acum->format( 'N' ) * 1440 + (int) $acum->format( 'G' ) * 60 + (int) $acum->format( 'i' );
	$curent  = end( $grila ); // înainte de primul program al săptămânii rulează ultimul din săptămâna trecută
	$urmator = $grila[0];
	foreach ( $grila as $i => $r ) {
		if ( (int) $r['zi'] * 1440 + aptv_minute( $r['ora'] ) <= $pozitie ) {
			$curent  = $r;
			$urmator = $grila[ $i + 1 ] ?? $grila[0];
		}
	}
	return array( $curent, $urmator );
}

/**
 * Textul unui program: „18:00 · Mirajul Muzicii”, cu link spre emisiune dacă e legată de una.
 *
 * @param array $r       Programul.
 * @param bool  $cu_link Pune link spre pagina emisiunii.
 */
function aptv_program_html( $r, $cu_link = true ) {
	$titlu = esc_html( $r['titlu'] );
	if ( $cu_link && ! empty( $r['emisiune'] ) ) {
		$em = get_term( (int) $r['emisiune'], 'category' );
		if ( $em instanceof WP_Term ) {
			$titlu = '<a href="' . esc_url( get_term_link( $em ) ) . '">' . $titlu . '</a>';
		}
	}
	return $titlu;
}

/* ---------------------------------------------------------------------------
 * Adresa /in-direct/ (fără pagină în WordPress: șablonul e în temă).
 * ------------------------------------------------------------------------- */
add_action(
	'init',
	function () {
		add_rewrite_rule( '^' . APTV_LIVE_SLUG . '/?$', 'index.php?aptv_live=1', 'top' );
		// O singură dată după instalare: WordPress trebuie să „învețe” adresa nouă.
		if ( get_option( 'aptv_live_reguli' ) !== APTV_LIVE_SLUG ) {
			flush_rewrite_rules( false );
			update_option( 'aptv_live_reguli', APTV_LIVE_SLUG );
		}
	}
);
add_filter(
	'query_vars',
	function ( $v ) {
		$v[] = 'aptv_live';
		return $v;
	}
);
add_filter(
	'template_include',
	function ( $sablon ) {
		if ( get_query_var( 'aptv_live' ) ) {
			global $wp_query;
			$wp_query->is_404  = false;
			$wp_query->is_home = false;
			status_header( 200 );
			return APTV_DIR . '/live.php';
		}
		return $sablon;
	}
);
add_filter(
	'document_title_parts',
	function ( $p ) {
		if ( get_query_var( 'aptv_live' ) ) {
			$p['title'] = 'În direct';
		}
		return $p;
	}
);
add_filter(
	'body_class',
	function ( $c ) {
		if ( get_query_var( 'aptv_live' ) ) {
			$c[] = 'aptv-pagina-live';
		}
		return $c;
	}
);

/* ---------------------------------------------------------------------------
 * Admin: Apollonia TV → În direct.
 * ------------------------------------------------------------------------- */
add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'aptv-setari', 'Transmisiunea în direct', 'În direct', 'manage_options', 'aptv-live', 'aptv_pagina_live' );
	},
	20
);

add_action(
	'admin_post_aptv_salveaza_live',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Nu aveți drepturi pentru această acțiune.' );
		}
		check_admin_referer( 'aptv_live' );
		$grila = array();
		$zile  = (array) ( $_POST['zi'] ?? array() );
		foreach ( $zile as $i => $zi ) {
			$titlu = sanitize_text_field( wp_unslash( $_POST['titlu'][ $i ] ?? '' ) );
			$ora   = sanitize_text_field( wp_unslash( $_POST['ora'][ $i ] ?? '' ) );
			if ( '' === $titlu || ! empty( $_POST['sterge'][ $i ] ) || ! preg_match( '/^(\d{1,2}):(\d{2})$/', $ora, $m ) ) {
				continue;
			}
			$grila[] = array(
				'zi'       => max( 1, min( 7, (int) $zi ) ),
				'ora'      => sprintf( '%02d:%02d', min( 23, (int) $m[1] ), min( 59, (int) $m[2] ) ),
				'titlu'    => $titlu,
				'emisiune' => absint( $_POST['emisiune'][ $i ] ?? 0 ),
			);
		}
		$video = sanitize_text_field( wp_unslash( $_POST['video'] ?? '' ) );
		if ( preg_match( '/(?:v=|youtu\.be\/|live\/|embed\/)([A-Za-z0-9_-]{11})/', $video, $m ) ) {
			$video = $m[1]; // s-a lipit un link întreg
		}
		update_option(
			'aptv_live',
			array(
				'activ' => ! empty( $_POST['activ'] ),
				'video' => preg_match( '/^[A-Za-z0-9_-]{11}$/', $video ) ? $video : '',
				'grila' => $grila,
			)
		);
		wp_safe_redirect( admin_url( 'admin.php?page=aptv-live&salvat=1' ) );
		exit;
	}
);

function aptv_pagina_live() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$s        = aptv_live();
	$grila    = aptv_grila();
	$emisiuni = get_terms( array( 'taxonomy' => 'category', 'hide_empty' => false ) );
	$emisiuni = array_filter(
		$emisiuni,
		function ( $t ) {
			return 'emisiune' === aptv_tip_categorie( $t );
		}
	);
	// Rânduri goale la final, pentru programe noi.
	$randuri = array_merge( $grila, array_fill( 0, 5, array( 'zi' => 1, 'ora' => '', 'titlu' => '', 'emisiune' => 0 ) ) );
	?>
	<div class="wrap">
		<h1>Transmisiunea în direct</h1>
		<?php if ( isset( $_GET['salvat'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>Salvat.</p></div>
		<?php endif; ?>
		<p>Pagina de pe site: <a href="<?php echo esc_url( aptv_live_url() ); ?>" target="_blank"><?php echo esc_html( aptv_live_url() ); ?></a></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="aptv_salveaza_live">
			<?php wp_nonce_field( 'aptv_live' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">Transmisiunea e pornită</th>
					<td>
						<label><input type="checkbox" name="activ" value="1" <?php checked( $s['activ'] ); ?>> Da — arată „● În direct” în meniu și banda de pe prima pagină</label>
						<p class="description">Când nu e bifat, pagina „În direct” există, dar spune că transmisiunea începe în curând, iar meniul și prima pagină nu o anunță.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="aptv-live-video">Videoclip anume (opțional)</label></th>
					<td>
						<input id="aptv-live-video" name="video" type="text" class="regular-text" value="<?php echo esc_attr( $s['video'] ); ?>" placeholder="Lăsați gol">
						<p class="description">Gol = transmisiunea curentă a canalului @apolloniatvcultural (recomandat). Completați linkul unei transmisiuni doar dacă playerul nu o găsește singur.</p>
					</td>
				</tr>
			</table>

			<h2>Grila săptămânală</h2>
			<p>Un rând = un program. Fiecare program ține până începe următorul. Grila se repetă în fiecare săptămână.
				Pentru un program zilnic, adăugați câte un rând pentru fiecare zi. Ca să ștergeți un rând, bifați „Șterge” sau goliți titlul.</p>
			<table class="widefat striped" style="max-width:980px">
				<thead><tr><th>Zi</th><th>Ora de început</th><th>Titlu</th><th>Emisiunea de pe site (opțional)</th><th>Șterge</th></tr></thead>
				<tbody>
				<?php foreach ( $randuri as $i => $r ) : ?>
					<tr>
						<td>
							<select name="zi[<?php echo (int) $i; ?>]">
								<?php foreach ( aptv_zile() as $n => $zi ) : ?>
									<option value="<?php echo (int) $n; ?>" <?php selected( (int) $r['zi'], $n ); ?>><?php echo esc_html( $zi ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
						<td><input type="time" name="ora[<?php echo (int) $i; ?>]" value="<?php echo esc_attr( $r['ora'] ); ?>"></td>
						<td><input type="text" name="titlu[<?php echo (int) $i; ?>]" value="<?php echo esc_attr( $r['titlu'] ); ?>" class="regular-text"></td>
						<td>
							<select name="emisiune[<?php echo (int) $i; ?>]">
								<option value="0">—</option>
								<?php foreach ( $emisiuni as $em ) : ?>
									<option value="<?php echo (int) $em->term_id; ?>" <?php selected( (int) $r['emisiune'], (int) $em->term_id ); ?>><?php echo esc_html( $em->name ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
						<td><?php if ( '' !== $r['titlu'] ) : ?><input type="checkbox" name="sterge[<?php echo (int) $i; ?>]" value="1"><?php endif; ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<p class="description">Ai nevoie de mai multe rânduri? Salvează: după fiecare salvare apar încă 5 rânduri goale.</p>
			<?php submit_button( 'Salvează' ); ?>
		</form>
	</div>
	<?php
}
