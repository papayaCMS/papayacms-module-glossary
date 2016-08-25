<?php

class GlossaryContentGlossaryTranslations extends PapayaDatabaseRecordsLazy {

  protected $_fields = [
    'id' => 'glossary_id',
    'language_id' => 'language_id',
    'title' => 'glossary_title',
    'modified' => 'glossary_modified'
  ];

  protected $_orderByProperties = ['title'];

  protected $_identifierProperties = ['id', 'language_id'];

  protected $_tableName = GlossaryContentTables::TABLE_GLOSSARY_TRANSLATIONS;
}