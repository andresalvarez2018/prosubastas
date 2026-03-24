<?php

/**
 * @file
 * Contains auction_item.page.inc.
 *
 * Page callback for Auction Items entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Auction Items templates.
 *
 * Default template: auction_item.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_auction_item(array &$variables) {
  // Fetch AuctionItem Entity Object.
  $auction_item = $variables['elements']['#auction_item'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
