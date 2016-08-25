<?php
/**
* Module Glossary admin
*
* @package commercial
* @subpackage glossary
* @version $Id: papaya_glossary.php 6 2014-02-20 12:06:00Z SystemVCS $
*/

/**
* Basic glossary module
*/
require_once(dirname(__FILE__).'/base_glossary.php');

/**
* Module Glossary admin
*
* @package commercial
* @subpackage glossary
*/
class papaya_glossary extends base_glossary {

  /**
  * Dialog object: Add glossary
  * @var object $addGlossaryDialog base_dialog
  */
  var $addGlossaryDialog = NULL;

  /**
  * Dialog object: Edit glossary
  * @var object $editGlossaryDialog base_dialog
  */
  var $editGlossaryDialog = NULL;

  /**
  * Dialog object: Add entry
  * @var object $addEntryDialog base_dialog
  */
  var $addEntryDialog = NULL;

  /**
  * Dialog object: Edit entry
  * @var object $editEntryDialog base_dialog
  */
  var $editEntryDialog = NULL;

  /**
  * Dialog object: Add or edit ignoreword
  * @var object $ignoreWordDialog base_dialog
  */
  var $ignoreWordDialog = NULL;

  /**
  * Steps: Glossary entries per page
  * @var integer $steps
  */
  var $steps = 10;

  /**
  * Absolute count of glossary entries
  * @var integer $entriesAbsCount
  */
  var $entriesAbsCount = 0;

  /**
  * Holds number of total ignore words in list
  * @var integer $entriesAbsCount
  */
  var $ignoreWordsAbsCount = 0;

  /**
  * Steps: Ignorewords per page
  * @var integer $stepsIgnoreWords
  */
  var $stepsIgnoreWords = 10;

  /**
  * List with words to ignore
  * @var array $ignoreWordList
  */
  var $ignoreWordList = array();

  /**
  * Initialize parameters
  * Session parameters are mode, glossary_id, offset and patt (for search pattern)
  * Switching mode resets the offset and patt parameter
  */
  function initialize() {
    $this->sessionParamName = 'PAPAYA_SESS_'.$this->paramName;
    $this->initializeParams();

    $this->sessionParams = $this->getSessionValue($this->sessionParamName);

    $this->initializeSessionParam('mode', array('offset', 'patt'));
    $this->initializeSessionParam('offset');
    $this->initializeSessionParam('patt');

    $this->initializeSessionParam('glossary_id');
    $this->setSessionValue($this->sessionParamName, $this->sessionParams);
  }

  /**
  * Executes commands sent by user and adds messages and information
  * Mode 0 (default) = Glossaries and entries / Mode 1 = Ignore words
  */
  function execute() {
    include_once(PAPAYA_INCLUDE_PATH.'system/base_language_select.php');
    $this->lngSelect = base_language_select::getInstance();

    if (isset($this->params['cmd']) && $this->params['cmd'] == 'fix_normalized') {
      if ($this->module->hasPerm(5)) {
        $this->fixNormalized();
      } else {
        $this->addMsg(
          MSG_WARNING,
          $this->_gt('You don\'t have the permission to fix the sorting of glossary entries.'));
      }
    }

    if (!isset($this->params['mode'])) {
      $this->params['mode'] = 0;
    }
    switch ($this->params['mode']) {
    case 1:
      $this->loadIgnoreWordList($this->params['offset']);
      if (isset($this->params['search']) && $this->ignoreWordsAbsCount == 0) {
        $this->addMsg(MSG_INFO, $this->_gt('No entries found.'));
      }
      if (!isset($this->params['cmd'])) {
        $this->params['cmd'] = '';
      }
      switch ($this->params['cmd']) {
      case 'ignoreword_delete':
        if ($this->module->hasPerm(4)) {
          if (isset($this->params['ignoreword_id']) &&
              $this->params['ignoreword_id'] > 0 &&
              isset($this->params['confirm_delete']) &&
              $this->params['confirm_delete']) {
            if ($this->deleteIgnoreWord($this->params['ignoreword_id'])) {
              $this->addMsg(MSG_INFO, $this->_gt('Entry deleted.'));
              unset($this->params['cmd']);
              unset($this->params['ignoreword_id']);
              $this->loadIgnoreWordList();
            } else {
              $this->addMsg(MSG_ERROR, $this->_gt('Database error.'));
            }
          }
        }
        break;
      case 'ignoreword_add':
        if ($this->module->hasPerm(4)) {
          if (isset($this->params['confirm_save']) && $this->params['confirm_save'] &&
              isset($this->params['ignoreword'])) {
            if (isset($this->params['ignoreword']) &&
                strlen($this->params['ignoreword']) > $this->tokenMinLength) {
              if (!$this->ignoreWordExists($this->params['ignoreword'])) {
                $this->initializeIgnoreWordDialog();
                if ($this->ignoreWordDialog->checkDialogInput()) {
                  if ($newId = $this->addIgnoreWord()) {
                    $this->addMsg(MSG_INFO, $this->_gt('Word added to ignoreword list.'));
                    unset($this->ignoreWordDialog);
                    $this->initializeIgnoreWordDialog(FALSE);
                    $this->loadIgnoreWordList();
                  } else {
                    $this->addMsg(MSG_ERROR, $this->_gt('Database error.'));
                  }
                }
              } else {
                $this->addMsg(MSG_ERROR, $this->_gt('Word exists already!'));
              }
            } else {
              $this->addMsg(MSG_ERROR, $this->_gt('Word is too short.'));
            }
          }
        }
        break;
      case 'ignoreword_edit':
        if ($this->module->hasPerm(4)) {
          if (isset($this->params['confirm_save']) && $this->params['confirm_save'] &&
              isset($this->params['ignoreword'])) {
            if (!$this->ignoreWordExists($this->params['ignoreword'])) {
              $this->initializeIgnoreWordDialog();
              if ($this->ignoreWordDialog->checkDialogInput()) {
                if ($this->updateIgnoreWord()) {
                  $this->addMsg(MSG_INFO, $this->_gt('Word modified.'));
                  $this->loadIgnoreWordList();
                } else {
                  $this->addMsg(MSG_ERROR, $this->_gt('Database error.'));
                }
              }
            } else {
              $this->addMsg(MSG_ERROR, $this->_gt('Word exists already.'));
            }
          }
        }
        break;
      }
      break;
    default:
      $this->loadGlossaries($this->lngSelect->currentLanguageId);
      $this->loadGlossaryEntries($this->lngSelect->currentLanguageId);
      if (isset($this->params['search']) && count($this->glossaryEntries) == 0) {
        $this->addMsg(MSG_INFO, $this->_gt('No entries found.'));
        $this->params['cmd'] = 'add_new_entry';
      } elseif (!isset($this->params['glossary_id']) ||
          $this->params['glossary_id'] == 0) {
        $this->params['cmd'] = 'add_new_gloss';
      } elseif (!isset($this->params['cmd']) && !isset($this->params['entry_id']) &&
          isset($this->params['glossary_id']) && $this->params['glossary_id'] > 0) {
        $this->params['cmd'] = 'edit_gloss';
      }
      if (!isset($this->params['cmd'])) {
        $this->params['cmd'] = '';
      }
      switch ($this->params['cmd']) {
      //del_trans also handles the deletion of glossary translations,
      //not only entry translations. so you don't need to look for del_gloss_trans
      case 'del_trans':
        if (isset($this->params['confirm_del_trans']) &&
            $this->params['confirm_del_trans'] == 1) {
          $translationDeleted = FALSE;
          if (isset($this->params['entry_id']) &&
              isset($this->glossaryEntries[$this->params['entry_id']]) &&
              isset($this->params['entry_lng_id'])) {
            if ($this->module->hasPerm(3)) {
              $type = 'Entry';
              $lngId = $this->params['entry_lng_id'];
              if (
                  $this->deleteTranslation(
                   'entry', $this->params['entry_id'], $this->params['entry_lng_id']
                  )
                 ) {
                $translationDeleted = TRUE;
              }
            } else {
              //Adding a break in order to avoid the output of
              //an info or error dialog when the needed permisson is not set.
              break;
            }
          } elseif (isset($this->params['glossary_id']) &&
                    isset($this->glossaries[$this->params['glossary_id']])) {
            if ($this->module->hasPerm(2)) {
              $type = 'Glossary';
              if (isset($this->params['glossary_lng_id']) && $this->params['glossary_lng_id']) {
                  $lngId = $this->params['glossary_lng_id'];
              } else {
                $lngId = $this->lngSelect->currentLanguageId;
              }
              if ($this->deleteTranslation('glossary', $this->params['glossary_id'], $lngId)) {
                $translationDeleted = TRUE;
              }
            } else {
              //Adding a break in order to avoid the output of
              //an info or error dialog when the needed permisson is not set.
              break;
            }
          }
          if ($translationDeleted) {
            $this->addMsg(
              MSG_INFO,
              sprintf(
                $this->_gt('%s deleted for language "%s" (%s).'),
                papaya_strings::escapeHTMLChars($this->_gt($type)),
                $this->lngSelect->languages[$lngId]['lng_title'],
                $this->lngSelect->languages[$lngId]['lng_short']
              )
            );
          } else {
            $this->addMsg(
              MSG_ERROR,
              sprintf(
                $this->_gt('%s could not be deleted for language "%s" (%s).'),
                $type,
                $this->lngSelect->languages[$lngId]['lng_title'],
                $this->lngSelect->languages[$lngId]['lng_short']
              )
            );
          }
        }
        break;
      case 'add_new_gloss':
        unset($this->params['glossary_id']);
      case 'add_gloss':
        if ($this->module->hasPerm(2)) {
          $this->initializeAddGlossaryDialog();
          if (!empty($this->params['confirm_add_gloss']) &&
              $this->addGlossaryDialog->checkDialogInput()) {
            if (!$this->glossaryTitleExists($this->params['glossary_title'])) {
              if ($this->addGlossary()) {
                $this->addMsg(MSG_INFO, $this->_gt('Glossary added.'));
                $this->logMsg(
                  MSG_INFO,
                  PAPAYA_LOGTYPE_MODULES,
                  sprintf(
                    'Glossary "%s" created.',
                    papaya_strings::escapeHTMLChars($this->params['glossary_title'])
                  )
                );
                unset($this->addGlossaryDialog);
                if (isset($this->params['glossary_id']) &&
                    ($this->params['glossary_id'] > 0)) {
                  $this->params['cmd'] = "edit_gloss";
                  $this->initializeEditGlossaryDialog();
                } else {
                  $this->initializeAddGlossaryDialog(FALSE);
                }
              } else {
                $this->addMsg(MSG_ERROR, $this->_gt('Glossary could not be added.'));
              }
            } else {
              $this->addMsg(MSG_ERROR, $this->_gt('Glossary exists already.'));
            }
          }
        }
        break;
      case 'edit_gloss':
        if ($this->module->hasPerm(2)) {
          $this->initializeEditGlossaryDialog();
          if (isset($this->params['confirm_edit_gloss']) &&
              $this->params['confirm_edit_gloss'] &&
              $this->editGlossaryDialog->checkDialogInput()) {
            $glossaryTitleExists = $this->glossaryTitleExists(
              $this->params['glossary_title'],
              isset($this->editGlossaryDialog->data['glossary_old_title']) ?
                $this->editGlossaryDialog->data['glossary_old_title'] : NULL
            );
            if (!$glossaryTitleExists) {
              if ($this->updateGlossary()) {
                $this->addMsg(MSG_INFO, $this->_gt('Glossary updated.'));
                $this->logMsg(
                  MSG_INFO,
                  PAPAYA_LOGTYPE_MODULES,
                  sprintf(
                    'Glossary "%s" modified.',
                    papaya_strings::escapeHTMLChars($this->params['glossary_title'])
                  )
                );
              } else {
                $this->addMsg(
                  MSG_ERROR,
                  $this->_gt('Glossary could not be updated.')
                );
              }
            } else {
              $this->addMsg(MSG_ERROR, $this->_gt('Glossary exists already.'));
            }
          }
        }
        break;
      case 'del_gloss':
        if ($this->module->hasPerm(2)) {
          if (isset($this->params['confirm_del_gloss']) &&
              $this->params['confirm_del_gloss'] &&
              isset($this->params['glossary_id']) &&
              isset($this->glossaries[$this->params['glossary_id']])) {
            $glossaryName = $this->glossaries[$this->params['glossary_id']]['glossary_title'];
            if ($this->deleteGlossary($this->params['glossary_id'])) {
              $this->addMsg(MSG_INFO, $this->_gt('Glossary deleted.'));
              $this->logMsg(
                MSG_INFO,
                PAPAYA_LOGTYPE_MODULES,
                sprintf('Glossary "%s" deleted.', $glossaryName)
              );
            } else {
              $this->addMsg(MSG_ERROR, $this->_gt('Glossary could not be deleted.'));
            }
          }
        }
        break;
      case 'add_new_entry':
        unset($this->params['entry_id']);
      case 'add_entry':
        if ($this->module->hasPerm(3)) {
          $this->initializeAddEntryDialog();
          if (isset($this->params['confirm_add_entry']) &&
              $this->params['confirm_add_entry'] &&
              $this->addEntryDialog->checkDialogInput()) {
            $firstToken = $this->getTokens($this->params['glossaryentry_term'], TRUE);
            if (!$this->ignoreWordExists($firstToken)) {
              if (!$this->entryTermExists($this->params['glossaryentry_term'])) {
                if ($this->addEntry($this->addEntryDialog->data)) {
                  $this->addMsg(MSG_INFO, $this->_gt('Entry added.'));
                  $this->logMsg(
                    MSG_INFO,
                    PAPAYA_LOGTYPE_MODULES,
                    sprintf(
                      'Glossary entry "%s" added',
                      papaya_strings::escapeHTMLChars($this->params['glossaryentry_term'])
                    )
                  );
                  unset($this->addEntryDialog);
                  if (isset($this->params['entry_id']) &&
                      ($this->params['entry_id'] > 0)) {
                    $this->params['cmd'] = "edit_entry";
                    $this->initializeEditEntryDialog();
                  } else {
                    $this->initializeAddEntryDialog(FALSE);
                  }
                } else {
                  $this->addMsg(MSG_ERROR, $this->_gt('Entry could not be added.'));
                }
              } else {
                $this->addMsg(
                  MSG_ERROR,
                  $this->_gt('Glossary entry exists already.')
                );
              }
            } else {
              $this->addMsg(
                MSG_ERROR,
                $this->_gt(
                  sprintf(
                    'Error in term: "%s" exists in ignore words list.',
                    papaya_strings::strtolower($firstToken)
                  )
                )
              );
            }
          }
        }
        break;
      case 'edit_entry':
        if ($this->module->hasPerm(3)) {
          $this->initializeEditEntryDialog();
          if (isset($this->params['confirm_edit_entry']) &&
              $this->params['confirm_edit_entry'] &&
              $this->editEntryDialog->checkDialogInput()) {
            $firstToken = $this->getTokens($this->params['glossaryentry_term'], TRUE);
            if (!$this->ignoreWordExists($firstToken)) {
              $entryTermExists = $this->entryTermExists(
                $this->params['glossaryentry_term'],
                isset($this->editEntryDialog->data['glossaryentry_old_term']) ?
                  $this->editEntryDialog->data['glossaryentry_old_term'] : NULL
              );
              if (!$entryTermExists) {
                if ($this->updateEntry($this->editEntryDialog->data)) {
                  $this->addMsg(MSG_INFO, $this->_gt('Entry updated.'));
                  $this->logMsg(
                    MSG_INFO,
                    PAPAYA_LOGTYPE_MODULES,
                    sprintf(
                      'Glossary entry "%s" updated',
                      papaya_strings::escapeHTMLChars($this->params['glossaryentry_term'])
                    )
                  );
                } else {
                  $this->addMsg(
                    MSG_INFO,
                    $this->_gt('Entry couldn\'t or doesn\'t need to be updated.')
                  );
                }
              } else {
                $this->addMsg(
                  MSG_ERROR,
                  $this->_gt('Glossary entry exists already.')
                );
              }
            } else {
              $this->addMsg(
                MSG_ERROR,
                $this->_gt(
                  sprintf(
                    'Error in term: "%s" exists in ignore words list.',
                    papaya_strings::strtolower($firstToken)
                  )
                )
              );
            }
          }
        }
        break;
      case 'del_entry':
        if ($this->module->hasPerm(3)) {
          if (isset($this->params['confirm_del_entry']) && isset($this->params['entry_id'])
              && isset($this->glossaryEntries[$this->params['entry_id']])) {
            $glossaryEntryName =
              $this->glossaryEntries[$this->params['entry_id']]['glossaryentry_term'];
            if ($this->deleteEntry($this->params['entry_id'])) {
              $this->addMsg(MSG_INFO, $this->_gt('Entry deleted.'));
              $this->logMsg(
                MSG_INFO,
                PAPAYA_LOGTYPE_MODULES,
                sprintf(
                  'Glossary entry "%s" deleted.',
                  papaya_strings::escapeHTMLChars($glossaryEntryName)
                )
              );
            } else {
              $this->addMsg(MSG_ERROR, $this->_gt('Entry couldn\'t be deleted.'));
            }
          }
        }
        break;
      }
    }
  }

  /**
  * Generates XML for admin page
  * Mode 0 (default) = Glossaries and entries / Mode 1 = Ignore words
  */
  function getXML() {
    $this->layout->addMenu(sprintf('<menu>%s</menu>'.LF, $this->getButtonXML()));

    if (!isset($this->params['mode'])) {
        $this->params['mode'] = 0;
    }
    switch ($this->params['mode']) {
    case 1:
      $this->layout->addLeft($this->getXMLIgnoreWordList());
      if (!isset($this->params['cmd'])) {
          $this->params['cmd'] = '';
      }
      switch ($this->params['cmd']) {
      case 'ignoreword_delete':
        if ($this->module->hasPerm(4)) {
          if (isset($this->params['ignoreword_id']) &&
              $this->params['ignoreword_id'] > 0) {
            $this->layout->add($this->getIgnoreWordDeleteDialog());
          }
        }
        break;
      case 'ignoreword_add':
      case 'ignoreword_edit':
      default:
        if ($this->module->hasPerm(4)) {
          $this->getIgnoreWordDialog();
        }
        break;
      }
      break;
    default:
      $this->setGlossaryEditDialogXML($this->params['cmd']);
      // xml glossary entries list
      if (isset($this->params['glossary_id']) &&
          $this->params['glossary_id'] > 0 &&
          isset($this->glossaries[$this->params['glossary_id']])) {
        $this->layout->addLeft($this->getXMLEntriesList());
        if (isset($this->params['entry_id']) &&
            isset($this->glossaryEntries[$this->params['entry_id']])) {
          $this->layout->addRight($this->getEntryInfo($this->params['entry_id']));
        } else {
          $this->layout->addRight($this->getGlossaryInfo());
        }
      } else {
        $this->layout->addLeft($this->getGlossariesList());
      }
    }
  }

  /**
  * Generates edit dialogs for each glossary edit use case.
  *
  * For each given command parameter, this method calls a method that creates
  * the corresponding xml for the edit dialog.
  *
  * @param mixed $cmd
  */
  function setGlossaryEditDialogXML($cmd = NULL) {
    switch ($cmd) {
    case 'add_new_gloss':
    case 'add_gloss':
      if ($this->module->hasPerm(2)) {
        $this->layout->add($this->getAddGlossaryDialog());
      }
      break;
    case 'edit_gloss':
      if ($this->module->hasPerm(2)) {
        if (isset($this->params['glossary_id']) &&
            isset($this->glossaries[$this->params['glossary_id']])) {
          if (isset($this->glossaries[$this->params['glossary_id']]['lng_id']) &&
              (
                $this->glossaries[$this->params['glossary_id']]['lng_id'] !=
                  $this->lngSelect->currentLanguageId
              )
             ) {
            $this->layout->add($this->getAddLngGlossaryDialog());
          } else {
            $this->layout->add($this->getEditGlossaryDialog());
          }
        }
      }
      break;
    case 'del_gloss':
      if ($this->module->hasPerm(2)) {
        if (!@$this->params['confirm_del_gloss'] &&
            isset($this->params['glossary_id']) &&
            isset($this->glossaries[$this->params['glossary_id']])) {
          $this->layout->add($this->getDelGlossaryDialog());
        }
      }
      break;
    case 'del_gloss_trans':
      // delete translation of glossaries
      if ($this->module->hasPerm(2)) {
        if (empty($this->params['confirm_del_trans'])) {
          if (isset($this->params['glossary_id']) &&
              isset($this->params['glossary_lng_id']) &&
              isset($this->glossaries[$this->params['glossary_id']])) {
            $this->layout->add(
              $this->getDelTranslationDialog(
              'glossary',
              $this->params['glossary_id'],
              $this->params['glossary_lng_id'])
            );
          }
        }
      }
      break;
    case 'add_new_entry':
    case 'add_entry':
      if ($this->module->hasPerm(3)) {
        $this->layout->add($this->getAddEntryDialog());
      }
      break;
    case 'edit_entry':
      if ($this->module->hasPerm(3)) {
        if (isset($this->params['entry_id']) &&
            isset($this->glossaryEntries[$this->params['entry_id']]) &&
            (
             !isset($this->glossaryEntries[$this->params['entry_id']]['lng_id']) ||
             (
               $this->glossaryEntries[$this->params['entry_id']]['lng_id'] !=
                $this->lngSelect->currentLanguageId
             )
            )
           ) {
          $this->layout->add($this->getAddLngEntryDialog());
        } else {
          $this->layout->add($this->getEditEntryDialog());
        }
      }
      break;
    case 'del_entry':
      if ($this->module->hasPerm(3)) {
        if (!@$this->params['confirm_del_entry'] &&
            isset($this->params['entry_id']) &&
            isset($this->glossaryEntries[$this->params['entry_id']])) {
          $this->layout->add($this->getDelEntryDialog());
        }
      }
      break;
      // delete translation for entries
    case 'del_trans':
      if (!@$this->params['confirm_del_trans']) {
        if (isset($this->params['entry_id']) &&
            isset($this->glossaryEntries[$this->params['entry_id']]) &&
            isset($this->params['entry_lng_id'])) {
          $this->layout->add(
            $this->getDelTranslationDialog(
              'entry', $this->params['entry_id'], $this->params['entry_lng_id']
            )
          );
        }
      }
      break;
    default:
      if ($this->module->hasPerm(3)) {
        if (isset($this->params['entry_id']) &&
            isset($this->glossaryEntries[$this->params['entry_id']]) &&
            count($this->glossaryEntries[$this->params['entry_id']]) > 0) {
          if (!$this->glossaryEntries[$this->params['entry_id']]['lng_id'] ==
                $this->lngSelect->currentLanguageId) {
            $this->layout->add($this->getAddLngEntryForm());
          }
        }
      }
    }
  }

  /**
  * Generates menubar with buttons
  *
  * @see getXML()
  * @return string as xml
  */
  function getButtonXML() {
    include_once(PAPAYA_INCLUDE_PATH.'system/base_btnbuilder.php');
    $this->menubar = new base_btnbuilder;
    $this->menubar->images = $this->images;

    if (isset($this->menubar) && is_object($this->menubar)) {
      if ($this->module->hasPerm(4)) {
        $btnSelected = (
            !isset($this->params['mode']) ||
            (isset($this->params['mode']) && $this->params['mode'] == 0)
          ) ? TRUE : FALSE;
        $this->menubar->addButton(
          'Glossaries',
          $this->getLink(array('mode' => '0')),
          'categories-view-list',
          '',
          $btnSelected
        );
        $btnSelected = (isset($this->params['mode']) && $this->params['mode'] == 1) ? TRUE : FALSE;
        $this->menubar->addButton(
          'Ignore words',
          $this->getLink(array('mode' => '1')),
          'items-page-ignoreword',
          '',
          $btnSelected
        );
        if ($this->module->hasPerm(5)) {
          //This function fixes normalized entries, but since the user will never
          //know what a normalized entry is, we simply call it "sorting". When the
          //sorting is broken, the user will just need to klick on this button.
          $this->menubar->addButton(
            'Fix sorting',
            $this->getLink(array('cmd' => 'fix_normalized')),
            'items-option'
          );
        }
      }

      switch (@$this->params['mode']) {
      case 1:
        if ($this->module->hasPerm(4)) {
          $this->menubar->addButton(
            'Add word',
            $this->getLink(
              array('cmd' => 'ignoreword_add', 'ignoreword_id' => 0)
            ),
            'actions-page-ignoreword-add'
          );
          if (isset($this->params['ignoreword_id']) &&
              isset($this->ignoreWordList[$this->params['ignoreword_id']])) {
            $this->menubar->addButton(
              'Delete word',
              $this->getLink(
                array(
                  'cmd' => 'ignoreword_delete',
                  'ignoreword_id' => (int)$this->params['ignoreword_id']
                )
              ),
              'actions-page-ignoreword-delete'
            );
          }
        }
        break;
      default:
        if ($this->module->hasPerm(2)) {
          $this->menubar->addSeperator();
          $this->menubar->addButton(
            'Add glossary',
            $this->getLink(array('cmd' => 'add_new_gloss')),
            'actions-folder-add',
            '',
            FALSE
          );
          if (!empty($this->params['glossary_id'])) {
            $this->menubar->addButton(
              'Delete glossary',
              $this->getLink(array('cmd' => 'del_gloss')),
              'actions-folder-delete',
              'Delete selected glossary',
              FALSE
            );
          }
        }

        if ($this->module->hasPerm(3) && !empty($this->params['glossary_id'])) {
          $this->menubar->addSeperator();
          $this->menubar->addButton(
            'Add entry',
            $this->getLink(array('cmd' => 'add_new_entry')),
            'actions-page-add',
            '',
            FALSE
          );
          if (!empty($this->params['entry_id']) &&
              isset($this->glossaryEntries[$this->params['entry_id']])) {
            $linkParams = array('cmd' => 'del_entry',
              'entry_id' => $this->params['entry_id']);
            $this->menubar->addButton(
              'Delete entry',
              $this->getLink($linkParams),
              'actions-page-delete',
              'Delete selected entry',
              FALSE
            );
          }
        }
      }
      return $this->menubar->getXML().' ';
    }
    return '';
  }

  /**
  * Initializes dialog to add or edit ignore words
  */
  function initializeIgnoreWordDialog($loadParams = TRUE) {
    if (!(isset($this->ignoreWordDialog) && is_object($this->ignoreWordDialog))) {
      if (isset($this->params['ignoreword_id']) &&
          isset($this->ignoreWordList[$this->params['ignoreword_id']])) {
        $data = $this->ignoreWordList[$this->params['ignoreword_id']];
        $hidden = array(
          'cmd' => 'ignoreword_edit',
          'ignoreword_id' => $data['ignoreword_id'],
          'confirm_save' => 1
        );
        $title = 'Edit';
        $btnTitle = 'Save';
      } else {
        $data = array();
        $hidden = array(
          'cmd' => 'ignoreword_add',
          'ignoreword_id' => 0,
          'confirm_save' => 1
        );
        $title = 'Add';
        $btnTitle = 'Add';
      }

      $fields = array(
        'ignoreword' => array('Word', '~^[^'.$this->nonWordChars.']+$~',
          TRUE, 'input', '30', '')
      );

      include_once(PAPAYA_INCLUDE_PATH.'system/base_dialog.php');
      $this->ignoreWordDialog = new base_dialog($this, $this->paramName, $fields, $data, $hidden);
      $this->ignoreWordDialog->dialogTitle = $this->_gt($title);
      $this->ignoreWordDialog->buttonTitle = $btnTitle;
      $this->ignoreWordDialog->baseLink = $this->baseLink;
      if ($loadParams) {
        $this->ignoreWordDialog->loadParams();
      }
    }
  }

  /**
  * Builds dialog to add and edit ignore words
  */
  function getIgnoreWordDialog() {
    $this->initializeIgnoreWordDialog();
    $this->layout->add($this->ignoreWordDialog->getDialogXML());
  }

  /**
  * Builds dialog to delete ignoreword (confirmation)
  */
  function getIgnoreWordDeleteDialog() {
    $hidden = array(
      'cmd' => 'ignoreword_delete',
      'ignoreword_id' => $this->params['ignoreword_id'],
      'confirm_delete' => 1,
    );

    $msg = sprintf($this->_gt('Delete word?'));

    include_once(PAPAYA_INCLUDE_PATH.'system/base_msgdialog.php');
    $dialog = new base_msgdialog($this, $this->paramName, $hidden, $msg, 'question');
    $dialog->buttonTitle = 'Delete';
    return $dialog->getMsgDialog();
  }

  /**
  * Gets xml list with words to ignore
  *
  * @return string $result as xml
  */
  function getXMLIgnoreWordList() {
    // search dialog
    $result = $this->getXMLSearchDialog();

    // ignore words list
    if (!empty($this->ignoreWordList) && is_array($this->ignoreWordList)) {
      $result .= sprintf('<listview title="%s">'.LF, $this->_gt('Ignore words'));
      $result .= $this->getXMLListNav(
        $this->params['offset'], $this->ignoreWordsAbsCount, $this->stepsIgnoreWords
      );
      $result .= '<items>'.LF;
      foreach ($this->ignoreWordList as $word) {
        $id = isset($this->params['ignoreword_id']) ? $this->params['ignoreword_id'] : NULL;
        $selected = ($id == $word['ignoreword_id']) ? ' selected="selected"' : '';
        $result .= sprintf(
          '<listitem title="%s" href="%s"%s/>'.LF,
          papaya_strings::escapeHTMLChars($word['ignoreword']),
          $this->getLink(
            array('cmd' => 'ignoreword_edit', 'ignoreword_id' => $word['ignoreword_id'])
          ),
          $selected
        );
      }
      $result .= '</items>'.LF;
      $result .= '</listview>'.LF;
    }
    return $result;
  }

  /**
  * Gets list with ignored words in glossary entry term for entry information
  *
  * @param string $term of glossary entry
  * @see papaya_glossary::getEntryInfo
  * @return string $result as xml
  */
  function getEntryIgnoreWordsInfo($term) {
    $result = '';
    $tokens = $this->getTokens($term, FALSE);
    $filter = $this->databaseGetSQLCondition('ignoreword', $tokens);

    $sql = "SELECT ignoreword AS word
              FROM %s
             WHERE $filter";
    $params = array($this->tableGlossaryIgnoreWords);
    if ($res = $this->databaseQueryFmt($sql, $params)) {
      $ignoredWords = array();
      while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $ingoredWords[] = $row;
      }
      if (isset($ingoredWords) && is_array($ingoredWords) &&
          count($ingoredWords) > 0) {
        $result .= sprintf(
          '<listitem image="%s" title="%s" href="%s" span="3"></listitem>'.LF,
          papaya_strings::escapeHTMLChars($this->images['items-page-ignoreword']),
          $this->_gt('Ignored words in term'),
          $this->getLink(array('mode' => 1), $this->paramName, FALSE)
        );
        foreach ($ingoredWords as $ignoreWord) {
          $result .= sprintf(
            '<listitem indent="2" title="%s" span="3"></listitem>'.LF,
            papaya_strings::escapeHTMLChars($ignoreWord['word'])
          );
        }
      }
    }

    return $result;
  }

  /**
  * Information of currently selected entry. Lists all available translations for
  * the given term and provides an icon button to delete the translation.
  *
  * @param integer $entryId
  * @see papaya_glossary::getEntryIgnoreWordsInfo
  * @return string $result xml representation of the selected entry info box
  */
  function getEntryInfo($entryId) {
    $result = '';
    $result .= sprintf('<listview title="%s">'.LF, $this->_gt('Entry Information'));
    $result .= '<items>';
    // needs glossary id and $entryId to get translation information
    if (isset($this->params['glossary_id']) && $this->params['glossary_id'] != '') {
      $sql = "SELECT get.glossaryentry_term, get.glossaryentry_firsttoken,
                     get.lng_id
                FROM %s AS ge
                LEFT OUTER JOIN %s AS get
                  ON (get.glossaryentry_id = ge.glossaryentry_id)
               WHERE ge.glossary_id = '%d' AND ge.glossaryentry_id = '%d'
               ORDER BY get.lng_id ASC";
      $params = array($this->tableGlossaryEntries,
        $this->tableGlossaryEntriesTrans, $this->params['glossary_id'], $entryId);
      if ($res = $this->databaseQueryFmt($sql, $params)) {
        $entryTerm = '';
        $translatedEntries = 0;
        $terms = array();
        // go through each language
        while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
          $terms[] = $row;
        }
        foreach ($terms as $row) {
          if (isset($row['lng_id']) && $row['lng_id'] > 0) {
            $translatedEntries++;
            if ($this->module->hasPerm(3)) {
              // linked image to delete translation
              $deleteTranslationLink = sprintf(
                '<a href="%s"><glyph src="%s" hint="%s" /></a>',
                $this->getLink(
                  array('cmd' => 'del_trans', 'entry_id' => $entryId,
                  'entry_lng_id'   => $row['lng_id'],
                  'entry_del_term' => $row['glossaryentry_term'])
                ),
                $this->images['actions-phrase-delete'],
                $this->_gt('Delete translation'));
            } else {
              $deleteTranslationLink = ' ';
            }
            // row to show language flag / name and link from above
            $lngParamName = base_module_options::readOption(
              $this->guid,
              'LANG_SELECT_PARAMNAME',
              'lngsel'
            );
            $link = $this->getLink(
              array('cmd' => 'edit_entry', 'entry_id' => $entryId, 'patt' => '')
            );
            $link .= sprintf('&%s[language_select]=%d', $lngParamName, $row['lng_id']);
            $result .= sprintf(
              '<listitem href="%s" title="%s" image="./pics/language/%s">'.
              '<subitem>%s</subitem><subitem>%s</subitem></listitem>'.LF,
              papaya_strings::escapeHTMLChars($link),
              papaya_strings::escapeHTMLChars(
                $this->lngSelect->languages[$row['lng_id']]['lng_title']),
              papaya_strings::escapeHTMLChars(
                $this->lngSelect->languages[$row['lng_id']]['lng_glyph']),
              papaya_strings::escapeHTMLChars(
                $this->lngSelect->languages[$row['lng_id']]['lng_short']),
              $deleteTranslationLink);
            // row to show title
            $result .= sprintf(
              '<listitem image="%s" title="%s">'.
              '<subitem><a href="%s">%s</a></subitem><subitem /></listitem>'.LF,
              $this->images['items-message'],
              papaya_strings::escapeHTMLChars($this->_gt('Title')),
              papaya_strings::escapeHTMLChars($link),
              papaya_strings::escapeHTMLChars($row['glossaryentry_term']));
            if ($this->lngSelect->currentLanguageId == $row['lng_id']) {
              $entryTerm = $row['glossaryentry_term'];
            }
          }
        }
        if ($translatedEntries > 0) {
          // add rows with ignored words information
          $result .= $this->getEntryIgnoreWordsInfo($entryTerm);
        } else {
          $result .= sprintf(
            '<listitem image="%s" title="%s"/>'.LF,
            $this->images['status-sign-problem'],
            papaya_strings::escapeHTMLChars($this->_gt('No information available.'))
          );
        }
      }
    }
    $result .= '</items>';
    $result .= '</listview>';
    return $result;
  }

  /**
  * Information of currently selected entry. Lists all available translations for
  * the given term and provides an icon button to delete the translation. Lists all
  * available translations for the selected glossary and provides an icon button to
  * delete the translation. This function's logic is an adaptation of
  * papaya_glossary::getEntryInfo.
  *
  * @see papaya_glossary::getEntryInfo
  * @return string $result xml representation of the selected glossary info box
  */
  function getGlossaryInfo() {
    $result = '';
    $result .= sprintf('<listview title="%s">'.LF, $this->_gt('Glossary translations'));
    $result .= '<items>'.LF;
    if (isset($this->params['glossary_id']) && $this->params['glossary_id'] != '') {
      //we need to call $this->_gt() here since we do not want it to interfere it with
      //fetchRow within the while-loop. Note that $this->_gt() implies database calls.
      //When a phrase is not translated, we have some nasty side effects.
      $iconHint = $this->_gt('Delete translation of glossary');
      $sql = "SELECT lng_id, glossary_title
                FROM %s
               WHERE glossary_id = %d
            ORDER BY lng_id ASC";
      $params = array($this->tableGlossaryTrans, (int)$this->params['glossary_id']);
      $res = $this->databaseQueryFmt($sql, $params);
      if ($res) {
        while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
          if (isset($row['lng_id']) && (int)$row['lng_id'] > 0) {
            if ($this->module->hasPerm(2)) {
              $deleteTranslationLink = sprintf(
                '<a href="%s"><glyph src="%s" hint="%s"/></a>'.LF,
                $this->getLink(
                  array(
                    'cmd' => 'del_gloss_trans',
                    'glossary_id' => $this->params['glossary_id'],
                    'glossary_lng_id' => $row['lng_id'],
                    'glossary_del_title' => $row['glossary_title'])
                  ),
                  $this->images['actions-phrase-delete'],
                  $iconHint
                );
            } else {
              $deleteTranslationLink = ' ';
            }
            $result .= sprintf(
              '<listitem title="%s" image="./pics/language/%s">'.
              '<subitem>%s</subitem><subitem>%s</subitem></listitem>'.LF,
              papaya_strings::escapeHTMLChars(
                $this->lngSelect->languages[$row['lng_id']]['lng_title']),
              papaya_strings::escapeHTMLChars(
                $this->lngSelect->languages[$row['lng_id']]['lng_glyph']),
              papaya_strings::escapeHTMLChars(
                $this->lngSelect->languages[$row['lng_id']]['lng_short']),
              $deleteTranslationLink);
            // row to show title
            $result .= sprintf(
              '<listitem image="%s" title="%s">'.
              '<subitem>%s</subitem><subitem /></listitem>'.LF,
              $this->images['items-message'],
              papaya_strings::escapeHTMLChars($this->_gt('Title')),
              papaya_strings::escapeHTMLChars($row['glossary_title']));
          }
        }
      }
    }
    $result .= '</items>'.LF;
    $result .= '</listview>'.LF;
    return $result;
  }

  /**
  * Get search pattern for sql statements
  *
  * @return string $patt sql pattern
  */
  function getSQLSearchPattern() {
    $patt = "%";
    if (FALSE === strpos($this->params['patt'], '*') &&
        FALSE === strpos($this->params['patt'], '?') &&
        strlen($this->params['patt']) > 0) {
      $this->params['patt'] = '*'.$this->params['patt'].'*';
    }
    if (isset($this->params['patt']) && $this->params['patt']) {
      $replaceChars = array('%' => '\%', '_' => '\_', '*' => '%', '?' => '_');
      $patt = strtr($this->params['patt'], $replaceChars);
    }
    return $patt;
  }

  /**
  * Load glossary entries for current glossary from database
  *
  * @param integer $lngId selected id of language translations
  * @return boolean
  */
  function loadGlossaryEntries($lngId) {
    unset($this->glossaryEntries);
    if (!isset($this->params['offset']) ||
        (isset($this->params['search']) && $this->params['search'] == 'pattern')) {
      $this->params['offset'] = 0;
    }

    $this->entriesAbsCount = 0;
    $offset = (isset($this->params['offset'])) ?
      (int)$this->params['offset'] : 0;

    if (isset($this->params['glossary_id']) &&
        $this->params['glossary_id'] != '') {

      if (isset($this->params['patt']) && ($this->params['patt'] != '%') &&
          ($this->params['patt'] != '')) {
        $patt = $this->getSQLSearchPattern();
        $filter = "AND get.glossaryentry_term LIKE '%s'";
      } else {
        $filter = '%s';
        $patt = '';
      }

      $sql = "SELECT ge.glossary_id, ge.glossaryentry_id,
                     get.glossaryentry_term, get.glossaryentry_explanation,
                     get.glossaryentry_derivation, get.glossaryentry_source,
                     get.glossaryentry_synonyms, get.glossaryentry_abbreviations,
                     get.glossaryentry_links, get.lng_id,
                     gem.glossaryentry_term AS main_language_term
                FROM %s AS ge
                LEFT OUTER JOIN %s AS get
                  ON (get.glossaryentry_id = ge.glossaryentry_id
                      AND get.lng_id = %d $filter)
                LEFT OUTER JOIN %s AS gem
                  ON (gem.glossaryentry_id = ge.glossaryentry_id AND
                      gem.lng_id = %d)
               WHERE ge.glossary_id = %d $filter
               ORDER BY glossaryentry_term";
      $params = array($this->tableGlossaryEntries,
        $this->tableGlossaryEntriesTrans, $lngId, $patt,
        $this->tableGlossaryEntriesTrans, (int)PAPAYA_CONTENT_LANGUAGE,
        $this->params['glossary_id'], $patt);

      if ($res = $this->databaseQueryFmt($sql, $params, $this->steps, $offset)) {
        $containsSelected = FALSE;
        while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
          if (isset($this->params['entry_id']) &&
              $row['glossaryentry_id'] == $this->params['entry_id']) {
            $containsSelected = TRUE;
          }
          $this->glossaryEntries[$row['glossaryentry_id']] = $row;
        }
        $this->entriesAbsCount = $res->absCount();
        if (isset($this->params['entry_id']) && !$containsSelected) {
          if ($res = $this->databaseQueryFmt($sql, $params)) {
            $i = 0;
            while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
              $entries[] = $row;
              if (isset($selected) && ($i > ($selected + $this->steps))) {
                break;
              } elseif (!isset($selected) && isset($this->params['entry_id']) &&
                        $row['glossaryentry_id'] == $this->params['entry_id']) {
                $selected = $i;
              }
              $i++;
            }
            if (isset($selected)) {
              unset($this->glossaryEntries);
              $this->params['offset'] = (int)(floor($selected / $this->steps) * $this->steps);
              $this->sessionParams['offset'] = $this->params['offset'];
              $entries = array_slice($entries, $this->params['offset'], $this->steps);
              foreach ($entries as $entry) {
                $this->glossaryEntries[$entry['glossaryentry_id']] = $entry;
              }
              $this->setSessionValue($this->sessionParamName, $this->sessionParams);
            }
          }
        }
      }
      return TRUE;
    }
    return FALSE;
  }

  /**
  * Glossary title exists already?
  *
  * @param string $glossaryTitle
  * @return boolean used or not
  */
  function glossaryTitleExists($glossaryTitle, $oldTitle = '') {
    if ($glossaryTitle != $oldTitle) {
      $sql = "SELECT COUNT(glossary_id)
                FROM %s
               WHERE glossary_title = '%s'
                 AND lng_id = %d";
      $params = array($this->tableGlossaryTrans, $glossaryTitle,
        $this->lngSelect->currentLanguageId);
      if ($res = $this->databaseQueryFmt($sql, $params)) {
        return ($res->fetchField() > 0);
      }
    }
    return FALSE;
  }

  /**
  * Adds new glossary to database
  *
  * @return boolean
  */
  function addGlossary() {
    if ($this->params['cmd'] == 'add_new_gloss') {
      $newId = $this->databaseInsertRecord($this->tableGlossary, 'glossary_id');
    } else {
      $newId = $this->params['glossary_id'];
    }
    if (isset($newId) && $newId !== FALSE) {
      $data = array(
        'glossary_id' => $newId,
        'lng_id' => $this->lngSelect->currentLanguageId,
        'glossary_title' => $this->params['glossary_title'],
        'glossary_text'  => $this->params['glossary_text']
      );
      if (FALSE !== $this->databaseInsertRecord($this->tableGlossaryTrans, NULL, $data)) {
        $this->loadGlossaries($this->lngSelect->currentLanguageId);
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
  * Updates glossary in database
  *
  * @return boolean
  */
  function updateGlossary() {
    $data = array(
      'glossary_title' => $this->params['glossary_title'],
      'glossary_text'  => $this->params['glossary_text'],
    );
    $condition = array(
      'glossary_id' => $this->params['glossary_id'],
      'lng_id' => $this->lngSelect->currentLanguageId
    );
    if (FALSE !== $this->databaseUpdateRecord($this->tableGlossaryTrans, $data, $condition)) {
      $this->loadGlossaries($this->lngSelect->currentLanguageId);
      return TRUE;
    }
    return FALSE;
  }

  /**
  * Deletes glossary and all its entries from database
  *
  * @param integer $id glossary id
  * @return boolean
  */
  function deleteGlossary($id) {
    $result = TRUE;
    $sql = 'SELECT glossaryentry_id FROM %s WHERE glossary_id = %d';
    if ($res = $this->databaseQueryFmt($sql, array($this->tableGlossaryEntries, $id))) {
      $entryIds = array();
      while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $entryIds[] = $row['glossaryentry_id'];
      }
      $result = (
        FALSE !== $this->databaseDeleteRecord(
          $this->tableGlossaryEntriesTrans, 'glossaryentry_id', $entryIds) &&
        FALSE !== $this->databaseDeleteRecord(
          $this->tableGlossaryEntries, 'glossary_id', $id) &&
        FALSE !== $this->databaseDeleteRecord(
          $this->tableGlossaryTrans, 'glossary_id', $id) &&
        FALSE !== $this->databaseDeleteRecord(
          $this->tableGlossary, 'glossary_id', $id)
      );
    } else {
      return FALSE;
    }

    $this->loadGlossaries($this->lngSelect->currentLanguageId);
    if ($result !== FALSE) {
        $this->params['glossary_id'] = 0;
        $this->sessionParams['glossary_id'] = 0;
        $this->setSessionValue($this->sessionParamName, $this->sessionParams);
    }
    return $result;
  }

  /**
  * Glossary entry term exists already?
  *
  * @param string $entryTerm
  * @return boolean used or not
  */
  function entryTermExists($entryTerm, $oldTerm = '') {
    if ($entryTerm != $oldTerm) {
      $sql = "SELECT COUNT(glossaryentry_id)
                FROM %s
               WHERE glossaryentry_term = '%s'
                 AND lng_id = %d";
      $params = array($this->tableGlossaryEntriesTrans, $entryTerm,
        $this->lngSelect->currentLanguageId);
      if ($res = $this->databaseQueryFmt($sql, $params)) {
        return ($res->fetchField() > 0);
      }
    }
    return FALSE;
  }

  /**
  * Get tokens or first token of a term
  *
  * @param string $term
  * @param boolean $firstToken optional, default value FALSE
  * @return mixed first token string or array with token strings
  */
  function getTokens($term, $firstToken = FALSE) {
    $tokens = preg_split('~['.$this->nonWordChars.']+~', $term);
    if ($firstToken !== FALSE) {
      if (is_array($tokens) && count($tokens) > 0) {
        return $tokens[0];
      } else {
        return '';
      }
    }
    return $tokens;
  }

  /**
  * Adds glossary entry to database
  *
  * @return mixed affected rows or boolean
  */
  function addEntry($values) {
    if ($this->params['cmd'] == 'add_new_entry') {
      $data = array('glossary_id' => $this->params['glossary_id']);
      $newId = $this->databaseInsertRecord($this->tableGlossaryEntries, 'glossaryentry_id', $data);
    } else {
      $newId = $this->params['entry_id'];
    }

    $firstToken = $this->getTokens($values['glossaryentry_term'], TRUE);
    if (isset($newId) && ($newId !== FALSE)) {
      $lngId = $this->lngSelect->currentLanguageId;
      $normalized = $this->normalizeEntry($values['glossaryentry_term']);
      $dataTrans = array(
        'glossaryentry_id' => $newId,
        'lng_id' => $lngId,
        'glossaryentry_term'          => $values['glossaryentry_term'],
        'glossaryentry_normalized'    => $normalized,
        'glossaryentry_explanation'   => $values['glossaryentry_explanation'],
        'glossaryentry_keywords'      => implode(
          "\n",
          $this->parseTextToWords($lngId, $values['glossaryentry_explanation'])
        ),
        'glossaryentry_derivation'    => $values['glossaryentry_derivation'],
        'glossaryentry_synonyms'      => $values['glossaryentry_synonyms'],
        'glossaryentry_abbreviations' => $values['glossaryentry_abbreviations'],
        'glossaryentry_source'        => $values['glossaryentry_source'],
        'glossaryentry_links'         => $values['glossaryentry_links'],
        'glossaryentry_firsttoken'    => $firstToken,
      );
      if ($entryId = $this->databaseInsertRecord(
            $this->tableGlossaryEntriesTrans, NULL, $dataTrans)) {
        $this->loadGlossaryEntries($this->lngSelect->currentLanguageId);
        return $entryId;
      }
    }
    return FALSE;
  }

  /**
  * Updates glossary entry in database
  *
  * @return boolean
  */
  function updateEntry($values) {
    $firstToken = $this->getTokens($values['glossaryentry_term'], TRUE);
    $lngId = $this->lngSelect->currentLanguageId;
    $normalized = $this->normalizeEntry($values['glossaryentry_term']);
    $data = array(
      'glossaryentry_term'          => $values['glossaryentry_term'],
      'glossaryentry_normalized'    => $normalized,
      'glossaryentry_explanation'   => $values['glossaryentry_explanation'],
      'glossaryentry_keywords'      => implode(
        "\n",
        $this->parseTextToWords($lngId, $values['glossaryentry_explanation'])
      ),
      'glossaryentry_derivation'    => $values['glossaryentry_derivation'],
      'glossaryentry_synonyms'      => $values['glossaryentry_synonyms'],
      'glossaryentry_abbreviations' => $values['glossaryentry_abbreviations'],
      'glossaryentry_source'        => $values['glossaryentry_source'],
      'glossaryentry_links'         => $values['glossaryentry_links'],
      'glossaryentry_firsttoken'    => $firstToken,
    );
    $condition = array(
      'glossaryentry_id' => $this->params['entry_id'],
      'lng_id' => $this->lngSelect->currentLanguageId
    );
    if (
        FALSE !== $this->databaseUpdateRecord($this->tableGlossaryEntriesTrans, $data, $condition)
       ) {
      $this->loadGlossaryEntries($this->lngSelect->currentLanguageId);
      return TRUE;
    }
    return FALSE;
  }

  /**
  * Deletes glossary entry from database
  *
  * @param integer $id entry id
  * @return mixed boolean false or affected rows
  */
  function deleteEntry($id) {
    $conditionTrans = array(
      'glossaryentry_id' => $id,
      'lng_id' => $this->lngSelect->currentLanguageId
    );
    if ($this->databaseDeleteRecord($this->tableGlossaryEntriesTrans, $conditionTrans)) {
      $condition = array(
        'glossaryentry_id' => $id, 'glossary_id' => $this->params['glossary_id']
      );
      $result = $this->databaseDeleteRecord(
        $this->tableGlossaryEntries, 'glossaryentry_id', $condition
      );
      $this->loadGlossaryEntries($this->lngSelect->currentLanguageId);

      if ($result !== FALSE) {
        $this->params['glossaryentry_id'] = 0;
        $this->sessionParams['glossaryentry_id'] = 0;
        $this->setSessionValue($this->sessionParamName, $this->sessionParams);
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
  * Ignore word exists already?
  *
  * @param string $ignoreWord word to ignore
  * @return boolean used or not
  */
  function ignoreWordExists($ignoreWord) {
    $ignoreWord = papaya_strings::strtolower($ignoreWord);
    $sql = "SELECT COUNT(ignoreword_id)
              FROM %s
             WHERE ignoreword = '%s'
               AND ignoreword_lngid = %d";
    $params = array($this->tableGlossaryIgnoreWords, $ignoreWord,
      $this->lngSelect->currentLanguageId);
    if ($res = $this->databaseQueryFmt($sql, $params)) {
      return ($res->fetchField() > 0);
    }
    return FALSE;
  }

  /**
  * Adds ignore word to database
  *
  * @return mixed affected rows or boolean
  */
  function addIgnoreWord() {
    $data = array(
      'ignoreword' => papaya_strings::strtolower($this->params['ignoreword']),
      'ignoreword_lngid' => $this->lngSelect->currentLanguageId,
    );
    return $this->databaseInsertRecord(
      $this->tableGlossaryIgnoreWords, 'ignoreword_id', $data
    );
  }

  /**
  * Updates ignore word entry in database
  *
  * @return boolean
  */
  function updateIgnoreWord() {
    $data = array(
      'ignoreword' => $this->params['ignoreword'],
    );
    $filter = array(
      'ignoreword_id' => $this->params['ignoreword_id'],
      'ignoreword_lngid' => $this->lngSelect->currentLanguageId,
    );
    return (
      FALSE !== $this->databaseUpdateRecord($this->tableGlossaryIgnoreWords, $data, $filter)
    );
  }

  /**
  * Deletes ignore word from database
  *
  * @param integer $ignoreWordId id of ignoreword database entry
  */
  function deleteIgnoreWord($ignoreWordId) {
    return FALSE !== $this->databaseDeleteRecord(
      $this->tableGlossaryIgnoreWords, array('ignoreword_id' => (int)$ignoreWordId)
    );
  }

  /**
  * Deletes entry / glossary translation
  *
  * @param string $type type of dialog, for entries or glossaries
  * @param $integer id entry id or glossary id
  * @param mixed $lngId optional, default value NULL for specific entry language id
  */
  function deleteTranslation($type, $id, $lngId = NULL) {
    // language id default value
    if ($lngId == NULL) {
      $lngId = $this->lngSelect->currentLanguageId;
    }
    // delete glossary translation
    if ($type == 'glossary') {
      // select entries of glossary from database
      $sql = "SELECT ge.glossaryentry_id
                FROM %s AS ge
                LEFT OUTER JOIN %s AS get
                  ON (ge.glossaryentry_id = get.glossaryentry_id)
               WHERE ge.glossary_id = %d
                 AND get.lng_id = %d";
      $res = $this->databaseQueryFmt(
        $sql, array($this->tableGlossaryEntries, $this->tableGlossaryEntriesTrans, $id, $lngId)
      );
      if ($res) {
        // set entries to array
        $entryIds = array();
        while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
          $entryIds[] = $row['glossaryentry_id'];
        }
        // get sql conditions / statement to delete glossary translations
        $entriesCondition = $this->databaseGetSQLCondition('glossaryentry_id', $entryIds);
        $lngIdCondition = $this->databaseGetSQLCondition('lng_id', $lngId);
        // sql statement to delete entries' translations
        $entriesTransSql = "DELETE FROM %s
                             WHERE ".$entriesCondition."
                               AND ".$lngIdCondition;
        $conditionTrans = array('glossary_id' => $id, 'lng_id' => $lngId);
        // send queries to delete glossary / entries translations
        $return = TRUE;
        $return = $this->databaseQueryFmtWrite($entriesTransSql, $this->tableGlossaryEntriesTrans);
        $return = $this->databaseDeleteRecord($this->tableGlossaryTrans, $conditionTrans);
        return $return;
      } else {
        return FALSE;
      }

    } elseif ($type == 'entry') {
      // delete entry translation
      return FALSE !== $this->databaseDeleteRecord(
        $this->tableGlossaryEntriesTrans, array('glossaryentry_id' => $id, 'lng_id' => $lngId)
      );
    }
  }

  /**
  * Builds form to delete translations of glossaries and entries. Which translation you
  * want to delete is determined by the $type parameter.
  *
  * @param string $type type of dialog, for entries or glossaries
  * @param $integer id entry id for $type = 'entry', glossary id for $type = 'glossary'
  * @param mixed $lngId optional, default value NULL for specific entry language id
  * @return string xml dialog
  */
  function getDelTranslationDialog($type, $id, $lngId = NULL) {
    $hidden = array(
      'cmd' => 'del_trans',
      'confirm_del_trans' => 1
    );
    // language id default value
    if ($lngId == NULL) {
      $lngId = $this->lngSelect->currentLanguageId;
    }
    // dialog properties for glossaries
    if ($type == 'glossary') {
      $hidden['glossary_id'] = $id;
      $hidden['glossary_lng_id'] = $lngId;
      if (isset($this->params['glossary_del_title'])) {
        $title = $this->params['glossary_del_title'];
      } else {
        $title = $this->glossaries[$id]['glossary_title'];
      }
      // dialog properties for entries
    } elseif ($type == 'entry') {
      $hidden['entry_id'] = $id;
      $hidden['entry_lng_id'] = $lngId;
      if (isset($this->params['entry_del_term'])) {
        $title = $this->params['entry_del_term'];
      } else {
        $title = $this->glossaryEntries[$id]['glossaryentry_term'];
      }
    }
    // dialog message
    $msg = sprintf(
      $this->_gt('Delete %s "%s" (%s) for language "%s" (%s)?'),
      papaya_strings::escapeHTMLChars($this->_gt($type)),
      papaya_strings::escapeHTMLChars($title),
      $id,
      $this->lngSelect->languages[$lngId]['lng_title'],
      $this->lngSelect->languages[$lngId]['lng_short']
    );
    // include dialog object
    include_once(PAPAYA_INCLUDE_PATH.'system/base_msgdialog.php');
    $dialog = new base_msgdialog($this, $this->paramName, $hidden, $msg, 'question');
    $dialog->buttonTitle = 'Delete';
    return $dialog->getMsgDialog();
  }

  /**
  * Adds add entry dialog to layout
  *
  * @see papaya_glossary::initializeAddGlossaryDialog
  */
  function getAddGlossaryDialog() {
    $this->initializeAddGlossaryDialog();
    $this->layout->add($this->addGlossaryDialog->getDialogXML());
  }

  /**
  * Gets form to add new glossary
  *
  * @return string xml dialog
  */
  function initializeAddGlossaryDialog($loadParams = TRUE) {
    if (!@is_object($this->addGlossaryDialog)) {
      $hidden['confirm_add_gloss'] = 1;
      if ($this->params['cmd'] == 'add_new_gloss') {
        $hidden['cmd'] = 'add_new_gloss';
      } else {
        $hidden['cmd'] = 'add_gloss';
      }
      $data = array();

      $fields = array(
        'glossary_title' => array('Title', 'isNoHTML', TRUE, 'input', 100, ''),
        'glossary_text' => array('Text', 'isNoHTML', TRUE, 'textarea', 10, '')
      );
      include_once(PAPAYA_INCLUDE_PATH.'system/base_dialog.php');
      $this->addGlossaryDialog = new base_dialog($this, $this->paramName, $fields, $data, $hidden);
      $this->addGlossaryDialog->dialogTitle = $this->_gt('Add glossary');
      $this->addGlossaryDialog->buttonTitle = 'Add';
      $this->addGlossaryDialog->inputFieldSize = 'x-large';
      if ($loadParams) {
        $this->addGlossaryDialog->loadParams();
      }
    }
  }

  /**
  * Builds dialog to add glossary in current language
  *
  * @return string xml dialog
  */
  function getAddLngGlossaryDialog() {
    $hidden = array(
      'cmd' => 'add_gloss',
      'glossary_id' => $this->params['glossary_id'],
    );

    $msg = sprintf(
      $this->_gt('Add glossary for language "%s" (%s) ?'),
      $this->lngSelect->currentLanguage['lng_title'],
      $this->lngSelect->currentLanguage['lng_short']
    );

    include_once(PAPAYA_INCLUDE_PATH.'system/base_msgdialog.php');
    $dialog = new base_msgdialog($this, $this->paramName, $hidden, $msg, 'question');
    $dialog->buttonTitle = 'Add';
    return $dialog->getMsgDialog();
  }

  /**
  * Adds edit glossary dialog to layout
  *
  * @see papaya_glossary::initializeEditGlossaryDialog
  */
  function getEditGlossaryDialog() {
    $this->initializeEditGlossaryDialog();
    $this->layout->add($this->editGlossaryDialog->getDialogXML());
  }

  /**
  * Initializes edit glossary dialog
  *
  * @return string xml dialog
  */
  function initializeEditGlossaryDialog() {
    if (!isset($this->editGlossaryDialog) &&
        !is_object($this->editGlossaryDialog) &&
        isset($this->glossaries[$this->params['glossary_id']])) {
      $hidden = array(
        'cmd' => 'edit_gloss',
        'confirm_edit_gloss' => 1,
      );
      $data = $this->glossaries[$this->params['glossary_id']];
      if (isset($data['glossary_title'])) {
        $data['glossary_old_title'] = $data['glossary_title'];
      }
      $fields = array(
        'glossary_title' => array('Title', 'isNoHTML', TRUE, 'input', 200, ''),
        'glossary_text' => array('Text', 'isNoHTML', TRUE, 'textarea', 10, '')
      );
      include_once(PAPAYA_INCLUDE_PATH.'system/base_dialog.php');
      $this->editGlossaryDialog = new base_dialog(
        $this, $this->paramName, $fields, $data, $hidden
      );
      include_once(PAPAYA_INCLUDE_PATH.'system/base_dialog.php');
      $this->editGlossaryDialog->dialogTitle = $this->_gt('Edit');
      $this->editGlossaryDialog->buttonTitle = 'Save';
      $this->editGlossaryDialog->inputFieldSize = 'x-large';
      $this->editGlossaryDialog->loadParams();
    }
  }

  /**
  * Builds form to delete glossary
  *
  * @return string xml dialog
  */
  function getDelGlossaryDialog() {
    $hidden = array(
      'cmd' => 'del_gloss',
      'confirm_del_gloss' => 1,
    );

    $msg = sprintf(
      $this->_gt('Delete glossary "%s" (%s)?'),
      $this->glossaries[$this->params['glossary_id']]['glossary_title'],
      $this->params['glossary_id']
    );

    include_once(PAPAYA_INCLUDE_PATH.'system/base_msgdialog.php');
    $dialog = new base_msgdialog($this, $this->paramName, $hidden, $msg, 'question');
    $dialog->buttonTitle = 'Delete';
    return $dialog->getMsgDialog();
  }

  /**
  * Adds add entry dialog to layout
  *
  * @see papaya_glossary::initializeAddEntryDialog
  */
  function getAddEntryDialog() {
    $this->initializeAddEntryDialog();
    $this->layout->add($this->addEntryDialog->getDialogXML());
  }

  /**
  * Builds form to add glossary entry
  *
  * @return string xml dialog
  */
  function initializeAddEntryDialog($loadParams = TRUE) {
    if (!@is_object($this->addEntryDialog)) {
      $hidden['confirm_add_entry'] = 1;
      $fieldSize = 'x-large';
      if ($this->params['cmd'] == 'add_new_entry') {
        $hidden['cmd'] = 'add_new_entry';
        $fieldSize = 'x-large';
      } else {
        $hidden['cmd'] = 'add_entry';
        $hidden['entry_id'] = $this->params['entry_id'];
        $fieldSize = 'medium';
      }
      $data = array();

      $fields = array(
        'glossaryentry_term' => array('Term', 'isNoHTML', TRUE, 'input', 200, ''),
        'glossaryentry_explanation' => array('Explanation', 'isSomeText', TRUE,
          'simplerichtext', 20, ''),
        'Optional',
        'glossaryentry_derivation' => array('Derivation', 'isNoHTML', FALSE,
          'input', 200, '', ''),
        'glossaryentry_synonyms' => array('Synonyms', 'isNoHTML', FALSE,
          'input', 200, '', ''),
        'glossaryentry_abbreviations' => array('Abbreviations', 'isNoHTML', FALSE,
          'input', 200, '', ''),
        'glossaryentry_source' => array('Source', 'isNoHTML', FALSE, 'input', 200,
          'Source of explanation for this term.', ''),
        'glossaryentry_links' => array('Links', 'isNoHTML', FALSE, 'textarea', 10,
          'Links for further research. One link each line; http:// may not be omitted
          . Titles may be set via: title of link=http://www.link.tld', ''),
      );

      include_once(PAPAYA_INCLUDE_PATH.'system/base_dialog.php');
      $this->addEntryDialog = new base_dialog($this, $this->paramName, $fields, $data, $hidden);
      $this->addEntryDialog->expandPapayaTags = TRUE;
      $this->addEntryDialog->dialogTitle = $this->_gt('Add entry');
      $this->addEntryDialog->buttonTitle = 'Add';
      $this->addEntryDialog->baseLink = $this->baseLink;
      $this->addEntryDialog->inputFieldSize = $fieldSize;
      if ($loadParams) {
        $this->addEntryDialog->loadParams();
      }
    }
  }

  /**
  * Builds dialog to add glossary entry in current language
  *
  * @return string xml dialog
  */
  function getAddLngEntryDialog() {
    $hidden = array(
      'cmd' => 'add_entry',
      'entry_id' => $this->params['entry_id'],
    );

    $msg = sprintf(
      $this->_gt('Add entry for language "%s" (%s) ?'),
      $this->lngSelect->currentLanguage['lng_title'],
      $this->lngSelect->currentLanguage['lng_short']
    );

    include_once(PAPAYA_INCLUDE_PATH.'system/base_msgdialog.php');
    $dialog = new base_msgdialog($this, $this->paramName, $hidden, $msg, 'question');
    $dialog->buttonTitle = 'Add';
    return $dialog->getMsgDialog();
  }

  /**
  * Adds edit entry dialog to layout
  *
  * @see papaya_glossary::initializeEditEntryDialog
  */
  function getEditEntryDialog() {
    $this->initializeEditEntryDialog();
    if (isset($this->editEntryDialog) && is_object($this->editEntryDialog)) {
      $this->layout->add($this->editEntryDialog->getDialogXML());
    }
  }

  /**
  * Initializes edit entry dialog
  */
  function initializeEditEntryDialog() {
    if (!isset($this->editEntryDialog) && !is_object($this->editEntryDialog) &&
        isset($this->glossaryEntries[@$this->params['entry_id']])) {

      $hidden = array(
        'cmd' => 'edit_entry',
        'entry_id' => $this->params['entry_id'],
        'confirm_edit_entry' => 1,
      );

      $data = array(
        'glossaryentry_term' =>  $this->glossaryEntries[
          $this->params['entry_id']]['glossaryentry_term'],
        'glossaryentry_old_term' => $this->glossaryEntries[
          $this->params['entry_id']]['glossaryentry_term'],
        'glossaryentry_explanation' => $this->glossaryEntries[
          $this->params['entry_id']]['glossaryentry_explanation'],
        'glossaryentry_derivation' => $this->glossaryEntries[
          $this->params['entry_id']]['glossaryentry_derivation'],
        'glossaryentry_synonyms' => $this->glossaryEntries[
          $this->params['entry_id']]['glossaryentry_synonyms'],
        'glossaryentry_abbreviations' => $this->glossaryEntries[
          $this->params['entry_id']]['glossaryentry_abbreviations'],
        'glossaryentry_source' => $this->glossaryEntries[
          $this->params['entry_id']]['glossaryentry_source'],
        'glossaryentry_links' => $this->glossaryEntries[
          $this->params['entry_id']]['glossaryentry_links'],
      );

      $fields = array(
        'glossaryentry_term' => array('Term', 'isNoHTML', TRUE, 'input', 200, ''),
        'glossaryentry_explanation' => array('Explanation', 'isSomeText', TRUE,
          'simplerichtext', 20, ''),
        'Optional',
        'glossaryentry_derivation' => array('Derivation', 'isNoHTML', FALSE,
          'input', 200, '', ''),
        'glossaryentry_synonyms' => array('Synonyms', 'isNoHTML', FALSE,
          'input', 200, '', ''),
        'glossaryentry_abbreviations' => array('Abbreviations', 'isNoHTML', FALSE,
          'input', 200, '', ''),
        'glossaryentry_source' => array('Source', 'isNoHTML', FALSE, 'input', 200,
          'Source of explanation for this term.', ''),
        'glossaryentry_links' => array('Links', 'isNoHTML', FALSE, 'textarea', 10,
          'Links for further research. One link each line, titles may be set via: '.
          'title of link=http://www.link.tld',
          ''),
      );

      include_once(PAPAYA_INCLUDE_PATH.'system/base_dialog.php');
      $this->editEntryDialog = new base_dialog(
        $this, $this->paramName, $fields, $data, $hidden
      );
      $this->editEntryDialog->expandPapayaTags = TRUE;
      $this->editEntryDialog->dialogTitle = $this->_gt('Edit entry');
      $this->editEntryDialog->buttonTitle = 'Edit';
      $this->editEntryDialog->baseLink = $this->baseLink;
      $this->editEntryDialog->inputFieldSize = 'medium';
      $this->editEntryDialog->loadParams();
    }
  }

  /**
  * Builds dialog to delete glossary entry
  *
  * @return string xml dialog
  */
  function getDelEntryDialog() {
    $hidden = array(
      'cmd' => 'del_entry',
      'entry_id' => $this->params['entry_id'],
      'confirm_del_entry'=>1,
    );

    $msg = sprintf(
      $this->_gt('Delete entry "%s"?'),
      $this->glossaryEntries[$this->params['entry_id']]['glossaryentry_term']
    );

    include_once(PAPAYA_INCLUDE_PATH.'system/base_msgdialog.php');
    $dialog = new base_msgdialog($this, $this->paramName, $hidden, $msg, 'question');
    $dialog->buttonTitle = 'Delete';
    return $dialog->getMsgDialog();
  }

  /**
  * Fetches data of current glossary
  *
  * @param integer $id glossary id
  * @return mixed array $row or boolean false
  */
  function getGlossaryData($id) {
    $sql = 'SELECT glossary_title AS title, glossary_text AS text
              FROM %s
             WHERE glossary_id = %d AND lng_id = %d';
    $res = $this->databaseQueryFmt(
      $sql, array($this->tableGlossaryTrans, $id, $this->lngSelect->currentLanguageId)
    );
    if ($res) {
      if ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        return $row;
      }
    }
    return FALSE;
  }

  /**
  * Builds xml list of glossaries
  *
  * @return string $result xml
  */
  function getGlossariesList() {
    $result = '';
    if (!empty($this->glossaries) && is_array($this->glossaries)) {
      $result .= sprintf(
        '<listview title="%s"><items>'.LF,
        $this->_gt('Glossaries')
      );
      foreach ($this->glossaries as $id => $glossary) {
        if (isset($this->params['glossary_id']) && isset($this->glossaries) &&
            isset($this->glossaries[$this->params['glossary_id']]) &&
            $id == $this->params['glossary_id']) {
          $selected = ' selected="selected"';
          $image = 'status-folder-open';
        } else {
          $selected = '';
          $image = 'items-folder';
        }
        if ($glossary['lng_id'] == $this->lngSelect->currentLanguageId) {
          $title = $glossary['glossary_title'];
        } elseif ($glossary['main_language_title']) {
          $title = '['.$glossary['main_language_title'].']';
        } else {
          $title = $this->_gt('No title');
        }
        $result .= sprintf(
          '<listitem href="%s" title="%s" image="%s"%s>'.LF,
          $this->getLink(array('cmd' => 'edit_gloss', 'glossary_id' => $id)),
          $title,
          $this->images[$image],
          $selected
        );
        $result .= '</listitem>';
      }
      $result .= '</items></listview>'.LF;
    }
    return $result;
  }

  /**
  * Builds list of entries for current glossary
  *
  * @see papaya_glossary::getXMLSearchDialog
  * @return string $result as xml
  */
  function getXMLEntriesList() {
    $result = '';
    // search dialog
    $result .= $this->getXMLSearchDialog();
    $result .= sprintf(
      '<listview title="%s" width="100%%">', $this->_gt('Glossary entries')
    );
    $result .= $this->getXMLListNav(
      !empty($this->params['offset']) ? $this->params['offset'] : 0,
      $this->entriesAbsCount,
      $this->steps
    );
    $result .= '<items>';
    $result .= sprintf(
      '<listitem title="%s" image="%s" href="%s">',
      $this->_gt("Other glossaries ..."),
      $this->images['actions-go-superior'],
      $this->getLink(array('offset' => 0, 'patt' => '', 'glossary_id' => '0'))
    );
    $result .= '</listitem>';

    $selected = (!empty($this->params['glossary_id']) && empty($this->params['entry_id'])) ?
        ' selected="selected"' : '';
    $image = (!empty($this->params['glossary_id']) && empty($this->params['entry_id'])) ?
        'status-folder-open' : 'items-folder';

    if ($this->glossaries[$this->params['glossary_id']]['lng_id'] ==
          $this->lngSelect->currentLanguageId) {
      $title = $this->glossaries[$this->params['glossary_id']]['glossary_title'];
    } elseif (
        $this->glossaries[$this->params['glossary_id']]['main_language_title']) {
      $title = '['.
        $this->glossaries[$this->params['glossary_id']]['main_language_title'].
        ']';
    } else {
      $title = $this->_gt('No title');
    }

    $result .= sprintf(
      '<listitem title="%s" image="%s" href="%s"%s>',
      $title,
      $this->images[$image],
      $this->getLink(array('cmd' => 'edit_gloss', 'glossary_id' => $this->params['glossary_id'])),
      $selected
    );
    $result .= '</listitem>';
    // gloassary entries

    if (!empty($this->glossaryEntries) && is_array($this->glossaryEntries)) {
      foreach ($this->glossaryEntries as $id => $entry) {
        if ($this->lngSelect->currentLanguageId == $entry['lng_id']) {
          $title = htmlspecialchars($entry['glossaryentry_term']);
        } elseif ($entry['main_language_term']) {
          $title = '['.$entry['main_language_term'].']';
        } else {
          $title = $this->_gt('No title');
        }
        $selectedId = isset($this->params['entry_id']) ? $this->params['entry_id'] : NULL;
        $selected = (
          isset($entry['glossaryentry_id']) && $entry['glossaryentry_id'] == $selectedId
        ) ? ' selected="selected"' : '';
        $result .= sprintf(
          '<listitem title="%s" image="%s" href="%s" indent="1"%s>',
          $title,
          $this->images['items-page'],
          $this->getLink(array('cmd' => 'edit_entry', 'entry_id' => $entry['glossaryentry_id'])),
          $selected
        );
        $result .= '</listitem>';
      }
    }
    $result .= '</items>';
    $result .= '</listview>';
    return $result;
  }

  /**
  * Get XML Search dialog
  *
  * @see papaya_glossary::getXMLEntriesList
  * @return string $result
  */
  function getXMLSearchDialog() {
    $result = '';
    $result .= sprintf(
      '<dialog title="%s" action="%s" width="100%%">'.LF,
      $this->_gt('Search'),
      $this->getLink(array('search' => 'pattern'))
    );
    $result .= '<lines>'.LF;
    $result .= '<line align="center">'.$this->getCharBtns().'</line>'.LF;
    $result .= '<line align="center">';
    $result .= sprintf(
      '<input type="text" class="smallinput" name="%s[patt]" value="%s" /></line>'.LF,
      $this->paramName,
      htmlspecialchars(!empty($this->params['patt']) ? $this->params['patt'] : '')
    );
    $result .= '</lines>'.LF;
    $result .= sprintf('<dlgbutton value="%s" />'.LF, $this->_gt('Search'));
    $result .= '</dialog>'.LF;
    return $result;
  }

  /**
  * Gets character buttons for search dialog naviagation
  *
  * @return string $result xml
  */
  function getCharBtns() {
    $result = '';
    $chars = 'abcdefghijklmnopqrstuvwxyz';
    $charCount = strlen($chars);
    for ($i = 0; $i < $charCount; $i++) {
      $result .= sprintf(
        '<a href="%s">%s</a> '.LF,
        $this->getLink(array('search' => 'pattern', 'patt' => $chars[$i].'*')),
        $chars[$i]
      );
      if ($i == 13) {
        $result .= '<br />';
      }
    }
    $result .= sprintf(
      '<br /><a href="%s">%s</a> '.LF,
      $this->getLink(array('search' => 'pattern', 'patt' => '')),
      $this->_gt('All')
    );
    return $result;
  }


  /**
  * Get list navigation
  *
  * @param integer $offset current offset
  * @param integer $step offset step
  * @param integer $max max offset
  * @param string $paramName offset param name
  */
  function getXMLListNav($offset, $max, $steps, $maxPages = 9, $paramName = 'offset') {
    if ($max > $steps) {
      $pageCount = ceil($max / $steps);
      $currentPage = ceil($offset / $steps);
      $result = '<buttons>';
      if ($currentPage > 0) {
        $i = ($currentPage - 1) * $steps;
        $result .= sprintf(
          '<button hint="%s" glyph="%s" href="%s"/>'.LF,
          papaya_strings::escapeHTMLChars($this->_gt('Previous page')),
          papaya_strings::escapeHTMLChars($this->images['actions-go-previous']),
          $this->getLink(array('cmd' => 'show', $paramName => $i))
        );
      } else {
        $result .= sprintf(
          '<button hint="%s" glyph="%s"/>'.LF,
          papaya_strings::escapeHTMLChars($this->_gt('Previous page')),
          papaya_strings::escapeHTMLChars($this->images['status-go-previous-disabled'])
        );
      }
      if ($pageCount > $maxPages) {
        $plusMinus = floor($maxPages / 2);
        $pageMin = ceil(($offset - ($steps * ($plusMinus))) / $steps);
        $pageMax = ceil(($offset + ($steps * ($plusMinus))) / $steps);
        if ($pageMin < 0) {
          $pageMin = 0;
        }
        if ($pageMin == 0) {
          $pageMax = $maxPages;
        } elseif ($pageMax >= $pageCount) {
          $pageMax = $pageCount;
          $pageMin = $pageCount - $maxPages;
        }
        for ($x = $pageMin; $x < $pageMax; $x++) {
          $i = $x * $steps;
          $down = ($i == $offset) ? ' down="down"' : '';
          $result .= sprintf(
            '<button title="%s" href="%s"%s/>'.LF,
            $x + 1,
            $this->getLink(array('cmd' => 'show', $paramName => $i)),
            $down
          );
        }
      } else {
        for ($i = 0, $x = 1; $i < $max; $i += $steps, $x++) {
          $down = ($i == $offset) ? ' down="down"' : '';
          $result .= sprintf(
            '<button title="%s" href="%s"%s/>'.LF,
            $x,
            $this->getLink(array('cmd' => 'show', $paramName => $i)),
            $down
          );
        }
      }
      if ($currentPage < $pageCount - 1) {
        $i = ($currentPage + 1) * $steps;
        $result .= sprintf(
          '<button hint="%s" glyph="%s" href="%s"/>'.LF,
          papaya_strings::escapeHTMLChars($this->_gt('Next page')),
          papaya_strings::escapeHTMLChars($this->images['actions-go-next']),
          $this->getLink(array('cmd' => 'show', $paramName => $i))
        );
      } else {
        $result .= sprintf(
          '<button hint="%s" glyph="%s"/>'.LF,
          papaya_strings::escapeHTMLChars($this->_gt('Next page')),
          papaya_strings::escapeHTMLChars($this->images['status-go-next-disabled'])
        );
      }
      $result .= '</buttons>';
      return $result;
    }
    return '';
  }

  /**
  * Loads ignore words to array
  *
  * @param integer $lngId active language id
  * @param integer $offset offset by parameter
  * @return boolean
  */
  function loadIgnoreWordList() {
    $this->ignoreWordList = array();
    if (!isset($this->params['offset']) ||
        (isset($this->params['search']) && $this->params['search'] == 'pattern')) {
      $this->params['offset'] = 0;
    }

    $patt = $this->getSQLSearchPattern();
    $this->ignoreWordsAbsCount = 0;
    $sql = "SELECT ignoreword_id, ignoreword
              FROM %s
             WHERE ignoreword_lngid = %d
               AND ignoreword LIKE '%s'
             ORDER BY ignoreword";
    $params = array($this->tableGlossaryIgnoreWords, $this->lngSelect->currentLanguageId, $patt);
    $res = $this->databaseQueryFmt(
      $sql, $params, (int)$this->stepsIgnoreWords, (int)$this->params['offset']
    );
    if ($res) {
      while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $this->ignoreWordList[$row['ignoreword_id']] = $row;
      }
      $this->ignoreWordsAbsCount = $res->absCount();
      return TRUE;
    }
    return FALSE;
  }

  function fixNormalized() {
    $sql = "SELECT glossaryentry_id, lng_id, glossaryentry_term
              FROM %s
           ";
    $params = array($this->tableGlossaryEntriesTrans);
    if ($res = $this->databaseQueryFmt($sql, $params)) {
      while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $entries[] = $row;
      }
    }
    if (isset($entries) && is_array($entries)) {
      foreach ($entries as $id => $entry) {
        $normalized = $this->normalizeEntry($entry['glossaryentry_term']);
        $data = array(
          'glossaryentry_normalized' => $normalized,
        );
        $condition = array(
          'glossaryentry_id' => $entry['glossaryentry_id'],
          'lng_id'           => $entry['lng_id'],
        );
        $this->databaseUpdateRecord($this->tableGlossaryEntriesTrans, $data, $condition);
      }
    }
  }

  function normalizeEntry($term) {
    $charReplace = array(
      'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Å' => 'A',
      'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'å' => 'a',
      'Ç' => 'C', 'ç' => 'c',
      'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'è' => 'e',
      'é' => 'e', 'ê' => 'e', 'ë' => 'e',
      'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'ì' => 'i',
      'í' => 'i', 'î' => 'i', 'ï' => 'i',
      'Ñ' => 'N', 'ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O',
      'Õ' => 'O', 'Ø' => 'O',
      'ð' => 'o', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
      'ø' => 'o', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U',
      'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'Ý' => 'Y', 'ý' => 'y',
      'ÿ' => 'y', 'Ä' => 'A', 'Ü' => 'U', 'Ö' => 'O',
      'ä' => 'a', 'ü' => 'u', 'ö' => 'o', 'ß' => 'ss'
    );
    $str = strtr($term, $charReplace);
    $str = preg_replace('/[^a-zA-Z0-9]/', '', $str);
    return papaya_strings::strtolower($str);
  }

}
?>
