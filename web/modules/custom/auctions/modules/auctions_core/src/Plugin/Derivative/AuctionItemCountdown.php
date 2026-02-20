<?php

namespace Drupal\auctions_core\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\language\ConfigurableLanguageManagerInterface;

/**
 * Provides AuctionItemCountdown block deriver (for future use).
 */
class AuctionItemCountdown extends DeriverBase {

  use StringTranslationTrait;

  /**
   * List of derivative definitions.
   *
   * @var array
   */
  protected $derivatives = [];

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $language_manager = \Drupal::languageManager();

    if ($language_manager instanceof ConfigurableLanguageManagerInterface) {
      $info = $language_manager->getDefinedLanguageTypesInfo();
      $configurable_types = $language_manager->getLanguageTypes();
      foreach ($configurable_types as $type) {
        $this->derivatives[$type] = $base_plugin_definition;
        $this->derivatives[$type]['admin_label'] = $this->t('Auction Item Countdown Timer (@type)', ['@type' => $info[$type]['name']]);
      }
      // If there is just one configurable type then change the title of block.
      if (count($configurable_types) == 1) {
        $this->derivatives[reset($configurable_types)]['admin_label'] = $this->t('Auction Item Countdown Timer');
      }
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
