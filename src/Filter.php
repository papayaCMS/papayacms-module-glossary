<?php

class GlossaryFilter
  extends
    PapayaObject
  implements
    PapayaPluginFilterContent,
    PapayaPluginEditable {

  const DOMAIN_CONNECTOR_GUID = '8ec0c5995d97c9c3cc9c237ad0dc6c0b';

  /**
   * @var PapayaPluginEditableContent
   */
  private $_content;

  /**
   * @var GlossaryContentGlossaries
   */
  private $_glossaries;

  /**
   * @var GlossaryContentIgnores
   */
  private $_ignoreWords;

  /**
   * @var GlossaryContentTermWords
   */
  private $_words;

  /**
   * @var GlossaryContentTerms
   */
  private $_terms;

  /**
   * @var array
   */
  private $_used = [];

  /**
   * @var int
   */
  private $_minimumTokenLength = 2;

  /**
   * @var bool
   */
  private $_isFullPage;

  /**
   * @see PapayaPluginEditable::content()
   * @param PapayaPluginEditableContent $content
   * @return PapayaPluginEditableContent
   */
  public function content(PapayaPluginEditableContent $content = NULL) {
    if (isset($content)) {
      $this->_content = $content;
    } elseif (NULL == $this->_content) {
      $this->_content = new PapayaPluginEditableContent();
      $this->_content->callbacks()->onCreateEditor = array($this, 'createEditor');
    }
    return $this->_content;
  }

  /**
   * @param object $callbackContext
   * @param PapayaPluginEditableContent $content
   * @return PapayaPluginEditor
   */
  public function createEditor(
    $callbackContext,
    PapayaPluginEditableContent $content
  ) {
    $editor = new PapayaAdministrationPluginEditorDialog($content);
    $editor->papaya($this->papaya());
    $dialog = $editor->dialog();
    $dialog->caption = new PapayaUiStringTranslated('Configure glossary data filter');
    $dialog->image = '';
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
          function($element) {
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
    if ($this->papaya()->plugins->has(self::DOMAIN_CONNECTOR_GUID)) {
      $dialog->fields[] = new PapayaUiDialogFieldInput(
        new PapayaUiStringTranslated('Domain option'),
        'glossary_domain_option',
        100,
        NULL,
        new PapayaFilterPcre('(^[A-Z_]+$)D')
      );
    }
    $dialog->fields[] = $group = new PapayaUiDialogFieldGroup(
      new PapayaUiStringTranslated('Link')
    );
    $group->fields[] = new PapayaUiDialogFieldInputPage(
      new PapayaUiStringTranslated('Glossary Page Id'),
      'glossary_page_id'
    );
    $group->fields[] = new PapayaUiDialogFieldInput(
      new PapayaUiStringTranslated('Glossary Link Class'),
      'glossary_link_class',
      100,
      'glossaryTermLink',
      new PapayaFilterPcre('(^[a-zA-Z-]+$)')
    );
    $group->fields[] = new PapayaUiDialogFieldSelectRadio(
      new PapayaUiStringTranslated('Backlink Parameters'),
      'add_refpage',
      new PapayaUiStringTranslatedList(
        [
          0 => 'disable',
          1 => 'enable'
        ]
      )
    );
    return $editor;
  }

  function prepare($content, PapayaObjectParameters $options = NULL) {
    $options = isset($options) ? $options : new PapayaObjectParameters([]);
    $this->_isFullPage = $options->get('fullpage', false);
    $this->_used = [];
    $tokens = preg_split('([^\pL]+)u', $content);
    $ignoreWords = iterator_to_array(
      new PapayaIteratorCallback(
        $this->ignoreWords(),
        function($value) {
          return $value['word'];
        },
        PapayaIteratorCallback::MODIFY_KEYS
      )
    );
    $words = [];
    foreach ($tokens as $token) {
      $word = PapayaUtilStringUtf8::toLowerCase($token);
      if (!isset($ignoreWords[$word]) && PapayaUtilStringUtf8::length($word) >= $this->_minimumTokenLength) {
        $words[$word] = TRUE;
      }
    };
    if (count($words) > 0) {
      $filter = [
        'language_id' => $this->papaya()->request->languageId,
        'normalized' => array_keys($words),
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
      $glossaryId = $this->getGlossaryFromDomainOptions($this->content()->get('glossary', 0));
      if ($glossaryId > 0) {
        $filter['glossary_id'] = $glossaryId;
      }
      $this->words()->load($filter);
    }
  }

  function applyTo($content) {
    if (empty($content)) {
      return $content;
    }
    try {
      $targetPageId = $this->content()->get('glossary_page_id', 0);
      $linkClassName = $this->content()->get('glossary_link_class', 'glossaryTermLink');
      if ($this->content()->get('add_refpage', 0)) {
        $parameters = [
          'refpage' => $this->papaya()->request->pageId
        ];
        $queryString = $this->papaya()->request->getParameters(PapayaRequest::SOURCE_QUERY)->getQueryString(
          $this->papaya()->request->getParameterGroupSeparator()
        );
        if ((string)$queryString !== '') {
          $parameters['refparams'] = $queryString;
        }
      }
      $linkTextModes = array_flip($this->content()->get('glossary_word_url_text', []));
      $words = iterator_to_array(
        new PapayaIteratorCallback(
          $this->words(),
          function($record) {
            return PapayaUtilStringUtf8::toLowerCase($record['word']);
          },
          PapayaIteratorCallback::MODIFY_KEYS
        )
      );
      uksort(
        $words,
        function($one, $two) {
          return PapayaUtilStringUtf8::length($two) - PapayaUtilStringUtf8::length($one);
        }
      );
      $pattern = '(\b('.implode('|', array_map('preg_quote', array_keys($words))).')\b)iu';
      $document = new PapayaXmlDocument();
      $document->appendElement('content')->appendXml($content);
      foreach ($document->xpath()->evaluate('//text()[not(ancestor::a)]') as $textNode) {
        $parts = preg_split($pattern, $textNode->textContent, -1, PREG_SPLIT_DELIM_CAPTURE);
        if (count($parts) > 1) {
          /** @var PapayaXmlElement $parentNode */
          $parentNode = $textNode->parentNode;
          foreach ($parts as $part) {
            $lower = PapayaUtilStringUtf8::toLowerCase($part);
            if (isset($words[$lower])) {
              $word = $words[$lower];
              $termId = $word['term_id'];
              $reference = $this->papaya()->pageReferences->get(
                $this->papaya()->request->languageIdentifier, $targetPageId
              );
              $pageTitle = isset($linkTextModes[$word['type']]) ? $word['word'] : $word['term_title'];
              $reference->setPageTitle(PapayaUtilFile::normalizeName($pageTitle, 100));
              $parameters['term'] = $termId;
              $reference->setParameters($parameters);
              if ($this->_isFullPage) {
                $this->_used[$termId][$lower] = TRUE;
              }
              $parentNode->insertBefore(
                $document->createElement(
                  'a',
                  $part,
                  [
                    'href' => (string)$reference,
                    'class' => $linkClassName,
                    'data-term-id' => $termId
                  ]
                ),
                $textNode
              );
            } else {
              $parentNode->insertBefore(
                $document->createTextNode($part),
                $textNode
              );
            }
          }
          $parentNode->removeChild($textNode);
        }
      }
      return $document->documentElement->saveFragment();
    } catch (PapayaXmlException $e) {
    }
    return $content;
  }

  function appendTo(PapayaXmlElement $parent) {
    if (count($this->_used) > 0) {
      $this->terms()->load(
        [
          'id' => array_keys($this->_used),
          'language_id' => $this->papaya()->request->languageId
        ]
      );
    } elseif (count($this->words()) > 0) {
      $this->terms()->load(
        [
          'id' => iterator_to_array(new PapayaIteratorArrayMapper($this->words(), 'term_id')),
          'language_id' => $this->papaya()->request->languageId
        ]
      );
    }
    if (count($this->terms()) > 0) {
      $glossary = $parent->appendElement('glossary');
      foreach ($this->terms() as $term) {
        $entry = $glossary->appendElement(
          'term',
          [
            'id' => $term['id']
          ]
        );
        $entry->appendElement('title', [], $term['term']);
        $entry->appendElement('explanation')->appendXml($term['explanation']);
        $synonyms = $entry->appendElement('synonyms');
        $this->appendWords($synonyms, 'synonym', $term['synonyms']);
        $synonyms = $entry->appendElement('abbreviations');
        $this->appendWords($synonyms, 'abbreviation', $term['abbreviations']);
        $synonyms = $entry->appendElement('derivations');
        $this->appendWords($synonyms, 'derivation', $term['derivations']);
      }
    }
  }

  private function appendWords(PapayaXmlElement $parent, $tagName, $string) {
    preg_match_all(
      '((?:^|,\\s*)(?<word>[^,]*))u', $string, $matches, PREG_SET_ORDER
    );
    foreach ($matches as $word) {
      if (trim($word['word']) != '') {
        $parent->appendElement($tagName, [], trim($word['word']));
      }
    }
  }

  private function getGlossaryFromDomainOptions($default) {
    $optionName = $this->content()->get('glossary_domain_option', '');
    if (
      !empty($optionsName) &&
      ($domainConnector = $this->papaya()->plugins->get(self::DOMAIN_CONNECTOR_GUID))
    ) {
      if ($data = $domainConnector->loadValues($optionName)) {
        $glossaryId = (int)$data[$optionName];
        return $glossaryId > 0 ? $glossaryId : $default;
      }
    }
    return $default;
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


  public function ignoreWords(GlossaryContentIgnores $ignoreWords = NULL) {
    if (isset($ignoreWords)) {
      $this->_ignoreWords = $ignoreWords;
    } elseif (NULL == $this->_ignoreWords) {
      $this->_ignoreWords = new GlossaryContentIgnores();
      $this->_ignoreWords->papaya($this->papaya());
      $this->_ignoreWords->activateLazyLoad(
        [
          'language_id' => $this->papaya()->request->languageId
        ]
      );
    }
    return $this->_ignoreWords;
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
    }
    return $this->_words;
  }

  public function terms(GlossaryContentTerms $terms = NULL) {
    if (isset($terms)) {
      $this->_terms = $terms;
    } elseif (NULL == $this->_terms) {
      $this->_terms = new GlossaryContentTerms();
      $this->_terms->papaya($this->papaya());
    }
    return $this->_terms;
  }
}