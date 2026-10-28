<?php
/**
 * MODUL: Setările site-ului (meniul „Apollonia TV” din admin).
 *
 * CE FACE: adaugă în admin pagina „Apollonia TV → Setări”, unde echipa poate schimba,
 * fără cod: telefonul, programul, e-mailul, adresa, linkurile rețelelor sociale,
 * descrierea din subsol și cheia YouTube API (folosită la import).
 *
 * UNDE SE FOLOSESC: oriunde în temă se scrie aptv_setare( 'telefon' ) etc.
 *
 * SIGURANȚĂ: doar administratorii pot vedea și salva pagina. Cheia YouTube nu este
 * niciodată afișată pe site și nici în pagina de setări după salvare.
 *
 * @package apolloniatv
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Valorile implicite — apar până când echipa completează pagina de setări.
 * Datele de contact sunt provizorii.
 */
function aptv_setari_implicite() {
	return array(
		'telefon'         => '0232 000 000',
		'whatsapp'        => '0770 960 762',
		'program'         => 'luni–vineri, 9–17',
		'email'           => 'contact@apolloniatv.ro',
		'adresa'          => '[Adresa redacției], Iași',
		'youtube'         => 'https://www.youtube.com/channel/UCA5cYbtmQExq5QuYj3U8OlQ',
		'facebook'        => 'https://www.facebook.com/ApolloniaTV',
		'instagram'       => 'https://www.instagram.com/apolloniatv/',
		'tiktok'          => 'https://www.tiktok.com/@apolloniatv',
		'despre'          => 'Platforma media educațională a Universității „Apollonia” din Iași: emisiuni, știri și reportaje, gratuit, pentru toată lumea.',
	);
}

/**
 * Citește o setare. Exemplu: aptv_setare( 'telefon' ) întoarce „0232 000 000”.
 *
 * @param string $cheie Numele setării (vezi lista din aptv_setari_implicite).
 * @return string
 */
function aptv_setare( $cheie ) {
	$salvate = get_option( 'aptv_setari', array() );
	$toate   = wp_parse_args( is_array( $salvate ) ? $salvate : array(), aptv_setari_implicite() );
	return isset( $toate[ $cheie ] ) ? (string) $toate[ $cheie ] : '';
}

/**
 * Telefonul în format pentru linkuri „tel:” (doar cifre și +).
 */
function aptv_telefon_link() {
	return 'tel:' . preg_replace( '/[^0-9+]/', '', aptv_setare( 'telefon' ) );
}

/**
 * Linkul care deschide o conversație WhatsApp cu numărul din setări (ex. 0700 000 000 → https://wa.me/40700000000).
 * Șir gol dacă nu e setat niciun număr.
 */
function aptv_whatsapp_link() {
	$cifre = preg_replace( '/\D/', '', aptv_setare( 'whatsapp' ) );
	if ( '' === $cifre ) {
		return '';
	}
	if ( 0 === strpos( $cifre, '00' ) ) {
		$cifre = substr( $cifre, 2 );        // 0040... → 40...
	} elseif ( 0 === strpos( $cifre, '0' ) ) {
		$cifre = '4' . $cifre;               // 07... (număr românesc) → 407...
	}
	return 'https://wa.me/' . $cifre;
}

/**
 * Pictograma WhatsApp simplificată (un balon de conversație cu receptor).
 */
function aptv_icon_whatsapp() {
	return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M4 20l1.3-3.9A8.5 8.5 0 1 1 8 19.1z"/><path d="M9 8.5c0 3.5 3 6.5 6.5 6.5l1-1.6-2-.9-.9 1a4.5 4.5 0 0 1-3-3l1-.9-.9-2z" fill="currentColor" stroke="none"/></svg>';
}

/**
 * Cheia YouTube API — stocată separat și neîncărcată automat la fiecare pagină.
 */
function aptv_cheie_youtube() {
	return (string) get_option( 'aptv_youtube_cheie', '' );
}

/**
 * Meniul „Apollonia TV” din admin, cu submeniul „Setări”.
 * Celelalte submeniuri (Structură, Import YouTube) se adaugă din modulele lor.
 */
add_action(
	'admin_menu',
	function () {
		add_menu_page( 'Apollonia TV', 'Apollonia TV', 'manage_options', 'aptv-setari', 'aptv_pagina_setari', 'dashicons-video-alt3', 3 );
		add_submenu_page( 'aptv-setari', 'Setări Apollonia TV', 'Setări', 'manage_options', 'aptv-setari', 'aptv_pagina_setari' );
	}
);

/**
 * Salvarea formularului de setări.
 */
add_action(
	'admin_post_aptv_salveaza_setari',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Nu aveți drepturi pentru această acțiune.' );
		}
		check_admin_referer( 'aptv_setari' );

		$implicite = aptv_setari_implicite();
		$noi       = array();
		$campuri_url   = array( 'youtube', 'facebook', 'instagram', 'tiktok' );
		$campuri_lungi = array( 'despre' );

		foreach ( array_keys( $implicite ) as $cheie ) {
			$valoare = isset( $_POST[ $cheie ] ) ? wp_unslash( $_POST[ $cheie ] ) : '';
			if ( in_array( $cheie, $campuri_url, true ) ) {
				$noi[ $cheie ] = esc_url_raw( $valoare );
			} elseif ( 'email' === $cheie ) {
				$noi[ $cheie ] = sanitize_email( $valoare );
			} elseif ( in_array( $cheie, $campuri_lungi, true ) ) {
				$noi[ $cheie ] = sanitize_textarea_field( $valoare );
			} else {
				$noi[ $cheie ] = sanitize_text_field( $valoare );
			}
		}
		update_option( 'aptv_setari', $noi );

		// Cheia YouTube se schimbă doar dacă s-a scris ceva nou în câmp (câmpul e mereu gol la afișare).
		if ( ! empty( $_POST['youtube_cheie'] ) ) {
			update_option( 'aptv_youtube_cheie', sanitize_text_field( wp_unslash( $_POST['youtube_cheie'] ) ), false );
		}
		if ( ! empty( $_POST['sterge_cheie'] ) ) {
			delete_option( 'aptv_youtube_cheie' );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=aptv-setari&salvat=1' ) );
		exit;
	}
);

/**
 * Afișarea paginii de setări.
 */
function aptv_pagina_setari() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$etichete = array(
		'telefon'    => array( 'Telefon (apare în bara de sus și în subsol)', 'text' ),
		'program'    => array( 'Programul la telefon', 'text' ),
		'whatsapp'   => array( 'Număr WhatsApp (lăsați gol ca să nu apară pe site)', 'text' ),
		'email'      => array( 'E-mail de contact', 'email' ),
		'adresa'     => array( 'Adresa redacției', 'text' ),
		'youtube'    => array( 'Link YouTube', 'url' ),
		'facebook'   => array( 'Link Facebook', 'url' ),
		'instagram'  => array( 'Link Instagram', 'url' ),
		'tiktok'     => array( 'Link TikTok', 'url' ),
		'despre'     => array( 'Subsol — scurtă descriere a Apollonia TV', 'textarea' ),
	);
	$are_cheie = '' !== aptv_cheie_youtube();
	?>
	<div class="wrap">
		<h1>Setări Apollonia TV</h1>
		<?php if ( isset( $_GET['salvat'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>Setările au fost salvate.</p></div>
		<?php endif; ?>
		<p>Aici schimbați datele care apar pe site. Nu este nevoie de cod.</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="aptv_salveaza_setari">
			<?php wp_nonce_field( 'aptv_setari' ); ?>
			<table class="form-table" role="presentation">
				<?php foreach ( $etichete as $cheie => $info ) : ?>
					<tr>
						<th scope="row"><label for="aptv-<?php echo esc_attr( $cheie ); ?>"><?php echo esc_html( $info[0] ); ?></label></th>
						<td>
							<?php if ( 'textarea' === $info[1] ) : ?>
								<textarea id="aptv-<?php echo esc_attr( $cheie ); ?>" name="<?php echo esc_attr( $cheie ); ?>" rows="3" class="large-text"><?php echo esc_textarea( aptv_setare( $cheie ) ); ?></textarea>
							<?php else : ?>
								<input id="aptv-<?php echo esc_attr( $cheie ); ?>" type="<?php echo esc_attr( $info[1] ); ?>" name="<?php echo esc_attr( $cheie ); ?>" value="<?php echo esc_attr( aptv_setare( $cheie ) ); ?>" class="regular-text">
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				<tr>
					<th scope="row"><label for="aptv-youtube-cheie">Cheia YouTube Data API</label></th>
					<td>
						<input id="aptv-youtube-cheie" type="password" name="youtube_cheie" value="" class="regular-text" autocomplete="off">
						<p class="description">
							<?php echo $are_cheie ? '✔ O cheie este deja salvată. Lăsați câmpul gol ca să o păstrați.' : 'Nicio cheie salvată încă. Este necesară doar pentru „Import YouTube”.'; ?>
						</p>
						<?php if ( $are_cheie ) : ?>
							<label><input type="checkbox" name="sterge_cheie" value="1"> Șterge cheia salvată</label>
						<?php endif; ?>
					</td>
				</tr>
			</table>
			<?php submit_button( 'Salvează setările' ); ?>
		</form>
	</div>
	<?php
}
