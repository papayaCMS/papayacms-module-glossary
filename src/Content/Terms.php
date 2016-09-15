<?php

class GlossaryContentTerms extends PapayaDatabaseRecordsLazy {

  protected $_fields = [
    'id' => 't.glossary_term_id',
    'language_id' => 'tt.language_id',
    'term' => 'tt.glossary_term',
    'term_fallback' => 'term_fallback',
    'modified' => 'tt.glossary_term_modified',
    'created' => 'tt.glossary_term_created',
    'explanation' => 'tt.glossary_term_explanation',
    'source' => 'tt.glossary_term_source',
    'links' => 'tt.glossary_term_links',
    'synonyms' => 'tt.glossary_term_synonyms',
    'abbreviations' => 'tt.glossary_term_abbreviations',
    'derivations' => 'tt.glossary_term_derivations'
  ];

  protected $_identifierProperties = ['id'];

  protected $_orderByProperties = [
    'term' => PapayaDatabaseInterfaceOrder::ASCENDING,
    'term_fallback' => PapayaDatabaseInterfaceOrder::ASCENDING,
    'id' => PapayaDatabaseInterfaceOrder::ASCENDING
  ];

  protected $_tableTerms = GlossaryContentTables::TABLE_TERMS;
  protected $_tableTermTranslations = GlossaryContentTables::TABLE_TERM_TRANSLATIONS;
  protected $_tableTermGlossaries = GlossaryContentTables::TABLE_TERM_GLOSSARY_LINKS;

  /**
   * Load pages defined by filter conditions.
   *
   * @param array $filter
   * @param NULL|integer $limit
   * @param NULL|integer $offset
   * @return bool
   */
  public function load($filter = array(), $limit = NULL, $offset = NULL) {
    $databaseAccess = $this->getDatabaseAccess();
    if (isset($filter['language_id'])) {
      $languageId = (int)$filter['language_id'];
      unset($filter['language_id']);
    } else {
      $languageId = 0;
    }
    if (isset($filter['glossary_id'])) {
      $glossaryId = (int)$filter['glossary_id'];
      unset($filter['glossary_id']);
      $sql = "SELECT 
                t.glossary_term_id, 
                tt.glossary_term, tt.language_id, 
                ttf.glossary_term term_fallback,
                tt.glossary_term_modified, tt.glossary_term_created, tt.glossary_term_explanation,
                tt.glossary_term_source, tt.glossary_term_links, 
                tt.glossary_term_synonyms, tt.glossary_term_abbreviations, tt.glossary_term_derivations
              FROM (%s AS t)
              LEFT JOIN %s AS tt ON (tt.glossary_term_id = t.glossary_term_id AND tt.language_id = '%d')
              LEFT JOIN %s AS ttf ON (ttf.glossary_term_id = t.glossary_term_id)
              WHERE t.glossary_term_id IN (SELECT glossary_term_id FROM %s WHERE glossary_id = %d)
                   ".PapayaUtilString::escapeForPrintf($this->_compileCondition($filter))."
                   ".$this->_compileOrderBy();
      $parameters = array(
        $databaseAccess->getTableName($this->_tableTerms),
        $databaseAccess->getTableName($this->_tableTermTranslations),
        $languageId,
        $databaseAccess->getTableName($this->_tableTermTranslations),
        $databaseAccess->getTableName($this->_tableTermGlossaries),
        $glossaryId
      );
    } else {
      $sql = "SELECT 
                t.glossary_term_id, 
                tt.glossary_term, tt.language_id, 
                ttf.glossary_term term_fallback,
                tt.glossary_term_modified, tt.glossary_term_created, tt.glossary_term_explanation,
                tt.glossary_term_source, tt.glossary_term_links, 
                tt.glossary_term_synonyms, tt.glossary_term_abbreviations, tt.glossary_term_derivations
              FROM (%s AS t)
              LEFT JOIN %s AS tt ON (tt.glossary_term_id = t.glossary_term_id AND tt.language_id = '%d')
              LEFT JOIN %s AS ttf ON (ttf.glossary_term_id = t.glossary_term_id)
                   ".PapayaUtilString::escapeForPrintf($this->_compileCondition($filter))."
                   ".$this->_compileOrderBy();
      $parameters = array(
        $databaseAccess->getTableName($this->_tableTerms),
        $databaseAccess->getTableName($this->_tableTermTranslations),
        $languageId,
        $databaseAccess->getTableName($this->_tableTermTranslations)
      );
    }
    return $this->_loadRecords($sql, $parameters, $limit, $offset, $this->_identifierProperties);
  }
}