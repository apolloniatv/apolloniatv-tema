<?php
/**
 * MODUL: Structura site-ului (categoriile).
 *
 * CUM E ORGANIZAT SITE-UL — totul se bazează pe categoriile WordPress, pe 3 niveluri:
 *
 *   Secțiune (nivel 1)   →  Temă (nivel 2)          →  Emisiune (nivel 3)
 *   Emisiuni             →  Cultură                 →  Stop cadru
 *   Știri                →  Locale                     (știrile nu au nivel 3)
 *   Universitate         →  Universitaria              (aici nivelul 2 e direct emisiunea)
 *
 * Un episod = un articol WordPress, pus în categoria emisiunii lui (ex. „Stop cadru”).
 *
 * CE FACE ACEST FIȘIER:
 *  1. Conține lista completă a categoriilor (mai jos, în aptv_structura()).
 *  2. Adaugă pagina „Apollonia TV → Structură” cu un buton care creează categoriile lipsă.
 *     Butonul poate fi apăsat oricând: nu dublează și nu șterge nimic.
 *  3. Adaugă la fiecare categorie două câmpuri: „Playlist YouTube” și „Culoare”.
 *  4. Conține funcții care spun ce fel de categorie e una anume (secțiune, temă, emisiune).
 *
 * CA SĂ ADĂUGAȚI O EMISIUNE NOUĂ: Articole → Categorii → Adaugă, alegeți ca „Părinte” tema
 * potrivită (ex. Cultură). Apare automat în meniu și pe prima pagină. Nu e nevoie de cod.
 *
 * @package apolloniatv
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lista categoriilor de pornire. Folosită doar de butonul „Creează structura”.
 * Fiecare element: slug => [ nume, descriere, culoare (doar la teme), playlist YouTube, copii ].
 */
function aptv_structura() {
	return array(
		'stiri'        => array(
			'nume'     => 'Știri',
			'desc'     => 'Ce se întâmplă în Iași, în țară și în lume.',
			// Playlistul „Stiri” de pe canalul @apolloniatvcultural. Fiecare știre importată aici
			// e pusă automat la Locale, Naționale sau Externe (vezi aptv_ruteaza_stire în import-youtube.php).
			'playlist' => 'PLZiC4LBfhp6o',
			'auto'     => true,
			'copii'  => array(
				'externe'     => array( 'nume' => 'Externe', 'ordine' => 1, 'desc' => 'Ce se întâmplă în lume, pe scurt.' ),
				'nationale'   => array( 'nume' => 'Naționale', 'ordine' => 2, 'desc' => 'Știri din țară: sănătate, societate, viața de zi cu zi.' ),
				'locale'      => array( 'nume' => 'Locale', 'ordine' => 3, 'desc' => 'Știri din Iași și din Moldova.' ),
				// Toate încărcările canalului (UU...), doar clipurile scurte, importate automat zilnic.
				'stiintifice' => array( 'nume' => 'Științifice', 'ordine' => 4, 'desc' => 'Studii și descoperiri din medicină și știință, explicate în mai puțin de un minut.', 'playlist' => 'UUA5cYbtmQExq5QuYj3U8OlQ', 'filtru' => 'scurte', 'auto' => true ),
			),
		),
		'emisiuni'     => array(
			'nume'  => 'Emisiuni',
			'desc'  => 'Toate emisiunile Apollonia TV, grupate pe teme.',
			'copii' => array(
				'educatie'    => array(
					'nume'    => 'Educație', 'ordine' => 1,
					'desc'    => 'Școala, universitatea și lumea cărților.',
					'culoare' => '#26A3FC',
					'copii'   => array(
						'educatia-incotro' => array( 'nume' => 'Educația Încotro', 'desc' => 'Dialoguri despre educație, simpozioane și conferințe.', 'playlist' => 'PLPOwGDShVMnc-OLSm4KJugYzqJtWhYj3v' ),
						'colocviile-ecre'  => array( 'nume' => 'Colocviile ECRE', 'desc' => 'Lansări de carte și discuții cu autori, inclusiv de la târgul Gaudeamus.', 'playlist' => 'PLPOwGDShVMnd5mYj9PPLpxycbFv9LEbRS' ),
					),
				),
				'cultura'     => array(
					'nume'    => 'Cultură', 'ordine' => 2,
					'desc'    => 'Muzică, tradiții, spectacole și oameni deosebiți.',
					'culoare' => '#FC4F4C',
					'copii'   => array(
						'stop-cadru'          => array( 'nume' => 'Stop cadru', 'desc' => 'Reportaje despre tradiții, concerte și evenimente culturale din Moldova.', 'playlist' => 'PLPOwGDShVMnfH6pdWm7RP2El1MT15-pQd' ),
						'mirajul-muzicii'     => array( 'nume' => 'Mirajul Muzicii', 'desc' => 'Concerte, colinde și invitați din lumea muzicii ieșene.', 'playlist' => 'PLPOwGDShVMndZAbLcyRaMpbSKDxPf6p_O' ),
						'portrete-in-miscare' => array( 'nume' => 'Portrete în mișcare', 'desc' => 'Oameni și locuri: sportivi, meșteri și poveștile Bucovinei.', 'playlist' => 'PLPOwGDShVMncJqgAQ33fmZQ_O2EDRF8UZ' ),
						// Canalul @apolloniatvcultural.
						'scena'               => array( 'nume' => 'Scena', 'desc' => 'Teatru și dans: actori, regizori și profesori de teatru despre spectacole, festivaluri și școala de actorie.', 'playlist' => 'PL_SY0YzDw6YmWt_dcJ3O-38LrT0K1UM5v', 'auto' => true ),
					),
				),
				'sanatate'    => array(
					'nume'    => 'Sănătate', 'ordine' => 3,
					'desc'    => 'Medicii explică, pe înțelesul tuturor.',
					'culoare' => '#FFFF40',
					'copii'   => array(
						'repere-in-medicina'    => array( 'nume' => 'Repere în Medicină', 'desc' => '[Descrierea emisiunii — de completat]' ),
						// Clipurile lungi recente de pe canal. Verificați cu „Doar simulare” înainte de import.
						'interviuri-sanatate'   => array( 'nume' => 'Interviuri de sănătate', 'desc' => 'Specialiști care explică pe înțelesul tuturor: inimă, alimentație, sănătate mintală. [Nume provizoriu — de confirmat]', 'playlist' => 'UUA5cYbtmQExq5QuYj3U8OlQ', 'filtru' => 'lungi' ),
					),
				),
				'actualitate' => array(
					'nume'    => 'Actualitate și dezbateri', 'ordine' => 4,
					'desc'    => 'Analize despre securitate și evenimentele zilei.',
					'culoare' => '#14527F',
					'copii'   => array(
						'noaptea-generalilor' => array( 'nume' => 'Noaptea Generalilor', 'desc' => 'Generali și experți analizează conflicte, alianțe și securitatea regiunii.', 'playlist' => 'PLPOwGDShVMndFJoqFQEjTXSdVKxWwSqld' ),
						// Ediție Specială și Tribuna liberă vin de pe canalul @apolloniatvcultural.
						'editie-speciala'     => array( 'nume' => 'Ediție Specială', 'desc' => 'Interviuri cu invitați de seamă — diplomați, oameni politici și profesori — despre identitate, educație și viitor.', 'playlist' => 'PLTfUotFeqyeI', 'auto' => true ),
						'tribuna-libera'      => array( 'nume' => 'Tribuna liberă', 'desc' => 'Dezbateri despre Iași, educație, democrație și viitorul țării.', 'playlist' => 'PL_SY0YzDw6Ym_TX4pAmG0i9v0lITbUngO', 'auto' => true ),
					),
				),
			),
		),
		'universitate' => array(
			'nume'  => 'Universitate',
			'desc'  => 'Conferințe, congrese și întâlniri cu personalități ale științei și culturii, filmate la Universitatea „Apollonia” din Iași.',
			'copii' => array(
				'universitaria'           => array( 'nume' => 'Universitaria', 'desc' => 'Prelegeri și invitați de seamă ai universității.', 'playlist' => 'PLPOwGDShVMndxaQg4IUx-oexuvqJN3du_' ),
				'congresul-apollonia-2022' => array( 'nume' => 'Congresul Apollonia 2022', 'desc' => 'Comunicările congresului, pe secțiuni.', 'playlist' => 'PLPOwGDShVMnfTkwIvLDKApbXMKuB501sr' ),
			),
		),
		// Proiecte video făcute de Apollonia TV pentru alții + serviciile oferite (vezi category.php).
		'portofoliu'   => array(
			'nume' => 'Portofoliu',
			'desc' => 'Proiecte video realizate de Apollonia TV pentru instituții, companii și organizații.',
		),
	);
}

/**
 * Actualizări de structură pentru site-ul deja instalat. Rulează o singură dată
 * (la prima încărcare a panoului de administrare după actualizarea temei).
 *  - 2: canalul @apolloniatvcultural (Scena, Tribuna liberă, Ediție Specială, știrile),
 *       secțiunea Portofoliu și noile descrieri pentru Externe / Naționale / Locale.
 */
add_action(
	'admin_init',
	function () {
		if ( (int) get_option( 'aptv_versiune_structura', 1 ) >= 2 || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		aptv_creeaza_structura(); // doar categoriile care lipsesc (Scena, Tribuna liberă, Portofoliu)

		$setari = array(
			'stiri'           => array( 'playlist' => 'PLZiC4LBfhp6o', 'auto' => true ),
			'editie-speciala' => array( 'playlist' => 'PLTfUotFeqyeI', 'auto' => true ),
			'scena'           => array( 'auto' => true ),
			'tribuna-libera'  => array( 'auto' => true ),
		);
		foreach ( $setari as $slug => $info ) {
			$t = get_term_by( 'slug', $slug, 'category' );
			if ( ! $t ) {
				continue;
			}
			if ( ! empty( $info['playlist'] ) && ! get_term_meta( $t->term_id, 'aptv_playlist', true ) ) {
				update_term_meta( $t->term_id, 'aptv_playlist', $info['playlist'] );
			}
			if ( ! empty( $info['auto'] ) ) {
				update_term_meta( $t->term_id, 'aptv_auto', 1 );
			}
		}
		// Descrierile vechi („din arhiva Apollonia TV”) și cea provizorie de la Ediție Specială.
		$structura = aptv_structura();
		$noi       = array(
			'externe'         => $structura['stiri']['copii']['externe']['desc'],
			'nationale'       => $structura['stiri']['copii']['nationale']['desc'],
			'locale'          => $structura['stiri']['copii']['locale']['desc'],
			'editie-speciala' => $structura['emisiuni']['copii']['actualitate']['copii']['editie-speciala']['desc'],
		);
		foreach ( $noi as $slug => $desc ) {
			$t = get_term_by( 'slug', $slug, 'category' );
			if ( $t && ( false !== strpos( $t->description, 'din arhiva Apollonia TV' ) || false !== strpos( $t->description, 'de completat' ) ) ) {
				wp_update_term( $t->term_id, 'category', array( 'description' => $desc ) );
			}
		}
		update_option( 'aptv_versiune_structura', 2 );
	}
);

/**
 * Creează recursiv categoriile care lipsesc. Nu modifică și nu șterge categoriile existente.
 *
 * @return int Câte categorii noi au fost create.
 */
function aptv_creeaza_structura( $lista = null, $parinte = 0 ) {
	$create = 0;
	if ( null === $lista ) {
		$lista = aptv_structura();
	}
	foreach ( $lista as $slug => $info ) {
		$existent = get_term_by( 'slug', $slug, 'category' );
		if ( $existent ) {
			$id = (int) $existent->term_id;
		} else {
			$rez = wp_insert_term(
				$info['nume'],
				'category',
				array(
					'slug'        => $slug,
					'parent'      => $parinte,
					'description' => $info['desc'],
				)
			);
			if ( is_wp_error( $rez ) ) {
				continue;
			}
			$id = (int) $rez['term_id'];
			++$create;
			if ( ! empty( $info['culoare'] ) ) {
				update_term_meta( $id, 'aptv_culoare', $info['culoare'] );
			}
			if ( ! empty( $info['playlist'] ) ) {
				update_term_meta( $id, 'aptv_playlist', $info['playlist'] );
			}
			if ( ! empty( $info['filtru'] ) ) {
				update_term_meta( $id, 'aptv_filtru', $info['filtru'] );
			}
			if ( ! empty( $info['auto'] ) ) {
				update_term_meta( $id, 'aptv_auto', 1 );
			}
			if ( ! empty( $info['ordine'] ) ) {
				update_term_meta( $id, 'aptv_ordine', (int) $info['ordine'] );
			}
		}
		if ( ! empty( $info['copii'] ) ) {
			$create += aptv_creeaza_structura( $info['copii'], $id );
		}
	}
	return $create;
}

/* -------------------------------------------------------------------------
 * Funcții care spun ce fel de categorie este una anume.
 * ---------------------------------------------------------------------- */

/**
 * Întoarce categoria de nivel 1 (secțiunea) a unei categorii: „emisiuni”, „stiri” sau „universitate”.
 *
 * @param WP_Term|int $categorie
 * @return WP_Term|null
 */
function aptv_sectiune( $categorie ) {
	$categorie = get_term( $categorie, 'category' );
	if ( ! $categorie || is_wp_error( $categorie ) ) {
		return null;
	}
	$stramosi = get_ancestors( $categorie->term_id, 'category', 'taxonomy' );
	$varf     = $stramosi ? end( $stramosi ) : $categorie->term_id;
	return get_term( $varf, 'category' );
}

/**
 * Tipul categoriei: 'sectiune' | 'tema' | 'emisiune' | 'stiri' | 'portofoliu'.
 *  - sectiune: Știri, Emisiuni, Universitate
 *  - tema:     Educație, Cultură... (copiii lui Emisiuni)
 *  - emisiune: Stop cadru... (copiii unei teme) și copiii lui Universitate
 *  - stiri:    Externe, Naționale, Locale, Științifice
 *  - portofoliu: secțiunea Portofoliu (are o pagină proprie, cu serviciile oferite)
 */
function aptv_tip_categorie( $categorie ) {
	$categorie = get_term( $categorie, 'category' );
	if ( ! $categorie || is_wp_error( $categorie ) ) {
		return '';
	}
	$nivel    = count( get_ancestors( $categorie->term_id, 'category', 'taxonomy' ) ) + 1;
	$sectiune = aptv_sectiune( $categorie );
	$slug     = $sectiune ? $sectiune->slug : '';

	if ( 'portofoliu' === $slug ) {
		return 'portofoliu';
	}
	if ( 1 === $nivel ) {
		return 'sectiune';
	}
	if ( 'stiri' === $slug ) {
		return 'stiri';
	}
	if ( 'emisiuni' === $slug && 2 === $nivel ) {
		return 'tema';
	}
	return 'emisiune';
}

/**
 * Categoria „cea mai specifică” a unui episod (de obicei emisiunea lui).
 */
function aptv_categoria_episodului( $post = null ) {
	$categorii = get_the_category( $post );
	if ( ! $categorii ) {
		return null;
	}
	// Alegem categoria cea mai adâncă (cu cei mai mulți strămoși).
	usort(
		$categorii,
		function ( $a, $b ) {
			return count( get_ancestors( $b->term_id, 'category', 'taxonomy' ) ) <=> count( get_ancestors( $a->term_id, 'category', 'taxonomy' ) );
		}
	);
	return $categorii[0];
}

/**
 * Culoarea temei (Educație = albastru, Cultură = roșu...). Emisiunile moștenesc culoarea temei.
 * Întoarce [fundal, text] — textul e ales automat să fie lizibil pe fundal.
 */
function aptv_culoare( $categorie ) {
	$categorie = get_term( $categorie, 'category' );
	$fundal    = '';
	while ( $categorie && ! is_wp_error( $categorie ) ) {
		$fundal = get_term_meta( $categorie->term_id, 'aptv_culoare', true );
		if ( $fundal || ! $categorie->parent ) {
			break;
		}
		$categorie = get_term( $categorie->parent, 'category' );
	}
	if ( ! $fundal ) {
		// Fără culoare setată: Știri = negru, Universitate = gri închis, restul = albastru închis.
		$sectiune = $categorie && ! is_wp_error( $categorie ) ? aptv_sectiune( $categorie ) : null;
		$slug     = $sectiune ? $sectiune->slug : '';
		$fundal   = 'stiri' === $slug ? '#000000' : ( 'universitate' === $slug ? '#444444' : '#14527F' );
	}
	// Text alb pe culori închise, bleumarin pe culori deschise.
	$hex  = ltrim( $fundal, '#' );
	$r    = hexdec( substr( $hex, 0, 2 ) );
	$g    = hexdec( substr( $hex, 2, 2 ) );
	$b    = hexdec( substr( $hex, 4, 2 ) );
	$lum  = ( 0.299 * $r + 0.587 * $g + 0.114 * $b ) / 255;
	$text = $lum < 0.5 ? '#FFFFFF' : '#0E2235';
	return array( $fundal, $text );
}

/**
 * Stilul unui element colorat în culoarea temei (fundal + text lizibil).
 * Se pune pe elemente cu clasa „aptv-c” (vezi tema.css).
 */
function aptv_stil_culoare( $categorie ) {
	list( $fundal, $text ) = aptv_culoare( $categorie );
	return sprintf( '--c-tare:%s;--c-tare-text:%s', $fundal, $text );
}

/* -------------------------------------------------------------------------
 * Câmpurile „Playlist YouTube” și „Culoare” din ecranul de editare a categoriei.
 * ---------------------------------------------------------------------- */

add_action(
	'category_edit_form_fields',
	function ( $categorie ) {
		$playlist = get_term_meta( $categorie->term_id, 'aptv_playlist', true );
		$culoare  = get_term_meta( $categorie->term_id, 'aptv_culoare', true );
		wp_nonce_field( 'aptv_categorie', 'aptv_categorie_nonce' );
		?>
		<tr class="form-field">
			<th scope="row"><label for="aptv-playlist">Playlist YouTube</label></th>
			<td>
				<input id="aptv-playlist" name="aptv_playlist" type="text" value="<?php echo esc_attr( $playlist ); ?>">
				<p class="description">Pentru emisiuni (și pentru „Știri”, unde fiecare știre e pusă automat la Locale / Naționale / Externe). ID-ul playlist-ului (începe cu „PL”) sau linkul complet. Folosit de „Import YouTube”.</p>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row"><label for="aptv-ordine">Ordine în meniu</label></th>
			<td>
				<input id="aptv-ordine" name="aptv_ordine" type="number" min="0" step="1" style="width:6em" value="<?php echo esc_attr( get_term_meta( $categorie->term_id, 'aptv_ordine', true ) ); ?>">
				<p class="description">1 = prima. Lăsat gol = ordine alfabetică, după cele numerotate.</p>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row"><label for="aptv-culoare">Culoare</label></th>
			<td>
				<input id="aptv-culoare" name="aptv_culoare" type="text" value="<?php echo esc_attr( $culoare ); ?>" placeholder="#26A3FC">
				<p class="description">Doar pentru teme (Educație, Cultură...). Cod de culoare, ex. #26A3FC. Emisiunile preiau culoarea temei.</p>
			</td>
		</tr>
		<?php
	}
);

add_action(
	'edited_category',
	function ( $term_id ) {
		if ( ! isset( $_POST['aptv_categorie_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['aptv_categorie_nonce'] ), 'aptv_categorie' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_categories' ) ) {
			return;
		}
		if ( isset( $_POST['aptv_playlist'] ) ) {
			update_term_meta( $term_id, 'aptv_playlist', aptv_extrage_playlist( wp_unslash( $_POST['aptv_playlist'] ) ) );
		}
		if ( isset( $_POST['aptv_ordine'] ) ) {
			$ordine = absint( $_POST['aptv_ordine'] );
			if ( $ordine ) {
				update_term_meta( $term_id, 'aptv_ordine', $ordine );
			} else {
				delete_term_meta( $term_id, 'aptv_ordine' );
			}
		}
		if ( isset( $_POST['aptv_culoare'] ) ) {
			$culoare = sanitize_hex_color( wp_unslash( $_POST['aptv_culoare'] ) );
			if ( $culoare ) {
				update_term_meta( $term_id, 'aptv_culoare', $culoare );
			} else {
				delete_term_meta( $term_id, 'aptv_culoare' );
			}
		}
	}
);

/**
 * Acceptă fie ID-ul playlist-ului, fie un link YouTube, și întoarce doar ID-ul.
 */
function aptv_extrage_playlist( $text ) {
	$text = trim( (string) $text );
	if ( preg_match( '/[?&]list=([A-Za-z0-9_-]+)/', $text, $m ) ) {
		return $m[1];
	}
	return preg_replace( '/[^A-Za-z0-9_-]/', '', $text );
}

/* -------------------------------------------------------------------------
 * Pagina „Apollonia TV → Structură”.
 * ---------------------------------------------------------------------- */

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'aptv-setari', 'Structura site-ului', 'Structură', 'manage_options', 'aptv-structura', 'aptv_pagina_structura' );
	}
);

add_action(
	'admin_post_aptv_creeaza_structura',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Nu aveți drepturi pentru această acțiune.' );
		}
		check_admin_referer( 'aptv_structura' );
		$create = aptv_creeaza_structura();
		wp_safe_redirect( admin_url( 'admin.php?page=aptv-structura&create=' . $create ) );
		exit;
	}
);

function aptv_pagina_structura() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1>Structura site-ului</h1>
		<?php if ( isset( $_GET['create'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>Gata. Categorii noi create: <?php echo (int) $_GET['create']; ?>.</p></div>
		<?php endif; ?>
		<p>Butonul de mai jos creează categoriile site-ului (Știri, Emisiuni cu temele și emisiunile, Universitate).
		Poate fi apăsat oricând: <strong>nu dublează și nu șterge nimic</strong>, doar adaugă ce lipsește.</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="aptv_creeaza_structura">
			<?php wp_nonce_field( 'aptv_structura' ); ?>
			<?php submit_button( 'Creează structura', 'primary', 'submit', false ); ?>
		</form>
		<h2>Structura actuală</h2>
		<ul style="list-style:disc;padding-left:2em">
			<?php
			wp_list_categories(
				array(
					'title_li'   => '',
					'hide_empty' => false,
					'show_count' => true,
				)
			);
			?>
		</ul>
	</div>
	<?php
}
