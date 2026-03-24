<?php

namespace Drupal\auctions_core;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Auction Bid(s) entities.
 *
 * @ingroup auctions_core
 */
class AuctionBidListBuilder extends EntityListBuilder {

  use AuctionToolsTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Bid ID');
    $header['bidder'] = $this->t('Bidder');
    $header['item'] = $this->t('Auction Item | Relist | ID');
    $header['relist'] = $this->t('Bid Relist');
    $header['amount'] = $this->t('Amount');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /**  @var \Drupal\auctions_core\Entity\AuctionBid $entity */

    $row['name'] = Link::createFromRoute($entity->id(), 'entity.auction_bid.edit_form',
      ['auction_bid' => $entity->id()]
    );
    $ownerName = $entity->getOwner()?->getAccountName() ?? $this->t('No Owner')->render();
    $username = $this->maskUsername($ownerName);
    $row['bidder'] = $username;
    $item = $entity->getItemEntity();
    // Detected:  deleted auction item. Probably from inform form deletion.
    $row['item'] = ($item) ? ($item->getName() . ' | ' . $item->getRelistCount()) . ' | ' . $entity->getItemId() : $this->t('⇒ Item Detached | • | @id', ['@id' => $entity->getItemId()]);
    $row['relist'] = $entity->getRelistGroup();
    $row['amount'] = $entity->getAmount();

    return $row + parent::buildRow($entity);
  }

}
