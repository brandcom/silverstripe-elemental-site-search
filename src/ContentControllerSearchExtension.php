<?php
namespace jbennecker\ElementalSiteSearch;

use SilverStripe\Core\Extension;
use SilverStripe\ORM\FieldType\DBField;

class ContentControllerSearchExtension extends Extension
{
    private static $allowed_actions = [
        'SearchForm',
    ];

    public function SearchForm(): SearchForm
    {
        return SearchForm::create($this->owner);
    }

    public function results(array $data, SearchForm $form): string
    {
        $data = [
            'Results' => $form->getResults(),
            'Query' => DBField::create_field('Text', $form->getSearchQuery()),
            'Title' => _t(__CLASS__ . '.PAGE_TITLE', 'Search results'),
        ];

        return $this->owner->customise($data)->renderWith(['Page_results', 'Page']);
    }
}
