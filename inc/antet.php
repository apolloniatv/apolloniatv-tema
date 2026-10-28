<?php
/**
 * MODUL: Antetul site-ului (partea de sus a fiecărei pagini) — direcția „TV” (v0.2).
 *
 * PE CALCULATOR, de sus în jos:
 *  1. Un rând alb: logo-ul, căutarea și butoanele de mărime a textului (A / A+ / A++).
 *  2. Bara de meniu albastră, ca bara de pe post: Acasă · Știri · Emisiuni · Universitate · Contact și ajutor.
 *     „Știri” și „Emisiuni” deschid, la clic, un panou cu temele și emisiunile.
 *
 * PE TELEFON: doar logo-ul și butonul „Meniu” (72 px înălțime, rămâne sus la derulare).
 * „Meniu” deschide un ecran întreg cu căutarea, toate secțiunile, mărimea textului și telefonul.
 *
 * TELEFONUL DE AJUTOR nu mai e în antet: apare în banda galbenă de jos a fiecărei pagini
 * (inc/subsol.php), în meniul de pe telefon și la „Contact și ajutor”.
 *
 * MENIUL SE CONSTRUIEȘTE SINGUR din categorii și ARATĂ DOAR CE ARE CONȚINUT:
 * o emisiune sau un tip de știri fără niciun episod publicat nu apare, ca să nu ducă
 * vizitatorul la o pagină goală. Apare automat după primul episod publicat.
 *
 * Partea interactivă (panouri, meniul de pe telefon, mărimea textului) e în assets/js/tema.js.
 *
 * @package apolloniatv
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dezactivează antetul și meniul GeneratePress (le înlocuim cu ale noastre).
 */
add_action(
	'after_setup_theme',
	function () {
		remove_action( 'generate_header', 'generate_construct_header' );
	},
	20
);
add_filter( 'generate_navigation_location', '__return_empty_string' );
add_filter( 'generate_mobile_header', '__return_false' );

/**
 * Aplică mărimea textului aleasă de vizitator ÎNAINTE să se afișeze pagina
 * (altfel pagina „ar sări” la încărcare). Alegerea e ținută în browserul vizitatorului.
 */
add_action(
	'wp_head',
	function () {
		echo "<script>try{var m=localStorage.getItem('aptv-text');if(m==='mare'||m==='foarte-mare'){document.documentElement.classList.add('aptv-text-'+m);}}catch(e){}</script>\n";
	},
	1
);

/**
 * Logo-ul: cel setat în Aspect → Personalizare → Identitate site, sau logo-ul inclus în temă.
 */
function aptv_logo( $clasa = 'aptv-logo' ) {
	$id = get_theme_mod( 'custom_logo' );
	if ( $id ) {
		return wp_get_attachment_image( $id, 'full', false, array( 'class' => $clasa, 'alt' => 'Apollonia TV' ) );
	}
	return '<img class="' . esc_attr( $clasa ) . '" src="' . esc_url( APTV_URL . '/assets/img/logo.png' ) . '" alt="Apollonia TV" width="941" height="145">';
}

/**
 * Copiii unei categorii (inclusiv cele încă fără episoade), în ordinea din câmpul
 * „Ordine în meniu” al categoriei; cele fără număr vin după, alfabetic.
 */
function aptv_subcategorii( $parinte ) {
	if ( ! $parinte ) {
		return array();
	}
	$copii = get_terms(
		array(
			'taxonomy'   => 'category',
			'parent'     => is_object( $parinte ) ? $parinte->term_id : (int) $parinte,
			'hide_empty' => false,
			'orderby'    => 'name',
		)
	);
	if ( is_wp_error( $copii ) ) {
		return array();
	}
	$rang = function ( $t ) {
		$o = (int) get_term_meta( $t->term_id, 'aptv_ordine', true );
		return $o > 0 ? $o : PHP_INT_MAX;
	};
	usort(
		$copii,
		function ( $a, $b ) use ( $rang ) {
			return array( $rang( $a ), $a->name ) <=> array( $rang( $b ), $b->name );
		}
	);
	return $copii;
}

/**
 * Numărul de articole publicate dintr-o categorie, INCLUSIV din subcategoriile ei.
 * (WordPress numără doar articolele puse direct în categorie; o temă ca „Cultură” ar avea 0.)
 */
function aptv_numar_total( $categorie ) {
	$categorie = get_term( $categorie, 'category' );
	if ( ! $categorie || is_wp_error( $categorie ) ) {
		return 0;
	}
	$total = (int) $categorie->count;
	foreach ( get_term_children( $categorie->term_id, 'category' ) as $id ) {
		$copil  = get_term( $id, 'category' );
		$total += $copil && ! is_wp_error( $copil ) ? (int) $copil->count : 0;
	}
	return $total;
}

/**
 * Ca aptv_subcategorii(), dar doar cele care au cel puțin un episod publicat.
 * Folosită în meniu și pe prima pagină, ca vizitatorul să nu ajungă la pagini goale.
 */
function aptv_subcategorii_cu_continut( $parinte ) {
	return array_values(
		array_filter(
			aptv_subcategorii( $parinte ),
			function ( $t ) {
				return aptv_numar_total( $t ) > 0;
			}
		)
	);
}

/**
 * Unde duce „Știri” din meniu: dacă un singur tip de știri are conținut
 * (de exemplu doar Științifice), direct acolo; altfel, la pagina tuturor știrilor.
 *
 * @return array [ link, lista tipurilor cu conținut ]
 */
function aptv_destinatie_stiri( $stiri ) {
	$tipuri = aptv_subcategorii_cu_continut( $stiri );
	if ( 1 === count( $tipuri ) ) {
		return array( get_term_link( $tipuri[0] ), $tipuri );
	}
	return array( get_term_link( $stiri ), $tipuri );
}

/**
 * Butoanele de mărime a textului (folosite în antet și în meniul de pe telefon).
 */
function aptv_butoane_marime( $eticheta_vizibila = 'TEXT' ) {
	?>
	<div class="aptv-marime" role="group" aria-label="Mărimea textului">
		<span class="aptv-marime__eticheta" aria-hidden="true"><?php echo esc_html( $eticheta_vizibila ); ?></span>
		<button type="button" data-marime="normal" aria-label="Text normal">A</button>
		<button type="button" data-marime="mare" aria-label="Text mare">A+</button>
		<button type="button" data-marime="foarte-mare" aria-label="Text foarte mare">A++</button>
	</div>
	<?php
}

/**
 * Formularul de căutare (antet și meniul de pe telefon). $id trebuie să fie unic pe pagină.
 */
function aptv_formular_cautare( $id ) {
	?>
	<form class="aptv-cautare" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
		<label for="<?php echo esc_attr( $id ); ?>" class="screen-reader-text">Căutați pe site</label>
		<input id="<?php echo esc_attr( $id ); ?>" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="Căutați o emisiune sau un subiect">
		<button type="submit">Caută</button>
	</form>
	<?php
}

/**
 * Antetul propriu-zis.
 */
add_action(
	'generate_header',
	function () {
		$stiri        = get_term_by( 'slug', 'stiri', 'category' );
		$emisiuni     = get_term_by( 'slug', 'emisiuni', 'category' );
		$universitate = get_term_by( 'slug', 'universitate', 'category' );

		list( $link_stiri, $tipuri_stiri ) = $stiri ? aptv_destinatie_stiri( $stiri ) : array( '', array() );
		$teme                              = $emisiuni ? aptv_subcategorii_cu_continut( $emisiuni ) : array();
		$panou_stiri                       = count( $tipuri_stiri ) > 1;

		$sectiune_cur = null;
		if ( is_category() ) {
			$sectiune_cur = aptv_sectiune( get_queried_object() );
		} elseif ( is_single() && aptv_categoria_episodului() ) {
			$sectiune_cur = aptv_sectiune( aptv_categoria_episodului() );
		}
		$curent = function ( $termen ) use ( $sectiune_cur ) {
			return $termen && $sectiune_cur && $termen->term_id === $sectiune_cur->term_id ? ' is-curent" aria-current="page' : '';
		};
		?>
		<header class="aptv-antet" id="masthead">
			<div class="aptv-container aptv-antet__sus">
				<a class="aptv-antet__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" aria-label="Apollonia TV — prima pagină">
					<?php echo aptv_logo(); // phpcs:ignore ?>
				</a>
				<div class="aptv-antet__unelte">
					<?php aptv_formular_cautare( 'aptv-cauta' ); ?>
					<?php aptv_butoane_marime(); ?>
				</div>
				<button type="button" class="aptv-meniu-buton" aria-controls="aptv-meniu-mobil" aria-expanded="false">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="square" aria-hidden="true" focusable="false"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
					Meniu
				</button>
			</div>

			<nav class="aptv-bara-meniu" aria-label="Meniu principal">
				<div class="aptv-container aptv-meniu">
					<a class="aptv-meniu__link<?php echo is_front_page() ? ' is-curent" aria-current="page' : ''; ?>" href="<?php echo esc_url( home_url( '/' ) ); ?>">Acasă</a>
					<?php if ( $stiri && $tipuri_stiri ) : ?>
						<a class="aptv-meniu__link<?php echo $panou_stiri ? ' aptv-meniu__deschide' : ''; ?><?php echo $curent( $stiri ); // phpcs:ignore ?>" href="<?php echo esc_url( $link_stiri ); ?>"<?php echo $panou_stiri ? ' data-panou="aptv-panou-stiri" aria-controls="aptv-panou-stiri" aria-expanded="false"' : ''; ?>>Știri<?php echo $panou_stiri ? ' <span aria-hidden="true">▾</span>' : ''; ?></a>
					<?php endif; ?>
					<?php if ( $emisiuni && $teme ) : ?>
						<a class="aptv-meniu__link aptv-meniu__deschide<?php echo $curent( $emisiuni ); // phpcs:ignore ?>" href="<?php echo esc_url( get_term_link( $emisiuni ) ); ?>" data-panou="aptv-panou-emisiuni" aria-controls="aptv-panou-emisiuni" aria-expanded="false">Emisiuni <span aria-hidden="true">▾</span></a>
					<?php endif; ?>
					<?php if ( $universitate && aptv_numar_total( $universitate ) ) : ?>
						<a class="aptv-meniu__link<?php echo $curent( $universitate ); // phpcs:ignore ?>" href="<?php echo esc_url( get_term_link( $universitate ) ); ?>">Universitate</a>
					<?php endif; ?>
					<a class="aptv-meniu__link aptv-meniu__contact" href="#contact">Contact și ajutor</a>
				</div>
			</nav>

			<?php if ( $teme ) : ?>
				<div class="aptv-panou" id="aptv-panou-emisiuni" hidden>
					<div class="aptv-container">
						<p class="aptv-panou__indemn">Alegeți o temă, apoi emisiunea dorită:</p>
						<div class="aptv-panou__grila">
							<?php foreach ( $teme as $tema ) : ?>
								<?php list( $fundal, $text ) = aptv_culoare( $tema ); ?>
								<div class="aptv-panou__tema">
									<a class="aptv-panou__titlu" href="<?php echo esc_url( get_term_link( $tema ) ); ?>" style="background:<?php echo esc_attr( $fundal ); ?>;color:<?php echo esc_attr( $text ); ?>"><?php echo esc_html( $tema->name ); ?></a>
									<ul>
										<?php foreach ( aptv_subcategorii_cu_continut( $tema ) as $emisiune ) : ?>
											<li><a href="<?php echo esc_url( get_term_link( $emisiune ) ); ?>"><?php echo esc_html( $emisiune->name ); ?></a></li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endforeach; ?>
						</div>
						<a class="aptv-panou__toate" href="<?php echo esc_url( get_term_link( $emisiuni ) ); ?>">Toate emisiunile ›</a>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $panou_stiri ) : ?>
				<div class="aptv-panou" id="aptv-panou-stiri" hidden>
					<div class="aptv-container aptv-panou__butoane">
						<?php foreach ( $tipuri_stiri as $tip ) : ?>
							<a href="<?php echo esc_url( get_term_link( $tip ) ); ?>"><?php echo esc_html( $tip->name ); ?></a>
						<?php endforeach; ?>
						<a href="<?php echo esc_url( get_term_link( $stiri ) ); ?>">Toate știrile</a>
					</div>
				</div>
			<?php endif; ?>
		</header>

		<?php /* ---------- Meniul de pe telefon: un ecran întreg ---------- */ ?>
		<div class="aptv-meniu-mobil" id="aptv-meniu-mobil" role="dialog" aria-modal="true" aria-label="Meniu" hidden>
			<div class="aptv-meniu-mobil__cap">
				<?php echo aptv_logo(); // phpcs:ignore ?>
				<button type="button" class="aptv-meniu-mobil__inchide">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true" focusable="false"><path d="M5 5l14 14M19 5L5 19"/></svg>
					Închide
				</button>
			</div>
			<div class="aptv-meniu-mobil__corp">
				<?php aptv_formular_cautare( 'aptv-cauta-mobil' ); ?>
				<nav aria-label="Meniu principal (telefon)">
					<ul class="aptv-meniu-mobil__lista">
						<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Acasă</a></li>
						<?php if ( $stiri && $tipuri_stiri ) : ?>
							<li><a href="<?php echo esc_url( $link_stiri ); ?>">Știri</a>
								<?php if ( $panou_stiri ) : ?>
									<ul>
										<?php foreach ( $tipuri_stiri as $tip ) : ?>
											<li><a href="<?php echo esc_url( get_term_link( $tip ) ); ?>"><?php echo esc_html( $tip->name ); ?></a></li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>
							</li>
						<?php endif; ?>
						<?php if ( $teme ) : ?>
							<li><a href="<?php echo esc_url( get_term_link( $emisiuni ) ); ?>">Emisiuni</a>
								<ul>
									<?php foreach ( $teme as $tema ) : ?>
										<li><a href="<?php echo esc_url( get_term_link( $tema ) ); ?>"><?php echo esc_html( $tema->name ); ?></a></li>
									<?php endforeach; ?>
								</ul>
							</li>
						<?php endif; ?>
						<?php if ( $universitate && aptv_numar_total( $universitate ) ) : ?>
							<li><a href="<?php echo esc_url( get_term_link( $universitate ) ); ?>">Universitate</a></li>
						<?php endif; ?>
						<li><a href="#contact" class="aptv-meniu-mobil__ancora">Contact și ajutor</a></li>
					</ul>
				</nav>
				<?php aptv_butoane_marime( 'Mărimea textului' ); ?>
				<a class="aptv-buton aptv-buton--galben aptv-buton--mare aptv-meniu-mobil__tel" href="<?php echo esc_attr( aptv_telefon_link() ); ?>">Aveți nevoie de ajutor? Sunați: <?php echo esc_html( aptv_setare( 'telefon' ) ); ?></a>
			</div>
		</div>
		<?php
	}
);
