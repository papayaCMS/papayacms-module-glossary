<?php

class GlossaryContentGlossaries extends PapayaDatabaseRecordsLazy {

  protected $_fields = [
    'id' => 'g.glossary_id',
    'language_id' => 'gt.lng_id',
    'title' => 'gt.glossary_title',
    'title_fallback' => 'title_fallback'
  ];

  protected $_identifierProperties = ['id'];

  protected $_orderByProperties = ['title' => PapayaDatabaseInterfaceOrder::ASCENDING];

  protected $_tableGlossaries = GlossaryContentTables::TABLE_GLOSSARIES;
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
    $sql = "SELECT g.glossary_id, gt.glossary_title, gt.lng_id, gtf.glossary_title title_fallback
              FROM %s AS g
              LEFT JOIN %s AS gt ON (gt.glossary_id = g.glossary_id AND gt.lng_id = '%d')
              LEFT JOIN %s AS gtf ON (gtf.glossary_id = g.glossary_id)
                   ".$this->_compileCondition($filter)."
                   ".$this->_compileOrderBy();
    $parameters = array(
      $databaseAccess->getTableName($this->_tableGlossaries),
      $databaseAccess->getTableName($this->_tableGlossaryTranslations),
      $languageId,
      $databaseAccess->getTableName($this->_tableGlossaryTranslations)
    );
    return $this->_loadRecords($sql, $parameters, $limit, $offset, $this->_identifierProperties);
  }
}