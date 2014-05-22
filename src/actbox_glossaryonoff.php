<?php
/**
* Action box to activate/deactivate the glossary links
*
* @package commercial
* @subpackage glossary
* @version $Id: actbox_glossaryonoff.php 2 2013-12-09 14:13:52Z weinert $
*/

/**
* Basic class Aktion box
*/
require_once(PAPAYA_INCLUDE_PATH.'system/base_actionbox.php');

/**
* Action box to activate/deactivate the glossary links
*
* @package commercial
* @subpackage glossary
*/
class actionbox_glossaryonoff extends base_actionbox {

  var $preview = TRUE;

  var $editFields = array(
    'glossary_page_id' => array('Glossary page id', 'isNum', TRUE, 'pageid', 5),
    'Buttons',
    'btncap_activate'   => array('Activate', 'isSomeText', TRUE, 'input', 30),
    'btncap_deactivate' => array('Deactivate', 'isSomeText', TRUE, 'input', 30),
    'Texts',
    'text_activate'   => array('Activate', 'isSomeText', FALSE, 'input', 400),
    'text_deactivate' => array('Deactivate', 'isSomeText', FALSE, 'input', 400),
    'text_morelink'   => array('Browse Glossary', 'isSomeText', FALSE, 'input',
      100),
  );

  /**
  * Get parsed data
  *
  * @return string $result as xml
  */
  function getParsedData() {
    $this->initializeParams();
    $this->data['text'] = '';
    if (isset($GLOBALS['PAPAYA_PAGE'])) {
      $glossaryActive = $GLOBALS['PAPAYA_PAGE']->getPageOption('GLOSSARY_ACTIVE');
    }
    if (isset($glossaryActive)) {
      $glossaryActive = (bool)$glossaryActive;
    } else {
      $glossaryActive = TRUE;
    }
    if (isset($this->params['glossary'])) {
      if ($this->params['glossary'] == 'off' && $glossaryActive) {
        //deactivate glossary
        if (isset($GLOBALS['PAPAYA_PAGE'])) {
          $GLOBALS['PAPAYA_PAGE']->setPageOption('GLOSSARY_ACTIVE', FALSE);
          $this->redirect($this->baseLink);
        }
      } elseif ($this->params['glossary'] == 'off') {
        //glossary already deactivated
        $glossaryActive = FALSE;
      } elseif (!$glossaryActive) {
        //activate glossary
        if (isset($GLOBALS['PAPAYA_PAGE'])) {
          $GLOBALS['PAPAYA_PAGE']->setPageOption('GLOSSARY_ACTIVE', TRUE);
          $this->redirect($this->baseLink);
        }
      } else {
        //glossary already activated
        $glossaryActive = TRUE;
      }
    }

    $result = sprintf(
      '<glossary action="%s" active="%s">',
      papaya_strings::escapeHTMLChars($this->baseLink),
      $glossaryActive ? 'true' : 'false'
    );
    $result .= sprintf(
      '<input type="hidden" name="%s[glossary]" value="%s" />',
      $this->paramName,
      $glossaryActive ? 'off' : 'on'
    );
    if (!$glossaryActive) {
      $result .= sprintf(
        '<message type="on">%s</message>',
        papaya_strings::escapeHTMLChars(@$this->data['text_activate'])
      );
    } else {
      $result .= sprintf(
        '<message type="off">%s</message>',
        papaya_strings::escapeHTMLChars(@$this->data['text_deactivate'])
      );
    }
    $result .= sprintf(
      '<action type="on" title="%s" />',
      papaya_strings::escapeHTMLChars(@$this->data['btncap_activate'])
    );
    $result .= sprintf(
      '<action type="off" title="%s" />',
      papaya_strings::escapeHTMLChars(@$this->data['btncap_deactivate'])
    );
    $result .= sprintf(
      '<link type="more" href="%s" title="%s" />',
      $this->getWebLink(@$this->data['glossary_page_id']),
      papaya_strings::escapeHTMLChars(@$this->data['text_morelink'])
    );
    $result .= '</glossary>';
    return $result;
  }

  /**
  * Redirect to specified page url
  *
  * @param string $to absolute target url
  * @return
  */
  function redirect($to) {
    $url = base_object::getAbsoluteURL($to);
    $GLOBALS['PAPAYA_PAGE']->sendHTTPStatus(302);
    header('Location: '.$url);
    exit;
  }

  /**
  * Get cache id
  *
  * @return boolean FALSE
  */
  function getCacheId() {
    return FALSE;
  }
}
?>