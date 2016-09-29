<?php

class GlossaryContentTermsExport extends PapayaDatabaseRecordsLazy {

  protected $_fields = [
    'language' => 'l.lng_short',
    'glossary_id' => 'tl.glossary_id',
    'glossary_title' => 'gt.glossary_title',
    'term_id' => 'tl.glossary_term_id',
    'term' => 'tt.glossary_term',
    'synonyms' => 'tt.glossary_term_synonyms',
    'abbreviations' => 'tt.glossary_term_abbreviations',
    'derivations' => 'tt.glossary_term_derivations',
    'modified' => 'tt.glossary_term_modified',
    'created' => 'tt.glossary_term_created',
    'source' => 'tt.glossary_term_source',
    'explanation' => 'tt.glossary_term_explanation',
    'links' => 'tt.glossary_term_links'
  ];

  protected $_orderByProperties = [
    'language' => PapayaDatabaseInterfaceOrder::ASCENDING,
    'glossary_title' => PapayaDatabaseInterfaceOrder::ASCENDING,
    'glossary_id' => PapayaDatabaseInterfaceOrder::ASCENDING,
    'term' => PapayaDatabaseInterfaceOrder::ASCENDING
  ];

  protected $_tableTermGlossaryLinks = GlossaryContentTables::TABLE_TERM_GLOSSARY_LINKS;
  protected $_tableTermTranslations = GlossaryContentTables::TABLE_TERM_TRANSLATIONS;
  protected $_tableGlossaryTranslations = GlossaryContentTables::TABLE_GLOSSARY_TRANSLATIONS;
  protected $_tableLanguages = PapayaContentTables::LANGUAGES;

  /**
   * Load terms defined by filter conditions.
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
      $sql = "SELECT 
              l.lng_short,
              tl.glossary_term_id, tl.glossary_id, gt.glossary_title,
              tt.glossary_term, tt.language_id,
              tt.glossary_term_modified, tt.glossary_term_created, tt.glossary_term_explanation,
              tt.glossary_term_source, tt.glossary_term_links, 
              tt.glossary_term_synonyms, tt.glossary_term_abbreviations, tt.glossary_term_derivations
             FROM (%s AS tl)
            INNER JOIN %s AS tt ON (tt.glossary_term_id = tl.glossary_term_id AND tt.language_id = '%d')
             LEFT JOIN %s AS gt ON (gt.glossary_id = tl.glossary_id AND gt.lng_id = tt.language_id)
             LEFT JOIN %s AS l ON (l.lng_id =  tt.language_id)
                 ".PapayaUtilString::escapeForPrintf($this->_compileCondition($filter))."
                 ".$this->_compileOrderBy();
      $parameters = array(
        $databaseAccess->getTableName($this->_tableTermGlossaryLinks),
        $databaseAccess->getTableName($this->_tableTermTranslations),
        $languageId,
        $databaseAccess->getTableName($this->_tableGlossaryTranslations),
        $databaseAccess->getTableName($this->_tableLanguages)
      );
    } else {
      $sql = "SELECT 
              l.lng_short,
              tl.glossary_term_id, tl.glossary_id, gt.glossary_title,
              tt.glossary_term, tt.language_id,
              tt.glossary_term_modified, tt.glossary_term_created, tt.glossary_term_explanation,
              tt.glossary_term_source, tt.glossary_term_links, 
              tt.glossary_term_synonyms, tt.glossary_term_abbreviations, tt.glossary_term_derivations
             FROM (%s AS tt)
             LEFT JOIN %s AS tl ON (tl.glossary_term_id = tt.glossary_term_id)
             LEFT JOIN %s AS gt ON (gt.glossary_id = tl.glossary_id AND gt.lng_id = tt.language_id)
             LEFT JOIN %s AS l ON (l.lng_id =  tt.language_id)
             WHERE tt.language_id = %d
                 ".PapayaUtilString::escapeForPrintf($this->_compileCondition($filter, 'AND'))."
                 ".$this->_compileOrderBy();
      $parameters = array(
        $databaseAccess->getTableName($this->_tableTermTranslations),
        $databaseAccess->getTableName($this->_tableTermGlossaryLinks),
        $databaseAccess->getTableName($this->_tableGlossaryTranslations),
        $databaseAccess->getTableName($this->_tableLanguages),
        $languageId
      );
    }
    return $this->_loadRecords($sql, $parameters, $limit, $offset, $this->_identifierProperties);
  }
}