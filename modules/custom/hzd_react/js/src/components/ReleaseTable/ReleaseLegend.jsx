import React from 'react'

export default function ReleaseLegend(props) {

  var legend = [
    <li><b>Legende:</b></li>,
    <li><img src="/modules/custom/hzd_release_management/images/download_icon.png" height="15" /> Download</li>,
    <li><img src="/modules/custom/hzd_release_management/images/document-icon.png" height="15" /> Dokumentation</li>,
    <li><img src="/modules/custom/hzd_release_management/images/icon.png" height="15" /> Early Warnings</li>,
    <li><img src="/modules/custom/hzd_release_management/images/create-icon.png" height="15" /> Early Warning erstellen</li>,
    <li><img class="white-bg" src="/modules/custom/hzd_release_management/images/e-icon-whitebg.png" height="18" /> Einsatzinformationen</li>,
  ];
  
  if (props.activeKey === "2" && ["ZRMK", "SITE-ADMIN"].includes(global.drupalSettings.role)) {
    // Legende für Releasekommentarfunktion.
    legend.push([
      <li><img src="/modules/custom/hzd_release_inprogress_comments/images/blue-icon.png" height="15" />Kommentare</li>,
      <li><img src="/modules/custom/hzd_release_inprogress_comments/images/create-green-icon.png" height="15" />Kommentieren</li>,
    ]);
  }

  if (props.activeKey === "3") {
    // Gesperrt.
    return "";
  }

  return (
    <div class="menu-filter">
      <ul>
        {legend}
      </ul>
    </div>
  );
}
