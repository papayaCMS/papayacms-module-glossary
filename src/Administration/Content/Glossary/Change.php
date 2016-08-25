<?php
/**
 * Add/save a glossary.
 *
 * @copyright 2016 by papaya Software GmbH - All rights reserved.
 * @link http://www.papaya-cms.com/
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License, version 2
 *
 * You can redistribute and/or modify this script under the terms of the GNU General Public
 * License (GPL) version 2, provided that the copyright and license notes, including these
 * lines, remain unmodified. papaya is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.
 *
 * @package Papaya-Modules
 * @subpackage Glossary
 * @version $Id: Change.php 39484 2014-03-03 11:21:06Z weinert $
 */

/**
 * Add/save a glossary.
 *
 * @package Papaya-Library
 * @subpackage Administration
 */
class GlossaryAdministrationContentGlossaryChange extends PapayaUiControlCommandDialog {

  /**
   * @var GlossaryContentGlossaryTranslation
   */
  private $_translation;

  public function translation(GlossaryContentGlossaryTranslation $translation = NULL) {
    if (isset($translation)) {
      $this->_translation = $translation;
    } elseif (NULL === $this->_translation) {
      $this->_translation = new GlossaryContentGlossaryTranslation();
      $this->_translation->papaya($this->papaya());
    }
    return $this->_translation;
  }

  /**
   * Create the add/edit dialog and assign callbacks.
   *
   * @return PapayaUiDialogDatabaseSave
   */
  public function createDialog() {
    $translation = $this->translation();
    $dialog = new PapayaUiDialogDatabaseSave($translation);
    $dialog->papaya($this->papaya());

    $dialog->caption = new PapayaUiStringTranslated('Glossary');
    $dialog->parameterGroup($this->owner()->parameterGroup());
    $dialog->hiddenFields->merge(
      array(
        'mode' => 'glossaries',
        'cmd' => 'change',
        'id' => $translation['id'],
        'language_id' => $translation['language_id']
      )
    );
    $dialog->fields[] = $field = new PapayaUiDialogFieldInput(
      new PapayaUiStringTranslated('Title'), 'title'
    );
    $field->setMandatory(TRUE);
    $dialog->fields[] = $field = new PapayaUiDialogFieldTextareaRichtext(
      new PapayaUiStringTranslated('Text'), 'text'
    );
    $dialog->buttons[] = new PapayaUiDialogButtonSubmit(new PapayaUiStringTranslated('Save'));

    $this->callbacks()->onExecuteSuccessful = array($this, 'handleExecutionSuccess');
    $this->callbacks()->onExecuteFailed = array($this, 'dispatchErrorMessage');

    return $dialog;
  }

  /**
   * Callback to dispatch a message to the user that the record was saved and trigger initial sync.
   */
  public function handleExecutionSuccess() {
    $this->papaya()->messages->dispatch(
      new PapayaMessageDisplayTranslated(
        PapayaMessage::SEVERITY_INFO, 'Glossary saved.'
      )
    );
  }

  /**
   * Callback to dispatch a message to the user that here was an input error.
   */
  public function dispatchErrorMessage($context, PapayaUiDialog $dialog) {
    $this->papaya()->messages->dispatch(
      new PapayaMessageDisplayTranslated(
        PapayaMessage::SEVERITY_ERROR,
        'Invalid input. Please check the fields "%s".',
        array(implode(', ', $dialog->errors()->getSourceCaptions()))
      )
    );
  }
}