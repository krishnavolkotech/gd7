<?php

namespace Drupal\hzd_sams;

use Drupal\user\Entity\User;
use Drupal\group\Entity\Group;

class SamsMail {
  
  protected $xmlData;
  protected $subscribers;
  protected $mailBody;
  protected $result;
   
  public function processSamsMails() {
    $this->readXml();
    if (!$this->xmlData) {
      \Drupal::messenger()->addMessage(t('Keine Daten zu Events vom SAMS gefunden'), 'error');
      return;
    }
    $this->loadSubscribers();
    if (!$this->subscribers) {
      \Drupal::messenger()->addMessage("Keine Abonnenten gefunden!", 'error');
      return;
    }
    $render['result']['abo']['#theme'] = 'table';
    $render['result']['abo']['#header'] = array(
      'Abonnent',
      'Anzahl Events (Abonniert)'
    );

    foreach ($this->subscribers as $subscriber) {
      $render['mail']['#theme'] = 'mailbody';
      $render['mail']['#header'] = array(
        'Event',
        'Name',
        'Checksum',
        // 'CreatedBy',
        // 'RepoKey',
        'Pfad',
        'Verschoben nach'
      );
      $render['mail']['#rows'] = []; 
      $count = 0;
      foreach ($this->xmlData->artifact as $xml) {
        if ($this->checkAbo($subscriber, $xml)) {
          $row[] = $xml->eventValue;
          $row[] = $xml->name;
          $row[] = $xml->id;
          // $row[] = reset($xml->createdBy);
          // $row[] = reset($xml->repoKey);
          $row[] = $xml->repopath ? $xml->repopath : $xml->sourceRepopath;
          $row[] = $xml->targetRepopath;
          $count++;
          $render['mail']['#rows'][] = $row;
          $row = array();
        }
    }
      // @todo Downloadlink in Email bauen
      // Code aus HzdSamsStorage f�r Bauen von Donwloadlink in Mail analog zu Donwloadlink in Tabelle
      // Anpassung schon gegonnen
      // foreach ($pageRows as $key => $row) {
      // /* Downloadlink bauen */
      // unset($link);
      // $link = '';
      // if ($xml->eventValue == "publish"){
      // $urlpath = 'https://sams-konsens-tst.hessen.doi-de.net/artifactory/'
             // . $xml->repopath;
      // }
       // if ($xml->eventValue == "move"){
      // $urlpath = 'https://sams-konsens-tst.hessen.doi-de.net/artifactory/'
             // . $xml->targetRepopath;
      // }
      // $url = Url::fromUri($urlpath);
      // $download_link = array('#title' => array('#markup' => $download //hier muss statt dem Downloadikon der Text aus xml hin), '#type' => 'link', '#url' => $url);
      // $link_path = \Drupal::service('renderer')->renderRoot($download_link);
      // $link = t('@link_path @link', array(
          // '@link_path' => $link_path,
          // '@link' => $link
              // )
        // );
      // $pageRows[$key]['download'] = $link;
      // foreach ($render as $row) {
        // echo $row;
      // }
      $user = User::load($subscriber);
      $render['result']['abo']['#rows'][] = [$user->getEmail(), $count];
      if (count($render['mail']['#rows']) > 0) {
        $render['mail']['#recipientUserId'] = $subscriber;
        $this->sendMail($subscriber, $render);
      }
      $render['mail'] = array();
    }
    $eventCount = count($this->xmlData->artifact);
    $render['result']['events']['#markup'] = "<p>Insgesamt: $eventCount Events.";


    return $render['result'];
  }
  
  private function readXml() {
    if (file_exists('../imports/sams/sams.xml')) {
      $xml = simplexml_load_file('../imports/sams/sams.xml');
      if (count($xml) == 0) {
        return;
      }
      $this->xmlData = $xml;
      $date = strftime('%Y%m%d_%H-%M-%S');
      rename('../imports/sams/sams.xml', "../imports/sams/archiv/sams.xml-$date");
    }
  }
  
  private function loadSubscribers() {
    $connection = \Drupal::database();
    // @todo select distinct?
    $this->subscribers = $connection->query('SELECT uid FROM sams_notifications__user_default_interval')
      ->fetchCol();
    $this->subscribers = array_unique($this->subscribers);
    
    foreach ($this->subscribers as $key => $subscriber) {
      $user = User::load($subscriber);
      $group = Group::load(\Drupal::config('cust_group.sams.settings')->get('sams_id'));
      $groupMember = $group->getMember($user);
      if (!$groupMember) {
        unset($this->subscribers[$key]);
      }
    }
  }
  
  private function checkAbo($subscriber, $xml) {
    // kint($xml);
    $connection = \Drupal::database();
    $query = $connection->select("sams_notifications__user_default_interval", "sn");
    $query->fields("sn")->condition("sn.uid", $subscriber);
    $subscriptions = $query->execute()->fetchAll();
    // TODO: Noch nur primitive Pfad-Auflösung -> funktioniert nicht bei jedem Artefakt!
    // Gleiches Problem in HzdSamsStorage.php -> behoben 17.12.19
    $path = $xml->repopath ? $xml->repopath : $xml->sourceRepopath;
    // pr($path);
    $explodedPath = explode("/", $path);
    array_pop($explodedPath);
    if (strpos($xml->repoKey, 'RPM') !== False) {
      array_pop($explodedPath);
    }
    $exRepoKey = explode("_", $xml->repoKey);
    $service = array_shift($exRepoKey);
    // pr($service);exit;
    $version = array_pop($explodedPath);
    $product = array_pop($explodedPath);
    $repo = array_shift($explodedPath);   
    foreach ($subscriptions as $subscription) {
      if ($service !== $subscription->service) {
        continue;
      }
      if ($subscription->class !== "ALL") {
        if (strpos(reset($xml->repoKey), $subscription->class) === False) {
          continue;
        }
      }
      // echo("$subscription->product gegen $product </br>");
      if ($subscription->product !== "ALL") {
        if ($subscription->product !== $product) {
          continue;
        }
      }
      if ($subscription->status === "ALL") {
        return True;
      }
      if (strpos(reset($xml->repoKey), $subscription->status) !== False) {
        return True;
      }
    }
    return False;
  }
 
  private function sendMail($subscriber, $render) {
    $mailManager = \Drupal::service('plugin.manager.mail');
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $module = 'hzd_sams';
    $key = 'sams';
    $user = User::load($subscriber);
    $this->mailBody = $render['mail'];
    $params['subject'] = "[SAMS KONSENS] Information über neue Events "; 
    // kint($render['mail']);
    $params['message'] = \Drupal::service('renderer')->render($render['mail']);
    //echo $params['message'];exit; // Kommentar entfernen, um nur Mailbody anzuschauen.
    $send = True;
    $result = $mailManager->mail($module, $key, $user->getEmail(), $langcode, $params, NULL, $send);
    if ($result['result']) {
      \Drupal::messenger()->addMessage(t('Mail sent.'), 'status');
    }
  }
}
