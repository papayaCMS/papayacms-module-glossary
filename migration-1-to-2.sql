INSERT INTO papaya_glossary_terms (glossary_term_id)
  SELECT glossaryentry_id FROM papaya_glossaryentries;

INSERT INTO papaya_glossary_term_translations
    (glossary_term_id, language_id,
     glossary_term, glossary_term_explanation, glossary_term_source, glossary_term_links,
     glossary_term_synonyms, glossary_term_abbreviations, glossary_term_derivations,
     glossary_term_modified, glossary_term_created)
    SELECT
      glossaryentry_id, lng_id,
      glossaryentry_term, glossaryentry_explanation, glossaryentry_source, glossaryentry_links,
      glossaryentry_synonyms, glossaryentry_abbreviations, glossaryentry_derivation,
      UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
    FROM papaya_glossaryentries_trans;

INSERT INTO papaya_glossary_term_links (glossary_id, glossary_term_id)
  SELECT glossary_id, glossaryentry_id FROM papaya_glossaryentries;
