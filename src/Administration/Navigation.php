<?php

class GlossaryAdministrationNavigation extends PapayaAdministrationPagePart {

  /**
   * Overload this method to create the commands structure.
   *
   * @param string $name
   * @param string $default
   * @return PapayaUiControlCommandController
   */
  protected function _createCommands($name = 'cmd', $default = 'terms') {
    $modes = parent::_createCommands($name, $default);
    $modes->parameterGroup($this->parameterGroup());
    $modes['terms'] = new GlossaryAdministrationNavigationTerms();
    $modes['glossaries'] = new GlossaryAdministrationNavigationGlossaries();
    $modes['ignore-words'] = new GlossaryAdministrationNavigationIgnores();
    return $modes;
  }
}