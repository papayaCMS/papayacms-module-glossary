<?php
/**
 * Glossary Content Output
 *
 * @package commercial
 * @subpackage glossary
 * @version $Id: content_glossary.php 5 2014-02-13 15:41:27Z SystemVCS $
 */

/**
 * Basic class page module
 */
require_once(PAPAYA_INCLUDE_PATH.'system/base_content.php');

/**
 * Basic search string parser
 */
require_once(PAPAYA_INCLUDE_PATH.'system/base_searchstringparser.php');

/**
 * Basic glossary module
 */
require_once(dirname(__FILE__).'/base_glossary.php');

/**
 * Glossary Content Output
 *
 * @package commercial
 * @subpackage glossary
 */
class content_glossary extends base_content {

  var $editFields = array(
    'nl2br'    => array('Automatic linebreak', 'isNum', FALSE, 'combo',
      array(0 => 'Yes', 1 => 'No'),
      'Papaya will apply your linebreaks to the output page.', 1),
    'title'    => array('Title', 'isNoHTML', TRUE, 'input', 200, ''),
    'teaser'   => array('Teaser', 'isSomeText', FALSE, 'simplerichtext', 10, '', ''),
    'text'     => array('Text', 'isSomeText', FALSE, 'richtext', 10, '', ''),
    'steps'    => array('Entries per page', 'isNum', TRUE, 'input', 200, '', 30),
    'search'   => array('Search Button Title', 'isNoHTML', TRUE, 'input', 200,
      '', 'Search'),
    'Glossary',
    'all'      => array('All', 'isNum', TRUE, 'yesno', '0'),
    'glossary' => array('glossary', 'isNum', FALSE, 'function',
      'callbackGlossaryCombo')
  );

  var $fullTextSearch = PAPAYA_SEARCH_BOOLEAN;

  var $glossaries = NULL;

  var $entriesAbsCount = 0;

  var $tableTopicTrans = PAPAYA_DB_TBL_TOPICS_TRANS;

  var $loadDetailedLists = FALSE;

  /**
   * GUID of domain connector
   * @var string
   */
  private $domainConnectorGuid = '8ec0c5995d97c9c3cc9c237ad0dc6c0b';

  /**
   * Initialize parameters
   */
  function initialize() {
    $this->glossaryObj = new base_glossary();
    $this->glossaryObj->loadGlossaries($this->parentObj->getContentLanguageId());
    $this->glossaries = $this->glossaryObj->glossaries;

    $this->sessionParamName = 'PAPAYA_SESS_'.$this->paramName;
    $this->initializeParams();
    $this->sessionParams = $this->getSessionValue($this->sessionParamName);
    $this->initializeSessionParam('offset');
    $this->setSessionValue($this->sessionParamName, $this->sessionParams);

    include_once(PAPAYA_INCLUDE_PATH.'system/base_language_select.php');
    $this->lngSelect = base_language_select::getInstance();
  }

  /**
   * Evaluates submittet form and performs actions
   */
  function execute() {
    if (!isset($this->params['offset'])) {
      $this->params['offset'] = 0;
    }

    if (isset($this->data['all']) && $this->data['all'] == 1) {
      $glossaryKeys = array(-1);
      if (isset($this->glossaries) && is_array($this->glossaries)) {
        $glossaryKeys = array_keys($this->glossaries);
      }
      $this->glossaryIds = $glossaryKeys;
    } else {
      $this->glossaryIds = array(@(int)$this->data['glossary']);
    }
    if (isset($this->params['char']) || isset($this->params['searchfor']) ||
      (isset($this->params['entry_id']) && $this->params['entry_id'] > 0) ||
      isset($this->params['mode'])) {
      if (isset($this->params['reset'])) {
        unset($this->params['offset']);
      }
    } else {
      unset($this->params['offset']);
      $this->params['mode'] = 'abc';
    }
    unset($this->glossaryEntries);
    $this->loadEntries();
    $this->logStatistic();

  }

  /**
   * Get parsed data
   *
   * @return string $result parsed data as XML
   */
  function getParsedData($outputParams) {
    $glossaryIds = array();
    $domainConnector = base_pluginloader::getPluginInstance(
      $this->domainConnectorGuid,
      $this
    );
    if ($domaindGlossar = $domainConnector->loadValues('DOMAIN-GLOSSARS')) {
      $glossaryIds[] = $domaindGlossar['DOMAIN-GLOSSARS'];
    }
    if (isset($outputParams['fullpage']) && $outputParams['fullpage']) {
      $this->loadDetailedLists = TRUE;
    } else {
      $this->loadDetailedLists = FALSE;
    }
    $this->initialize();
    $this->execute();

    $result = '';
    if (isset($this->params['refpage'])) {
      $result .= '<robots>follow, noindex</robots>';
    }
    if (! empty($glossaryIds)) {
      foreach ($glossaryIds as $glossary) {
        $result .= sprintf('<domain-glossary>%d</domain-glossary>' . LF, $glossary);
      }
    }
    $result .= sprintf(
      '<title encoded="%s">%s</title>' . LF,
      rawurlencode(@$this->data['title']),
      PapayaUtilStringXml::escape(@$this->data['title'])
    );
    $result .= sprintf(
      '<teaser>%s</teaser>'.LF,
      $this->getXHTMLString(@$this->data['teaser'], ((bool)@$this->data['nl2br']))
    );
    $result .= sprintf(
      '<text>%s</text>' . LF,
      $this->getXHTMLString(@$this->data['text'], !((bool)@$this->data['nl2br']))
    );

    $result .= $this->getSearchForm();
    $result .= $this->getGlossaryEntries($this->parentObj->getContentLanguageId());
    $result .= $this->getPageNavigation();

    return $result;
  }

  /**
   * Generate search form XML (pattern search)
   *
   * @return string $result parsed data as XML
   */
  function getSearchForm() {
    $result = '';
    $result .= sprintf(
      '<search searchfor="%s" char="%s" entry_id="%s">' . LF,
      PapayaUtilStringXml::escapeAttribute(@$this->params['searchfor']),
      PapayaUtilStringXml::escapeAttribute(@$this->params['char']),
      PapayaUtilStringXml::escapeAttribute(@$this->params['entry_id'])
    );
    $result .= $this->getCharList();
    $result .= sprintf(
      '<dialog title="%s" action="%s" method="get">'.LF,
      'Search',
      PapayaUtilStringXml::escapeAttribute(
        $this->getWebLink(
          NULL,
          NULL,
          NULL,
          NULL,
          $this->paramName,
          $this->parentObj->topic['TRANSLATION']['topic_title'],
          0
        )
      )
    );
    $result .= sprintf(
      '<input name="%s[%s]" value="%s" type="text"></input>' . LF,
      PapayaUtilStringXml::escapeAttribute($this->paramName),
      'searchfor',
      PapayaUtilStringXml::escapeAttribute(@$this->params['searchfor'])
    );
    $result .= sprintf(
      '<input name="%s[reset]" value="1" type="hidden"></input>' . LF,
      PapayaUtilStringXml::escapeAttribute($this->paramName)
    );
    $result .= sprintf(
      '<button value="%s" />'.LF,
      PapayaUtilStringXml::escapeAttribute(@$this->data['search'])
    );
    $result .= '</dialog>'.LF;
    $result .= '</search>'.LF;
    return $result;
  }


  function checkURLFileName($currentFileName, $outputMode) {
    if (isset($this->params['entry_id'])) {
      $lngId = $this->parentObj->getContentLanguageId();
      $this->loadEntriesById($this->params['entry_id'], $lngId);

      if (isset($this->glossaryEntries[$this->params['entry_id']])) {
        $entryName = $this->escapeForFilename(
          $this->glossaryEntries[$this->params['entry_id']]['glossaryentry_term'],
          'index',
          $this->parentObj->currentLanguage['lng_ident']
        );
        if ($entryName === $currentFileName) {
          return FALSE;
        } else {
          $queryString = (isset($_SERVER['QUERY_STRING'])) ? $_SERVER['QUERY_STRING'] : '';
          return $this->getAbsoluteURL(
            $this->getWebLink(
              $this->parentObj->topicId,
              $lngId,
              $outputMode,
              NULL,
              $this->paramName,
              $entryName,
              0
            )
          ).$this->recodeQueryString($queryString);
        }
      }
    }
    $pageFileName = $this->escapeForFilename(
      $this->parentObj->topic['TRANSLATION']['topic_title'],
      'index',
      $this->parentObj->currentLanguage['lng_ident']
    );
    if ($currentFileName != $pageFileName) {
      $url = $this->getWebLink(
        $this->parentObj->topicId, NULL, $outputMode, NULL, NULL, $pageFileName
      );
      $queryString = (isset($_SERVER['QUERY_STRING'])) ? $_SERVER['QUERY_STRING'] : '';
      return $this->getAbsoluteURL($url).$this->recodeQueryString($queryString);
    }
  }

  protected function getIdByTerm($term, $lngId) {
    $term = iconv("UTF-8", "ASCII//TRANSLIT", strtolower(str_replace("-", "", $term)));

    $sql = "SELECT ge.glossary_id, get.glossaryentry_id,
                   get.glossaryentry_normalized, get.lng_id
              FROM %s AS ge, %s as get
             WHERE get.glossaryentry_normalized = '%s'
               AND ge.glossaryentry_id = get.glossaryentry_id
               AND get.lng_id = %d

             ORDER BY get.glossaryentry_normalized";
    $params = array(
      $this->glossaryObj->tableGlossaryEntries,
      $this->glossaryObj->tableGlossaryEntriesTrans,
      $term,
      $lngId
    );

    if ($res = $this->glossaryObj->databaseQueryFmt(
      $sql, $params, $this->data['steps'], $this->params['offset'])) {
      return $res->fetchRow(DB_FETCHMODE_ASSOC);
    }
  }

  /**
   * Generate char list for navigation
   *
   * @return string $result parsed data as XML
   */
  function getCharList() {
    $result = '';
    $result .= '<chars>'.LF;
    $existingChars =
      $this->getExistingFirstChars($this->parentObj->getContentLanguageId());
    $numbers = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9);
    $existingNums = array_intersect($numbers, array_keys($existingChars));
    if (count($existingNums) > 0) {
      $numSum = 0;
      foreach ($existingNums as $i => $v) {
        $numSum += $existingChars[$i]['count'];
      }
      $numLink = sprintf(
        ' href="%s"',
        PapayaUtilStringXml::escapeAttribute(
          $this->getWebLink(
            $this->parentObj->topicId,
            NULL,
            NULL,
            array('char' => '#'),
            $this->paramName,
            $this->parentObj->topic['TRANSLATION']['topic_title'],
            0
          )
        )
      );
      $numCount = sprintf(' count="%s"', PapayaUtilStringXml::escapeAttribute($numSum));
    }
    $result .= sprintf('<char%s%s>#</char>', @$numLink, @$numCount);
    for ($i = ord('a'); $i <= ord('z'); $i++) {
      $c = chr($i);
      if (isset($existingChars[$c])) {
        $link = sprintf(
          ' href="%s"',
          PapayaUtilStringXml::escapeAttribute(
            $this->getWebLink(
              $this->parentObj->topicId,
              NULL,
              NULL,
              array('char' => $c),
              $this->paramName,
              $this->parentObj->topic['TRANSLATION']['topic_title'],
              0
            )
          )
        );
        $count = sprintf(
          ' count="%s"', PapayaUtilStringXml::escapeAttribute($existingChars[$c]['count'])
        );
      } else {
        $count = '';
        $link = '';
      }
      $result .= sprintf(
        '<char%s%s>%s</char>' . LF,
        $link,
        $count,
        PapayaUtilStringXml::escape(strtoupper($c))
      );
    }
    $result .= '</chars>'.LF;
    return $result;
  }

  /**
   * Counts number of entries starting with each letter
   *
   * @param integer $lngId language id
   * @return array $chars array with each letter and number of occurance
   */
  function getExistingFirstChars($lngId) {
    $chars = array();
    if (isset($this->glossaryIds) && count($this->glossaryIds) > 0) {
      $glossaryFilter = $this->glossaryObj->databaseGetSQLCondition(
        'ge.glossary_id',
        $this->glossaryIds
      );
      $sql = "SELECT COUNT(*) AS count,
                     SUBSTR(glossaryentry_normalized, 1, 1) AS firstchar
                FROM %s AS ge, %s AS get
               WHERE $glossaryFilter
                 AND ge.glossaryentry_id = get.glossaryentry_id
                 AND get.lng_id = %d
               GROUP BY firstchar ASC";
      $condition = array($this->glossaryObj->tableGlossaryEntries,
        $this->glossaryObj->tableGlossaryEntriesTrans, $lngId);
      if ($res = $this->glossaryObj->databaseQueryFmt($sql, $condition)) {
        while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
          $chars[strtolower($row['firstchar'])] = $row;
        }
      }
    }
    return $chars;
  }

  /**
   * Calls appropriate search function
   *
   * @param boolean $recursion optional, default value FALSE
   */
  function loadEntries($recursion = FALSE) {
    $this->entriesAbsCount = 0;
    $this->params['offset'] = (isset($this->params['offset'])) ?
      (int)$this->params['offset'] : 0;

    if (isset($this->params['entry_id']) && $this->params['entry_id'] > 0) {
      $lngId = (isset($this->params['show_lng'])) ? $this->params['show_lng'] :
        $lngId = $this->parentObj->getContentLanguageId();
      $this->entriesAbsCount = $this->loadEntriesById(
        @(int)$this->params['entry_id'], $lngId
      );
    } elseif (isset($this->params['char'])) {
      $char = papaya_strings::substr($this->params['char'], 0, 1);
      $this->entriesAbsCount = $this->loadEntriesByChar(
        $char, $this->parentObj->getContentLanguageId()
      );
    } elseif (isset($this->params['searchfor']) &&
      strlen($this->params['searchfor']) > 2) {
      $this->entriesAbsCount = $this->loadEntriesBySearch(
        $this->params['searchfor'], $this->parentObj->getContentLanguageId()
      );
    } elseif (isset($this->params['mode'])) {
      $this->entriesAbsCount = $this->loadEntriesList(
        $this->params['mode'],
        $this->parentObj->getContentLanguageId()
      );
    }
    if ($this->params['offset'] > $this->entriesAbsCount && (!$recursion)) {
      $this->params['offset'] = floor((int)$this->entriesAbsCount / (int)$this->data['steps']) *
        $this->data['steps'];
      $this->loadEntries(TRUE);
    }
  }

  /**
   * Performs search by character (letter) a term starts with
   *
   * @param string $char letter
   * @param integer $lngId language Id
   * @return integer absCount() value
   */
  function loadEntriesByChar($char, $lngId) {
    $firstCharQuery = ' AND '.$this->glossaryObj->databaseGetSQLCondition(
        'SUBSTR(glossaryentry_normalized, 1, 1)', $char);
    $glossaryFilter = $this->glossaryObj->databaseGetSQLCondition(
      'ge.glossary_id',
      $this->glossaryIds
    );
    if ($this->loadDetailedLists) {
      $detailFields = ', get.glossaryentry_explanation';
    } else {
      $detailFields = '';
    }
    $sql = "SELECT ge.glossary_id, get.glossaryentry_id,
                   get.glossaryentry_term, get.glossaryentry_derivation,
                   get.glossaryentry_synonyms, get.glossaryentry_abbreviations,
                   get.glossaryentry_normalized, get.lng_id
                   $detailFields
              FROM %s AS ge, %s as get
             WHERE ge.glossaryentry_id = get.glossaryentry_id
               AND get.lng_id = %d
               AND $glossaryFilter $firstCharQuery
             ORDER BY get.glossaryentry_normalized";
    $condition = array(
      $this->glossaryObj->tableGlossaryEntries,
      $this->glossaryObj->tableGlossaryEntriesTrans,
      $lngId
    );
    if ($res = $this->glossaryObj->databaseQueryFmt(
      $sql, $condition, $this->data['steps'], $this->params['offset'])) {
      while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $this->glossaryEntries[$row['glossaryentry_id']] = $row;
      }
      return $res->absCount();
    }
  }

  /**
   * Performs search by pattern (search string)
   *
   * @param string $searchFor search string
   * @param integer $lngId language Id
   */
  function loadEntriesBySearch($searchFor, $lngId) {
    $parser = new searchstringparser;
    $searchFields = array('get.glossaryentry_term',
      'get.glossaryentry_explanation', 'get.glossaryentry_derivation',
      'get.glossaryentry_synonyms', 'get.glossaryentry_abbreviations');
    $glossaryFilter = $this->glossaryObj->databaseGetSQLCondition(
      'ge.glossary_id', $this->glossaryIds);
    $filter = $parser->getSQL($searchFor, $searchFields, $this->fullTextSearch);
    if ($filter !== FALSE) {
      $filter = ' AND '.str_replace("%", "%%", $filter);
    }
    if ($this->loadDetailedLists) {
      $detailFields = ', get.glossaryentry_explanation';
    } else {
      $detailFields = '';
    }
    $sql = "SELECT ge.glossary_id, get.glossaryentry_id,
                   get.glossaryentry_term, get.glossaryentry_derivation,
                   get.glossaryentry_synonyms, get.glossaryentry_abbreviations,
                   get.glossaryentry_normalized, get.lng_id
                   $detailFields
              FROM %s AS ge, %s as get
             WHERE $glossaryFilter
               AND ge.glossaryentry_id = get.glossaryentry_id
               AND get.lng_id = %d
               $filter
             ORDER BY get.glossaryentry_normalized";
    $params = array(
      $this->glossaryObj->tableGlossaryEntries,
      $this->glossaryObj->tableGlossaryEntriesTrans,
      $lngId
    );

    if ($res = $this->glossaryObj->databaseQueryFmt(
      $sql, $params, $this->data['steps'], $this->params['offset'])) {
      while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $this->glossaryEntries[$row['glossaryentry_id']] = $row;
      }
      return $res->absCount();
    }
  }

  /**
   * Fetchs glossaryentries by Id
   *
   * @param integer $id
   * @param integer $lngId language Id
   * @return integer 0 or 1
   */
  function loadEntriesById($id, $lngId) {
    $sql = "SELECT ge.glossary_id, get.glossaryentry_id,
                   get.glossaryentry_term, get.glossaryentry_normalized,
                   get.glossaryentry_explanation,
                   get.glossaryentry_firsttoken, get.glossaryentry_keywords,
                   get.glossaryentry_derivation, get.glossaryentry_source,
                   get.glossaryentry_synonyms, get.glossaryentry_abbreviations,
                   get.glossaryentry_links, get.lng_id
            FROM %s AS ge, %s as get
            WHERE ge.glossaryentry_id = get.glossaryentry_id
              AND get.lng_id = %d
              AND get.glossaryentry_id = %d
            ORDER BY get.glossaryentry_normalized";
    $condition = array(
      $this->glossaryObj->tableGlossaryEntries,
      $this->glossaryObj->tableGlossaryEntriesTrans,
      $lngId, $id
    );
    if ($res = $this->glossaryObj->databaseQueryFmt(
      $sql, $condition, $this->data['steps'], $this->params['offset'])) {
      if ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {

        $this->data['glossary_words'] = explode(
          "\n",
          $row['glossaryentry_keywords']
        );

        foreach ($this->data['glossary_words'] as $key => $word) {
          if ($word == $row['glossaryentry_firsttoken']) {
            unset($this->data['glossary_words'][$key]);
          }
        }

        $this->loadFilterData();
        unset($this->data['glossary_words']);

        $row['glossaryentry_explanation'] =
          $this->applyFilterData($row['glossaryentry_explanation']);

        $this->glossaryEntries[$row['glossaryentry_id']] = $row;
        $sql = "SELECT ge.glossary_id, get.glossaryentry_id,
                       get.glossaryentry_term, get.glossaryentry_derivation,
                       get.glossaryentry_synonyms, get.glossaryentry_abbreviations,
                       get.glossaryentry_normalized, get.lng_id
                  FROM %s AS ge, %s as get
                 WHERE ge.glossaryentry_id = get.glossaryentry_id
                   AND get.glossaryentry_id = %d
                 ORDER BY get.glossaryentry_normalized";
        $condition = array(
          $this->glossaryObj->tableGlossaryEntries,
          $this->glossaryObj->tableGlossaryEntriesTrans, $id
        );
        $res = $this->glossaryObj->databaseQueryFmt(
          $sql, $condition, $this->data['steps'], $this->params['offset']
        );
        if ($res) {
          while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
            $this->glossaryEntries[$row['glossaryentry_id']]['lng_ids'][$row['lng_id']] =
              $row['glossaryentry_term'];
          }
        }
        return 1;
      }
    }
    return 0;
  }

  /**
   * Fetchs glossaryentries alphabetically
   *
   * @param string $mode mode can be 'abc' for paging or 'flat' for all on one page
   * @param integer $lngId language Id
   * @return integer absolute count of entries
   */
  function loadEntriesList($mode, $lngId) {
    $this->glossaryEntries = array();
    if (!@$this->data['steps'] > 0) {
      $this->data['steps'] = 30;
    }
    $glossaryFilter = $this->glossaryObj->databaseGetSQLCondition(
      'ge.glossary_id', $this->glossaryIds);
    if ($this->loadDetailedLists) {
      $detailFields = ', get.glossaryentry_explanation';
    } else {
      $detailFields = '';
    }
    $sql = "SELECT ge.glossary_id, get.glossaryentry_id,
                   get.glossaryentry_term, get.glossaryentry_derivation,
                   get.glossaryentry_synonyms, get.glossaryentry_abbreviations,
                   get.glossaryentry_normalized, get.lng_id
                   $detailFields
              FROM %s AS ge, %s as get
             WHERE $glossaryFilter
               AND ge.glossaryentry_id = get.glossaryentry_id
               AND get.lng_id = %d
             ORDER BY get.glossaryentry_normalized";
    $condition = array(
      $this->glossaryObj->tableGlossaryEntries,
      $this->glossaryObj->tableGlossaryEntriesTrans,
      $lngId
    );
    if ($mode == 'abc') {
      $res = $this->glossaryObj->databaseQueryFmt(
        $sql,
        $condition,
        (int)$this->data['steps'],
        @(int)$this->params['offset']
      );
    } elseif ($mode == 'flat') {
      $res = $this->glossaryObj->databaseQueryFmt($sql, $condition);
    }
    if ($res) {
      while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        if (isset($row['glossaryentry_id']) && $row['glossaryentry_id'] > 0) {
          $this->glossaryEntries[$row['glossaryentry_id']] = $row;
        }
      }
      return $res->absCount();
    }
  }

  /**
   * Generates XML for glossary entries
   * (filtered by search or a-z) following state of page navigation
   *
   * @param integer $selectedLngId selected language Id
   * @return string $result XML for glossaryentries
   */
  function getGlossaryEntries($selectedLngId) {
    $result = '';
    if (isset($this->glossaryEntries) && is_array($this->glossaryEntries) &&
      count($this->glossaryEntries) > 0) {
      $showContent = 'no';
      if (isset($this->params['entry_id']) && $this->params['entry_id'] > 0) {
        $showContent = 'yes';
      }
      $flatMode = (@$this->params['mode'] == 'flat') ? 'yes' : 'no';
      $result .= sprintf(
        '<glossary total="%s" showcontent="%s" flatmode="%s">' . LF,
        $this->entriesAbsCount,
        $showContent,
        $flatMode
      );
      $i = 0;
      $oldChar = NULL;
      foreach ($this->glossaryEntries as $id => $entry) {
        if (isset($showContent) && $showContent == 'yes') {
          $displayedEntries[] = $id;
        }
        $i++;
        $glossaryName = $this->glossaries[$entry['glossary_id']]['glossary_title'];
        $content = '';
        if (
          isset($entry['glossaryentry_explanation']) &&
          $entry['glossaryentry_explanation'] != ''
        ) {
          $content .= sprintf(
            '<explanation>%s</explanation>' . LF,
            $this->getXHTMLString(
              $entry['glossaryentry_explanation'],
              !((bool)@$this->data['nl2br'])
            )
          );
        }
        if (
          isset($entry['glossaryentry_links']) &&
          $entry['glossaryentry_links'] != ''
        ) {
          $content .= sprintf(
            '<links>%s</links>' . LF,
            $this->getLinksXML($entry['glossaryentry_links'])
          );
        }
        if (
          isset($entry['glossaryentry_source']) &&
          $entry['glossaryentry_source'] != ''
        ) {
          $content .= sprintf(
            '<source>%s</source>' . LF,
            $this->getXHTMLString($entry['glossaryentry_source'])
          );
        }
        if (
          isset($entry['lng_ids']) &&
          is_array($entry['lng_ids']) &&
          count($entry['lng_ids']) > 0
        ) {
          $content .= '<translations>'.LF;
          $defaultLink = $this->params;
          $defaultLink['entry_id'] = $id;

          if ($this->params['offset'] > 0) {
            $linkParams['search_offset'] = $this->params['offset'];
            $defaultLink['search_offset'] = $this->params['offset'];
          }

          unset($defaultLink['offset']);
          foreach ($entry['lng_ids'] as $lngId => $title) {
            if (isset($this->params['show_lng'])) {
              $selected = ($this->params['show_lng'] == $lngId) ?
                'selected="selected"': '';
            } else {
              $selected = ($selectedLngId == $lngId) ? 'selected="selected"': '';
            }
            $content .= sprintf(
              '<translation href="%s" lng_short="%s" lng_title="%s" %s>%s</translation>' . LF,
              PapayaUtilStringXml::escapeAttribute(
                $this->getWebLink(
                  NULL,
                  $this->papaya()->languages[$lngId]['identifier'],
                  NULL,
                  $defaultLink,
                  $this->paramName,
                  $title ?: $this->parentObj->topic['TRANSLATION']['topic_title']
                )
              ),
              PapayaUtilStringXml::escapeAttribute(
                $this->papaya()->languages[$lngId]['code']
              ),
              PapayaUtilStringXml::escapeAttribute(
                $this->papaya()->languages[$lngId]['title']
              ),
              $selected,
              PapayaUtilStringXml::escapeAttribute(
                $title
              )
            );
          }
          $content .= '</translations>'.LF;
        }
        if (isset($this->params['offset'])) {
          $number = $this->params['offset'] + $i;
        } else {
          $number = $i;
        }
        $linkParams = $this->params;
        $linkParams['entry_id'] = $id;

        if (isset($linkParams['mode']) && $linkParams['mode'] == 'abc') {
          unset($linkParams['mode']);
        }

        unset($linkParams['offset']);
        $href = $this->getWebLink(
          NULL,
          NULL,
          NULL,
          $linkParams,
          $this->paramName,
          $this->parentObj->topic['TRANSLATION']['topic_title'],
          0
        );

        $termHref = $this->getWebLink(
          NULL,
          NULL,
          NULL,
          $linkParams,
          $this->paramName,
          $entry['glossaryentry_term'],
          0
        );

        $firstChar = papaya_strings::strtoupper(
          papaya_strings::substr($entry['glossaryentry_normalized'], 0, 1)
        );
        if (isset($this->params['mode']) && $firstChar != $oldChar) {
          if (!empty($oldChar)) {
            $result .= '</charsection>';
          }
          $charSection = sprintf(' charsection="%s"', $firstChar);
          $result .= sprintf(
            '<charsection title="%s">',
            papaya_strings::escapeHTMLChars($firstChar)
          );
          $oldChar = $firstChar;
        } else {
          $charSection = '';
        }
        $result .= sprintf(
          '<glossaryentry term="%s" derivation="%s" synonyms="%s"
            abbreviations="%s" glossary="%s" number="%s" href="%s" termhref="%s">%s</glossaryentry>',
          papaya_strings::escapeHTMLChars($entry['glossaryentry_term']),
          papaya_strings::escapeHTMLChars($entry['glossaryentry_derivation']),
          papaya_strings::escapeHTMLChars($entry['glossaryentry_synonyms']),
          papaya_strings::escapeHTMLChars($entry['glossaryentry_abbreviations']),
          papaya_strings::escapeHTMLChars($glossaryName),
          (int)$number,
          papaya_strings::escapeHTMLChars($href),
          papaya_strings::escapeHTMLChars($termHref),
          $content
        );
      }
      if (!empty($oldChar)) {
        $result .= '</charsection>';
      }
      if (
        isset($displayedEntries) &&
        is_array($displayedEntries) &&
        count($displayedEntries) > 0
      ) {
        $lngId = (isset($this->params['show_lng']))
          ? $this->params['show_lng'] : $this->parentObj->getContentLanguageId();
      }
      $result .= '</glossary>';
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
   * @return string $result XML string formed <link href="http://www.link.org" title="linktext" />
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
            '<link href="%s" title="%s" />' . LF,
            PapayaUtilStringXml::escapeAttribute($href),
            PapayaUtilStringXml::escapeAttribute($caption)
          );
        }
      }
    }
    return $result;
  }

  /**
   * Generates page navigation
   *
   * @return string $result XML string for navigation
   */
  function getPageNavigation() {
    if ($this->data['steps'] < 1) {
      $this->data['steps'] = 30;
    }
    $result = '';
    $backLink = '';
    if (isset($this->params)) {
      if (isset($this->params['searchfor'])) {
        $linkParams = array('searchfor' =>
          papaya_strings::escapeHTMLChars($this->params['searchfor']));
      } elseif (isset($this->params['char'])) {
        $linkParams = array('char' => $this->params['char']);
      } elseif (isset($this->params['mode'])) {
        $linkParams = array('mode' => $this->params['mode']);
      }

      if (isset($this->params['entry_id'])) {
        $linkParams = array('entry_id' => $this->params['entry_id']);
        if (isset($this->params['refpage'])) {
          $backLink = $this->getBaseLink($this->params['refpage']).
            $this->recodeQueryString(@$this->params['urlparams']);
        } else {
          $backParams = $this->params;
          if (isset($this->params['search_offset'])) {
            $backParams['offset'] = $this->params['search_offset'];
            unset($backParams['search_offset']);
          }

          if ($this->params['offset'] == 0) {
            unset($backParams['offset']);
          }

          unset($backParams['entry_id']);
          $backLink = $this->getWebLink(
            $this->parentObj->topicId,
            NULL,
            NULL,
            $backParams,
            $this->paramName,
            $this->parentObj->topic['TRANSLATION']['topic_title'],
            0
          );
        }
      }
      $allLink = $this->getWebLink(
        $this->parentObj->topicId,
        NULL,
        NULL,
        array('mode' => 'flat'),
        $this->paramName,
        $this->parentObj->topic['TRANSLATION']['topic_title'],
        0
      );
      $abcLink = $this->getWebLink(
        $this->parentObj->topicId,
        NULL,
        NULL,
        array('mode' => 'abc'),
        $this->paramName,
        $this->parentObj->topic['TRANSLATION']['topic_title'],
        0
      );

      $result .= sprintf(
        '<navi back_href="%s" all_href="%s" abc_href="%s">',
        PapayaUtilStringXml::escapeAttribute($backLink),
        PapayaUtilStringXml::escapeAttribute($allLink),
        PapayaUtilStringXml::escapeAttribute($abcLink)
      );
      if (@$this->params['mode'] != 'flat') {
        if ($this->params['offset'] - $this->data['steps'] >= 0) {
          $linkParams['offset'] = $this->params['offset'] - $this->data['steps'];
          $result .= sprintf(
            '<prev href="%s" />',
            PapayaUtilStringXml::escapeAttribute(
              $this->getWebLink(
                $this->parentObj->topicId,
                NULL,
                NULL,
                $linkParams,
                $this->paramName,
                $this->parentObj->topic['TRANSLATION']['topic_title'],
                0
              )
            )
          );
        }
        if (
          ($this->params['offset'] + $this->data['steps']) < $this->entriesAbsCount
        ) {
          $linkParams['offset'] = $this->params['offset'] + $this->data['steps'];
          $result .= sprintf(
            '<next href="%s" />',
            PapayaUtilStringXml::escapeAttribute(
              $this->getWebLink(
                $this->parentObj->topicId,
                NULL,
                NULL,
                $linkParams,
                $this->paramName,
                $this->parentObj->topic['TRANSLATION']['topic_title'],
                0
              )
            )
          );
        }
        if (isset($linkParams['offset'])) {
          $linkParams['offset'] = 0;
          $result .= sprintf(
            '<first href="%s" />',
            PapayaUtilStringXml::escapeAttribute(
              $this->getWebLink(
                $this->parentObj->topicId,
                NULL,
                NULL,
                $linkParams,
                $this->paramName,
                $this->parentObj->topic['TRANSLATION']['topic_title'],
                0
              )
            )
          );
          $linkParams['offset'] = floor((int)$this->entriesAbsCount / (int)$this->data['steps']) *
            (int)$this->data['steps'];
          $result .= sprintf(
            '<last href="%s" />',
            PapayaUtilStringXml::escapeAttribute(
              $this->getWebLink(
                $this->parentObj->topicId,
                NULL,
                NULL,
                $linkParams,
                $this->paramName,
                $this->parentObj->topic['TRANSLATION']['topic_title'],
                0
              )
            )
          );
          $current = floor(
              (int)$this->params['offset'] / ($this->data['steps'])
            ) + 1;
          $linkParams['offset'] = '';
          $result .= sprintf(
            '<page current="%d" total="%d" step="%d" href="%s" />',
            $current,
            ceil((int)$this->entriesAbsCount / (int)$this->data['steps']),
            (int)$this->data['steps'],
            PapayaUtilStringXml::escapeAttribute(
              $this->getWebLink(
                $this->parentObj->topicId,
                NULL,
                NULL,
                $linkParams,
                $this->paramName,
                $this->parentObj->topic['TRANSLATION']['topic_title'],
                0
              )
            )
          );
        }
      }
      $to = $this->params['offset'] + $this->data['steps'];
      if ($to > $this->entriesAbsCount) {
        $to = $this->entriesAbsCount;
      }
      $result .= sprintf(
        '<results from="%d" to="%d" total="%d" />',
        $this->params['offset'] + 1,
        $to,
        $this->entriesAbsCount
      );
      $result .= '</navi>';
    }
    return $result;
  }

  /**
   * Callback function to generate combo for existing glossaries
   *
   * @return string $result xml
   */
  function callbackGlossaryCombo() {
    $result = '';
    if (
      isset($this->data['all']) &&
      ((int)$this->data['all'] == 0) &&
      isset($this->glossaries) &&
      is_array($this->glossaries) &&
      (count($this->glossaries) > 0)
    ) {
      $result .= sprintf(
        '<select name="%s[glossary]" class="dialogSelect dialogScale">',
        PapayaUtilStringXml::escapeAttribute($this->paramName)
      );
      foreach ($this->glossaries as $k => $v) {
        $result .= '';
        $selected = ($k == $this->data['glossary']) ?
          ' selected="selected"' : '';
        $result .= sprintf(
          '<option value="%s"%s>%s </option>',
          PapayaUtilStringXml::escapeAttribute($k),
          $selected,
          PapayaUtilStringXml::escapeAttribute($v['glossary_title'])
        );
      }
      $result .= '</select>';
    }
    return $result;

  }

  /**
   * Get parsed teaser title and text
   *
   * @return string $result xml
   */
  function getParsedTeaser() {
    $result = '';
    $result .= sprintf(
      '<title>%s</title>' . LF,
      $this->getXHTMLString(@$this->data['title'])
    );
    $result .= sprintf(
      '<text>%s</text>' . LF,
      $this->getXHTMLString(@$this->data['teaser'], TRUE)
    );
    return $result;
  }

  /**
   * Logs glossary search words or referer
   */
  function logStatistic() {
    if (isset($this->params['entry_id']) && $this->params['entry_id'] > 0) {
      $data = array(
        'glossaryentry_id' => $this->params['entry_id'],
        'lng_id'           => $this->lngSelect->currentLanguageId,
      );
      if (isset($this->params['refpage'])) {
        $data['referer'] = (int)$this->params['refpage'];
      }

      include_once(PAPAYA_INCLUDE_PATH.'system/base_statistic_entries_tracking.php');
      return base_statistic_entries_tracking::logEntry($this->guid, 'glossary_visit', $data);
    }
  }

  /**
   * Get topic title of referer
   *
   * @param integer $topicId id of referer topic
   * @param integer $lngId selected language id
   * @return mixed array with topic title or boolean false
   */
  function getRefererTopic($topicId, $lngId) {
    $sql = "SELECT topic_title
              FROM %s
             WHERE topic_id = %d AND lng_id = %s";
    $params = array($this->tableTopicTrans, $topicId, $lngId);
    if ($res = $this->glossaryObj->databaseQueryFmt($sql, $params)) {
      return $res->fetchField();
    }
    return FALSE;
  }
}