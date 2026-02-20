<?php

namespace Drupal\auctions_core;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Common functions.
 */
trait AuctionToolsTrait {
  use StringTranslationTrait;

  /**
   * Rounds up user cents for validation and mathematical simplicity.
   *
   * @param float $float
   *   The floating-point value to be rounded.
   *
   * @return float
   *   The rounded float value with two decimal places.
   */
  public function roundCents(float $float = 0) {
    return \round($float, 2);
  }

  /**
   * Takes a stored float and formats it as readable cents .
   *
   * @param float $float
   *   The floating-point value to be formatted.
   * @param string $dec
   *   The decimal point character.
   * @param string $thous
   *   The thousands separator character.
   *
   * @return string
   *   The formatted string representing cents.
   */
  public function showAsCents(float $float = 0, $dec = '.', $thous = '') {
    return \number_format($float, 2, $dec, $thous);
  }

  /**
   * Masks the user name by leaving only the first and last letter.
   *
   * @param string $name
   *   The user's name to be masked.
   *
   * @return string
   *   The masked user name.
   */
  public function maskUsername($name) {
    $format_name = '';
    if (\Drupal::currentUser()->hasPermission('see bidders names')) {
      $format_name = $name;
    }
    else {
      $format_name = $this->replaceUsername($name);
    }
    return $format_name;
  }

  /**
   * Handles masking a username depending on string length.
   *
   * @param string $name
   *   The user's name to be masked.
   *
   * @return string
   *   The masked user name.
   */
  public function replaceUsername($name) {
    // If less than 2 character replace all of them.
    $characters = \strlen($name);
    if ($characters <= 2) {
      return \str_repeat("*", $characters);
    }
    return \preg_replace("/(?!^).(?!$)/", "*", $name);
  }

  /**
   * Return a list bid types.
   */
  public static function bidTypeList() {
    return [
      'standard' => t("Standard Bid"),
      'instant' => t("Instant Bid"),
      'auto' => t("Autobid"),
    ];
  }

  /**
   * Return a list bid types.
   */
  public static function bidPurchaseOffer() {
    return [
      '0' => t("n/a"),
      '2' => t("Offered | Acceptance Pending"),
      '-1' => t("Rejected Offer to Purchase"),
      '3' => t("Purchesed Item"),
    ];
  }

  /**
   * Return a list of known global currencies.
   */
  public static function itemWorkflows() {
    return [
      '-1' => t("Deleted"),
      '0' => t("Not yet started"),
      '1' => t("Active"),
      '2' => t("Relisted"),
      '3' => t("Finished"),
    ];
  }

  /**
   * Return a list of known global currencies.
   */
  public function itemPriceMonitor() {
    return [
      'price_starting' => 'setPriceStarting',
      'price_buy_now' => 'setPriceBuyNow',
      'bid_step' => 'setBidStep',
    ];
  }

  /**
   * Return a list of allowed tags in email. Master control.
   */
  public function allowedTags() {
    return [
      'html',
      'head',
      'body',
      'style',
      'p',
      'a',
      'br',
      'b',
      'u',
      'em',
      'strong',
      'ul',
      'ol',
      'li',
      'dl',
      'dt',
      'dd',
      'div',
      'span',
      'header',
      'main',
      'section',
      'footer',
      'cite',
      'blockquote',
      'h1',
      'h2',
      'h3',
      'h4',
      'h5',
      'h6',
      'sup',
      'sub',
    ];
  }

  /**
   * Returns the module default timezone.
   */
  public function auctionDefaultTimeZone(): \DateTimeZone {
    return new \DateTimeZone('UTC');
  }

}
