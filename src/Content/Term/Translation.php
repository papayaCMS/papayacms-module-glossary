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

  /**
   * @var GlossaryContentTermTranslations
   */
  private $_translations;

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
    $filter = [
      'language_id' => $this['language_id'],
      'term_id' => $this['id']
    ];
    $words = new PapayaIteratorCallback(
      new GlossaryContentTermWordList(
        [
          GlossaryContentTermWords::TYPE_TERM => $this['term'],
          GlossaryContentTermWords::TYPE_SYNONYM => $this['synonyms'],
          GlossaryContentTermWords::TYPE_ABBREVIATION => $this['abbreviations'],
          GlossaryContentTermWords::TYPE_DERIVATION => $this['derivations']
        ]
      ),
      function($record) use ($filter) {
        return array_merge($record, $filter);
      }
    );
    if ($this->words()->truncate($filter)) {
      $this->words()->insert($words);
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

  public function translations(GlossaryContentTermTranslations $translations = NULL) {
    if (isset($translations)) {
      $this->_translations = $translations;
    } elseif (NULL === $this->_translations) {
      $this->_translations = new GlossaryContentTermTranslations();
      $this->_translations->papaya($this->papaya());
      $this->_translations->activateLazyLoad(
        [
          'id' => (int)$this['id']
        ]
      );
    }
    return $this->_translations;
  }
}