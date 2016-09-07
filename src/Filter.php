<?php

class GlossaryFilter
  extends
    PapayaObject
  implements
    PapayaPluginFilterContent,
    PapayaPluginEditable {

  const DOMAIN_CONNECTOR_GUID = '8ec0c5995d97c9c3cc9c237ad0dc6c0b';

  /**
   * @var PapayaPluginEditableContent
   */
  private $_content;

  /**
   * @var GlossaryContentGlossaries
   */
  private $_glossaries;

  /**
   * @see PapayaPluginEditable::content()
   * @param PapayaPluginEditableContent $content
   * @return PapayaPluginEditableContent
   */
  public function content(PapayaPluginEditableContent $content = NULL) {
    if (isset($content)) {
      $this->_content = $content;
    } elseif (NULL == $this->_content) {
      $this->_content = new PapayaPluginEditableContent();
      $this->_content->callbacks()->onCreateEditor = array($this, 'createEditor');
    }
    return $this->_content;
  }

  /**
   * @param object $callbackContext
   * @param PapayaPluginEditableContent $content
   * @return PapayaPluginEditor
   */
  public function createEditor(
    $callbackContext,
    PapayaPluginEditableContent $content
  ) {
    $editor = new PapayaAdministrationPluginEditorDialog($content);
    $editor->papaya($this->papaya());
    $dialog = $editor->dialog();
    $dialog->caption = new PapayaUiStringTranslated('Configure glossary data filter');
    $dialog->image = '';
    $dialog->fields[] = new PapayaUiDialogFieldInputPage(
      new PapayaUiStringTranslated('Glossary Page Id'),
      'glossary_page_id',
      NULL,
      TRUE
    );
    $glossaryOptions = [
      '0' => new PapayaUiStringTranslated('All')
    ];
    if ($this->papaya()->plugins->has(self::DOMAIN_CONNECTOR_GUID)) {
      $glossaryOptions['-1'] = new PapayaUiStringTranslated('Domain specific');
    }
    $dialog->fields[] = new PapayaUiDialogFieldSelectRadio(
      new PapayaUiStringTranslated('Glossary'),
      'glossary',
      new PapayaIteratorMultiple(
        PapayaIteratorMultiple::MIT_KEYS_ASSOC,
        $glossaryOptions,
        new PapayaIteratorCallback(
          $this->glossaries(),
          function($element) {
            if (!empty($element['title'])) {
              return $element['title'];
            } elseif (!empty($element['title_fallback'])) {
              return $element['title_fallback'];
            } elseif (empty($element['title'])) {
              return '[#'.$element['id'].']';
            }
          }
        )
      )
    );
    $dialog->fields[] = new PapayaUiDialogFieldSelectRadio(
      new PapayaUiStringTranslated('Reference Parameters'),
      'add_refpage',
      new PapayaUiStringTranslatedList(array('no', 'yes'))
    );
    return $editor;
  }

  public function glossaries(GlossaryContentGlossaries $glossaries = NULL) {
    if (isset($glossaries)) {
      $this->_glossaries = $glossaries;
    } elseif (NULL == $this->_glossaries) {
      $this->_glossaries = new GlossaryContentGlossaries();
      $this->_glossaries->papaya($this->papaya());
      $this->_glossaries->activateLazyLoad(
        [
          'language_id' => $this->papaya()->administrationLanguage->id
        ]
      );
    }
    return $this->_glossaries;
  }

  function prepare($content, $options = []) {
    // TODO: Implement prepare() method.
  }

  function applyTo($content) {
    // TODO: Implement applyTo() method.
  }

  function appendTo(PapayaXmlElement $parent) {
    // TODO: Implement appendTo() method.
  }

}