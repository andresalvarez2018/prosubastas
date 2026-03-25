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
  public $auctionFinished = "¡Esta subasta ha sido cerrada!";

  /**
   * The message for when the auction is not yet open.
   *
   * @var string
   */
  public $auctionNew = "¡Esta subasta aún no está abierta!";

  /**
   * The message for when the last bid is by the same user.
   *
   * @var string
   */
  public $lastBidIsYours = "Usted es el último postor. No se permite superarse a sí mismo.";

  /**
   * The message for when the auction has closed.
   *
   * @var string
   */
  public $auctionHasClosed = "¡La subasta ha cerrado!";

  /**
   * The message for when the auction has expired.
   *
   * @var string
   */
  public $auctionHasExpired = "¡La subasta ha expirado! %value";

  /**
   * The message for when the bid is not high enough.
   *
   * @var string
   */
  public $higherThanLastBid = "Esta puja no es lo suficientemente alta...";

  /**
   * The message for when the amount is higher than the allowed bid threshold.
   *
   * @var string
   */
  public $higherThanThreshold = "El monto es superior al umbral de puja permitido. %value";


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
