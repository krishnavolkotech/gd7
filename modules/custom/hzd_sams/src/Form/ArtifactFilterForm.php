<?php

namespace Drupal\hzd_sams\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for filtering the artifact table.
 */
class ArtifactFilterForm extends FormBase {
  
    /** 
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'sams_filter_form';
    }
    
    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $type = NULL, $samsData = NULL) {
      
      $parameters = array();
      
      // Parameter aus URL auslesen
      $request = \Drupal::request()->query;
      $parameters['services'] = $request->get('services');
      $parameters['products'] = $request->get('products');
      $parameters['versions'] = $request->get('versions');
      $parameters['limit'] = $request->get('limit');

      $filter_value = $parameters;
      $form['#method'] = 'get';
      $wrapper = 'released_results_wrapper';
      
      // Verfahrensfilter
      $stateServices = \Drupal::state()->get('samsFilterServices');
      $services = array('<' . $this->t('Service')->render() . '>');
      
      // Kein Service gewählt
      if (!$filter_value['services'] && $samsData) {
        foreach ($samsData['services'] as $service) {
          $unkeyedServices[] = $service;
        }

        foreach ($unkeyedServices as $service) {
          $foundServices[array_search($service, $stateServices)] = $service;
        }
        if ($foundServices) {
          $services += $foundServices;
        }
        $_SESSION['hzd_sams'] = array('savedServices' => $services);
      }
      // Service wurde gewählt && daten vom sams
      elseif ($samsData) {
        $services = $_SESSION['hzd_sams']['savedServices'];
      }
      // pr($samsData['services']);
      $form['#prefix'] = "<div class = 'releases_filters'>";
      $form['#suffix'] = "</div>";
      $default_value_services = $filter_value['services'];
      if (!$default_value_services) {
        $default_value_services = $form_state->getValue('services');
      }
      
      $form['services'] = array(
          '#type' => 'select',
          '#options' => $services,
          '#default_value' => $default_value_services,
          '#weight' => -7,
          "#prefix" => "<div class = 'service_search_dropdown hzd-form-element'>",
          '#suffix' => '</div>',
          '#attributes' => array(
              'onchange' => 'this.form.submit()'
              )
          );
          
      
      // $service = $filter_value['services'];
      
      // TODO: Funktion zur Befüllung von Produkt und Versionsfilter schreiben
      // $this->setFilterOptions($data, $type)
      /* Produktfilter */
      
      $stateProducts = \Drupal::state()->get('samsFilterProducts');
      // pr($stateProducts);exit;
      
      $products = array('<' . $this->t('Product') . '>');
      
      $request = \Drupal::request();
      $product = $request->get('products'); // REDUNDANT siehe oben
      if ($samsData && $product != NULL) {
        
        // $isFirst = True;
        // pr($samsData['products']);exit;
        // ksm($samsData);
        foreach ($samsData['products'] as $product) {
          // if ($isFirst) {
            // $isFirst = False;
            // continue;
          // }
          $unkeyedProducts[] = $product;
        }
        
        foreach ($unkeyedProducts as $product) {
          $foundProducts[array_search($product, $stateProducts)] = $product;
        }
        
        if ($foundProducts) {
          $products += $foundProducts;
        }
        // pr($products);exit;
        
        $form['services']['#attributes'] = array(
            'onchange' => 'jQuery("#edit-products").val(0);jQuery("#edit-versions").val(0);this.form.submit()'
        );
      }
      // pr($form['services']);exit;
      /* Wiederverwenden für Artefakt-Klasse? Erlaubt Speicherung eines Wertes
        im POST Array */
      // $form['r_type'] = array(
          // '#type' => 'hidden',
          // '#value' => $type
      // );
            
      // pr($products);exit;
      $default_value_products = $filter_value['products'];
      if (!$default_value_products) {
        $default_value_products = $form_state->getValue('products');
        // pr($default_value_products);exit;
      }
      
      $form['products'] = array(
          '#type' => 'select',
          '#options' => $products,
          '#default_value' => $default_value_products,
          '#weight' => -6,
          "#prefix" => "<div class = 'releases_search_dropdown hzd-form-element'>",
          '#suffix' => '</div>',
          '#attributes' => array(
              'onchange' => 'this.form.submit()',
          ),
      );
      
      if ($parameters['versions'] > 0) {
        $form['products']['#attributes'] = array(
          'onchange' => 'jQuery("#edit-versions").val(0);this.form.submit()'
        );
      }

      // Versionsfilter
      foreach (\Drupal::state()->get('samsFilterVersions') as $version) {
        $stateVersions[] = $version;
      }
      
      $versions = array('<' . $this->t('Version') . '>');
      
      $product = $request->get('products');
      
      if ($samsData && $product) {
        
        // $isFirst = True;
        // pr($samsData['versions']);exit;
        
        foreach ($samsData['versions'] as $version) {
          // if ($isFirst) {
            // $isFirst = False;
            // continue;
          // }
          $unkeyedVersions[] = $version;
        }
        
        foreach ($unkeyedVersions as $version) {
          $versions[array_search($version, $stateVersions)] = $version;
        }
        
      }

      // $default_value_versions = $form_state->getValue('versions');
      
      $default_value_versions = $filter_value['versions'];
      
      if (!$default_value_versions) {
        $default_value_versions = $form_state->getValue('versions');
      }

      $form['versions'] = array(
          '#type' => 'select',
          '#options' => $versions,
          '#default_value' => $default_value_versions,
          '#weight' => -5,
          "#prefix" => "<div class = 'releases_search_dropdown hzd-form-element'>",
          '#suffix' => '</div>',
          '#attributes' => array(
              'onchange' => 'this.form.submit()',
          ),
      );
      
      // $form['filter_startdate'] = array(
          // '#type' => 'textfield',
          // '#attributes' => array(
              // 'class' => array("start_date"),
              // 'placeholder' => array(
                  // '<' . $this->t('Start Date')->render() . '>',
              // ),
              // 'onchange' => 'this.form.submit()',
          // ),
          // '#default_value' => isset($filter_value['filter_startdate']) ?
              // $filter_value['filter_startdate'] : $form_state->getValue('filter_startdate'),
          // '#weight' => -4,
          // '#prefix' => "<div class = 'filter_start_date  hzd-form-element'>",
          // '#suffix' => "</div>",
          // '#validated' => TRUE,
      // );
      
      // $form['filter_enddate'] = array(
          // '#type' => 'textfield',
          // '#weight' => -3,
          // '#attributes' => array(
              // 'class' => array("end_date"),
              // 'placeholder' => array(
                  // '<' . $this->t('End Date')->render() . '>'
              // ),
              // 'onchange' => 'this.form.submit()',
          // ),
          // '#default_value' => isset($filter_value['filter_enddate']) ?
              // $filter_value['filter_enddate'] : $form_state->getValue('filter_enddate'),
          // '#prefix' => "<div class = 'filter_end_date hzd-form-element'>",
          // '#suffix' => "</div>",
          // '#validated' => TRUE,
      // );
      
      $default_limit = array(
          20 => 20,
          50 => 50,
          100 => 100,
          'all' => t('All'),
      );
      
      $form['limit'] = array(
          '#type' => 'select',
          '#options' => $default_limit,
          '#default_value' => isset($filter_value['limit']) ?
              $filter_value['limit'] : $form_state->getValue('limit'),
          '#weight' => 8,
          '#attributes' => array(
              'onchange' => 'this.form.submit()',
          ),
          "#prefix" => "<div class = 'limit_search_dropdown hzd-form-element'>",
          '#suffix' => '</div>',
      );
      $form['actions'] = array(
          '#type' => 'container',
          '#weight' => 100,
      );
      $form['actions']['reset'] = array(
          '#type' => 'button',
          '#value' => t('Reset'),
          '#weight' => 100,
          '#validate' => array(),
          '#attributes' => array('onclick' => 'reset_form_elements();return false;'),
          '#prefix' => '<div class = "reset_form">',
          '#suffix' => '</div><div style = "clear:both"></div>',
      );
      $form['#exclude_from_print']=1;
      
      return $form;
    }
    
    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
    
    }

}
