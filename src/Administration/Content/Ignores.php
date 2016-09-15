<?php

class GlossaryAdministrationContentIgnores extends PapayaUiControlCommandDialogDatabaseRecord  {

  private $_action = self::ACTION_SAVE;

  /**
   * GlossaryAdministrationContentIgnores constructor.
   * @param int $action
   */
  public function __construct($action) {
    parent::__construct(new GlossaryContentIgnore(), $this->_action = $action);
  }

  public function createDialog() {
    $dialog = parent::createDialog();
    $loaded = $this->record()->load(
      [
        'id' => $this->parameters()->get('ignore_word_id', 0),
        'language_id' => $this->parameters()->get('language_id', $this->papaya()->administrationLanguage->id)
      ]
    );
    $dialog->parameterGroup($this->parameterGroup());
    $dialog->hiddenFields()->merge(
      [
        'mode' => 'ignore-words',
        'cmd' => $this->_action == self::ACTION_DELETE ? 'delete' : 'change',
        'ignore_word_id' => $this->parameters()->get('ignore_word_id', 0),
        'language_id' => $this->parameters()->get('language_id', $this->papaya()->administrationLanguage->id)
      ]
    );
    switch ($this->_action) {
    case self::ACTION_DELETE :
      if (!$loaded) {
        return $dialog;
      }
      $dialog->caption = new PapayaUiStringTranslated('Delete');
      $dialog->fields[] = new PapayaUiDialogFieldInformation(
        new PapayaUiStringTranslated('Delete ignore word: "%s"', [$this->record()['word']]),
        'places-trash'
      );
      $dialog->buttons[] = new PapayaUiDialogButtonSubmit(new PapayaUiStringTranslated('Delete'));
      break;
    default :
      $dialog->caption = new PapayaUiStringTranslated($loaded ? 'Edit' : 'Add');
      $dialog->data()->merge($this->record());
      $dialog->fields[] = $field = new PapayaUiDialogFieldInput(
        new PapayaUiStringTranslated('Word'),
        'word'
      );
      $field->setMandatory(TRUE);
      $dialog->buttons[] = new PapayaUiDialogButtonSubmit(new PapayaUiStringTranslated('Save'));
      break;
    }
    return $dialog;
  }
}