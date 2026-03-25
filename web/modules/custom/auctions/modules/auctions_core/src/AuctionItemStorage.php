<?php

namespace Drupal\auctions_core;

use Drupal\auctions_core\Entity\AuctionItemInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the storage handler class for Auction Items entities.
 *
 * This extends the base storage class, adding required special handling for
 * Auction Items entities.
 *
 * @ingroup auctions_core
 */
class AuctionItemStorage extends SqlContentEntityStorage implements AuctionItemStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(AuctionItemInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {auction_item_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {auction_item_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(AuctionItemInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {auction_item_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('auction_item_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
