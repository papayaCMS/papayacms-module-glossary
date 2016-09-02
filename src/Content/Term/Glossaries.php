<?php

class GlossaryContentTermGlossaries extends PapayaDatabaseRecordsLazy {

  protected $_fields = [
    'id' => 'tl.glossary_id',
    'term_id' => 'tl.glossary_term_id',
    'title' => 'gt.glossary_title'
  ];

  protected $_orderByProperties = ['title' => PapayaDatabaseInterfaceOrder::ASCENDING];

  protected $_tableName = GlossaryContentTables::TABLE_TERM_GLOSSARY_LINKS;
  protected $_tableGlossaryTranslations = GlossaryContentTables::TABLE_GLOSSARY_TRANSLATIONS;

  /**
   * Load glossaries defined by filter conditions.
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
    $sql = "SELECT tl.glossary_id, gt.glossary_title
              FROM %s AS tl
              LEFT JOIN %s AS gt ON (gt.glossary_id = tl.glossary_id AND gt.lng_id = '%d')
                   ".$this->_compileCondition($filter)."
                   ".$this->_compileOrderBy();
    $parameters = array(
      $databaseAccess->getTableName($this->_tableName),
      $databaseAccess->getTableName($this->_tableGlossaryTranslations),
      $languageId
    );
    return $this->_loadRecords($sql, $parameters, $limit, $offset, NULL);
  }
}