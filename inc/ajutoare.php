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
 */
function aptv_imagine( $post = null, $clasa = '' ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}
	if ( has_post_thumbnail( $post ) ) {
		return get_the_post_thumbnail( $post, 'aptv-card', array( 'class' => $clasa, 'alt' => '' ) );
	}
	$id = aptv_video_id( $post );
	if ( ! $id ) {
		return '';
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
function aptv_icon_play( $culoare = '#0E2235' ) {
	return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M8 5v14l11-7z" fill="' . esc_attr( $culoare ) . '"/></svg>';
}

/**
 * Cardul unui episod (imagine, durată, emisiune, titlu, dată).
 * Folosit pe prima pagină, în liste de știri și la „Urmează”.
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
	?>
	<a class="aptv-card aptv-card--<?php echo esc_attr( $varianta ); ?>" href="<?php echo esc_url( get_permalink( $post ) ); ?>">
		<span class="aptv-card__media">
			<?php echo aptv_imagine( $post, 'aptv-card__img' ); // phpcs:ignore WordPress.Security.EscapeOutput -- HTML generat de WordPress / escapat mai sus. ?>
			<span class="aptv-card__play"><?php echo aptv_icon_play(); // phpcs:ignore ?></span>
			<?php if ( $durata && 'scurt' !== $varianta ) : ?>
				<span class="aptv-card__durata"><?php echo esc_html( $durata ); ?></span>
			<?php endif; ?>
		</span>
		<span class="aptv-card__text">
			<?php if ( $categorie && 'scurt' !== $varianta ) : ?>
				<span class="aptv-card__eticheta"><?php echo esc_html( $categorie->name ); ?></span>
			<?php endif; ?>
			<span class="aptv-card__titlu"><?php echo esc_html( get_the_title( $post ) ); ?></span>
			<?php if ( 'scurt' !== $varianta ) : ?>
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
