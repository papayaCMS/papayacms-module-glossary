<?php
/**
* Module Glossary
*
* Glossary administration
*
* @package commercial
* @subpackage glossary
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
* @package commercial
* @subpackage glossary
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
    1 => 'Manage',
    2 => 'Create/Edit glossaries',
    3 => 'Create/Edit glossary entries',
    4 => 'Create/Edit glossary ignorewords',
    //Normalize entries:
    5 => 'Fix sorting'
  );

  function execModule() {
    if ($this->hasPerm(1, TRUE)) {
      $path = dirname(__FILE__);
      include_once($path.'/papaya_glossary.php');
      $glossary = new papaya_glossary;
      $glossary->module = $this;
      $glossary->images = $this->images;
      $glossary->layout = $this->layout;
      $glossary->authUser = $this->authUser;
      $glossary->initialize();
      $glossary->execute();
      $glossary->getXML();
    }
  }

}
?>
