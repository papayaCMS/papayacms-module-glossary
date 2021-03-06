<?php

class GlossaryContentTermWords extends PapayaDatabaseRecordsLazy {

  const TYPE_TERM = '1';
  const TYPE_SYNONYM = '2';
  const TYPE_ABBREVIATION = '3';
  const TYPE_DERIVATION = '4';

  protected $_fields = [
    'term_id' => 'w.glossary_term_id',
    'term_title' => 'tt.glossary_term',
    'term_explanation' => 'tt.glossary_term_explanation',
    'term_modified' => 'tt.glossary_term_modified',
    'language_id' => 'w.language_id',
    'type' => 'w.glossary_word_type',
    'word' => 'w.glossary_word',
    'normalized' => 'w.normalized',
    'character' => 'w.first_char'
  ];

  protected $_orderByProperties = ['word' => PapayaDatabaseInterfaceOrder::ASCENDING];

  protected $_tableName = GlossaryContentTables::TABLE_TERM_WORDS;
  protected $_tableTermTranslations = GlossaryContentTables::TABLE_TERM_TRANSLATIONS;
  protected $_tableTermLinks = GlossaryContentTables::TABLE_TERM_GLOSSARY_LINKS;

  public function load($filter = [], $limit = NULL, $offset = NULL) {
    $databaseAccess = $this->getDatabaseAccess();
    if (empty($filter['glossary_id'])) {
      $sql = "SELECT w.glossary_term_id, tt.glossary_term, tt.glossary_term_explanation, tt.glossary_term_modified,
                     w.language_id, w.glossary_word_type, w.glossary_word, w.normalized, w.first_char 
                FROM %s AS w
               INNER JOIN %s AS tt ON (tt.glossary_term_id = w.glossary_term_id AND tt.language_id = w.language_id) ";
      $sql .= PapayaUtilString::escapeForPrintf(
        $this->_compileCondition($filter).$this->_compileOrderBy()
      );
      $parameters = [
        $databaseAccess->getTableName($this->_tableName),
        $databaseAccess->getTableName($this->_tableTermTranslations)
      ];
    } else {
      if (is_array($filter['glossary_id'])) {
        array_walk($filter['glossary_id'], 'intval');
        $glossaryFilter = 'glossary_id IN ('.implode(', ', $filter['glossary_id']).')';
      } else {
        $glossaryFilter = sprintf('glossary_id = %d', $filter['glossary_id']);
      }
      unset($filter['glossary_id']);

      $sql = "SELECT w.glossary_term_id, tt.glossary_term, tt.glossary_term_explanation, tt.glossary_term_modified, 
                     w.language_id, w.glossary_word_type, w.glossary_word, w.normalized, w.first_char 
                FROM %s AS w
               INNER JOIN %s AS tt ON (tt.glossary_term_id = w.glossary_term_id AND tt.language_id = w.language_id)
               WHERE 
                 w.glossary_term_id IN (
                   SELECT glossary_term_id FROM %s WHERE %s
                 ) ";
      $sql .= PapayaUtilString::escapeForPrintf(
        $this->_compileCondition($filter, 'AND').$this->_compileOrderBy()
      );
      $parameters = [
        $databaseAccess->getTableName($this->_tableName),
        $databaseAccess->getTableName($this->_tableTermTranslations),
        $databaseAccess->getTableName($this->_tableTermLinks),
        $glossaryFilter
      ];
    }
    return $this->_loadRecords($sql, $parameters, $limit, $offset, $this->_identifierProperties);
  }
}