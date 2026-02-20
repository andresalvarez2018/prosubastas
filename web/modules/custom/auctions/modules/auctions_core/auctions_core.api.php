<?php

/**
 * @file
 * Provides information about fee ranges for the auction module.
 */

/**
 * Tell the auction module about your fee ranges.
 *
 * @return array
 *   An array of fee ranges.
 */
function hook_auctions_fee_ranges() {
  return [
    [
      'from' => 0,
      'sell_price_fee' => 0.03,
      'single_auction_fee' => 15,
    ],
    [
      'from' => 10000,
      'sell_price_fee' => 0.025,
      'single_auction_fee' => 50,
    ],
    [
      'from' => 100000,
      'sell_price_fee' => 0.02,
      'single_auction_fee' => 100,
    ],
  ];
}

/**
 * Alter existing fee ranges.
 *
 * @param array &$ranges
 *   An array of fee ranges to be altered.
 */
function hook_auctions_fee_ranges_alter(array &$ranges) {
  $ranges[1]['from'] = 15000;
}
