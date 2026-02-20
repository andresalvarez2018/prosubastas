<?php

namespace Drupal\auctions_core\Plugin\Validation\Constraint;

use Drupal\auctions_core\AuctionToolsTrait;
use Drupal\Core\Datetime\DrupalDateTime;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the Order Number constraint.
 */
class CurbBidsValidator extends ConstraintValidator {

  use AuctionToolsTrait;

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {

    if ($entity->hasField('amount') && $entity->hasField('item') && !$entity->get('item')->isEmpty()) {
      $item = $entity->getItemEntity();
      $itemRelist = $item->getRelistCount();
      $currentBids = $item->getBids($itemRelist, 3);
      $processBids = $item->summarizeBids($currentBids);
      $amount = floatval($entity->getAmount());
      if (!empty($processBids[0]) && $processBids[0]['uid'] == $entity->getOwnerId()) {
        $this->context
          ->buildViolation($constraint->lastBidIsYours)
          ->atPath('amount')
          ->addViolation();
        // Since this is the heaviest rule:  check for it first, thenreturn.
        return NULL;
      }

      $itemPriceStarting = $item->getPriceStarting();
      $itemWorkflow = $item->getWorkflow();
      // Check if the value is an number.
      if ($itemWorkflow == 3 || $itemWorkflow == 4) {
        $this->context
          ->buildViolation($constraint->auctionFinished)
          ->atPath('amount')
          ->addViolation();
      }
      if ($itemWorkflow == 0) {
        $this->context
          ->buildViolation($constraint->auctionNew)
          ->atPath('amount')
          ->addViolation();
      }

      // If is past end time.  @page load vs submit post.
      $now = new DrupalDateTime('now');
      $auctionDates = $item->getDate();
      $getUserTimezone = date_default_timezone_get();
      $userTimezone = new \DateTimeZone($getUserTimezone);
      $auctionEnds = DrupalDateTime::createFromFormat('Y-m-d\TH:i:s', $auctionDates['end'], $this->auctionDefaultTimeZone())->setTimezone($userTimezone)->format('U');
      if ($now->format('U') > $auctionEnds) {
        $this->context
          ->buildViolation($constraint->auctionHasExpired, ['%value' => $auctionDates['end']])
          ->atPath('amount')
          ->addViolation();
      }

      // If isn't higher than last/threshold.
      if (!empty($processBids[0]) && $amount < $processBids[0]['amount']) {
        $this->context
          ->buildViolation($constraint->higherThanLastBid, ['%value' => $thresholdDiff])
          ->atPath('amount')
          ->addViolation();
      }

    }

  }

}
