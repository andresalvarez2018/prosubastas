<?php

namespace Drupal\auctions_core\Form;

use Drupal\auctions_core\AuctionToolsTrait;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Auction Bid(s) edit forms.
 *
 * @ingroup auctions_core
 */
class AuctionBidRejectForm extends ContentEntityForm {

  use AuctionToolsTrait;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    $instance = parent::create($container);
    $instance->account = $container->get('current_user');
    $instance->messenger = $container->get('messenger');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\auctions_core\Entity\AuctionBid $entity */

    if ($this->account->isAnonymous()) {
      $form['item']['#access'] = FALSE;
      unset($form['actions']);
      $form['#suffix'] = $this->t('Sorry, you are not a user:  <ul><li>@login</li><li>@register</li></ul>', [
        '@login' => Link::createFromRoute($this->t('Login'), 'user.login')->toString(),
        '@register' => Link::createFromRoute($this->t('Register'), 'user.register')->toString(),
      ]);

      return $form;
    }

    // If not anon, continue...
    $form = parent::buildForm($form, $form_state);

    // Can not add a bit as another user.
    $form['user_id']['widget']['#disabled'] = TRUE;

    // @todo @form load -- if is now after end time force item to finished workflow.
    $form['purchase_offer']['widget']['#access'] = FALSE;

    // Cast bid is not to be re-edited.  makes no sence.
    if (!$this->entity->isNew()) {
      $this->messenger()->addError("Bids can not be edited.");

      $form['item']['widget'][0]['target_id']['#disabled'] = TRUE;
      $form['user_id']['widget'][0]['target_id']['#disabled'] = TRUE;
      $form['type']['widget']['#disabled'] = TRUE;
      $form['amount']['widget']['#disabled'] = TRUE;
      // Remove buttons.
      unset($form['actions']);
    }
    $form['amount']['widget'][0]['value']['#min'] = 0;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $item = $entity->getItemEntity();

    // Set corresponding relist count.
    $relistCount = $entity->getItemRelistCount();
    $entity->setRelistGroup($relistCount);

    // If bid is instant -- close item.
    $type = $entity->getType();
    if ($type == 'instant') {
      $item->setWorkflow(3);
      $item->save();
    }

    // Round off amound.
    $price = $form_state->getValue('amount');
    $roundPrice = $this->roundCents($price[0]['value']);
    $entity->setAmount($roundPrice);

    // Now save form (produce a BID ID).
    $status = parent::save($form, $form_state);

    $isNew = $entity->isNew();
    // Handle corresponding entity reference between bids<>item.
    if ($isNew) {
      $item->get('bids')->appendItem([
        'target_id' => $entity->id(),
      ]);
      $item->save();
    }

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Auction Bid.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Auction Bid.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.auction_bid.canonical', ['auction_bid' => $entity->id()]);
  }

}
