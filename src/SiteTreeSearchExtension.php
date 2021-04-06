<?php

namespace jbennecker\ElementalSiteSearch;

use DNADesign\Elemental\Extensions\ElementalPageExtension;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\Connect\MySQLSchemaManager;
use SilverStripe\ORM\DataExtension;
use SilverStripe\View\SSViewer;

class SiteTreeSearchExtension extends DataExtension
{
    /**
     * @var array
     */
    private static $db = [
        'Title' => 'Varchar(255)',
        'SearchContent' => 'Text',
        'Keywords' => 'Text',
        'ShowInSearch' => 'Boolean',
        'Weight' => 'Int',
    ];

    /**
     * @var array
     */
    private static $indexes = [
        'SearchFields' => [
            'type' => 'fulltext',
            'columns' => ['Title', 'SearchContent', 'Keywords'],
        ],
    ];

    /**
     * @var array
     */
    private static $defaults = [
        'ShowInSearch' => true,
        'Weight' => 1,
    ];

    /**
     * @var array
     */
    private static $create_table_options = [
        MySQLSchemaManager::ID => 'ENGINE=MyISAM',
    ];

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $this->getOwner()->SearchContent = $this->collateSearchContent();
    }

    /**
     * Generate the search content to use for the searchable object
     *
     * We just retrieve it from the templates.
     */
    private function collateSearchContent(): string
    {
        // Get the page
        /** @var SiteTree $page */
        $page = $this->getOwner();

        // Get the page's default content if we have any
        /** @var string $content */
        $content = $this->getOwner()->Content;

        if (self::isElementalPage($page)) {
            // Get the page's elemental content
            $content .= $this->collateSearchContentFromElements();
        }

        return $content;
    }

    /**
     * @param SiteTree $page
     * @return mixed
     */
    private static function isElementalPage($page)
    {
        return $page::has_extension(ElementalPageExtension::class);
    }

    /**
     * @return string|string[]|null
     */
    private function collateSearchContentFromElements()
    {
        // Get the original theme
        $originalThemes = SSViewer::get_themes();

        // Init content
        $content = '';

        try {
            // Enable frontend themes in order to correctly render the elements as they would be for the frontend
            Config::nest();
            SSViewer::set_themes(SSViewer::config()->get('themes'));

            // Get the elements content
            $content .= $this->getOwner()->getElementsForSearch();

            // Clean up the content
            $content = preg_replace('/\s+/', ' ', $content);

            // Return themes back for the CMS
            Config::unnest();
        } finally {
            // Restore themes
            SSViewer::set_themes($originalThemes);
        }

        return $content;
    }

    public function updateSettingsFields(FieldList &$fields)
    {
        $fields->insertAfter('ShowInMenus', CheckboxField::create('ShowInSearch', 'Show in search?'));
    }
}
