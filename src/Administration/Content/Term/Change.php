<?php
/**
 * Add/save a glossary term.
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
 * Add/save a glossary term.
 *
 * @package Papaya-Library
 * @subpackage Administration
 */
class GlossaryAdministrationContentTermChange extends PapayaUiControlCommandDialog {

  /**
   * @var GlossaryContentTermTranslation
   */
  private $_translation;
  /**
   * @var GlossaryContentTerm
   */
  private $_term;

  public function translation(GlossaryContentTermTranslation $translation = NULL) {
    if (isset($translation)) {
      $this->_translation = $translation;
    } elseif (NULL === $this->_translation) {
      $this->_translation = new GlossaryContentTermTranslation();
      $this->_translation->papaya($this->papaya());
    }
    return $this->_translation;
  }

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
   * Create the add/edit dialog and assign callbacks.
   *
   * @return PapayaUiDialogDatabaseSave
   */
  public function createDialog() {
    $term = $this->term();
    $translation = $this->translation();
    $termId = $this->parameters()->get(
      'term_id', 0, new PapayaFilterInteger(1)
    );
    $filter = [
      'id' => $termId,
      'language_id' => $this->papaya()->administrationLanguage->id
    ];
    $loaded = $term->load(['id' => $termId]);
    if (!$translation->load($filter)) {
      $translation->key()->assign($filter);
    }
    $dialog = new PapayaUiDialogDatabaseSave($translation);
    $dialog->papaya($this->papaya());
    $dialog->caption = new PapayaUiStringTranslated($loaded ? 'Edit Term' : 'Add Term');
    $dialog->image = $this->papaya()->administrationLanguage->image;
    $dialog->parameterGroup($this->owner()->parameterGroup());
    $dialog->hiddenFields->merge(
      array(
        'mode' => 'terms',
        'cmd' => 'change',
        'term_id' => $termId,
        'language_id' => $this->papaya()->administrationLanguage->id
      )
    );
    $dialog->fields[] = $field = new PapayaUiDialogFieldInput(
      new PapayaUiStringTranslated('Term'), 'term'
    );
    $field->setMandatory(TRUE);
    $dialog->fields[] = $field = new PapayaUiDialogFieldInput(
      new PapayaUiStringTranslated('Synonyms'), 'synonyms'
    );
    $dialog->fields[] = $field = new PapayaUiDialogFieldInput(
      new PapayaUiStringTranslated('Abbreviations'), 'abbreviations'
    );
    $dialog->fields[] = $group = new PapayaUiDialogFieldGroup(
      new PapayaUiStringTranslated('Texts')
    );
    $group->fields[] = $field = new PapayaUiDialogFieldTextareaRichtext(
      new PapayaUiStringTranslated('Explanation'), 'explanation'
    );
    $group->fields[] = $field = new PapayaUiDialogFieldInput(
      new PapayaUiStringTranslated('Source'), 'source'
    );
    $group->fields[] = $field = new PapayaUiDialogFieldTextareaLines(
      new PapayaUiStringTranslated('Links'), 'links', 6, NULL, new PapayaFilterPcre('([^=]+=.+)')
    );
    $dialog->buttons[] = new PapayaUiDialogButtonSubmit(new PapayaUiStringTranslated('Save'));

    $dialog->callbacks()->onBeforeSave = function() use ($termId, $translation) {
      if ($termId < 1) {
        $term = new GlossaryContentTerm();
        $term->save();
        $termId = $translation['id'] = $term['id'];
        $translation->key()->assign(['id' => $termId]);
        $this->parameters()->set('term_id', $termId);
        $this->resetAfterSuccess(TRUE);
      }
      if ($termId < 1) {
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
        PapayaMessage::SEVERITY_INFO, 'Term saved.'
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