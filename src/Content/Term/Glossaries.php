<?php

class GlossaryContentTermGlossaries extends PapayaDatabaseRecordsLazy {

  protected $_fields = [
    'id' => 'g.glossary_id',
    'term_id' => 'tl.glossary_term_id',
    'title' => 'gt.glossary_title'
  ];

  protected $_identifierProperties = ['id'];

  protected $_orderByProperties = ['title' => PapayaDatabaseInterfaceOrder::ASCENDING];

  protected $_tableGlossaries = GlossaryContentTables::TABLE_GLOSSARIES;
  protected $_tableGlossaryTranslations = GlossaryContentTables::TABLE_GLOSSARY_TRANSLATIONS;
  protected $_tableGlossaryTermLinks = GlossaryContentTables::TABLE_GLOSSARY_TERM_LINKS;

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
    if (isset($filter['term_id'])) {
      $termId = (int)$filter['term_id'];
      unset($filter['term_id']);
    } else {
      $termId = 0;
    }
    $sql = "SELECT g.glossary_id, gt.glossary_title
              FROM %s AS g
             INNER JOIN %s as tl ON (tl.term_id = '%d')
              LEFT JOIN %s AS gt ON (gt.glossary_id = g.glossary_id AND gt.lng_id = '%d')
                   ".$this->_compileCondition($filter)."
                   ".$this->_compileOrderBy();
    $parameters = array(
      $databaseAccess->getTableName($this->_tableGlossaries),
      $databaseAccess->getTableName($this->_tableGlossaryTermLinks),
      $termId,
      $databaseAccess->getTableName($this->_tableGlossaryTranslations),
      $languageId
    );
    return $this->_loadRecords($sql, $parameters, $limit, $offset, 'id');
  }
}