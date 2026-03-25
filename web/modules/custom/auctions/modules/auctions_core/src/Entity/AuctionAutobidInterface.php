<?php

namespace Drupal\auctions_core\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Auction Autobid entities.
 *
 * @ingroup auctions_core
 */
interface AuctionAutobidInterface extends ContentEntityInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Auction Autobid creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Auction Autobid.
   */
  public function getCreatedTime();

  /**
   * Sets the Auction Autobid creation timestamp.
   *
   * @param int $timestamp
   *   The Auction Autobid creation timestamp.
   *
   * @return \Drupal\auctions_core\Entity\AuctionAutobidInterface
   *   The called Auction Autobid entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Sets the Auction Autobid revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\auctions_core\Entity\AuctionAutobidInterface
   *   The called Auction Autobid entity.
   */
  public function setRevisionUserId($uid);

  /**
   * Gets the maximum bid amount for the auction autobid.
   */
  public function getAmountMax();

  /**
   * {@inheritdoc}
   */
  public function setAmountMax($float);

  /**
   * Gets the type of the autobid.
   */
  public function getType();

  /**
   * {@inheritdoc}
   */
  public function setType($int);

  /**
   * {@inheritdoc}
   */
  public function getItemEntity();

  /**
   * {@inheritdoc}
   */
  public function getItemId();

  /**
   * {@inheritdoc}
   */
  public function getItemRelistCount();

  /**
   * Gets the relist group for the autobid.
   */
  public function getRelistGroup();

  /**
   * {@inheritdoc}
   */
  public function setRelistGroup($int);

}
