<?php

namespace Drupal\auctions_core\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks number input elements values for simple money-like signatures.
 *
 * @Constraint(
 *   id = "AuctionsPrice",
 *   label = @Translation("Auctions Price validate", context = "Validation"),
 *   type = "string"
 * )
 */
class AuctionsPrice extends Constraint {

  /**
   * The message that will be shown if the value is not an integer.
   *
   * @var string
   */
  public $notNumeric = '%value is not a number';

  /**
   * The message that will be shown if the value is not negative.
   *
   * @var string
   */
  public $notNegative = '%value is below zero (0)';

}
