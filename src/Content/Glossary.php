<?php
class GlossaryContentGlossary extends PapayaDatabaseRecord  {

  protected $_fields = [
    'id' => 'glossary_id'
  ];

  protected $_tableName = GlossaryContentTables::TABLE_GLOSSARIES;

  /**
   * @var GlossaryContentGlossaryTranslations
   */
  private $_translations;

  public function _createCallbacks() {
    $callbacks = parent::_createCallbacks();
    $callbacks->onBeforeDelete = function() {
      return $this->translations()->delete(['id' => $this['id']]);
    };
    return $callbacks;
  }

  public function translations(GlossaryContentGlossaryTranslations $translations = NULL) {
    if (isset($translations)) {
      $this->_translations = $translations;
    } elseif (NULL === $this->_translations) {
      $this->_translations = new GlossaryContentGlossaryTranslations();
      $this->_translations->papaya($this->papaya());
    }
    return $this->_translations;
  }
}