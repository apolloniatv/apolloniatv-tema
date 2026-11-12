<?php
/**
 * MODUL: Funcții ajutătoare.
 *
 * Funcții mici, folosite de mai multe pagini. Fiecare are o explicație deasupra.
 * Datele unui episod (salvate de importul YouTube, în câmpuri ascunse ale articolului):
 *   _aptv_video   = ID-ul videoclipului YouTube (ex. At09ChS29-I)
 *   _aptv_durata  = durata, în secunde
 *   _aptv_scurt   = 1 dacă e clip scurt (YouTube Shorts)
 *
 * @package apolloniatv
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ID-ul YouTube al episodului. Dacă articolul a fost scris de mână (nu importat),
 * îl caută în textul articolului (primul link YouTube).
 */
function aptv_video_id( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}
	$id = (string) get_post_meta( $post->ID, '_aptv_video', true );
	if ( $id ) {
		return $id;
	}
	if ( preg_match( '~(?:youtube\.com/(?:watch\?v=|shorts/|embed/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $post->post_content, $m ) ) {
		return $m[1];
	}
	return '';
}

/**
 * Durata în format de ceas: 49:23 sau 1:02:48. Șir gol dacă nu se știe.
 */
function aptv_durata( $post = null ) {
	$sec = (int) get_post_meta( get_post( $post )->ID ?? 0, '_aptv_durata', true );
	if ( $sec <= 0 ) {
		return '';
	}
	$h = intdiv( $sec, 3600 );
	$m = intdiv( $sec % 3600, 60 );
	$s = $sec % 60;
	return $h ? sprintf( '%d:%02d:%02d', $h, $m, $s ) : sprintf( '%d:%02d', $m, $s );
}

/**
 * Durata în cuvinte, pentru oameni: „49 de minute”, „sub 1 minut”.
 */
function aptv_durata_text( $post = null ) {
	$sec = (int) get_post_meta( get_post( $post )->ID ?? 0, '_aptv_durata', true );
	if ( $sec <= 0 ) {
		return '';
	}
	if ( $sec < 60 ) {
		return 'sub 1 minut';
	}
	return aptv_plural( (int) round( $sec / 60 ), 'minut', 'minute' );
}

/**
 * Pluralul corect în română: 1 episod, 2 episoade, 21 de episoade.
 */
function aptv_plural( $n, $singular, $plural ) {
	if ( 1 === $n ) {
		return '1 ' . $singular;
	}
	$de = ( 0 === $n || $n % 100 >= 20 ) ? ' de ' : ' ';
	return $n . $de . $plural;
}

/**
 * Imaginea episodului: imaginea reprezentativă, dacă există; altfel imaginea de pe YouTube.
 *
 * @param bool $mare true = imaginea de sus a primei pagini: se încarcă imediat (nu „la derulare”)
 *                   și la rezoluție mare (1280 px), cu revenire automată la cea obișnuită
 *                   dacă videoclipul nu are una mare (vezi assets/js/tema.js, „Imagini mari”).
 */
function aptv_imagine( $post = null, $clasa = '', $mare = false ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}
	if ( has_post_thumbnail( $post ) ) {
		return get_the_post_thumbnail( $post, $mare ? 'large' : 'aptv-card', array( 'class' => $clasa, 'alt' => '', 'loading' => $mare ? 'eager' : 'lazy' ) );
	}
	$id = aptv_video_id( $post );
	if ( ! $id ) {
		return '';
	}
	if ( $mare ) {
		return sprintf(
			'<img src="%s" data-aptv-rezerva="%s" alt="" class="%s" fetchpriority="high" decoding="async" width="1280" height="720">',
			esc_url( 'https://i.ytimg.com/vi/' . $id . '/maxresdefault.jpg' ),
			esc_url( 'https://i.ytimg.com/vi/' . $id . '/hqdefault.jpg' ),
			esc_attr( $clasa )
		);
	}
	return sprintf(
		'<img src="%s" alt="" class="%s" loading="lazy" decoding="async" width="480" height="360">',
		esc_url( 'https://i.ytimg.com/vi/' . $id . '/hqdefault.jpg' ),
		esc_attr( $clasa )
	);
}

/**
 * Pictograma „Play” (triunghi), folosită pe carduri și butoane.
 */
function aptv_icon_play( $culoare = '#FFFFFF' ) {
	return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 4v16l13-8z" fill="' . esc_attr( $culoare ) . '"/></svg>';
}

/**
 * Data de la care Apollonia TV emite doar online (ieșirea de pe cablu).
 * Episoadele mai vechi primesc eticheta „ARHIVĂ” pe pagina lor.
 */
const APTV_DATA_ONLINE = '2025-11-10';

/** Episodul e din arhiva de pe cablu? */
function aptv_e_arhiva( $post = null ) {
	return get_the_date( 'Y-m-d', $post ) < APTV_DATA_ONLINE;
}

/** Episodul a fost publicat în ultimele 7 zile? (primește eticheta „NOU”) */
function aptv_e_nou( $post = null ) {
	return ( time() - (int) get_post_time( 'U', true, $post ) ) < 7 * DAY_IN_SECONDS;
}

/**
 * Eticheta plină, în culoarea temei, cu numele emisiunii (ca etichetele de pe post).
 *
 * @param WP_Term|null $categorie
 * @param string       $text Text în loc de numele categoriei (opțional).
 */
function aptv_eticheta( $categorie, $text = '' ) {
	if ( ! $categorie ) {
		return '';
	}
	return sprintf(
		'<span class="aptv-eticheta aptv-c" style="%s">%s</span>',
		esc_attr( aptv_stil_culoare( $categorie ) ),
		esc_html( $text ? $text : $categorie->name )
	);
}

/**
 * Cardul unui episod: imaginea (cu butonul de redare și durata în colțul de jos),
 * eticheta emisiunii (+ „NOU”), titlul (maximum 3 rânduri) și data.
 * Butonul de redare stă în colț, ca să nu acopere textul scris pe imaginile de pe YouTube.
 *
 * @param WP_Post|int $post
 * @param string      $varianta 'normal' | 'scurt' (clip vertical) | 'lista' (rând orizontal)
 */
function aptv_card( $post, $varianta = 'normal' ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return;
	}
	$categorie = aptv_categoria_episodului( $post );
	$durata    = aptv_durata( $post );
	$e_scurt   = 'scurt' === $varianta;
	?>
	<a class="aptv-card aptv-card--<?php echo esc_attr( $varianta ); ?>" href="<?php echo esc_url( get_permalink( $post ) ); ?>">
		<span class="aptv-card__media">
			<?php echo aptv_imagine( $post, 'aptv-card__img' ); // phpcs:ignore WordPress.Security.EscapeOutput -- HTML generat de WordPress / escapat mai sus. ?>
			<span class="aptv-redare"><?php echo aptv_icon_play(); // phpcs:ignore ?><?php echo ( $durata && ! $e_scurt ) ? '<span>' . esc_html( $durata ) . '</span>' : ''; ?><span class="screen-reader-text">Pornește</span></span>
		</span>
		<span class="aptv-card__text">
			<?php if ( ! $e_scurt ) : // aceeași etichetă colorată în toate listele (nu text simplu în unele) ?>
				<span class="aptv-etichete">
					<?php echo aptv_eticheta( $categorie ); // phpcs:ignore -- escapat în funcție. ?>
					<?php if ( aptv_e_nou( $post ) ) : ?><span class="aptv-eticheta aptv-eticheta--nou">NOU</span><?php endif; ?>
				</span>
			<?php endif; ?>
			<span class="aptv-card__titlu"><?php echo esc_html( get_the_title( $post ) ); ?></span>
			<?php if ( ! $e_scurt ) : ?>
				<span class="aptv-card__data"><?php echo esc_html( get_the_date( 'j F Y', $post ) ); ?></span>
			<?php endif; ?>
		</span>
	</a>
	<?php
}

/**
 * Firul „Unde vă aflați”: Acasă › Emisiuni › Cultură › Stop cadru.
 *
 * @param WP_Term|null $categorie Categoria curentă.
 * @param string       $final     Textul ultimului element (ex. „Episodul 3”); gol = categoria e ultima.
 */
function aptv_unde_va_aflati( $categorie = null, $final = '' ) {
	$elemente = array( '<a href="' . esc_url( home_url( '/' ) ) . '">Acasă</a>' );
	if ( $categorie ) {
		$lant = array_reverse( get_ancestors( $categorie->term_id, 'category', 'taxonomy' ) );
		foreach ( $lant as $id ) {
			$t          = get_term( $id, 'category' );
			$elemente[] = '<a href="' . esc_url( get_term_link( $t ) ) . '">' . esc_html( $t->name ) . '</a>';
		}
		$elemente[] = $final
			? '<a href="' . esc_url( get_term_link( $categorie ) ) . '">' . esc_html( $categorie->name ) . '</a>'
			: '<strong aria-current="page">' . esc_html( $categorie->name ) . '</strong>';
	}
	if ( $final ) {
		$elemente[] = '<strong aria-current="page">' . esc_html( $final ) . '</strong>';
	}
	echo '<nav class="aptv-firul" aria-label="Unde vă aflați">' . implode( ' <span aria-hidden="true">›</span> ', $elemente ) . '</nav>'; // phpcs:ignore -- elementele sunt escapate mai sus.
}

/**
 * Este pagina curentă (sau articolul curent) din Portofoliu?
 */
function aptv_e_portofoliu() {
	if ( is_category() ) {
		return 'portofoliu' === aptv_tip_categorie( get_queried_object() );
	}
	if ( is_singular( 'post' ) ) {
		$c = aptv_categoria_episodului();
		return $c && 'portofoliu' === aptv_tip_categorie( $c );
	}
	return false;
}

/**
 * Pictograma unui serviciu din Portofoliu, aleasă după cuvintele din nume
 * (live, montaj, social, platou, podcast, promo...). Implicit: o cameră video.
 */
function aptv_icon_serviciu( $nume ) {
	$n     = strtolower( remove_accents( $nume ) );
	$forme = array(
		'live'      => '<circle cx="12" cy="12" r="2.5" fill="currentColor"/><path d="M7.8 7.8a6 6 0 0 0 0 8.4M16.2 7.8a6 6 0 0 1 0 8.4M4.9 4.9a10 10 0 0 0 0 14.2M19.1 4.9a10 10 0 0 1 0 14.2"/>',
		'direct'    => '<circle cx="12" cy="12" r="2.5" fill="currentColor"/><path d="M7.8 7.8a6 6 0 0 0 0 8.4M16.2 7.8a6 6 0 0 1 0 8.4M4.9 4.9a10 10 0 0 0 0 14.2M19.1 4.9a10 10 0 0 1 0 14.2"/>',
		'montaj'    => '<circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M20 4 8.1 15.9M14.5 14.5 20 20M8.1 8.1 12 12"/>',
		'social'    => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 13.5 6.8 4M15.4 6.5l-6.8 4"/>',
		'retele'    => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 13.5 6.8 4M15.4 6.5l-6.8 4"/>',
		'platou'    => '<rect x="3" y="4" width="18" height="12" rx="1"/><path d="M8 20h8M12 16v4"/>',
		'studio'    => '<rect x="3" y="4" width="18" height="12" rx="1"/><path d="M8 20h8M12 16v4"/>',
		'podcast'   => '<rect x="9" y="2" width="6" height="12" rx="3"/><path d="M5 10a7 7 0 0 0 14 0M12 17v5M8 22h8"/>',
		'promo'     => '<path d="M3 11v2a1 1 0 0 0 1 1h3l6 4V6L7 10H4a1 1 0 0 0-1 1zM17 8a5 5 0 0 1 0 8M20 5a9 9 0 0 1 0 14"/>',
		'publicit'  => '<path d="M3 11v2a1 1 0 0 0 1 1h3l6 4V6L7 10H4a1 1 0 0 0-1 1zM17 8a5 5 0 0 1 0 8M20 5a9 9 0 0 1 0 14"/>',
		'foto'      => '<path d="M4 7h3l2-3h6l2 3h3a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1z"/><circle cx="12" cy="13" r="4"/>',
	);
	$forma = '<rect x="2" y="6" width="14" height="12" rx="1"/><path d="m16 10 6-3v10l-6-3z"/>';
	foreach ( $forme as $cuvant => $f ) {
		if ( false !== strpos( $n, $cuvant ) ) {
			$forma = $f;
			break;
		}
	}
	return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $forma . '</svg>';
}

/**
 * Blocul „Aveți un eveniment?” cu datele de contact completate în Setări (Portofoliu și proiectele lui).
 *
 * @param string $titlu Titlul blocului.
 */
function aptv_cerere_oferta( $titlu = 'Aveți un eveniment? Spuneți-ne despre el.' ) {
	?>
	<section class="aptv-oferta" aria-labelledby="aptv-oferta-titlu">
		<div>
			<h2 id="aptv-oferta-titlu"><?php echo esc_html( $titlu ); ?></h2>
			<p><?php echo esc_html( aptv_telefon_link() ? aptv_indemn_contact() . ' sau scrieți-ne.' : 'Scrieți-ne pe e-mail.' ); ?> Vă răspundem cu o ofertă potrivită evenimentului dumneavoastră.</p>
		</div>
		<div class="aptv-oferta__butoane">
			<?php aptv_butoane_contact( 'Cerere de ofertă — servicii video', 'inchis' ); ?>
		</div>
	</section>
	<?php
}

/**
 * Este clipul vertical (9:16)? Doar știrile științifice sunt filmate vertical;
 * restul materialelor (știri, emisiuni, proiecte) sunt 16:9, chiar dacă sunt scurte.
 *
 * @param int|WP_Post|null $post
 */
function aptv_e_vertical( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return false;
	}
	$stiintifice = get_term_by( 'slug', 'stiintifice', 'category' );
	return $stiintifice && has_term( $stiintifice->term_id, 'category', $post );
}

/**
 * Paginarea listelor: butoane mari, „Pagina 2 din 5”, link înapoi și înainte.
 *
 * @param string $eticheta Numele listei pentru cititoarele de ecran (ex. „Pagini cu episoade”).
 * @param string $inapoi   Textul butonului spre pagina anterioară.
 * @param string $inainte  Textul butonului spre pagina următoare.
 */
function aptv_paginare( $eticheta = 'Alte pagini', $inapoi = '← Înapoi', $inainte = 'Mai departe →' ) {
	global $wp_query;
	$total = (int) $wp_query->max_num_pages;
	if ( $total < 2 ) {
		return;
	}
	$curenta = max( 1, (int) get_query_var( 'paged' ) );
	$linkuri = paginate_links(
		array(
			'total'     => $total,
			'current'   => $curenta,
			'mid_size'  => 1,
			'end_size'  => 1,
			'prev_text' => $inapoi,
			'next_text' => $inainte,
			'type'      => 'array',
		)
	);
	if ( ! $linkuri ) {
		return;
	}
	?>
	<nav class="aptv-paginare" aria-label="<?php echo esc_attr( $eticheta ); ?>">
		<p class="aptv-paginare__stare">Pagina <?php echo (int) $curenta; ?> din <?php echo (int) $total; ?></p>
		<ul class="aptv-paginare__lista">
			<?php foreach ( $linkuri as $link ) : ?>
				<li><?php echo $link; // phpcs:ignore -- generat de WordPress. ?></li>
			<?php endforeach; ?>
		</ul>
	</nav>
	<?php
}

/**
 * Imaginea unei emisiuni („afișul”): imaginea pusă de echipă la categorie (câmpul „Afiș”)
 * sau, dacă nu e pusă, imaginea celui mai nou episod.
 *
 * @param WP_Term $emisiune
 * @param string  $clasa    Clasa CSS a imaginii.
 * @return string HTML (gol dacă emisiunea nu are niciun episod cu video).
 */
function aptv_afis_emisiune( $emisiune, $clasa = '' ) {
	$url = (string) get_term_meta( $emisiune->term_id, 'aptv_afis', true );
	if ( ! $url ) {
		$cache = 'aptv_afis_' . $emisiune->term_id;
		$url   = get_transient( $cache );
		if ( false === $url ) {
			$ultim = get_posts(
				array(
					'cat'            => $emisiune->term_id,
					'posts_per_page' => 1,
					'no_found_rows'  => true,
					'fields'         => 'ids',
					'meta_key'       => '_aptv_video', // phpcs:ignore WordPress.DB.SlowDBQuery
				)
			);
			$video = $ultim ? preg_replace( '/[^A-Za-z0-9_-]/', '', (string) get_post_meta( $ultim[0], '_aptv_video', true ) ) : '';
			// mqdefault: 320 × 180, 16:9, există pentru orice videoclip.
			$url = $video ? 'https://i.ytimg.com/vi/' . $video . '/mqdefault.jpg' : '';
			set_transient( $cache, $url, DAY_IN_SECONDS );
		}
	}
	if ( ! $url ) {
		return '';
	}
	return '<img class="' . esc_attr( $clasa ) . '" src="' . esc_url( $url ) . '" alt="" loading="lazy" decoding="async">';
}

/**
 * Categoriile (emisiuni, teme, tipuri de știri) potrivite cu textul căutat. Compară fără diacritice
 * și după rădăcina cuvântului (primele 5 litere): „muzica” → „Mirajul Muzicii”.
 *
 * @param string $cautat
 * @return WP_Term[] Maximum 6.
 */
function aptv_categorii_potrivite( $cautat ) {
	$norm     = function ( $t ) {
		return strtolower( remove_accents( (string) $t ) );
	};
	$cuvinte  = array_filter(
		preg_split( '/[^a-z0-9]+/', $norm( $cautat ) ),
		function ( $c ) {
			return strlen( $c ) >= 4;
		}
	);
	if ( ! $cuvinte ) {
		return array();
	}
	$potrivite = array();
	foreach ( get_terms( array( 'taxonomy' => 'category', 'hide_empty' => true ) ) as $t ) {
		if ( is_wp_error( $t ) || (int) get_option( 'default_category' ) === (int) $t->term_id ) {
			continue;
		}
		$nume = preg_split( '/[^a-z0-9]+/', $norm( $t->name ) );
		foreach ( $cuvinte as $c ) {
			$radacina = substr( $c, 0, 5 );
			foreach ( $nume as $n ) {
				if ( strlen( $n ) >= 4 && 0 === strpos( $n, $radacina ) ) {
					$potrivite[ $t->term_id ] = $t;
					break 2;
				}
			}
		}
	}
	return array_slice( array_values( $potrivite ), 0, 6 );
}
