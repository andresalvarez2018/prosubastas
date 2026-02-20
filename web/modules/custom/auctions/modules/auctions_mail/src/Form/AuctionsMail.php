<?php

namespace Drupal\auctions_mail\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures forms module settings.
 */
class AuctionsMail extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'auctions_mail_content';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'auctions_mail.content',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('auctions_mail.content');

    $form['email-from'] = [
      '#type' => 'email',
      '#required' => TRUE,
      '#title' => $this->t('Email <q>from</q> address'),
      '#default_value' => $config->get('email-from'),
    ];
    $form['email-replyto'] = [
      '#type' => 'email',
      '#required' => TRUE,
      '#title' => $this->t('Email <q>replyTo</q> Address'),
      '#default_value' => $config->get('email-replyto'),
    ];

    $form['bid-placed-notify-author'] = [
      '#type' => 'details',
      '#title' => $this->t('New Bid — Notifiy Owner'),
    ];
    $form['bid-placed-notify-author']['subject-author'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Subject Line'),
      '#default_value' => $config->get('subject-author'),
    // 'subject' => 'Bid placed in your auction.',
    ];
    $form['bid-placed-notify-author']['body-author'] = [
      '#type' => 'text_format',
      '#format' => $config->get('body-author')['format'],
      '#allowed_formats' => ['auction_html'],
      '#required' => TRUE,
      '#title' => $this->t('Message'),
      '#default_value' => $config->get('body-author')['value'],
      '#description' => $this->t('use <q>@auction</q> to add link to Auction Page.'),
    ];

    $form['bid-placed-notify-bidders'] = [
      '#type' => 'details',
      '#title' => $this->t('New Bid — Notify Bidders'),
    ];
    $form['bid-placed-notify-bidders']['subject-bidder'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Subject Line'),
      '#default_value' => $config->get('subject-bidder'),
    ];
    $form['bid-placed-notify-bidders']['body-bidder'] = [
      '#type' => 'text_format',
      '#format' => $config->get('body-bidder')['format'],
      '#allowed_formats' => ['auction_html'],
      '#required' => TRUE,
      '#title' => $this->t('Message'),
      '#default_value' => $config->get('body-bidder')['value'],
      '#description' => $this->t('use <q>@auction</q> to add link to Auction Page.'),
    ];

    $form['item-closed-notify-owner'] = [
      '#type' => 'details',
      '#title' => $this->t('Auction Item Closed - Notify Owner'),
    ];
    $form['item-closed-notify-owner']['subject-closed-owner'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Owner Subject Line'),
      '#default_value' => $config->get('subject-closed-owner'),
    ];

    $form['item-closed-notify-owner']['body-closed-owner'] = [
      '#type' => 'text_format',
      '#format' => $config->get('body-closed-owner')['format'],
      '#allowed_formats' => ['auction_html'],
      '#required' => TRUE,
      '#title' => $this->t('Owner Message'),
      '#default_value' => $config->get('body-closed-owner')['value'],
      '#description' => $this->t('use <q>@auction</q> to add link to Auction Page.'),
    ];

    $form['item-closed-notify-owner']['subject-closed-owner-winner'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Winner Subject Line: '),
      '#default_value' => $config->get('subject-closed-owner-winner'),
    ];

    $form['item-closed-notify-owner']['body-closed-owner-winner'] = [
      '#type' => 'text_format',
      '#format' => $config->get('body-closed-owner-winner')['format'],
      '#allowed_formats' => ['auction_html'],
      '#required' => TRUE,
      '#title' => $this->t('Winner Message'),
      '#default_value' => $config->get('body-closed-owner-winner')['value'],
      '#description' => $this->t(
        'Use <q>@auction</q> to add link to Auction Page and <q>@user</q> for
        the Winner.'
      ),
    ];

    $form['item-closed-notify-bidder'] = [
      '#type' => 'details',
      '#title' => $this->t('Auction Item Closed - Notify Bidders'),
    ];

    $form['item-closed-notify-bidder']['subject-closed-bidder'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Subject Line'),
      '#default_value' => $config->get('subject-closed-bidder'),
    ];
    $form['item-closed-notify-bidder']['body-closed-bidder'] = [
      '#type' => 'text_format',
      '#format' => $config->get('body-closed-bidder')['format'],
      '#allowed_formats' => ['auction_html'],
      '#required' => TRUE,
      '#title' => $this->t('Message'),
      '#default_value' => $config->get('body-closed-bidder')['value'],
      '#description' => $this->t('use <q>@auction</q> to add link to Auction Page.'),
    ];

    $form['item-closed-notify-winner'] = [
      '#type' => 'details',
      '#title' => $this->t('Auction Item Closed - Notify Winner'),
    ];

    $form['item-closed-notify-winner']['subject-closed-winner'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Subject Line'),
      '#default_value' => $config->get('subject-closed-winner'),
    ];
    $form['item-closed-notify-winner']['body-closed-winner'] = [
      '#type' => 'text_format',
      '#format' => $config->get('body-closed-bidder')['format'],
      '#allowed_formats' => ['auction_html'],
      '#required' => TRUE,
      '#title' => $this->t('Message'),
      '#default_value' => $config->get('body-closed-winner')['value'],
      '#description' => $this->t('use <q>@auction</q> to add link to Auction Page.'),
    ];

    $form['item-relisted-notify-owner'] = [
      '#type' => 'details',
      '#title' => $this->t('Auction Item Relisted - Notify Owner'),
    ];
    $form['item-relisted-notify-owner']['subject-relisted-owner'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Subject Line'),
      '#default_value' => $config->get('subject-relisted-owner'),
    ];

    $form['item-relisted-notify-owner']['body-relisted-owner'] = [
      '#type' => 'text_format',
      '#format' => $config->get('body-relisted-owner')['format'],
      '#allowed_formats' => ['auction_html'],
      '#required' => TRUE,
      '#title' => $this->t('Message'),
      '#default_value' => $config->get('body-relisted-owner')['value'],
      '#description' => $this->t('use <q>@auction</q> to add link to Auction Page.'),
    ];

    $form['item-relisted-notify-bidder'] = [
      '#type' => 'details',
      '#title' => $this->t('Auction Item Relisted - Notify Bidders'),
    ];

    $form['item-relisted-notify-bidder']['subject-relisted-bidder'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Subject Line'),
      '#default_value' => $config->get('subject-relisted-bidder'),
    ];
    $form['item-relisted-notify-bidder']['body-relisted-bidder'] = [
      '#type' => 'text_format',
      '#format' => $config->get('body-relisted-bidder')['format'],
      '#allowed_formats' => ['auction_html'],
      '#required' => TRUE,
      '#title' => $this->t('Message'),
      '#default_value' => $config->get('body-relisted-bidder')['value'],
      '#description' => $this->t('use <q>@auction</q> to add link to Auction Page.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('auctions_mail.content')
      ->set('email-from', $form_state->getValue('email-from'))
      ->set('email-replyto', $form_state->getValue('email-replyto'))

      ->set('subject-author', $form_state->getValue('subject-author'))
      ->set('body-author', $form_state->getValue('body-author'))
      ->set('subject-bidder', $form_state->getValue('subject-bidder'))
      ->set('body-bidder', $form_state->getValue('body-bidder'))

      ->set('subject-closed-owner', $form_state->getValue('subject-closed-owner'))
      ->set('body-closed-owner', $form_state->getValue('body-closed-owner'))
      ->set('subject-closed-owner-winner', $form_state->getValue('subject-closed-owner-winner'))
      ->set('body-closed-owner-winner', $form_state->getValue('body-closed-owner-winner'))
      ->set('subject-closed-bidder', $form_state->getValue('subject-closed-bidder'))
      ->set('body-closed-bidder', $form_state->getValue('body-closed-bidder'))
      ->set('subject-closed-winner', $form_state->getValue('subject-closed-winner'))
      ->set('body-closed-winner', $form_state->getValue('body-closed-winner'))

      ->set('subject-relisted-owner', $form_state->getValue('subject-relisted-owner'))
      ->set('body-relisted-owner', $form_state->getValue('body-relisted-owner'))
      ->set('subject-relisted-bidder', $form_state->getValue('subject-relisted-bidder'))
      ->set('body-relisted-bidder', $form_state->getValue('body-relisted-bidder'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
