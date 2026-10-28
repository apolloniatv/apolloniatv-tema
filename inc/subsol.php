<?php
/**
 * MODUL: Subsolul site-ului (partea de jos a fiecărei pagini).
 *
 * CE CONȚINE:
 *  1. Banda galbenă „Nu găsiți ce căutați? Sunați-ne” — pe toate paginile,
 *     pentru că e cel mai important ajutor pentru vizitatorii vârstnici.
 *  2. Coloanele: despre Apollonia TV, linkuri, contact, rețele sociale.
 *
 * Toate textele și datele de contact vin din Apollonia TV → Setări.
 * Linkul „Contact” din meniu duce aici (ancora #contact).
 *
 * @package apolloniatv
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dezactivează subsolul standard GeneratePress.
 */
add_action(
	'after_setup_theme',
	function () {
		remove_action( 'generate_footer', 'generate_construct_footer_widgets', 5 );
		remove_action( 'generate_footer', 'generate_construct_footer' );
	},
	20
);

/**
 * Pictogramele rețelelor sociale (desen simplu, fără fișiere externe).
 */
function aptv_icon_retea( $retea ) {
	$desene = array(
		'youtube'   => '<rect x="2" y="5" width="20" height="14" rx="4"/><path d="M10 9v6l5-3z" fill="currentColor"/>',
		'facebook'  => '<rect x="3" y="3" width="18" height="18" rx="4"/><path d="M15 8h-1.5a2 2 0 0 0-2 2v11M9.5 13h5"/>',
		'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.3" cy="6.7" r="0.6" fill="currentColor"/>',
		'tiktok'    => '<path d="M14 3v11.5a3.5 3.5 0 1 1-3.5-3.5"/><path d="M14 3c.5 2.6 2.4 4.4 5 4.6"/>',
	);
	return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . ( $desene[ $retea ] ?? '' ) . '</svg>';
}

/**
 * Lista rețelelor sociale, cu numele afișat.
 */
function aptv_retele() {
	return array(
		'youtube'   => 'YouTube',
		'facebook'  => 'Facebook',
		'instagram' => 'Instagram',
		'tiktok'    => 'TikTok',
	);
}

add_action(
	'generate_footer',
	function () {
		$email = aptv_setare( 'email' );
		// În Portofoliu, banda galbenă e înlocuită de blocul „Cereți o ofertă” din pagină.
		if ( ! aptv_e_portofoliu() ) :
		?>
		<section class="aptv-container aptv-ajutor" aria-labelledby="aptv-ajutor-titlu">
			<div class="aptv-ajutor__in">
				<div>
					<h2 id="aptv-ajutor-titlu">Nu găsiți ce căutați?</h2>
					<p>Sunați-ne (<?php echo esc_html( aptv_setare( 'program' ) ); ?>) și vă ajutăm să ajungeți la emisiunea dorită.</p>
				</div>
				<div class="aptv-ajutor__butoane">
					<a class="aptv-buton aptv-buton--inchis" href="<?php echo esc_attr( aptv_telefon_link() ); ?>">Sunați: <?php echo esc_html( aptv_setare( 'telefon' ) ); ?></a>
					<?php if ( aptv_whatsapp_link() ) : ?>
						<a class="aptv-buton aptv-buton--alb" href="<?php echo esc_url( aptv_whatsapp_link() ); ?>" target="_blank" rel="noopener"><?php echo aptv_icon_whatsapp(); // phpcs:ignore ?> Scrieți-ne pe WhatsApp</a>
					<?php endif; ?>
					<?php if ( $email ) : ?>
						<a class="aptv-buton aptv-buton--alb" href="<?php echo esc_attr( 'mailto:' . $email ); ?>">Scrieți-ne un e-mail</a>
					<?php endif; ?>
				</div>
			</div>
		</section>
		<?php endif; ?>

		<footer class="aptv-subsol" id="contact">
			<div class="aptv-container aptv-subsol__grila">
				<div>
					<?php echo aptv_logo( 'aptv-subsol__logo' ); // phpcs:ignore ?>
					<p><?php echo esc_html( aptv_setare( 'despre' ) ); ?></p>
				</div>
				<div>
					<h2 class="aptv-subsol__titlu">Pe site</h2>
					<ul>
						<?php foreach ( array( 'stiri', 'emisiuni', 'universitate', 'portofoliu' ) as $slug ) : ?>
							<?php $t = get_term_by( 'slug', $slug, 'category' ); ?>
							<?php if ( $t ) : ?>
								<li><a href="<?php echo esc_url( get_term_link( $t ) ); ?>"><?php echo esc_html( $t->name ); ?></a></li>
							<?php endif; ?>
						<?php endforeach; ?>
					</ul>
				</div>
				<div>
					<h2 class="aptv-subsol__titlu">Contact</h2>
					<p>
						Telefon: <a href="<?php echo esc_attr( aptv_telefon_link() ); ?>"><?php echo esc_html( aptv_setare( 'telefon' ) ); ?></a><br>
						<?php if ( aptv_whatsapp_link() ) : ?>
							WhatsApp: <a href="<?php echo esc_url( aptv_whatsapp_link() ); ?>" target="_blank" rel="noopener"><?php echo esc_html( aptv_setare( 'whatsapp' ) ); ?></a><br>
						<?php endif; ?>
						<?php if ( $email ) : ?>
							E-mail: <a href="<?php echo esc_attr( 'mailto:' . $email ); ?>"><?php echo esc_html( $email ); ?></a><br>
						<?php endif; ?>
						<?php echo esc_html( aptv_setare( 'adresa' ) ); ?>
					</p>
				</div>
				<div>
					<h2 class="aptv-subsol__titlu">Urmăriți-ne și pe</h2>
					<ul class="aptv-subsol__retele">
						<?php foreach ( aptv_retele() as $cheie => $nume ) : ?>
							<?php if ( aptv_setare( $cheie ) ) : ?>
								<li><a href="<?php echo esc_url( aptv_setare( $cheie ) ); ?>" target="_blank" rel="noopener"><?php echo aptv_icon_retea( $cheie ); // phpcs:ignore ?><?php echo esc_html( $nume ); ?></a></li>
							<?php endif; ?>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
			<p class="aptv-container aptv-subsol__copyright">© <?php echo esc_html( wp_date( 'Y' ) ); ?> Apollonia TV · Universitatea „Apollonia” din Iași</p>
		</footer>
		<?php
	}
);
