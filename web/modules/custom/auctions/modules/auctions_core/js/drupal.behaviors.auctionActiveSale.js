(function ($, Drupal, once) {
  'use strict';

  /**
   * Format remaining seconds as MM:SS.
   *
   * @param {number} seconds
   *   Remaining seconds.
   *
   * @return {string}
   *   Formatted string.
   */
  function formatTime(seconds) {
    var m = Math.floor(seconds / 60);
    var s = seconds % 60;
    return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
  }

  /**
   * Update the timer display and check for expiry.
   *
   * @param {jQuery} $wrapper
   *   The wrapper element.
   * @param {Object} timer
   *   The timer state object.
   */
  function updateWrapper($wrapper, timer) {
    var now = Math.floor(Date.now() / 1000);
    var timeLeft = Math.max(0, timer.activeEnd - now);

    // Check drupalSettings for a newer active_end (e.g. after AJAX refresh).
    var ds = drupalSettings.auctions_core && drupalSettings.auctions_core.active_sale
      ? drupalSettings.auctions_core.active_sale[timer.itemId]
      : null;
    if (ds && ds.active_end && ds.active_end > timer.activeEnd) {
      timer.activeEnd = ds.active_end;
      $wrapper.attr('data-active-end', timer.activeEnd);
      timeLeft = Math.max(0, timer.activeEnd - now);
    }

    if (timeLeft <= 0) {
      clearInterval(timer.intervalId);
      timer.intervalId = null;
      timer.$display.text(Drupal.t('Finalizando...'));
      doHeartbeat($wrapper, timer);
      return;
    }

    timer.$display.text(
      Drupal.t('La subasta finaliza en @time si no hay nuevas pujas:', {
        '@time': formatTime(timeLeft)
      })
    );
  }

  /**
   * Call the heartbeat endpoint when timer reaches zero.
   *
   * @param {jQuery} $wrapper
   *   The wrapper element.
   * @param {Object} timer
   *   The timer state object.
   */
  function doHeartbeat($wrapper, timer) {
    var itemId = timer.itemId;
    fetch(Drupal.url('auction/item/' + itemId + '/heartbeat'), {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
      .then(function (response) {
        if (!response.ok) {
          throw new Error('Network response was not ok.');
        }
        return response.json();
      })
      .then(function (data) {
        if (data.time_left > 0) {
          timer.activeEnd = data.active_end;
          $wrapper.attr('data-active-end', data.active_end);
          if (drupalSettings.auctions_core && drupalSettings.auctions_core.active_sale && drupalSettings.auctions_core.active_sale[itemId]) {
            drupalSettings.auctions_core.active_sale[itemId].active_end = data.active_end;
          }
          timer.intervalId = setInterval(function () {
            updateWrapper($wrapper, timer);
          }, 1000);
          updateWrapper($wrapper, timer);
          return;
        }
        if (data.workflow == 3 || data.time_left == 0) {
          showEnded($wrapper);
        }
      })
      .catch(function () {
        showEnded($wrapper);
      });
  }

  /**
   * Disable bidding and show ended message.
   *
   * @param {jQuery} $wrapper
   *   The wrapper element.
   */
  function showEnded($wrapper) {
    var $formWrapper = $('#auctions-core-bidders-wrapper');
    if ($formWrapper.length) {
      $formWrapper.addClass('auction-ended');
      $formWrapper.find('input, button, select, textarea').prop('disabled', true);
    }
    $wrapper.find('.auction-active-sale-countdown').text(Drupal.t('Subasta finalizada'));
    $wrapper.find('.auction-countdown').hide();
  }

  /**
   * Behavior to manage the active sale countdown and heartbeat.
   */
  Drupal.behaviors.auctionActiveSale = {
    attach: function (context, settings) {
      $('.auction-active-sale-wrapper', context).each(function () {
        var wrapper = this;
        if (!once('auction-active-sale', wrapper).length) {
          return;
        }

        var $wrapper = $(wrapper);
        var itemId = $wrapper.data('auction-item-id');
        var activeEnd = parseInt($wrapper.attr('data-active-end'), 10) || 0;
        if (!activeEnd || !itemId) {
          return;
        }

        var timer = {
          itemId: itemId,
          activeEnd: activeEnd,
          intervalId: null,
          $display: $('<div class="auction-active-sale-countdown"></div>')
        };

        $wrapper.prepend(timer.$display);

        timer.intervalId = setInterval(function () {
          updateWrapper($wrapper, timer);
        }, 1000);
        updateWrapper($wrapper, timer);

        // Listen for global AJAX completions to reset immediately if another
        // bid extended the timer.
        $(document).on('ajaxComplete.auctionActiveSale', function (e, xhr, ajaxSettings) {
          var ds = drupalSettings.auctions_core && drupalSettings.auctions_core.active_sale
            ? drupalSettings.auctions_core.active_sale[itemId]
            : null;
          if (ds && ds.active_end && ds.active_end > timer.activeEnd) {
            timer.activeEnd = ds.active_end;
            $wrapper.attr('data-active-end', timer.activeEnd);
            updateWrapper($wrapper, timer);
          }
        });
      });
    }
  };

})(jQuery, Drupal, once);
