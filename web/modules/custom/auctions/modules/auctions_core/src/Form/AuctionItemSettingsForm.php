<?php

namespace Drupal\auctions_core\Form;

use Drupal\auctions_core\Service\AuctionTools;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form for configuring auction item settings.
 *
 * @ingroup auctions_core
 */
class AuctionItemSettingsForm extends ConfigFormBase {

  /**
   * The AuctionTools service.
   *
   * @var \Drupal\auctions_core\Service\AuctionTools
   */
  protected $auctionTools;

  /**
   * Constructs a new AuctionItemSettingsForm object.
   *
   * @param \Drupal\auctions_core\Service\AuctionTools $auctionTools
   *   The AuctionTools service.
   */
  public function __construct(AuctionTools $auctionTools) {
    $this->auctionTools = $auctionTools;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('auctions_core.tools')
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'auction_item_entity_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'auctions.item_settings',
    ];
  }

  /**
   * Defines the settings form for Auction Items entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('auctions.item_settings');
    $hasCommercePrice = $this->auctionTools->hasModule('commerce_price');
    $form['#attributes']['novalidate'] = 'novalidate';

    $form['refresh'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Bid Refresh'),
      '#description' => $this->t('Reminder:  The permission to <b>Add Auction Bid(s) entities | <q>Access Bidding Form</q></b> is needed also for ROLES to access this Json Callback Route.'),
    ];

    $form['refresh']['ajax-refresh'] = [
      '#title' => $this->t('Enable Global Freature'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('ajax-refresh'),
    ];
    $form['refresh']['container'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Bid Refresh Options'),
      '#states' => [
        'visible' => [
          ':input[name*="ajax-refresh"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['refresh']['container']['ajax-preactivate'] = [
      '#title' => $this->t('Pre-active refreshing of Current Bid Price.'),
      '#description' => $this->t('Reminder:  This may increase Server Load.'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('ajax-preactivate'),
    ];
    $form['refresh']['container']['ajax-rate'] = [
      '#title' => $this->t('Ajax Promise refresh rate'),
      '#type' => 'number',
      '#required' => TRUE,
      '#field_suffix' => $this->t('Seconds'),
      '#min' => .5,
      '#max' => 60,
      '#step' => 'any',
      '#default_value' => $config->get('ajax-rate'),
      '#description' =>
      '<ul>'
      .'<li>'.$this->t('Current Value for Refresh Event setTimeout:  @msms', ['@ms' => $config->get('ajax-rate')*1000])
      .'<li>'.$this->t('Min allowed:  .5 Sec').'</li>'
      .'<li>'.$this->t('Max allowed:  1 Min (60sec)').'</li>'
      .'</ul>'
      ,
    ];
 $endIncrease = $config->get('refresh-adrenaline') ?? 60;
 $form['refresh']['container']['refresh-adrenaline'] = [
      '#title' => $this->t('Refresh Adrenaline (Intensify rate as Countdown Runs Out)'),
      '#type' => 'number',
      '#required' => TRUE,
      '#field_suffix' => $this->t('Seconds left of Countdown'),
      '#min' => 1,
      '#max' => 3600,
      '#step' => 1,
      '#default_value' => $endIncrease ,
      '#description' => '<ul>'
      .'<li>'.$this->t('Refresh rate will drop to Every Second when @seconds from the end of the countdown.', ['@seconds'=>\Drupal::translation()->formatPlural((int)$endIncrease, '1 second', '@count seconds') ]).'</li>'
      .'<li>'.$this->t('Min allowed:  1 Second').'</li>'
      .'<li>'.$this->t('Max allowed:   3600 Seconds (5min)').'</li>'
      .'</ul>'
    ];

    $form['common'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Currency Settings'),
    ];
    $form['common']['commerce-price-active'] = [
      '#access' => $hasCommercePrice,
      '#markup' => '<br><dl><dt>' . $this->t('Commerce Price is active.') . '</dt>' .
      '<dd>' . $this->t('Be sure to match Currencies with store settings: admin/commerce/config/currencies') . '</dd></dl>',
    ];
    // reminder:  handle language character!
    $form['common']['dollar-symbol'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Dollar Symbol'),
      '#default_value' => $config->get('dollar-symbol'),
      '#maxlength' => 1,
      '#size' => 1,
    ];
    $form['common']['thousand-separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Thousand separator'),
      '#default_value' => $config->get('thousand-separator'),
      '#maxlength' => 1,
      '#size' => 1,
    ];
    $form['common']['decimal-separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Decimal separator'),
      '#default_value' => $config->get('decimal-separator'),
      '#maxlength' => 1,
      '#size' => 1,
    ];

    $form['common']['currency'] = [
      '#type' => 'details',
      '#title' => $this->t('Auction Item Currency'),
    ];
    $form['common']['currency']['active-currency'] = [
      '#title' => $this->t('Select currencies to allow for Auction Items.'),
      '#type' => 'checkboxes',
      '#required' => TRUE,
      '#default_value' => $config->get('active-currency'),
      '#options' => $this->auctionTools->currencyOptions(),
    ];
    $form['common']['commerce-price-active'] = [
      '#access' => $hasCommercePrice,
      '#markup' => '<br><dl><dt>' . $this->t('Commerce Price is active.') . '</dt>' .
      '<dd>' . $this->t('Adjust active Store currencies as needed admin/commerce/config/currencies') . '</dd></dl>',
    ];

    $form['date'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Auctions Dates'),
    ];
    $dateFormat = \Drupal::entityTypeManager()
      ->getStorage('date_format')
      ->loadMultiple();
    $format_list = [];
    foreach($dateFormat as $id => $format){
      $format_list[$id] = '['.$id.'] '.$format->label();
    }
    $form['date']['use_format'] = [
      '#type' => 'select',
      '#options' => $format_list,
      '#title' => $this->t('Countdown Date Formatter'),
      '#default_value' => $config->get('use_format'),
     '#description' => $this->t('Current availble formats:  @link', ['@link'=>Link::createFromRoute('Date and time formats', 'entity.date_format.collection')->toString()])
    ];

    $range = \range(1, 10);
    $years = \array_combine($range, $range);
    $form['date']['daterange']['years-ahead'] = [
      '#title' => $this->t('How many years forward can a user list an auction item'),
      '#type' => 'select',
      '#required' => TRUE,
      '#default_value' => $config->get('years-ahead'),
      '#options' => $years,
    ];

    $form['autobid'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Autobidding'),
    ];
    $form['autobid']['autobid-mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Global Feature.'),
      '#default_value' => $config->get('autobid-mode'),
    ];
    $form['autobid']['trigger'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Trigger Weight Logic'),
      '#states' => [
        'visible' => [
          ':input[name*="autobid-mode"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $range = \range(1, 1056);
    $slice = ['0' => $this->t('All/Unlimited')] + \array_combine($range, $range);
    $form['autobid']['trigger']['autobid-slice'] = [
      '#type' => 'select',
      '#options' => $slice,
      '#title' => $this->t('Autobid User Limit'),
      '#description' => $this->t('Help reduce flooding.'),
      '#default_value' => $config->get('autobid-slice'),
    ];
    $form['autobid']['trigger']['autobids-ordering'] = [
      '#type' => 'radios',
      '#required' => TRUE,
      '#title' => $this->t('Trigger Autobid By'),
      '#options' => [
        'created' => $this->t('Date Created'),
        'changed' => $this->t('Date Last Updated'),
        'amount_max' => $this->t('Max Amount'),
      ],
      '#default_value' => $config->get('autobids-ordering'),
    ];
    $form['autobid']['trigger']['autobids-direction'] = [
      '#type' => 'radios',
      '#required' => TRUE,
      '#title' => $this->t('Final Sort'),
      '#options' => [
        'ASC' => $this->t('Ascending'),
        'DESC' => $this->t('Desending'),
      ],
      '#default_value' => $config->get('autobids-direction'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('auctions.item_settings');

    $config->set('ajax-refresh', $form_state->getValue(['ajax-refresh']));
    $config->set('ajax-rate', $form_state->getValue(['ajax-rate']));
    $config->set('ajax-preactivate', $form_state->getValue(['ajax-preactivate']));
    $config->set('refresh-adrenaline', $form_state->getValue(['refresh-adrenaline']));

    $config->set('active-currency', $form_state->getValue(['active-currency']));
    $config->set('decimal-separator', $form_state->getValue(['decimal-separator']));
    $config->set('dollar-symbol', $form_state->getValue(['dollar-symbol']));
    $config->set('thousand-separator', $form_state->getValue(['thousand-separator']));

    $config->set('use_format', $form_state->getValue(['use_format']));
    $config->set('years-ahead', $form_state->getValue(['years-ahead']));

    $config->set('autobid-mode', $form_state->getValue(['autobid-mode']));
    $config->set('autobids-ordering', $form_state->getValue(['autobids-ordering']));
    $config->set('autobids-direction', $form_state->getValue(['autobids-direction']));
    $config->set('autobid-slice', $form_state->getValue(['autobid-slice']));

    $config->save();
    parent::submitForm($form, $form_state);
  }

}
