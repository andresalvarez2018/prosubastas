<?php

namespace Drupal\auctions_core\Service;

use Drupal\auctions_core\AuctionToolsTrait;
use Drupal\auctions_core\Entity\AuctionAutobidInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageDefault;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationManager;
use Symfony\Component\Mime\Header\UnstructuredHeader;

/**
 * An informational helper.
 *
 * @ingroup auctions_core
 */
class AuctionTools {

  use StringTranslationTrait;
  use AuctionToolsTrait;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  public $entityTypeManager;

  /**
   * Core module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Core module handler.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  public $configFactory;

  /**
   * Current User.
   *
   * @var Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The language default.
   *
   * @var \Drupal\Core\Language\LanguageDefault
   */
  protected $languageDefault;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  public $languageManager;

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $database;

  /**
   * The renderer service.
   *
   * This is not injected because that would result in a circular dependency.
   * Instead, the renderer should pass itself to the ThemeManager when it is
   * constructed, using the setRenderer() method.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  public $renderer;

  /**
   * The UUID service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  public $uuidService;

  /**
   * AuctionTools constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The core module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user account proxy.
   * @param \Drupal\Core\Language\LanguageDefault $language_default
   *   The default language manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The UUID service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $configFactory, AccountProxyInterface $currentUser, LanguageDefault $language_default, LanguageManagerInterface $language_manager, Connection $database, RendererInterface $renderer, UuidInterface $uuid_service) {
    $this->entityTypeManager = $entityTypeManager;
    $this->moduleHandler = $module_handler;
    $this->configFactory = $configFactory;
    $this->currentUser = $currentUser;
    $this->languageDefault = $language_default;
    $this->languageManager = $language_manager;
    $this->database = $database;
    $this->renderer = $renderer;
    $this->uuidService = $uuid_service;
  }

  /**
   * Wapper for service lookup.
   */
  public function hasModule($moduleName = 'commerce_price') {
    return $this->moduleHandler->moduleExists($moduleName);
  }

  /**
   * {@inheritdoc}
   */
  public function getCommerceCurrencies() {
    $active_currencies = [];
    if ($this->hasModule('commerce_price')) {
      /** @var \Drupal\commerce_price\Entity\CurrencyInterface[] $currencies */
      $currencies = $this->entityTypeManager->getStorage('commerce_currency')->loadMultiple();

      foreach ($currencies as $currency) {
        if ($currency->status()) {
          $active_currencies[$currency->getCurrencyCode()] = $currency->getName();
        }
      }
    }
    return $active_currencies;
  }

  /**
   * Get active currencies as per the settings.
   *
   * @return array
   *   An array of active currency options.
   */
  public function getActiveItemCurrencies() {
    $currencyList = self::currencyList();
    // Note: activeCurrenciesConfig is a workaround for installation, as
    // config may not be installed before the entity is initialized.
    $activeCurrenciesConfig = $this->configFactory->get('auctions.item_settings')->get('active-currency');
    $activeCurrencies = $activeCurrenciesConfig ? $activeCurrenciesConfig : ['USD' => 'United States Dollar'];
    $activeCurrency = array_flip($activeCurrencies);
    unset($activeCurrency[0]);
    $options = [];
    foreach ($activeCurrency as $currency) {
      $options[$currency] = $currencyList[$currency];
    }
    return $options;
  }

  /**
   * Masks user name by leaving only the first and last letter.
   *
   * @return string
   *   The masked user name.
   */
  public function activeCurrencyList() {
    $hasCommercePrice = self::hasModule('commerce_price');
    $currencyCodes = self::getActiveItemCurrencies();
    if ($hasCommercePrice) {
      $currencyCodes = self::getCommerceCurrencies();
    }
    return $currencyCodes;
  }

  /**
   * Setup to send mail.
   *
   * @return bool
   *   TRUE if the mail was sent successfully, otherwise FALSE.
   */
  public function sendMail($to, $from, $reply, $subject, array $body, array $params = []) {

    if (empty($to)) {
      return FALSE;
    }

    $default_params = [
      'headers' => [
        'Content-Type' => 'text/html; charset=iso-8859-1',
        'MIME-Version' => '1.0',
        'To' => $to,
        'From' => $from,
        'Reply-To' => $reply,
        'X-Mailer' => 'Drupal Auction',
      ],
      'to' => $to,
      'subject' => $subject,
      'langcode' => $this->languageManager->getCurrentLanguage()->getId(),
      'body' => $body,
    ];
    if (!empty($params['cc'])) {
      $default_params['headers']['Cc'] = $params['cc'];
    }
    if (!empty($params['bcc'])) {
      $default_params['headers']['Bcc'] = $params['bcc'];
    }
    $params = \array_replace($default_params, $params);

    // Change the active language to ensure the email is properly translated.
    if ($params['langcode'] != $default_params['langcode']) {
      $this->changeActiveLanguage($params['langcode']);
    }

    $message = $this->mail($params);
    // Revert back to the original active language.
    if ($params['langcode'] != $default_params['langcode']) {
      $this->changeActiveLanguage($default_params['langcode']);
    }

    return (bool) $message;
  }

  /**
   * Sends an email message. this fn is 'forked' from  phpMail to allow html.
   *
   * @param array $message
   *   A message array, as described in hook_mail_alter().
   *
   * @return bool
   *   TRUE if the mail was successfully accepted, otherwise FALSE.
   */
  protected function mail(array $message) {
    $mail_result = FALSE;
    $request = \Drupal::request();
    $line_endings = Settings::get('mail_line_endings', PHP_EOL);

    // If 'Return-Path' isn't already set in php.ini, we pass it separately
    // as an additional parameter instead of in the header.
    if (isset($message['headers']['Return-Path'])) {
      $return_path_set = \strpos(\ini_get('sendmail_path'), ' -f');
      if (!$return_path_set) {
        $message['Return-Path'] = $message['headers']['Return-Path'];
        unset($message['headers']['Return-Path']);
      }
    }

    $mimeheaders = [];
    foreach ($message['headers'] as $name => $value) {
      $mimeheaders[] = (new UnstructuredHeader('name', $value))->getBodyAsString();
    }
    $mail_headers = \implode("\n", $mimeheaders);

    // Prepare mail commands.
    $mail_subject = (new UnstructuredHeader('subject', $message['subject']))->getBodyAsString();

    // Reminder: email uses CRLF for line-endings. PHP's API requires LF
    // on Unix and CRLF on Windows. Drupal automatically guesses the
    // line-ending format appropriate for your system. If you need to
    // override this, adjust $settings['mail_line_endings'] in settings.php.
    $render_body = '<html><body>' . $this->renderer->render($message['body']) . '</body></html>';
    $mail_body = \preg_replace('@\r?\n@', $line_endings, $render_body);
    // For headers, PHP's API suggests that we use CRLF normally,
    // but some MTAs incorrectly replace LF with CRLF. See #234403.
    // We suppress warnings and notices from mail() because of issues on some
    // hosts. The return value of this method will still indicate whether mail
    // was sent successfully.
    if (!$request->server->has('WINDIR') && \strpos($request->server->get('SERVER_SOFTWARE'), 'Win32') === FALSE) {
      // On most non-Windows systems, the "-f" option to the sendmail command
      // is used to set the Return-Path. There is no space between -f and
      // the value of the return path.
      // We validate the return path, unless it is equal to the site mail, which
      // we assume to be safe.
      $site_mail = $this->configFactory->get('system.site')->get('mail');
      $additional_headers = isset($message['Return-Path']) && ($site_mail === $message['Return-Path'] || static::isShellSafe($message['Return-Path'])) ? '-f' . $message['Return-Path'] : '';
      $mail_result = @mail(
        $message['to'],
        $mail_subject,
        $mail_body,
        $mail_headers,
        $additional_headers
      );
    }
    else {
      // On Windows, PHP will use the value of sendmail_from for the
      // Return-Path header.
      $old_from = \ini_get('sendmail_from');
      $returnPath = $message['Return-Path'] ?? '';
      ini_set('sendmail_from', $returnPath);
      $mail_result = @mail(
        $message['to'],
        $mail_subject,
        $mail_body,
        $mail_headers
      );
      \ini_set('sendmail_from', $old_from);
    }

    return $mail_result;
  }

  /**
   * Disallows potentially unsafe shell characters.
   *
   * Functionally similar to PHPMailer::isShellSafe() which resulted from
   * CVE-2016-10045. Note that escapeshellarg and escapeshellcmd are inadequate
   * for this purpose.
   *
   * @param string $string
   *   The string to be validated.
   *
   * @return bool
   *   True if the string is shell-safe.
   *
   * @see https://github.com/PHPMailer/PHPMailer/issues/924
   * @see https://github.com/PHPMailer/PHPMailer/blob/v5.2.21/class.phpmailer.php#L1430
   *
   * @todo Rename to ::isShellSafe() and/or discuss whether this is the correct
   *   location for this helper.
   */
  protected static function isShellSafe($string) {
    if (\escapeshellcmd($string) !== $string || !\in_array(\escapeshellarg($string), [
      "'{$string}'",
      "\"{$string}\"",
    ])) {
      return FALSE;
    }
    if (\preg_match('/[^a-zA-Z0-9@_\\-.]/', $string) !== 0) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Changes the active language for translations.
   *
   * @param string $langcode
   *   The langcode.
   */
  private function changeActiveLanguage($langcode) {
    if (!$this->languageManager->isMultilingual()) {
      return;
    }
    $language = $this->languageManager->getLanguage($langcode);
    if (!$language) {
      return;
    }
    // The language manager has no method for overriding the default
    // language, like it does for config overrides. We have to change the
    // default language service's current language.
    // @see https://www.drupal.org/project/drupal/issues/3029010
    $this->languageDefault->set($language);
    $this->languageManager->setConfigOverrideLanguage($language);
    $this->languageManager->reset();

    // The default string_translation service, TranslationManager, has a
    // setDefaultLangcode method. However, this method is not present on
    // either of its interfaces. Therefore we check for the concrete class
    // here so that any swapped service does not break the application.
    // @see https://www.drupal.org/project/drupal/issues/3029003
    $string_translation = $this->getStringTranslation();
    if ($string_translation instanceof TranslationManager) {
      $string_translation->setDefaultLangcode($language->getId());
      $string_translation->reset();
    }
  }

  /**
   * Return a general list of recommended Bid Step increments.
   *
   * @param int $auctionItemID
   *   The ID of the auction item.
   * @param bool $relistCount
   *   (Optional) The relist count to filter by.
   *
   * @return array
   *   An array of unique user IDs.
   *
   *    # relist group: Allows to GET ALL, ever...
   */
  public function uniqueUserList($auctionItemID, $relistCount = FALSE) {
    $auctionUsers = $this->database->select('auction_bid', 'bids');
    $auctionUsers->fields('bids', ['user_id']);
    $auctionUsers->distinct();
    $auctionUsers->condition('bids.item', $auctionItemID, '=');
    if ($relistCount) {
      $auctionUsers->condition('bids.relist_group', $relistCount, '=');
    }
    $result = $auctionUsers->execute()->fetchCol();

    return $result;
  }

  /**
   * Return a general list of users who have autobid max opt-ins.
   *
   * @param int $auctionItemID
   *   The ID of the auction item.
   * @param int $amountMax
   *   The minimum amount max for opt-ins.
   * @param int $uidExclude
   *   The user ID to exclude.
   * @param bool $relistCount
   *   (Optional) The relist count to filter by.
   *
   * @return array|false
   *   An array of users who have autobid max opt-ins if found,FALSE if not .
   *
   * @relist group:
   *   Allows to GET ALL, ever...
   */
  public function autobidsGroupedByUser($auctionItemID, $amountMax, $uidExclude, $relistCount = FALSE) {
    $conf = $this->configFactory->get('auctions.item_settings');
    $autobidsOrdering = $conf->get('autobids-ordering');
    $autobidsDirection = $conf->get('autobids-direction');

    $auctionUsers = $this->database->select('auction_autobid', 'auto');
    $auctionUsers->fields('auto', ['id', 'item', 'amount_max', 'user_id']);
    $auctionUsers->condition('auto.item', $auctionItemID, '=');
    $auctionUsers->condition('auto.status', 1, '=');
    $auctionUsers->condition('auto.user_id', $uidExclude, '!=');  /* exclude self */
    $auctionUsers->condition('auto.amount_max', $amountMax, '>=');
    if ($relistCount) {
      $auctionUsers->condition('auto.relist_group', $relistCount, '=');
    }
    // Reminder: Ordered by who opted in first.
    $auctionUsers->orderBy($autobidsOrdering, $autobidsDirection);
    $result = $auctionUsers->execute()->fetchAll(\PDO::FETCH_ASSOC);
    $groupby = $result ? $this->groupResultBy('user_id', $result) : FALSE;
    return $groupby;
  }

  /**
   * Return a general list of bids grouped by user.
   *
   * @param int $auctionItemID
   *   The ID of the auction item.
   * @param bool $relistCount
   *   (Optional) The relist count to filter by.
   *
   * @return array|false
   *   An array of bids grouped by user if found, or FALSE if not found.
   *
   * @relist group:
   *   Allows to GET ALL, ever...
   */
  public function bidsGroupedByUser($auctionItemID, $relistCount = FALSE) {
    $auctionUsers = $this->database->select('auction_bid', 'bids');
    $auctionUsers->fields('bids', [
      'id',
      'item',
      'amount',
      'relist_group',
      'user_id',
      'type',
    ]);
    $auctionUsers->condition('bids.item', $auctionItemID, '=');
    if ($relistCount) {
      $auctionUsers->condition('bids.relist_group', $relistCount, '=');
    }
    $auctionUsers->orderBy('id', 'DESC');
    $result = $auctionUsers->execute()->fetchAll(\PDO::FETCH_ASSOC);

    $groupby = $result ? $this->groupResultBy('user_id', $result) : FALSE;
    return $groupby;
  }

  /**
   * Internal:  grouping for bidsGroupedByUser(),.
   */
  private function groupResultBy($key, $data) {
    $result = [];
    foreach ($data as $val) {
      if (\array_key_exists($key, $val)) {
        $result[$val[$key]][] = $val;
      }
    }
    return $result;
  }

  /**
   * Return Auction Item Winner.
   *
   * @param $auctionItemID
   *   Auction Item ID.
   * @param $relistCount
   *   Relist count.
   */
  public function getAuctionItemWinner($auctionItemID, $relistCount = FALSE) {
    $query = $this->database->select('auction_bid', 'bids');
    $query->fields('bids', ['id', 'item', 'amount', 'relist_group', 'user_id', 'type']);
    $query->condition('bids.item', $auctionItemID, '=');

    if ($relistCount) {
      $query->condition('bids.relist_group', $relistCount, '=');
    }

    $query->orderBy('amount', 'DESC');
    $users = $query->execute()->fetchAllAssoc('user_id');
    $winner = reset($users);

    if (!empty($winner)) {
      return $this->entityTypeManager->getStorage('user')->load($winner->user_id);
    }

    return FALSE;
  }

  /**
   * Process Auction Bid for the Auction Item.
   */
  public function handleBid($uid, $item, $amount, $typeAuto = FALSE, $close = FALSE) {
    $values = [
      'user_id' => $uid,
      'item' => $item->getId(),
      'relist_group' => $item->getRelistCount(),
      'type' => $typeAuto ? 'auto' : 'standard',
      'purchase_offer' => 0,
      'amount' => $amount,
    ];

    // Save the Bid.
    if ($close) {
      $bid = $this->saveBid($values, TRUE);
    }
    else {
      $bid = $this->saveBid($values);

      // Tirggers Autobid if the Autobit price is higher.
      if (!$typeAuto) {
        $autobid = $this->getHighestItemAutoBid($item->getId(), $item->getRelistCount());

        if ($autobid instanceof AuctionAutobidInterface &&
            $autobid->getOwnerId() !== $uid &&
            $autobid->getAmountMax() > $amount) {

          $autoAmount = $amount + $item->getBidStep();
          $autoAmount = $autoAmount < $autobid->getAmountMax() ? $autoAmount : $autobid->getAmountMax();
          $this->handleBid($autobid->getOwnerId(), $item, $autoAmount, TRUE);
        }
      }
    }

    return $bid;
  }

  /**
   * Saves a bid for an auction item.
   *
   * This method creates and saves bid entity for specific auction item based on
   * the provided values. It also updates the corresponding item entity and sets
   * the workflow state if needed.
   *
   * @param array $value
   *   An associative array containing the bid values, including 'user_id',
   *   'item','relist_group', 'type', 'purchase_offer', and 'amount'.
   * @param bool $close
   *   (Optional) A boolean indicating whethr bid should close the auction item.
   *   Defaults to FALSE.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The saved bid entity.
   */
  public function saveBid($values, $close = FALSE) {
    $bid = $this->entityTypeManager->getStorage('auction_bid')->create([
      'user_id' => $values['user_id'],
      'item' => ['target_id' => $values['item']],
      'relist_group' => $values['relist_group'],
      'type' => $values['type'],
      'purchase_offer' => $values['purchase_offer'],
    ]);
    $bid->setAmount($values['amount']);
    $bid->save();

    // Corresponding entity ref.
    if ($bid->id()) {
      $item = $bid->getItemEntity();
      $item->get('bids')->appendItem([
        'target_id' => $bid->id(),
      ]);

      if ($close) {
        $item->setWorkflow(3);
      }

      $item->save();
    }

    return $bid;
  }

  /**
   * Get 'any' autobid by uid, relist - order by highest (as current)
   */
  public function seekAutobid($uid, $auctionItemId, $relistGroup = FALSE) {
    $autobids = $this->entityTypeManager->getStorage('auction_autobid');
    $seekAutobid = $autobids->getQuery();
    $seekAutobid->condition('user_id', $uid);
    $seekAutobid->condition('item', $auctionItemId);
    if ($relistGroup) {
      $seekAutobid->condition('relist_group', $relistGroup);
    }
    $seekAutobid->sort('amount_max', 'DESC');
    $seekAutobid->accessCheck();
    $autobidList = $seekAutobid->execute();
    $hasAutobid = $autobids->loadMultiple($autobidList);

    return reset($hasAutobid);
  }

  /**
   * Flush thru and remove 'any' autobid triggers that could possibly exists.
   *
   * Reminder/direction:  change to handle thru publishing state @keep history..
   */
  public function removeAutobid($uid, $auctionItemId, $relistGroup = FALSE) {
    $autobids = $this->entityTypeManager->getStorage('auction_autobid');
    $seekAutobid = $autobids->getQuery();
    $seekAutobid->condition('user_id', $uid);
    $seekAutobid->condition('item', $auctionItemId);
    if ($relistGroup) {
      $seekAutobid->condition('relist_group', $relistGroup);
    }
    $seekAutobid->accessCheck();
    $autobidList = $seekAutobid->execute();
    $removals = $autobids->loadMultiple($autobidList);
    $ids = [];
    foreach ($removals as $id => $entity) {
      $entity->delete();
      $ids[$id] = $id;
    }
    return $ids;
  }

  /**
   * Get the Highest Item Autobid.
   */
  public function getHighestItemAutoBid($auctionItemId, $relistGroup = FALSE) {
    $autobidStorage = $this->entityTypeManager->getStorage('auction_autobid');
    $autobidQuery = $autobidStorage->getQuery();
    $autobidQuery->condition('item', $auctionItemId);

    if ($relistGroup) {
      $autobidQuery->condition('relist_group', $relistGroup);
    }

    $autobidQuery->sort('amount_max', 'DESC');
    $autobidQuery->accessCheck();
    $autobidList = $autobidQuery->execute();
    $autobid = $autobidStorage->loadMultiple($autobidList);

    return reset($autobid);
  }

  /**
   * Handles the autobid functionality for a user.
   *
   * @param int $uid
   *   The user ID for whom the autobid is handled.
   * @param int $auctionItemId
   *   The ID of the auction item to which the autobid is related.
   * @param int $relistGroup
   *   The relist group associated with the autobid.
   * @param float $amountMax
   *   The maximum bid amount for the autobid.
   *
   * @return \Drupal\YourModule\Entity\Autobid|bool
   *   The autobid entity if created or updated, or FALSE if there was an error.
   */
  public function handleAutobid($uid, $auctionItemId, $relistGroup, $amountMax) {
    $autobid = $this->seekAutobid($uid, $auctionItemId, $relistGroup);
    $item = $this->entityTypeManager->getStorage('auction_item')->load($auctionItemId);
    $step = $item->getBidStep();
    $highest = $this->getHighestItemAutoBid($auctionItemId, $relistGroup);
    $currentHightest = $item->seekCurrentHightest();

    if ($autobid instanceof AuctionAutobidInterface) {
      $autobid->setAmountMax($amountMax);
      $autobid->setName($this->showAsCents($amountMax, '.', ','));
      $autobid->save();
    }
    else {
      $autobid = $this->createAutobid($uid, $auctionItemId, $relistGroup, $amountMax);
    }

    if (!$highest || $highest->getOwnerId() === $autobid->getOwnerId()) {
      return $autobid;
    }

    // If here are more than one active autobids, the price is raised to the
    // user placed autobid if this is lower.
    if ($autobid->getAmountMax() < $highest->getAmountMax()) {
      $currBid = $autobid->getAmountMax();
      $prevBid = $highest->getAmountMax();
      if (($currBid + $step) < $prevBid) {
        $currBidAmount = $currBid;
        $prevBidAmount = $currBid + $step;
        $currUid = $uid;
        $prevUid = $highest->getOwnerId();
      }
      else {
        $currBidAmount = $prevBid;
        $prevBidAmount = $currBid;
        $currUid = $highest->getOwnerId();
        $prevUid = $uid;
      }

      // Place the Bid (type "auto"), for the AutoBid registered.
      $this->handleBid($currUid, $item, $this->roundCents($currBidAmount), TRUE);
      $this->handleBid($prevUid, $item, $this->roundCents($prevBidAmount), TRUE);
    }
    elseif ($autobid->getAmountMax() > $highest->getAmountMax()) {
      $currBid = $autobid->getAmountMax();
      $prevBid = $highest->getAmountMax();

      if (($currBid - $step) > $prevBid) {
        $currBidAmount = $prevBid + $step;
        $prevBidAmount = $prevBid;
        $currUid = $uid;
        $prevUid = $highest->getOwnerId();
      }
      else {
        $currBidAmount = $prevBid;
        $prevBidAmount = $currBid;
        $currUid = $highest->getOwnerId();
        $prevUid = $uid;
      }

      // Place a bid for the previous higher AutoBid.
      $this->handleBid($currUid, $item, $this->roundCents($currBidAmount), TRUE);
      $this->handleBid($prevUid, $item, $this->roundCents($prevBidAmount), TRUE);
    }
    else {
      // Wins the last one autobidding. NOTE: As requirement from PM.
      $this->handleBid($highest->getOwnerId(), $item, $autobid->getAmountMax() - $step, TRUE);
      $this->handleBid($uid, $item, $autobid->getAmountMax(), TRUE);
    }

    return $autobid;
  }

  /**
   * Return a general list of recommendind Bid Step increments.
   */
  private function createAutobid($uid, $auctionItemId, $relistGroup, $amountMax) {
    $auctionAutobid = $this->entityTypeManager->getStorage('auction_autobid');
    $amount = $this->roundCents($amountMax);
    $autobid = $auctionAutobid->create([
      'user_id' => $uid,
      'name' => $this->showAsCents($amount, '.', ',') /* Reminder:  always create a name. */,
      'amount_max' => $amount,
      'item' => $auctionItemId,
      'relist_group' => $relistGroup,
      'uuid' => $this->uuidService->generate(),
      'status' => 1,
      'created' => \time(),
    ]);

    $autobid->save();

    return $autobid;
  }

  /**
   * Return a  list of recommended Bid Step increments. concept, unused @fees.
   */
  public function suggestedBidIncrements() {
    return [
      '$50 — 300' => '$25',
      '$300 — 500' => '$50',
      '$500 — 2,000' => '$100',
      '$2,000 — 5,000' => '$250',
      '$5,000 — 10,000' => '$500',
      '$10,000 — 20,000' => '$1,000',
      '$20,000 — 50,000' => '$2,500',
      '$50,000 — 100,000' => '$5,000',
      '$100,000 — 300,000' => '$10,000',
      '$300,000 — 1,000,000' => '$25,000',
      '$1,000,000 — 2,000,000' => '$50,000',
      '$2,000,000 — 3,000,000' => '$100,000',
      '$3,000,000 — 5,000,000' => '$250,000',
      '$5,000,000 — 10,000,000' => '$500,000',
      '$10,000,000+' => '$1,000,000',
    ];
  }

  /**
   * A helper to build checkbox/select list options.
   */
  public function currencyOptions() {
    $currencies = self::currencyList();
    foreach ($currencies as $k => $v) {
      $currencies[$k] = $k . ' | ' . $v;
    }
    return $currencies;
  }

  /**
   * Return a list of known global currencies.
   */
  public function currencyList() {
    $currencies = [
      "AED" => $this->t("United Arab Emirates dirham"),
      "AFN" => $this->t("Afghan afghani"),
      "ALL" => $this->t("Albanian lek"),
      "AMD" => $this->t("Armenian dram"),
      "ANG" => $this->t("Netherlands Antillean guilder"),
      "AOA" => $this->t("Angolan kwanza"),
      "ARS" => $this->t("Argentine peso"),
      "AUD" => $this->t("Australian Dollar"),
      "AWG" => $this->t("Aruban florin"),
      "AZN" => $this->t("Azerbaijani manat"),
      "BAM" => $this->t("Bosnia and Herzegovina convertible mark"),
      "BBD" => $this->t("Barbados Dollar"),
      "BDT" => $this->t("Bangladeshi taka"),
      "BGN" => $this->t("Bulgarian lev"),
      "BHD" => $this->t("Bahraini dinar"),
      "BIF" => $this->t("Burundian franc"),
      "BMD" => $this->t("Bermudian Dollar"),
      "BND" => $this->t("Brunei Dollar"),
      "BOB" => $this->t("Boliviano"),
      "BRL" => $this->t("Brazilian real"),
      "BSD" => $this->t("Bahamian Dollar"),
      "BTN" => $this->t("Bhutanese ngultrum"),
      "BWP" => $this->t("Botswana pula"),
      "BYN" => $this->t("New Belarusian ruble"),
      "BYR" => $this->t("Belarusian ruble"),
      "BZD" => $this->t("Belize Dollar"),
      "CAD" => $this->t("Canadian Dollar"),
      "CDF" => $this->t("Congolese franc"),
      "CHF" => $this->t("Swiss franc"),
      "CLF" => $this->t("Unidad de Fomento"),
      "CLP" => $this->t("Chilean peso"),
      "CNY" => $this->t("Renminbi|Chinese yuan"),
      "COP" => $this->t("Colombian peso"),
      "CRC" => $this->t("Costa Rican colon"),
      "CUC" => $this->t("Cuban convertible peso"),
      "CUP" => $this->t("Cuban peso"),
      "CVE" => $this->t("Cape Verde escudo"),
      "CZK" => $this->t("Czech koruna"),
      "DJF" => $this->t("Djiboutian franc"),
      "DKK" => $this->t("Danish krone"),
      "DOP" => $this->t("Dominican peso"),
      "DZD" => $this->t("Algerian dinar"),
      "EGP" => $this->t("Egyptian pound"),
      "ERN" => $this->t("Eritrean nakfa"),
      "ETB" => $this->t("Ethiopian birr"),
      "EUR" => $this->t("Euro"),
      "FJD" => $this->t("Fiji Dollar"),
      "FKP" => $this->t("Falkland Islands pound"),
      "GBP" => $this->t("Pound sterling"),
      "GEL" => $this->t("Georgian lari"),
      "GHS" => $this->t("Ghanaian cedi"),
      "GIP" => $this->t("Gibraltar pound"),
      "GMD" => $this->t("Gambian dalasi"),
      "GNF" => $this->t("Guinean franc"),
      "GTQ" => $this->t("Guatemalan quetzal"),
      "GYD" => $this->t("Guyanese Dollar"),
      "HKD" => $this->t("Hong Kong Dollar"),
      "HNL" => $this->t("Honduran lempira"),
      "HRK" => $this->t("Croatian kuna"),
      "HTG" => $this->t("Haitian gourde"),
      "HUF" => $this->t("Hungarian forint"),
      "IDR" => $this->t("Indonesian rupiah"),
      "ILS" => $this->t("Israeli new shekel"),
      "INR" => $this->t("Indian rupee"),
      "IQD" => $this->t("Iraqi dinar"),
      "IRR" => $this->t("Iranian rial"),
      "ISK" => $this->t("Icelandic króna"),
      "JMD" => $this->t("Jamaican Dollar"),
      "JOD" => $this->t("Jordanian dinar"),
      "JPY" => $this->t("Japanese yen"),
      "KES" => $this->t("Kenyan shilling"),
      "KGS" => $this->t("Kyrgyzstani som"),
      "KHR" => $this->t("Cambodian riel"),
      "KMF" => $this->t("Comoro franc"),
      "KPW" => $this->t("North Korean won"),
      "KRW" => $this->t("South Korean won"),
      "KWD" => $this->t("Kuwaiti dinar"),
      "KYD" => $this->t("Cayman Islands Dollar"),
      "KZT" => $this->t("Kazakhstani tenge"),
      "LAK" => $this->t("Lao kip"),
      "LBP" => $this->t("Lebanese pound"),
      "LKR" => $this->t("Sri Lankan rupee"),
      "LRD" => $this->t("Liberian Dollar"),
      "LSL" => $this->t("Lesotho loti"),
      "LYD" => $this->t("Libyan dinar"),
      "MAD" => $this->t("Moroccan dirham"),
      "MDL" => $this->t("Moldovan leu"),
      "MGA" => $this->t("Malagasy ariary"),
      "MKD" => $this->t("Macedonian denar"),
      "MMK" => $this->t("Myanmar kyat"),
      "MNT" => $this->t("Mongolian tögrög"),
      "MOP" => $this->t("Macanese pataca"),
      "MRO" => $this->t("Mauritanian ouguiya"),
      "MUR" => $this->t("Mauritian rupee"),
      "MVR" => $this->t("Maldivian rufiyaa"),
      "MWK" => $this->t("Malawian kwacha"),
      "MXN" => $this->t("Mexican peso"),
      "MXV" => $this->t("Mexican Unidad de Inversion"),
      "MYR" => $this->t("Malaysian ringgit"),
      "MZN" => $this->t("Mozambican metical"),
      "NAD" => $this->t("Namibian Dollar"),
      "NGN" => $this->t("Nigerian naira"),
      "NIO" => $this->t("Nicaraguan córdoba"),
      "NOK" => $this->t("Norwegian krone"),
      "NPR" => $this->t("Nepalese rupee"),
      "NZD" => $this->t("New Zealand Dollar"),
      "OMR" => $this->t("Omani rial"),
      "PAB" => $this->t("Panamanian balboa"),
      "PEN" => $this->t("Peruvian Sol"),
      "PGK" => $this->t("Papua New Guinean kina"),
      "PHP" => $this->t("Philippine peso"),
      "PKR" => $this->t("Pakistani rupee"),
      "PLN" => $this->t("Polish złoty"),
      "PYG" => $this->t("Paraguayan guaraní"),
      "QAR" => $this->t("Qatari riyal"),
      "RON" => $this->t("Romanian leu"),
      "RSD" => $this->t("Serbian dinar"),
      "RUB" => $this->t("Russian ruble"),
      "RWF" => $this->t("Rwandan franc"),
      "SAR" => $this->t("Saudi riyal"),
      "SBD" => $this->t("Solomon Islands Dollar"),
      "SCR" => $this->t("Seychelles rupee"),
      "SDG" => $this->t("Sudanese pound"),
      "SEK" => $this->t("Swedish krona"),
      "SGD" => $this->t("Singapore Dollar"),
      "SHP" => $this->t("Saint Helena pound"),
      "SLL" => $this->t("Sierra Leonean leone"),
      "SOS" => $this->t("Somali shilling"),
      "SRD" => $this->t("Surinamese Dollar"),
      "SSP" => $this->t("South Sudanese pound"),
      "STD" => $this->t("São Tomé and Príncipe dobra"),
      "SVC" => $this->t("Salvadoran colón"),
      "SYP" => $this->t("Syrian pound"),
      "SZL" => $this->t("Swazi lilangeni"),
      "THB" => $this->t("Thai baht"),
      "TJS" => $this->t("Tajikistani somoni"),
      "TMT" => $this->t("Turkmenistani manat"),
      "TND" => $this->t("Tunisian dinar"),
      "TOP" => $this->t("Tongan paʻanga"),
      "TRY" => $this->t("Turkish lira"),
      "TTD" => $this->t("Trinidad and Tobago Dollar"),
      "TWD" => $this->t("New Taiwan Dollar"),
      "TZS" => $this->t("Tanzanian shilling"),
      "UAH" => $this->t("Ukrainian hryvnia"),
      "UGX" => $this->t("Ugandan shilling"),
      "USD" => $this->t("United States Dollar"),
      "UYI" => $this->t("Uruguay Peso en Unidades Indexadas"),
      "UYU" => $this->t("Uruguayan peso"),
      "UZS" => $this->t("Uzbekistan som"),
      "VEF" => $this->t("Venezuelan bolívar"),
      "VND" => $this->t("Vietnamese đồng"),
      "VUV" => $this->t("Vanuatu vatu"),
      "WST" => $this->t("Samoan tala"),
      "XAF" => $this->t("Central African CFA franc"),
      "XCD" => $this->t("East Caribbean Dollar"),
      "XOF" => $this->t("West African CFA franc"),
      "XPF" => $this->t("CFP franc"),
      "XXX" => $this->t("No currency"),
      "YER" => $this->t("Yemeni rial"),
      "ZAR" => $this->t("South African rand"),
      "ZMW" => $this->t("Zambian kwacha"),
      "ZWL" => $this->t("Zimbabwean Dollar"),
    ];
    return $currencies;
  }

}
