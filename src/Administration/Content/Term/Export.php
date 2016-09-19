<?php

class GlossaryAdministrationContentTermExport
  extends PapayaUiControlCommand {

  private $_quote = '"';
  private $_separator = ',';
  private $_linebreak = "\r\n";
  private $_encodedLinebreak = '\\n';

  /**
   * @var GlossaryContentTermsExport
   */
  private $_terms;

  public function appendTo(PapayaXmlElement $parent) {
    $columns = $this->terms()->mapping()->getProperties();
    $response = $this->papaya()->response;
    $response->setContentType('text/csv');
    $response->headers()->set('Content-Disposition', 'attachment; filename="glossary_export.csv"');
    $response->content(
      new PapayaResponseContentList(
        new PapayaIteratorCallback(
          new PapayaIteratorMultiple(
            [$columns],
            new PapayaIteratorCallback(
              $this->terms(),
              function(array $row) use ($columns) {
                $result = [];
                foreach ($columns as $name) {
                  switch ($name) {
                  case 'created' :
                  case 'modified' :
                    $result[$name] = PapayaUtilDate::timestampToString($row[$name], TRUE, TRUE, FALSE);
                    break;
                  default:
                    $result[$name] = (string)$row[$name];
                  }
                }
                return $result;
              }
            )
          ),
          function (array $row) {
            $result = '';
            foreach ($row as $value) {
              $result .= $this->_separator.$this->csvQuote($value);
            }
            return substr($result, strlen($this->_separator));
          }
        ),
        $this->_linebreak
      )
    );
    $response->send(TRUE);
  }

  public function terms(GlossaryContentTermsExport $terms = NULL) {
    if (isset($terms)) {
      $this->_terms = $terms;
    } elseif (NULL == $this->_terms) {
      $this->_terms = new GlossaryContentTermsExport();
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
      $this->_terms->activateLazyLoad($filter);
    }
    return $this->_terms;
  }

  /**
   * Prepare a header or data value for csv. The value is escaped and quotes if needed.
   *
   * @param string $value
   * @return string
   */
  private function csvQuote($value) {
    $quotesNeeded =
      '('.preg_quote($this->_quote).'|'.preg_quote($this->_separator).'|[\r\n])';
    if (preg_match($quotesNeeded, $value)) {
      $encoded = preg_replace(
        array(
          '('.preg_quote($this->_quote).')',
          "(\r\n|\n\r|[\r\n])"
        ),
        array(
          $this->_quote.'$0',
          $this->_encodedLinebreak
        ),
        $value
      );
      return $this->_quote.$encoded.$this->_quote;
    } else {
      return $value;
    }
  }
}