<?php

namespace Drupal\auctions_core\Plugin\Field\FieldFormatter;

use Drupal\auctions_core\AuctionToolsTrait;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldFormatter\DateTimeCustomFormatter;
use Drupal\datetime_range\DateTimeRangeTrait;

/**
 * Plugin implementation of the 'Auctions Countdown' formatter for 'daterange' fields.
 *
 * @FieldFormatter(
 *   id = "date_range_auctions_countdown",
 *   label = @Translation("Auctions Countdown Formatter"),
 *   field_types = {
 *     "daterange"
 *   }
 * )
 */
class DateRangeCountdownFormatter extends DateTimeCustomFormatter {

  use DateTimeRangeTrait;
  use AuctionToolsTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'display' => 'end_date',
      'hide_time' => FALSE,
      'hide_interval' => FALSE,
      'font_size' => '16',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      // To make this module compatible with optional_end_date
      // Deal with start_date and end date separately.
      if (!empty($item->start_date)) {
        /** @var \Drupal\Core\Datetime\DrupalDateTime $start_date */
        $dateFormat = \Drupal::service('date.formatter');
        $useDateSegment = $this->getSetting('display');

        // Start, assume there is only a start.
        $start_date = $item->start_date->getTimestamp();
        $formatted = $dateFormat->format($start_date, 'long');
        $introPhrase = $this->t('Auction Starts:  @formatted.', ['@formatted' => $formatted]);

        // Process end date.
        if (!empty($item->end_date) && $useDateSegment == 'end_date') {
          /** @var \Drupal\Core\Datetime\DrupalDateTime $end_date */
          $end_date = $item->end_date->getTimestamp();
          $formatted = $dateFormat->format($end_date, 'long');
          $introPhrase = $this->t('Auction Ends:  @formatted.', ['@formatted' => $formatted]);
        }

        $elements[$delta] = [
          '#theme' => 'auction_time',
          '#datetime' => $dateFormat->format($$useDateSegment, 'Y-m-d H:i:s'),
          '#formatted' => $introPhrase,
          '#unix' => $dateFormat->format($$useDateSegment, 'custom', 'U'),
          '#font_size' => $this->getSetting('font_size'),
          '#hide_time' => $this->getSetting('hide_time'),
          '#hide_interval' => $this->getSetting('hide_interval'),
        ];

      }
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['display'] = [
      '#type' => 'select',
      '#options' => [
        'start_date' => $this->t('Start of Range'),
        'end_date' => $this->t('End of Range'),
      ],
      '#title' => t('Date format for the single day date range'),
      '#default_value' => $this->getSetting('display') ? : 'end_date',
    ];
    $form['hide_time'] = [
      '#type' => 'checkbox',
      '#title' => t('Hide Time'),
      '#default_value' => $this->getSetting('hide_time'),
    ];
    $form['hide_interval'] = [
      '#type' => 'checkbox',
      '#title' => t('Hide Interval Phrasing'),
      '#default_value' => $this->getSetting('hide_interval'),
    ];
    $form['font_size'] = [
      '#type' => 'number',
      '#required' => TRUE,
      '#min' => 6,
      '#max' => 256,
      '#field_suffix' => 'px',
      '#title' => t('Font Size of Digits'),
      '#default_value' => $this->getSetting('font_size') ? : '16',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = $this->t('Countdown displays: @display. Font Size: @fontpx',
        [
          '@display' => $this->getSetting('display') ? : 'end_value',
          '@font' => $this->getSetting('font_size') ? : '16',
        ]
      );

    return $summary;
  }

}
