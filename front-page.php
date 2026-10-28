<?php
/**
 * PRIMA PAGINĂ (homepage) — direcția „TV” (v0.2).
 *
 * Secțiunile, de sus în jos:
 *   1. Recomandat              – episodul bifat „Recomandat” (inc/recomandat.php), mare, cu butonul „Pornește”
 *   2. Cele mai noi materiale  – automat: ultimele 4 (fără știrile științifice scurte)
 *   3. Emisiuni pe teme        – automat: temele și emisiunile lor (doar cele cu episoade)
 *   4. Știri științifice       – automat: ultimele 6 din categoria „Științifice”
 *   5. Din universitate        – automat: emisiunile din categoria „Universitate”
 * (Banda galbenă cu telefonul și subsolul vin din inc/subsol.php.)
 *
 * O secțiune fără conținut nu se afișează.
 *
 * @package apolloniatv
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$recomandat   = aptv_episod_recomandat();
$stiintifice  = get_term_by( 'slug', 'stiintifice', 'category' );
$emisiuni     = get_term_by( 'slug', 'emisiuni', 'category' );
$universitate = get_term_by( 'slug', 'universitate', 'category' );

// Cele mai noi: fără clipurile scurte, fără „Necategorisite” și fără episodul deja recomandat sus.
$cele_mai_noi = get_posts(
	array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => 4,
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
		'post__not_in'        => $recomandat ? array( $recomandat->ID ) : array(),
		'category__not_in'    => array_filter( array( $stiintifice ? $stiintifice->term_id : 0, (int) get_option( 'default_category' ) ) ),
	)
);
$scurte = $stiintifice ? aptv_cele_mai_noi( $stiintifice->term_id, 6 ) : array();
?>
<main id="main" class="aptv-main">

	<!-- 1. Recomandat -->
	<?php if ( $recomandat ) : ?>
		<?php
		$cat_rec = aptv_categoria_episodului( $recomandat );
		$dur_rec = aptv_durata( $recomandat );
		?>
		<section class="aptv-container aptv-recomandat" aria-labelledby="aptv-rec-titlu">
			<p class="aptv-recomandat__bun-venit"><?php echo esc_html( aptv_setare( 'bun_venit' ) ); ?></p>
			<div class="aptv-recomandat__in">
				<a class="aptv-recomandat__media" href="<?php echo esc_url( get_permalink( $recomandat ) ); ?>" tabindex="-1" aria-hidden="true">
					<?php echo aptv_imagine( $recomandat, 'aptv-card__img', true ); // phpcs:ignore ?>
					<span class="aptv-redare aptv-redare--mare"><?php echo aptv_icon_play(); // phpcs:ignore ?><?php echo $dur_rec ? '<span>' . esc_html( $dur_rec ) . '</span>' : ''; ?></span>
				</a>
				<div class="aptv-recomandat__text">
					<span class="aptv-etichete">
						<span class="aptv-eticheta aptv-eticheta--recomandat">RECOMANDAT</span>
						<?php echo aptv_eticheta( $cat_rec ); // phpcs:ignore ?>
					</span>
					<h1 id="aptv-rec-titlu" class="aptv-titlu-banda"><a href="<?php echo esc_url( get_permalink( $recomandat ) ); ?>"><?php echo esc_html( get_the_title( $recomandat ) ); ?></a></h1>
					<p class="aptv-recomandat__meta"><?php echo esc_html( get_the_date( 'j F Y', $recomandat ) . ( aptv_durata_text( $recomandat ) ? ' · ' . aptv_durata_text( $recomandat ) : '' ) ); ?></p>
					<a class="aptv-buton aptv-buton--mare" href="<?php echo esc_url( get_permalink( $recomandat ) ); ?>"><?php echo aptv_icon_play(); // phpcs:ignore ?> Pornește</a>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<!-- 2. Cele mai noi materiale -->
	<?php if ( $cele_mai_noi ) : ?>
		<section class="aptv-container aptv-sectiune" id="noutati" aria-labelledby="aptv-noi">
			<h2 id="aptv-noi" class="aptv-titlu-banda">Cele mai noi materiale</h2>
			<div class="aptv-grila aptv-grila--4">
				<?php foreach ( $cele_mai_noi as $p ) : ?>
					<?php aptv_card( $p ); ?>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

	<!-- 3. Emisiuni pe teme -->
	<?php $teme = aptv_subcategorii_cu_continut( $emisiuni ); ?>
	<?php if ( $teme ) : ?>
		<section class="aptv-container aptv-sectiune" aria-labelledby="aptv-teme">
			<h2 id="aptv-teme" class="aptv-titlu-banda">Emisiuni pe teme</h2>
			<p class="aptv-sectiune__intro">Nu știți numele emisiunii? Alegeți tema, apoi emisiunea.</p>
			<div class="aptv-grila aptv-grila--teme">
				<?php foreach ( $teme as $tema ) : ?>
					<?php list( $fundal, $text ) = aptv_culoare( $tema ); ?>
					<div class="aptv-tema">
						<a class="aptv-tema__cap" href="<?php echo esc_url( get_term_link( $tema ) ); ?>" style="background:<?php echo esc_attr( $fundal ); ?>;color:<?php echo esc_attr( $text ); ?>"><?php echo esc_html( $tema->name ); ?></a>
						<?php foreach ( aptv_subcategorii_cu_continut( $tema ) as $em ) : ?>
							<a class="aptv-tema__emisiune" href="<?php echo esc_url( get_term_link( $em ) ); ?>">
								<span><?php echo esc_html( $em->name ); ?></span>
								<span class="aptv-tema__nr"><?php echo esc_html( aptv_plural( (int) $em->count, 'episod', 'episoade' ) ); ?> ›</span>
							</a>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

	<!-- 4. Știri științifice -->
	<?php if ( $scurte ) : ?>
		<section class="aptv-container aptv-sectiune" aria-labelledby="aptv-sci">
			<div class="aptv-sectiune__cap">
				<h2 id="aptv-sci" class="aptv-titlu-banda">Știri științifice, pe scurt</h2>
				<a href="<?php echo esc_url( get_term_link( $stiintifice ) ); ?>">Toate cele <?php echo esc_html( aptv_plural( (int) $stiintifice->count, 'știre', 'știri' ) ); ?> ›</a>
			</div>
			<p class="aptv-sectiune__intro">Descoperiri din medicină și știință, explicate în mai puțin de un minut.</p>
			<div class="aptv-grila aptv-grila--scurte">
				<?php foreach ( $scurte as $p ) : ?>
					<?php aptv_card( $p, 'scurt' ); ?>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

	<!-- 5. Din universitate -->
	<?php $din_univ = aptv_subcategorii_cu_continut( $universitate ); ?>
	<?php if ( $din_univ ) : ?>
		<section class="aptv-container aptv-sectiune" aria-labelledby="aptv-univ">
			<div class="aptv-univ">
				<div class="aptv-univ__text">
					<span class="aptv-eticheta aptv-eticheta--alb">DIN UNIVERSITATE</span>
					<h2 id="aptv-univ">Universitatea „Apollonia” din Iași</h2>
					<p><?php echo esc_html( $universitate->description ); ?></p>
				</div>
				<div class="aptv-univ__lista">
					<?php foreach ( $din_univ as $em ) : ?>
						<a href="<?php echo esc_url( get_term_link( $em ) ); ?>">
							<span><?php echo esc_html( $em->name ); ?></span>
							<span class="aptv-univ__nr"><?php echo esc_html( aptv_plural( (int) $em->count, 'episod', 'episoade' ) ); ?> ›</span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

</main>
<?php
get_footer();
