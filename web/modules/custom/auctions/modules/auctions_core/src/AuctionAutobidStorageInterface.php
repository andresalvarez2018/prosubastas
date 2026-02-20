<?php

namespace Drupal\auctions_core;

use Drupal\auctions_core\Entity\AuctionAutobidInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the storage handler class for Auction Autobid entities.
 *
 * This extends the base storage class, adding required special handling for
 * Auction Autobid entities.
 *
 * @ingroup auctions_core
 */
interface AuctionAutobidStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Auction Autobid revision IDs for a specific Auction Autobid.
   *
   * @param \Drupal\auctions_core\Entity\AuctionAutobidInterface $entity
   *   The Auction Autobid entity.
   *
   * @return int[]
   *   Auction Autobid revision IDs (in ascending order).
   */
  public function revisionIds(AuctionAutobidInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Auction Autobid author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Auction Autobid revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

}
