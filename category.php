<?php
/**
 * PAGINILE DE CATEGORIE: secțiuni, teme, emisiuni și tipuri de știri.
 *
 * O singură pagină care se comportă diferit după tipul categoriei (vezi inc/structura.php):
 *
 *   Emisiuni (secțiune)   → toate temele, fiecare cu emisiunile ei (carduri)
 *   Cultură (temă)        → butoane cu temele + emisiunile temei alese
 *   Universitate          → emisiunile din universitate (carduri)
 *   Stop cadru (emisiune) → banda emisiunii + lista episoadelor în ordine cronologică
 *   Știri / Locale ...    → butoane cu tipurile de știri + cele mai noi știri
 *   Portofoliu            → serviciile video oferite + proiectele realizate + „Cereți o ofertă”
 *
 * @package apolloniatv
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$categorie = get_queried_object();
$tip       = aptv_tip_categorie( $categorie );
$sectiune  = aptv_sectiune( $categorie );

/**
 * Cardul unei emisiuni (folosit doar pe această pagină).
 */
$card_emisiune = function ( $emisiune ) {
	$parinte               = $emisiune->parent ? get_term( $emisiune->parent, 'category' ) : null;
	?>
	<a class="aptv-emisiune" href="<?php echo esc_url( get_term_link( $emisiune ) ); ?>">
		<span class="aptv-emisiune__afis aptv-c" style="<?php echo esc_attr( aptv_stil_culoare( $emisiune ) ); ?>">
			<?php if ( $parinte ) : ?>
				<span class="aptv-emisiune__tema"><?php echo esc_html( mb_strtoupper( $parinte->name ) ); ?></span>
			<?php endif; ?>
			<span class="aptv-emisiune__nume"><?php echo esc_html( $emisiune->name ); ?></span>
		</span>
		<span class="aptv-emisiune__corp">
			<?php if ( $emisiune->description ) : ?>
				<span><?php echo esc_html( $emisiune->description ); ?></span>
			<?php endif; ?>
			<span class="aptv-mic"><?php echo esc_html( aptv_plural( (int) $emisiune->count, 'episod', 'episoade' ) ); ?></span>
			<span class="aptv-buton aptv-buton--mic">Vezi episoadele ›</span>
		</span>
	</a>
	<?php
};

/**
 * Rândul de butoane (teme sau tipuri de știri), cu cel curent evidențiat.
 */
$butoane = function ( $parinte, $curent, $eticheta_toate ) {
	?>
	<nav class="aptv-file" aria-label="Alegeți">
		<a href="<?php echo esc_url( get_term_link( $parinte ) ); ?>"<?php echo $curent->term_id === $parinte->term_id ? ' class="is-curent" aria-current="page"' : ''; ?>><?php echo esc_html( $eticheta_toate ); ?></a>
		<?php foreach ( aptv_subcategorii_cu_continut( $parinte ) as $copil ) : ?>
			<a href="<?php echo esc_url( get_term_link( $copil ) ); ?>"<?php echo $curent->term_id === $copil->term_id ? ' class="is-curent" aria-current="page"' : ''; ?>><?php echo esc_html( $copil->name ); ?></a>
		<?php endforeach; ?>
	</nav>
	<?php
};
?>
<main id="main" class="aptv-main">

<?php if ( 'emisiune' === $tip ) : ?>
	<?php
	/* ---------------- Pagina unei emisiuni ---------------- */
	$stil_culoare = aptv_stil_culoare( $categorie );
	$primul                = get_posts( array( 'cat' => $categorie->term_id, 'posts_per_page' => 1, 'orderby' => 'date', 'order' => 'ASC', 'no_found_rows' => true ) );
	$ultimul               = get_posts( array( 'cat' => $categorie->term_id, 'posts_per_page' => 1, 'orderby' => 'date', 'order' => 'DESC', 'no_found_rows' => true ) );
	$parinte               = $categorie->parent ? get_term( $categorie->parent, 'category' ) : null;
	$noi_primele           = aptv_ordine_noi();
	$total                 = (int) $GLOBALS['wp_query']->found_posts;
	$pagina                = max( 1, (int) get_query_var( 'paged' ) );
	$adresa                = get_term_link( $categorie );
	?>
	<section class="aptv-banda aptv-c" style="<?php echo esc_attr( $stil_culoare ); ?>">
		<div class="aptv-container">
			<?php aptv_unde_va_aflati( $categorie ); ?>
			<div class="aptv-banda__in">
				<div class="aptv-banda__text">
					<span class="aptv-banda__supra">EMISIUNE<?php echo $parinte ? ' · ' . esc_html( mb_strtoupper( $parinte->name ) ) : ''; ?></span>
					<h1><?php echo esc_html( $categorie->name ); ?></h1>
					<?php if ( $categorie->description ) : ?>
						<p><?php echo esc_html( $categorie->description ); ?></p>
					<?php endif; ?>
					<?php if ( $primul && $ultimul ) : ?>
						<?php
						$an1 = get_the_date( 'Y', $primul[0] );
						$an2 = get_the_date( 'Y', $ultimul[0] );
						?>
						<p class="aptv-banda__info"><?php echo esc_html( aptv_plural( $total, 'episod', 'episoade' ) . ' · ' . ( $an1 === $an2 ? $an1 : $an1 . '–' . $an2 ) ); ?></p>
					<?php endif; ?>
				</div>
				<?php if ( $primul ) : ?>
					<a class="aptv-buton aptv-buton--inchis aptv-buton--mare" href="<?php echo esc_url( get_permalink( $primul[0] ) ); ?>"><?php echo aptv_icon_play( '#FFFFFF' ); // phpcs:ignore ?> Începe cu primul episod</a>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<div class="aptv-container aptv-sectiune">
		<div class="aptv-sectiune__cap">
			<h2 class="aptv-titlu-banda">Toate episoadele</h2>
			<nav class="aptv-file" aria-label="Ordinea episoadelor">
				<a href="<?php echo esc_url( $adresa ); ?>"<?php echo $noi_primele ? '' : ' class="is-curent" aria-current="true"'; ?>>Cele mai vechi primele</a>
				<a href="<?php echo esc_url( add_query_arg( 'ordine', 'noi', $adresa ) ); ?>"<?php echo $noi_primele ? ' class="is-curent" aria-current="true"' : ''; ?>>Cele mai noi primele</a>
			</nav>
		</div>

		<?php if ( have_posts() ) : ?>
			<ol class="aptv-episoade">
				<?php
				$index = 0;
				while ( have_posts() ) :
					the_post();
					$nr = $noi_primele
						? $total - ( ( $pagina - 1 ) * APTV_EPISOADE_PE_PAGINA ) - $index
						: ( ( $pagina - 1 ) * APTV_EPISOADE_PE_PAGINA ) + $index + 1;
					++$index;
					$durata = aptv_durata();
					?>
					<li>
						<a class="aptv-episod" href="<?php the_permalink(); ?>">
							<span class="aptv-episod__media">
								<?php echo aptv_imagine( null, 'aptv-card__img' ); // phpcs:ignore ?>
								<span class="aptv-redare"><?php echo aptv_icon_play(); // phpcs:ignore ?><?php echo $durata ? '<span>' . esc_html( $durata ) . '</span>' : ''; ?></span>
							</span>
							<span class="aptv-episod__text">
								<span class="aptv-card__eticheta">Episodul <?php echo (int) $nr; ?> · <?php echo esc_html( get_the_date( 'j F Y' ) ); ?></span>
								<span class="aptv-episod__titlu"><?php the_title(); ?></span>
							</span>
							<span class="aptv-buton aptv-buton--mic aptv-episod__porneste">Pornește</span>
						</a>
					</li>
				<?php endwhile; ?>
			</ol>
			<?php
			the_posts_pagination(
				array(
					'prev_text'          => '← Episoadele anterioare',
					'next_text'          => 'Episoadele următoare →',
					'screen_reader_text' => 'Mai multe episoade',
				)
			);
			?>
		<?php else : ?>
			<p class="aptv-gol">Episoadele acestei emisiuni vor fi adăugate în curând.</p>
		<?php endif; ?>

		<?php
		$frati = $parinte ? array_filter(
			aptv_subcategorii_cu_continut( $parinte ),
			function ( $t ) use ( $categorie ) {
				return $t->term_id !== $categorie->term_id;
			}
		) : array();
		?>
		<?php if ( $frati ) : ?>
			<section class="aptv-alte" aria-labelledby="aptv-alte">
				<h2 id="aptv-alte">Alte emisiuni<?php echo 'tema' === aptv_tip_categorie( $parinte ) ? ' de ' . esc_html( mb_strtolower( $parinte->name ) ) : ''; ?></h2>
				<div class="aptv-file">
					<?php foreach ( $frati as $f ) : ?>
						<a href="<?php echo esc_url( get_term_link( $f ) ); ?>"><?php echo esc_html( $f->name ); ?></a>
					<?php endforeach; ?>
					<a class="aptv-file__toate" href="<?php echo esc_url( get_term_link( $parinte ) ); ?>">Toate ›</a>
				</div>
			</section>
		<?php endif; ?>
	</div>

<?php elseif ( $sectiune && 'stiri' === $sectiune->slug ) : ?>
	<?php /* ---------------- Știri (toate sau un tip anume) ---------------- */ ?>
	<div class="aptv-container aptv-sectiune">
		<?php aptv_unde_va_aflati( $categorie ); ?>
		<h1 class="aptv-titlu-pagina aptv-titlu-banda">Știri</h1>
		<p class="aptv-sectiune__intro">Alegeți tipul de știri. Apăsați pe o știre ca să o urmăriți.</p>
		<?php $butoane( $sectiune, $categorie, 'Toate știrile' ); ?>
		<?php if ( 'stiri' === $tip && $categorie->description ) : ?>
			<p class="aptv-sectiune__intro"><?php echo esc_html( $categorie->description ); ?></p>
		<?php endif; ?>
		<?php if ( have_posts() ) : ?>
			<div class="aptv-grila aptv-grila--stiri">
				<?php
				while ( have_posts() ) :
					the_post();
					aptv_card( get_post(), get_post_meta( get_the_ID(), '_aptv_scurt', true ) ? 'lista-scurt' : 'lista' );
				endwhile;
				?>
			</div>
			<?php
			the_posts_pagination(
				array(
					'prev_text'          => '← Mai noi',
					'next_text'          => 'Mai vechi →',
					'screen_reader_text' => 'Mai multe știri',
				)
			);
			?>
		<?php else : ?>
			<p class="aptv-gol">Știrile din această categorie vor fi adăugate în curând.</p>
		<?php endif; ?>
	</div>

<?php elseif ( 'portofoliu' === $tip ) : ?>
	<?php /* ---------------- Portofoliu: servicii + proiecte ---------------- */ ?>
	<section class="aptv-banda aptv-c" style="<?php echo esc_attr( aptv_stil_culoare( $categorie ) ); ?>">
		<div class="aptv-container">
			<?php aptv_unde_va_aflati( $categorie ); ?>
			<div class="aptv-banda__in">
				<div class="aptv-banda__text">
					<span class="aptv-banda__supra">SERVICII VIDEO APOLLONIA TV</span>
					<h1><?php echo esc_html( $categorie->name ); ?></h1>
					<p><?php echo esc_html( aptv_setare( 'portofoliu_intro' ) ); ?></p>
				</div>
				<a class="aptv-buton aptv-buton--galben aptv-buton--mare" href="#aptv-oferta-titlu">Cereți o ofertă</a>
			</div>
		</div>
	</section>

	<div class="aptv-container aptv-sectiune">
		<?php $servicii = aptv_servicii(); ?>
		<?php if ( $servicii ) : ?>
			<section aria-labelledby="aptv-servicii">
				<h2 id="aptv-servicii" class="aptv-titlu-banda">Ce vă putem oferi</h2>
				<ul class="aptv-servicii">
					<?php foreach ( $servicii as $serviciu ) : ?>
						<li class="aptv-serviciu">
							<span class="aptv-serviciu__icon"><?php echo aptv_icon_serviciu( $serviciu[0] ); // phpcs:ignore -- SVG fix. ?></span>
							<h3><?php echo esc_html( $serviciu[0] ); ?></h3>
							<?php if ( $serviciu[1] ) : ?>
								<p><?php echo esc_html( $serviciu[1] ); ?></p>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

		<section class="aptv-proiecte" aria-labelledby="aptv-proiecte">
			<h2 id="aptv-proiecte" class="aptv-titlu-banda">Proiecte realizate</h2>
			<?php if ( have_posts() ) : ?>
				<div class="aptv-grila aptv-grila--3">
					<?php
					while ( have_posts() ) :
						the_post();
						aptv_card( get_post(), 'normal' );
					endwhile;
					?>
				</div>
				<?php
				the_posts_pagination(
					array(
						'prev_text'          => '← Mai noi',
						'next_text'          => 'Mai vechi →',
						'screen_reader_text' => 'Mai multe proiecte',
					)
				);
				?>
			<?php else : ?>
				<p class="aptv-gol">Proiectele vor fi adăugate în curând.</p>
			<?php endif; ?>
		</section>

		<?php aptv_cerere_oferta(); ?>
	</div>

<?php else : ?>
	<?php
	/* ---------------- Emisiuni / o temă / Universitate: carduri de emisiuni ---------------- */
	$e_emisiuni = $sectiune && 'emisiuni' === $sectiune->slug;
	?>
	<div class="aptv-container aptv-sectiune">
		<?php aptv_unde_va_aflati( $categorie ); ?>
		<h1 class="aptv-titlu-pagina aptv-titlu-banda"><?php echo esc_html( $e_emisiuni ? 'Emisiuni' : $categorie->name ); ?></h1>
		<p class="aptv-sectiune__intro">
			<?php echo $e_emisiuni ? 'Alegeți o temă. Apoi apăsați pe numele emisiunii ca să vedeți toate episoadele.' : esc_html( $categorie->description ); ?>
		</p>

		<?php if ( $e_emisiuni ) : ?>
			<?php $butoane( $sectiune, $categorie, 'Toate temele' ); ?>
		<?php endif; ?>

		<?php
		// Pe „Emisiuni”: toate temele, fiecare cu emisiunile ei. Altfel: emisiunile categoriei curente.
		$grupuri = ( 'sectiune' === $tip && $e_emisiuni ) ? aptv_subcategorii_cu_continut( $categorie ) : array( $categorie );
		foreach ( $grupuri as $grup ) :
			$emisiuni_grup = aptv_subcategorii_cu_continut( $grup );
			if ( ! $emisiuni_grup ) {
				continue;
			}
			?>
			<section class="aptv-grup">
				<?php if ( count( $grupuri ) > 1 ) : ?>
					<h2><a href="<?php echo esc_url( get_term_link( $grup ) ); ?>"><?php echo esc_html( $grup->name ); ?></a></h2>
				<?php elseif ( 'tema' === $tip ) : ?>
					<h2>Emisiuni de <?php echo esc_html( mb_strtolower( $grup->name ) ); ?></h2>
				<?php endif; ?>
				<div class="aptv-grila aptv-grila--3">
					<?php
					foreach ( $emisiuni_grup as $em ) {
						$card_emisiune( $em );
					}
					?>
				</div>
			</section>
		<?php endforeach; ?>
	</div>
<?php endif; ?>

</main>
<?php
get_footer();
