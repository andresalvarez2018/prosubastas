<?php

namespace Drupal\auctions_core\Form;

use Drupal\auctions_core\AuctionToolsTrait;
use Drupal\auctions_core\Entity\AuctionAutobidInterface;
use Drupal\auctions_core\Entity\AuctionItem;
use Drupal\auctions_core\Plugin\Validation\Constraint\CurbBids;
use Drupal\auctions_core\Service\AuctionTools;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class HelloForm.
 */
class BiddersForm extends FormBase {

  use AuctionToolsTrait;
  /**
   * Drupal\Core\Messenger\MessengerInterface definition.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;
  /**
   * Entity Type Manager.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * AuctionTools.
   *
   * @var Drupal\auctions_core\Service\AuctionTools
   */
  protected $auctionTools;

  /**
   * Core module handler.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  public $configFactory;

  /**
   * Constructs a new BiddersForm object.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger Factory.
   * @param Drupal\Core\Session\AccountInterface $currentUser
   *   Account Interface.
   * @param Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity Type Manager.
   * @param \Drupal\auctions_core\Service\AuctionTools $auctionTools
   *   AuctionTools service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Configuration Factory.
   */
  public function __construct(MessengerInterface $messenger, AccountInterface $currentUser, EntityTypeManagerInterface $entityTypeManager, AuctionTools $auctionTools, ConfigFactoryInterface $configFactory) {
    $this->messenger = $messenger;
    $this->currentUser = $currentUser;
    $this->entityTypeManager = $entityTypeManager;
    $this->auctionTools = $auctionTools;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('auctions_core.tools'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'auctions_core_bidders';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $auctionItem = NULL) {
    $auctionConf = $this->configFactory()->getEditable('auctions.item_settings');
    $dollar = $auctionConf->get('dollar-symbol');
    $thous = $auctionConf->get('thousand-separator');
    $dec = $auctionConf->get('decimal-separator');
    $item = ( !empty($auctionItem[0]) && ($auctionItem[0] instanceof AuctionItem)) ? $auctionItem[0] : FALSE;

    $currency = '<small>' . $item->getCurrencyCode() . '</small>';
    if ($item) {
      // Send only id, refresh data.
      $form_state->setFormState([
        'auction_item_id' => $item->getId(),
      ]);

      $hasBuyNow = $item->hasBuyNow();
      $instantOnly = $item->getInstantOnly();
      $canBid = $this->currentUser->hasPermission('add auction bids entities');
      if ( !$canBid) {
        $form['permission-denied'] = [
          '#type' => 'inline_template',
          '#template' => '<p>{{ message }}</p>',
            '#context' => [
            'message' => $this->t('No puede pujar en este momento'),
          ],
        ];
      }
      elseif ($this->currentUser->isAnonymous()) {

        $destinationUrl = Url::fromRoute('<current>');
        $loginLink = Link::createFromRoute($this->t('Iniciar sesión'), 'user.login', [], ['query' => ['destination' => $destinationUrl->toString()]])->toString();
        $form['is-anon'] = [
          '#type' => 'inline_template',
          '#template' => '<p>{{ message }}</p><ul><li>{{ login }}</li><li>{{ register }}</li></ul>',
          '#context' => [
            'message' => $this->t('Por favor, inicie sesión para poder pujar'),
            'login' => $loginLink,
            'register' => Link::createFromRoute($this->t('Registrarse'), 'user.register')->toString(),
          ],
        ];

      }
      else {
        if ($item->isClosed()) {
          $form['aution-closed'] = [
            '#type' => 'item',
            '#description' => $this->t('La subasta está cerrada.'),
          ];
        }
        elseif ($item->isOpen()) {
          $currentHightest = $item->seekCurrentHightest();
          $minPrice = $currentHightest['minPrice'];
          $selfBid = ($currentHightest['leadBid'] && $currentHightest['leadBid']->getOwnerId() == $this->currentUser->id()) ? TRUE : FALSE;
          // reminder: add a header for general display of related meta bubble:
          // - relist count.
          // - autobidders count.
          $form['welcome'] = [
            '#type' => 'item',
            '#access' => !$instantOnly,
            '#attributes' => ['class' => ['current-price-wrapper']],
            '#title' => $this->t('Precio actual'),
            '#description' => $dollar . $this->showAsCents($minPrice, $dec, $thous) . ' ' . $currency,
          ];

          $hasAutobid = $this->auctionTools->seekAutobid(
            $this->currentUser->id(),
            $item->id(),
            $item->getRelistCount()
          );

          $currentAutobid = FALSE;
          $automaxTitle = $this->t('Su máximo de puja automática');
          $autoBidOptInTitle = $this->t('Activar puja automática.');

          if ($hasAutobid instanceof AuctionAutobidInterface) {
            if ($hasAutobid->getAmountMax() > $item->seekCurrentHightest()['minPrice']) {
              $currentAutobid = $hasAutobid;
              $automaxTitle = $this->t('Ajuste su máximo de puja automática');
            }
            else {
              $currentAutobid = FALSE;
              $autoBidOptInTitle = $this->t(
                'Su puja automática ha sido superada. Volver a activar puja automática.'
              );
            }
          }

          $showAutobidding = !$instantOnly && $auctionConf->get('autobid-mode') == 1;
          $form['autobid'] = [
            '#type' => 'fieldset',
            '#access' => $this->currentUser->hasPermission('add auction autobid entities') && $showAutobidding,
            '#title' => $this->t('Puja automática'),
            '#attributes' => [
              'class' => [
                'autobid-wrapper',
              ],
            ],
          ];

          $amountMax = $currentAutobid ? $currentAutobid->getAmountMax() : ($minPrice + $auctionItem[0]->getBidStep());
          $form['autobid']['last-bidder'] = [
            '#type' => 'item',
            '#access' => $currentAutobid ? TRUE : FALSE,
            '#markup' => $this->t('Su máximo actual:'),
            '#description' => $dollar . $this->showAsCents($amountMax, $dec, $thous) . ' ' . $currency,
          ];

          $form['autobid']['autobid_opt'] = [
            '#type' => 'checkbox',
            '#title' => $autoBidOptInTitle,
            '#access' => !$currentAutobid,
            '#default_value' => $currentAutobid ? 1 : 0,
            '#states' => [
              'invisible' => [
                ':input[name*="autobid_opt"]' => ['checked' => TRUE],
              ],
            ],
          ];
          $form['autobid']['amount_max'] = [
            '#type' => 'number',
            '#field_prefix' => $dollar,
            '#title' => $automaxTitle,
            '#description' => $this->t('El incremento de puja de este artículo es @dollar@bidStep:', [
              '@dollar' => $dollar,
              '@bidStep' => $auctionItem[0]->getBidStep(),
            ]),
            '#step' => 'any',
            '#min' => $minPrice + ($item->getBidStep() * 2),
            '#states' => [
              'invisible' => [
                ':input[name*="autobid_opt"]' => ['checked' => FALSE],
              ],
              /* Reminder:  can't make required or browser validation will loop if opt-out btn is used. */
            ],
          ];
          if ($currentAutobid) {
            $form['autobid']['amount_max']['#access'] = FALSE;
          }

          $form['autobid']['opt_out'] = [
            '#type' => 'submit',
            '#name' => 'opt-out',
            '#access' => isset($currentAutobid) && $currentAutobid ? TRUE : FALSE,
            '#submit' => ['::optOutSubmit'],
            '#value' => $this->t('Desactivar mi puja automática'),
            '#attributes' => [
              'title' => $this->t('Cancelar la puja automática sin realizar una puja.'),
              'alt' => $this->t('Haga clic aquí para desactivar la puja automática sin realizar una puja.'),
            ],
            '#states' => [
              'invisible' => [
                ':input[name*="autobid_opt"]' => ['checked' => FALSE],
              ],
            ],
            '#ajax' => [
              'callback' => '::ajaxSubmit',
              'wrapper' => 'auctions-core-bidders-wrapper',
            ],
          ];

          $form['bids'] = [
            '#type' => 'container',
            '#access' => !$instantOnly && !$selfBid,
            '#attributes' => ['class' => ['standard-bid', 'clearfix']],
          ];

          $form['bids']['amount'] = [
            '#type' => 'number',
            '#field_prefix' => $dollar,
            '#required' => TRUE,
            '#field_suffix' => $currency,
            '#title' => $this->t('Su puja'),
            '#min' => ($minPrice + $item->getBidStep()),
            '#size' => 12,
            '#step' => 'any',
          ];
          $default = $minPrice + $item->getBidStep();
          $form['bids']['amount']['#default_value'] = $default;

          $form['bids']['submit'] = [
            '#type' => 'submit',
            '#name' => 'submit',
            '#value' => $this->t('Realizar puja'),
            '#ajax' => [
              'callback' => '::ajaxSubmit',
              'wrapper' => 'auctions-core-bidders-wrapper',
            ],
          ];

          $form['self-bid'] = [
            '#type' => 'item',
            '#access' => $selfBid,
            '#markup' => '<p>' . $this->t('Usted tiene la puja más alta actualmente.') . '</p>',
          ];

          $bidThreshold = $item->seekBidThreshold();
          $form['instant'] = [
            '#type' => $instantOnly ? 'fieldset' : 'details',
            '#open' => $instantOnly,
            '#title' => $this->t('Compra inmediata'),
            /*   todo: format from conf  . */
            '#markup' => $dollar . $this->showAsCents($item->getPriceBuyNow(), $dec, $thous) . ' ' . $currency,
            '#access' => ($instantOnly || $hasBuyNow) && !$bidThreshold,
            '#attributes' => [
              'class' => [
                'instant-bid',
              ],
            ],
          ];
          $form['instant']['buy_now_verify'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Sí, deseo comprar ahora'),
          ];
          $form['instant']['buy_now'] = [
            '#type' => 'submit',
            '#name' => 'buy-now',
            '#submit' => ['::buyNowSubmit'],
            '#validate' => ['::buyNowValidate'],
            '#value' => $this->t('¡Comprar ahora!'),
            '#states' => [
              'invisible' => [
                ':input[name*="buy_now_verify"]' => ['checked' => FALSE],
              ],
            ],
            '#ajax' => [
              'callback' => '::ajaxSubmit',
              'wrapper' => 'auctions-core-bidders-wrapper',
            ],
          ];

          $form['refresh'] = [
            '#type' => 'button',
            '#value' => $this->t('Actualizar'),
            '#name' => 'refresh-btn',
            '#attributes' => ['class' => ['auctions-core-refresh-btn']],
            '#ajax' => [
              'callback' => '::ajaxSubmit',
              'wrapper' => 'auctions-core-bidders-wrapper',
            ],
          ];

          if ($item->hasBids()) {
            $form['current_bids'] = views_embed_view('bids_relist_group', 'embed_1', $item->getRelistCount(), $item->id());
            $form['current_bids']['#weight'] = 100;
          }
        }
        else {
          $form['auction-open'] = [
            '#type' => 'item',
            '#description' => $this->t('La puja aún no está abierta.'),
          ];
        }

      }

    }
    $form['#prefix'] = '<div id="auctions-core-bidders-wrapper">';
    $form['#suffix'] = '</div>';

    $form['#attached']['library'][] = 'auctions_core/bidders';
    $form['#attached']['library'][] = 'auctions_core/refresh';
    $form['#cache']['max-age'] = 0;
    $form['#cache']['tags'][] = 'auction_item:' . $item->id();
    return $form;
  }

  /**
   * Ajax callback for the bidding form.
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    $response = new \Drupal\Core\Ajax\AjaxResponse();
    $response->addCommand(new \Drupal\Core\Ajax\ReplaceCommand('#auctions-core-bidders-wrapper', $form));
    $response->addCommand(new \Drupal\Core\Ajax\PrependCommand('#auctions-core-bidders-wrapper', ['#type' => 'status_messages']));
    return $response;
  }

  /**
   * Form validation handler for the Buy Now submission.
   *
   * This method checks if the Buy Now submission is valid and
   * displays errors if any validation checks fail.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function buyNowValidate(array &$form, FormStateInterface $form_state) {
    $curbBids = new CurbBids();
    $itemId = $form_state->get('auction_item_id');
    $item = \Drupal::entityTypeManager()->getStorage('auction_item')->load($itemId);
    if ($item->isClosed()) {
      $form_state->setErrorByName('instant-price', $curbBids->auctionHasClosed);
    }
    $verify = $form_state->getValues()['buy_now_verify'];
    if ($verify == 0) {
      $form_state->setErrorByName('buy_now_verify', $this->t('You must pre-verify <q>Buy Now</q> submission.'));
    }
  }

  /**
   * Form submission handler for opting out of Auto Bidding.
   *
   * This method handles the form submission when a user chooses to opt out of
   * Auto Bidding for a specific auction item.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function optOutSubmit(array &$form, FormStateInterface $form_state) {
    $itemId = $form_state->get('auction_item_id');
    $item = \Drupal::entityTypeManager()->getStorage('auction_item')->load($itemId);
    $this->auctionTools->removeAutobid($this->currentUser->id(), $itemId, $item->getRelistCount());
  }

  /**
   * {@inheritdoc}
   */
  public function buyNowSubmit(array &$form, FormStateInterface $form_state) {
    $itemId = $form_state->get('auction_item_id');
    $item = \Drupal::entityTypeManager()->getStorage('auction_item')->load($itemId);

    $bid = $this->auctionTools->handleBid(
      $this->currentUser->id(),
      $item,
      $item->getPriceBuyNow(),
      FALSE,
      TRUE
    );

    if ($bid->id()) {
      $this->messenger()->addMessage($this->t('Congratulations! You have placed an Instant Buy! $%price.', [
        '%price' => $this->showAsCents($item->getPriceBuyNow(), '.', ','),
      ]));
    }
    else {
      $this->messenger()->addError($this->t(
        'Something went wrong, please contact an Administrator.'
      ));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $triggerBtn = $form_state->getTriggeringElement()['#name'];
    if ($triggerBtn == 'submit') {

      $curbBids = new CurbBids();
      $itemId = $form_state->get('auction_item_id');
      $item = \Drupal::entityTypeManager()->getStorage('auction_item')->load($itemId);
      $itemRelist = $item->getRelistCount();
      $currentBids = $item->getBids($itemRelist, 3);
      $processBids = $item->summarizeBids($currentBids);
      $amount = \floatval($form_state->getValue('amount'));
      if ( !empty($processBids[0]) && $processBids[0]['uid'] == $this->currentUser->id()) {
        // Since this is the heaviest rule:  check for it first.
        $form_state->setErrorByName('amount', $curbBids->lastBidIsYours);
      }

      $itemPriceStarting = $item->getPriceStarting();
      $itemWorkflow = $item->getWorkflow();
      // Check if the value is an number.
      if ($itemWorkflow == 3 || $itemWorkflow == 4) {
        $form_state->setErrorByName('amount', $curbBids->auctionFinished);

      }
      if ($itemWorkflow == 0) {
        $form_state->setErrorByName('amount', $curbBids->auctionNew);
      }

      // If is past end time.  @page load vs submit post.
      $now = new DrupalDateTime('now');
      $auctionDates = $item->getDate();
      $getUserTimezone = date_default_timezone_get();
      $userTimezone = new \DateTimeZone($getUserTimezone);
      $auctionEnds = DrupalDateTime::createFromFormat('Y-m-d\TH:i:s', $auctionDates['end'], $this->auctionDefaultTimeZone())->setTimezone($userTimezone)->format('U');
      if ($now->format('U') > $auctionEnds) {
        $form_state->setErrorByName('amount', $curbBids->auctionHasExpired);
      }
      // If isn't higher than last/threshold.
      $highestCurrent = $item->seekCurrentHightest();
      if (( !empty($processBids[0]) && $amount < $processBids[0]['amount'])
        ||
        ( !($amount > ($highestCurrent['minPrice'])))
      ) {
        $form_state->setErrorByName('amount', $curbBids->higherThanLastBid);
      }

      // Autobid.
      $hasAutobid = $this->auctionTools->seekAutobid($this->currentUser->id(), $item->id(), $itemRelist);
      $currentAutobid = FALSE;
      if ($hasAutobid instanceof AuctionAutobidInterface) {
        $currentAutobid = $hasAutobid;
      }

      $autobidOpt = $form_state->getValue('autobid_opt');
      // Reminder:  all form buttons should have unique #name.
      $triggerBtn = $form_state->getTriggeringElement()['#name'];
      $amountMax = $this->roundCents((float) $form_state->getValue('amount_max'));
      if ($triggerBtn != 'opt-out'
        && $autobidOpt == 1
        && ($currentAutobid && $amountMax != 0)
        && $amountMax < $highestCurrent['minPrice']
      ) {
        $form_state->setErrorByName('amount_max', $this->t('Your Max autobid has to be higher than current highest bid. Try a higher amount or click <q>Opt Out</q> .'));
      }
      parent::validateForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $itemId = $form_state->get('auction_item_id');
    $item = \Drupal::entityTypeManager()->getStorage('auction_item')->load($itemId);
    $autobidOpt = $form_state->getValue('autobid_opt');
    // Reminder:  all form buttons should have unique #name.
    $amountMax = \floatval($form_state->getValue('amount_max'));
    if ($autobidOpt == 1 && $amountMax != 0) {
      // Process autobid.
      $this->auctionTools->handleAutobid(
        $this->currentUser->id(),
        $item->id(),
        $item->getRelistCount(),
        $amountMax
      );
    }

    $amount = \floatval($form_state->getValue('amount'));
    $bid = $this->auctionTools->handleBid(
      $this->currentUser->id(),
      $item,
      $amount
    );

    if ($bid->id()) {
      $this->messenger()->addMessage($this->t('Congratulations! You have placed your bid! $%price.', [
        '%price' => $this->showAsCents($amount, '.', ','),
      ]));
    }
    else {
      $this->messenger()->addError($this->t('Bidding failed.'));
    }
  }

}
