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
 * Țările și orașele de mai jos primesc automat și forma cu „-ei” (Polonia → Poloniei).
 */
function aptv_cuvinte_pastrate() {
	static $lista = null;
	if ( null !== $lista ) {
		return $lista;
	}
	$prescurtari = array(
		'OMS', 'ONU', 'UE', 'SUA', 'UK', 'NATO', 'NASA', 'FDA', 'EMA', 'ANM', 'MIT', 'TV', 'CNAS', 'ANAF', 'BNR',
		'ADHD', 'HPV', 'HIV', 'SIDA', 'ADN', 'ARN', 'ARNm', 'COVID', 'COVID-19', 'CO2', 'CO₂', 'AVC', 'BAC',
		'AI', 'IA', 'IQ', 'IMC', 'BMI', 'LDL', 'HDL', 'RMN', 'ATI', 'UPU', 'SMURD', 'ISU', 'UNAGE',
		'ChatGPT', 'SpaceX', 'YouTube', 'TikTok', 'Facebook', 'Instagram', 'Google', 'Apple', 'Tivoli', 'Mar-a-Lago',
	);
	// Locuri. Cele terminate în „a” primesc și forma de genitiv („-ei”).
	$locuri = array(
		'România', 'Moldova', 'Europa', 'Africa', 'America', 'Asia', 'Australia', 'Antarctica', 'China', 'Japonia', 'India', 'Coreea',
		'Rusia', 'Ucraina', 'Germania', 'Franța', 'Italia', 'Spania', 'Portugalia', 'Polonia', 'Ungaria', 'Bulgaria', 'Serbia',
		'Grecia', 'Turcia', 'Austria', 'Elveția', 'Olanda', 'Belgia', 'Suedia', 'Norvegia', 'Finlanda', 'Danemarca', 'Irlanda',
		'Cehia', 'Slovacia', 'Croația', 'Slovenia', 'Albania', 'Bosnia', 'Lituania', 'Letonia', 'Estonia', 'Georgia', 'Armenia',
		'Siria', 'Arabia Saudită', 'Columbia', 'Argentina', 'Brazilia', 'Venezuela', 'Bolivia', 'Nigeria', 'Kenya', 'Indonezia',
		'Malaezia', 'Mongolia', 'Groenlanda', 'Islanda', 'Canada', 'Cuba', 'Roma', 'Moscova', 'Varșovia', 'Viena', 'Praga',
		'Dobrogea', 'Transilvania', 'Bucovina', 'Muntenia', 'Oltenia', 'Suceava', 'Constanța', 'Marea Neagră', 'Dunărea',
	);
	$fara_genitiv = array(
		'Iași', 'Iașul', 'Iașului', 'București', 'Bucureștiul', 'Bucureștiului', 'Cluj', 'Cluj-Napoca', 'Timișoara', 'Brașov', 'Bacău',
		'Bârlad', 'Vaslui', 'Botoșani', 'Neamț', 'Piatra Neamț', 'Galați', 'Brăila', 'Focșani', 'Pașcani', 'Hârlău', 'Sibiu', 'Oradea',
		'Craiova', 'Ploiești', 'Pitești', 'Cernavodă', 'Chișinău', 'Kiev', 'Paris', 'Londra', 'Berlin', 'Madrid', 'Bruxelles',
		'Washington', 'New York', 'Beijing', 'Tokyo', 'Ierusalim', 'Gaza', 'Israel', 'Iran', 'Irak', 'Egipt', 'Maroc', 'Liban',
		'Republica Moldova', 'Republicii Moldova', 'Peru', 'Chile', 'Mexic', 'Vietnam', 'Pakistan', 'Afganistan', 'Statele Unite', 'Marea Britanie', 'Regatul Unit', 'Vatican',
		'Copou', 'Carpați', 'Făgăraș', 'Munții Făgăraș', 'Bucegi', 'Delta Dunării',
		// Sărbători, oameni, boli cu nume propriu.
		'Anul Nou', 'Crăciun', 'Crăciunul', 'Paște', 'Paștele', 'Sfânta Parascheva', 'Sfântul Dumitru', 'Sfântul Andrei',
		'Mihai Eminescu', 'Apollonia', 'Alzheimer', 'Parkinson', 'Trump', 'Biden', 'Putin', 'Zelenski', 'Macron', 'Merz', 'Orban',
		'Vučić', 'Leon', 'Francisc',
	);
	$lista = array_merge( $prescurtari, $fara_genitiv );
	foreach ( $locuri as $loc ) {
		$lista[] = $loc;
		if ( 'a' === mb_substr( $loc, -1 ) && false === strpos( $loc, ' ' ) ) {
			$lista[] = mb_substr( $loc, 0, -1 ) . 'ei'; // Polonia → Poloniei, Franța → Franței
		}
	}
	return $lista;
}

/**
 * Numele proprii scrise corect în descrierea articolului (de obicei scrisă normal, nu cu majuscule):
 * cuvintele cu literă mare care NU stau la început de propoziție. Ex.: „... papa Leon al XIV-lea, în Peru.”
 *
 * @param int $post_id
 * @return string[] [ 'forma mică' => 'Forma corectă' ]
 */
function aptv_nume_din_descriere( $post_id ) {
	static $memorie = array();
	$post_id = (int) $post_id;
	if ( ! $post_id ) {
		return array();
	}
	if ( isset( $memorie[ $post_id ] ) ) {
		return $memorie[ $post_id ];
	}
	$text  = html_entity_decode( wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) ), ENT_QUOTES, 'UTF-8' );
	$nume  = array();
	// Un cuvânt cu majusculă precedat de literă mică/cifră/virgulă + spațiu = în mijlocul propoziției.
	if ( preg_match_all( '/(?<=[\p{Ll}\p{N},;:„"(]\s)(\p{Lu}[\p{L}\-]*)/u', $text, $m ) ) {
		foreach ( $m[1] as $cuvant ) {
			$litere = preg_replace( '/[^\p{L}]/u', '', $cuvant );
			// Sărim peste cuvintele scrise integral cu majuscule (titluri repetate), afară de numerele romane.
			if ( mb_strlen( $litere ) > 1 && mb_strtoupper( $litere ) === $litere && ! preg_match( '/^[IVXLC]+(-lea|-a)?$/u', $cuvant ) ) {
				continue;
			}
			$nume[ mb_strtolower( $cuvant ) ] = $cuvant;
		}
	}
	return $memorie[ $post_id ] = $nume;
}

/**
 * Transformă o bucată de text scrisă cu majuscule în scriere normală.
 *
 * @param string   $text
 * @param string[] $nume_extra Nume proprii învățate din descriere (vezi aptv_nume_din_descriere).
 */
function aptv_din_majuscule( $text, $nume_extra = array() ) {
	$litere = preg_replace( '/[^\p{L}]/u', '', $text );
	if ( mb_strlen( $litere ) < 6 || mb_strtoupper( $litere ) !== $litere ) {
		return $text; // Nu e scris (doar) cu majuscule: rămâne cum e.
	}
	// Diacriticele vechi cu sedilă (ş, ţ) devin cele corecte, cu virgulă (ș, ț).
	$rezultat = mb_strtolower( strtr( $text, array( 'Ş' => 'Ș', 'Ţ' => 'Ț', 'ş' => 'ș', 'ţ' => 'ț' ) ) );

	// Cuvintele păstrate își recapătă forma (comparat fără să țină cont de litere mari/mici).
	$cuvinte = array_merge( aptv_cuvinte_pastrate(), array_values( $nume_extra ) );
	usort(
		$cuvinte,
		function ( $a, $b ) {
			return mb_strlen( $b ) - mb_strlen( $a ); // întâi expresiile lungi („Piatra Neamț” înaintea lui „Neamț”)
		}
	);
	foreach ( $cuvinte as $cuvant ) {
		$rezultat = preg_replace( '/(?<![\p{L}\p{N}])' . preg_quote( mb_strtolower( $cuvant ), '/' ) . '(?![\p{L}\p{N}])/u', $cuvant, $rezultat );
	}

	// Numere romane: „al xiv-lea”, „secolul xx”, „carol i” → XIV-lea, XX, I.
	$rezultat = preg_replace_callback(
		'/(?<![\p{L}\p{N}])([ivxlc]+)(-lea|-a)(?![\p{L}])/u',
		function ( $m ) {
			return mb_strtoupper( $m[1] ) . $m[2];
		},
		$rezultat
	);
	$rezultat = preg_replace_callback(
		'/(?<![\p{L}\p{N}])(ii|iii|iv|vii|viii|ix|xi|xii|xiii|xiv|xv|xvi|xvii|xviii|xix|xx|xxi)(?![\p{L}\p{N}])/u',
		function ( $m ) {
			return mb_strtoupper( $m[1] );
		},
		$rezultat
	);

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
 *
 * @param string $titlu
 * @param int    $post_id Articolul (pentru numele proprii din descriere); 0 = fără.
 */
function aptv_titlu_lizibil( $titlu, $post_id = 0 ) {
	$titlu = (string) $titlu;
	if ( ! preg_match( '/\p{Lu}{4}/u', $titlu ) ) {
		return $titlu; // nicio bucată cu majuscule: nimic de făcut
	}
	$nume   = $post_id ? aptv_nume_din_descriere( $post_id ) : array();
	$bucati = explode( ' · ', $titlu );
	return implode(
		' · ',
		array_map(
			function ( $b ) use ( $nume ) {
				// Și propozițiile din aceeași bucată: „GUMĂ DE MESTECAT ÎMPOTRIVA HPV? Rezultate surprinzătoare”.
				$propozitii = preg_split( '/(?<=[.?!])\s+/u', $b );
				return implode(
					' ',
					array_map(
						function ( $p ) use ( $nume ) {
							return aptv_din_majuscule( $p, $nume );
						},
						$propozitii
					)
				);
			},
			$bucati
		)
	);
}

/**
 * Începutul repetat al titlurilor unei emisiuni, de obicei numele moderatorului
 * („Andreea Barbieru · Tineri dirijori...”, la aproape toate episoadele Mirajul Muzicii).
 * Repetat în fiecare titlu, ascunde subiectul. Se calculează o dată pe zi.
 *
 * @param WP_Term $emisiune
 * @return string Începutul (fără „ · ”) sau '' dacă nu se repetă la cel puțin 10 episoade și 30% din ele.
 */
function aptv_prefix_emisiune( $emisiune ) {
	$cheie   = 'aptv_prefix2_' . $emisiune->term_id; // „2”: regula s-a schimbat, nu folosim calculele vechi
	$memorat = get_transient( $cheie );
	if ( false !== $memorat ) {
		return (string) $memorat;
	}
	global $wpdb;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$titluri = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT p.post_title FROM {$wpdb->posts} p JOIN {$wpdb->term_relationships} r ON r.object_id = p.ID JOIN {$wpdb->term_taxonomy} t ON t.term_taxonomy_id = r.term_taxonomy_id
			 WHERE t.term_id = %d AND p.post_type = 'post' AND p.post_status = 'publish' LIMIT 300",
			$emisiune->term_id
		)
	);
	$prefix = '';
	if ( count( $titluri ) >= 5 ) {
		$numar = array();
		$forme = array();
		foreach ( $titluri as $t ) {
			$bucati = explode( ' · ', $t, 2 );
			if ( 2 !== count( $bucati ) || mb_strlen( $bucati[0] ) > 40 ) {
				continue;
			}
			$k             = strtolower( remove_accents( trim( $bucati[0] ) ) );
			$numar[ $k ]   = ( $numar[ $k ] ?? 0 ) + 1;
			$forme[ $k ][] = trim( $bucati[0] );
		}
		if ( $numar ) {
			arsort( $numar );
			$k = (string) array_key_first( $numar );
			// Repetat la cel puțin 10 episoade și la cel puțin 30% dintre ele: e un nume, nu un subiect.
			if ( $numar[ $k ] >= 10 && $numar[ $k ] >= 0.3 * count( $titluri ) ) {
				$prefix = $k; // forma fără diacritice; potrivirea se face tot fără diacritice
			}
		}
	}
	set_transient( $cheie, $prefix, DAY_IN_SECONDS );
	return $prefix;
}

/**
 * Scoate din titlu începutul repetat al emisiunii (vezi aptv_prefix_emisiune).
 */
function aptv_titlu_fara_prefix( $titlu, $post_id = 0 ) {
	if ( ! $post_id || false === strpos( (string) $titlu, ' · ' ) || 'post' !== get_post_type( $post_id ) ) {
		return $titlu;
	}
	$emisiune = function_exists( 'aptv_categoria_episodului' ) ? aptv_categoria_episodului( $post_id ) : null;
	if ( ! $emisiune || 'emisiune' !== aptv_tip_categorie( $emisiune ) ) {
		return $titlu;
	}
	$prefix = aptv_prefix_emisiune( $emisiune );
	if ( '' === $prefix ) {
		return $titlu;
	}
	$bucati = explode( ' · ', $titlu, 2 );
	return strtolower( remove_accents( trim( $bucati[0] ) ) ) === $prefix ? $bucati[1] : $titlu;
}

// Doar pe site (nu în admin), ca echipa să vadă și să editeze titlul original.
if ( ! is_admin() ) {
	add_filter( 'the_title', 'aptv_titlu_lizibil', 5, 2 );
	add_filter( 'the_title', 'aptv_titlu_fara_prefix', 6, 2 );
	add_filter(
		'document_title_parts',
		function ( $parti ) {
			if ( isset( $parti['title'] ) ) {
				$parti['title'] = aptv_titlu_lizibil( $parti['title'], is_singular() ? get_queried_object_id() : 0 );
			}
			return $parti;
		}
	);
}
