<?php
/**
 * PRIMA PAGINĂ (homepage).
 *
 * Secțiunile, de sus în jos:
 *   1. Bun venit + episodul recomandat   – textele: Apollonia TV → Setări; episodul: bifa „Recomandat”
 *   2. Cum găsiți ce vă interesează      – text fix (3 pași)
 *   3. Cele mai noi materiale            – automat: ultimele 3 episoade (fără știrile științifice)
 *   4. Știri științifice, pe scurt       – automat: ultimele 6 din categoria „Științifice”
 *   5. Răsfoiți după temă                – automat: temele din Emisiuni, cu emisiunile lor
 *   6. Din universitate                  – automat: emisiunile din categoria „Universitate”
 *   7. Urmăriți-ne și pe                 – linkurile din Apollonia TV → Setări
 * (Banda „Nu găsiți ce căutați?” și subsolul vin din inc/subsol.php.)
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
$stiri        = get_term_by( 'slug', 'stiri', 'category' );
$universitate = get_term_by( 'slug', 'universitate', 'category' );

// Cele mai noi: fără clipurile scurte și fără episodul deja recomandat sus.
$cele_mai_noi = get_posts(
	array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => 3,
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
		'post__not_in'        => $recomandat ? array( $recomandat->ID ) : array(),
		// Fără știrile științifice (au secțiunea lor) și fără articolele rămase în „Necategorisite”.
		'category__not_in'    => array_filter( array( $stiintifice ? $stiintifice->term_id : 0, (int) get_option( 'default_category' ) ) ),
	)
);
$scurte = $stiintifice ? aptv_cele_mai_noi( $stiintifice->term_id, 6 ) : array();
?>
<main id="main" class="aptv-main">

	<!-- 1. Bun venit + recomandat -->
	<section class="aptv-hero">
		<div class="aptv-container aptv-hero__in">
			<div class="aptv-hero__text">
				<span class="aptv-eticheta aptv-eticheta--galben">BINE AȚI VENIT</span>
				<h1><?php echo esc_html( aptv_setare( 'hero_titlu' ) ); ?></h1>
				<p class="aptv-hero__intro"><?php echo esc_html( aptv_setare( 'hero_text' ) ); ?></p>
				<div class="aptv-butoane">
					<a class="aptv-buton" href="#noutati">Vezi cele mai noi materiale</a>
					<?php if ( $emisiuni ) : ?>
						<a class="aptv-buton aptv-buton--contur" href="<?php echo esc_url( get_term_link( $emisiuni ) ); ?>">Alege o emisiune</a>
					<?php endif; ?>
					<?php if ( $stiri ) : ?>
						<a class="aptv-buton aptv-buton--contur" href="<?php echo esc_url( get_term_link( $stiri ) ); ?>">Știri</a>
					<?php endif; ?>
				</div>
			</div>
			<?php if ( $recomandat ) : ?>
				<div class="aptv-hero__recomandat">
					<span class="aptv-eticheta aptv-eticheta--rosu aptv-hero__insigna">RECOMANDAT</span>
					<?php aptv_card( $recomandat, 'mare' ); ?>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<!-- 2. Cum găsiți ce vă interesează -->
	<section class="aptv-container aptv-sectiune" aria-labelledby="aptv-cum">
		<h2 id="aptv-cum">Cum găsiți ce vă interesează</h2>
		<ol class="aptv-pasi">
			<li><span class="aptv-pasi__nr" aria-hidden="true">1</span><span><strong>Alegeți o temă</strong>De exemplu Cultură, Sănătate sau Știri.</span></li>
			<li><span class="aptv-pasi__nr" aria-hidden="true">2</span><span><strong>Alegeți emisiunea</strong>Vedeți toate episoadele, în ordine.</span></li>
			<li><span class="aptv-pasi__nr" aria-hidden="true">3</span><span><strong>Apăsați „Pornește”</strong>Filmul pornește chiar pe pagină.</span></li>
		</ol>
	</section>

	<!-- 3. Cele mai noi materiale -->
	<?php if ( $cele_mai_noi ) : ?>
		<section class="aptv-container aptv-sectiune" id="noutati" aria-labelledby="aptv-noi">
			<div class="aptv-sectiune__cap">
				<h2 id="aptv-noi">Cele mai noi materiale</h2>
			</div>
			<div class="aptv-grila aptv-grila--3">
				<?php foreach ( $cele_mai_noi as $p ) : ?>
					<?php aptv_card( $p ); ?>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

	<!-- 4. Știri științifice -->
	<?php if ( $scurte ) : ?>
		<section class="aptv-container aptv-sectiune" aria-labelledby="aptv-sci">
			<div class="aptv-sectiune__cap">
				<h2 id="aptv-sci">Știri științifice, pe scurt</h2>
				<a href="<?php echo esc_url( get_term_link( $stiintifice ) ); ?>">Toate știrile științifice →</a>
			</div>
			<p class="aptv-sectiune__intro">Descoperiri din medicină și știință, explicate în mai puțin de un minut.</p>
			<div class="aptv-grila aptv-grila--scurte">
				<?php foreach ( $scurte as $p ) : ?>
					<?php aptv_card( $p, 'scurt' ); ?>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

	<!-- 5. Răsfoiți după temă -->
	<?php $teme = aptv_subcategorii( $emisiuni ); ?>
	<?php if ( $teme ) : ?>
		<section class="aptv-container aptv-sectiune" aria-labelledby="aptv-teme">
			<h2 id="aptv-teme">Răsfoiți după temă</h2>
			<p class="aptv-sectiune__intro">Nu știți numele emisiunii? Alegeți tema care vă interesează.</p>
			<div class="aptv-grila aptv-grila--4">
				<?php foreach ( $teme as $tema ) : ?>
					<?php
					list( $fundal, $text ) = aptv_culoare( $tema );
					$nume_emisiuni         = wp_list_pluck( aptv_subcategorii( $tema ), 'name' );
					?>
					<a class="aptv-tema" href="<?php echo esc_url( get_term_link( $tema ) ); ?>">
						<span class="aptv-tema__nume" style="background:<?php echo esc_attr( $fundal ); ?>;color:<?php echo esc_attr( $text ); ?>"><?php echo esc_html( $tema->name ); ?></span>
						<?php if ( $tema->description ) : ?>
							<span class="aptv-tema__desc"><?php echo esc_html( $tema->description ); ?></span>
						<?php endif; ?>
						<?php if ( $nume_emisiuni ) : ?>
							<span class="aptv-tema__lista"><strong>Conține:</strong> <?php echo esc_html( implode( ', ', $nume_emisiuni ) ); ?></span>
						<?php endif; ?>
						<span class="aptv-tema__mergi">Deschide →</span>
					</a>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

	<!-- 6. Din universitate -->
	<?php $din_univ = aptv_subcategorii( $universitate ); ?>
	<?php if ( $din_univ ) : ?>
		<section class="aptv-container aptv-sectiune" aria-labelledby="aptv-univ">
			<div class="aptv-univ">
				<div class="aptv-univ__text">
					<span class="aptv-eticheta aptv-eticheta--pe-inchis">DIN UNIVERSITATE</span>
					<h2 id="aptv-univ">Universitatea „Apollonia” din Iași</h2>
					<p><?php echo esc_html( $universitate->description ); ?></p>
				</div>
				<div class="aptv-univ__lista">
					<?php foreach ( $din_univ as $em ) : ?>
						<a href="<?php echo esc_url( get_term_link( $em ) ); ?>">
							<span><strong><?php echo esc_html( $em->name ); ?></strong>
							<?php if ( $em->description ) : ?><br><span class="aptv-mic"><?php echo esc_html( $em->description ); ?></span><?php endif; ?></span>
							<span class="aptv-univ__mergi">Vezi →</span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<!-- 7. Rețele sociale -->
	<section class="aptv-container aptv-sectiune" aria-labelledby="aptv-social">
		<h2 id="aptv-social">Urmăriți-ne și pe rețelele sociale</h2>
		<p class="aptv-sectiune__intro">Aceleași materiale, acolo unde vă este mai comod.</p>
		<div class="aptv-grila aptv-grila--4">
			<?php foreach ( aptv_retele() as $cheie => $nume ) : ?>
				<?php if ( aptv_setare( $cheie ) ) : ?>
					<a class="aptv-retea" href="<?php echo esc_url( aptv_setare( $cheie ) ); ?>" target="_blank" rel="noopener"><?php echo aptv_icon_retea( $cheie ); // phpcs:ignore ?><?php echo esc_html( $nume ); ?></a>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
	</section>

</main>
<?php
get_footer();
