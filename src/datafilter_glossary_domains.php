<?php
/**
* Domain specific glossary data filter
*
* @package commercial
* @subpackage glossary
* @version $Id: datafilter_glossary_domains.php 2 2013-12-09 14:13:52Z weinert $
*/

/**
* Glossary data filter
*/
require_once(dirname(__FILE__) . '/datafilter_glossary.php');

/**
* Domain specific glossary data filter
*
* @package commercial
* @subpackage glossary
*/
class datafilter_glossary_domains extends datafilter_glossary {

  /**
  * GUID of domain connector
  * @var string
  */
  private $domainConnectorGuid = '8ec0c5995d97c9c3cc9c237ad0dc6c0b';

  /**
  * Load glossary words for text
  *
  * @param string $str text input
  * @return boolean status
  */
  function loadFilterData($data) {
    if (isset($this->glossaryObj) && is_object($this->glossaryObj) &&
        isset($data['glossary_words'])) {

      $glossaryIds = NULL;
      if (isset($this->data['all_glossaries']) && (int)$this->data['all_glossaries'] == 0) {

        $glossaryIds = array();
        if (isset($this->data['glossary']) && $this->data['glossary'] > 0) {
          $glossaryIds[] = $this->data['glossary'];
        }
        $domainConnector = base_pluginloader::getPluginInstance(
          $this->domainConnectorGuid,
          $this
        );
        // load ID of domain specific glossary
        if ($domaindGlossar = $domainConnector->loadValues('DOMAIN-GLOSSARS')) {
          $glossaryIds[] = $domaindGlossar['DOMAIN-GLOSSARS'];
        }
      }

      if (
        $this->glossaryObj->loadTermLinksByWordList(
          $data['glossary_words'],
          $this->lngId,
          $this->topicId,
          !empty($this->data['glossary_page_id']) ? $this->data['glossary_page_id'] : NULL,
          TRUE,
          $glossaryIds
        )
      ) {
        return TRUE;
      }
    }
    return FALSE;
  }
}
?>