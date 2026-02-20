<?php

namespace Drupal\auctions_core\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Auction Bid(s) entities.
 */
class AuctionBidViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.
    return $data;
  }

}
