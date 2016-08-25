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
    $commands = parent::_createCommands($name, $default);
    $commands->parameterGroup($this->parameterGroup());
    $commands['terms'] = new GlossaryAdministrationContentTerms();
    $commands['glossaries'] = $subCommands = new PapayaUiControlCommandController('cmd', 'edit');
    $subcommands['edit'] = new GlossaryAdministrationContentGlossaryChange();
    $commands['ignore-words'] = new GlossaryAdministrationContentIgnores();
    return $commands;
  }

  /**
   * @param PapayaUiToolbarSet $toolbar
   */
  protected function _initializeToolbar(PapayaUiToolbarSet $toolbar) {
    parent::_initializeToolbar($toolbar);
    $toolbar->elements[] = $select = new PapayaUiToolbarSelectButtons(
      [$this->parameterGroup(), 'mode'],
      [
        'terms' => [
          'caption' => 'Terms',
          'image' => 'categories-view-list'
        ],
        'glossaries' => [
          'caption' => 'Glossaries',
          'image' => 'items-folder'
        ],
        'ignore-words' => [
          'caption' => 'Ignore words',
          'image' => 'items-page-ignoreword'
        ]
      ]
    );
    $select->defaultValue = 'terms';
    $toolbar->elements[] = new PapayaUiToolbarSeparator();
    switch ($this->parameters()->get('mode')) {
    case 'glossaries' :
      $toolbar->elements[] = $button = new PapayaUiToolbarButton();
      $button->reference->setParameters(
        [ 'mode' => 'glossaries', 'cmd' => 'add'],
        $this->parameterGroup()
      );
      $button->caption = new PapayaUiStringTranslated('Add glossary');
      $button->image = 'actions-folder-add';
      break;
    case 'ignore-words' :
      $toolbar->elements[] = $button = new PapayaUiToolbarButton();
      $button->reference->setParameters(
        [ 'mode' => 'ignore-words', 'cmd' => 'add'],
        $this->parameterGroup()
      );
      $button->caption = new PapayaUiStringTranslated('Add glossary');
      $button->image = 'actions-folder-add';
      break;
    case 'terms' :
    default :
      $toolbar->elements[] = $button = new PapayaUiToolbarButton();
      $button->reference->setParameters(
        [ 'mode' => 'terms', 'cmd' => 'add'],
        $this->parameterGroup()
      );
      $button->caption = new PapayaUiStringTranslated('Add term');
      $button->image = 'actions-table-add';
      break;
    }
  }
}