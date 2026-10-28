# Documentația site-ului apolloniatv.ro

Acest document explică, pe înțelesul oricui, cum funcționează site-ul și ce faceți dacă ceva nu merge.
Pentru detalii tehnice, fiecare fișier de cod are la început o explicație în română.

---

## 1. Cum e construit site-ul

| Piesă | Ce este |
|---|---|
| **WordPress** | sistemul în care se adaugă conținutul |
| **GeneratePress** (tema „părinte”) | tema de bază, gratuită, ușoară; se actualizează singură din WordPress |
| **Apollonia TV** (tema „copil”, acest cod) | tot ce e specific site-ului: aspectul, meniul, prima pagină, importul YouTube |
| **GitHub** (`apolloniatv/apolloniatv-tema`) | locul unde stă codul temei copil, cu istoricul tuturor modificărilor |
| **Actualizările** (modul al temei) | WordPress vede singur versiunile noi ale temei de pe GitHub (Panou → Actualizări) |

Codul nostru **nu modifică GeneratePress**. Dacă GeneratePress se actualizează, site-ul rămâne la fel.

---

## 2. Cum e organizat conținutul

Totul se bazează pe **categoriile** WordPress (Articole → Categorii), pe trei niveluri:

```
Știri ─────────── Externe · Naționale · Locale · Științifice
Emisiuni ──────── Educație ─── Educația Încotro, Colocviile ECRE
                  Cultură ──── Stop cadru, Mirajul Muzicii, Portrete în mișcare, Scena
                  Sănătate ─── Repere în Medicină, Interviuri de sănătate
                  Actualitate și dezbateri ── Noaptea Generalilor, Ediție Specială, Tribuna liberă
Universitate ──── Universitaria, Congresul Apollonia 2022
Portofoliu ────── proiecte video făcute pentru alții (fără subcategorii)
```

Conținutul vine de pe două canale YouTube: canalul principal Apollonia TV și **@apolloniatvcultural**
(știrile, Scena, Ediție Specială, Tribuna liberă).

- **Un episod = un articol** WordPress, pus în categoria emisiunii lui.
- **Meniul și prima pagină se construiesc singure** din aceste categorii. Nu există un meniu de editat de mână.
- **Meniul arată doar ce are conținut.** O emisiune sau un tip de știri fără niciun episod publicat nu apare în meniu și pe prima pagină; apare singur după primul episod.
- **Titlurile cu MAJUSCULE** sunt afișate pe site în scriere normală; în admin rămân cum au fost scrise. Un nume propriu care apare greșit (cu literă mică) se adaugă în lista din `inc/titluri.php`.
- **Data articolului = data difuzării.** Episoadele unei emisiuni apar în ordinea datei (primul episod sus).

---

## 3. Ce poate face echipa, fără cod

### Schimbați episodul recomandat de pe prima pagină
1. Articole → deschideți episodul dorit.
2. În dreapta, la „Prima pagină”, bifați **Recomandat pe prima pagină**.
3. Apăsați **Actualizează**.

Câștigă întotdeauna ultimul episod bifat. Nu trebuie debifat cel vechi.
În lista de articole, coloana „★ Prima pagină” arată ce episoade sunt bifate.

### Schimbați telefonul, WhatsApp-ul, e-mailul, adresa, linkurile rețelelor sau textul din subsol
Apollonia TV → **Setări**. Completați și apăsați **Salvează setările**.

### Portofoliu: serviciile și proiectele
- **Serviciile** (înregistrare, live, montaj...) se schimbă din Apollonia TV → **Setări** → „Portofoliu — serviciile oferite”:
  câte un serviciu pe rând, sub forma `Nume: descriere`. Pictograma se alege singură după nume.
- **Un proiect nou** = un articol cu linkul YouTube, pus în categoria **Portofoliu** (doar în ea).
  Dacă videoclipul a fost deja importat în altă categorie, deschideți articolul și mutați-l la Portofoliu.
- Proiectele din Portofoliu nu apar la „Cele mai noi” pe prima pagină.
- Numele din meniu este numele categoriei: îl schimbați din Articole → Categorii (ex. „Colaborări”).

### Adăugați o emisiune nouă
1. Articole → Categorii → Adaugă o categorie nouă.
2. La **Părinte** alegeți tema (ex. „Cultură”).
3. Scrieți o descriere scurtă (apare pe site).
4. După salvare, deschideți categoria (Editează) și completați **Playlist YouTube**.

Emisiunea apare automat în meniu și pe prima pagină.

### Schimbați ordinea temelor în meniu
Articole → Categorii → Editează tema → **Ordine în meniu** (1 = prima).

### Adăugați un episod de mână
Articole → Adaugă: titlul, apoi lipiți linkul YouTube în text (se transformă singur în player), alegeți categoria emisiunii, setați data (în dreapta, „Publică”) și publicați.

### Importați episoade din YouTube
Apollonia TV → **Import YouTube**:
1. Alegeți emisiunea. Playlist-ul se completează singur dacă e salvat la categorie.
2. Lăsați bifat **Doar simulare** și apăsați **Pornește importul**. Verificați lista.
3. Debifați „Doar simulare” și porniți din nou.

Importul poate fi rulat oricând din nou: **videoclipurile deja importate sunt sărite**.
Videoclipurile „Unlisted” se importă normal, dacă playlist-ul e public.
Videoclipurile private și cele care nu permit încorporarea sunt sărite (apar în listă cu motivul).

**Se importă singure, zilnic** categoriile care au bifat „Importă automat zilnic”: Științifice, Știri,
Scena, Ediție Specială și Tribuna liberă.

### Știrile de pe @apolloniatvcultural (Locale / Naționale / Externe)
Puneți știrea în playlistul **„Stiri”** al canalului @apolloniatvcultural. Site-ul o preia zilnic și o pune singur
la Locale, Naționale sau Externe:
- dacă în titlu sau în descrierea de pe YouTube scrie **#locale**, **#nationale** sau **#externe**, eticheta decide;
- altfel, după titlu: Iași, Bacău, Bârlad, Suceava... → Locale; țări, capitale, lideri străini → Externe; restul → Naționale.

Dacă o știre ajunge greșit, o mutați din WordPress (Articole → editare → Categorii). Lista de cuvinte este în
`inc/import-youtube.php` (funcția `aptv_ruteaza_stire`).

---

## 4. Lucruri care se fac o singură dată (la instalare)

1. **Setări → General → Limba site-ului: Română.** (Altfel datele apar în engleză: „26 November”.)
2. **Setări → Legături permanente: „Denumire articol”.**
3. Ștergeți articolul „Hello world!” / „Salut, lume!”.
4. Apollonia TV → Structură → **Creează structura**.
5. Apollonia TV → Setări → completați datele reale și **cheia YouTube API** (vezi mai jos).

### Cum se obține cheia YouTube API (gratuită)
1. Intrați pe console.cloud.google.com cu un cont Google al echipei.
2. Creați un proiect nou (ex. „apolloniatv-site”).
3. „APIs & Services” → „Library” → căutați **YouTube Data API v3** → **Enable**.
4. „APIs & Services” → „Credentials” → „Create credentials” → **API key**.
5. Recomandat: la cheie, „API restrictions” → doar „YouTube Data API v3”.
6. Copiați cheia în Apollonia TV → Setări → „Cheia YouTube Data API”.

Cheia se salvează doar în baza de date WordPress. Nu apare în cod, pe GitHub sau pe site.

---

## 5. Cum se publică o modificare de cod

1. Modificarea se face în codul temei și se trimite pe GitHub.
2. **În `style.css` se mărește numărul de la „Version”** (ex. 0.1.0 → 0.1.1). Fără asta, actualizarea nu e observată.
3. În WordPress: **Panou → Actualizări** → „Verifică din nou” → bifați tema „Apollonia TV” → **Actualizează temele**.

Fiecare versiune rămâne pe GitHub. Dacă o versiune nouă strică ceva, se poate reveni la cea anterioară.

---

## 6. Dacă ceva nu merge

| Problemă | Ce faceți |
|---|---|
| Site-ul afișează „A apărut o eroare critică” după o actualizare a temei | Reveniți la versiunea anterioară a temei (din GitHub) sau activați temporar GeneratePress (Aspect → Teme) — conținutul nu se pierde. |
| O singură funcție nu merge (ex. meniul) | În `functions.php`, puneți `//` în fața liniei modulului respectiv. Restul site-ului merge în continuare. |
| Importul spune „YouTube a refuzat cererea” | Verificați cheia API (poate a fost ștearsă sau restricționată greșit). Limita gratuită zilnică e foarte mare; nu ar trebui atinsă. |
| Un videoclip e „sărit (nu poate fi încorporat)” | În YouTube Studio → videoclipul → Detalii → „Afișați mai multe” → bifați „Permiteți încorporarea”. Apoi rulați din nou importul. |
| Episoadele unei emisiuni sunt în ordine greșită | Verificați data articolelor (trebuie să fie data difuzării). |
| Un episod apare de două ori | Ștergeți unul dintre articole. Importul nu creează dubluri; dublura vine de la adăugarea de mână. |
| Datele apar în engleză | Setări → General → Limba site-ului: Română. |

---

## 7. Hartă a fișierelor

```
style.css               „cartea de identitate” a temei (numele, versiunea)
functions.php           încarcă modulele de mai jos
inc/setari.php          pagina Apollonia TV → Setări
inc/structura.php       categoriile, câmpurile Playlist / Culoare / Ordine, pagina Structură
inc/recomandat.php      bifa „Recomandat pe prima pagină”
inc/ordine-episoade.php ordinea cronologică a episoadelor
inc/ajutoare.php        funcții mici (durata, imaginea, cardul unui episod, „Unde vă aflați”)
inc/video.php           playerul care se încarcă la clic
inc/antet.php           logo, căutare, A/A+/A++, bara de meniu, meniul de pe telefon
inc/titluri.php         titlurile cu MAJUSCULE apar pe site în scriere normală
inc/subsol.php          banda „Nu găsiți ce căutați?” și subsolul
inc/episod.php          episodul anterior / următor, cele mai noi materiale
inc/import-youtube.php  importul din YouTube și sincronizarea zilnică
inc/actualizari.php     versiunile noi de pe GitHub apar în Panou → Actualizări
front-page.php          prima pagină
category.php            paginile de emisiuni, teme, emisiune, știri
single.php              pagina unui episod
search.php              rezultatele căutării
assets/css/tema.css     aspectul (culorile se schimbă la începutul fișierului)
assets/js/tema.js       meniul, mărimea textului, playerul
assets/img/logo.png     logo-ul (folosit dacă nu e setat altul în Personalizare)
```
