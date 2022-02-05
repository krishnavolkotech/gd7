<?php

namespace Drupal\hzd_sams;

use Drupal\Core\Url;
use Drupal\Core\Pager\PagerParametersInterface;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\hzd_artifact_comments\HzdArtifactCommentStorage;

class Aql{}

/**
 * Handles rest data storage and connection to SAMS KONSENS.
 */
class HzdSamsStorage {

  protected $request;
  protected $type;
  protected $class;
  protected $service;
  protected $product;
  protected $version;
  protected $filterData;
  protected $samsData;

  public function __construct() {
    $this->request = \Drupal::request();
    $this->service = $this->request->get('services');
    $this->product = $this->request->get('products');
    $this->version = $this->request->get('versions');
  }

  /**
   * Returns current artifact-class context or false.
   */
  public function getCurrentClass() {
    if ($this->class) {
      return $this->class;
    }
    else {
      return false;
    }
  }
  
  /**
   * Returns table with artifacts as a render array.
   *
   * @return array
   *   The render array.
   *
   */
  public function getArtifactTable() {
    $rows = $this->buildRows();
    // $header = array(t('REPO-Bezeichnung'), t('Artefakt-Bezeichnung'), t('Datum'), t('Comment'), t('debug'), t('Download'));
    // $header = array(t('REPO-Bezeichnung'), t('Artefakt-Bezeichnung'), t('Datum'), t('Comment'), t('Download'));
    $header = array(
      array('data' => t('Repository'), 'class' => 'sorter-text'),
      array('data' => t('Artifact'), 'class' => 'sorter-text'),
      array('data' => t('Date'), 'class' => 'sorter-artifact-date'),
      array('data' => t('Comment'), 'class' => 'sorter-no-parser'),
      array('data' => 'D/I', 'class' => 'sorter-no-parser')
    );

    $build[] = array(
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
      '#attributes' => [
        'id' => "sortable_artifact_table", 'class' => ["tablesorter", 'artifacts']
      ],
      '#empty' => t('No records found'),
    );

    return $build;
  }

  /**
   * - Abfrage der Daten vom SAMS-Server via REST Schnittstelle
   * - Aktualisierung der gespeicherten SAMS Daten (in State-System)
   */
  public function fetch($service=NULL, $product=NULL, $version=NULL, $class=NULL, $status=NULL) {
    // @todo Storage / fetch vereinfachen, für Artefakttabellen und Abo Funktion
    // Zusammensetzen der Rest-Abfrage optimieren

    // $debug = "Service: " . $service . "\n"
    //   . "Produkt: ". $product . "\n"
    //   . "Version: ". $version . "\n"
    //   . "Status: ". $status;
    // file_put_contents('drupal_debug.log', $debug);

    if($service !== NULL) {
      $this->service = $service;
    }
    if($product !== NULL && $product !== '') {
      $this->product = $product;
    }
    else {
      $product = NULL;
    }
    if($version !== NULL) {
      $this->version = $version;
    }
    if($class !== NULL) {
      $this->class = $class;
    }

    // pr(\Drupal::state()->get('samsFilterServices'));
    if ($this->class === Null) {   // zu Dev-Zwecken für performance test
      $url = Url::fromRoute('<current>');
      $urlString = $url->toString();
      $path = explode("/", $urlString);
      switch ($path[3]) {
        case 'bibliotheken':
          $this->class = 'BIBLIOTHEK';
          break;
        case 'entwicklungsversionen':
          $this->class = 'ENTWICKLUNGSVERSION';
          $status = "TEST";
          break;
        case 'mock-objekte':
          $this->class = 'MOCK';
          break;
        case 'schema':
          $this->class = 'SCHEMA';
          break;
        case 'norm':
          $this->class = 'NORM';
          break;
        case 'performance-test':  // Nur zu Dev-Zwecken
          $status = '';
        default:
          $this->class = NULL;
      }
    }
    elseif ($this->class === '') {
    }
    elseif (is_numeric($this->class) && $this->class >= 0 && $this->class <= 5) {
      $classes = [
        '',
        'BIBLIOTHEK',
        'ENTWICKLUNGSVERSION',
        'MOCK',
        'SCHEMA',
        'NORM',
      ];
      $this->class = $classes[$this->class];
    }
    else {
      $this->class = '';
    }

    if ($status === Null) {   // zu Dev-Zwecken für performance test
      $status = count($path) == 5 ? strtoupper($path[4]) : 'FINAL';
    }
    // stdClass-Objekt um JSON-Objekt zu erzeugen
    $aql = new Aql();


    // $debug = "Service: " . $service . "\n"
    //   . "Produkt: ". $product . "\n"
    //   . "Version: ". $version . "\n"
    //   . "Status: ". $status;
    // file_put_contents('drupal_debug.log', $debug);

    // Liefert alle Artefakte
    // $aql->query['$and'][]['name']['$nmatch'] = '*.pom';      /*
    // $aql->query['$and'][]['name']['$nmatch'] = '*.xml';       *  Wird vom SAMS nun vorgefiltert.
    // $aql->query['$and'][]['path']['$nmatch'] = '*repodata*';  */
    $aql->query['$and'][]['repo']['$nmatch'] = 'BPK*'; // DEBUG

    if ($status) {
      $aql->query['$and'][]['repo']['$match'] = '*' . $status;
    }

    if ($this->class) {
      $aql->query['$and'][]['repo']['$match'] = '*' . $this->class . '*';
    }

    if ($this->service !== NULL) {    // Verfahren gewählt
      //$seletedService = \Drupal::state()->get('samsFilterServices')[$this->service-1];
      $seletedService = \Drupal::state()->get('samsFilterServices')[$this->service]; // EXPERIMENT
      $aql->query['$and'][]['repo']['$match'] = '*' . $seletedService . '*';
    }

    // TODO: verursacht sicherlich notice oder warning im Log
    if ($this->product !== NULL) {   // Produkt und Verfahren gewählt
      $selectedProduct = \Drupal::state()->get('samsFilterProducts')[$this->product];
      $aql->query['$and'][]['path']['$match'] = '*' . $selectedProduct . '*';
    }

    if ($this->version > 0) {    // Version gewählt
      $selectedVersion = \Drupal::state()->get('samsFilterVersions')[$this->version];
      $aql->query['$and'][]['path']['$match'] = '*' . $selectedVersion . '*';
    }
    // pr($aql->query);exit;
    $json = 'items.find(' . json_encode($aql->query) . ')';
    // file_put_contents('drupal_debug.log', $json);

    // pr($json);
    // ksm($json);
    // ksm($path, $aql);
    // $url = "https://sams-konsens-tst.hessen.doi-de.net/artifactory/api/search/aql";
    //Catharina - Anpassung für Nutzung der Einträge auf Sams Konfig Seite
    // $url = "http://10.7.5.120:8081/artifactory/api/search/aql";
    $samsSettings = \Drupal::config('cust_group.sams.settings');
    $url = $samsSettings->get('sams_url') . "/artifactory/api/search/aql";
    $headers = array('Content-Type: text/plain');

    // REST Abfrage an SAMS schicken
    $curl = curl_init($url);

    // curl_setopt($curl, CURLOPT_HEADER, true);  // Debugging
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    //Catharina - Anpassung für Nutzung der Einträge auf Sams Konfig Seite
    // curl_setopt($curl, CURLOPT_USERPWD, 'bpk_user:password');
    curl_setopt($curl, CURLOPT_USERPWD, $samsSettings->get('sams_user') . ":" . $samsSettings->get('sams_pw'));
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
    curl_setopt($curl, CURLOPT_POST, true);
    // curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    // curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); //funktioniert nicht so lange SAMS-Testsystem da kein eigenes Zertifikat
    // curl_setopt($curl, CURLOPT_SSLVERSION, 0);

    $response = curl_exec($curl);
    curl_close($curl);

    /* REST JSON Daten verarbeiten */
    $samsImportObject = json_decode($response);
    $i = 0;

    // pr(count($samsImportObject->results));
    if (count($samsImportObject->results) === 0) {
      return;
    }

    foreach ($samsImportObject->results as $artifactData) {
      $repo = $artifactData->repo;

      // Nicht-KONSENS Repos rausfiltern
      if (strpos($repo, '_') === False) {
        continue;
      }

      $restServices[] = explode("_", $repo)[0];
      $artifact = $artifactData->name;
      
      $path = $artifactData->path;
      $explodedPath = explode("/", $path);
      if (strpos($repo, 'RPM') !== False) {
        array_pop($explodedPath);
      }
      $restVersions[] = array_pop($explodedPath);
      $restProducts[] = array_pop($explodedPath);

      $artifactCommentCell = '';
      $time = date_create_from_format('Y-m-d\TH:i:s.uP', $artifactData->modified);
      $modified = date_format($time, 'd.m.Y H:i:s');

      $rows[] = array(
        'repo' => $repo,
        'artifact' => $artifact,
        'date' => $modified,
        'comment' => $artifactCommentCell,
        'download' => '',
        'path' => $path, // Debugging
       );
       $i++;
      }
      for ($i=0;$i<count($restProducts);$i++) {
        $result[] = $restProducts[$i] . " - " . $restVersions[$i];
      }
      // kint($result);

    // $stateData = array(
      // ['services'] => $restServices,
      // ['products'] => $restProducts,
      // ['versions'] => $restVersions
    // );

    $this->samsData['artifacts'] = $rows;
    $this->samsData['total'] = $samsImportObject->range->total;

    // pr($this->samsData['total']);exit;
    $this->saveDataToState($restServices, $restProducts, $restVersions);
  }

  /**
   * Returns rows for the table of artifacts.
   *
   * @return array
   *   Array of artifact data.
   */
  private function buildRows() {
    $pageRows = '';
    if (!$this->samsData['artifacts']) {
      return($pageRows);
    }
    // $request = \Drupal::request();
    $limit = $this->request->get('limit');
    $limit = $limit ? ($limit == 'all' ? NULL : $limit) : 20;

    /* PAGER */
    if ($limit) {
      $page = \Drupal::service('pager.parameters')->findPage();
      $num_per_page = $limit;
      $offset = $num_per_page * $page;
      \Drupal::service('pager.manager')->createPager($this->samsData['total'], $limit);
    }

    $pageRows = array_slice($this->samsData['artifacts'], $offset, $limit);

    // Download Links und Kommentare bauen
    $options['attributes'] = array('class' => 'download_img_icon');
    $download_imgpath = drupal_get_path('module', 'hzd_release_management') . '/images/download_icon.png';
    $download = "<img src = '/" . $download_imgpath . "'>";

    foreach ($pageRows as $key => $row) {
      /* Downloadlink bauen */
      unset($action);
      $action = '';

      // @todo url aus konfig ziehen
      $urlpath = 'https://sams-konsens.hessen.doi-de.net/artifactory/'
        . $row['repo'] . '/'
        . $row['path'] . '/'
        . $row['artifact'];
      $url = Url::fromUri($urlpath, $options);

      $download_link = array(
          '#title' => array(
            '#markup' => $download
          ),
          '#type' => 'link',
          '#url' => $url,
          '#attributes' => ['target' => '_blank'],
        );

      $link_path = \Drupal::service('renderer')->renderRoot($download_link);
      $popoverInfoIcon = array(
        '#markup' => ''
      );
      if (strpos($row['repo'], 'RPM') === False) {
        $popoverInfoIcon = array(
          '#markup' => '<a href="" class="artifact-info-icon disabled" artifact="' . $row['artifact'] . '"><img data-toggle="tooltip" data-placement="left" title="GAV-Koordinaten" src="/themes/hzd/images/notification-icon.png"></a>',
        );
      }
      $popover = \Drupal::service('renderer')->renderRoot($popoverInfoIcon);
      $action = t('@link_path @link @popover', array(
          '@link_path' => $link_path,
          '@link' => $action,
          '@popover' => $popover
        )
      );
      $pageRows[$key]['download'] = $action;

      // Artefakt Kommentar View/Create bauen
//      $artifactCommentCell = HzdArtifactCommentStorage::fillCommentsCell($row['artifact'], $row['repo']);
      $artifactCommentCell = HzdArtifactCommentStorage::fillCommentsCell($row['artifact']);
      $pageRows[$key]['comment'] = $artifactCommentCell;
      unset($pageRows[$key]['path']);

    }
    // ksm($pageRows);
    return $pageRows;
  }

  /**
   * Speichert Verfahren, Produkte und Versionen im State-System.
   *
   * @param array $restServices
   *   Liste der Verfahren, die gespeichert werden sollen.
   * @param array $restProducts
   *   Liste der Produkte, die gespeichert werden sollen.
   * @param array $restVersions
   *   Liste der Versionen, die gespeichert werden sollen.
   *
   */
  private function saveDataToState($restServices, $restProducts, $restVersions) {
    // pr($stateData);exit;
    // Daten für ArtifactFilterForm
    // $restServices = $stateData['services'];
    // $restProducts = $stateData['products'];
    // $restVersions = $stateData['versions'];

    $restServices = array_unique($restServices);
    $services = array_values($restServices);
    // pr($restServices);

    $restVersions = array_unique($restVersions);
    $versions = array_values($restVersions);

    $restProducts = array_unique($restProducts);
    $products = array_values($restProducts);

    $this->filterData = array(
      'services' => $services,
      'products' => $products,
      'versions' => $versions
    );

    // Neue Daten zu Verfahren / Produkten / Versionen im State-System speichern
    if ($this->service === NULL && $this->product === NULL && $this->version === NULL) {
      // $restServices = array_unique($restServices);
      $savedServices = \Drupal::state()->get('samsFilterServices');

      if (!$savedServices) {
        $savedServices[0] = '';
      }

      $newServices = array_diff($restServices, $savedServices);
      foreach ($newServices as $key => $newService) {
        $savedServices[] = $newService;
      }

      $savedProducts = \Drupal::state()->get('samsFilterProducts');
      if (!$savedProducts) {
        $savedProducts[0] = '';
      }

      $newProducts = array_diff($restProducts, $savedProducts);
      foreach ($newProducts as $key => $newProduct) {
        $savedProducts[] = $newProduct;
      }

      $savedVersions = \Drupal::state()->get('samsFilterVersions');
      if (!$savedVersions) {
        $savedVersions[0] = '';
      }

      $newVersions = array_diff($restVersions, $savedVersions);
      foreach ($newVersions as $key => $newVersion) {
        $savedVersions[] = $newVersion;
      }

      // pr($savedProducts);exit;
      \Drupal::state()->set('samsFilterServices', $savedServices);
      \Drupal::state()->set('samsFilterProducts', $savedProducts);
      \Drupal::state()->set('samsFilterVersions', $savedVersions);

      // \Drupal::state()->set('samsData', $savedServices);
      // \Drupal::state()->delete('samsData');

      // pr($savedServices);
      // pr($savedProducts);
      // pr($savedVersions);
    }

      // Speicher resetten
      // $stateData[] = \Drupal::state()->get('samsFilterServices');
      // $stateData[] = \Drupal::state()->get('samsFilterProducts');
      // $stateData[] = \Drupal::state()->get('samsFilterVersions');
      // kint($stateData);
      // Speicher resetten
      // \Drupal::state()->delete('samsFilterServices');
      // \Drupal::state()->delete('samsFilterProducts');
      // \Drupal::state()->delete('samsFilterVersions');

  }

  public function getClass() {
    return $this->class;
  }

  public function getFilterData() {
    return $this->filterData;
  }

  // public static function resetArtifactState() {
  //     // Speicher resetten
  //     // \Drupal::state()->delete('samsFilterServices');
  //     // \Drupal::state()->delete('samsFilterProducts');
  //     // \Drupal::state()->delete('samsFilterVersions');
  // }
}
