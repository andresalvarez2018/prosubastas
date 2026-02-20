<?php

namespace Drupal\auctions_core;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Auction Autobid entities.
 *
 * @ingroup auctions_core
 */
class AuctionAutobidListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Auction Autobid ID');
    $header['link'] = $this->t('Link');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /**  @var \Drupal\auctions_core\Entity\AuctionAutobid $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->id(),
      'entity.auction_autobid.edit_form',
      ['auction_autobid' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
