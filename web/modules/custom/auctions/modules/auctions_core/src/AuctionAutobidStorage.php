<?php

namespace Drupal\auctions_core;

use Drupal\auctions_core\Entity\AuctionAutobidInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the storage handler class for Auction Autobid entities.
 *
 * This extends the base storage class, adding required special handling for
 * Auction Autobid entities.
 *
 * @ingroup auctions_core
 */
class AuctionAutobidStorage extends SqlContentEntityStorage implements AuctionAutobidStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(AuctionAutobidInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {auction_autobid_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {auction_autobid_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

}
