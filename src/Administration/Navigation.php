<?php

class GlossaryAdministrationNavigation extends PapayaAdministrationPagePart {

  /**
   * Overload this method to create the commands structure.
   *
   * @param string $name
   * @param string $default
   * @return PapayaUiControlCommandController
   */
  protected function _createCommands($name = 'cmd', $default = 'show') {
    $commands = parent::_createCommands('mode', 'terms');
    $commands->parameterGroup($this->parameterGroup());
    $commands['terms'] = new GlossaryAdministrationNavigationTerms();
    $commands['glossaries'] = new GlossaryAdministrationNavigationGlossaries();
    $commands['ignore-words'] = new GlossaryAdministrationNavigationIgnores();
    return $commands;
  }
}