<?php

class GlossaryContentTermWords extends PapayaDatabaseRecordsLazy {

  protected $_fields = [
    'term_id' => 'glossary_term_id',
    'language_id' => 'language_id',
    'type' => 'glossary_word_type',
    'word' => 'glossary_word',
    'normalized' => 'normalized'
  ];

  protected $_identifierProperties = ['term_id'];

  protected $_orderByProperties = ['term_id' => PapayaDatabaseInterfaceOrder::ASCENDING];

  protected $_tableName = GlossaryContentTables::TABLE_TERM_WORDS;
}