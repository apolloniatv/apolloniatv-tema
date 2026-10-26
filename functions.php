<?php
/**
 * Apollonia TV — fișierul principal al temei copil.
 *
 * CE FACE: încarcă, pe rând, fiecare „modul” al site-ului din folderul inc/.
 * Fiecare modul face un singur lucru și are propriul fișier, ca să fie ușor de găsit.
 *
 * DACĂ CEVA NU MERGE: puteți dezactiva temporar un modul punând // în fața
 * liniei lui de mai jos. Restul site-ului continuă să funcționeze.
 *
 * Lista modulelor (pe scurt — detalii în DOCUMENTATIE.md):
 *   setari.php           – pagina „Apollonia TV” din admin (telefon, e-mail, rețele, cheia YouTube)
 *   structura.php        – creează categoriile site-ului (Știri, Emisiuni, Universitate) cu un clic
 *   recomandat.php       – bifa „Recomandat pe prima pagină” din ecranul de editare al episodului
 *   ordine-episoade.php  – episoadele unei emisiuni apar în ordine cronologică
 *   ajutoare.php         – funcții mici folosite de mai multe pagini (durată, imagine, culoare temă)
 *   video.php            – playerul YouTube se încarcă abia la clic (site mai rapid)
 *   antet.php            – bara de ajutor, butoanele A / A+ / A++ și meniul cu teme și emisiuni
 *   subsol.php           – subsolul site-ului
 *   episod.php           – găsește episodul anterior / următor și cele mai noi materiale
 *   import-youtube.php   – aduce episoadele din playlist-urile YouTube
 *
 * @package apolloniatv
 */

// Oprește execuția dacă fișierul e deschis direct din browser (măsură de siguranță standard WordPress).
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Versiunea temei — se folosește ca fișierele CSS/JS noi să nu rămână blocate în cache-ul vizitatorilor.
define( 'APTV_VERSIUNE', wp_get_theme()->get( 'Version' ) );
define( 'APTV_DIR', get_stylesheet_directory() );
define( 'APTV_URL', get_stylesheet_directory_uri() );

require APTV_DIR . '/inc/setari.php';
require APTV_DIR . '/inc/structura.php';
require APTV_DIR . '/inc/recomandat.php';
require APTV_DIR . '/inc/ordine-episoade.php';
require APTV_DIR . '/inc/ajutoare.php';
require APTV_DIR . '/inc/video.php';
require APTV_DIR . '/inc/antet.php';
require APTV_DIR . '/inc/subsol.php';
require APTV_DIR . '/inc/episod.php';
require APTV_DIR . '/inc/import-youtube.php';

/**
 * Încarcă stilurile și scripturile temei.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style( 'aptv-tema', APTV_URL . '/assets/css/tema.css', array( 'generate-style' ), APTV_VERSIUNE );
		wp_enqueue_script( 'aptv-tema', APTV_URL . '/assets/js/tema.js', array(), APTV_VERSIUNE, array( 'strategy' => 'defer', 'in_footer' => true ) );
	},
	20
);

/**
 * Setări de bază ale temei.
 */
add_action(
	'after_setup_theme',
	function () {
		// Imaginile reprezentative ale episoadelor (preluate din YouTube) sunt 16:9.
		add_theme_support( 'post-thumbnails' );
		add_image_size( 'aptv-card', 640, 360, true );
		load_child_theme_textdomain( 'apolloniatv', APTV_DIR . '/languages' );
	}
);

/**
 * Lățimea implicită a conținutului (aceeași ca în mock-up: 1240px).
 * Dacă cineva o schimbă din Aspect → Personalizare, valoarea de acolo are prioritate.
 */
add_filter(
	'generate_option_defaults',
	function ( $implicite ) {
		$implicite['container_width'] = 1240;
		return $implicite;
	}
);

/**
 * Pe paginile construite de noi (prima pagină, liste de emisiuni, episoade)
 * nu vrem bara laterală a GeneratePress — doar conținutul, pe toată lățimea.
 */
add_filter(
	'generate_sidebar_layout',
	function () {
		return 'no-sidebar';
	}
);
