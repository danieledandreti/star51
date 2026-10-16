# Star51 v1.2.1 - Search Documentation Update

Released on 16 October 2026.

## English

### Version 1.2.1

This documentation-only update consolidates the search explanations in these
release notes and removes the standalone search document. Application code,
search behavior, configuration and database structure are unchanged from v1.2.0.
Existing v1.2.0 installations require no application update or migration.

### Version 1.2.0 - Search Improvements

Star51 v1.2.0 improves public collection search while keeping a single,
simple search field.

### Improvements

- Search item titles and summaries only; extended descriptions, formats and
  item years no longer produce extra matches. This scope also applies to Nova.
- Public search requires all requested words, in any order and across the
  title and summary.
- Find short titles such as `IT`, `Up` and `9`. One- and two-character terms
  match whole words; longer terms still support partial matches.
- Treat hyphens, punctuation and straight or typographic apostrophes as
  separators. `x-men` and `x men` return the same matches.
- Ignore empty, whitespace-only and punctuation-only public searches.
- Sort public results by item year, newest first, then title A-Z and ID
  descending. Missing or zero years appear last.
- Keep result counts and pagination consistent through shared search conditions.
- Optimize short-term comparisons using temporary text normalization and
  prepared `LIKE` parameters.

### Search Examples and Result Order

- `fabbrica cioccolato` and `cioccolato fabbrica` use the same matching rules:
  every word must occur in the title or summary, even across both fields.
- `cioc` can match `cioccolato`; short terms such as `IT` match whole words.
- Punctuation separates words: `x-men` and `x men` are equivalent, but
  `xmen` is not automatically split and `ET` is not equivalent to `E.T.`.
- Letter case and accent comparisons follow the database collation.
- A match in the summary is valid even when the title does not contain the term.
- Results use the item's year, not its insertion or publication date: newest
  year first, then title A-Z, then item ID descending when both are equal.
- Missing or zero years appear last. Equal or absent years fall back to title
  order, with item ID as the final tie-breaker.
- Only published items in active, non-system categories and subcategories
  appear publicly. Results show nine items per page and preserve the query.
- The server processes at most 255 query characters; input with no letters or
  digits does not start an item search.
- Nova requires all words in titles or summaries too, but retains partial-word
  matching, a three-character input minimum and alphabetical ordering. It can
  also find unpublished items.

### Compatibility and Upgrade

- PHP 8.0+ and MySQL 8.0+, as already required by Star51.
- No database migration, configuration change or bulk content rewrite.
- Preserve the existing local configuration and uploaded images when upgrading.
  Do not overwrite an installed site's configuration with the empty file in
  the clean installation package.
- Public results can change because matching now uses titles and summaries,
  all query words, and punctuation separators.
- Nova retains its three-character minimum and alphabetical ordering.
- No automatic synonym, spelling, joined-word or Roman-numeral conversion.

### Validation of v1.2.0

- Solo regression suite: 100/100 passed.
- 24 HTTP acceptance cases on the 846-item film catalog, five equivalence
  comparisons, pagination and manual browser checks passed.
- Local short-query HTTP samples improved from approximately 1-1.8 seconds
  to 15-28 milliseconds. These measurements describe one local test setup,
  not a guarantee for every host or catalog size.

## Italiano

### Versione 1.2.1

Questo aggiornamento riguarda soltanto la documentazione: riunisce qui le
spiegazioni della ricerca e rimuove il documento separato. Codice applicativo,
ricerca, configurazione e struttura database restano invariati rispetto alla
v1.2.0. Le installazioni v1.2.0 non richiedono aggiornamenti applicativi o migrazioni.

### Versione 1.2.0 - Miglioramenti della ricerca

Star51 v1.2.0 migliora la ricerca pubblica mantenendo un unico campo semplice.

### Miglioramenti

- Ricerca soltanto in titolo e testo breve: descrizione estesa, formato e
  anno non generano piu corrispondenze aggiuntive. Questo ambito vale anche in Nova.
- Tutte le parole richieste devono essere presenti, in ordine libero e anche
  distribuite tra titolo e testo breve.
- Titoli corti come `IT`, `Up` e `9` sono ricercabili. I termini di uno o due
  caratteri sono parole intere; quelli piu lunghi ammettono frammenti.
- Trattini, punteggiatura e apostrofi normali o tipografici separano i termini.
  `x-men` e `x men` restituiscono le stesse corrispondenze.
- Richieste vuote, di soli spazi o di sola punteggiatura non avviano la ricerca.
- Risultati per anno dell'oggetto dal piu recente al piu vecchio, poi titolo
  A-Z e ID decrescente. Anni mancanti o zero in fondo.
- Conteggio e paginazione condividono le condizioni di ricerca.
- Confronto dei termini corti ottimizzato con normalizzazione temporanea
  del testo e parametri preparati `LIKE`.

### Esempi e ordine dei risultati

- `fabbrica cioccolato` e `cioccolato fabbrica` seguono le stesse regole:
  tutte le parole devono comparire nel titolo o nel testo breve, anche nei due
  campi combinati.
- `cioc` trova anche `cioccolato`; termini corti come `IT` sono parole intere.
- La punteggiatura separa i termini: `x-men` equivale a `x men`, ma `xmen`
  non viene separato automaticamente ed `ET` non equivale a `E.T.`.
- Maiuscole e accenti seguono la collation del database.
- Una corrispondenza nel testo breve resta valida anche se manca nel titolo.
- Conta l'anno dell'oggetto, non la data di inserimento o pubblicazione: anno
  decrescente, poi titolo A-Z e infine ID decrescente in caso di ulteriore parita.
- Anni mancanti o zero in fondo. Con anni uguali o assenti prevale il titolo,
  con ID come ultimo criterio in caso di parita.
- Nel pubblico compaiono solo schede pubblicate in categorie e sottocategorie
  attive e non di sistema; nove risultati per pagina, conservando la ricerca.
- Il server considera al massimo 255 caratteri; richieste senza lettere o
  cifre non avviano la ricerca degli articoli.
- Anche Nova richiede tutte le parole in titolo o testo breve, ma mantiene
  frammenti di parola, minimo di tre caratteri e ordine alfabetico. Include
  anche schede non pubblicate.

### Compatibilita e Aggiornamento

- PHP 8.0+ e MySQL 8.0+, gia richiesti da Star51.
- Nessuna migrazione database, modifica di configurazione o riscrittura dei dati.
- Conservare configurazione e immagini della propria installazione. Il file
  di configurazione vuoto del pacchetto pulito non deve sostituire quello in uso.
- I risultati possono cambiare per il nuovo ambito titolo/testo breve e per
  la ricerca di tutte le parole con separatori equivalenti.
- Nova mantiene minimo di tre caratteri e ordinamento alfabetico.
- Nessuna conversione automatica di sinonimi, errori, parole unite o numeri romani.

### Verifiche della v1.2.0

- Suite Solo: 100/100.
- 24 prove HTTP sul catalogo di 846 schede, cinque confronti di equivalenza,
  paginazione e prove manuali nel browser superati.
- Nei campioni locali le ricerche corte sono scese da circa 1-1,8 secondi a
  15-28 millisecondi. Sono misure del singolo ambiente, non una promessa generale.
