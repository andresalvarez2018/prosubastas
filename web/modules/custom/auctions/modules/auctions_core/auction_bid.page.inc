<?php

/**
 * @file
 * Contains auction_bid.page.inc.
 *
 * Page callback for Auction Bid(s) entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Auction Bid(s) templates.
 *
 * Default template: auction_bid.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_auction_bid(array &$variables) {
  // Fetch AuctionBid Entity Object.
  $auction_bid = $variables['elements']['#auction_bid'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
