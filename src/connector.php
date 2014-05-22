<?php
/**
 * Glossary Connector
 *
 * @copyright 2002-20013 by papaya Software GmbH - All rights reserved.
 * @link http://www.papaya-cms.com/
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License, version 2
 *
 * You can redistribute and/or modify this script under the terms of the GNU General Public
 * License (GPL) version 2, provided that the copyright and license notes, including these
 * lines, remain unmodified. papaya is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.
 *
 * @package Papaya-Modules
 * @subpackage commercial
 * @version $Id: connector.php 2 2013-12-09 14:13:52Z weinert $
 */

/**
 * Basic class connector
 */
require_once(PAPAYA_INCLUDE_PATH.'system/base_connector.php');

/**
 * Connector for the Search Module
 *
 * @package Gi
 * @subpackage Search
 */
class PapayaModuleCommercialGlossaryConnector extends base_connector {

  /**
   * The module guid
   *
   * @var string
   */
  private $_moduleGUID = 'd69f7409d3616a11795a7005dbddce24';

  /**
   * @var null|base_glossary
   */
  private $_baseGlossary = NULL;

  /**
   * The plugin options
   * @var array
   */
  public $pluginOptionFields = array();

  /**
   * @var null|base_module_options
   */
  private $_optionsObject = NULL;

  /**
   * @param base_module_options $optionsObject
   * @return base_module_options
   */
  public function _optionsObject(base_module_options $optionsObject = NULL) {
    if (NULL !== $optionsObject) {
      $this->_optionsObject = $optionsObject;
    }
    if (NULL === $this->_optionsObject) {
      include_once(PAPAYA_INCLUDE_PATH.'system/base_module_options.php');
      $this->_optionsObject = new base_module_options();
    }
    return $this->_optionsObject;
  }

  /**
   * @param base_glossary $base
   * @return base_glossary|null
   */
  public function baseGlossary(base_glossary $base = NULL) {
    if (isset($base)) {
      $this->_baseGlossary = $base;
    } elseif (is_null($this->_baseGlossary)) {
      include_once(dirname(__FILE__).'/base_glossary.php');
      $this->_baseGlossary = new base_glossary();
    }
    return $this->_baseGlossary;
  }

  /**
   * @param int $id
   * @param int $lngId
   * @return null|array
   */
  public function loadEntry($id, $lngId) {
    $result = $this->baseGlossary()->getGlossaryEntriesByIds($lngId, array($id));
    if (is_array($result) && count($result) > 0 && isset($result[$id])) {
      return $result[$id];
    }
    return NULL;
  }

  /**
   * @param int $id
   * @param int $lngId
   * @return null
   */
  public function getTerm($id, $lngId) {
    $entry = $this->loadEntry($id, $lngId);

    if ($entry != NULL) {
      return $entry['glossaryentry_term'];
    }
    return NULL;
  }
}