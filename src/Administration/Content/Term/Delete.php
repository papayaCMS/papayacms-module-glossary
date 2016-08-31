<?php

class GlossaryAdministrationContentTermDelete
  extends PapayaUiControlCommandDialog {

  /**
   * @var GlossaryContentTerm
   */
  private $_term;


  public function term(GlossaryContentTerm $term = NULL) {
    if (isset($term)) {
      $this->_term = $term;
    } elseif (NULL === $this->_term) {
      $this->_term = new GlossaryContentTerm();
      $this->_term->papaya($this->papaya());
    }
    return $this->_term;
  }

  /**
   * Create dialog and add fields for the dynamic values defined by the current theme values page
   *
   * @see PapayaUiControlCommandDialog::createDialog()
   * @return PapayaUiDialog
   */
  public function createDialog() {
    $termId = $this->parameters()->get('term_id', 0);
    if ($termId > 0) {
      $loaded = $this->term()->load($termId);
    } else {
      $loaded = FALSE;
    }
    $dialog = new PapayaUiDialogDatabaseDelete($this->term());
    $dialog->papaya($this->papaya());
    $dialog->caption = new PapayaUiStringTranslated('Delete term');
    if ($loaded) {
      $dialog->parameterGroup($this->parameterGroup());
      $dialog->parameters($this->parameters());
      $dialog->hiddenFields()->merge(
        array(
          'mode' => 'terms',
          'cmd' => 'delete',
          'term_id' => $termId
        )
      );
      $dialog->fields[] = new PapayaUiDialogFieldInformation(
        new PapayaUiStringTranslated('Delete term'),
        'places-trash'
      );
      $dialog->buttons[] = new PapayaUiDialogButtonSubmit(new PapayaUiStringTranslated('Delete'));
      $this->callbacks()->onExecuteSuccessful = function() {
        $this->papaya()->messages->dispatch(
          new PapayaMessageDisplayTranslated(
            PapayaMessage::SEVERITY_INFO,
            'Term deleted.'
          )
        );
      };
    } else {
      $dialog->fields[] = new PapayaUiDialogFieldMessage(
        PapayaMessage::SEVERITY_INFO, 'Term not found.'
      );
    }
    return $dialog;
  }
}