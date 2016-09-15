<?php

class GlossaryContentTermWordIndex
  extends PapayaObject
  implements PapayaDatabaseInterfaceAccess {

  private $_databaseAccessObject;

  public function update($limit = NULL, $offset = NULL) {
    $databaseAccess = $this->getDatabaseAccess();
    $sql = "SELECT language_id, glossary_term_id, 
                   glossary_term, glossary_term_abbreviations, glossary_term_synonyms, glossary_term_derivations 
              FROM %s 
              ORDER BY glossary_term_id, language_id";
    $parameters = [
      $databaseAccess->getTableName(GlossaryContentTables::TABLE_TERM_TRANSLATIONS)
    ];
    $words = new PapayaIteratorMultiple();
    foreach ($databaseAccess->queryFmt($sql, $parameters, $limit, $offset) as $term) {
      $words->attachIterator(
        new PapayaIteratorCallback(
          new GlossaryContentTermWordList(
            [
              GlossaryContentTermWords::TYPE_TERM => $term['glossary_term'],
              GlossaryContentTermWords::TYPE_SYNONYM => $term['glossary_term_synonyms'],
              GlossaryContentTermWords::TYPE_ABBREVIATION => $term['glossary_term_abbreviations'],
              GlossaryContentTermWords::TYPE_DERIVATION => $term['glossary_term_derivations']
            ]
          ),
          function($word) use ($term) {
            return [
              'glossary_term_id' => $term['glossary_term_id'],
              'language_id' => $term['language_id'],
              'glossary_word_type' => $word['type'],
              'glossary_word' => $word['word'],
              'normalized' => $word['normalized'],
              'first_char' => $word['character']
            ];
          }
        )
      );
    }
    if ($offset < 1) {
      $databaseAccess->emptyTable(
        $databaseAccess->getTableName(GlossaryContentTables::TABLE_TERM_WORDS)
      );
    }
    return $databaseAccess->insertRecords(
      $databaseAccess->getTableName(GlossaryContentTables::TABLE_TERM_WORDS),
      iterator_to_array($words)
    );
  }

  /**
   * Set database access object
   *
   * @param PapayaDatabaseAccess $databaseAccessObject
   */
  public function setDatabaseAccess(PapayaDatabaseAccess $databaseAccessObject) {
    $this->_databaseAccessObject = $databaseAccessObject;
  }

  /**
   * Get database access object
   *
   * @return PapayaDatabaseAccess
   */
  public function getDatabaseAccess() {
    if (!isset($this->_databaseAccessObject)) {
      $this->_databaseAccessObject = $this->papaya()->database->createDatabaseAccess($this);
    }
    return $this->_databaseAccessObject;
  }

}