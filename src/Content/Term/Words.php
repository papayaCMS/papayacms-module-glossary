<?php

class GlossaryContentTermWords extends PapayaDatabaseRecordsLazy {

  protected $_fields = [
    'id' => 'glossary_word_id',
    'type' => 'glossary_word_type',
    'term_id' => 'glossary_term_id',
    'language_id' => 'language_id',
    'word' => 'glossary_word',
    'normalized' => 'normalized'
  ];

  protected $_identifierProperties = ['id'];

  protected $_orderByProperties = ['id' => PapayaDatabaseInterfaceOrder::ASCENDING];

  protected $_tableName = GlossaryContentTables::TABLE_TERM_WORDS;
}