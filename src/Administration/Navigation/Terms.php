<?php

class GlossaryAdministrationNavigationTerms extends PapayaUiControlCommand {

  const ITEMS_PER_PAGE = 25;

  /**
   * @var PapayaUiDialog
   */
  private $_filterDialog = NULL;

  /**
   * @var PapayaUiListview
   */
  private $_listview = NULL;
  /**
   * @var GlossaryContentTerms
   */
  private $_terms = NULL;

  /**
   * @var GlossaryContentGlossaries
   */
  private $_glossaries;

  /**
   * @var PapayaUiToolbarPaging
   */
  private $_paging;

  public function appendTo(PapayaXmlElement $parent) {
    $parent->append($this->filterDialog());
    $this->paging()->itemsCount = $this->terms()->absCount();
    $this->listview()->toolbars->topLeft->elements[] = $this->paging();
    $parent->append($this->listview());
  }

  public function filterDialog(PapayaUiDialog $filterDialog = NULL) {
    if (isset($filterDialog)) {
      $this->_filterDialog = $filterDialog;
    } elseif (NULL === $this->_filterDialog) {
      $this->_filterDialog = $dialog = new PapayaUiDialog();
      $dialog->options->useConfirmation = FALSE;
      $dialog->options->useToken = FALSE;
      $dialog->parameterMethod(PapayaUiDialog::METHOD_MIXED_GET);
      $dialog->parameterGroup($this->parameterGroup());
      $dialog->options->dialogWidth = PapayaUiDialogOptions::SIZE_SMALL;
      $dialog->options->captionStyle = PapayaUiDialogOptions::CAPTION_NONE;
      $dialog->caption = new PapayaUiStringTranslated('Search');
      $dialog->fields[] = new PapayaUiDialogFieldInput(new PapayaUiStringTranslated('Term'), 'search-for');
      $dialog->fields[] = new PapayaUiDialogFieldSelect(
        new PapayaUiStringTranslated('Glossary'),
        'glossary_id',
        new PapayaIteratorMultiple(
          PapayaIteratorMultiple::MIT_KEYS_ASSOC,
          [ 0 => new PapayaUiStringTranslated('All') ],
          new PapayaIteratorCallback(
            $this->glossaries(),
            function($element) {
              if (!empty($element['title'])) {
                return $element['title'];
              } elseif (!empty($element['title_fallback'])) {
                return $element['title_fallback'];
              } else {
                return '[#'.$element['id'].']';
              }
            }
          )
        ),
        FALSE
      );
      $dialog->buttons[] = $button = new PapayaUiDialogButtonLink(
        new PapayaUiStringTranslated('Clear'),
        PapayaUiDialogButton::ALIGN_LEFT
      );
      $button->reference()->setParameters(
        ['mode' => 'terms'],
        $this->parameterGroup()
      );
      $dialog->buttons[] = new PapayaUiDialogButtonSubmit(new PapayaUiStringTranslated('Search'));
    }
    return $this->_filterDialog;
  }

  /**
   * @param PapayaUiListview $listview
   * @return PapayaUiListview
   */
  public function listview(PapayaUiListview $listview = NULL) {
    if (isset($listview)) {
      $this->_listview = $listview;
    } elseif (NULL === $this->_listview) {
      $this->_listview = $listview = new PapayaUiListview();
      $listview->papaya($this->papaya());
      $listview->caption = new PapayaUiStringTranslated('Terms');
      $listview->builder(
        $builder = new PapayaUiListviewItemsBuilder($this->terms())
      );
      $listview->builder()->callbacks()->onCreateItem = array($this, 'callbackCreateItem');
      $listview->builder()->callbacks()->onCreateItem->context = $builder;
      $listview->parameterGroup($this->parameterGroup());
      $listview->parameters($this->parameters());
    }
    return $this->_listview;
  }

  public function paging(PapayaUiToolbarPaging $paging = NULL) {
    if (isset($paging)) {
      $this->_paging = $paging;
    } elseif (NULL === $this->_paging) {
      $this->_paging = $paging = new PapayaUiToolbarPaging(
        [$this->parameterGroup(), 'offset'], 0, PapayaUiToolbarPaging::MODE_OFFSET
      );
      $paging->papaya($this->papaya());
      $paging->itemsPerPage = self::ITEMS_PER_PAGE;
      $paging->reference()->setParameters(
        [
          'glossary_id' => $this->parameters()->get('glossary_id', 0) ?: NULL,
          'search-for' => $this->parameters()->get('search-for', '') ?: NULL
        ],
        $this->parameterGroup()
      );
    }
    return $this->_paging;
  }

  public function terms(GlossaryContentTerms $terms = NULL) {
    if (isset($terms)) {
      $this->_terms = $terms;
    } elseif (NULL == $this->_terms) {
      $this->_terms = new GlossaryContentTerms();
      $this->_terms->papaya($this->papaya());
      $filter =  [
        'language_id' => $this->papaya()->administrationLanguage->id
      ];
      if ($glossaryId = $this->parameters()->get('glossary_id', 0)) {
        $filter['glossary_id'] = $glossaryId;
      }
      if ($searchFor = $this->parameters()->get('search-for', '')) {
        $filter['term,contains'] = $searchFor;
      }
      $this->_terms->activateLazyLoad(
        $filter,
        $this->paging()->itemsPerPage,
        $this->parameters()->get('offset', 0)
      );
    }
    return $this->_terms;
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
   * @param PapayaUiListviewItemsBuilder $builder
   * @param PapayaUiListviewItems $items
   * @param mixed $element
   * @param mixed $index
   */
  public function callbackCreateItem($builder, $items, $element, $index) {
    $items[] = $item = new PapayaUiListviewItem('items-page', $element['term']);
    if (empty($element['term']) && !empty($element['term_fallback'])) {
      $item->caption = '['.$element['term_fallback'].']';
    } elseif (empty($element['term'])) {
      $item->caption = '[#'.$element['id'].']';
    }
    $item->papaya($this->papaya());
    $item->reference->setParameters(
      array(
        'mode' => 'terms',
        'cmd' => 'change',
        'term_id' => $element['id'],
        'offset' => $this->parameters()->get('offset', 0),
        'search-for' => $this->parameters()->get('search-for', ''),
        'glossary_id' => $this->parameters()->get('glossary_id', 0)
      ),
      $this->parameterGroup()
    );
    $item->selected = (
      $this->parameters()->get('term_id') == $element['id']
    );
  }
}