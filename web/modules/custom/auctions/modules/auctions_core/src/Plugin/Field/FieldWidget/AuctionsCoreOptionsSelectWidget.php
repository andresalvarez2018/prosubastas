<?php

namespace Drupal\auctions_core\Plugin\Field\FieldWidget;

use Drupal\auctions_core\Entity\AuctionBid;
use Drupal\auctions_core\Entity\AuctionItem;
use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'auctions_core_options_select' widget.
 *
 * @FieldWidget(
 *   id = "auctions_core_options_select",
 *   label = @Translation("Auctions Core | Select list"),
 *   field_types = {
 *     "entity_reference",
 *     "list_integer",
 *     "list_float",
 *     "list_string"
 *   },
 *   multiple_values = TRUE
 * )
 */
class AuctionsCoreOptionsSelectWidget extends OptionsWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $entity = $items->getEntity();
    $fieldName = $items->getName();
    $options = $this->getOptions($entity);
    $type = $entity->getEntityType();
    $ogleFields = [
      'field_order_bid',
      'field_order_item',
    ];
    if (\in_array($fieldName, $ogleFields)) {
      switch ($type->id()) {
        case 'commerce_order_item':
          // Reminder:  auciton_bid does not have a name/title base.
          foreach ($options as $k => $v) {
            $option = $v;
            if (\is_numeric($k)) {
              switch ($fieldName) {
                case 'field_order_bid':
                  $bid = AuctionBid::load($k);
                  $item = $bid->getItemEntity();
                  $option = $this->t('@amount | @id | relist:@relist | @name', [
                    '@amount' => $bid->getAmount(),
                    '@id' => $k,
                    '@relist' => $bid->getRelistGroup(),
                    '@name' => ($item) ? $item->getName() : $this->t('Detached ID @id', [
                      '@id' => $bid->getItemId(),
                    ]),
                  ]);
                  break;

                case 'field_order_item':
                  $item = AuctionItem::load($k);

                  $option = $this->t('@id @name', [
                    '@id' => $k,
                    '@name' => $item->getName(),

                  ]);

                  break;
              }
            }
            $options[$k] = $option;
          }
          break;

      }
    }

    $element += [
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $this->getSelectedOptions($items),
      // Do not display a 'multiple' select box if there is only one option.
      '#multiple' => $this->multiple && count($this->options) > 1,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function sanitizeLabel(&$label) {
    // Select form inputs allow unencoded HTML entities, but no HTML tags.
    $label = Html::decodeEntities(strip_tags($label));
  }

  /**
   * {@inheritdoc}
   */
  protected function supportsGroups() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues($values, array $form, FormStateInterface $form_state) {
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEmptyLabel() {
    if ($this->multiple) {
      // Multiple select: add a 'none' option for non-required fields.
      if (!$this->required) {
        return $this->t('- None -');
      }
    }
    else {
      // Single select: add a 'none' option for non-required fields,
      // and a 'select a value' option for required fields that do not come
      // with a value selected.
      if (!$this->required) {
        return $this->t('- None -');
      }
      if (!$this->has_value) {
        return $this->t('- Select a value -');
      }
    }
  }

}
