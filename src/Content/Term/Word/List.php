<?php

class GlossaryContentTermWordList implements IteratorAggregate {

  private $_words = [];

  public function __construct(array $data) {
    foreach ($data as $type => $string) {
      $this->add($type, $string);
    }
  }

  private function add($type, $string) {
    preg_match_all(
      '((?:^|,\\s*)(?<word>(?<firstWord>(?<firstChar>\\p{L})?[^,\\s]+)[^,]*))u',
      $string,
      $matches,
      PREG_SET_ORDER
    );
    foreach ($matches as $match) {
      $word = trim(PapayaUtilArray::get($match, 'word', ''));
      if (!isset($this->_words[$word])) {
        $this->_words[$word] = [
          'type' => $type,
          'word' => $word,
          'normalized' => PapayaUtilStringUtf8::toLowerCase(
            PapayaUtilArray::get($match, 'firstWord', '')
          ),
          'character' => PapayaUtilStringUtf8::toLowerCase(
            PapayaUtilArray::get($match, 'firstChar', '0')
          )
        ];
      }
    }
  }

  public function getIterator() {
    return new ArrayIterator($this->_words);
  }
}