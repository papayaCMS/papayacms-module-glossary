<?php
/**
* Module Glossary Two - rewrite to new structure
*
* Glossary administration
*
* @package glossary
* @version $Id: edmodule_glossary.php 6 2014-02-20 12:06:00Z SystemVCS $
*/

/**
* Basic module class
*/
require_once(PAPAYA_INCLUDE_PATH.'system/base_module.php');

/**
* Module Glossary
*
* Glossary administration
*
* @package glossary
* @package module_glossary
*/
class edmodule_glossary extends base_module {
  /**
  * Plugin option fields
  * @var array
  */
  var $pluginOptionFields = array(
    'LANG_SELECT_PARAMNAME' => array(
      'Language Selector Parameter Namespace',
      'isNoHTML',
      TRUE,
      'input',
      255,
      '',
      'lngsel'
    )
  );

  var $permissions = array(
    GlossaryAdministration::PERMISSION_MANAGE => 'Manage',
    GlossaryAdministration::PERMISSION_MANAGE_GLOSSARIES => 'Create/Edit glossaries',
    GlossaryAdministration::PERMISSION_MANAGE_TERMS => 'Create/Edit glossary entries',
    GlossaryAdministration::PERMISSION_MANAGE_IGNORE => 'Create/Edit glossary ignorewords'
  );

  function execModule() {
    if ($this->hasPerm(GlossaryAdministration::PERMISSION_MANAGE, TRUE)) {
      $administration = new GlossaryAdministration(
        $this->layout,
        $this->guid
      );
      $administration->papaya($this->papaya());
      $administration->execute();
    }
  }
}
