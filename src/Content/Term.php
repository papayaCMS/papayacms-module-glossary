<?php

class GlossaryContentTerm extends PapayaDatabaseRecordLazy {

  protected $_fields = [
    'id' => 'glossary_term_id'
  ];

  protected $_identifierProperties = ['id'];

  protected $_orderByProperties = ['id' => PapayaDatabaseInterfaceOrder::ASCENDING];

  protected $_tableName = GlossaryContentTables::TABLE_TERMS;
}