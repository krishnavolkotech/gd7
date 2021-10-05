<?php

/**
 * @file
 * Contains Drupal\favorites\Form\StarFavAddForm
 */

namespace Drupal\favorites\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\favorites\FavoriteStorage;
use Drupal\Core\Url;
use Drupal\Component\Utility\UrlHelper;
use Drupal\favorites\Controller\MyFavController;

/**
 * Class StarFavAddForm.
 *
 * @package Drupal\favorites\Form\StarFavAddForm
 */
class StarFavAddForm extends FormBase
{
    
    protected $account;
    
    public function __construct() {
        $this->account = \Drupal::currentUser();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'star_favorites_add';
    }
    
    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

        $request = \Drupal::request();
        $route_match = \Drupal::routeMatch();
        $title = \Drupal::service('title_resolver')->getTitle($request, $route_match->getRouteObject());
	if(is_array($title)) {
            $title = \Drupal::service('renderer')->render($title);
        }
        if ($title == '') {
            $current_path = \Drupal::service('path.current')->getPath();
            $title = \Drupal::service('path_alias.manager')->getAliasByPath($current_path);
        }
        if (is_string($title)) {
            $title = strip_tags($title);
        }
//        kint($title);
        //pr($title);exit;
        $path = \Drupal::request()->getPathInfo();
        $query = \Drupal::request()->getQueryString();
        
        $form['title'] = array(
            '#type' => 'hidden',
            '#value' => $title,
        );
        $form['path'] = array(
            '#type' => 'hidden',
            '#value' => $path,
        );
        $form['query'] = array(
            '#type' => 'hidden',
            '#value' => $query,
        );
        
        
        $uid = \Drupal::currentUser()->id();
        $fid = FavoriteStorage::favExists($uid, $path, $query);
        
        if ($fid) {
            $button_text = t('Delete', array(), array('context' => 'Add a favorite to the list'));
            $submit_url = Url::fromRoute('favorites.removeAjax', array('fid' => $fid));
            $fav_class = 'add-fav';
        } else {
            $button_text = t('Add', array(), array('context' => 'Add a favorite to the list'));
            $submit_url = Url::fromRoute('favorites.add');
            $fav_class = 'del-fav';
        }
        
        /*    $form['add_to_favorites'] = array(
              '#type' => 'checkbox',
              '#default_value' => !empty($fid),
              '#attributes' => array(
                'class' => array('add-fav-checkbox',$fav_class),
                'onclick' => 'this.form.submit()',
                ),
              '#ajax' => array(
                'wrapper' => 'add-to-faborites-checkbox',
                //'url' => $submit_url,
              '#prefix' => '<div id="add-to-faborites-checkbox">',
              '#suffix' => '</div>',
             ),
            );    */
        
        /**
         * @todo:  uncheck the ajax property and check for ajax submission
         */
        $form['submit'] = array(
            '#type' => 'submit',
            '#value' => $button_text,
            //    '#ajax' => array(
            //     'url' => $submit_url,
            //    ),
            '#attributes' => array(
                'class' => array('add-fav-submit', $fav_class),
            ),
        );
        return $form;
    }
    
//    public function favorites_add_favorites_checkbox_form_callback(array &$form, FormStateInterface &$form_state) {
//        $form_state->setRebuild(TRUE);
//        return $form;
//    }
    
    /**
     * {@inheritdoc}
     * @todo obsolete?
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
        
    }
    
    /**
     * {@inheritdoc}
     * @todo obsolete?
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        
        $path = $form_state->getValue('path');
        $query = $form_state->getValue('query');
        
        $uid = \Drupal::currentUser()->id();
        $fid = FavoriteStorage::favExists($uid, $path, $query);
        
        $fav = new MyFavController();
        if ($fid) {
            $fav->remove($fid);
        } else {
            $fav->addFavJS();
        }
        \Drupal::service('cache_tags.invalidator')->invalidateTags(['config:block.block.myfavorites_2']);
        $form_state->setRebuild();
    }
    
}
