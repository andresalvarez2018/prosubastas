<?php

namespace Drupal\auctions_core;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Auction Items entities.
 *
 * @ingroup auctions_core
 */
class AuctionItemListBuilder extends EntityListBuilder {

  use AuctionToolsTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Item Title');
    $header['id'] = $this->t('ID');
    $header['relist'] = $this->t('Relist');
    $header['workflow'] = $this->t('Worklow');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /**  @var \Drupal\auctions_core\Entity\AuctionItem $entity */
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.auction_item.edit_form',
      ['auction_item' => $entity->id()]
    );
    $row['id'] = $entity->id();
    $row['relist'] = $entity->getRelistCount();
    $workflows = $this->itemWorkflows();
    $workflowStatus = $entity->getWorkflow();
    $row['workflow'] = $workflows[$workflowStatus];

    return $row + parent::buildRow($entity);
  }

}
