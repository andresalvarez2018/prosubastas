<?php

namespace Drupal\auctions_core\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;

/**
 * Item Related Validation.
 *
 * @Constraint(
 *   id = "CurbItems",
 *   label = @Translation("Curbs item submissions.", context = "Validation"),
 *   type = "entity:auction_item"
 * )
 */
class CurbItems extends CompositeConstraintBase {

  /**
   * The message for when the Buy Now amount is lower than the Starting Price.
   *
   * @var string
   */
  public $buyNowLowerThanStartingPrice = 'Buy Now amount is lower than Starting Price.';

  /**
   * The message for when Auction Item is set as Instant Only without Buy Now.
   *
   * @var string
   */
  public $instantOnlyWithoutBuyNowPrice = 'Auction Item is Set as Instant Only without Buy Now Price';

  /**
   * The message for when the Starting Price is zero.
   *
   * @var string
   */
  public $startingPriceIsZero = 'Starting Price cannot be zero.';

  /**
   * {@inheritdoc}
   */
  public function coversFields() {
    return ['price_buy_now', 'price_starting', 'instant_only'];
  }

}
