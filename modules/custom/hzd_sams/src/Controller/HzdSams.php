<?php

namespace Drupal\hzd_sams\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\hzd_sams\HzdSamsStorage;
use Drupal\hzd_release_management\HzdreleasemanagementStorage;
use Drupal\hzd_artifact_comments\HzdArtifactCommentStorage;
use Drupal\hzd_sams\SamsMail;
use Symfony\Component\HttpFoundation\Request;

class HzdSams extends ControllerBase {
  
  public function sendMail() {
    $render['title']['#markup'] = "<h1>Mail vom SAMS</h1>";
    $mail = new SamsMail();
    $render['mailresult'] = $mail->processSamsMails();
    return $render;
  }

  /**
   * Diese Methode baut Render-Arrays zu Sichten auf das SAMS.
   */
  public function artifacts() {
    global $base_url;
    $type = 'released'; // TODO: abbauen
      
    $storage = new HzdSamsStorage();
    $storage->fetch();
    $samsData = $storage->getFilterData();
      
    $output['#attached']['library'][] = 'hzd_sams/hzd_sams.artifact_info';
    $output['#title'] = $storage->getClass();
    $output['filter'] = \Drupal::formBuilder()
      ->getForm('Drupal\hzd_sams\Form\ArtifactFilterForm', $type, $samsData);
    
    $legendenText = "
      <div class='menu-filter'>
      <ul>
      <li><b>Legende:</b></li><li><img height=15 src='/modules/custom/hzd_release_management/images/download_icon.png'> Artefakt herunterladen</li>
      <li><img height=15 src='/modules/custom/hzd_release_inprogress_comments/images/blue-icon.png'>Kommentare ansehen</li>
      <li><img height=15 src='/modules/custom/hzd_release_inprogress_comments/images/create-green-icon.png'>Kommentieren</li>
      <li><img height=15 src='/themes/hzd/images/notification-icon.png' class='white-image-background'>Koordinaten anzeigen</li>
      </ul>
      </div>";

    if ($storage->getCurrentClass() == 'ENTWICKLUNGSVERSION') {
      $legendenText = "
        <div class='menu-filter'>
        <ul>
        <li><b>Legende:</b></li><li><img height=15 src='/modules/custom/hzd_release_management/images/download_icon.png'> Artefakt herunterladen</li>
        <li><img height=15 src='/modules/custom/hzd_release_inprogress_comments/images/blue-icon.png'>Kommentare ansehen</li>
        <li><img height=15 src='/modules/custom/hzd_release_inprogress_comments/images/create-green-icon.png'>Kommentieren</li>
        </ul>
        </div>";
    }

    $output['legend'] = [
      '#type' => 'inline_template',
      '#template' => '
        <style>
          .white-image-background {
            background-color: white;
          }
        </style>'
        . $legendenText,
      '#context' => [
        'legendenText' => $legendenText,
      ],
      '#exclude_from_print' => 1
    ];
    $output['artifacts'] = $storage->getArtifactTable();
    $output['pager'] = array(
      '#type' => 'pager',
    );
    return $output;
  }
/**
   * Diese Methode baut das Formular für Registrierungsdatenübermittlung ans SAMS.
   */
  public function registrationForm() {
    // global $base_url;
  $outpout['title'] = ' Hier Registierungsseite';
   // $output['content'] = \Drupal::formBuilder()
      // ->getForm('Drupal\hzd_sams\Form\SamsRegistrationForm');
  return $output;
  }
    
    
  /**
   * Ajax callback function für Artefakt info-icon Feature.
   * 
   * @param object $request
   *   Request object used to process $_POST information about artifact.
   */
  public function artifact_info_callback(Request $request) {
    $repo = $request->request->all()['repo'];
    $link = $request->request->all()['link'];

    $path = substr($link,strpos($link, $repo)+strlen($repo)+1);
    $samsSettings = \Drupal::config('cust_group.sams.settings');
    $samsUrl = $samsSettings->get('sams_url');

    // GAV-Koordinaten
    $url = $samsUrl 
      . '/artifactory/ui/dependencydeclaration?buildtool=maven&path=' 
      . $path . '&repoKey=' . $repo;

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_USERPWD, $samsSettings->get('sams_user') . ":" . $samsSettings->get('sams_pw'));
    $gavRaw = curl_exec($curl);
    curl_close($curl);
    
    // Gradle
    $url = $samsUrl 
    . '/artifactory/ui/dependencydeclaration?buildtool=gradle&path='
    . $path . '&repoKey=' . $repo;

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_USERPWD, $samsSettings->get('sams_user') . ":" . $samsSettings->get('sams_pw'));
    $gradleRaw = curl_exec($curl);
    curl_close($curl);

    $gavResponse = json_decode($gavRaw);
    $gradleResponse = json_decode($gradleRaw);
    
    $gavTitel = '<h4>Maven</h4>';
    $formatiert = $gavResponse->dependencyData;
    $formatiert = str_replace('<', '&lt;', $formatiert);
    $formatiert = str_replace('>', '&gt;', $formatiert);
    $gradleTitle = '<hr><h4>Gradle</h4>';

    $content = $gavTitel
      . '<button type="button" class="btn btn-info"><span class="glyphicon glyphicon-copy" aria-hidden="true"></span>&nbsp;&nbsp;In Zwischenablage</button>'
      . '<textarea class="gav form-control" cols="45" readonly>' . $formatiert . '</textarea>'
      . $gradleTitle
      . '<button type="button" class="btn btn-info"><span class="glyphicon glyphicon-copy" aria-hidden="true"></span>&nbsp;&nbsp;In Zwischenablage</button>'
      . '<textarea class="gradle form-control" cols="45" rows="2" readonly>' . $gradleResponse->dependencyData . '</textarea>';

    // file_put_contents('drupal_debug.log', json_encode($formatiert));
    $response = new \Drupal\Core\Ajax\AjaxResponse();
    $response->setData([
      'body' => $content
    ]);

    return $response;
  }
}
