<?php
/**
* Glossary data filter
*
* @package commercial
* @subpackage glossary
* @version $Id: datafilter_glossary.php 5 2014-02-13 15:41:27Z SystemVCS $
*/

/**
* Basic class page module
*/
require_once(PAPAYA_INCLUDE_PATH.'system/base_datafilter.php');

/**
* Glossary data filter
*
* @package commercial
* @subpackage glossary
*/
class datafilter_glossary extends base_datafilter {

  /**
  * base_glossary plugin object
  * @var object $baseGlossary
  */
  var $glossaryObj = NULL;

  /**
  * Topic id of owner object
  * @var integer $topicId
  */
  var $topicId = 0;

  /**
  * Language id of owner object
  * @var integer $lngId
  */
  var $lngId = 0;

  /**
  * Edit fields
  * @var array $editFields
  */
  var $editFields = array(
    'glossary_page_id' => array ('Glossary page id', 'isNum', TRUE, 'pageid', 15),
    'all_glossaries'   => array('All glossaries', 'isNum', TRUE, 'yesno', null, '', '0'),
    'glossary'         => array('Glossary', 'isNum', FALSE, 'function',
      'callbackGlossaryCombo'),
    'add_refpage' => array('Add reference page parameters', 'isNum', TRUE, 'yesno', null, '', 0)
  );

  /**
  * Initialize glossary object and variables
  *
  * @param object $contentObj object of content by base_datafilter_list
  * @return boolean status result
  */
  function initialize($contentObj) {
    $this->initGlossaryObject();
    if (isset($this->glossaryObj) && is_object($this->glossaryObj)) {
      $this->glossaryObj->addReferencePageParameters = isset($this->data['add_refpage'])
        ? (bool)$this->data['add_refpage'] : false;
      $this->lngId = $contentObj->parentObj->topic['TRANSLATION']['lng_id'];
      $this->topicId = $contentObj->parentObj->topicId;
      if (isset($this->lngId) && isset($this->topicId) &&
          $this->glossaryObj->loadGlossaries($this->lngId) &&
          isset($this->glossaryObj->lngSelect)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
  * Initializes glossary object to handle glossaries and entries later
  */
  function initGlossaryObject() {
    if (!(isset($this->glossaryObj) && is_object($this->glossaryObj))) {
      include_once(dirname(__FILE__).'/base_glossary.php');
      $this->glossaryObj = new base_glossary();
    }
  }

  /**
  * Callback function to generate combo for existing glossaries
  *
  * @return string $result xml
  */
  function callbackGlossaryCombo() {
    $result = '';
    $this->initGlossaryObject();
    if (isset($this->glossaryObj) && is_object($this->glossaryObj)) {
      include_once(PAPAYA_INCLUDE_PATH.'system/base_language_select.php');
      $this->lngSelect = &base_language_select::getInstance();
      $this->glossaryObj->loadGlossaries($this->lngSelect->currentLanguageId);

      if (isset($this->glossaryObj->glossaries) &&
          is_array($this->glossaryObj->glossaries) &&
          count($this->glossaryObj->glossaries) > 0) {
        $result .= sprintf(
          '<select name="%s[glossary]" class="dialogSelect dialogScale">', $this->paramName
        );
        foreach ($this->glossaryObj->glossaries as $k => $v) {
          $result .= '';
          $selected = ($k == $this->data['glossary']) ? ' selected="selected"' : '';
          $result .= sprintf(
            '<option value="%s"%s>%s </option>',
            $k,
            $selected,
            papaya_strings::escapeHTMLChars($v['glossary_title'])
          );
        }
        $result .= '</select>';
      }
    }
    return $result;

  }

  /**
  * Prepares filter data
  *
  * @param array $data data array of content
  * @param array $keys array of keys to select from $data
  * @return mixed array word list or boolean false
  */
  function prepareFilterData(&$data, $keys) {
    if (isset($this->glossaryObj) && is_object($this->glossaryObj)) {
      $textToParse = '';
      foreach ($keys as $key) {
        $textToParse .= $data[$key].',';
      }
      $textToParse = substr($textToParse, 0, strlen($textToParse) - 1);
      $data['glossary_words'] = $this->glossaryObj->parseTextToWords($this->lngId, $textToParse);
    }
  }

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
      if (isset($this->data['all_glossaries']) &&
          ((int)$this->data['all_glossaries'] == 0) &&
          isset($this->data['glossary']) && $this->data['glossary'] > 0) {
        $glossaryIds = array($this->data['glossary']);
      }

      return (bool)$this->glossaryObj->loadTermLinksByWordList(
        $data['glossary_words'],
        $this->lngId,
        $this->topicId,
        !empty($this->data['glossary_page_id']) ? $this->data['glossary_page_id'] : NULL,
        TRUE,
        $glossaryIds
      );
    }
    return FALSE;
  }

  /**
  * Replace words with glossary words / links in text
  *
  * @param string $str text input
  * @return string $str text result / output
  */
  function applyFilterData($str) {
    if (isset($this->glossaryObj) && is_object($this->glossaryObj)) {
      return $this->glossaryObj->replaceGlossaryWords($str);
    }
    return $str;
  }

  /**
  * Gets xml output of glossary entries
  *
  * @param array $parseParams parsing params
  * @return string $result xml
  */
  function getFilterData($parseParams = NULL) {
    $result = '';
    if (isset($this->glossaryObj) && is_object($this->glossaryObj) &&
        isset($parseParams['fullpage']) && $parseParams['fullpage']) {

      $entries = NULL;
      if (!empty($this->glossaryObj->storedIds)) {
        $entries = $this->glossaryObj->getGlossaryEntriesByIds(
          $this->lngId, $this->glossaryObj->storedIds
        );
      }

      if (is_array($entries) && count($entries) > 0) {
        $result .= '<glossary>'.LF;
        foreach ($entries as $id => $entry) {
          $result .= sprintf(
            '<glossaryentry id="%d" term="%s">'.LF,
            $id,
            papaya_strings::escapeHTMLChars($entry['glossaryentry_term'])
          );
          $result .= sprintf(
            '<explanation>%s</explanation>'.LF,
            $this->getXHTMLString($entry['glossaryentry_explanation'])
          );
          $result .= sprintf(
            '<derivation>%s</derivation>'.LF,
            $this->getXHTMLString($entry['glossaryentry_derivation'])
          );
          $result .= sprintf(
            '<synonyms>%s</synonyms>'.LF,
            $this->getXHTMLString($entry['glossaryentry_synonyms'])
          );
          $result .= sprintf(
            '<abbreviations>%s</abbreviations>'.LF,
            $this->getXHTMLString($entry['glossaryentry_abbreviations'])
          );
          $result .= sprintf(
            '<source>%s</source>'.LF,
            $this->getXHTMLString($entry['glossaryentry_source'])
          );
          $result .= $this->getLinksXML($entry['glossaryentry_links']);
          $result .= '</glossaryentry>';
        }
        $result .= '</glossary>';
      }
    }
    return $result;
  }

  /**
  * Generates XML link tags from string
  *
  * Syntax of $dataStr (one link each line)
  *   LINKTEXT=http://www.link.org
  *   http://www.link.org
  *
  * @param string $dataStr string to generate links from
  * @return string $result XML string formed <link href="url" title="linktext" />
  */
  function getLinksXML($dataStr) {
    $result = '';
    if (trim($dataStr) != '') {
      $links = array();
      if (($lines = explode("\n", $dataStr)) && is_array($lines)) {
        foreach ($lines as $line) {
          $s = trim($line);
          if ($s != '') {
            $links[] = $s;
          }
        }
      }
      if (count($links) > 0) {
        foreach ($links as $link) {
          if (strpos($link, '=') > 0) {
            list($caption, $href) = explode('=', trim($link), 2);
          } else {
            $href = trim($link);
            $caption = $href;
          }
          $result .= sprintf(
            '<link href="%s" title="%s" />'.LF,
            htmlspecialchars($href),
            htmlspecialchars($caption)
          );
        }
      }
    }
    return $result;
  }

}
?>