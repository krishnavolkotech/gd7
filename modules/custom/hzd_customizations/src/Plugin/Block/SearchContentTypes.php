<?php

/**
 * @file
 * Contains \Drupal\hzd_customizations\Plugin\Block\GroupMenuBlock.
 */

namespace Drupal\hzd_customizations\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\group\Entity\Group;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides a 'Search With Content Types' block.
 *
 * @Block(
 *   id = "search_group_content",
 *   admin_label = @Translation("Group Specific Content Types"),
 *   category = @Translation("Custom Search")
 * )
 */
class SearchContentTypes extends BlockBase {
  
  /**
   * {@inheritdoc}
   */
  public function build() {
      $content_types = $this->search_content_types();
      $searchHtml = '<div class="row">Suche Verfeinern</div>';
      return [
          '#theme' => 'search_content_types',
          '#title' => "Suche Verfeinern",
          '#content_types' => $content_types,
          '#max-age' => 0,
      ];
  }

  public function search_content_types() {
      $params = \Drupal::request()->query->all();
      $urlClass = 'hidden custom-facet-link';
      $urlPage = 'view.solr_search.page_1';
      $urlAttributes = ['attributes' => ['class' => [$urlClass]]];
      $url = Url::fromRoute($urlPage, $params, $urlAttributes);

      $content_types = [];
      $content_types['global'] = [
          'title' => $this->t("Global"),
          'links' => [
              [
                'title' => 'Gruppen',
                'url' => $link = Link::fromTextAndUrl('Gruppen', Url::fromRoute($urlPage, $params + ['f[0]' => 'parent_group:gruppen'], $urlAttributes))->toString()
              ],
              ['title' => 'Mitglieder', 'url' => $link = Link::fromTextAndUrl('Mitglieder', Url::fromRoute($urlPage, $params + ['f[0]' => 'parent_group:gruppen'], $urlAttributes))->toString()],
          ]
      ];

      $content_types['gruppeninhalte'] = [
          'title' => $this->t("Gruppeninhalte"),
          'links' => [
              ['title' => 'Seiten', 'url' => $link = Link::fromTextAndUrl('Seiten', Url::fromRoute($urlPage, $params + ['f[0]' => 'parent_group:gruppen'], $urlAttributes))->toString()],
              ['title' => 'FAQ', 'url' => $link = Link::fromTextAndUrl('FAQ', Url::fromRoute($urlPage, $params + ['f[0]' => 'parent_group:gruppen'], $urlAttributes))->toString()],
              ['title' =>'Forumnach', 'url' => $link = Link::fromTextAndUrl('Forumnach', Url::fromRoute($urlPage, $params + ['f[0]' => 'parent_group:gruppen'], $urlAttributes))->toString()],
          ]
      ];

      $content_types['incident_management'] = [
          'title' => $this->t("Incident Management"),
          'links' => [
              ['title' => 'Störung oder Blockzeit', 'url' => $link = Link::fromTextAndUrl('Störung oder Blockzeit', Url::fromRoute($urlPage, $params + ['f[0]' => 'parent_group:gruppen'], $urlAttributes))->toString()],
          ]
      ];

      $content_types['problem_management'] = [
          'title' => $this->t("Problem Management"),
          'links' => [
              ['title' => 'Problem', 'url' => $link = Link::fromTextAndUrl('Problem', Url::fromRoute($urlPage, $params + ['f[0]' => 'parent_group:gruppen'], $urlAttributes))->toString()],
          ]
      ];

      $content_types['release_management'] = [
          'title' => $this->t("Release Management"),
          'links' => [
              ['title' => 'Release', 'url' => $link = Link::fromTextAndUrl('Release', Url::fromRoute($urlPage, $params + ['f[0]' => 'parent_group:gruppen'], $urlAttributes))->toString()],
              ['title' => 'Planungsdatei', 'url' => $link = Link::fromTextAndUrl('Planungsdatei', Url::fromRoute($urlPage, $params + ['f[0]' => 'parent_group:gruppen'], $urlAttributes))->toString()],
              ['title' => 'Release Comments', 'url' => $link = Link::fromTextAndUrl('Release Comments', Url::fromRoute($urlPage, $params + ['f[0]' => 'parent_group:gruppen'], $urlAttributes))->toString()],
          ]
      ];

      $content_types['risiko_management'] = [
          'title' => $this->t("Risiko Management"),
          'links' => [
              ['title' => 'Einzelrisiko', 'url' => $link = Link::fromTextAndUrl('Einzelrisiko', Url::fromRoute($urlPage, $params + ['f[0]' => 'parent_group:gruppen'], $urlAttributes))->toString()],
              ['title' => 'Maßnahme', 'url' => $link = Link::fromTextAndUrl('Maßnahme', Url::fromRoute($urlPage, $params + ['f[0]' => 'parent_group:gruppen'], $urlAttributes))->toString()],
              ['title' => 'Risikocluster', 'url' => $link = Link::fromTextAndUrl('Risikocluster', Url::fromRoute($urlPage, $params + ['f[0]' => 'parent_group:gruppen'], $urlAttributes))->toString()],
          ]
      ];

      return $content_types;
  }
  
}
