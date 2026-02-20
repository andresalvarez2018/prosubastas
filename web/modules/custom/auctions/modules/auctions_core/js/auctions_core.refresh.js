(function ($, Drupal) {
    'use strict';

    /**
     * Behavior to refresh the bidding form periodically.
     */
    Drupal.behaviors.auctionsCoreRefresh = {
        attach: function (context, settings) {
            // Find the specific wrapper.
            var $wrapper = $('#auctions-core-bidders-wrapper', context);

            if ($wrapper.length && once('auctions-core-refresh-init', 'body', context).length) {
                // Set an interval to refresh the form.
                // Default to 15 seconds for relatively real-time feeling.
                var refreshInterval = 5000;

                setInterval(function () {
                    var $btn = $('.auctions-core-refresh-btn');
                    if ($btn.length) {
                        // Trigger the AJAX refresh.
                        $btn.trigger('mousedown');
                    }
                }, refreshInterval);
            }
        }
    };
})(jQuery, Drupal);
