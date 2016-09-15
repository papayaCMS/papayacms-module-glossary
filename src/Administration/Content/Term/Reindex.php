<?php

class GlossaryAdministrationContentTermReindex
  extends PapayaUiControlCommandDialog {

  /**
   * @var GlossaryContentTermWordIndex
   */
  private $_index;

  /**
   * Create dialog and add fields for the dynamic values defined by the current theme values page
   *
   * @see PapayaUiControlCommandDialog::createDialog()
   * @return PapayaUiDialog
   */
  public function createDialog() {
    $dialog = parent::createDialog();
    $dialog->caption = new PapayaUiStringTranslated('Rebuild');
    $dialog->parameterGroup($this->parameterGroup());
    $dialog->parameters($this->parameters());
    $dialog->hiddenFields()->merge(
      array(
        'mode' => 'terms',
        'cmd' => 'reindex'
      )
    );
    $dialog->fields[] = new PapayaUiDialogFieldInformation(
      new PapayaUiStringTranslated('Force term index rebuild?'),
      'status-dialog-confirmation'
    );
    $dialog->buttons[] = new PapayaUiDialogButtonSubmit(new PapayaUiStringTranslated('Rebuild'));
    $this->callbacks()->onExecuteSuccessful = function() {
      if (FALSE !== $this->index()->update()) {
        $this->papaya()->messages->dispatch(
          new PapayaMessageDisplayTranslated(
            PapayaMessage::SEVERITY_INFO, 'Index updated.'
          )
        );
      }
    };
    return $dialog;
  }

  public function index(GlossaryContentTermWordIndex $index = NULL) {
    if (isset($index)) {
      $this->_index = $index;
    } elseif (NULL === $this->_index) {
      $this->_index = new GlossaryContentTermWordIndex();
      $this->_index->papaya($this->papaya());
    }
    return $this->_index;
  }
}