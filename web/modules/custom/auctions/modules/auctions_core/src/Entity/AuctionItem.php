<?php

namespace Drupal\auctions_core\Entity;

use Drupal\auctions_core\AuctionToolsTrait;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Auction Items entity.
 *
 * @ingroup auctions_core
 *
 * @ContentEntityType(
 *   id = "auction_item",
 *   label = @Translation("Auction Items"),
 *   handlers = {
 *     "storage" = "Drupal\auctions_core\AuctionItemStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\auctions_core\AuctionItemListBuilder",
 *     "views_data" = "Drupal\auctions_core\Entity\AuctionItemViewsData",
 *     "translation" = "Drupal\auctions_core\AuctionItemTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\auctions_core\Form\AuctionItemForm",
 *       "add" = "Drupal\auctions_core\Form\AuctionItemForm",
 *       "edit" = "Drupal\auctions_core\Form\AuctionItemForm",
 *       "relist" = "Drupal\auctions_core\Form\AuctionItemRelistForm",
 *       "delete" = "Drupal\auctions_core\Form\AuctionItemDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\auctions_core\AuctionItemHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\auctions_core\AuctionItemAccessControlHandler",
 *   },
 *   base_table = "auction_item",
 *   data_table = "auction_item_field_data",
 *   revision_table = "auction_item_revision",
 *   revision_data_table = "auction_item_field_revision",
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log"
 *   },
 *   translatable = TRUE,
 *   admin_permission = "administer auction items entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *     "workflow" = "workflow"
 *   },
 *   links = {
 *     "canonical" = "/auction/item/{auction_item}",
 *     "add-form" = "/admin/content/auctions/add",
 *     "edit-form" = "/admin/content/auctions/{auction_item}/edit",
 *     "relist-form" = "/admin/content/auctions/{auction_item}/relist",
 *     "delete-form" = "/admin/content/auctions/{auction_item}/delete",
 *     "version-history" = "/admin/content/auctions/{auction_item}/revisions",
 *     "revision" = "/admin/content/auctions/{auction_item}/revisions/{auction_item_revision}/view",
 *     "revision_revert" = "/admin/content/auctions/{auction_item}/revisions/{auction_item_revision}/revert",
 *     "revision_delete" = "/admin/content/auctions/{auction_item}/revisions/{auction_item_revision}/delete",
 *     "translation_revert" = "/admin/content/auctions/{auction_item}/revisions/{auction_item_revision}/revert/{langcode}",
 *     "collection" = "/admin/content/auctions",
 *   },
 *   field_ui_base_route = "auction_item.settings"
 * )
 */
class AuctionItem extends EditorialContentEntityBase implements AuctionItemInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;
  use AuctionToolsTrait;

  /**
   * Implements preCreate for the AuctionItem entity.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage_controller
   *   The storage controller.
   * @param array $values
   *   An array of values to be used during the creation of the entity.
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);

    // Additional logic for preCreate, if necessary.
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * Implements urlRouteParameters for the entity.
   *
   * @param string $rel
   *   The relation type.
   *
   * @return array
   *   An array of URL route parameters.
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
   * Implements preSave for the entity.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage.
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }

      // Handle workflow to new if after the start date on entity create.
      $dateStatus = $this->getDateStatus();
      if ($this->isNew() && $dateStatus['start'] == 'post') {
        $this->setWorkflow(1);
      }

      // If no revision author has been set explicitly,
      // make the auction_item owner the revision author.
      if (!$this->getRevisionUser()) {
        $this->setRevisionUserId($this->getOwnerId());
      }
    }
  }

  /**
   * Gets the ID of the entity.
   *
   * @return int
   *   The entity ID.
   */
  public function getId() {
    return $this->get('id')->value;
  }

  /**
   * Sets the ID of the entity.
   *
   * @param int $id
   *   The entity ID to set.
   *
   * @return $this
   */
  public function setId($id) {
    $this->set('id', $id);
    return $this;
  }

  /**
   * Gets the name of the entity.
   *
   * @return string
   *   The entity name.
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * Sets the name of the entity.
   *
   * @param string $name
   *   The entity name to set.
   *
   * @return $this
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * Gets the created time of the entity.
   *
   * @return int
   *   The Unix timestamp of the creation time.
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * Sets the created time of the entity.
   *
   * @param int $timestamp
   *   The Unix timestamp of the creation time.
   *
   * @return $this
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * Gets the owner of the entity.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity representing the owner.
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * Gets the owner's user ID.
   *
   * @return int
   *   The user ID of the owner.
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * Sets the owner's user ID.
   *
   * @param int $uid
   *   The user ID to set as the owner.
   *
   * @return $this
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * Sets the owner of the entity.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account to set as the owner.
   *
   * @return $this
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * Gets the date information of the entity.
   *
   * @return array
   *   An array contain 'value' and 'end' elements representing the date values.
   */
  public function getDate() {
    return [
      'value' => $this->get('date')->value,
      'end' => $this->get('date')->end_value,
    ];
  }

  /**
   * Sets the date range for the entity.
   *
   * @param array $daterange
   *   An array with 'value' and 'end_value' elements representing the date
   *   values in 'Y-m-d\TH:i:s' format.
   *
   * @return $this
   */
  public function setDate($daterange) {
    $this->set('date', $daterange);
    return $this;
  }

  /**
   * Gets the price threshold for the entity.
   *
   * @return int
   *   The price threshold value.
   */
  public function getPriceThreshold() {
    $priceThreshold = $this->get('price_threshold')->value;
    return $priceThreshold;
  }

  /**
   * Sets the price threshold for the entity.
   *
   * @param int $int
   *   The price threshold value to set.
   *
   * @return $this
   */
  public function setPriceThreshold($int) {
    $this->set('price_threshold', $int);
    return $this;
  }

  /**
   * Get the state of this auction item date to determine pre/active/post logic.
   *
   * This method calculates the status of the auction item based on its dates.
   * It returns an array about the item's status and date details.
   *
   * @return array
   *   An array containing the following keys:
   *   - 'status': The general status ('start', 'active', or 'end').
   *   - 'start': The start status ('pre' or 'post').
   *   - 'end': The end status ('pre' or 'post').
   *   - 'date': An array with 'value' and 'end' representing the date values.
   *   - 'now': The current timestamp in Unix format.
   *   - 'date_formatted': An array with formatted date values.
   */
  public function getDateStatus(): array {
    $date = $this->getDate();
    $now = new DrupalDateTime('now');
    $getUserTimezone = date_default_timezone_get();
    $userTimezone = new \DateTimeZone($getUserTimezone);
    $currently = $now->setTimezone($userTimezone)->format('U');
    $auctionStarts = DrupalDateTime::createFromFormat('Y-m-d\TH:i:s', $date['value'], $this->auctionDefaultTimeZone())->setTimezone($userTimezone);
    $auctionStartsTimestamp = $auctionStarts->format('U');
    $auctionEnds = DrupalDateTime::createFromFormat('Y-m-d\TH:i:s', $date['end'], $this->auctionDefaultTimeZone())->setTimezone($userTimezone);
    $auctionEndsTimestamp = $auctionEnds->format('U');
    $status = 'start';
    $start = 'pre';
    $end = 'pre';
    if ($currently > $auctionStartsTimestamp && $currently < $auctionEndsTimestamp) {
      $status = 'active';
    }
    if ($currently > $auctionStartsTimestamp) {
      $status = 'end';
      $start = 'post';
    }

    if ($currently > $auctionEndsTimestamp) {
      $status = 'post';
      $end = 'post';
    }
    $dateFormatter = \Drupal::service('date.formatter');
    return [
      'status' => $status,
      'start' => $start,
      'end' => $end,
      'date' => $date,
      'now' => $now->format('U'),
      'date_formatted' => [
        'start_time' => $auctionStarts->format('Y-m-d H:i:s'),
        'end_time' => $auctionEnds->format('Y-m-d H:i:s'),
        'start_human' => $dateFormatter->format($auctionStartsTimestamp, 'long'),
        'end_human' => $dateFormatter->format($auctionEndsTimestamp, 'long'),
        'start_unix' => $auctionStartsTimestamp,
        'end_unix' => $auctionEndsTimestamp,
      ],
    ];
  }

  /**
   * Bst grouping of if auction item is closed.
   *
   * Return bool.
   */
  public function isClosed() {
    $dateStatus = $this->getDateStatus();
    $workflow = $this->getWorkflow();
    $isClosed = FALSE;
    if ($workflow == -1 || $workflow == 3 || $workflow == 4) {
      $isClosed = TRUE;
    }
    if ($dateStatus['status'] == 'post') {
      $isClosed = TRUE;
    }
    return $isClosed;
  }

  /**
   * Checks if the auction item is open for bidding.
   *
   * This method determines if the auction item is open for bidding based on its
   * workflow state and date status.
   *
   * @return bool
   *   TRUE if the auction item is open for bidding, FALSE otherwise.
   */
  public function isOpen() {
    $dateStatus = $this->getDateStatus();
    $workflow = $this->getWorkflow();
    $isOpen = FALSE;
    if ($workflow == '1') {
      $isOpen = TRUE;
    }
    if ($dateStatus['start'] == 'post') {
      $isOpen = TRUE;
    }
    return $isOpen;
  }

  /**
   * Get the Instant Only value.
   *
   * @return bool
   *   TRUE if the auction item is an instant-only item, FALSE otherwise.
   */
  public function getInstantOnly() {
    $instantOnly = $this->get('instant_only')->value;
    return $instantOnly;
  }

  /**
   * Check if the auction item has a "Buy Now" option and return the price.
   *
   * This method checks if the auction item has a "Buy Now" option available and
   * returns the buy now price if it exists, or FALSE if there is no "Buy Now"
   * option.
   *
   * @return false|float
   *   The buy now price (if available), or FALSE if there is no  option.
   */
  public function hasBuyNow() {
    $instantOnly = $this->getInstantOnly();
    // Need raw value.
    $buyNowPrice = \floatval($this->get('price_buy_now')->value);
    return $instantOnly || $buyNowPrice !== 0 ? $buyNowPrice : FALSE;
  }

  /**
   * Set the Instant Only value.
   *
   * @param bool $value
   *   TRUE if the auction item is an instant-only item, FALSE otherwise.
   *
   * @return $this
   */
  public function setInstantOnly($value) {
    $this->set('instant_only', $value);
    return $this;
  }

  /**
   * Get the relist count for the auction item.
   *
   * @return int
   *   The number of times the auction item has been relisted.
   */
  public function getRelistCount() {
    $relistCount = $this->get('relist_count')->value;
    return $relistCount;
  }

  /**
   * Set the relist count for the auction item.
   *
   * @param int $value
   *   The number of times the auction item has been relisted.
   *
   * @return $this
   */
  public function setRelistCount($value) {
    $this->set('relist_count', $value);
    return $this;
  }

  /**
   * Get the workflow status of the auction item.
   *
   * @return int
   *   The workflow status of the auction item.
   */
  public function getWorkflow() {
    $workflow = $this->get('workflow')->value;
    return $workflow;
  }

  /**
   * Allow other modules to interact upon auction_item workflow changes.
   */
  public function setWorkflow($int) {
    $this->set('workflow', $int);
    // Send new workflow state into hookl.
    \Drupal::moduleHandler()->invokeAll('auctions_core_workflow_action', [
      &$this, $int,
    ]);
    return $this;
  }

  /**
   * Gets the currency code associated with this entity.
   */
  public function getCurrencyCode() {
    $currencyCode = $this->get('currency_code')->value;
    return $currencyCode;
  }

  /**
   * Sets the currency code for this entity.
   *
   * @param string $value
   *   The currency code to set.
   *
   * @return $this
   *   The current instance for chaining.
   */
  public function setCurrencyCode($value) {
    $this->set('currency_code', $value);
    return $this;
  }

  /**
   * Gets the starting price for this entity.
   */
  public function getPriceStarting() {
    $priceStarting = $this->get('price_starting')->value;
    return $priceStarting;
  }

  /**
   * Sets the starting price for this entity.
   */
  public function setPriceStarting($float) {
    $this->set('price_starting', $this->roundCents($float));
    return $this;
  }

  /**
   * Gets the bid step for this entity.
   */
  public function getBidStep() {
    $bidStep = $this->get('bid_step')->value;
    return $this->showAsCents($bidStep);
  }

  /**
   * Sets the bid step for this entity.
   */
  public function setBidStep($float) {
    $this->set('bid_step', $this->roundCents($float));
    return $this;
  }

  /**
   * Gets the Buy Now price for this entity.
   */
  public function getPriceBuyNow() {
    $priceBuyNow = $this->get('price_buy_now')->value;
    return $this->showAsCents($priceBuyNow);
  }

  /**
   * Sets the Buy Now price for this entity.
   */
  public function setPriceBuyNow($float) {
    $this->set('price_buy_now', $this->roundCents($float));
    return $this;
  }

  /**
   * Get all bids that belong to 'relist_group' on auction_bid entities.
   *
   * @param bool $relist
   *   TRUE to get all bids. FALSE to get just 'hot' bids.
   * @param int|false $topLimit
   *   Number of top bids to fetch or FALSE to get all.
   *
   * @return \Drupal\auctions_core\Entity\AuctionBidInterface[]
   *   An array of Bid Entities ordered by the highest amount.
   */
  public function getBids($relist = 0, $topLimit = FALSE) {
    $bidIds = $this->getRelistBids($relist, $topLimit);
    $bids = [];
    if ($bidIds) {
      $bidStorage = \Drupal::entityTypeManager()->getStorage('auction_bid');
      $bids = $bidStorage->loadMultiple($bidIds);
    }
    return $bids;
  }

  /**
   * Get bid IDs, pre-sorted by amount.
   *
   * @param bool $relist
   *   TRUE to get all bids. FALSE to get just 'hot' bids.
   * @param int|false $topLimit
   *   Number of top bids to fetch or FALSE to get all.
   *
   * @return int[]
   *   An array of bid IDs.
   */
  public function getRelistBids($relist = 0, $topLimit = FALSE) {
    $query = \Drupal::entityTypeManager()->getStorage('auction_bid')->getQuery();
    $id = $this->getId();
    $query->condition('item', $id);

    // Do not include rejected 'purchase_offer'.
    $query->condition('purchase_offer', '-1', '!=');

    if ($relist !== FALSE) {
      $query->condition('relist_group', $relist);
    }
    if ($topLimit) {
      $query->range(0, $topLimit);
    }
    $query->sort('amount', 'DESC');
    $query->accessCheck();
    $entity_ids = $query->execute();
    return $entity_ids;
  }

  /**
   * Check if there are any bids for this item.
   *
   * @return bool
   *   TRUE if there are bids, FALSE otherwise.
   */
  public function hasBids() {
    $relistBidsCount = $this->getRelistBids($this->getRelistCount(), 3);
    return !empty($relistBidsCount);
  }

  /**
   * Set the bids for this item.
   *
   * @param \Drupal\auctions_core\Entity\AuctionBidInterface[] $bids
   *   An array of Bid Entities to set for this item.
   *
   * @return $this
   */
  public function setBids(array $bids) {
    $this->set('bids', $bids);
    return $this;
  }

  /**
   * Summarize bids for data simplicity.
   *
   * @param \Drupal\auctions_core\Entity\AuctionBidInterface[] $bids
   *   An array of Bid Entities to summarize.
   *
   * @return float[]
   *   An array of summarized data.
   */
  public function summarizeBids($bids) {
    $processed = [];
    foreach ($bids as $bid) {
      $processed[] = [
        'id' => $bid->getId(),
        'amount' => \floatval($bid->getAmount()),
        'uid' => $bid->getOwnerId(),
        'bid' => $bid,
      ];
    }
    return $processed;
  }

  /**
   * Summarize bids for data simplicity.
   *
   * @return array
   *   An array containing the 'minPrice' and 'leadBid' data.
   */
  public function seekCurrentHightest() {
    $currentHightest['minPrice'] = \floatval($this->getPriceStarting());
    $currentHightest['leadBid'] = FALSE;

    $highestBid = FALSE;
    $highestBids = $this->getBids($this->getRelistCount(), 1);
    if ($this->hasBids()) {
      $highestBidKeys = \array_keys($highestBids);
      $currentHightest['leadBid'] = $highestBids[$highestBidKeys[0]];
      $highestBid = \floatval($currentHightest['leadBid']->getAmount());
    }

    if ($highestBid > $currentHightest['minPrice']) {
      $currentHightest['minPrice'] = $highestBid;
    }
    return $currentHightest;
  }

  /**
   * Logic to determine if instant bid button display is near its percentages.
   *
   * @return bool
   *   TRUE if the instant bid button should be displayed, FALSE otherwise.
   */
  public function seekBidThreshold() {
    $percent = $this->getPriceThreshold();
    $dateStatus = $this->getDateStatus();
    $thresholdStatus = FALSE;

    $start = $dateStatus['date_formatted']['start_unix'];
    $end = $dateStatus['date_formatted']['end_unix'];
    $now = $dateStatus['now'];
    $watch['range'] = $end - $start;
    $watch['at'] = $now - $start;
    $watch['percent'] = $this->roundCents($watch['at'] / $watch['range']) * 100;
    // If time is @percent
    if ($watch['percent'] >= $percent) {
      $thresholdStatus = TRUE;
    }

    // If current price is @percent of instant amount.
    $instantPrice = $this->roundCents($this->getPriceBuyNow());
    $currentPrice = $this->seekCurrentHightest();
    $instantPercent = $instantPrice * ($percent / 100);

    if ($instantPercent <= $currentPrice['minPrice'] && $this->getPriceBuyNow() != 0) {
      $thresholdStatus = TRUE;
    }

    // If is INSTANT ONLY cancel both the above.
    if ($this->getInstantOnly()) {
      $thresholdStatus = FALSE;
    }
    return $thresholdStatus;
  }

  /**
   * Defines the base field definitions for your entity.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition[]
   *   An array of base field definitions.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Add the published field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the Item was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the Item was last edited.'));

    $fields['revision_translation_affected'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Revision translation affected'))
      ->setDescription(t('Indicates if the last edit of a translation belongs to current revision.'))
      ->setReadOnly(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    $fields['status']->setDescription(t('Auction Item is published/unpublished trait.'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 41,
      ]);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Item Title'))
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setSettings([
        'max_length' => 1056,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Item Owner'))
      ->setDescription(t('The User ID of for payment of Final Sale.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
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
      ->setDisplayConfigurable('view', FALSE);

    $fields['date'] = BaseFieldDefinition::create('daterange')
      ->setLabel(t('Auction Open Period'))
      ->setDescription(t('This time is stored at GTM/UTC.  Users will see time/countdown shifted to their Account timezone.  Anonynous will default to site timezone.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'daterange_datelist',
        'settings' => [
          'date_order' => 'YMD',
          'time_type' => 12,
          'increment' => 1,
        ],
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['relist_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Relist Count'))
      ->setDescription(t('Number of times this item has been relisted.'))
      ->setSetting('unsigned', TRUE)
      ->setDefaultValue(0)
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    $fields['workflow'] = BaseFieldDefinition::create('list_integer')
      ->setLabel(t('Auction Status'))
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setRequired(TRUE)
      ->setCardinality(1)
      ->setDefaultValue(0)
      ->setSettings([
        'allowed_values_function' => [
          '\Drupal\auctions_core\AuctionToolsBase',
          'itemWorkflows',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'list_default',
        'weight' => 6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'auctions_core_options_select',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['currency_code'] = BaseFieldDefinition::create('list_string')
      ->setSettings([
        'allowed_values_function' => 'auctions_core_active_currency_list',
      ])
      ->setDefaultValueCallback('auctions_core_active_currency_list_default')
      ->setLabel('Currency')
      ->setCardinality(1)
      ->setDisplayOptions('form', [
        'type' => 'auctions_core_options_select',
        'weight' => 5,
      ])
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', FALSE);

    $fields['instant_only'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Instant Buy only mode.'))
      ->setRevisionable(FALSE)
      ->setTranslatable(FALSE)
      ->setDisplayOptions('form', [
        'weight' => 9 ,
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['price_starting'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Starting Price'))
      ->setRevisionable(FALSE)
      ->setTranslatable(FALSE)
      ->setDisplayOptions('form', [
        'weight' => 10,
        'type' => 'number',
        'settings' => [
          'display_label' => TRUE,
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'number_decimal',
      ])
      ->addConstraint('AuctionsPrice')
      ->addConstraint('NotNull')
      ->setDisplayConfigurable('form', TRUE)
      ->setDefaultValue('0');

    $fields['bid_step'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Bid Step'))
      ->setDefaultValue('5.00')
      ->setRevisionable(FALSE)
      ->setTranslatable(FALSE)
      ->setDisplayOptions('form', [
        'weight' => 11,
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
      ->setDisplayConfigurable('view', FALSE)
      ->addConstraint('NotNull');

    $fields['price_buy_now'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Buy Now Price'))
      ->setRevisionable(FALSE)
      ->setTranslatable(FALSE)
      ->setDisplayOptions('form', [
        'weight' => 14,
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
      ->setDefaultValue('0');

    $fields['price_threshold'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Buy Now Threshold'))
      ->setDescription(t("Buy Now button will now longer be available"))
      ->setRevisionable(FALSE)
      ->setTranslatable(FALSE)
      ->setDisplayOptions('form', [
        'weight' => 15,
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
      ->setDisplayConfigurable('view', FALSE)
      ->addConstraint('NotNull')
      ->setDefaultValue('85');

    $fields['bids'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Bids for this Auction'))
      ->setDescription(t("The Bid's this offer is for"))
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setSetting('target_type', 'auction_bid')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 100,
      ])
      ->setDisplayOptions('form', [
        'type' => 'inline_entity_form_complex',
        'weight' => 100,
        'settings' => [
          'form_mode' => 'default',
          'label_singular' => '',
          'label_plural' => '',
          'allow_new' => TRUE,
          'match_operator' => 'CONTAINS',
          'revision' => FALSE,
          'override_labels' => FALSE,
          'collapsible' => TRUE,
          'collapsed' => FALSE,
          'allow_existing' => FALSE,
          'allow_duplicate' => FALSE,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(FALSE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    return $fields;
  }

}
