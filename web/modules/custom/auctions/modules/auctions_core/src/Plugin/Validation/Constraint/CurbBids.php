<?php

namespace Drupal\auctions_core\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;

/**
 * Bid Related Validation.
 *
 * @Constraint(
 *   id = "CurbBids",
 *   label = @Translation("Curb Bids", context = "Validation"),
 *   type = "entity:auction_bid"
 * )
 */
class CurbBids extends CompositeConstraintBase {

  /**
   * The message for when the auction has been closed.
   *
   * @var string
   */
  public $auctionFinished = "This auction has been closed!";

  /**
   * The message for when the auction is not yet open.
   *
   * @var string
   */
  public $auctionNew = "This auction is not yet open!";

  /**
   * The message for when the last bid is by the same user.
   *
   * @var string
   */
  public $lastBidIsYours = "You are the last bidder. Self outbidding is not allowed.";

  /**
   * The message for when the auction has closed.
   *
   * @var string
   */
  public $auctionHasClosed = "Auction has closed!";

  /**
   * The message for when the auction has expired.
   *
   * @var string
   */
  public $auctionHasExpired = "Auction has expired! %value";

  /**
   * The message for when the bid is not high enough.
   *
   * @var string
   */
  public $higherThanLastBid = "This bid is not high enough…";

  /**
   * The message for when the amount is higher than the allowed bid threshold.
   *
   * @var string
   */
  public $higherThanThreshold = "Amount is higher than the allowed bid threshold. %value";

  /**
   * Defines the fields to validate against.
   *
   * @return string[]
   *   An array of field names to validate.
   */
  public function coversFields() {
    return ["amount", "item"];
  }

}
