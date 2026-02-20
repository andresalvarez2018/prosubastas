(function($, Drupal) {
  'use strict';
  /**
   * Attaches the JS countdown behavior
   */
  Drupal.behaviors.auctionCountdown = {
    attach: function(context) {
      $(once('auction-countdown-active', '.auction-countdown', context)).each(function() {
        var countdown = this;
        $(countdown).find('.countdown').auctionCountdown({
          timestamp: $(this).data('unix') * 1000,
          font_size: $(this).data('font-size'),
          callback: function(weeks, days, hours, minutes, seconds) {
            var dateStrings = new Array();
            var weekStr = Drupal.formatPlural(weeks, '1 week', '@count weeks');
            dateStrings['@weeks'] = weekStr;
            var daysStr = Drupal.formatPlural(days, '1 day', '@count days');
            dateStrings['@days'] = daysStr;
            var hoursStr = Drupal.formatPlural(hours, '1 hour', '@count hours');
            dateStrings['@hours'] = hoursStr;
            var minutesStr = Drupal.formatPlural(minutes, '1 minute', '@count minutes');
            dateStrings['@minutes'] = minutesStr;
            var secondsStr = Drupal.formatPlural(seconds, '1 second', '@count seconds');
            dateStrings['@seconds'] = secondsStr;

            // Countdown: if unit is zero and fade it, keep visual blocking.
            if (weeks === 0) {
              $(countdown).find('.countWeeks, .countDivWeeks').animate({
                opacity: '10%'
              }, 1500);
              $(countdown).find('.countWeeks').attr({
                'title': ''
              });
            } else {
              $(countdown).find('.countWeeks').attr({
                'title': weekStr
              });
            }
            if (days === 0) {
              $(countdown).find('.countDays, .countDivDays').animate({
                opacity: '10%'
              }, 1500);
              $(countdown).find('.countWeeks').attr({
                'title': ''
              });
           } else {
              $(countdown).find('.countDays').attr({
                'title': daysStr
              });
            }
            if (hours === 0 && days === 0 && weeks === 0) {
              $(countdown).find('.countHrs, .countDivHrs').animate({
                opacity: '10%'
              }, 1500);
              $(countdown).find('.countWeeks').attr({
                'title': ''
              });
            } else {
              $(countdown).find('.countHrs').attr({
                'title': hoursStr
              });
            }
            if (minutes === 0 && hours === 0 && days === 0 && weeks === 0) {
              $(countdown).find('.countMins, .countDivMins').animate({
                opacity: '10%'
              }, 1500);
              $(countdown).find('.countWeeks').attr({
                'title': ''
              });
            } else {
              $(countdown).find('.countMins').attr({
                'title': minutesStr
              });
            }
            if (seconds === 0 && minutes === 0 && hours === 0 && days === 0 && weeks === 0) {
              $(countdown).find('.countSecs, .countDivSecs').animate({
                opacity: '10%'
              }, 1500);
              $(countdown).find('.countWeeks').attr({
                'title': ''
              });
            } else {
              $(countdown).find('.countSecs').attr({
                'title': secondsStr
              });
            }

            // Handle interval phrasing.
            if ($(countdown).find('.interval').length > 0) {
              var messageParts = [];
              var message = '';
              if (weeks > 0) {
                messageParts.push(dateStrings['@weeks']);
              }
              if (days > 0) {
                messageParts.push(dateStrings['@days']);
              }
              if (hours > 0) {
                messageParts.push(dateStrings['@hours']);
              }
              if (minutes > 0) {
                messageParts.push(dateStrings['@minutes']);
              }
              if (seconds > 0) {
                messageParts.push(dateStrings['@seconds']);
              }
              if (messageParts.length > 0) {
                var message = messageParts.join(', ');
                if (messageParts.length > 1) {
                  // Add "and" before the last item if there are more than one item.
                  var lastCommaIndex = message.lastIndexOf(', ');
                  message = message.substring(0, lastCommaIndex) + ' ' + Drupal.t('and') + ' ' + message.substring(lastCommaIndex + 2);
                }
                message += ' ' + Drupal.t('left');
              }
              $(countdown).find('.interval').html(message);
            }
          }

        });
      });
    }
  };
})(jQuery, Drupal);
