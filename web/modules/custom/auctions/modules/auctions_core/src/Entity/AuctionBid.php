<?php

namespace Drupal\auctions_core\Entity;

use Drupal\auctions_core\AuctionToolsTrait;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Defines the Auction Bid(s) entity.
 *
 * @ingroup auctions_core
 *
 * @ContentEntityType(
 *   id = "auction_bid",
 *   label = @Translation("Auction Bids"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\auctions_core\AuctionBidListBuilder",
 *     "views_data" = "Drupal\auctions_core\Entity\AuctionBidViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\auctions_core\Form\AuctionBidForm",
 *       "add" = "Drupal\auctions_core\Form\AuctionBidForm",
 *       "edit" = "Drupal\auctions_core\Form\AuctionBidForm",
 *       "delete" = "Drupal\auctions_core\Form\AuctionBidDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\auctions_core\AuctionBidHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\auctions_core\AuctionBidAccessControlHandler",
 *   },
 *   base_table = "auction_bid",
 *   translatable = FALSE,
 *   admin_permission = "administer auction bid(s) entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "item" = "item",
 *     "amount" = "amount",
 *   },
 *   links = {
 *     "canonical" = "/admin/conten/auctions/bid/{auction_bid}",
 *     "add-form" = "/admin/content/auctions/bid/add",
 *     "edit-form" = "/admin/content/auctions/bid/{auction_bid}/edit",
 *     "delete-form" = "/admin/content/auctions/bid/{auction_bid}/delete",
 *     "collection" = "/admin/content/auctions/bid",
 *   },
 *   field_ui_base_route = "auction_bid.settings"
 * )
 */
class AuctionBid extends ContentEntityBase implements AuctionBidInterface {

  use EntityChangedTrait;
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
   * Trigger hook_auctions_core_new_bid() if standard/instant bid.
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    if ($this->isNew() && $this->getType() != 'auto') {
      \Drupal::moduleHandler()->invokeAll('auctions_core_new_bid', [&$this]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->get('id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setId($id) {
    $this->set('id', $id);
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
   * Gets the bid amount for the auction bid.
   */
  public function getAmount() {
    $amount = $this->get('amount')->value;
    return $this->showAsCents($amount);
  }

  /**
   * {@inheritdoc}
   */
  public function setAmount($float) {
    $this->set('amount', $this->roundCents($float));
    return $this;
  }

  /**
   * Get the bid type.
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
   * Get the relist group of the associated item.
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

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Bid Owned by'))
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
      ->setLabel(t('Bid is for Auction Item'))
      ->setDescription(t('The Auction Item this bid is toward.'))
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

    $fields['type'] = BaseFieldDefinition::create("list_string")
      ->setSettings([
        'allowed_values_function' => [
          '\Drupal\auctions_core\AuctionToolsBase', 'bidTypeList',
        ],
      ])
      ->setLabel('Bid Type')
      ->setRequired(TRUE)
      ->setCardinality(1)
      ->addConstraint('NotNull')
      ->setDisplayOptions('form', [
        'type' => 'auctions_core_options_select',
        'weight' => 6,
      ])
      ->setDefaultValue('standard')
      ->setDisplayConfigurable('form', TRUE);

    $fields['purchase_offer'] = BaseFieldDefinition::create("list_string")
      ->setSettings([
        'allowed_values_function' => [
          '\Drupal\auctions_core\AuctionToolsBase', 'bidPurchaseOffer',
        ],
      ])
      ->setLabel('Purchase Offer')
      ->setRequired(TRUE)
      ->setCardinality(1)
      ->setDisplayOptions('form', [
        'type' => 'auctions_core_options_select',
        'weight' => 6,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'number_decimal',
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE)
      ->setDefaultValue(0);

    $fields['amount'] = BaseFieldDefinition::create('float')
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

    return $fields;
  }

}
