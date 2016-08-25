<?php

class GlossaryAdministrationNavigationTerms extends PapayaUiControlCommand {

  /**
   * @var PapayaUiDialog
   */
  private $_filterDialog = NULL;

  public function appendTo(PapayaXmlElement $parent) {
    $parent->append($this->filterDialog());
  }

  public function filterDialog(PapayaUiDialog $filterDialog = NULL) {
    if (isset($filterDialog)) {
      $this->_filterDialog = $filterDialog;
    } elseif (NULL === $this->_filterDialog) {
      $this->_filterDialog = $dialog = new PapayaUiDialog();
      $dialog->parameterGroup('filter');
      $dialog->options->dialogWidth = PapayaUiDialogOptions::SIZE_SMALL;
      $dialog->options->captionStyle = PapayaUiDialogOptions::CAPTION_NONE;
      $dialog->caption = new PapayaUiStringTranslated('Search');
      $dialog->fields[] = new PapayaUiDialogFieldInput(new PapayaUiStringTranslated('Term'), 'term');
      $dialog->fields[] = new PapayaUiDialogFieldSelect(
        new PapayaUiStringTranslated('Glossary'), 'glossary', [ 0 => 'All' ]
      );
    }
    return $this->_filterDialog;
  }

}