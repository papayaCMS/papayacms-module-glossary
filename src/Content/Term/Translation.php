<?php

class GlossaryContentTermTranslation extends PapayaDatabaseRecordLazy {

  protected $_fields = [
    'id' => 'glossary_term_id',
    'language_id' => 'language_id',
    'modified' => 'glossary_term_modified',
    'created' => 'glossary_term_created',
    'term' => 'glossary_term',
    'explanation' => 'glossary_term_explanation',
    'source' => 'glossary_term_source',
    'links' => 'glossary_term_links',
    'synonyms' => 'glossary_term_synonyms',
    'abbreviations' => 'glossary_term_abbreviations',
    'derivations' => 'glossary_term_derivations'
  ];

  protected $_tableName = GlossaryContentTables::TABLE_TERM_TRANSLATIONS;

  /**
   * @var GlossaryContentTermWords
   */
  private $_words;

  protected function _createKey() {
    return new PapayaDatabaseRecordKeyFields($this, $this->_tableName, ['id', 'language_id']);
  }

  public function _createCallbacks() {
    $callbacks = parent::_createCallbacks();
    $callbacks->onBeforeDelete = function() {
      return $this->words()->truncate(['term_id' => $this['id']]);
    };
    $callbacks->onBeforeInsert = function() {
      $term = new GlossaryContentTerm();
      if ($this['id'] < 1 || !$term->load(['id' => $this['id']])) {
        $term->save();
        $this['id'] = $term['id'];
      }
      $this['created'] = $this['modified'] = time();
      return $term['id'] > 0;
    };
    $callbacks->onBeforeUpdate = function() {
      $this['modified'] = time();
      return true;
    };
    $callbacks->onAfterInsert = [$this, 'updateWords'];
    $callbacks->onAfterUpdate = [$this, 'updateWords'];
    return $callbacks;
  }

  public function updateWords() {
    $keys = [
      'term' => GlossaryContentTermWords::TYPE_TERM,
      'synonyms' => GlossaryContentTermWords::TYPE_SYNONYM,
      'abbreviations' => GlossaryContentTermWords::TYPE_ABBREVIATION,
      'derivations' => GlossaryContentTermWords::TYPE_DERIVATION
    ];
    $words = [];
    foreach ($keys as $key => $type) {
      $this->buildWordList($words, $this[$key], $type);
    }
    $filter = [
      'language_id' => $this['language_id'],
      'term_id' => $this['id']
    ];
    if ($this->words()->truncate($filter)) {
      var_dump($words);
      $this->words()->insert($words);
    }
  }

  private function buildWordList(&$words, $string, $type) {
    preg_match_all(
      '((?:^|,\\s*)(?<word>(?<firstWord>(?<firstChar>\\p{L})?[^,\\s]+)[^,]*))u',
      $string,
      $matches,
      PREG_SET_ORDER
    );
    foreach ($matches as $word) {
      $words[] = [
        'language_id' => $this['language_id'],
        'term_id' => $this['id'],
        'type' => $type,
        'word' => trim(PapayaUtilArray::get($word, 'word', '')),
        'normalized' => PapayaUtilStringUtf8::toLowerCase(
          PapayaUtilArray::get($word, 'firstWord', '')
        ),
        'character' => PapayaUtilStringUtf8::toLowerCase(
          PapayaUtilArray::get($word, 'firstChar', '0')
        )
      ];
    }
  }

  /**
   * @param GlossaryContentTermWords $words
   * @return GlossaryContentTermWords
   */
  public function words(GlossaryContentTermWords $words = NULL) {
    if (isset($words)) {
      $this->_words = $words;
    } elseif (NULL === $this->_words) {
      $this->_words = new GlossaryContentTermWords();
      $this->_words->papaya($this->papaya());
    }
    return $this->_words;
  }
}