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
  /**
   * @var GlossaryContentGlossary
   */
  private $_glossary;

  public function translation(GlossaryContentGlossaryTranslation $translation = NULL) {
    if (isset($translation)) {
      $this->_translation = $translation;
    } elseif (NULL === $this->_translation) {
      $this->_translation = new GlossaryContentGlossaryTranslation();
      $this->_translation->papaya($this->papaya());
    }
    return $this->_translation;
  }

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
   * Create the add/edit dialog and assign callbacks.
   *
   * @return PapayaUiDialogDatabaseSave
   */
  public function createDialog() {
    $glossary = $this->glossary();
    $translation = $this->translation();
    $glossaryId = $this->parameters()->get(
      'glossary_id', NULL, new PapayaFilterInteger(1)
    );
    $filter = [
      'id' => $glossaryId,
      'language_id' => $this->papaya()->administrationLanguage->id
    ];
    $loaded = $glossary->load(['id' => $glossaryId]);
    if (!$translation->load($filter)) {
      $translation->key()->assign($filter);
    }
    $dialog = new PapayaUiDialogDatabaseSave($translation);
    $dialog->papaya($this->papaya());
    $dialog->caption = new PapayaUiStringTranslated($loaded ? 'Edit Glossary' : 'Add Glossary');
    $dialog->image = $this->papaya()->administrationLanguage->image;
    $dialog->parameterGroup($this->owner()->parameterGroup());
    $dialog->hiddenFields->merge(
      array(
        'mode' => 'glossaries',
        'cmd' => 'change',
        'glossary_id' => $this->parameters()->get('glossary_id', $translation['id']),
        'language_id' => $this->papaya()->administrationLanguage->id
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

    $dialog->callbacks()->onBeforeSave = function() use ($glossaryId, $translation) {
      if ($glossaryId < 1) {
        $glossary = new GlossaryContentGlossary();
        $glossary->save();
        $glossaryId = $translation['id'] = $glossary['id'];
        $translation->key()->assign(['id' => $glossaryId]);
        $this->parameters()->set('glossary_id', $glossaryId);
        $this->resetAfterSuccess(TRUE);
      }
      if ($glossaryId < 1) {
        return FALSE;
      }
      return TRUE;
    };

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