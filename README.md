# SilverStripe Elemental Site Search Module
Basic site search for the SilverStripe Elemental module. It works by saving a text reprentation of the elemental area to an extra field in SiteTree.


## Requires

* [SilverStripe](https://www.silverstripe.org/)
* [SilverStripe Elemental](https://github.com/dnadesign/silverstripe-elemental)


## Usage

### Install this module with composer

    composer require jbennecker/silverstripe-elemental-site-search
    
Make sure you don't have `FulltextSearchable` enabled in `_config.php`.

### Add the search form

To add the search form, add `$SearchForm` anywhere in your template. 

For example in Header.ss

    ...
    <div class="search-form">
        $SearchForm
    </div>      
    ...

### Override the template 

Lastly you can overrde the template for the result page. 

app/templates/Layout/Page_results.ss

    <div id="Content" class="searchResults">
        <h1>$Title</h1>
    
        <% if $Query %>
            <p class="searchQuery"><strong>You searched for &quot;{$Query}&quot;</strong></p>
        <% end_if %>
    
        <% if $Results %>
        <ul id="SearchResults">
            <% loop $Results %>
            <li>
                <a class="searchResultHeader" href="$Link">
                    <% if $MenuTitle %>
                    $MenuTitle
                    <% else %>
                    $Title
                    <% end_if %>
                </a>
                <p>$Content.LimitWordCountXML</p>
                <a class="readMoreLink" href="$Link" 
                    title="Read more about &quot;{$Title}&quot;"
                    >Read more about &quot;{$Title}&quot;...</a>
            </li>
            <% end_loop %>
        </ul>
        <% else %>
        <p>Sorry, your search query did not return any results.</p>
        <% end_if %>
    
        <% if $Results.MoreThanOnePage %>
        <div id="PageNumbers">
            <% if $Results.NotLastPage %>
            <a class="next" href="$Results.NextLink" title="View the next page">Next</a>
            <% end_if %>
            <% if $Results.NotFirstPage %>
            <a class="prev" href="$Results.PrevLink" title="View the previous page">Prev</a>
            <% end_if %>
            <span>
                <% loop $Results.Pages %>
                    <% if $CurrentBool %>
                    $PageNum
                    <% else %>
                    <a href="$Link" title="View page number $PageNum">$PageNum</a>
                    <% end_if %>
                <% end_loop %>
            </span>
            <p>Page $Results.CurrentPage of $Results.TotalPages</p>
        </div>
        <% end_if %>
    </div>

### Clear caches

Then finally add ?flush=1 to the URL and you should see the new template.
