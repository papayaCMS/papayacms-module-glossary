<?php

class GlossaryContentGlossaryTranslations extends PapayaDatabaseRecordsLazy {

  protected $_fields = [
    'id' => 'glossary_id',
    'language_id' => 'lng_id',
    'title' => 'glossary_title',
    'modified' => 'glossary_modified'
  ];

  protected $_orderByProperties = ['title'];

  protected $_identifierProperties = ['id', 'language_id'];

  protected $_tableName = GlossaryContentTables::TABLE_GLOSSARY_TRANSLATIONS;

  public function delete($filter) {
    $databaseAccess = $this->getDatabaseAccess();
    return (
      FALSE !== $databaseAccess->deleteRecord(
        $databaseAccess->getTableName($this->_tableName),
        $this->mapping()->mapPropertiesToFields($filter)
      )
    );
  }
}