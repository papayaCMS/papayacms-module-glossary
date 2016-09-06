<?php

class GlossaryAdministrationContentTermTranslationDelete
  extends PapayaUiControlCommandDialog {

  /**
   * @var GlossaryContentTermTranslation
   */
  private $_translation;


  public function translation(GlossaryContentTermTranslation $translation = NULL) {
    if (isset($translation)) {
      $this->_translation = $translation;
    } elseif (NULL === $this->_translation) {
      $this->_translation = new GlossaryContentTermTranslation();
      $this->_translation->papaya($this->papaya());
      $this->_translation->activateLazyLoad(
        [
          'id' => $this->parameters()->get('term_id', 0, new PapayaFilterInteger(1)),
          'language_id' => $this->parameters()->get(
            'language_id',
            $this->papaya()->administrationLanguage->id
          ),
          'offset' => $this->parameters()->get('offset', 0),
          'search-for' => $this->parameters()->get('search-for', ''),
          'glossary_id' => $this->parameters()->get('glossary_id', 0)
        ]
      );
    }
    return $this->_translation;
  }

  public function createCondition() {
    return new PapayaUiControlCommandConditionRecord($this->translation());
  }

  /**
   * Create dialog and add fields for the dynamic values defined by the current theme values page
   *
   * @see PapayaUiControlCommandDialog::createDialog()
   * @return PapayaUiDialog
   */
  public function createDialog() {
    $termId = $this->parameters()->get('term_id', 0);
    $languageId = $this->parameters()->get('language_id', $this->papaya()->administrationLanguage->id);
    if ($termId > 0) {
      $loaded = $this->translation()->load(['id' => $termId, 'language_id' => $languageId]);
    } else {
      $loaded = FALSE;
    }
    $dialog = new PapayaUiDialogDatabaseDelete($this->translation());
    $dialog->papaya($this->papaya());
    $dialog->caption = new PapayaUiStringTranslated('Delete term translation');
    if ($loaded) {
      $dialog->parameterGroup($this->parameterGroup());
      $dialog->parameters($this->parameters());
      $dialog->hiddenFields()->merge(
        array(
          'mode' => 'terms',
          'cmd' => 'delete-translation',
          'term_id' => $termId,
          'language_id' => $languageId
        )
      );
      $dialog->fields[] = new PapayaUiDialogFieldInformation(
        new PapayaUiStringTranslated('Delete term translation'),
        'places-trash'
      );
      $dialog->buttons[] = new PapayaUiDialogButtonSubmit(new PapayaUiStringTranslated('Delete'));
      $this->callbacks()->onExecuteSuccessful = function() {
        $this->papaya()->messages->dispatch(
          new PapayaMessageDisplayTranslated(
            PapayaMessage::SEVERITY_INFO,
            'Term translation deleted.'
          )
        );
      };
    } else {
      $dialog->fields[] = new PapayaUiDialogFieldMessage(
        PapayaMessage::SEVERITY_INFO, 'Term translation not found.'
      );
    }
    return $dialog;
  }
}