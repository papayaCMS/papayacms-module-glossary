<?php

class GlossaryAdministrationInformationTermGlossaries extends PapayaUiControlCommandDialog  {

  /**
   * @var PapayaUiListview
   */
  private $_listview;

  /**
   * @var GlossaryContentTerm
   */
  private $_term;

  /**
   * @var GlossaryContentGlossaries
   */
  private $_glossaries;

  /**
   * @var GlossaryContentTermGlossaries
   */
  private $_termGlossaries;

  public function __construct(GlossaryContentGlossaries $glossaries, GlossaryContentTerm $term) {
    $this->_glossaries = $glossaries;
    $this->_term = $term;
  }

  protected function createDialog() {
    $this->_term->glossaries()->load(
      ['term_id' => $this->_term['id']]
    );
    $dialog = parent::createDialog();
    $dialog->caption = new PapayaUiStringTranslated("Glossaries");
    $dialog->parameterGroup($this->parameterGroup());
    $dialog->hiddenFields->merge(
      array(
        'mode' => 'terms',
        'cmd' => 'change',
        'change' => 'term-glossaries',
        'term_id' => $this->_term['id'],
        'language_id' => $this->papaya()->administrationLanguage->id,
        'offset' => $this->parameters()->get('offset', 0),
        'search-for' => $this->parameters()->get('search-for', ''),
        'glossary_id' => $this->parameters()->get('glossary_id', 0)
      )
    );
    $dialog->data()->set(
      'term-glossaries',
      PapayaUtilArrayMapper::byIndex($this->_term->glossaries(), 'id')
    );
    $dialog->fields[] = new PapayaUiDialogFieldCollector(
      'term-glossaries',
      [],
      new PapayaFilterArray(
        new PapayaFilterList(PapayaUtilArrayMapper::byIndex($this->_glossaries, 'id'))
      )
    );
    $dialog->fields[] = $field = new PapayaUiDialogFieldListview($this->listview());
    $dialog->buttons[] = new PapayaUiDialogButtonSubmit(new PapayaUiStringTranslated('Save'));

    $this->callbacks()->onExecuteSuccessful = function() use ($dialog) {
      $termId = $this->_term['id'];
      $glossaryLinks = [];
      foreach ($dialog->parameters()->get('term-glossaries', []) as $glossaryId) {
        $glossaryLinks[] = ['term_id' => $termId, 'id' => (int)$glossaryId];
      }
      if (
        $this->_term->glossaries()->truncate(['term_id' => $termId]) &&
        (
          empty($glossaryLinks) ||
          FALSE !== $this->_term->glossaries()->insert($glossaryLinks)
        )
      ) {
        $this->papaya()->messages->dispatch(
          new PapayaMessageDisplayTranslated(
            PapayaMessage::SEVERITY_INFO, 'Term glossaries saved.'
          )
        );
      }
    };
    return $dialog;
  }

  public function listview(PapayaUiListview $listview = NULL) {
    if (isset($listview)) {
      $this->_listview = $listview;
    } elseif (NULL == $this->_listview) {
      $this->_listview = $listview = new PapayaUiListview();
      $listview->papaya($this->papaya());
      $listview->builder(
        $builder = new PapayaUiListviewItemsBuilder($this->_glossaries)
      );
      $listview->builder()->callbacks()->onCreateItem = array($this, 'callbackCreateItem');
      $listview->builder()->callbacks()->onCreateItem->context = $builder;
      $listview->parameterGroup($this->parameterGroup());
      $listview->parameters($this->parameters());
    }
    return $this->_listview;
  }

  public function termGlossaries(GlossaryContentTermGlossaries $glossaries = NULL) {
    if (isset($glossaries)) {
      $this->_termGlossaries = $glossaries;
    } elseif (NULL == $this->_termGlossaries) {
      $this->_termGlossaries = $this->_term->glossaries();
      $this->_termGlossaries->activateLazyLoad(
        [
          'term_id' => $this->_term['id']
        ]
      );
    }
    return $this->_termGlossaries;
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
    $item->subitems[] = $subitem = new PapayaUiListviewSubitemCheckbox(
      $this->dialog(), 'term-glossaries', $element['id']
    );
    return $item;
  }

  public function createCondition() {
    return new PapayaUiControlCommandConditionRecord($this->_term);
  }
}