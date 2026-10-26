<?php
/**
 * MODUL: Antetul site-ului (partea de sus a fiecărei pagini).
 *
 * CE CONȚINE, de sus în jos:
 *  1. Bara albastră de ajutor: telefonul (din Apollonia TV → Setări) și butoanele
 *     A / A+ / A++ pentru mărimea textului.
 *  2. Logo-ul și căutarea.
 *  3. Meniul principal: Acasă · Știri · Emisiuni · Universitate · Contact.
 *     „Știri” și „Emisiuni” deschid, la clic, un panou cu temele și emisiunile.
 *
 * PANOURILE SE CONSTRUIESC SINGURE din categorii: o emisiune nouă adăugată la
 * Articole → Categorii apare automat în meniu. Nu se editează meniul de mână.
 *
 * Antetul standard GeneratePress este dezactivat și înlocuit cu acesta.
 * Partea interactivă (deschiderea panourilor, mărimea textului) e în assets/js/tema.js.
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
 * Antetul propriu-zis.
 */
add_action(
	'generate_header',
	function () {
		$stiri        = get_term_by( 'slug', 'stiri', 'category' );
		$emisiuni     = get_term_by( 'slug', 'emisiuni', 'category' );
		$universitate = get_term_by( 'slug', 'universitate', 'category' );
		$sectiune_cur = null;
		if ( is_category() ) {
			$sectiune_cur = aptv_sectiune( get_queried_object() );
		} elseif ( is_single() && aptv_categoria_episodului() ) {
			$sectiune_cur = aptv_sectiune( aptv_categoria_episodului() );
		}
		$e_curent = function ( $termen ) use ( $sectiune_cur ) {
			return $termen && $sectiune_cur && $termen->term_id === $sectiune_cur->term_id;
		};
		?>
		<div class="aptv-bara-ajutor">
			<div class="aptv-container aptv-bara-ajutor__in">
				<p class="aptv-bara-ajutor__tel">
					Aveți nevoie de ajutor? Sunați-ne la
					<a href="<?php echo esc_attr( aptv_telefon_link() ); ?>"><?php echo esc_html( aptv_setare( 'telefon' ) ); ?></a>
					<span class="aptv-bara-ajutor__program">(<?php echo esc_html( aptv_setare( 'program' ) ); ?>)</span>
				</p>
				<div class="aptv-marime" role="group" aria-label="Mărimea textului">
					<span aria-hidden="true">Mărimea textului:</span>
					<button type="button" data-marime="normal" aria-label="Text normal">A</button>
					<button type="button" data-marime="mare" aria-label="Text mare">A+</button>
					<button type="button" data-marime="foarte-mare" aria-label="Text foarte mare">A++</button>
				</div>
			</div>
		</div>

		<header class="aptv-antet" id="masthead">
			<div class="aptv-container aptv-antet__sus">
				<a class="aptv-antet__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" aria-label="Apollonia TV — prima pagină">
					<?php echo aptv_logo(); // phpcs:ignore ?>
				</a>
				<form class="aptv-cautare" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
					<label for="aptv-cauta" class="screen-reader-text">Căutați pe site</label>
					<input id="aptv-cauta" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="Căutați o emisiune sau un subiect…">
					<button type="submit">Caută</button>
				</form>
			</div>

			<nav class="aptv-container aptv-meniu" aria-label="Meniu principal">
				<a class="aptv-meniu__link<?php echo is_front_page() ? ' is-curent' : ''; ?>" href="<?php echo esc_url( home_url( '/' ) ); ?>"<?php echo is_front_page() ? ' aria-current="page"' : ''; ?>>Acasă</a>
				<?php if ( $stiri ) : ?>
					<a class="aptv-meniu__link aptv-meniu__deschide<?php echo $e_curent( $stiri ) ? ' is-curent' : ''; ?>" href="<?php echo esc_url( get_term_link( $stiri ) ); ?>" data-panou="aptv-panou-stiri" aria-controls="aptv-panou-stiri" aria-expanded="false">Știri <span aria-hidden="true">▾</span></a>
				<?php endif; ?>
				<?php if ( $emisiuni ) : ?>
					<a class="aptv-meniu__link aptv-meniu__deschide<?php echo $e_curent( $emisiuni ) ? ' is-curent' : ''; ?>" href="<?php echo esc_url( get_term_link( $emisiuni ) ); ?>" data-panou="aptv-panou-emisiuni" aria-controls="aptv-panou-emisiuni" aria-expanded="false">Emisiuni <span aria-hidden="true">▾</span></a>
				<?php endif; ?>
				<?php if ( $universitate ) : ?>
					<a class="aptv-meniu__link<?php echo $e_curent( $universitate ) ? ' is-curent' : ''; ?>" href="<?php echo esc_url( get_term_link( $universitate ) ); ?>">Universitate</a>
				<?php endif; ?>
				<a class="aptv-meniu__link" href="#contact">Contact</a>
			</nav>

			<?php if ( $emisiuni ) : ?>
				<div class="aptv-panou" id="aptv-panou-emisiuni" hidden>
					<div class="aptv-container">
						<p class="aptv-panou__indemn">Alegeți o temă, apoi emisiunea dorită:</p>
						<div class="aptv-panou__grila">
							<?php foreach ( aptv_subcategorii( $emisiuni ) as $tema ) : ?>
								<?php list( $fundal ) = aptv_culoare( $tema ); ?>
								<div class="aptv-panou__tema">
									<a class="aptv-panou__titlu" href="<?php echo esc_url( get_term_link( $tema ) ); ?>">
										<span class="aptv-punct" style="background:<?php echo esc_attr( $fundal ); ?>"></span><?php echo esc_html( $tema->name ); ?>
									</a>
									<ul>
										<?php foreach ( aptv_subcategorii( $tema ) as $emisiune ) : ?>
											<li><a href="<?php echo esc_url( get_term_link( $emisiune ) ); ?>"><?php echo esc_html( $emisiune->name ); ?></a></li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endforeach; ?>
						</div>
						<a class="aptv-panou__toate" href="<?php echo esc_url( get_term_link( $emisiuni ) ); ?>">Toate emisiunile →</a>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $stiri ) : ?>
				<div class="aptv-panou" id="aptv-panou-stiri" hidden>
					<div class="aptv-container aptv-panou__butoane">
						<?php foreach ( aptv_subcategorii( $stiri ) as $tip ) : ?>
							<a href="<?php echo esc_url( get_term_link( $tip ) ); ?>"><?php echo esc_html( $tip->name ); ?></a>
						<?php endforeach; ?>
						<a href="<?php echo esc_url( get_term_link( $stiri ) ); ?>">Toate știrile</a>
					</div>
				</div>
			<?php endif; ?>
		</header>
		<?php
	}
);
