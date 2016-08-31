<?php

class GlossaryContentTerm extends PapayaDatabaseRecordLazy {

  protected $_fields = [
    'id' => 'glossary_term_id'
  ];

  protected $_tableName = GlossaryContentTables::TABLE_TERMS;

  /**
   * @var GlossaryContentGlossaryTranslations
   */
  private $_translations;

  public function _createCallbacks() {
    $callbacks = parent::_createCallbacks();
    $callbacks->onBeforeDelete = function() {
      return $this->translations()->truncate(['id' => $this['id']]);
    };
    return $callbacks;
  }

  public function translations(GlossaryContentTermTranslations $translations = NULL) {
    if (isset($translations)) {
      $this->_translations = $translations;
    } elseif (NULL === $this->_translations) {
      $this->_translations = new GlossaryContentTermTranslations();
      $this->_translations->papaya($this->papaya());
    }
    return $this->_translations;
  }
}