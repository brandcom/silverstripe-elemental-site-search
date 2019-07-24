<?php
namespace jbennecker\ElementalSiteSearch;

use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\ORM\Connect\MySQLSchemaManager;
use SilverStripe\ORM\DataExtension;
use SilverStripe\View\SSViewer;

class SiteTreeSearchExtension extends DataExtension
{

    private static $db = [
        'Title' => 'Varchar(255)',
        'SearchContent' => 'Text',
        'Keywords' => 'Text',
        'ShowInSearch' => 'Boolean',
        'Weight' => 'Int',
    ];

    private static $indexes = [
        'SearchFields' => [
            'type' => 'fulltext',
            'columns' => ['Title', 'SearchContent', 'Keywords'],
        ],
    ];

    private static $defaults = [
        'ShowInSearch' => true,
        'Weight' => 1,
    ];

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
     * We just retrive it from the templates.
     *
     * @return string
     */
    private function collateSearchContent()
    {
        // Get the original theme
        $originalThemes = SSViewer::get_themes();

        // Get the page content if we have any
        $content = $this->getOwner()->Content;

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

    public function updateSettingsFields(&$fields)
    {
        $fields->insertAfter('ShowInMenus', CheckboxField::create('ShowInSearch', 'Show in search?'));
    }
}
