<?php

namespace Drupal\auctions_core\Entity;

use Drupal\auctions_core\AuctionToolsTrait;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Defines the Auction Autobid entity.
 *
 * @ingroup auctions_core
 *
 * @ContentEntityType(
 *   id = "auction_autobid",
 *   label = @Translation("Auction Autobid"),
 *   handlers = {
 *     "storage" = "Drupal\auctions_core\AuctionAutobidStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\auctions_core\AuctionAutobidListBuilder",
 *     "views_data" = "Drupal\auctions_core\Entity\AuctionAutobidViewsData",
 *
 *     "access" = "Drupal\auctions_core\AuctionAutobidAccessControlHandler",
 *   },
 *   base_table = "auction_autobid",
 *   revision_table = "auction_autobid_revision",
 *   revision_data_table = "auction_autobid_field_revision",
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log"
 *   },
 *   translatable = FALSE,
 *   admin_permission = "administer auction autobid entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *     "user_id" = "user",
 *     "item" = "item"
 *   },
 * )
 */
class AuctionAutobid extends EditorialContentEntityBase implements AuctionAutobidInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;
  use AuctionToolsTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);

    if ($rel === 'revision_revert' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }
    elseif ($rel === 'revision_delete' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }

    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // If no revision author has been set explicitly,
    // make the auction_autobid owner the revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * Get the maximum amount.
   *
   * @return float
   *   The maximum amount, converted to cents.
   */
  public function getAmountMax() {
    $amount = $this->get('amount_max')->value;
    return $this->showAsCents($amount);
  }

  /**
   * {@inheritdoc}
   */
  public function setAmountMax($float) {
    $this->set('amount_max', $this->roundCents($float));
    return $this;
  }

  /**
   * Get the type of the auction.
   *
   * @return int
   *   The type of the auction.
   */
  public function getType() {
    $type = $this->get('type')->value;
    return $type;
  }

  /**
   * {@inheritdoc}
   */
  public function setType($int) {
    $this->set('type', $int);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemEntity() {
    return $this->get('item')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemId() {
    return $this->get('item')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemRelistCount() {
    $item = $this->getItemEntity();
    $relistCount = $item->getRelistCount();
    return $relistCount;
  }

  /**
   * Get the relist group.
   *
   * @return int
   *   The relist group of the auction.
   */
  public function getRelistGroup() {
    $relistGroup = $this->get('relist_group')->value;
    return $relistGroup;
  }

  /**
   * {@inheritdoc}
   */
  public function setRelistGroup($int) {
    $this->set('relist_group', $int);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Add the published field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Auction Autobid entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Auction Autobid entity.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['amount_max'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Bid Amount'))
      ->setDisplayOptions('form', [
        'weight' => '10',
        'type' => 'number',
        'settings' => [
          'display_label' => TRUE,
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'number_decimal',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->addConstraint('AuctionsPrice')
      ->addConstraint('NotNull')
      ->setRequired(TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Bidder'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['item'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Auction Item'))
      ->setDescription(t('Autobid is applied against Auction Item'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'auction_item')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE)
      ->setCardinality(1);

    $fields['relist_group'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Relist Group'))
      ->setSetting('unsigned', TRUE)
      ->setDefaultValue(0)
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    $fields['status']->setDescription(t('Opt in out switch (published)'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
