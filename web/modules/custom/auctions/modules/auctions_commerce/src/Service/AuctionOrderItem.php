<?php

namespace Drupal\auctions_commerce\Service;

use Drupal\auctions_core\AuctionToolsTrait;
use Drupal\auctions_core\Entity\AuctionBid;
use Drupal\auctions_core\Service\AuctionTools;
use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Class ProblemLog.
 */
class AuctionOrderItem {

  use StringTranslationTrait;
  use AuctionToolsTrait;

  /**
   * Entity Type Manager.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Current Stor service.
   *
   * @var \Drupal\commerce_store\CurrentStoreInterface
   */
  protected $currentStore;

  /**
   * The Cart Provider service.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

  /**
   * The Cart Manager service.
   *
   * @var \Drupal\commerce_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * Configuration Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Configuration Factory.
   *
   * @var \Drupal\auctions_core\Service\AuctionTools
   */
  protected $auctionTools;

  /**
   * The renderer service.
   *
   * This is not injected because that would result in a circular dependency.
   * Instead, the renderer should pass itself to the ThemeManager when it is
   * constructed, using the setRenderer() method.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs the OrderItem toolkit.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity Type Manager.
   * @param \Drupal\Core\Store\CurrentStoreInterface $currentStore
   *   The current store.
   * @param \Drupal\commerce_cart\CartProviderInterface $cartProvider
   *   The cart provider.
   * @param \Drupal\commerce_cart\CartManagerInterface $cartManager
   *   The cart manager.
   * @param \Psr\Log\LoggerChannelFactoryInterface $logger
   *   The logger channel factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\auctions_core\AuctionTools $auctionTools
   *   The AuctionTools service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, CurrentStoreInterface $currentStore, CartProviderInterface $cartProvider, CartManagerInterface $cartManager, LoggerChannelFactoryInterface $logger, ConfigFactory $configFactory, AuctionTools $auctionTools, RendererInterface $renderer) {
    $this->entityTypeManager = $entityTypeManager;
    $this->currentStore = $currentStore;
    $this->cartProvider = $cartProvider;
    $this->cartManager = $cartManager;
    $this->logger = $logger;
    $this->configFactory = $configFactory;
    $this->auctionTools = $auctionTools;
    $this->renderer = $renderer;
  }

  /**
   * Adds an Order item to the Bid user and sets amount and other  fields.
   *
   * @param \Drupal\auctions_core\Entity\AuctionBid $auctionBid
   *   The AuctionBid entity.
   *
   * @return \Drupal\commerce_order\Entity\OrderItem
   *   The created OrderItem entity, with an ID if new.
   */
  public function createAuctionOrderItem(AuctionBid $auctionBid) {
    $auctionItem = $auctionBid->getItemEntity();

    $order_item = $this->entityTypeManager->getStorage('commerce_order_item')->create([
      'type' => 'auction_item',
      'title' => $this->t('Auction Item "@item" for $@amount', [
        '@amount' => $auctionBid->getAmount(),
        '@item' => ($auctionItem) ? $auctionItem->getName() : $this->t('Detected Title'),
      ]),
      'unit_price' => [
        'number' => $auctionBid->getAmount(),
        'currency_code' => $auctionItem->getCurrencyCode(),
      ],
      'field_order_bid' => ['target_id' => $auctionBid->getId()],
      'field_order_item' => ['target_id' => $auctionItem->getId()],
    ]);

    $store = $this->currentStore->getStore();
    if (!$store) {
      $this->logger->get('Auctions Commerce')->error($this->t('Commerce Store is not setup with a store'));
    }
    $account = $auctionBid->getOwner();
    // Reminder:  Always use the 'default' order type.
    $cart = $this->cartProvider->getCart('default', $store, $account);
    if (!$cart) {
      $cart = $this->cartProvider->createCart('default', $store, $account);
    }
    $orderItem = $this->cartManager->addOrderItem($cart, $order_item, FALSE);
    // @todo frees, taxes.
    // $orderItem = $this->orderItemAdjustments($orderItem);
    $orderItem->save();
    $bid = [
      '@bid' => $auctionBid->getId(),
    ];

    if ($orderItem) {
      $this->logger->get('Auctions Commerce')->notice($this->t('Auction Order Item added. BID: @bid', $bid));
      $auctionsCommerceMail = $this->configFactory->get('auctions_commerce.mail');

      // Notify bidder.
      $to = $account->getEmail();
      $subject = $auctionsCommerceMail->get('subject-bidder');
      $cartUrl = Url::fromRoute('commerce_cart.page', [], ['absolute' => TRUE]);
      $cartLink = Link::fromTextAndUrl($store->getName(), $cartUrl)->toString();
      $build = [
        '#type' => 'processed_text',
        '#text' => $auctionsCommerceMail->get('body-bidder')['value'],
        '#format' => $auctionsCommerceMail->get('body-bidder')['format'],
        '#filter_types_to_skip' => [],
        '#langcode' => $this->auctionTools->languageManager->getCurrentLanguage()->getId(),
      ];
      $formattedBody = $this->renderer->renderPlain($build);
      $tokenBody = \str_replace('@cart', $cartLink, $formattedBody);
      $body = [
        '#markup' => $tokenBody,
        '#allowed_tags' => $this->allowedTags(),
      ];
      $this->auctionTools->sendMail($to, $this->getStoreMail(), $this->getStoreMail(), $subject, $body);

      // Notify auction item owner.
      $owner = $auctionItem->getOwner();
      $subject = $auctionsCommerceMail->get('subject-owner');
      $cartUrl = Url::fromRoute('commerce_cart.page', [], ['absolute' => TRUE]);
      $cartLink = Link::fromTextAndUrl($store->getName(), $cartUrl)->toString();
      $build = [
        '#type' => 'processed_text',
        '#text' => $auctionsCommerceMail->get('body-owner')['value'],
        '#format' => $auctionsCommerceMail->get('body-owner')['format'],
        '#filter_types_to_skip' => [],
        '#langcode' => $this->auctionTools->languageManager->getCurrentLanguage()->getId(),
      ];
      $formattedBody = $this->renderer->renderPlain($build);
      $tokenBody = \str_replace('@cart', $cartLink, $formattedBody);
      $body = [
        '#markup' => $tokenBody,
        '#allowed_tags' => $this->allowedTags(),
      ];
      $this->auctionTools->sendMail($owner->getEmail(), $this->getStoreMail(), $this->getStoreMail(), $subject, $body);
    }
    else {
      $this->logger->get('Auctions Commerce')->error($this->t('Auction Order Item not added. BID: @bid', $bid));
    }
    return $orderItem;
  }

  /**
   * Apply adjustments to the given order item, such as custom fees.
   *
   * @param \Drupal\commerce_order\Entity\OrderItem $order_item
   *   The order item to which adjustments should be applied.
   *
   * @return \Drupal\commerce_order\Entity\OrderItem
   *   The updated order item entity.
   */
  private function orderItemAdjustments(OrderItem &$order_item) {
    // r&d: https://www.valuebound.com/resources/blog/how-to-manipulate-pricing-using-order-processor-commerce-2-x
    $order_item->setAdjustments([]);
    // @todo fee proportional logic.
    $fee = '20.00';
    $adjustments = $order_item->getAdjustments();
    // Apply custom adjustment.
    $adjustments[] = new Adjustment([
      'type' => 'fee',
      'label' => 'Auction Free',
      'amount' => new Price($fee, 'USD'), /* todo! get unit_price|currency_code */
      'included' => TRUE,
    ]);
    $order_item->setAdjustments($adjustments);
    return $order_item;
  }

  /**
   * Return Default Store Email address.
   */
  public function getStoreMail() {
    return $this->currentStore->getStore()->get('mail')->getString();
  }

}
