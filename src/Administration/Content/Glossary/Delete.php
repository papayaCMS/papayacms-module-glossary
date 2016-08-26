<?php

class GlossaryAdministrationContentGlossaryDelete
  extends PapayaUiControlCommandDialog {
  /**
   * @var GlossaryContentGlossary
   */
  private $_glossary;

  public function glossary(GlossaryContentGlossaryTranslation $glossary = NULL) {
    if (isset($glossary)) {
      $this->_glossary = $glossary;
    } elseif (NULL === $this->_glossary) {
      $this->_glossary = new GlossaryContentGlossary();
      $this->_glossary->papaya($this->papaya());
    }
    return $this->_glossary;
  }

  /**
   * Create dialog and add fields for the dynamic values defined by the current theme values page
   *
   * @see PapayaUiControlCommandDialog::createDialog()
   * @return PapayaUiDialog
   */
  public function createDialog() {
    $glossaryId = $this->parameters()->get('glossary_id', 0);
    if ($glossaryId > 0) {
      $loaded = $this->glossary()->load($glossaryId);
    } else {
      $loaded = FALSE;
    }
    $dialog = new PapayaUiDialogDatabaseDelete($this->glossary());
    $dialog->papaya($this->papaya());
    $dialog->caption = new PapayaUiStringTranslated('Delete glossary');
    if ($loaded) {
      $dialog->parameterGroup($this->parameterGroup());
      $dialog->parameters($this->parameters());
      $dialog->hiddenFields()->merge(
        array(
          'mode' => 'glossaries',
          'cmd' => 'delete',
          'glossary_id' => $glossaryId
        )
      );
      $dialog->fields[] = new PapayaUiDialogFieldInformation(
        new PapayaUiStringTranslated('Delete glossary'),
        'places-trash'
      );
      $dialog->buttons[] = new PapayaUiDialogButtonSubmit(new PapayaUiStringTranslated('Delete'));
      $this->callbacks()->onExecuteSuccessful = function() {
        $this->papaya()->messages->dispatch(
          new PapayaMessageDisplayTranslated(
            PapayaMessage::SEVERITY_INFO,
            'Glossary deleted.'
          )
        );
      };
    } else {
      $dialog->fields[] = new PapayaUiDialogFieldMessage(
        PapayaMessage::SEVERITY_INFO, 'Glossary not found.'
      );
    }
    return $dialog;
  }
}