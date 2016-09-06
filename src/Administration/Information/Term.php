<?php

class GlossaryAdministrationInformationTerm extends PapayaUiControlCommand  {

  /**
   * @var PapayaUiListview
   */
  private $_listview;

  /**
   * @var GlossaryContentTerm
   */
  private $_term;

  /**
   * @var GlossaryContentTermTranslations
   */
  private $_translations;

  public function __construct(GlossaryContentTerm $term) {
    $this->_term = $term;
  }

  public function appendTo(PapayaXmlElement $parent) {
    $parent->append($this->listview());
  }

  public function listview(PapayaUiListview $listview = NULL) {
    if (isset($listview)) {
      $this->_listview = $listview;
    } elseif (NULL == $this->_listview) {
      $this->_listview = $listview = new PapayaUiListview();
      $listview->papaya($this->papaya());
      $listview->caption = new PapayaUiStringTranslated('Information');
      $translations = $this->translations();
      foreach ($this->papaya()->languages as $language) {
        if (isset($translations[$language['id']])) {
          $translation = $translations[$language['id']];
          $listview->items[] = $item = new PapayaUiListviewItem(
            (empty($language['image'])) ? '' : './pics/language/'.$language['image'],
            $language['title']
          );
          $item->columnSpan = 2;
          $listview->items[] = $item = new PapayaUiListviewItem('', new PapayaUiStringTranslated('Term'));
          $item->subitems[] = new PapayaUiListviewSubitemText($translation['term']);
          $listview->items[] = $item = new PapayaUiListviewItem('', new PapayaUiStringTranslated('Modified'));
          $item->subitems[] = new PapayaUiListviewSubitemDate((int)$translation['modified']);
        }
      }
    }
    return $this->_listview;
  }

  public function translations(GlossaryContentTermTranslations $translations = NULL) {
    if (isset($translations)) {
      $this->_translations = $translations;
    } elseif (NULL === $this->_translations) {
      $this->_translations = $this->_term->translations();
      $this->_translations->papaya($this->papaya());
    }
    return $this->_translations;
  }
}