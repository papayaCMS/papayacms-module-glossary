<?php

class GlossaryContentTermTranslation extends PapayaDatabaseRecordLazy {

  protected $_fields = [
    'id' => 'glossaryterm_id',
    'language_id' => 'language__id',
    'term' => 'glossary_term',
    'explanation' => 'glossary_term_explanation',
    'synonyms' => 'glossary_term_synonyms'
  ];

  protected $_identifierProperties = ['id', 'language_id'];

  protected $_orderByProperties = ['id' => PapayaDatabaseInterfaceOrder::ASCENDING];

  protected $_tableName = GlossaryContentTables::TABLE_TERM_TRANSLATIONS;
}