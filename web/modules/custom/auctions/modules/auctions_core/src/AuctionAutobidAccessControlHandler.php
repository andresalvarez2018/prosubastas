<?php

namespace Drupal\auctions_core;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the Auction Autobid entity.
 *
 * @see \Drupal\auctions_core\Entity\AuctionAutobid.
 */
class AuctionAutobidAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\auctions_core\Entity\AuctionAutobidInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished auction autobid entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published auction autobid entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit auction autobid entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete auction autobid entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add auction autobid entities');
  }

}
