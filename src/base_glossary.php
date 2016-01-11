<?php
/**
 * Module Glossary
 *
 * @package commercial
 * @subpackage glossary
 * @version $Id: base_glossary.php 5 2014-02-13 15:41:27Z SystemVCS $
 */

/**
 * Basic class for database access
 */
require_once(PAPAYA_INCLUDE_PATH.'system/sys_base_db.php');

/**
 * Module Glossary
 *
 * @package commercial
 * @subpackage glossary
 */
class base_glossary extends base_db {

  /**
   * Database table for glossaries
   * @var string $tableGlossary
   */
  var $tableGlossary = '';

  /**
   * Database table for glossary translations
   * @var string $tableGlossaryTran
   */
  var $tableGlossaryTrans = '';

  /**
   * Database table for glossary ignore words
   * @var string $tableGlossaryIgnoreWords
   */
  var $tableGlossaryIgnoreWords = '';

  /**
   * Database table for glossary entries
   * @var string $tableGlossaryEntries
   */
  var $tableGlossaryEntries = '';

  /**
   * Database table for glossary entry translations
   * @var string $tableGlossaryEntriesTrans
   */
  var $tableGlossaryEntriesTrans = '';

  /**
   * Array of existing glossaries
   * @var array $glossaries
   */
  var $glossaries = NULL;

  /**
   * Characters which are not allowed in dialog
   * @var string $nonWordChars as regular expression
   */
  var $nonWordChars = '\\x00-\\x26\\x28-\\x2C\\x2E-\\x2F\\x3A-\\x3F\\x5B-\\x5F\\x7B-\\x7F';

  /**
   * Minimum token byte length
   * @var integer $tokenMinLength
   */
  var $tokenMinLength = 2;

  var $addReferencePageParameters = false;

  /**
   * Constructor, set member variables for table names
   *
   * @param string $paramName optional, default value 'gl'
   */
  function __construct($paramName = 'gl') {
    $this->paramName = $paramName;
    $this->tableGlossary = PAPAYA_DB_TABLEPREFIX.'_glossary';
    $this->tableGlossaryTrans = PAPAYA_DB_TABLEPREFIX.'_glossary_trans';
    $this->tableGlossaryIgnoreWords =
      PAPAYA_DB_TABLEPREFIX.'_glossary_ignorewords';
    $this->tableGlossaryEntries = PAPAYA_DB_TABLEPREFIX.'_glossaryentries';
    $this->tableGlossaryEntriesTrans =
      PAPAYA_DB_TABLEPREFIX.'_glossaryentries_trans';
  }

  /**
   * Pipe to __construct()
   *
   * @param string $paramName optional, default value 'gl'
   */
  function base_glossary($paramName = 'gl') {
    $this->__construct($paramName);
  }

  /**
   * Fetches data of glossaries from database
   *
   * @param integer $lngId selected id of language translations
   * @return boolean load status
   */
  function loadGlossaries($lngId) {
    unset($this->glossaries);
    $sql = 'SELECT g.glossary_id, g.lng_id, g.glossary_title, g.glossary_text,
                   g.lng_id, gm.glossary_title AS main_language_title
            FROM %s AS g
            LEFT OUTER JOIN %s AS gm
              ON (g.glossary_id = gm.glossary_id AND gm.lng_id = %d)
            ORDER BY glossary_title ASC';
    $params = array(
      $this->tableGlossaryTrans, $this->tableGlossaryTrans,
      (int)PAPAYA_CONTENT_LANGUAGE
    );

    if ($res = $this->databaseQueryFmt($sql, $params)) {
      while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        if ($row['lng_id'] == $lngId ||
          !isset($this->glossaries[$row['glossary_id']])) {
          $this->glossaries[$row['glossary_id']] = $row;
        }
      }
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Gets all words from a string
   *
   * @param string $str input text
   * @return array with parsed words
   */
  function parseTextToWords($lngId, $str) {
    $words = array();
    $tokens = preg_split('~['.$this->nonWordChars.']+~', $str);
    $ignoreWords = $this->loadIgnoreWords($lngId);

    $checkIgnoreWords = FALSE;
    if (isset($ignoreWords) && is_array($ignoreWords) &&
      count($ignoreWords) > 0) {
      $ignoreWords = array_flip($ignoreWords);
      $checkIgnoreWords = TRUE;
    }

    if (is_array($tokens) && count($tokens) > 0) {
      foreach ($tokens as $key => $token) {
        if (
          strlen($token) > $this->tokenMinLength &&
          !array_key_exists($token, $words)
        ) {
          if (
            (
              $checkIgnoreWords &&
              !array_key_exists(papaya_strings::strtolower($token), $ignoreWords)
            ) || !$checkIgnoreWords
          ) {
            $words[$token] = TRUE;
          }
        }
      }
    }
    return array_keys($words);
  }

  /**
   * Loads ignore words for parse text to words in lower case
   *
   * @param integer $lngId current language id
   * @return mixed array with words to ignore or boolean false
   */
  function loadIgnoreWords($lngId) {
    $ignoreWords = array();
    $sql = "SELECT ignoreword_id, ignoreword
              FROM %s
             WHERE ignoreword_lngid = %d
             ORDER BY ignoreword";
    $params = array($this->tableGlossaryIgnoreWords, $lngId);
    if ($res = $this->databaseQueryFmt($sql, $params)) {
      while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $ignoreWords[] = $row['ignoreword'];
      }
      return $ignoreWords;

    }
    return FALSE;
  }

  /**
   * Loads all entries for a given word list
   *
   * @param array $words words
   * @param integer $lng language id
   * @param integer $currentPageId current page id for links
   * @param integer $targetPageId target page (glossary page) id for links
   * @param boolean $storedIds store ids or not
   * @param array $glossaryIds optional array to set glossary filter
   * @return boolean found something to replace
   */
  function loadTermLinksByWordList(
    $words, $lngId, $currentPageId, $targetPageId, $storeIds = FALSE, $glossaryIds = NULL
  ) {
    unset($this->termLinks);
    if (isset($GLOBALS['PAPAYA_PAGE'])) {
      $glossaryActive = $GLOBALS['PAPAYA_PAGE']->getPageOption('GLOSSARY_ACTIVE');
    }
    if (isset($glossaryActive)) {
      $glossaryActive = (bool)$glossaryActive;
    } else {
      $glossaryActive = TRUE;
    }

    if ($glossaryActive && isset($words) && is_array($words) && count($words) > 0) {
      if (
        isset($glossaryIds) && is_array($glossaryIds) && count($glossaryIds) > 0
      ) {
        $filterFirstToken = $this->databaseGetSQLCondition('get.glossaryentry_firsttoken', $words);
        $filterGlossaries = $this->databaseGetSQLCondition('ge.glossary_id', $glossaryIds);
        $sql = "SELECT ge.glossaryentry_id, get.glossaryentry_term,
                       get.glossaryentry_firsttoken
                FROM %s AS ge
                LEFT OUTER JOIN %s AS get
                  ON (ge.glossaryentry_id = get.glossaryentry_id)
               WHERE get.lng_id = '%d'
                 AND $filterGlossaries
                 AND $filterFirstToken";
        $params = array(
          $this->tableGlossaryEntries,
          $this->tableGlossaryEntriesTrans,
          (int)$lngId
        );
      } else {
        $filter = $this->databaseGetSQLCondition('glossaryentry_firsttoken', $words);
        $sql = "SELECT glossaryentry_id, glossaryentry_term,
                       glossaryentry_firsttoken
                FROM %s
               WHERE lng_id = '%d'
                 AND $filter";
        $params = array($this->tableGlossaryEntriesTrans, (int)$lngId);
      }

      if ($res = $this->databaseQueryFmt($sql, $params, 100)) {
        $keyWords = array_flip($words);
        $rows = array();
        while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
          if (array_key_exists($row['glossaryentry_firsttoken'], $keyWords)) {
            $rows[] = $row;
          }
        }
        foreach ($rows as $row) {
          $parameters = array(
            'entry_id'  => (int)$row['glossaryentry_id']
          );
          if ($this->addReferencePageParameters) {
            $parameters['refpage'] = $currentPageId;
            $parameters['urlparams'] = empty($_SERVER['QUERY_STRING']) ? '' : $_SERVER['QUERY_STRING'];
          }
          $this->termLinks[$row['glossaryentry_term']] = sprintf(
            '<a href="%s" class="glossaryTermLink">%s</a>',
            PapayaUtilStringXml::escapeAttribute(
              $this->getWebLink(
                $targetPageId,
                '',
                '',
                $parameters,
                'bab',
                $row['glossaryentry_term']
              )
            ),
            PapayaUtilStringXml::escape($row['glossaryentry_term'])
          );
          if ($storeIds) {
            $this->storedWords[$row['glossaryentry_term']] = $row['glossaryentry_id'];
          }
        }
        if (isset($this->termLinks) && is_array($this->termLinks)) {
          uksort($this->termLinks, array($this, 'compareArrayKeyLength'));
          return (count($this->termLinks) > 0);
        }
      }
    }
    return FALSE;
  }

  /**
   * Load glossary entries by specified ids
   *
   * @param integer $lngId selected id of language translations
   * @param mixed $ids optional, default value NULL
   * @return array loaded entries
   */
  function getGlossaryEntriesByIds($lngId, $ids = NULL) {
    $result = array();
    if ($ids == NULL && isset($this->storedIds) && is_array($this->storedIds)) {
      $ids = $this->storedIds;
    }
    if (isset($ids) && ((is_array($ids) && count($ids) > 0) || $ids > 0)) {
      $condition = $this->databaseGetSQLCondition('glossaryentry_id', $ids);
      $sql = "SELECT glossaryentry_id, glossaryentry_term,
                     glossaryentry_explanation, glossaryentry_derivation,
                     glossaryentry_synonyms, glossaryentry_abbreviations,
                     glossaryentry_source, glossaryentry_links
                FROM %s
               WHERE lng_id = '%d' AND $condition";
      $params = array($this->tableGlossaryEntriesTrans, $lngId);
      if ($res = $this->databaseQueryFmt($sql, $params)) {
        while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
          $result[$row['glossaryentry_id']] = $row;
        }
      }
    }
    return $result;
  }

  /**
   * Compares key length of two given array keys
   *
   * @param string $key1 first array key
   * @param string $key2 second array key
   * @return integer array length or 0
   */
  function compareArrayKeyLength($key1, $key2) {
    if (strlen($key1) == strlen($key2)) {
      return 0;
    } else {
      return (strlen($key1) > strlen($key2)) ? -1 : 1;
    }
  }

  /**
   * Replace glossary words with glossary term links
   *
   * @param string $str input string to work with
   * @return string result or input string
   */
  function replaceGlossaryWords($str) {
    if (
      isset($this->termLinks) && is_array($this->termLinks) && count($this->termLinks) > 0
    ) {
      $offset = 0;
      $max = strlen($str);
      $next = strpos($str, '<');

      $replaceStrings = TRUE;
      $result = '';
      while ($offset < $max && $next !== FALSE) {
        $buffer = substr($str, $offset, $next - $offset);
        $offset = $next;
        if ($replaceStrings) {
          $next = FALSE;
          $replaceStrings = FALSE;
          if (strlen($buffer) > 0) {
            $this->rememberEntriesInString($buffer);
            $result .= strtr($buffer, $this->termLinks);
          }
          $next = @strpos($str, '>', $offset);
          if ($next !== FALSE) {
            $next++;
          }
        } else {
          $replaceStrings = TRUE;
          $result .= $buffer;
          $next = @strpos($str, '<', $offset);
        }
      }
      if ($replaceStrings && $offset < $max) {
        $buffer = substr($str, $offset);
        $this->rememberEntriesInString($buffer);
        $result .= strtr($buffer, $this->termLinks);
      } else {
        $result .= substr($str, $offset);
      }
      // Remove nested term links
      $result = preg_replace(
        '((<a(?:\s[^<>]*)?>)([^<>]*)(<a(?:\s[^<>]*)?>))u',
        '$1$2',
        $result
      );
      $result = preg_replace(
        '((<\/a(?:\s[^<>]*)?>)([^<>]*)(<\/a(?:\s[^<>]*)?>))u',
        '$1$2',
        $result
      );
      return $result;

    }
    return $str;
  }

  /**
   * Set stored ids when a buffer matches in term and removes stored words
   *
   * @param string $buffer
   */
  function rememberEntriesInString($buffer) {
    if (
      isset($this->storedWords) && is_array($this->storedWords) && count($this->storedWords) > 0
    ) {
      foreach ($this->storedWords as $term => $id) {
        if (FALSE !== strpos($buffer, $term)) {
          $this->storedIds[] = $id;
          unset($this->storedWords[$term]);
        }
      }
    }
  }

}