<?php

class GlossaryAdministration extends PapayaAdministrationPage {

  const PERMISSION_MANAGE = 1;
  const PERMISSION_MANAGE_GLOSSARIES = 2;
  const PERMISSION_MANAGE_TERMS = 3;
  const PERMISSION_MANAGE_IGNORE = 4;

  protected $_parameterGroup = 'glossary';

  /**
   * @var string
   */
  private $_moduleId;

  public function __construct(PapayaTemplate $layout, $moduleId) {
    parent::__construct($layout);
    $this->_moduleId = $moduleId;
  }

  public function getModuleId() {
    return $this->_moduleId;
  }

  protected function createContent() {
    return new GlossaryAdministrationContent($this);
  }

  protected function createNavigation() {
    return new GlossaryAdministrationNavigation($this);
  }

  protected  function createInformation() {
    return new GlossaryAdministrationInformation($this);
  }
}