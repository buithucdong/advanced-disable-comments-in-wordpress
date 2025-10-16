jQuery(document).ready(function($) {
    'use strict';
    
    var everywhereCheckbox = $('#everywhere');
    var otherCheckboxes = $('#post, #page, #product, #xml_rpc, #rest_api');
    
    /**
     * Update checkbox states based on "Everywhere" checkbox
     */
    function updateCheckboxStates() {
        if (everywhereCheckbox.is(':checked')) {
            otherCheckboxes.prop('checked', true).prop('disabled', true);
        } else {
            otherCheckboxes.prop('disabled', false);
        }
    }
    
    // Listen for changes
    everywhereCheckbox.on('change', updateCheckboxStates);
    
    // Initialize on page load
    updateCheckboxStates();
});