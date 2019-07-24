<?php
namespace jbennecker\ElementalSiteSearch;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\FieldType\DBField;

class ContentControllerSearchExtension extends Extension
{

    private static $allowed_actions = [
        'SearchForm',
    ];

    public function SearchForm()
    {
        $form = SearchForm::create($this->owner);
        return $form;
    }

    public function results($data, $form, $request)
    {
        $data = [
            'Results' => $form->getResults(),
            'Query' => DBField::create_field('Text', $form->getSearchQuery()),
            'Title' => _t('ContentControllerSearchExtension.PageTitle', 'Search results'),
        ];
        return $this->owner->customise($data)->renderWith(['Page_results', 'Page']);
    }
}
