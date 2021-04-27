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
 *   admin_label = @Translation("Content Type search filter"),
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
      $params = \Drupal::routeMatch()->getParameters();
      $params = \Drupal::request()->query->all();
      $urlClass = 'hidden custom-facet-link';
      $urlPage = 'view.solr_search.page_1';
      $urlAttributes = ['attributes' => ['class' => [$urlClass]]];
      $url = Url::fromRoute($urlPage, $params, $urlAttributes);
      $content_type_facet = 'inhalt_inhalt_inhaltstyp';


      // Processes
      $processes = [
        /*Todo: enable search to filter by type group and type users*/
        /*'global' => 'Global',*/
        'gruppeninhalte' => 'Gruppeninhalte',
        'incident_management' => 'Incident Management',
        'problem_management' => 'Problem Management',
        'release_management' => 'Release Management',
        'risiko_management' => 'Risiko Management'
      ];

      // Content Types
      $process_content_types = [
        'global' => ['group', 'user'],
        'gruppeninhalte' => ['page', 'faqs', 'forum'],
        'incident_management' => ['downtimes'],
        'problem_management' => ['problem'],
        'release_management' => ['deployed_releases', 'planning_files', 'quickinfo'],
        'risiko_management' => ['risk', 'measure', 'risk_cluster']
                              ];

      $content_types_titles = ['group' => t('Gruppen'), 'page' => t('Seite'), 'faqs'
      => t('FAQ'), 'forum' => t('Forenthema'), 'downtimes' => t('Störung oder
      Blockzeit'), 'problem' => t('Problem'), 'deployed_releases' => t('Eingesetzteinformationen'),
      'planning_files' => t('Planungsdatei'), 'quickinfo' => t('RZ-Schnellinfo'), 'risk' => t('Einzelrisiko'), 'measure' => t('Maßnahme'),
      'risk_cluster' => t('Risikocluster'), 'user' => t('Mitglieder')];

      $content_types = [];
      foreach ($processes as $process_key => $process_title) {
      $content_types[$process_key]['title'] = $this->t($process_title);
      $links = [];
      foreach ($process_content_types[$process_key] as $content_type) {
        $content_type_params = [];
        $content_type_params['fulltext'] = isset($params['fulltext']) ? $params['fulltext'] : "";
        $content_type_params['f'] = isset($params['f']) ? $params['f'] : [];
        if (isset($params['created'])) {
          $content_type_params['created'] = $params['created'];
        }

        $content_type_filter = $content_type_facet . ':' . $content_type;
        if (!isset($content_type_params['f']) || !in_array($content_type_filter, $content_type_params['f'])) {
          $content_type_params['f'][] = $content_type_filter;
          $content_type_checked = '';
        } else {
          if (($key = array_search($content_type_filter, $content_type_params['f'])) !== false) {
            unset($content_type_params['f'][$key]);
          }
          $content_type_checked = 'checked';
        }
        $links[] = ['title' => $content_types_titles[$content_type],
        'checked' => $content_type_checked,
        'url' => Link::fromTextAndUrl($content_types_titles[$content_type], Url::fromRoute($urlPage, $content_type_params, $urlAttributes))->toString(),
        ];
        $content_types[$process_key]['links'] = $links;
      }
  }

return $content_types;
/*      $grouppen_params = $params;
      $group_content_type = $content_type_facet . ':' . $group;
      if (!in_array(':group', $grouppen_params[f])) {
        $grouppen_params[f][] = 'inhalt_inhalt_inhaltstyp:group';
        $grouppen_checked = '';
      } else {
        $grouppen_checked = 'checked';
      }

      $mitglider_params = $params;
      if (!in_array('node_group_content_type:mitglider:Gruppen', $mitglider_params[f])) {
        $mitglider_params[f][] = 'node_group_content_type:mitglider:Gruppen';
        $mitglider_checked = '';
      } else {
        $mitglider_checked = 'checked';
      }

      $content_types['global'] = [
          'title' => $this->t("Global"),
          'links' => [
              [
                'title' => 'Gruppen',
                'checked' => $grouppen_checked,
                'url' => $link = Link::fromTextAndUrl('Gruppen', Url::fromRoute($urlPage, $grouppen_params, $urlAttributes))->toString()
              ],
              ['title' => 'Mitglieder', 'checked' => $mitglider_checked, 'url' => $link = Link::fromTextAndUrl('Mitglieder', Url::fromRoute($urlPage, $mitglider_params, $urlAttributes))->toString()],
          ]
      ];

      $seiten_params = $params;
      if (!in_array('inhalt_inhalt_inhaltstyp:page', $seiten_params[f])) {
        $seiten_params[f][] = 'inhalt_inhalt_inhaltstyp:page';
        $seiten_checked = '';
      } else {
        $seiten_checked = 'checked';
      }

      $faq_params = $params;
      if (!in_array('inhalt_inhalt_inhaltstyp:faq', $faq_params[f])) {
        $faq_params[f][] = 'inhalt_inhalt_inhaltstyp:faq';
        $faq_checked = '';
      } else {
        $faq_checked = 'checked';
      }

      $forum_params = $params;
      if (!in_array('inhalt_inhalt_inhaltstyp:forum', $forum_params[f])) {
        $forum_params[f][] = 'inhalt_inhalt_inhaltstyp:forum';
        $forum_checked = '';
      } else {
        $forum_checked = 'checked';
      }

      $content_types['gruppeninhalte'] = [
          'title' => $this->t("Gruppeninhalte"),
          'links' => [
              ['title' => 'Seiten', 'checked' => $seiten_checked, 'url' => $link = Link::fromTextAndUrl('Seiten', Url::fromRoute($urlPage, $seiten_params, $urlAttributes))->toString()],
              ['title' => 'FAQ', 'checked' => $faq_checked, 'url' => $link = Link::fromTextAndUrl('FAQ', Url::fromRoute($urlPage, $faq_params, $urlAttributes))->toString()],
              ['title' =>'Forumnach', 'checked' => $forum_checked, 'url' => $link = Link::fromTextAndUrl('Forumnach', Url::fromRoute($urlPage, $forum_params, $urlAttributes))->toString()],
          ]
      ];


      $incident_params = $params;
      if (!in_array('inhalt_inhalt_inhaltstyp:downtimes', $incident_params[f])) {
        $incident_params[f][] = 'inhalt_inhalt_inhaltstyp:downtimes';
        $incident_checked = '';
      } else {
        $incident_checked = 'checked';
      }

      $content_types['incident_management'] = [
          'title' => $this->t("Incident Management"),
          'links' => [
              ['title' => 'Störung oder Blockzeit', 'checked' => $incident_checked, 'url' => $link = Link::fromTextAndUrl('Störung oder Blockzeit', Url::fromRoute($urlPage, $incident_params, $urlAttributes))->toString()],
          ]
      ];

return $content_types;
      $problem_params = $params;
      if (!in_array('inhalt_inhalt_inhaltstyp:problem', $problem_params[f])) {
        $problem_params[f][] = 'inhalt_inhalt_inhaltstyp:problem';
        $problem_checked = '';
      } else {
        $problem_checked = 'checked';
      }
      $content_types['problem_management'] = [
          'title' => $this->t("Problem Management"),
          'checked' => $problem_checked,
          'url' => Link::fromTextAndUrl('Problem', Url::fromRoute($urlPage, $problem_params, $urlAttributes))->toString()],
          ]
      ];
return $content_types;

      $release_params = $params;
      if (!in_array('inhalt_inhalt_inhaltstyp:release', $release_params[f])) {
        $release_params[f][] = 'inhalt_inhalt_inhaltstyp:release';
        $release_checked = '';
      } else {
        $release_checked = 'checked';
      }


      $plangdat_params = $params;
      if (!in_array('inhalt_inhalt_inhaltstyp:planning_files', $plangdat_params[f])) {
        $plangdat_params[f][] = 'inhalt_inhalt_inhaltstyp:planning_files';
        $plangdat_checked = '';
      } else {
        $plangdat_checked = 'checked';
      }

      $relcom_params = $params;
      if (!in_array('node_group_content_type:release_comments:Release Management', $relcom_params[f])) {
        $relcom_params[f][] = 'node_group_content_type:release_comments:Release Management';
        $relcom_checked = '';
      } else {
        $relcom_checked = 'checked';
      }

      $content_types['release_management'] = [
          'title' => $this->t("Release Management"),
          'links' => [
              ['title' => 'Release', 'checked' => $release_checked, 'url' => $link = Link::fromTextAndUrl('Release', Url::fromRoute($urlPage, $release_params, $urlAttributes))->toString()],
              ['title' => 'Planungsdatei', 'checked' => $plangdat_checked, 'url' => $link = Link::fromTextAndUrl('Planungsdatei', Url::fromRoute($urlPage, $plangdat_params, $urlAttributes))->toString()],
              ['title' => 'Release Comments', 'checked' => $relcom_checked, 'url' => $link = Link::fromTextAndUrl('Release Comments', Url::fromRoute($urlPage, $relcom_params, $urlAttributes))->toString()],
          ]
      ];


      $einz_params = $params;
      if (!in_array('node_group_content_type:risk:Risiko Management', $einz_params[f])) {
        $einz_params[f][] = 'node_group_content_type:risk:Risiko Management';
        $einz_checked = '';
      } else {
        $einz_checked = 'checked';
      }

      $manahme_params = $params;
      if (!in_array('node_group_content_type:measure:Risiko Management', $manahme_params[f])) {
        $manahme_params[f][] = 'node_group_content_type:measure:Risiko Management';
        $manahme_checked = '';
      } else {
        $manahme_checked = 'checked';
      }

      $riskcluster_params = $params;
      if (!in_array('node_group_content_type:risk_cluster:Risiko Management', $riskcluster_params[f])) {
        $riskcluster_params[f][] = 'node_group_content_type:risk_cluster:Risiko Management';
        $riskcluster_checked = '';
      } else {
        $riskcluster_checked = 'checked';
      }

      $content_types['risiko_management'] = [
          'title' => $this->t("Risiko Management"),
          'links' => [
              ['title' => 'Einzelrisiko', 'checked' => $einz_checked, 'url' => $link = Link::fromTextAndUrl('Einzelrisiko', Url::fromRoute($urlPage, $einz_params, $urlAttributes))->toString()],
              ['title' => 'Maßnahme', 'checked' => $manahme_checked, 'url' => $link = Link::fromTextAndUrl('Maßnahme', Url::fromRoute($urlPage, $manahme_params, $urlAttributes))->toString()],
              ['title' => 'Risikocluster', 'checked' => $riskcluster_checked, 'url' => $link = Link::fromTextAndUrl('Risikocluster', Url::fromRoute($urlPage, $riskcluster_params, $urlAttributes))->toString()],
          ]
      ];

      return $content_types;*/
  }

  public function getCacheMaxAge() {
    return 0;
  }

}
