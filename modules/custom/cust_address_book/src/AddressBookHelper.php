<?php
namespace Drupal\cust_address_book;
use Drupal\Core\Url;
class AddressBookHelper { 
 static function alphabetic_list_users($url, $sql = NULL) {
    $alpha_array = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
    if($sql == NULL) {
      $sql = "SELECT DISTINCT UCASE(LEFT(lastname,1)) as alphabet FROM {cust_profile} ORDER BY alphabet";
    }
    $result = db_query($sql)->fetchAll();
    $alphabets = '';
    foreach($result as $alpha) {
      $users_list[] = $alpha->alphabet; 
    }
    foreach($alpha_array as $val) {
      if(in_array($val, $users_list)) {
        $query['query'] = array('name_st' => $val);
        $url = Url::fromUserInput('/user/search_page/' . $val, $query);
        $add_url = array('#title' => array('#markup' => '<span class="people_alphabet alphabets">' . $val . '</span>'), 
                     '#type' => 'link',
                     '#url' => $url,
                   );
        $alphabets .=  \Drupal::service('renderer')->renderRoot($add_url);
      }
      else {
        $alphabets .= '<span class="alphabets">' . $val . '</span>';
      }
    }
    $all_query['query'] = array('name_st' => 'All');
    $all_url = Url::fromUserInput('/user/search_page/all', $all_query);
    $add_all_url = array('#title' => array('#markup' => '<span class="people_alphabet alphabets">' . t('All') . '</span>'), 
                     '#type' => 'link',
                     '#url' => $all_url,
                   );
    $alphabets .=  \Drupal::service('renderer')->renderRoot($add_all_url);
    return  $alphabets;
  }
}
