<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Drupal\hzd_customizations\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Description of QuickinfopdfController.
 *
 * @author sureshk
 */
class QuickinfopdfController extends ControllerBase {

  /**
   * Menu callback; presents the node editing form, or redirects to delete confirmation.
   */
  public function custom_quickinfo_pdf($node) {
    // drupal_set_header('Content-Type: text/plain');.
    global $base_url;
    $output = '<html><head>';
    $output .= "<script>

function subst() {
  var vars={};
  var x=document.location.search.substring(1).split('&');
  for(var i in x) {var z=x[i].split('=',2);vars[z[0]] = unescape(z[1]);}
  var x=['frompage','topage','page','webpage','section','subsection','subsubsection'];
  for(var i in x) {
    var y = document.getElementsByClassName(x[i]);
    for(var j=0; j<y.length; ++j) y[j].textContent = vars[x[i]];
  }
}
</script></head>";
    $output .= "<style>";
    $output .= " table {
                 /*margin-left: 2cm;*/
              }
              img {
                padding: 20px;
              }
.padding-5 {
  padding:5px;
  font-size:14px;
}
.title {
  text-align: center;
  font-size: 16px;
}
body {
  font-size: 16px;
}
a {
  color: #376092;
  text-decoration: none;
}
";
    $output .= "</style>";
    $output .= "<body  onload='subst()'>";
    $output .= "<table  cellpadding='0' cellspacing='0' border='1'  style=' margin-bottom:15px !important; padding-bottom:0px !important;' width='100%'>";
    $output .= '<tr><td  rowspan="3"><img src= "' . $base_url . '/' . drupal_get_path('module', 'hzd_customizations') . '/hzd_logo.gif" alt ="Logo"></td>';
    $unique_id = $node->status ? $node->field_unique_id[0]['value'] : t("XXXX");
    $output .= '<td   rowspan="3" class="title"><b>' . t('RZ-Schnellinfo-Nr.') . ' ' . $unique_id . '</b><br>' . t($node->title) . '</td>';
    $published_on = $node->status ? date('d.m.Y', $node->changed) : t("PREVIEW");
    $output .= '<td class="padding-5" style="min-width:110px;">' . t("Published on") . ': <br>' . $published_on . '</td></tr>';
    if ($node->status) {
      $published_by = db_result(\Drupal::database()->query("SELECT CONCAT(firstname, ' ', lastname) FROM {cust_profile} WHERE uid = %d", $node->revision_uid));
    }
    else {
      $published_by = "<br/>";
    }
    $output .= '<tr><td  class="padding-5" style="min-width:110px;">' . t("Published by") . ': <br>' . $published_by . '</td></tr>';
    $output .= '<tr><td class="padding-5" style="min-width:110px;">' . t('Page') . ' <span class="page"></span> ' . t('of') . ' <span class="topage"></span>' . '</td></tr>';
    $output .= "</table></body></html>";
    print $output;
    exit(0);
  }

}
