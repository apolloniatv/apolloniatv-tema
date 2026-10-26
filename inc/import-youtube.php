<?php
/**
 * MODUL: Importul episoadelor din YouTube.
 *
 * CE FACE: citește un playlist YouTube și creează câte un articol WordPress pentru
 * fiecare videoclip: titlu, descriere, data difuzării, durata și playerul.
 * Articolul e pus în categoria aleasă (de obicei emisiunea).
 *
 * UNDE: Apollonia TV → Import YouTube. Funcționează și cu videoclipurile „Unlisted”
 * (nelistate), atâta timp cât playlist-ul în sine este public.
 *
 * E SIGUR SĂ FIE RULAT DE MAI MULTE ORI: videoclipurile deja importate sunt sărite
 * (se recunosc după ID-ul YouTube). Nu se modifică și nu se șterge nimic existent.
 *
 * SINCRONIZARE AUTOMATĂ: o categorie poate fi bifată „Importă automat zilnic”
 * (în Articole → Categorii → editare). O dată pe zi, site-ul verifică cele mai noi
 * 50 de videoclipuri din playlist-ul ei și le adaugă pe cele noi. Potrivit pentru
 * playlist-ul cu toate încărcările canalului (știrile științifice noi apar singure pe site).
 *
 * NECESITĂ: cheia YouTube Data API, salvată în Apollonia TV → Setări.
 *
 * CUM FUNCȚIONEAZĂ TEHNIC: importul merge în „pași” de câte 50 de videoclipuri
 * (limita YouTube), fiecare pas fiind o cerere separată, ca să nu depășească
 * limita de timp a serverului (30 de secunde). Pagina din admin pornește pașii
 * unul după altul și afișează progresul.
 *
 * @package apolloniatv
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Durata maximă (secunde) pentru ca un clip să fie considerat „scurt” (YouTube Shorts: până la 3 minute). */
const APTV_SCURT_MAX = 180;

/* -------------------------------------------------------------------------
 * Comunicarea cu YouTube.
 * ---------------------------------------------------------------------- */

/**
 * Cerere către YouTube Data API. Întoarce datele sau un WP_Error cu mesaj în română.
 */
function aptv_youtube_cerere( $resursa, $parametri ) {
	$cheie = aptv_cheie_youtube();
	if ( ! $cheie ) {
		return new WP_Error( 'aptv_fara_cheie', 'Lipsește cheia YouTube API (Apollonia TV → Setări).' );
	}
	$parametri['key'] = $cheie;
	$url              = add_query_arg( array_map( 'rawurlencode', $parametri ), 'https://www.googleapis.com/youtube/v3/' . $resursa );
	$raspuns          = wp_remote_get( $url, array( 'timeout' => 20 ) );
	if ( is_wp_error( $raspuns ) ) {
		return new WP_Error( 'aptv_retea', 'Serverul nu a putut contacta YouTube: ' . $raspuns->get_error_message() );
	}
	$date = json_decode( wp_remote_retrieve_body( $raspuns ), true );
	$cod  = wp_remote_retrieve_response_code( $raspuns );
	if ( 200 !== $cod ) {
		$mesaj = $date['error']['message'] ?? ( 'cod ' . $cod );
		return new WP_Error( 'aptv_youtube', 'YouTube a refuzat cererea: ' . wp_strip_all_tags( $mesaj ) );
	}
	return is_array( $date ) ? $date : new WP_Error( 'aptv_youtube', 'Răspuns YouTube neașteptat.' );
}

/**
 * Transformă durata YouTube (ex. „PT1H2M48S”) în secunde.
 */
function aptv_durata_secunde( $iso ) {
	if ( ! preg_match( '/^P(?:(\d+)D)?(?:T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?)?$/', (string) $iso, $m ) ) {
		return 0;
	}
	return ( (int) ( $m[1] ?? 0 ) ) * 86400 + ( (int) ( $m[2] ?? 0 ) ) * 3600 + ( (int) ( $m[3] ?? 0 ) ) * 60 + (int) ( $m[4] ?? 0 );
}

/**
 * Curăță titlul unui videoclip pentru site: scoate numele emisiunii (care apare deja
 * pe pagină), datele de tipul „26.11.2017” și etichetele „#shorts”.
 *
 * Exemplu: „Stop cadru | 26.11.2017 | Teodora Puianu | Descopera Bucovina”
 *       →  „Teodora Puianu · Descopera Bucovina”
 *
 * Dacă după curățare nu mai rămâne nimic, se păstrează titlul original.
 */
function aptv_curata_titlu( $titlu, $nume_emisiune = '' ) {
	$original = trim( $titlu );
	$norm     = function ( $s ) {
		return trim( preg_replace( '/\s+/u', ' ', strtolower( remove_accents( $s ) ) ) );
	};
	$emisiune = $norm( $nume_emisiune );

	// Etichetele #ceva.
	$titlu = preg_replace( '/(^|\s)#[\p{L}\p{N}_]+/u', '', $original );

	$bucati = preg_split( '/\s*\|\s*/u', $titlu );
	$pastra = array();
	foreach ( $bucati as $bucata ) {
		$bucata = trim( $bucata, " \t-–—,:" );
		if ( '' === $bucata ) {
			continue;
		}
		$n = $norm( $bucata );
		// Numele emisiunii singur (inclusiv variante gen „Stop cadrul”, „emisie”).
		if ( $emisiune && ( $n === $emisiune || $n === $emisiune . 'l' ) ) {
			continue;
		}
		if ( 'emisie' === $n ) {
			continue;
		}
		// Date: 26.11.2017, 18 12 2017.
		if ( preg_match( '/^\d{1,2}[.\s\/-]\d{1,2}[.\s\/-]\d{2,4}$/', $bucata ) ) {
			continue;
		}
		// Numele emisiunii la începutul bucății: „Colocviile ECRE, Lansare de carte” → „Lansare de carte”.
		if ( $emisiune && 0 === strpos( $n, $emisiune ) ) {
			$rest = trim( mb_substr( $bucata, mb_strlen( $nume_emisiune ) ), " \t-–—,:" );
			if ( '' === $rest ) {
				continue;
			}
			$bucata = $rest;
		}
		$pastra[] = $bucata;
	}
	$rezultat = trim( implode( ' · ', $pastra ) );
	return '' !== $rezultat ? $rezultat : $original;
}

/**
 * Data difuzării episodului, în ora GMT (cum o cere WordPress).
 *
 * Multe emisiuni vechi au data difuzării în titlu („Stop cadru | 26.11.2017 | ...”),
 * iar pe YouTube au fost încărcate câteva zile mai târziu. Dacă titlul conține o dată
 * validă, o folosim pe aceea (ora 20:00, ora României); altfel, data încărcării pe YouTube.
 */
function aptv_data_difuzare( $titlu, $data_youtube ) {
	if ( preg_match( '/(?<!\d)(\d{1,2})[.\s\/-](\d{1,2})[.\s\/-](\d{4})(?!\d)/', (string) $titlu, $m ) && checkdate( (int) $m[2], (int) $m[1], (int) $m[3] ) && (int) $m[3] >= 1990 ) {
		$locala = sprintf( '%04d-%02d-%02d 20:00:00', $m[3], $m[2], $m[1] );
		return get_gmt_from_date( $locala );
	}
	$moment = $data_youtube ? strtotime( $data_youtube ) : time();
	return gmdate( 'Y-m-d H:i:s', $moment ? $moment : time() );
}

/**
 * Adresa (slug-ul) articolului: doar litere fără diacritice, cifre și cratime.
 * Exemplu: „Stop cadru Teodora Puianu · Descoperă Bucovina” → stop-cadru-teodora-puianu-descopera-bucovina
 */
function aptv_slug( $text ) {
	$text = preg_replace( '/&#?[A-Za-z0-9]+;/', ' ', (string) $text ); // coduri HTML (ex. emoji codificate)
	$text = remove_accents( $text );
	$text = preg_replace( '/[^A-Za-z0-9\s-]+/', ' ', $text );
	return sanitize_title( mb_substr( trim( $text ), 0, 90 ) );
}

/**
 * Care dintre aceste ID-uri YouTube există deja pe site?
 *
 * @param string[] $ids
 * @return string[]
 */
function aptv_videouri_existente( $ids ) {
	global $wpdb;
	if ( ! $ids ) {
		return array();
	}
	$semne = implode( ',', array_fill( 0, count( $ids ), '%s' ) );
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $semne conține doar %s.
	return $wpdb->get_col( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_aptv_video' AND meta_value IN ($semne)", $ids ) );
}

/**
 * UN PAS de import: maximum 50 de videoclipuri dintr-un playlist.
 *
 * @param array $optiuni {
 *     @type int    $categorie  ID-ul categoriei în care se pun articolele.
 *     @type string $playlist   ID-ul playlist-ului.
 *     @type string $token      Pagina din playlist (gol = prima).
 *     @type string $filtru     'toate' | 'scurte' | 'lungi'.
 *     @type bool   $curata     Curăță titlurile.
 *     @type string $stare      'publish' | 'draft'.
 *     @type bool   $simulare   true = doar arată ce s-ar importa.
 * }
 * @return array|WP_Error [ 'create' => int, 'sarite' => int, 'urmator' => string, 'jurnal' => string[] ]
 */
function aptv_import_pas( $optiuni ) {
	$categorie = get_term( (int) $optiuni['categorie'], 'category' );
	if ( ! $categorie || is_wp_error( $categorie ) ) {
		return new WP_Error( 'aptv_categorie', 'Categoria aleasă nu există.' );
	}
	$parametri = array(
		'part'       => 'contentDetails,status',
		'playlistId' => $optiuni['playlist'],
		'maxResults' => 50,
	);
	if ( ! empty( $optiuni['token'] ) ) {
		$parametri['pageToken'] = $optiuni['token'];
	}
	$lista = aptv_youtube_cerere( 'playlistItems', $parametri );
	if ( is_wp_error( $lista ) ) {
		return $lista;
	}

	$rezultat = array(
		'create'  => 0,
		'sarite'  => 0,
		'urmator' => $lista['nextPageToken'] ?? '',
		'total'   => (int) ( $lista['pageInfo']['totalResults'] ?? 0 ),
		'jurnal'  => array(),
	);

	$ids = array();
	foreach ( $lista['items'] ?? array() as $element ) {
		$id = $element['contentDetails']['videoId'] ?? '';
		if ( $id ) {
			$ids[] = $id;
		}
	}
	if ( ! $ids ) {
		return $rezultat;
	}

	$existente = aptv_videouri_existente( $ids );
	$noi       = array_values( array_diff( $ids, $existente ) );
	$rezultat['sarite'] += count( $existente );

	if ( ! $noi ) {
		return $rezultat;
	}

	$detalii = aptv_youtube_cerere(
		'videos',
		array(
			'part' => 'snippet,contentDetails,status',
			'id'   => implode( ',', $noi ),
		)
	);
	if ( is_wp_error( $detalii ) ) {
		return $detalii;
	}

	foreach ( $detalii['items'] ?? array() as $video ) {
		$id      = $video['id'];
		$titlu   = $video['snippet']['title'] ?? '';
		$durata  = aptv_durata_secunde( $video['contentDetails']['duration'] ?? '' );
		$privat  = 'private' === ( $video['status']['privacyStatus'] ?? '' );
		$embed   = ! empty( $video['status']['embeddable'] );
		$e_scurt = $durata > 0 && $durata <= APTV_SCURT_MAX;

		if ( $privat ) {
			++$rezultat['sarite'];
			$rezultat['jurnal'][] = 'Sărit (privat): ' . $titlu;
			continue;
		}
		if ( ! $embed ) {
			++$rezultat['sarite'];
			$rezultat['jurnal'][] = 'Sărit (nu poate fi încorporat pe alte site-uri — se poate schimba din YouTube Studio): ' . $titlu;
			continue;
		}
		if ( ( 'scurte' === $optiuni['filtru'] && ! $e_scurt ) || ( 'lungi' === $optiuni['filtru'] && $e_scurt ) ) {
			++$rezultat['sarite'];
			continue;
		}

		$titlu_site = ! empty( $optiuni['curata'] ) ? aptv_curata_titlu( $titlu, $categorie->name ) : $titlu;
		$data_gmt   = aptv_data_difuzare( $titlu, $video['snippet']['publishedAt'] ?? '' );

		if ( ! empty( $optiuni['simulare'] ) ) {
			++$rezultat['create'];
			$rezultat['jurnal'][] = 'S-ar importa: ' . $titlu_site . ' (' . get_date_from_gmt( $data_gmt, 'j.m.Y' ) . ')';
			continue;
		}

		$descriere = trim( (string) ( $video['snippet']['description'] ?? '' ) );
		$descriere = preg_replace( '/(^|\s)#[\p{L}\p{N}_]+/u', '', $descriere );

		// Emoji (📍, 📢 ...) nu pot fi salvate direct în baza de date a serverului nostru
		// (setul de caractere „utf8”). Le transformăm în coduri HTML, care se afișează la fel.
		$titlu_site = wp_encode_emoji( $titlu_site );

		$post_id = wp_insert_post(
			array(
				'post_type'     => 'post',
				'post_status'   => 'draft' === $optiuni['stare'] ? 'draft' : 'publish',
				'post_title'    => $titlu_site,
				'post_name'     => aptv_slug( $categorie->name . ' ' . $titlu_site ),
				'post_content'  => $descriere ? wp_encode_emoji( wp_kses_post( wpautop( make_clickable( esc_html( $descriere ) ) ) ) ) : '',
				'post_date_gmt' => $data_gmt,
				'post_date'     => get_date_from_gmt( $data_gmt ),
				'post_category' => array( $categorie->term_id ),
				'post_author'   => get_current_user_id() ? get_current_user_id() : 1,
				'meta_input'    => array(
					'_aptv_video'       => $id,
					'_aptv_durata'      => $durata,
					'_aptv_scurt'       => $e_scurt ? 1 : 0,
					'_aptv_titlu_yt'    => wp_encode_emoji( $titlu ),
				),
			),
			true
		);
		if ( is_wp_error( $post_id ) ) {
			$rezultat['jurnal'][] = 'Eroare la „' . $titlu . '”: ' . $post_id->get_error_message();
			continue;
		}
		++$rezultat['create'];
		$rezultat['jurnal'][] = 'Importat: ' . $titlu_site;
	}

	return $rezultat;
}

/* -------------------------------------------------------------------------
 * Pagina „Apollonia TV → Import YouTube”.
 * ---------------------------------------------------------------------- */

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'aptv-setari', 'Import YouTube', 'Import YouTube', 'manage_options', 'aptv-import', 'aptv_pagina_import' );
	}
);

/**
 * Un pas de import cerut de pagina din admin (prin AJAX).
 */
add_action(
	'wp_ajax_aptv_import_pas',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'mesaj' => 'Nu aveți drepturi pentru această acțiune.' ), 403 );
		}
		check_ajax_referer( 'aptv_import', 'nonce' );

		$categorie = isset( $_POST['categorie'] ) ? absint( $_POST['categorie'] ) : 0;
		$playlist  = isset( $_POST['playlist'] ) ? aptv_extrage_playlist( wp_unslash( $_POST['playlist'] ) ) : '';
		if ( ! $playlist && $categorie ) {
			$playlist = (string) get_term_meta( $categorie, 'aptv_playlist', true );
		}
		if ( ! $categorie || ! $playlist ) {
			wp_send_json_error( array( 'mesaj' => 'Alegeți categoria și completați playlist-ul.' ) );
		}

		$filtru = isset( $_POST['filtru'] ) ? sanitize_key( wp_unslash( $_POST['filtru'] ) ) : 'toate';
		$rez    = aptv_import_pas(
			array(
				'categorie' => $categorie,
				'playlist'  => $playlist,
				'token'     => isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '',
				'filtru'    => in_array( $filtru, array( 'toate', 'scurte', 'lungi' ), true ) ? $filtru : 'toate',
				'curata'    => ! empty( $_POST['curata'] ),
				'stare'     => isset( $_POST['stare'] ) && 'draft' === $_POST['stare'] ? 'draft' : 'publish',
				'simulare'  => ! empty( $_POST['simulare'] ),
			)
		);
		if ( is_wp_error( $rez ) ) {
			wp_send_json_error( array( 'mesaj' => $rez->get_error_message() ) );
		}
		wp_send_json_success( $rez );
	}
);

function aptv_pagina_import() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	// Playlist-ul și filtrul salvate la fiecare categorie, ca formularul să se completeze singur.
	$info_categorii = array();
	foreach ( get_terms( array( 'taxonomy' => 'category', 'hide_empty' => false ) ) as $t ) {
		$info_categorii[ $t->term_id ] = array(
			'playlist' => (string) get_term_meta( $t->term_id, 'aptv_playlist', true ),
			'filtru'   => (string) get_term_meta( $t->term_id, 'aptv_filtru', true ),
		);
	}
	?>
	<div class="wrap">
		<h1>Import YouTube</h1>
		<?php if ( ! aptv_cheie_youtube() ) : ?>
			<div class="notice notice-warning"><p>Mai întâi salvați cheia YouTube API în <a href="<?php echo esc_url( admin_url( 'admin.php?page=aptv-setari' ) ); ?>">Apollonia TV → Setări</a>.</p></div>
		<?php endif; ?>

		<p>Alegeți emisiunea. Dacă are deja un playlist salvat, câmpul se completează singur.
		Videoclipurile deja importate sunt sărite automat, deci importul poate fi rulat oricând din nou.</p>
		<p><strong>Sfat:</strong> rulați întâi cu „Doar simulare” bifat, ca să vedeți titlurile înainte de import.</p>

		<form id="aptv-import" onsubmit="return false">
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="aptv-imp-categorie">Pune episoadele în</label></th>
					<td>
						<?php
						wp_dropdown_categories(
							array(
								'id'               => 'aptv-imp-categorie',
								'name'             => 'categorie',
								'hide_empty'       => false,
								'hierarchical'     => true,
								'show_option_none' => '— alegeți —',
								'orderby'          => 'name',
							)
						);
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="aptv-imp-playlist">Playlist YouTube</label></th>
					<td><input id="aptv-imp-playlist" name="playlist" type="text" class="regular-text" placeholder="PL... sau linkul playlist-ului">
					<p class="description">Pentru toate încărcările canalului folosiți <code>UUA5cYbtmQExq5QuYj3U8OlQ</code>.</p></td>
				</tr>
				<tr>
					<th scope="row">Ce videoclipuri</th>
					<td>
						<label><input type="radio" name="filtru" value="toate" checked> Toate</label><br>
						<label><input type="radio" name="filtru" value="scurte"> Doar clipurile scurte (până la 3 minute)</label><br>
						<label><input type="radio" name="filtru" value="lungi"> Doar clipurile lungi (peste 3 minute)</label>
					</td>
				</tr>
				<tr>
					<th scope="row">Opțiuni</th>
					<td>
						<label><input type="checkbox" name="curata" value="1" checked> Curăță titlurile (scoate numele emisiunii și datele din titlu)</label><br>
						<label><input type="checkbox" name="stare" value="draft"> Creează ca ciorne (nu apar pe site până nu le publicați)</label><br>
						<label><input type="checkbox" name="simulare" value="1" checked> Doar simulare (nu creează nimic, doar arată lista)</label>
					</td>
				</tr>
			</table>
			<p><button type="button" class="button button-primary button-hero" id="aptv-imp-start">Pornește importul</button></p>
		</form>

		<div id="aptv-imp-stare" style="font-size:15px;font-weight:600"></div>
		<ol id="aptv-imp-jurnal" style="max-height:420px;overflow:auto;background:#fff;border:1px solid #ccd0d4;padding:12px 12px 12px 36px;display:none"></ol>
	</div>

	<script>
	( function () {
		var info = <?php echo wp_json_encode( $info_categorii ); ?>;
		var form = document.getElementById( 'aptv-import' );
		var sel = document.getElementById( 'aptv-imp-categorie' );
		var pl = document.getElementById( 'aptv-imp-playlist' );
		var stare = document.getElementById( 'aptv-imp-stare' );
		var jurnal = document.getElementById( 'aptv-imp-jurnal' );
		var buton = document.getElementById( 'aptv-imp-start' );

		// La alegerea categoriei: completează playlist-ul și filtrul salvate.
		sel.addEventListener( 'change', function () {
			var i = info[ sel.value ] || {};
			pl.value = i.playlist || '';
			var f = form.querySelector( 'input[name=filtru][value="' + ( i.filtru || 'toate' ) + '"]' );
			if ( f ) { f.checked = true; }
		} );

		function scrie( text ) {
			var li = document.createElement( 'li' );
			li.textContent = text;
			jurnal.appendChild( li );
			jurnal.style.display = 'block';
		}

		buton.addEventListener( 'click', function () {
			if ( sel.value === '-1' ) { stare.textContent = 'Alegeți întâi categoria.'; return; }
			buton.disabled = true;
			jurnal.innerHTML = '';
			var total = { create: 0, sarite: 0 };
			var simulare = form.simulare.checked;

			function pas( token, nr ) {
				var date = new FormData( form );
				date.append( 'action', 'aptv_import_pas' );
				date.append( 'nonce', '<?php echo esc_js( wp_create_nonce( 'aptv_import' ) ); ?>' );
				date.append( 'token', token || '' );
				stare.textContent = 'Pasul ' + nr + '… (' + total.create + ( simulare ? ' de importat' : ' importate' ) + ', ' + total.sarite + ' sărite)';
				fetch( ajaxurl, { method: 'POST', body: date, credentials: 'same-origin' } )
					.then( function ( r ) { return r.json(); } )
					.then( function ( r ) {
						if ( ! r.success ) { throw new Error( ( r.data && r.data.mesaj ) || 'Eroare necunoscută.' ); }
						total.create += r.data.create;
						total.sarite += r.data.sarite;
						r.data.jurnal.forEach( scrie );
						if ( r.data.urmator ) { pas( r.data.urmator, nr + 1 ); return; }
						stare.textContent = 'Gata. ' + ( simulare ? 'S-ar importa: ' : 'Importate: ' ) + total.create + ' · Sărite (deja existente sau filtrate): ' + total.sarite;
						buton.disabled = false;
					} )
					.catch( function ( e ) {
						stare.textContent = 'Oprit: ' + e.message;
						buton.disabled = false;
					} );
			}
			pas( '', 1 );
		} );
	} )();
	</script>
	<?php
}

/* -------------------------------------------------------------------------
 * Sincronizarea automată zilnică.
 * ---------------------------------------------------------------------- */

/**
 * Bifa „Importă automat zilnic” din ecranul de editare a categoriei
 * și alegerea filtrului implicit (toate / scurte / lungi).
 */
add_action(
	'category_edit_form_fields',
	function ( $categorie ) {
		$auto   = (bool) get_term_meta( $categorie->term_id, 'aptv_auto', true );
		$filtru = (string) get_term_meta( $categorie->term_id, 'aptv_filtru', true );
		?>
		<tr class="form-field">
			<th scope="row">Import automat</th>
			<td>
				<label><input type="checkbox" name="aptv_auto" value="1" <?php checked( $auto ); ?>> Importă automat, zilnic, videoclipurile noi din playlist</label>
				<p class="description">Potrivit pentru playlist-urile care primesc des videoclipuri noi (ex. toate încărcările canalului).</p>
				<p>
					<label for="aptv-filtru">Ce videoclipuri:</label>
					<select id="aptv-filtru" name="aptv_filtru">
						<option value="toate" <?php selected( $filtru, 'toate' ); ?>>Toate</option>
						<option value="scurte" <?php selected( $filtru, 'scurte' ); ?>>Doar scurte (până la 3 minute)</option>
						<option value="lungi" <?php selected( $filtru, 'lungi' ); ?>>Doar lungi (peste 3 minute)</option>
					</select>
				</p>
			</td>
		</tr>
		<?php
	},
	20
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
		if ( ! empty( $_POST['aptv_auto'] ) ) {
			update_term_meta( $term_id, 'aptv_auto', 1 );
		} else {
			delete_term_meta( $term_id, 'aptv_auto' );
		}
		if ( isset( $_POST['aptv_filtru'] ) ) {
			$f = sanitize_key( wp_unslash( $_POST['aptv_filtru'] ) );
			update_term_meta( $term_id, 'aptv_filtru', in_array( $f, array( 'toate', 'scurte', 'lungi' ), true ) ? $f : 'toate' );
		}
	}
);

/**
 * Programează sincronizarea zilnică (WordPress o rulează la prima vizită după ora stabilită).
 */
add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( 'aptv_sincronizare_zilnica' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'aptv_sincronizare_zilnica' );
		}
	}
);

add_action(
	'aptv_sincronizare_zilnica',
	function () {
		if ( ! aptv_cheie_youtube() ) {
			return;
		}
		$categorii = get_terms(
			array(
				'taxonomy'   => 'category',
				'hide_empty' => false,
				'meta_key'   => 'aptv_auto', // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value' => '1', // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);
		foreach ( is_wp_error( $categorii ) ? array() : $categorii as $t ) {
			$playlist = (string) get_term_meta( $t->term_id, 'aptv_playlist', true );
			if ( ! $playlist ) {
				continue;
			}
			$filtru = (string) get_term_meta( $t->term_id, 'aptv_filtru', true );
			// Doar primul pas (cele mai noi 50 de videoclipuri).
			aptv_import_pas(
				array(
					'categorie' => $t->term_id,
					'playlist'  => $playlist,
					'token'     => '',
					'filtru'    => $filtru ? $filtru : 'toate',
					'curata'    => true,
					'stare'     => 'publish',
					'simulare'  => false,
				)
			);
		}
	}
);

/**
 * La schimbarea temei, oprește sincronizarea programată (curățenie).
 */
add_action(
	'switch_theme',
	function () {
		wp_clear_scheduled_hook( 'aptv_sincronizare_zilnica' );
	}
);
