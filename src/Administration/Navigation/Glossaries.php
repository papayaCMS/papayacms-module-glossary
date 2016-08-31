<?php

class GlossaryAdministrationNavigationGlossaries extends PapayaUiControlCommand {

  /**
   * @var PapayaUiListview
   */
  private $_listview = NULL;
  /**
   * @var GlossaryContentGlossaries
   */
  private $_glossaries = NULL;

  public function appendTo(PapayaXmlElement $parent) {
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
      $listview->caption = new PapayaUiStringTranslated('Glossaries');
      $listview->builder(
        $builder = new PapayaUiListviewItemsBuilder($this->glossaries())
      );
      $listview->builder()->callbacks()->onCreateItem = array($this, 'callbackCreateItem');
      $listview->builder()->callbacks()->onCreateItem->context = $builder;
      $listview->parameterGroup($this->parameterGroup());
      $listview->parameters($this->parameters());
    }
    return $this->_listview;
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
   * @return null|PapayaUiListviewItem
   */
  public function callbackCreateItem($builder, $items, $element, $index) {
    $items[] = $item = new PapayaUiListviewItem('items-folder', $element['title']);
    if (empty($element['title']) && !empty($element['title_fallback'])) {
      $item->caption = '['.$element['title_fallback'].']';
    } elseif (empty($element['title'])) {
      $item->caption = '[#'.$element['id'].']';
    }
    $item->papaya($this->papaya());
    $item->reference->setParameters(
      array(
        'mode' => 'glossaries',
        'cmd' => 'change',
        'glossary_id' => $element['id']
      ),
      $this->parameterGroup()
    );
    $item->selected = $selected = (
      $this->parameters()->get('glossary_id') == $element['id']
    );
    if ($selected) {
      $item->image = 'status-folder-open';
    }
  }
}