<?php

class GlossaryConnector extends PapayaObject {

  public function getTerm($termId, $languageId) {
    $term = new GlossaryContentTerm();
    $term->papaya($this->papaya());
    $term->activateLazyLoad(
       [
         'id' => $termId,
         'language_id' => $languageId
       ]
    );
    return $term;
  }

}