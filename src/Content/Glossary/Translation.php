<?php

class GlossaryContentGlossaryTranslation extends PapayaDatabaseRecordLazy {

  protected $_fields = [
    'id' => 'glossary_id',
    'language_id' => 'lng_id',
    'title' => 'glossary_title',
    'text' => 'glossary_text'
  ];

  protected $_tableName = GlossaryContentTables::TABLE_GLOSSARY_TRANSLATIONS;

  protected function _createKey() {
    return new PapayaDatabaseRecordKeyFields($this, $this->_tableName, ['id', 'language_id']);
  }
}