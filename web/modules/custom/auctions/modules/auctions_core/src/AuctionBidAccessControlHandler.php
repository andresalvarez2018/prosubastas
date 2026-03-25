<?php

namespace Drupal\auctions_core;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the Auction Bid(s) entity.
 *
 * @see \Drupal\auctions_core\Entity\AuctionBid.
 */
class AuctionBidAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\auctions_core\Entity\AuctionBidInterface $entity */

    switch ($operation) {

      case 'view':

        return AccessResult::allowedIfHasPermission($account, 'view published auction bids entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit auction bids entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete auction bids entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add auction bids entities');
  }

}
