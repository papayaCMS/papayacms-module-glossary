<?php

class GlossaryAdministration extends PapayaAdministrationPage {

  const PERMISSION_MANAGE = 1;
  const PERMISSION_MANAGE_GLOSSARIES = 2;
  const PERMISSION_MANAGE_TERMS = 3;
  const PERMISSION_MANAGE_IGNORE = 4;
  const PERMISSION_MANAGE_INDEX = 5;
  const PERMISSION_EXPORT = 6;

  protected $_parameterGroup = 'glossary';

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