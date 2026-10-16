# Star51 v1.2.0 - Easier Collection Search

Code and release notes updated on main on 16 October 2026. The v1.2.0 tag and GitHub Release are pending.

## English

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

### Validation

- Solo regression suite: 100/100 passed.
- 24 HTTP acceptance cases on the 846-item film catalog, five equivalence
  comparisons, pagination and manual browser checks passed.
- Local short-query HTTP samples improved from approximately 1-1.8 seconds
  to 15-28 milliseconds. These measurements describe one local test setup,
  not a guarantee for every host or catalog size.

## Italiano

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

### Compatibilita e Aggiornamento

- PHP 8.0+ e MySQL 8.0+, gia richiesti da Star51.
- Nessuna migrazione database, modifica di configurazione o riscrittura dei dati.
- Conservare configurazione e immagini della propria installazione. Il file
  di configurazione vuoto del pacchetto pulito non deve sostituire quello in uso.
- I risultati possono cambiare per il nuovo ambito titolo/testo breve e per
  la ricerca di tutte le parole con separatori equivalenti.
- Nova mantiene minimo di tre caratteri e ordinamento alfabetico.
- Nessuna conversione automatica di sinonimi, errori, parole unite o numeri romani.

### Verifiche

- Suite Solo: 100/100.
- 24 prove HTTP sul catalogo di 846 schede, cinque confronti di equivalenza,
  paginazione e prove manuali nel browser superati.
- Nei campioni locali le ricerche corte sono scese da circa 1-1,8 secondi a
  15-28 millisecondi. Sono misure del singolo ambiente, non una promessa generale.
