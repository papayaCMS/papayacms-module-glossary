<?php

class GlossaryAdministrationContent extends PapayaAdministrationPagePart {

  public function appendTo(PapayaXmlElement $parent) {
    parent::appendTo($parent);
  }

  /**
   * Overload this method to create the commands structure.
   *
   * @param string $name
   * @param string $default
   * @return PapayaUiControlCommandController
   */
  protected function _createCommands($name = 'mode', $default = 'terms') {
    $moduleId = $this->getPage()->getModuleId();
    $modes = parent::_createCommands($name, $default);
    $modes->parameterGroup($this->parameterGroup());

    $modes['terms'] = $commands = new GlossaryAdministrationContentTerms();
    $commands->permission([$moduleId, GlossaryAdministration::PERMISSION_MANAGE_TERMS]);

    $modes['glossaries'] = $commands = new PapayaUiControlCommandController('cmd', 'change');
    $commands->permission([$moduleId, GlossaryAdministration::PERMISSION_MANAGE_GLOSSARIES]);
    $commands['change'] = new GlossaryAdministrationContentGlossaryChange();
    $commands['delete'] = new GlossaryAdministrationContentGlossaryDelete();

    $modes['ignore-words'] = $commands = new PapayaUiControlCommandController('cmd', 'change');
    $commands->permission([$moduleId, GlossaryAdministration::PERMISSION_MANAGE_IGNORE]);
    $commands['change'] = new GlossaryAdministrationContentIgnores(
      PapayaUiControlCommandDialogDatabaseRecord::ACTION_SAVE
    );
    $commands['delete'] = new GlossaryAdministrationContentIgnores(
      PapayaUiControlCommandDialogDatabaseRecord::ACTION_DELETE
    );

    return $modes;
  }

  /**
   * @param PapayaUiToolbarSet $toolbar
   */
  protected function _initializeToolbar(PapayaUiToolbarSet $toolbar) {
    $moduleId = $this->getPage()->getModuleId();
    parent::_initializeToolbar($toolbar);
    $toolbar->elements[] = $select = new PapayaUiToolbarSelectButtons(
      [$this->parameterGroup(), 'mode'],
      [
        'terms' => [
          'caption' => 'Terms',
          'image' => 'categories-view-list',
          'enabled' => $this->papaya()->administrationUser->hasPerm(
            GlossaryAdministration::PERMISSION_MANAGE_TERMS,
            $moduleId
          )
        ],
        'glossaries' => [
          'caption' => 'Glossaries',
          'image' => 'items-folder',
          'enabled' => $this->papaya()->administrationUser->hasPerm(
            GlossaryAdministration::PERMISSION_MANAGE_GLOSSARIES,
            $moduleId
          )
        ],
        'ignore-words' => [
          'caption' => 'Ignore words',
          'image' => 'items-page-ignoreword',
          'enabled' => $this->papaya()->administrationUser->hasPerm(
            GlossaryAdministration::PERMISSION_MANAGE_IGNORE,
            $moduleId
          )
        ]
      ]
    );
    $select->defaultValue = 'terms';
    $toolbar->elements[] = new PapayaUiToolbarSeparator();
    switch ($this->parameters()->get('mode')) {
    case 'glossaries' :
      $toolbar->elements[] = $button = new PapayaUiToolbarButton();
      $button->reference->setParameters(
        [ 'mode' => 'glossaries', 'cmd' => 'change', 'glossary_id' => 0],
        $this->parameterGroup()
      );
      $button->caption = new PapayaUiStringTranslated('Add glossary');
      $button->image = 'actions-folder-add';
      if ($glossaryId = $this->parameters()->get('glossary_id', 0, new PapayaFilterInteger(1))) {
        $toolbar->elements[] = $button = new PapayaUiToolbarButton();
        $button->reference->setParameters(
          [ 'mode' => 'glossaries', 'cmd' => 'delete', 'glossary_id' => $glossaryId],
          $this->parameterGroup()
        );
        $button->caption = new PapayaUiStringTranslated('Delete glossary');
        $button->image = 'actions-folder-delete';
      }
      break;
    case 'ignore-words' :
      $toolbar->elements[] = $button = new PapayaUiToolbarButton();
      $button->reference->setParameters(
        [ 'mode' => 'ignore-words', 'cmd' => 'change'],
        $this->parameterGroup()
      );
      $button->caption = new PapayaUiStringTranslated('Add word');
      $button->image = 'actions-page-ignoreword-add';
      if ($wordId = $this->parameters()->get('ignore_word_id', 0, new PapayaFilterInteger(1))) {
        $toolbar->elements[] = $button = new PapayaUiToolbarButton();
        $button->reference->setParameters(
          [ 'mode' => 'ignore_words', 'cmd' => 'delete', 'ignore_word_id' => $wordId],
          $this->parameterGroup()
        );
        $button->caption = new PapayaUiStringTranslated('Delete ignore word');
        $button->image = 'actions-page-ignoreword-delete';
      }
      break;
    case 'terms' :
    default :
      $toolbar->elements[] = $button = new PapayaUiToolbarButton();
      $button->reference->setParameters(
        [ 'mode' => 'terms', 'cmd' => 'change'],
        $this->parameterGroup()
      );
      $button->caption = new PapayaUiStringTranslated('Add term');
      $button->image = 'actions-table-add';
      break;
    }
  }
}