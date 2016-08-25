<?php

class GlossaryContentTerms extends PapayaDatabaseRecordsLazy {

  protected $_fields = [
    'id' => 'terms.glossaryentry_id',
    'term' => 'terms.glossaryentry_term'
  ];

  protected $_identifierProperties = ['id'];

  protected $_orderByProperties = ['id' => PapayaDatabaseInterfaceOrder::ASCENDING];
}