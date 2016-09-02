<?php

class GlossaryAdministrationInformation extends PapayaAdministrationPagePart {

  /**
   * @var GlossaryContentGlossaries
   */
  private $_term;
  private $_glossaries;

  /**
   * Overload this method to create the commands structure.
   *
   * @param string $name
   * @param string $default
   * @return PapayaUiControlCommandController
   */
  protected function _createCommands($name = 'cmd', $default = 'terms') {
    $modes = parent::_createCommands($name, $default);
    $modes->parameterGroup($this->parameterGroup());
    $modes['terms'] = new PapayaUiControlCommandList(
      new GlossaryAdministrationInformationTermGlossaries($this->glossaries(), $this->term()),
      new GlossaryAdministrationInformationTerm($this->term())
    );
    return $modes;
  }

  public function term(GlossaryContentTerm $term = NULL) {
    if (isset($term)) {
      $this->_term = $term;
    } elseif (NULL === $this->_term) {
      $this->_term = new GlossaryContentTerm();
      $this->_term->papaya($this->papaya());
      $this->_term->activateLazyLoad(
        [
          'id' => $this->parameters()->get('term_id', 0)
        ]
      );
    }
    return $this->_term;
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
}