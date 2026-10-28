<?php
/**
 * MODUL: Titluri mai ușor de citit.
 *
 * CE FACE: titlurile scrise integral cu MAJUSCULE („CREIERUL SE RESETEAZĂ PSIHOLOGIC
 * DE ANUL NOU”) sunt afișate pe site în scriere normală („Creierul se resetează
 * psihologic de Anul Nou”). Textul cu majuscule se citește mai greu, mai ales de vârstnici.
 *
 * IMPORTANT: titlul salvat în WordPress NU se schimbă. Transformarea se face doar la
 * afișare, deci dezactivarea modulului (în functions.php) readuce titlurile originale.
 *
 * Se transformă doar bucățile de titlu scrise în întregime cu majuscule; un titlu deja
 * scris normal rămâne neatins. Prescurtările (OMS, ADHD...) și numele proprii din listele
 * de mai jos își păstrează forma. Un nume propriu lipsă se adaugă în aptv_cuvinte_pastrate().
 *
 * @package apolloniatv
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cuvinte care își păstrează forma exactă după transformare.
 */
function aptv_cuvinte_pastrate() {
	return array(
		// Prescurtări.
		'OMS', 'ONU', 'UE', 'SUA', 'UK', 'NATO', 'NASA', 'FDA', 'EMA', 'ANM', 'MIT', 'TV',
		'ADHD', 'HPV', 'HIV', 'SIDA', 'ADN', 'ARN', 'ARNm', 'COVID', 'COVID-19', 'CO2', 'CO₂',
		'AI', 'IA', 'IQ', 'IMC', 'BMI', 'LDL', 'HDL', 'RMN', 'ATI', 'UPU', 'SMURD', 'ISU',
		'ChatGPT', 'SpaceX', 'YouTube', 'TikTok', 'Facebook', 'Instagram', 'Google', 'Apple',
		// Locuri și nume proprii frecvente.
		'Iași', 'România', 'Moldova', 'București', 'Cluj', 'Europa', 'China', 'Japonia', 'India',
		'Rusia', 'Ucraina', 'Germania', 'Franța', 'Italia', 'Spania', 'Israel', 'Africa', 'America',
		'Statele Unite', 'Marea Britanie', 'Anul Nou', 'Crăciun', 'Crăciunul', 'Paște', 'Paștele',
		'Sfânta Parascheva', 'Mihai Eminescu', 'Apollonia', 'Alzheimer', 'Parkinson', 'Trump', 'Biden',
	);
}

/**
 * Transformă o bucată de text scrisă cu majuscule în scriere normală.
 */
function aptv_din_majuscule( $text ) {
	$litere = preg_replace( '/[^\p{L}]/u', '', $text );
	if ( mb_strlen( $litere ) < 6 || mb_strtoupper( $litere ) !== $litere ) {
		return $text; // Nu e scris (doar) cu majuscule: rămâne cum e.
	}
	$rezultat = mb_strtolower( $text );

	// Cuvintele păstrate își recapătă forma (comparat fără să țină cont de litere mari/mici).
	foreach ( aptv_cuvinte_pastrate() as $cuvant ) {
		$rezultat = preg_replace( '/(?<![\p{L}\p{N}])' . preg_quote( mb_strtolower( $cuvant ), '/' ) . '(?![\p{L}\p{N}])/u', $cuvant, $rezultat );
	}

	// Literă mare la început și după . ? !
	$rezultat = preg_replace_callback(
		'/(^|[.?!]\s+)(\p{Ll})/u',
		function ( $m ) {
			return $m[1] . mb_strtoupper( $m[2] );
		},
		ltrim( $rezultat )
	);
	return $rezultat;
}

/**
 * Aplică transformarea fiecărei bucăți dintr-un titlu („TITLU MARE · Subtitlu normal”).
 */
function aptv_titlu_lizibil( $titlu ) {
	$bucati = explode( ' · ', (string) $titlu );
	return implode( ' · ', array_map( 'aptv_din_majuscule', $bucati ) );
}

// Doar pe site (nu în admin), ca echipa să vadă și să editeze titlul original.
if ( ! is_admin() ) {
	add_filter( 'the_title', 'aptv_titlu_lizibil', 5 );
	add_filter(
		'document_title_parts',
		function ( $parti ) {
			if ( isset( $parti['title'] ) ) {
				$parti['title'] = aptv_titlu_lizibil( $parti['title'] );
			}
			return $parti;
		}
	);
}
