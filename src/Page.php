<?php
/**
 * Glossary page
 *
 * @copyright 2013 by papaya Software GmbH - All rights reserved.
 * @link http://www.papaya-cms.com/
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License, version 2
 *
 * You can redistribute and/or modify this script under the terms of the GNU General Public
 * License (GPL) version 2, provided that the copyright and license notes, including these
 * lines, remain unmodified. papaya is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.
 *
 * @package Papaya-Library
 * @subpackage Modules-Standard
 * @version $Id: Article.php 39795 2014-05-06 15:35:52Z weinert $
 */

/*
 * Glossary page
*
* @package Papaya-Library
* @subpackage Modules-Standard
*/
class GlossaryPage
  extends
  PapayaObjectInteractive
  implements
  PapayaPluginConfigurable,
  PapayaPluginAppendable,
  PapayaPluginQuoteable,
  PapayaPluginEditable,
  PapayaPluginCacheable,
  PapayaPluginAddressable {

  const DOMAIN_CONNECTOR_GUID = '8ec0c5995d97c9c3cc9c237ad0dc6c0b';

  /**
   * @var PapayaPluginEditableContent
   */
  private $_content = NULL;

  /**
   * @var PapayaObjectParameters
   */
  private $_configuration = NULL;

  /**
   * @var PapayaCacheIdentifierDefinition
   */
  private $_cacheDefinition = NULL;

  /**
   * @var object
   */
  private $_owner = NULL;

  /**
   * @var PapayaPluginFilterContent
   */
  private $_contentFilters = NULL;

  /**
   * @var GlossaryContentGlossaries
   */
  private $_glossaries;

  /**
   * @var GlossaryContentTermWordCharacters
   */
  private $_characters;

  /**
   * @var GlossaryContentTermWords
   */
  private $_words;

  /**
   * @var GlossaryContentTermTranslation
   */
  private $_term;

  /**
   * @var int $_defaultPageLimit default limit for terms per page
   */
  private $_defaultPageLimit = 20;

  private $_linkTypes = [
    GlossaryContentTermWords::TYPE_TERM => 'term',
    GlossaryContentTermWords::TYPE_SYNONYM => 'synonym',
    GlossaryContentTermWords::TYPE_DERIVATION => 'derivation',
    GlossaryContentTermWords::TYPE_ABBREVIATION => 'abbreviation',
  ];
  private $_linkTypeGroups = [
    GlossaryContentTermWords::TYPE_TERM => 'terms',
    GlossaryContentTermWords::TYPE_SYNONYM => 'synonyms',
    GlossaryContentTermWords::TYPE_DERIVATION => 'derivations',
    GlossaryContentTermWords::TYPE_ABBREVIATION => 'abbreviations',
  ];

  public function __construct($owner) {
    $this->_owner = $owner;
  }

  /**
   * Append the page output xml to the DOM.
   *
   * @see PapayaXmlAppendable::appendTo()
   * @param PapayaXmlElement $parent
   */
  public function appendTo(PapayaXmlElement $parent) {
    $pageReference =$this->papaya()->pageReferences->get(
      $this->papaya()->request->pageId,
      $this->papaya()->request->languageIdentifier
    );
    $filters = $this->filters();
    $filters->prepare(
      $this->content()->get('text', ''),
      $this->configuration()
    );
    $parent->appendElement('title', [], $this->content()->get('title', ''));
    $parent->appendElement('teaser')->appendXml($this->content()->get('teaser', ''));
    $parent->appendElement('text')->appendXml(
      $filters->applyTo($this->content()->get('text', ''))
    );

    $pageUrl = new PapayaUiReferencePage();
    $pageUrl->load($this->papaya()->request);
    $glossaryId = $this->getGlossaryId();
    $termId = $this->parameters()->get('term', 0);
    $character = $this->getCharacter();
    $linkTextModes = array_flip($this->content()->get('glossary_word_url_text', []));
    $outputMode = $this->parameters()->get('mode', NULL, new PapayaFilterList(['flat']));

    if ($this->parameters()->get('mode', '') != 'flat') {
      $paging = new PapayaUiPagingCount(
        'page',
        $this->parameters()->get('page'),
        $this->words()->absCount()
      );
      if (!empty($character)) {
        $paging->reference->setParameters(
          ['char' => $character]
        );
      }
      $paging->itemsPerPage = $this->content()->get('steps', $this->_defaultPageLimit);
    } else {
      $paging = FALSE;
    }

    $parameters = [];
    if (!empty($character)) {
      $parameters['char'] = $character;
    }
    if ($paging && 1 < ($pageOffset = $paging->getCurrentPage())) {
      $parameters['page'] = $pageOffset;
    };

    $reference = clone $pageReference;
    $reference->setParameters($parameters);
    $glossaryNode = $parent->appendElement(
      'glossary',
      [
        'character' => $character,
        'href' => $reference
      ]
    );

    $filter = [
      'language_id' => $this->papaya()->request->languageId,
      'id' => $termId,
      'term_id' => $termId,
      'glossary_id' => $glossaryId
    ];
    $term = $this->term();
    if ($term->load($filter)) {
      $reference = clone $pageReference;
      $reference->setPageTitle($term['term']);
      $reference->setParameters(
        [
          'term' => $term['id']
        ]
      );
      $termNode = $glossaryNode->appendElement(
        'term',
        [
          'id' => $term['id'],
          'href' => $reference
        ]
      );
      $termNode->appendElement('title', [], $term['term']);
      $termNode->appendElement('explanation')->appendXml($term['explanation']);
      $termNode->appendElement('source', [], $term['source']);
      $linksNode = $termNode->appendElement('links');
      foreach (explode("\n", $term['links']) as $link) {
        if (FALSE !== strpos($link, '=')) {
          list($caption, $url) = explode('=', $link, 2);
        } else {
          $caption = $url = $link;
        }
        $linksNode->appendElement('link', ['href' => trim($url)], trim($caption));
      }

      $term->words()->load($filter);
      $groupNodes = [];
      foreach ($term->words() as $word) {
        $type = $word['type'];
        $groupTag = isset($this->_linkTypeGroups[$type]) ? $this->_linkTypeGroups[$type] : 'others';
        if (isset($groupNodes[$groupTag])) {
          $groupNode = $groupNodes[$groupTag];
        } else {
          $groupNodes[$groupTag] = $groupNode = $termNode->appendElement($groupTag);
        }
        $parameters['term'] = $word['term_id'];
        $reference = clone $pageReference;
        $pageTitle = isset($linkTextModes[$word['type']]) ? $word['word'] : $word['term_title'];
        $reference->setPageTitle($pageTitle);
        $reference->setParameters($parameters);
        $isSelected = (
          $pageUrl->getPageTitle() ==
          PapayaUtilFile::normalizeName($word['word'], 100, $this->papaya()->request->languageIdentifier)
        );
        $groupNode->appendElement(
          isset($this->_linkTypes[$type]) ? $this->_linkTypes[$type] : 'other',
          [
            'selected' => $isSelected ? 'true' : null,
            'href' => $reference
          ],
          trim($word['word'])
        );
      };
      $translationsNode = $termNode->appendElement('translations');
      foreach ($term->translations() as $translation) {
        $reference = clone $pageReference;
        $reference->setPageLanguage(
          $this->papaya()->languages[$translation['language_id']]['identifier']
        );
        $reference->setParameters(
          ['term' => $termId]
        );
        $reference->setPageTitle($translation['term']);
        $language = $this->papaya()->languages->getLanguage($translation['language_id']);
        $translationsNode->appendElement(
          'translation',
          [
            'language' => $language['code'],
            'language-title' => $language['title'],
            'href' => $reference,
            'selected' => $translation['language_id'] == $this->papaya()->request->languageId ? 'true' : NULL
          ]
        )->appendText($translation['term']);
      }
    } else {
      if ($character != '') {
        $reference = clone $pageReference;
        $reference->setParameters(
          [
            'mode' => $outputMode
          ]
        );
        $glossaryNode->appendElement(
          'link',
          [
            'rel' => 'up',
            'href' => $reference
          ]
        );
      }
      $modes = ['all' => 'flat', 'paged' => NULL];
      foreach ($modes as $relation => $mode) {
        if ($outputMode != $mode) {
          $reference = clone $pageReference;
          $reference->setParameters(
            [
              'mode' => $mode,
              'char' => $character != '' ? $character : NULL
            ]
          );
          $glossaryNode->appendElement(
            'link',
            [
              'rel' => $relation,
              'href' => $reference
            ]
          );
        }
      }
      $groupsNode = $glossaryNode->appendElement('groups');
      if ($paging) {
        $groupsNode->append($paging);
      }
      $groups = [];
      foreach ($this->characters() as $group) {
        if (empty($group['character'])) {
          continue;
        }
        $reference = clone $pageReference;
        $reference->setParameters(
          [
            'char' => $group['character'],
            'mode' => $outputMode
          ]
        );
        $groups[$group['character']] = $groupsNode->appendElement(
          'group',
          [
            'character' => $group['character'],
            'count' => $group['count'],
            'href' => $reference
          ]
        );
        if ($group['character'] == $character) {
          $groups[$group['character']]->setAttribute('selected', 'true');
        }
      }
      foreach ($this->words() as $word) {
        if (isset($groups[$word['character']])) {
          /** @var PapayaXmlElement $group */
          $group = $groups[$word['character']];
          $parameters['term'] = $word['term_id'];
          $reference = clone $pageReference;
          $reference->setPageTitle(isset($linkTextModes[$word['type']]) ? $word['word'] : $word['term_title']);
          $reference->setParameters($parameters);
          $term = $group->appendElement(
            'term',            [
              'type' => PapayaUtilArray::get($this->_linkTypes, $word['type'], ''),
              'href' => $reference,
            ]
          );
          $term->appendElement('title', [], $word['word']);
          $term->appendElement('updated')->appendText(PapayaUtilDate::timestampToString($word['term_modified']));
          if ($this->configuration()->get('fullpage', FALSE)) {
            $term->appendElement('explanation')->appendXml($word['term_explanation']);
          }
        }
      }
    }
    $parent->append($filters);
  }

  /**
   * Append the teaser output xml to the DOM.
   *
   * @see PapayaXmlAppendable::appendTo()
   * @param PapayaXmlElement $parent
   * @return NULL|PapayaXmlElement|void
   */
  public function appendQuoteTo(PapayaXmlElement $parent) {
    $parent->appendElement('title', [], $this->content()->get('title', ''));
    $parent->appendElement('text')->appendXml($this->content()->get('teaser', ''));
  }

  /**
   * The content is an {@see ArrayObject} containing the stored data.
   *
   * @see PapayaPluginEditable::content()
   * @param PapayaPluginEditableContent $content
   * @return PapayaPluginEditableContent
   */
  public function content(PapayaPluginEditableContent $content = NULL) {
    if (isset($content)) {
      $this->_content = $content;
    } elseif (NULL == $this->_content) {
      $this->_content = new PapayaPluginEditableContent();
      $this->_content->callbacks()->onCreateEditor = [$this, 'createEditor'];
    }
    return $this->_content;
  }

  /**
   * The configuration is an {@see ArrayObject} containing options that can affect the
   * execution of other methods (like appendTo()).
   *
   * @see PapayaPluginConfigurable::configuration()
   * @param PapayaObjectParameters $configuration
   * @return PapayaObjectParameters
   */
  public function configuration(PapayaObjectParameters $configuration = NULL) {
    if (isset($configuration)) {
      $this->_configuration = $configuration;
    } elseif (NULL == $this->_configuration) {
      $this->_configuration = new PapayaObjectParameters();
    }
    return $this->_configuration;
  }

  /**
   * The editor is used to change the stored data in the administration interface.
   *
   * In this case it the editor creates an dialog from a field definition.
   *
   * @see PapayaPluginEditableContent::editor()
   *
   * @param object $callbackContext
   * @param PapayaPluginEditableContent $content
   * @return PapayaPluginEditor
   */
  public function createEditor(
    /** @noinspection PhpUnusedParameterInspection */
    $callbackContext,
    PapayaPluginEditableContent $content
  ) {
    $editor = new PapayaAdministrationPluginEditorDialog($content);
    $dialog = $editor->dialog();
    $dialog->fields[] = new PapayaUiDialogFieldSelectRadio(
      new PapayaUiStringTranslated('Glossary'),
      'glossary',
      new PapayaIteratorMultiple(
        PapayaIteratorMultiple::MIT_KEYS_ASSOC,
        [
          '0' => new PapayaUiStringTranslated('All')
        ],
        new PapayaIteratorCallback(
          $this->glossaries(),
          function ($element) {
            if (!empty($element['title'])) {
              return $element['title'];
            } elseif (!empty($element['title_fallback'])) {
              return $element['title_fallback'];
            } elseif (empty($element['title'])) {
              return '[#'.$element['id'].']';
            }
          }
        )
      )
    );
    $dialog->fields[] = $field = new PapayaUiDialogFieldSelectCheckboxes(
      new PapayaUiStringTranslated('Link Types'),
      'glossary_word_types',
      new PapayaUiStringTranslatedList(
        [
          GlossaryContentTermWords::TYPE_TERM => 'Term',
          GlossaryContentTermWords::TYPE_SYNONYM => 'Synonym',
          GlossaryContentTermWords::TYPE_ABBREVIATION => 'Abbreviation',
          GlossaryContentTermWords::TYPE_DERIVATION => 'Derivation'
        ]
      )
    );
    $field->setDefaultValue(
      [GlossaryContentTermWords::TYPE_TERM]
    );
    $dialog->fields[] = $field = new PapayaUiDialogFieldSelectCheckboxes(
      new PapayaUiStringTranslated('Link Url'),
      'glossary_word_url_text',
      new PapayaUiStringTranslatedList(
        [
          GlossaryContentTermWords::TYPE_SYNONYM => 'Synonym',
          GlossaryContentTermWords::TYPE_ABBREVIATION => 'Abbreviation',
          GlossaryContentTermWords::TYPE_DERIVATION => 'Derivation'
        ]
      ),
      FALSE
    );
    $dialog->fields[] = $field = new PapayaUiDialogFieldInputNumber(
      new PapayaUiStringTranslated('Terms per page'),
      'steps',
      $this->_defaultPageLimit,
      TRUE,
      1,
      3
    );
    $dialog->fields[] = $group = new PapayaUiDialogFieldGroup(
      new PapayaUiStringTranslated('Texts')
    );
    $group->fields[] = $field = new PapayaUiDialogFieldInput(
      new PapayaUiStringTranslated('Title'),
      'title',
      400
    );
    $field->setMandatory(TRUE);
    $group->fields[] = $field = new PapayaUiDialogFieldTextareaRichtext(
      new PapayaUiStringTranslated('Teaser'),
      'teaser',
      5,
      '',
      new PapayaFilterXml(),
      PapayaUiDialogFieldTextareaRichtext::RTE_SIMPLE
    );
    $group->fields[] = $field = new PapayaUiDialogFieldTextareaRichtext(
      new PapayaUiStringTranslated('Text'),
      'text',
      15,
      '',
      new PapayaFilterXml()
    );
    $editor->papaya($this->papaya());
    return $editor;
  }

  /**
   * Define the code definition parameters for the output.
   *
   * @see PapayaPluginCacheable::cacheable()
   * @param PapayaCacheIdentifierDefinition $definition
   * @return PapayaCacheIdentifierDefinition
   */
  public function cacheable(PapayaCacheIdentifierDefinition $definition = NULL) {
    if (isset($definition)) {
      $this->_cacheDefinition = $definition;
    } elseif (NULL == $this->_cacheDefinition) {
      $this->_cacheDefinition = new PapayaCacheIdentifierDefinitionGroup(
        new PapayaCacheIdentifierDefinitionPage(),
        new PapayaCacheIdentifierDefinitionParameters(
          ['mode', 'char', 'term', 'page']
        ),
        new PapayaCacheIdentifierDefinitionCallback(
          function() {
            $pageUrl = new PapayaUiReferencePage();
            $pageUrl->load($this->papaya()->request);
            return [
              $pageUrl->getPageTitle()
            ];
          }
        )
      );
    }
    return $this->_cacheDefinition;
  }

  public function filters(PapayaPluginFilterContent $filters = NULL) {
    if (isset($filters)) {
      $this->_contentFilters = $filters;
    } elseif (NULL == $this->_contentFilters) {
      $this->_contentFilters = new PapayaPluginFilterContentRecords($this->_owner);
    }
    return $this->_contentFilters;
  }

  public function glossaries(GlossaryContentGlossaries $glossaries = NULL) {
    if (isset($glossaries)) {
      $this->_glossaries = $glossaries;
    } elseif (NULL == $this->_glossaries) {
      $this->_glossaries = new GlossaryContentGlossaries();
      $this->_glossaries->papaya($this->papaya());
      $this->_glossaries->activateLazyLoad(
        [
          'language_id' => $this->papaya()->administrationLanguage->id
        ]
      );
    }
    return $this->_glossaries;
  }

  /**
   * @param GlossaryContentTermWordCharacters $characters
   * @return GlossaryContentTermWordCharacters
   */
  public function characters(GlossaryContentTermWordCharacters $characters = NULL) {
    if (isset($characters)) {
      $this->_characters = $characters;
    } elseif (NULL === $this->_characters) {
      $this->_characters = new GlossaryContentTermWordCharacters();
      $this->_characters->papaya($this->papaya());
      $filter = [
        'language_id' => $this->papaya()->request->languageId,
        'glossary_id' => $this->getGlossaryId(),
        'type' => $this->getLinkTypes()
      ];
      $this->_characters->activateLazyLoad($filter);
    }
    return $this->_characters;
  }

  public function term(GlossaryContentTermTranslation $term = NULL) {
    if (isset($term)) {
      $this->_term = $term;
    } elseif (NULL === $this->_term) {
      $this->_term = new GlossaryContentTermTranslation();
      $this->_term->papaya($this->papaya());
    }
    return $this->_term;
  }

  /**
   * @param GlossaryContentTermWords $words
   * @return GlossaryContentTermWords
   */
  public function words(GlossaryContentTermWords $words = NULL) {
    if (isset($words)) {
      $this->_words = $words;
    } elseif (NULL === $this->_words) {
      $this->_words = new GlossaryContentTermWords();
      $this->_words->papaya($this->papaya());
      $filter = [
        'language_id' => $this->papaya()->request->languageId,
        'glossary_id' => $this->getGlossaryId(),
        'type' => $this->getLinkTypes()
      ];
      $character = $this->getCharacter();
      if (!empty($character)) {
        $filter['character,contains'] = $character.'*';
      }
      if ($this->parameters()->get('mode', '') == 'flat') {
        $this->_words->activateLazyLoad($filter);
      } else {
        $pageSize = $this->content()->get('steps', $this->_defaultPageLimit, new PapayaFilterInteger(1));
        $this->_words->activateLazyLoad(
          $filter,
          $pageSize,
          ($this->parameters()->get('page', 1, new PapayaFilterInteger(1)) - 1) * $pageSize
        );
      }
    }
    return $this->_words;
  }

  private function getGlossaryId() {
    $glossaryId = $this->content()->get('glossary', 0);
    $optionName = $this->content()->get('glossary_domain_option', '');
    if (
      !empty($optionsName) &&
      ($domainConnector = $this->papaya()->plugins->get(self::DOMAIN_CONNECTOR_GUID))
    ) {
      if ($data = $domainConnector->loadValues($optionName)) {
        if (($domainGlossaryId = (int)$data[$optionName]) > 0) {
          $glossaryId = $domainGlossaryId;
        }
      }
    }
    return $glossaryId > 0 ? $glossaryId : NULL;
  }

  private function getCharacter() {
    $character = PapayaUtilStringUtf8::toLowerCase(
      $this->parameters()->get(
        'char',
        '',
        new PapayaFilterPcre('(^[\\p{L}0])u')
      )
    );
    return $character;
  }

  private function getLinkTypes() {
    return $this->content()->get(
      'glossary_word_types',
      [GlossaryContentTermWords::TYPE_TERM],
      new PapayaFilterLogicalAnd(
        new PapayaFilterArraySize(1),
        new PapayaFilterArray(
          new PapayaFilterList(
            [
              GlossaryContentTermWords::TYPE_TERM,
              GlossaryContentTermWords::TYPE_SYNONYM,
              GlossaryContentTermWords::TYPE_ABBREVIATION,
              GlossaryContentTermWords::TYPE_DERIVATION
            ]
          )
        )
      )
    );
  }

  /**
   * @param PapayaRequest $request
   * @return bool|string
   */
  public function validateUrl(PapayaRequest $request) {
    $termId = $this->parameters()->get('term', 0);
    if ($termId > 0) {
      $words = $this->term()->words();
      $linkTextModes = array_flip($this->content()->get('glossary_word_url_text', []));
      $filter = [
        'language_id' => $this->papaya()->request->languageId,
        'term_id' => $termId,
        'glossary_id' => $this->getGlossaryId(),
        'type' => $this->getLinkTypes()
      ];
      if ($termId && $words->load($filter)) {
        $pageUrl = new PapayaUiReferencePage();
        $pageUrl->load($this->papaya()->request);
        foreach ($words as $word) {
          $pageTitle = isset($linkTextModes[$word['type']]) ? $word['word'] : $word['term_title'];
          $pageTitleNormalized = PapayaUtilFile::normalizeName(
            $pageTitle, 100, $this->papaya()->request->languageIdentifier
          );
          if ($pageUrl->getPageTitle() == $pageTitleNormalized) {
            return TRUE;
          }
        }
        if (!empty($word['term_title'])) {
          $pageUrl->setPageTitle($word['term_title']);
          $pageUrl->setParameters($this->papaya()->request->getParameters(PapayaRequest::SOURCE_QUERY));
          return $pageUrl->get();
        }
      }
    }
    return FALSE;
  }
}
