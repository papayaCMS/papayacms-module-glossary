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

    $modes['terms'] = $commands = new PapayaUiControlCommandController('cmd', 'change');
    $commands->permission([$moduleId, GlossaryAdministration::PERMISSION_MANAGE_TERMS]);
    $commands['change'] = new GlossaryAdministrationContentTermChange();
    $commands['delete'] = new GlossaryAdministrationContentTermDelete();
    $commands['delete-translation'] = new GlossaryAdministrationContentTermTranslationDelete();
    $commands['reindex'] = $command = new GlossaryAdministrationContentTermReindex();
    $command->permission([$moduleId, GlossaryAdministration::PERMISSION_MANAGE_INDEX]);
    $commands['export'] = $command = new GlossaryAdministrationContentTermExport();
    $command->permission([$moduleId, GlossaryAdministration::PERMISSION_EXPORT]);


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
          [ 'mode' => 'ignore-words', 'cmd' => 'delete', 'ignore_word_id' => $wordId],
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
      /** @var PapayaUiControlCommand $command */
      $command = $this->commands()['terms']['delete'];
      if ($command->condition()->validate()) {
        $toolbar->elements[] = $button = new PapayaUiToolbarButton();
        $button->reference->setParameters(
          [
            'mode' => 'terms',
            'cmd' => 'delete',
            'term_id' => $this->parameters()->get('term_id'),
            'offset' => $this->parameters()->get('offset', 0),
            'search-for' => $this->parameters()->get('search-for', ''),
            'glossary_id' => $this->parameters()->get('glossary_id', 0)
          ],
          $this->parameterGroup()
        );
        $button->caption = new PapayaUiStringTranslated('Delete term');
        $button->image = 'actions-table-delete';
        /** @var PapayaUiControlCommand $command */
        $command = $this->commands()['terms']['delete-translation'];
        if ($command->condition()->validate()) {
          $toolbar->elements[] = $button = new PapayaUiToolbarButton();
          $button->reference->setParameters(
            [
              'mode' => 'terms',
              'cmd' => 'delete-translation',
              'term_id' => $this->parameters()->get('term_id'),
              'offset' => $this->parameters()->get('offset', 0),
              'search-for' => $this->parameters()->get('search-for', ''),
              'glossary_id' => $this->parameters()->get('glossary_id', 0)
            ],
            $this->parameterGroup()
          );
          $button->caption = new PapayaUiStringTranslated('Delete term translation');
          $button->image = 'actions-phrase-delete';
        }
      }
      $toolbar->elements[] = new PapayaUiToolbarSeparator();
      /** @var PapayaUiControlCommand $command */
      $command = $this->commands()['terms']['reindex'];
      if ($command->condition()->validate()) {
        $toolbar->elements[] = $button = new PapayaUiToolbarButton();
        $button->reference->setParameters(
          ['mode' => 'terms', 'cmd' => 'reindex'],
          $this->parameterGroup()
        );
        $button->caption = new PapayaUiStringTranslated('Rebuild index');
        $button->image = 'actions-tree-scan';
      }
      /** @var PapayaUiControlCommand $command */
      $command = $this->commands()['terms']['export'];
      if ($command->condition()->validate()) {
        $toolbar->elements[] = $button = new PapayaUiToolbarButton();
        $button->reference->setParameters(
          [
            'mode' => 'terms',
            'cmd' => 'export',
            'search-for' => $this->parameters()->get('search-for', ''),
            'glossary_id' => $this->parameters()->get('glossary_id', 0)
          ],
          $this->parameterGroup()
        );
        $button->caption = new PapayaUiStringTranslated('Export CSV');
        $button->image = 'actions-download';
      }
      break;
    }
  }
}