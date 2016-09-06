<?php

class GlossaryContentTermTranslations extends PapayaDatabaseRecordsLazy {

  protected $_fields = [
    'id' => 'glossary_term_id',
    'language_id' => 'language_id',
    'term' => 'glossary_term',
    'modified' => 'glossary_term_modified',
    'created' => 'glossary_term_created'
  ];

  protected $_orderByProperties = ['term' => PapayaDatabaseInterfaceOrder::ASCENDING];

  protected $_identifierProperties = ['language_id'];

  protected $_tableName = GlossaryContentTables::TABLE_TERM_TRANSLATIONS;
}