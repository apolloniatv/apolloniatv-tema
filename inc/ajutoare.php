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
			<?php if ( ! $e_scurt && 'lista' !== $varianta ) : ?>
				<span class="aptv-etichete">
					<?php echo aptv_eticheta( $categorie ); // phpcs:ignore -- escapat în funcție. ?>
					<?php if ( aptv_e_nou( $post ) ) : ?><span class="aptv-eticheta aptv-eticheta--nou">NOU</span><?php endif; ?>
				</span>
			<?php elseif ( 'lista' === $varianta && $categorie ) : ?>
				<span class="aptv-card__supra"><?php echo esc_html( $categorie->name ); ?></span>
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
