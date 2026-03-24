<?php

namespace Drupal\auctions_core\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Auction Items entities.
 *
 * @ingroup auctions_core
 */
interface AuctionItemInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Auction Items name.
   *
   * @return string
   *   Name of the Auction Items.
   */
  public function getName();

  /**
   * Sets the Auction Items name.
   *
   * @param string $name
   *   The Auction Items name.
   *
   * @return \Drupal\auctions_core\Entity\AuctionItemInterface
   *   The called Auction Items entity.
   */
  public function setName($name);

  /**
   * Gets the Auction Items creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Auction Items.
   */
  public function getCreatedTime();

  /**
   * Sets the Auction Items creation timestamp.
   *
   * @param int $timestamp
   *   The Auction Items creation timestamp.
   *
   * @return \Drupal\auctions_core\Entity\AuctionItemInterface
   *   The called Auction Items entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the Auction Items revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Auction Items revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\auctions_core\Entity\AuctionItemInterface
   *   The called Auction Items entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Auction Items revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Auction Items revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\auctions_core\Entity\AuctionItemInterface
   *   The called Auction Items entity.
   */
  public function setRevisionUserId($uid);

  /**
   * {@inheritdoc}
   */
  public function getWorkflow();

  /**
   * {@inheritdoc}
   */
  public function setWorkflow($unsigned);

  /**
   * {@inheritdoc}
   */
  public function getCurrencyCode();

  /**
   * {@inheritdoc}
   */
  public function setCurrencyCode($value);

  /**
   * {@inheritdoc}
   */
  public function getPriceStarting();

  /**
   * {@inheritdoc}
   */
  public function setPriceStarting($float);

  /**
   * {@inheritdoc}
   */
  public function getBidStep();

  /**
   * {@inheritdoc}
   */
  public function setBidStep($float);

  /**
   * {@inheritdoc}
   */
  public function getPriceBuyNow();

  /**
   * {@inheritdoc}
   */
  public function setPriceBuyNow($float);

  /**
   * {@inheritdoc}
   */
  public function getDateStatus();

  /**
   * {@inheritdoc}
   */
  public function seekBidThreshold();

  /**
   * {@inheritdoc}
   */
  public function getBids();

  /**
   * {@inheritdoc}
   */
  public function setBids(array $bids);

}
