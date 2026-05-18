<?php

declare(strict_types=1);

namespace Drupal\auctions_core\Controller;

use Drupal\auctions_core\Entity\AuctionItem;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for active sale heartbeat and auto-close.
 */
class AuctionActiveSaleController extends ControllerBase {

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * Constructs a new AuctionActiveSaleController.
   *
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator service.
   */
  public function __construct(CacheTagsInvalidatorInterface $cache_tags_invalidator) {
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cache_tags.invalidator')
    );
  }

  /**
   * Heartbeat endpoint for active sale timer.
   *
   * Returns the current active_end, workflow and remaining time. If the item
   * is in active workflow and time_left has reached zero, it auto-closes the
   * auction by setting workflow to 3.
   *
   * @param \Drupal\auctions_core\Entity\AuctionItem $auction_item
   *   The auction item (upcast from route parameter).
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with active_end, workflow and time_left.
   */
  public function heartbeat(AuctionItem $auction_item, Request $request): JsonResponse {
    $active_end = $auction_item->getActiveEnd();
    $workflow = $auction_item->getWorkflow();
    $now = \time();
    $time_left = \max(0, $active_end - $now);

    // Auto-close if active sale timer has expired and active_end was initialized.
    if ($workflow == 1 && $active_end > 0 && $time_left == 0) {
      $auction_item->setWorkflow(3);
      $auction_item->save();
      $workflow = 3;
      $this->cacheTagsInvalidator->invalidateTags(['auction_item:' . $auction_item->id()]);
    }

    $data = [
      'active_end' => $active_end,
      'workflow' => $workflow,
      'time_left' => $time_left,
    ];

    return new JsonResponse($data);
  }

}
