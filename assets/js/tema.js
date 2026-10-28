/**
 * Apollonia TV — partea interactivă a site-ului.
 *
 * Trei funcții, independente una de alta:
 *   1. Meniul: „Știri” și „Emisiuni” deschid/închid panoul lor la clic.
 *   2. Mărimea textului: butoanele A / A+ / A++ (alegerea se ține minte în browser).
 *   3. Playerul: la apăsarea pe „Pornește” se încarcă playerul YouTube și filmul pornește.
 *
 * Dacă JavaScript nu merge, site-ul rămâne utilizabil: „Știri”/„Emisiuni” duc la
 * paginile lor, iar sub player apare linkul „Urmăriți pe YouTube”.
 */
( function () {
	'use strict';

	/* 1. Meniul --------------------------------------------------------- */
	var deschizatoare = document.querySelectorAll( '.aptv-meniu__deschide' );

	function inchideToate( exceptie ) {
		deschizatoare.forEach( function ( buton ) {
			if ( buton === exceptie ) {
				return;
			}
			buton.setAttribute( 'aria-expanded', 'false' );
			var panou = document.getElementById( buton.dataset.panou );
			if ( panou ) {
				panou.hidden = true;
			}
		} );
	}

	deschizatoare.forEach( function ( buton ) {
		// Se comportă ca un buton (deschide panoul), nu ca un link.
		buton.setAttribute( 'role', 'button' );
		buton.addEventListener( 'click', function ( e ) {
			var panou = document.getElementById( buton.dataset.panou );
			if ( ! panou ) {
				return; // fără panou, linkul duce la pagina secțiunii
			}
			e.preventDefault();
			var deschis = buton.getAttribute( 'aria-expanded' ) === 'true';
			inchideToate( buton );
			buton.setAttribute( 'aria-expanded', deschis ? 'false' : 'true' );
			panou.hidden = deschis;
		} );
		// Tasta Spațiu deschide și ea panoul (ca la orice buton).
		buton.addEventListener( 'keydown', function ( e ) {
			if ( e.key === ' ' ) {
				e.preventDefault();
				buton.click();
			}
		} );
	} );

	// Tasta Escape sau un clic în afara antetului închid panourile.
	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'Escape' ) {
			var deschis = document.querySelector( '.aptv-meniu__deschide[aria-expanded="true"]' );
			inchideToate( null );
			if ( deschis ) {
				deschis.focus();
			}
		}
	} );
	document.addEventListener( 'click', function ( e ) {
		if ( ! e.target.closest( '.aptv-antet' ) ) {
			inchideToate( null );
		}
	} );

	/* 2. Mărimea textului și contrastul ---------------------------------- */
	var radacina = document.documentElement;
	var TREPTE = [ 'normal', 'mare', 'foarte-mare' ];
	var NUME = { 'normal': 'Text normal', 'mare': 'Text mare', 'foarte-mare': 'Text foarte mare' };

	function citeste( cheie, implicit ) {
		try {
			return localStorage.getItem( cheie ) || implicit;
		} catch ( err ) {
			return implicit; // browserul nu permite salvarea — alegerea se aplică doar pe pagina curentă
		}
	}
	function scrie( cheie, valoare ) {
		try {
			localStorage.setItem( cheie, valoare );
		} catch ( err ) {}
	}

	var treapta = Math.max( 0, TREPTE.indexOf( citeste( 'aptv-text', 'normal' ) ) );

	function arataMarime( anunta ) {
		var marime = TREPTE[ treapta ];
		radacina.classList.remove( 'aptv-text-mare', 'aptv-text-foarte-mare' );
		if ( marime !== 'normal' ) {
			radacina.classList.add( 'aptv-text-' + marime );
		}
		document.querySelectorAll( '.aptv-marime' ).forEach( function ( grup ) {
			var minus = grup.querySelector( '[data-pas="-1"]' );
			var plus = grup.querySelector( '[data-pas="1"]' );
			if ( minus ) {
				minus.setAttribute( 'aria-disabled', treapta === 0 ? 'true' : 'false' );
			}
			if ( plus ) {
				plus.setAttribute( 'aria-disabled', treapta === TREPTE.length - 1 ? 'true' : 'false' );
			}
			var stare = grup.querySelector( '.aptv-marime__stare' );
			if ( stare && anunta ) {
				stare.textContent = NUME[ marime ];
			}
		} );
	}

	document.querySelectorAll( '.aptv-marime button[data-pas]' ).forEach( function ( b ) {
		b.addEventListener( 'click', function () {
			var noua = treapta + parseInt( b.dataset.pas, 10 );
			if ( noua < 0 || noua >= TREPTE.length ) {
				return;
			}
			treapta = noua;
			scrie( 'aptv-text', TREPTE[ treapta ] );
			arataMarime( true );
		} );
	} );
	arataMarime( false );

	// Contrast: culorile pastel (implicit) ⇄ culorile puternice.
	function arataContrast() {
		var activ = radacina.classList.contains( 'aptv-contrast' );
		document.querySelectorAll( '.aptv-contrast-buton' ).forEach( function ( b ) {
			b.setAttribute( 'aria-pressed', activ ? 'true' : 'false' );
		} );
	}
	document.querySelectorAll( '.aptv-contrast-buton' ).forEach( function ( b ) {
		b.addEventListener( 'click', function () {
			var activ = radacina.classList.toggle( 'aptv-contrast' );
			scrie( 'aptv-contrast', activ ? '1' : '0' );
			arataContrast();
		} );
	} );
	arataContrast();

	/* 3. Playerul --------------------------------------------------------- */
	document.addEventListener( 'click', function ( e ) {
		var buton = e.target.closest( '.aptv-player__buton' );
		if ( ! buton ) {
			return;
		}
		var player = buton.closest( '.aptv-player' );
		var id = player.dataset.video;
		if ( ! /^[A-Za-z0-9_-]{11}$/.test( id ) ) {
			return;
		}
		var cadru = document.createElement( 'iframe' );
		cadru.src = 'https://www.youtube-nocookie.com/embed/' + id + '?autoplay=1&rel=0&playsinline=1';
		cadru.title = player.dataset.titlu || 'Film Apollonia TV';
		cadru.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen';
		cadru.allowFullscreen = true;
		player.innerHTML = '';
		player.appendChild( cadru );
		cadru.focus();
	} );

	/* 4. Meniul de pe telefon (ecran întreg) ------------------------------ */
	var butonMeniu = document.querySelector( '.aptv-meniu-buton' );
	var meniuMobil = document.getElementById( 'aptv-meniu-mobil' );

	function inchideMeniuMobil() {
		if ( ! meniuMobil || meniuMobil.hidden ) {
			return;
		}
		meniuMobil.hidden = true;
		document.documentElement.classList.remove( 'aptv-meniu-deschis' );
		butonMeniu.setAttribute( 'aria-expanded', 'false' );
		butonMeniu.focus();
	}

	if ( butonMeniu && meniuMobil ) {
		butonMeniu.addEventListener( 'click', function () {
			meniuMobil.hidden = false;
			document.documentElement.classList.add( 'aptv-meniu-deschis' );
			butonMeniu.setAttribute( 'aria-expanded', 'true' );
			var inchide = meniuMobil.querySelector( '.aptv-meniu-mobil__inchide' );
			if ( inchide ) {
				inchide.focus();
			}
		} );
		meniuMobil.querySelector( '.aptv-meniu-mobil__inchide' ).addEventListener( 'click', inchideMeniuMobil );
		// „Contact și ajutor” duce la subsolul aceleiași pagini: închidem întâi meniul.
		meniuMobil.querySelectorAll( 'a[href^="#"]' ).forEach( function ( a ) {
			a.addEventListener( 'click', function () {
				meniuMobil.hidden = true;
				document.documentElement.classList.remove( 'aptv-meniu-deschis' );
				butonMeniu.setAttribute( 'aria-expanded', 'false' );
			} );
		} );
		document.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Escape' ) {
				inchideMeniuMobil();
			}
		} );
		// Dacă ecranul se lărgește (telefon rotit, fereastră mărită), meniul de telefon se închide.
		window.addEventListener( 'resize', function () {
			if ( window.innerWidth > 760 ) {
				inchideMeniuMobil();
			}
		} );
	}

	/* 5. Imagini mari ------------------------------------------------------- */
	// Imaginea de sus a primei pagini se cere la rezoluție mare. Unele videoclipuri nu au
	// una mare: YouTube trimite atunci o imagine gri de 120 px. O înlocuim cu cea obișnuită.
	document.querySelectorAll( 'img[data-aptv-rezerva]' ).forEach( function ( img ) {
		function verifica() {
			if ( img.naturalWidth > 0 && img.naturalWidth <= 120 ) {
				img.src = img.dataset.aptvRezerva;
				img.removeAttribute( 'data-aptv-rezerva' );
			}
		}
		img.addEventListener( 'error', function () {
			if ( img.dataset.aptvRezerva ) {
				img.src = img.dataset.aptvRezerva;
				img.removeAttribute( 'data-aptv-rezerva' );
			}
		} );
		if ( img.complete ) {
			verifica();
		} else {
			img.addEventListener( 'load', verifica );
		}
	} );
}() );
