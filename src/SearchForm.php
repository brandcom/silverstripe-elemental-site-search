<?php

namespace jbennecker\ElementalSiteSearch;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\Validator;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\PaginatedList;
use SilverStripe\ORM\Queries\SQLSelect;

class SearchForm extends Form
{
    private static $casting = [
        'SearchQuery' => 'Text',
    ];

    public function __construct(
        RequestHandler $controller = null,
        $name = 'SearchForm',
        FieldList $fields = null,
        FieldList $actions = null,
        Validator $validator = null
    ) {
        $fields = FieldList::create(
            TextField::create('query', _t(__CLASS__ . '.SEARCH', 'Search'))
                ->setAttribute('placeholder', _t(__CLASS__ . '.SEARCH', 'Search'))
                ->setAttribute('minlength', ('4')),
        );
        $actions = FieldList::create(
            FormAction::create('results', _t(__CLASS__ . '.GO', 'Go'))
        );

        parent::__construct($controller, $name, $fields, $actions, $validator);
        $this->setFormMethod('get');
        $this->disableSecurityToken();
    }

    public function getResults()
    {
        $keywords = Convert::raw2sql(trim($this->getSearchQuery()));

        if (empty($keywords)) {
            return false;
        }

        $andProcessor = function ($matches) {
            return " +" . $matches[2] . " +" . $matches[4] . " ";
        };

        $notProcessor = function ($matches) {
            return " -" . $matches[3];
        };

        $keywords = preg_replace_callback('/()("[^()"]+")( and )("[^"()]+")()/i', $andProcessor, $keywords);
        $keywords = preg_replace_callback('/(^| )([^() ]+)( and )([^ ()]+)( |$)/i', $andProcessor, $keywords);
        $keywords = preg_replace_callback('/(^| )(not )("[^"()]+")/i', $notProcessor, $keywords);
        $keywords = preg_replace_callback('/(^| )(not )([^() ]+)( |$)/i', $notProcessor, $keywords);

        $keywords = $this->addStarsToKeywords($keywords);

        $start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
        $pageLength = 10;

        // Build query
        $sql = new SQLSelect();
        $sql->setDistinct(true);
        $sql->setFrom('SiteTree_Live');
        $sql->addSelect(
            "(( 2 * (MATCH (`SiteTree_Live`.`Title`) AGAINST ('{$keywords}' IN BOOLEAN MODE))) +( 0.5 * (MATCH (`SiteTree_Live`.`SearchContent`) AGAINST ('{$keywords}' IN BOOLEAN MODE))) +( 1.2 * (MATCH (`SiteTree_Live`.`Keywords`) AGAINST ('{$keywords}' IN BOOLEAN MODE))) +Weight) AS Relevance"
        );
        $sql->setWhere(
            "(MATCH (`SiteTree_Live`.`Title`,`SiteTree_Live`.`SearchContent`,`SiteTree_Live`.`Keywords`) AGAINST ('{$keywords}' IN BOOLEAN MODE))"
        );
        $sql->setOrderBy("Relevance", "DESC");
        $sql->setOrderBy(["Relevance" => "DESC", "Created" => "DESC"]);

        $this->extend('updateSearchQuery', $sql);

        // Add permission check
        $sql->addWhere(["ShowInSearch" => true]);
        $this->extend('updateSearchQuery', $sql);

        $totalCount = $sql->count();
        $sql->setLimit($pageLength, $start);
        $result = $sql->execute();
        $objects = ArrayList::create();

        // add based on permission
        foreach ($result as $row) {
            $SiteTree = SiteTree::create($row);
            $objects->add($SiteTree);
        }

        $list = new PaginatedList($objects);
        $list->setPageStart($start);
        $list->setPageLength($pageLength);
        $list->setTotalItems($totalCount);
        $list->setLimitItems(false);

        return $list;
    }

    public function getSearchQuery(): ?string
    {
        $request = $this->getRequestHandler()->getRequest();

        return $request->requestVar('query');
    }

    /**
     * @return string
     */
    private function addStarsToKeywords($keywords)
    {
        $keywords = trim($keywords);
        if (!$keywords) {
            return "";
        }

        $splitWords = explode(" ", $keywords);
        $newWords = [];

        do {
            $word = current($splitWords);
            if ($word[1] == '"') {
                while (next($splitWords) !== false) {
                    $subword = current($splitWords);
                    $word .= ' ' . $subword;
                    if (substr($subword, -1) == '"') {
                        break;
                    }
                }
            } else {
                $word .= '*';
            }
            $newWords[] = $word;
        } while (next($splitWords) !== false);

        return implode(" ", $newWords);
    }
}
