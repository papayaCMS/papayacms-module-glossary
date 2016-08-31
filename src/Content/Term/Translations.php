<?php

class GlossaryContentTermTranslations extends PapayaDatabaseRecordsLazy {

  protected $_fields = [
    'id' => 'glossary_term_id',
    'language_id' => 'language_id',
    'title' => 'glossary_term',
    'modified' => 'glossary_term_modified'
  ];

  protected $_orderByProperties = ['title'];

  protected $_identifierProperties = ['id', 'language_id'];

  protected $_tableName = GlossaryContentTables::TABLE_TERM_TRANSLATIONS;
}