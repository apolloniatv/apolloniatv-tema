# Apollonia TV — tema site-ului apolloniatv.ro

Temă copil (child theme) WordPress, construită peste [GeneratePress](https://wordpress.org/themes/generatepress/).
Site-ul este o poartă de acces simplă către emisiunile și știrile Apollonia TV, gândită mai ales pentru vizitatorii vârstnici.

**Documentația completă, în limbaj non-tehnic: [DOCUMENTATIE.md](DOCUMENTATIE.md).**

## Ce face

- Antet cu telefon de ajutor, butoane de mărire a textului (A / A+ / A++) și meniu cu teme și emisiuni, construit automat din categorii.
- Prima pagină: episod recomandat (bifă în editor), cele mai noi materiale, știri științifice, teme, universitate, rețele sociale.
- Pagina emisiunii: episoadele în ordine cronologică, cu numerotare și comutare „cele mai noi primele”.
- Pagina episodului: player YouTube încărcat la clic (site rapid), „Episodul următor”, „Urmează în...”.
- Import din playlist-uri YouTube (inclusiv videoclipuri „Unlisted”) și sincronizare automată zilnică.
- Pagină de setări în admin pentru datele de contact, linkuri și cheia YouTube API.

## Instalare

1. Instalați și **lăsați instalată** tema GeneratePress (Aspect → Teme → Adaugă).
2. Încărcați tema o singură dată, ca arhivă ZIP care conține folderul `apolloniatv-tema/` (Aspect → Teme → Adaugă → Încarcă) și activați-o.
3. De aici înainte, versiunile noi apar singure în **Panou → Actualizări** (modulul `inc/actualizari.php`; fără plugin-uri).
4. Urmați pașii din [DOCUMENTATIE.md, secțiunea 4](DOCUMENTATIE.md#4-lucruri-care-se-fac-o-singură-dată-la-instalare).

## Cerințe

WordPress 6.4+, PHP 8.1+, GeneratePress 3.x.

## Publicarea unei versiuni noi

Măriți `Version` din `style.css`, apoi faceți push pe `main`. În câteva ore (sau imediat, cu „Verifică din nou” în Panou → Actualizări) WordPress oferă actualizarea.
