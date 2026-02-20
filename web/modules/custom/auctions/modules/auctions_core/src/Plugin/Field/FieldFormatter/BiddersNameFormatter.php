<?php

namespace Drupal\auctions_core\Plugin\Field\FieldFormatter;

use Drupal\auctions_core\AuctionToolsTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;

/**
 * Plugin implementation of the 'bidders name' formatter.
 *
 * @FieldFormatter(
 *   id = "bidders_name",
 *   label = @Translation("Auctions Core Bidders Name"),
 *   description = @Translation("Display the referenced author user entity."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class BiddersNameFormatter extends EntityReferenceFormatterBase {

  use AuctionToolsTrait;

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      /** @var \Drupal\user\UserInterface $referenced_user */
      $username = $entity->getAccountName();
      $displayName = $this->maskUsername($username);
      $elements[$delta] = [
        '#markup' => $displayName,
        '#cache' => [
          'max-age' => 0,
        ],
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getFieldStorageDefinition()->getSetting('target_type') == 'user';
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity) {
    return $entity->access('view label', NULL, TRUE);
  }

}
