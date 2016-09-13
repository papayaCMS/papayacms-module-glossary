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
  PapayaPluginCacheable {

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
  private $_defaultPageLimit = 2;

  private $_linkTypes = [
    GlossaryContentTermWords::TYPE_TERM => 'term',
    GlossaryContentTermWords::TYPE_SYNONYM => 'synonym',
    GlossaryContentTermWords::TYPE_DERIVATION => 'derivation',
    GlossaryContentTermWords::TYPE_ABBREVIATION => 'abbreviation',
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
    $character = $this->getCharacter();
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

    $parameters = [];
    if (!empty($character)) {
      $parameters['char'] = $character;
    }
    if (1 < ($pageOffset = $paging->getCurrentPage())) {
      $parameters['page'] = $pageOffset;
    };

    $reference = clone $pageReference;
    $reference->setParameters($parameters);
    $glossaryNode = $parent->appendElement(
      'glossary',
      [
        'char' => $character,
        'href' => $reference
      ]
    );
    $groupsNode = $glossaryNode->appendElement('groups');
    $groupsNode->append($paging);
    $groups = [];
    foreach ($this->characters() as $group) {
      if (empty($group['character'])) {
        continue;
      }
      $reference = clone $pageReference;
      $reference->setParameters(['char' => $group['character']]);
      $groups[$group['character']] = $groupsNode->appendElement(
        'group',
        [
          'character' => $group['character'],
          'count' => $group['count'],
          'href' => $reference
        ]
      );
    }
    $linkTextModes = array_flip($this->content()->get('glossary_word_url_text', []));
    foreach ($this->words() as $word) {
      if (isset($groups[$word['character']])) {
        /** @var PapayaXmlElement $group */
        $group = $groups[$word['character']];
        $parameters['term'] = $word['term_id'];
        $reference = clone $pageReference;
        $pageTitle = isset($linkTextModes[$word['type']]) ? $word['word'] : $word['term_title'];
        $reference->setPageTitle(PapayaUtilFile::normalizeName($pageTitle, 100));
        $reference->setParameters($parameters);
        $term = $group->appendElement(
          'term',
          [
            'type' => PapayaUtilArray::get($this->_linkTypes, $word['type'], ''),
            'href' => $reference,
          ]
        );
        $term->appendElement('title', [], $word['word']);
        $term->appendElement('updated')->appendText(PapayaUtilDate::timestampToString($word['term_modified']));
        $term->appendElement('explanation')->appendXml($word['term_explanation']);
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
      [GlossaryContentTermWords::TYPE_TERM, GlossaryContentTermWords::TYPE_DERIVATION]
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
      $this->_cacheDefinition = new PapayaCacheIdentifierDefinitionPage();
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
        'language_id' => $this->papaya()->request->languageId
      ];
      $this->_characters->activateLazyLoad($filter);
    }
    return $this->_characters;
  }

  private function term(GlossaryContentTermTranslation $term) {
    if (isset($term)) {
      $this->_term = $term;
    } elseif (NULL === $this->_term) {
      $this->_term = new GlossaryContentTermTranslation();
      $this->_term->papaya($this->papaya());
      $filter = [
        'language_id' => $this->papaya()->request->languageId,
        'term_id' => $this->parameters()->get('term', 0)
      ];
      $this->_characters->activateLazyLoad($filter);
    }
    return $this->_characters;
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
        'type' => $this->content()->get(
          'glossary_word_types',
          [GlossaryContentTermWords::TYPE_TERM, GlossaryContentTermWords::TYPE_DERIVATION],
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
        )
      ];
      $character = $this->getCharacter();
      if (!empty($character)) {
        $filter['character,contains'] = $character.'*';
      }
      $pageSize = $this->content()->get('steps', $this->_defaultPageLimit, new PapayaFilterInteger(1));
      $this->_words->activateLazyLoad(
        $filter,
        $pageSize,
        ($this->parameters()->get('page', 1, new PapayaFilterInteger(1)) - 1) * $pageSize
      );
    }
    return $this->_words;
  }

  private function getCharacter() {
    $character = PapayaUtilStringUtf8::toLowerCase(
      $this->parameters()->get(
        'char',
        '',
        new PapayaFilterLogicalAnd(
          new PapayaFilterStringLength(0, 1),
          new PapayaFilterPcre('(^[a-z0])')
        )
      )
    );
    return $character;
  }
}
