<?php

class GlossaryAdministrationInformationTerm extends PapayaUiControlCommand  {

  /**
   * @var PapayaUiListview
   */
  private $_listview;

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
    }
    return $this->_listview;
  }

}