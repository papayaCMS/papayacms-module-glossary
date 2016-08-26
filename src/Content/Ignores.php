<?php

class GlossaryContentIgnores extends PapayaDatabaseRecordsLazy {

  protected $_fields = [
    'id' => 'ignoreword_id',
    'language_id' => 'ignoreword_lngid',
    'word' => 'ignoreword'
  ];

  protected $_identifierProperties = ['id'];

  protected $_orderByProperties = ['word' => PapayaDatabaseInterfaceOrder::ASCENDING];

  protected $_tableName = GlossaryContentTables::TABLE_IGNORE_WORDS;
}