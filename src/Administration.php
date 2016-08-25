<?php

class GlossaryAdministration extends PapayaAdministrationPage {

  const PERMISSION_MANAGE = 1;
  const PERMISSION_MANAGE_GLOSSARIES = 2;
  const PERMISSION_MANAGE_ENTRIES = 3;
  const PERMISSION_MANAGE_IGNORE = 4;

  protected $_parameterGroup = 'glossary';

  private $_permissions = [];

  public function __construct(PapayaTemplate $layout, array $permissions) {
    parent::__construct($layout);
    $this->_permissions = $permissions;
  }

  protected function createContent() {
    return new GlossaryAdministrationContent();
  }

  protected function createNavigation() {
    return new GlossaryAdministrationNavigation();
  }
}