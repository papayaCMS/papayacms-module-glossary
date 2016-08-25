<?php

class GlossaryContentGlossaries extends PapayaDatabaseRecordsLazy {

  protected $_fields = [
    'id' => 'g.glossary_id',
    'title' => 'gt.glossary_title'
  ];

  protected $_identifierProperties = ['id'];

  protected $_orderByProperties = ['id' => PapayaDatabaseInterfaceOrder::ASCENDING];

  protected $_tableGlossaries = GlossaryContentTables::TABLE_GLOSSARIES;
  protected $_tableGlossaryTranslations = GlossaryContentTables::TABLE_GLOSSARY_TRANSLATIONS;

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
    $sql = "SELECT g.glossary_id, gt.glossary_title
              FROM %s AS g
              LEFT JOIN %s AS gt ON (gt.glossary_id = g.glossary_id AND gt.lng_id = '%d')
                   ".$this->_compileCondition($filter)."
                   ".$this->_compileOrderBy();
    $parameters = array(
      $databaseAccess->getTableName($this->_tableGlossaries),
      $databaseAccess->getTableName($this->_tableGlossaryTranslations),
      $languageId
    );
    return $this->_loadRecords($sql, $parameters, $limit, $offset, 'id');
  }
}