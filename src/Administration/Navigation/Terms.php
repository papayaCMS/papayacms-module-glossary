<?php

class GlossaryAdministrationNavigationTerms extends PapayaUiControlCommand {

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
   * @var PapayaUiToolbarPaging
   */
  private $_paging;

  public function appendTo(PapayaXmlElement $parent) {
    $parent->append($this->filterDialog());
    $this->listview()->toolbars->topLeft->elements[] = $this->paging();
    $parent->append($this->listview());
  }

  public function filterDialog(PapayaUiDialog $filterDialog = NULL) {
    if (isset($filterDialog)) {
      $this->_filterDialog = $filterDialog;
    } elseif (NULL === $this->_filterDialog) {
      $this->_filterDialog = $dialog = new PapayaUiDialog();
      $dialog->parameterGroup('filter');
      $dialog->options->dialogWidth = PapayaUiDialogOptions::SIZE_SMALL;
      $dialog->options->captionStyle = PapayaUiDialogOptions::CAPTION_NONE;
      $dialog->caption = new PapayaUiStringTranslated('Search');
      $dialog->fields[] = new PapayaUiDialogFieldInput(new PapayaUiStringTranslated('Term'), 'term');
      $dialog->fields[] = new PapayaUiDialogFieldSelect(
        new PapayaUiStringTranslated('Glossary'), 'glossary', [ 0 => 'All' ]
      );
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
        [$this->parameterGroup(), 'page'],
        $this->terms()->absCount()
      );
      $paging->papaya($this->papaya());
    }
    return $this->_paging;
  }

  public function terms(GlossaryContentTerms $terms = NULL) {
    if (isset($terms)) {
      $this->_terms = $terms;
    } elseif (NULL == $this->_terms) {
      $this->_terms = new GlossaryContentTerms();
      $this->_terms->papaya($this->papaya());
      $this->_terms->activateLazyLoad(
        [
          'language_id' => $this->papaya()->administrationLanguage->id
        ],
        $this->paging()->itemsPerPage,
        $this->paging()->currentOffset
      );
    }
    return $this->_terms;
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
        'term_id' => $element['id']
      ),
      $this->parameterGroup()
    );
    $item->selected = (
      $this->parameters()->get('term_id') == $element['id']
    );
  }
}