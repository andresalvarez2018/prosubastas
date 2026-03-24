<?php

namespace Drupal\auctions_core\Controller;

use Drupal\auctions_core\Entity\AuctionItemInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AuctionItemController.
 *
 *  Returns responses for Auction Items routes.
 */
class AuctionItemController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->dateFormatter = $container->get('date.formatter');
    $instance->renderer = $container->get('renderer');
    return $instance;
  }

  /**
   * Displays a Auction Items revision.
   *
   * @param int $auction_item_revision
   *   The Auction Items revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($auction_item_revision) {
    $auction_item = $this->entityTypeManager()->getStorage('auction_item')
      ->loadRevision($auction_item_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('auction_item');

    return $view_builder->view($auction_item);
  }

  /**
   * Page title callback for a Auction Items revision.
   *
   * @param int $auction_item_revision
   *   The Auction Items revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($auction_item_revision) {
    $auction_item = $this->entityTypeManager()->getStorage('auction_item')
      ->loadRevision($auction_item_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $auction_item->label(),
      '%date' => $this->dateFormatter->format($auction_item->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a Auction Items.
   *
   * @param \Drupal\auctions_core\Entity\AuctionItemInterface $auction_item
   *   A Auction Items object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(AuctionItemInterface $auction_item) {
    $account = $this->currentUser();
    $auction_item_storage = $this->entityTypeManager()->getStorage('auction_item');

    $langcode = $auction_item->language()->getId();
    $langname = $auction_item->language()->getName();
    $languages = $auction_item->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $build['#title'] = $has_translations
    ? $this->t('@langname revisions for %title', [
      '@langname' => $langname,
      '%title' => $auction_item->label(),
    ])
    : $this->t('Revisions for %title', ['%title' => $auction_item->label()]);

    $header = [$this->t('Revision'), $this->t('Operations')];
    $revert_permission = (($account->hasPermission("revert all auction items revisions") || $account->hasPermission('administer auction items entities')));
    $delete_permission = (($account->hasPermission("delete all auction items revisions") || $account->hasPermission('administer auction items entities')));

    $rows = [];

    $vids = $auction_item_storage->revisionIds($auction_item);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\auctions_core\AuctionItemInterface $revision */
      $revision = $auction_item_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $vid;
        // If ($vid != $auction_item->getRevisionId()) {.
        $link = Link::createFromRoute($date, 'entity.auction_item.revision', [
          'auction_item' => $auction_item->id(),
          'auction_item_revision' => $vid,
        ])->toString();
        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => $this->renderer->renderPlain($username),
              'message' => [
                '#markup' => $revision->getRevisionLogMessage(),
                '#allowed_tags' => Xss::getHtmlTagList(),
              ],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => $has_translations ?
              Url::fromRoute('entity.auction_item.translation_revert', [
                'auction_item' => $auction_item->id(),
                'auction_item_revision' => $vid,
                'langcode' => $langcode,
              ]) :
              Url::fromRoute('entity.auction_item.revision_revert', [
                'auction_item' => $auction_item->id(),
                'auction_item_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.auction_item.revision_delete', [
                'auction_item' => $auction_item->id(),
                'auction_item_revision' => $vid,
              ]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['auction_item_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
