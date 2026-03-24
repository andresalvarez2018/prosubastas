<?php

namespace Drupal\auctions_core;

use Drupal\auctions_core\Entity\AuctionItemInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
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
interface AuctionItemStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Auction Items revision IDs for a specific Auction Items.
   *
   * @param \Drupal\auctions_core\Entity\AuctionItemInterface $entity
   *   The Auction Items entity.
   *
   * @return int[]
   *   Auction Items revision IDs (in ascending order).
   */
  public function revisionIds(AuctionItemInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Auction Items author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Auction Items revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\auctions_core\Entity\AuctionItemInterface $entity
   *   The Auction Items entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(AuctionItemInterface $entity);

  /**
   * Unsets the language for all Auction Items with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
