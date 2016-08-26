<?php

class GlossaryContentTerms extends PapayaDatabaseRecordsLazy {

  protected $_fields = [
    'id' => 't.glossaryentry_id',
    'language_id' => 'tt.lng_id',
    'term' => 'tt.glossaryentry_term'
  ];

  protected $_identifierProperties = ['id'];

  protected $_orderByProperties = ['id' => PapayaDatabaseInterfaceOrder::ASCENDING];

  protected $_tableTerms = GlossaryContentTables::TABLE_TERMS;
  protected $_tableTermTranslations = GlossaryContentTables::TABLE_TERM_TRANSLATIONS;

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
    $sql = "SELECT 
                t.glossaryentry_id, 
                tt.glossaryentry_term, tt.lng_id, 
                ttf.glossaryentry_term fallback_title
              FROM %s AS t
              LEFT JOIN %s AS tt ON (tt.glossaryentry_id = t.glossaryentry_id AND tt.lng_id = '%d')
              LEFT JOIN %s AS ttf ON (ttf.glossaryentry_id = t.glossaryentry_id)
                   ".$this->_compileCondition($filter)."
                   ".$this->_compileOrderBy();
    $parameters = array(
      $databaseAccess->getTableName($this->_tableTerms),
      $databaseAccess->getTableName($this->_tableTermTranslations),
      $languageId,
      $databaseAccess->getTableName($this->_tableTermTranslations)
    );
    return $this->_loadRecords($sql, $parameters, $limit, $offset, 'id');
  }
}