/**
 * Copyright © 2021 Neosoft Technologies. All rights reserved.
 */

/**
 * Documentation: https://community.algolia.com/magento/doc/m2/frontend-events/
 **/

/**
 * Autocomplete hook method
 * autocomplete.js documentation: https://github.com/algolia/autocomplete.js
 **/
requirejs([
    'algoliaBundle',
    'jquery'
], function (algoliaBundle, $) {
    algoliaBundle.$(function ($) {
        algolia.registerHook('beforeAutocompleteSources', function (sources, algoliaClient) {
            // Initialize the newly created index
            var store_locator_index = algoliaClient.initIndex(algoliaConfig.indexName + "_store_locators");
            const customIndexOptions = {
                hitsPerPage: 4
            };

            // id_of_your_template should be the value of the ID attribute in the <script> tag of your template
            const customTemplate = $('#autocomplete_store_locator_template').html();

            // Append the new source to the sources array
            sources.push({
                source: algoliaBundle.autocomplete.sources.hits(store_locator_index, customIndexOptions),
                templates: {
                    suggestion(hit) {
                        return algoliaBundle.Hogan.compile(customTemplate).render(hit);
                    }
                }
            });

            return sources;
        });

        algolia.registerHook('beforeAutocompleteOptions', function (options) {
            console.log('In hook method to modify autocomplete options');

            // Modify autocomplete options

            return options;
        });

        /**
         * InstantSearch hook methods
         * IS.js documentation: https://community.algolia.com/instantsearch.js/v2/getting-started.html
         **/

        algolia.registerHook('beforeInstantsearchInit', function (instantsearchOptions) {
            console.log('In method to modify instantsearch options');

            // Modify instant search options

            return instantsearchOptions;
        });

        algolia.registerHook('beforeWidgetInitialization', function (allWidgetConfiguration) {
            console.log('In hook method to modify instant search widgets');

            // Modify instant search widgets

            return allWidgetConfiguration;
        });

        algolia.registerHook('beforeInstantsearchStart', function (search) {
            console.log('In hook method to modify instant search instance before search started');

            // Modify instant search instance before search started

            return search;
        });

        algolia.registerHook('afterInstantsearchStart', function (search) {
            console.log('In hook method to modify instant search instance after search started');

            // Modify instant search instance after search started

            return search;
        });
    });
});
