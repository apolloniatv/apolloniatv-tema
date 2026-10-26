<?php
/**
 * MODUL: Playerul video (încărcare la clic).
 *
 * DE CE: un player YouTube încărcat direct aduce în pagină mult cod de la YouTube
 * și încetinește site-ul. Aici, pe pagină apare doar imaginea filmului și un buton
 * mare „Pornește”. Abia la apăsare se încarcă playerul real, care pornește imediat.
 *
 * UNDE SE APLICĂ:
 *  1. Pe pagina episodului, deasupra textului (pentru episoadele importate).
 *  2. Oriunde cineva lipește un link YouTube în editor (blocul „YouTube” / „Încorporare”).
 *
 * Playerul folosește youtube-nocookie.com (varianta YouTube fără cookie-uri de urmărire
 * până la pornirea filmului).
 *
 * Partea care încarcă playerul la clic este în assets/js/tema.js (secțiunea „Player”).
 *
 * @package apolloniatv
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Codul HTML al playerului.
 *
 * @param string $video_id ID-ul YouTube.
 * @param string $titlu    Titlul filmului (pentru cititoarele de ecran).
 * @param bool   $scurt    true pentru clipurile verticale (Shorts).
 * @return string
 */
function aptv_player( $video_id, $titlu = '', $scurt = false ) {
	$video_id = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $video_id );
	if ( ! $video_id ) {
		return '';
	}
	// hqdefault există pentru orice videoclip (imaginea e decupată la 16:9 din CSS).
	$imagine = 'https://i.ytimg.com/vi/' . $video_id . '/hqdefault.jpg';
	ob_start();
	?>
	<div class="aptv-player<?php echo $scurt ? ' aptv-player--scurt' : ''; ?>" data-video="<?php echo esc_attr( $video_id ); ?>" data-titlu="<?php echo esc_attr( $titlu ); ?>">
		<img class="aptv-player__img" src="<?php echo esc_url( $imagine ); ?>" alt="" decoding="async">
		<button type="button" class="aptv-player__buton">
			<?php echo aptv_icon_play(); // phpcs:ignore ?>
			<span>Pornește</span>
			<span class="screen-reader-text">: <?php echo esc_html( $titlu ); ?></span>
		</button>
		<noscript><a href="<?php echo esc_url( 'https://www.youtube.com/watch?v=' . $video_id ); ?>">Urmăriți pe YouTube</a></noscript>
	</div>
	<?php
	return (string) ob_get_clean();
}

/**
 * Înlocuiește blocul YouTube din editor cu playerul nostru.
 */
add_filter(
	'render_block_core/embed',
	function ( $html, $bloc ) {
		$url = $bloc['attrs']['url'] ?? '';
		if ( ! $url || ! preg_match( '~(?:youtube\.com/(?:watch\?v=|shorts/|embed/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m ) ) {
			return $html;
		}
		return aptv_player( $m[1], get_the_title(), false !== strpos( $url, '/shorts/' ) );
	},
	10,
	2
);
