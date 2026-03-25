<?php

namespace Drupal\auctions_core\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\UserInterface;

/**
 * Provides an interface for defining Auction Bid(s) entities.
 *
 * @ingroup auctions_core
 */
interface AuctionBidInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * {@inheritdoc}
   */
  public function getId();

  /**
   * {@inheritdoc}
   */
  public function setId($id);

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime();

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp);

  /**
   * {@inheritdoc}
   */
  public function getOwner();

  /**
   * {@inheritdoc}
   */
  public function getOwnerId();

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account);

  /**
   * Get the bid amount.
   */
  public function getAmount();

  /**
   * {@inheritdoc}
   */
  public function setAmount($float);

  /**
   * Get the bid type.
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
   * Get the relist group of the associated item.
   */
  public function getRelistGroup();

  /**
   * {@inheritdoc}
   */
  public function setRelistGroup($int);

}
