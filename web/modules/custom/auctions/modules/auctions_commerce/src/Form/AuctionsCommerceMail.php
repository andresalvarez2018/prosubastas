<?php

namespace Drupal\auctions_commerce\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures forms module settings.
 */
class AuctionsCommerceMail extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'auctions_commerce_mail';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'auctions_commerce.mail',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('auctions_commerce.mail');

    $form['auction-order-item-bidder'] = [
      '#type' => 'details',
      '#title' => $this->t('Notify Winnder:  Order Item in cart'),
    ];
    $form['auction-order-item-bidder']['subject-bidder'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Subject Line'),
      '#default_value' => $config->get('subject-bidder'),
    ];
    $form['auction-order-item-bidder']['From/replyTo'] = [
      '#type' => 'item',
      '#title' => $this->t('From:'),
      '#description' => $this->t('Note:  From email is taken form Store email.'),
    ];
    $form['auction-order-item-bidder']['body-bidder'] = [
      '#type' => 'text_format',
      '#format' => $config->get('body-bidder')['format'],
      '#allowed_formats' => ['auction_html'],
      '#required' => TRUE,
      '#title' => $this->t('Message'),
      '#default_value' => $config->get('body-bidder')['value'],
      '#description' => $this->t('use <q>@cart</q> to add link to Commerce Card (link title by Store Name).'),
    ];

    $form['auction-order-item-owner'] = [
      '#type' => 'details',
      '#title' => $this->t('Notify Item Owner'),
    ];
    $form['auction-order-item-owner']['subject-owner'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Subject Line'),
      '#default_value' => $config->get('subject-owner'),
    ];
    $form['auction-order-item-owner']['From/replyTo'] = [
      '#type' => 'item',
      '#title' => $this->t('From:'),
      '#description' => $this->t('Note:  From email is taken form Store email.'),
    ];
    $form['auction-order-item-owner']['body-owner'] = [
      '#type' => 'text_format',
      '#format' => $config->get('body-owner')['format'],
      '#allowed_formats' => ['auction_html'],
      '#required' => TRUE,
      '#title' => $this->t('Message'),
      '#default_value' => $config->get('body-owner')['value'],
    ];

    $form['auction-order-item-removed'] = [
      '#type' => 'details',
      '#title' => $this->t('Auction Order Item Removed from Cart'),
    ];
    $form['auction-order-item-removed']['subject-removed'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Subject Line'),
      '#default_value' => $config->get('subject-removed'),
    ];
    $form['auction-order-item-removed']['From/replyTo'] = [
      '#type' => 'item',
      '#title' => $this->t('From:'),
      '#description' => $this->t('Note:  From email is taken form Store email.'),
    ];
    $form['auction-order-item-removed']['body-removed'] = [
      '#type' => 'text_format',
      '#format' => $config->get('body-removed')['format'],
      '#allowed_formats' => ['auction_html'],
      '#required' => TRUE,
      '#title' => $this->t('Message'),
      '#default_value' => $config->get('body-removed')['value'],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('auctions_commerce.mail')
      ->set('subject-bidder', $form_state->getValue('subject-bidder'))
      ->set('body-bidder', $form_state->getValue('body-bidder'))

      ->set('subject-owner', $form_state->getValue('subject-owner'))
      ->set('body-owner', $form_state->getValue('body-owner'))

      ->set('subject-removed', $form_state->getValue('subject-removed'))
      ->set('body-removed', $form_state->getValue('body-removed'))

      ->save();
    parent::submitForm($form, $form_state);
  }

}
