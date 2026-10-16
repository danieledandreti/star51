# Searching Your Collection

Star51's public search looks in item titles and summaries. Enter the words
you remember; every word must occur in either field, in any order.

## Matching

- Terms of three or more letters or digits can match part of a word.
  For example, `cioc` matches `cioccolato`.
- One- and two-character terms match whole words. `IT` does not match a
  fragment inside a longer word; `9` does not match the year `1999`.
- Spaces, punctuation, apostrophes and hyphens separate search terms.
  `x-men` and `x men` are equivalent, as are straight and typographic
  apostrophes in `L'uomo d'acciaio`.
- Letter case and accent comparisons follow the database collation.
- Empty input or input containing only whitespace or punctuation does not
  start a search. The server processes at most 255 characters.
- Extended descriptions, formats and item years are not searched.

There is no fuzzy spelling, synonym or Roman-numeral conversion. `xmen`
is not automatically expanded to `x men`; `ET` is different from `E.T.`;
`Star Wars 3` is not automatically equivalent to `Star Wars III`.

Matches in summaries are intentional. Searching for `dune` can also find
an item whose summary mentions a dune buggy.

## Result Order

1. Item year, newest first.
2. Title, alphabetically when years are equal.
3. Item ID, descending when both year and title are equal.

Items with no year, or with year zero, appear last in alphabetical order.
When all years are equal or absent, title order determines the list.
The item year is independent of the date when an item was added or published.

Only published items in active, non-system categories and subcategories are
included. Pagination keeps the search terms and shows nine items per page.

## Nova Administration

Nova also searches titles and summaries and requires every requested word.
Its existing behavior is separate: partial-word matching, a minimum of
three input characters and alphabetical result order. It can find unpublished
items too. The public search refinements do not change Nova's search rules.

## Compatibility

The public whole-word comparison uses MySQL 8.0 `REGEXP_REPLACE`, consistent
with Star51's existing MySQL 8.0+ requirement. No schema migration or data
rewrite is required.
