<?php

namespace Drupal\auctions_core\Plugin\Block;

use Drupal\auctions_core\Entity\AuctionItem;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a "Auction Item Countdown Timer" block.
 *
 * @Block(
 *   id = "auction_item_countdown",
 *   admin_label = @Translation("Auction Item Countdown Timer"),
 *   category = @Translation("Auctions")
 * )
 */
class AuctionItemCountdown extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The Route Matcher.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new DropdownLanguage instance.
   *
   * @param array $block_configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route Matcher.
   */
  public function __construct(array $block_configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match) {
    parent::__construct($block_configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $block_configuration, $plugin_id, $plugin_definition) {
    return new static(
      $block_configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['font_size' => 28];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['font_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Timer font size'),
      '#default_value' => $this->configuration['font_size'],
      '#required' => TRUE,
      '#min' => 1,
      '#max' => 256,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['font_size'] = $form_state->getValue('font_size');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $auctionItem = FALSE;
    $routedItems = $this->routeMatch;
    if (!empty($this->configuration['auction_item']) && $this->configuration['auction_item'][0] instanceof AuctionItem) {
      $auctionItem = $this->configuration['auction_item'][0];
    }
    else {
      // If not set:  discover.
      foreach ($routedItems->getParameters() as $param) {
        if ($param instanceof Node) {
          if ($param->getType() == 'auction' && $param->hasField('field_auction_item') && !$param->get('field_auction_item')->isEmpty()) {
            $auctionItem = $param->field_auction_item->referencedEntities()[0];
          }
        }
        if ($param instanceof AuctionItem) {
          $auctionItem = $param;
        }
      }
    }
    if ($auctionItem) {
      $dates = $auctionItem->getDateStatus();
      $countdown = [];
      if (!$auctionItem->isClosed()) {
        $countdown['datetime'] = $dates['date_formatted'][$dates['status'] . '_time'];
        $countdown['unix'] = $dates['date_formatted'][$dates['status'] . '_unix'];
        $countdown['formatted'] = $dates['date_formatted'][$dates['status'] . '_human'];
      }
      if (!empty($countdown)) {
        $introPhrase = $this->t('Auction Starts:  @formatted.', ['@formatted' => $countdown['formatted']]);
        if ($dates['status'] == 'end') {
          $introPhrase = $this->t('Auction Ends:  @formatted.', ['@formatted' => $countdown['formatted']]);
        }
        $build['content'] = [
          '#theme' => 'auction_time',
          '#datetime' => $countdown['datetime'],
          '#formatted' => $introPhrase,
          '#unix' => $countdown['unix'],
          '#font_size' => $this->configuration['font_size'],
        ];
        // Attach library containing css and js files.
      }
      else {
        $build['content'] = [
          '#item' => 'auction_time',
          '#markup' => $this->t('This Auction is over.'),
        ];
      }
    }
    $build['#attached']['library'][] = 'auctions_core/countdown';
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
