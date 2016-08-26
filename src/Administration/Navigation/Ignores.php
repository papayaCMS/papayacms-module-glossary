<?php

class GlossaryAdministrationNavigationIgnores extends PapayaUiControlCommand {

  /**
   * @var PapayaUiListview
   */
  private $_listview = NULL;
  /**
   * @var GlossaryContentIgnores
   */
  private $_ignoreWords = NULL;
  /**
   * @var PapayaUiToolbarPaging
   */
  private $_paging;

  public function appendTo(PapayaXmlElement $parent) {
    $this->listview()->toolbars->topLeft->elements[] = $this->paging();
    $parent->append($this->listview());
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
      $listview->caption = new PapayaUiStringTranslated('Ignore Words');
      $listview->builder(
        $builder = new PapayaUiListviewItemsBuilder($this->ignoreWords())
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
        $this->ignoreWords()->absCount()
      );
      $paging->papaya($this->papaya());
    }
    return $this->_paging;
  }

  public function ignoreWords(GlossaryContentIgnores $ignoreWords = NULL) {
    if (isset($ignoreWords)) {
      $this->_ignoreWords = $ignoreWords;
    } elseif (NULL == $this->_ignoreWords) {
      $this->_ignoreWords = new GlossaryContentIgnores();
      $this->_ignoreWords->papaya($this->papaya());
      $this->_ignoreWords->activateLazyLoad(
        [
          'language_id' => $this->papaya()->administrationLanguage->id
        ],
        $this->paging()->itemsPerPage,
        $this->paging()->currentOffset
      );
    }
    return $this->_ignoreWords;
  }

  /**
   * @param PapayaUiListviewItemsBuilder $builder
   * @param PapayaUiListviewItems $items
   * @param mixed $element
   * @param mixed $index
   */
  public function callbackCreateItem($builder, $items, $element, $index) {
    $items[] = $item = new PapayaUiListviewItem('items-page-ignoreword', $element['word']);
    $item->papaya($this->papaya());
    $item->reference->setParameters(
      array(
        'mode' => 'ignore-words',
        'cmd' => 'change',
        'ignore_word_id' => $element['id']
      ),
      $this->parameterGroup()
    );
    $item->selected = (
      $this->parameters()->get('ignore_word_id') == $element['id']
    );
  }
}