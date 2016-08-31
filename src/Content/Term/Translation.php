<?php

class GlossaryContentTermTranslation extends PapayaDatabaseRecordLazy {

  protected $_fields = [
    'id' => 'glossary_term_id',
    'language_id' => 'language_id',
    'term' => 'glossary_term',
    'explanation' => 'glossary_term_explanation',
    'synonyms' => 'glossary_synonyms'
  ];

  protected $_tableName = GlossaryContentTables::TABLE_TERM_TRANSLATIONS;

  protected function _createKey() {
    return new PapayaDatabaseRecordKeyFields($this, $this->_tableName, ['id', 'language_id']);
  }
}