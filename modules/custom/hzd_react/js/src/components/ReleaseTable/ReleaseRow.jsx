import React from 'react'

export default function ReleaseRow(props) {

  const unixTimestamp = props.release.attributes.field_date;

  const milliseconds = unixTimestamp * 1000;

  const dateObject = new Date(milliseconds);

  const options = {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  };
  const humanDateFormat = dateObject.toLocaleDateString('de-DE', options);

  let comments = [];
  if ("early-warnings" in props.release.links) {
    comments.push(<a href={props.release.links["early-warnings"].href} class="view-earlywarning" title="Early Warnings für dieses Release anzeigen"><span class="warningcount">{props.release.links["early-warnings"].meta.linkParams.earlyWarningCount}</span>&nbsp;</a>);
  }
  else {
    comments.push(<a><span class="nonecommentcount"></span></a>);
  }
  comments.push(<a href={'/release-management/add/early-warnings?services=' + props.release.serviceNid + '&amp;releases=' + props.release.attributes.drupal_internal__nid + '&amp;type=progress&amp;release_type=459'} class="create_earlywarning" title="Early Warning für dieses Release erstellen"><img src="/modules/custom/hzd_release_management/images/create-icon.png" height="15" />&nbsp;</a>);

  if ("release-comments" in props.release.links) {
    comments.push(<a href={'/release-management/add/release-comments?services=' + props.release.serviceNid + '&amp;releases=' + props.release.attributes.drupal_internal__nid + '&amp;type=progress&amp;release_type=459'} class="create_comment" title="Release kommentieren"><img src="/modules/custom/hzd_release_inprogress_comments/images/create-green-icon.png" height="15" />&nbsp;</a>);
    if (props.release.links["release-comments"].meta.linkParams.releaseCommentCount > 0) {
      comments.push(
        <a href={props.release.links["release-comments"].href} class="view-comment" title="Releasekommentare für dieses Release anzeigen"><span class="commentcount">{props.release.links["release-comments"].meta.linkParams.releaseCommentCount}</span></a>
      );
    }
  }
  else {
    comments.push(<a><span class="nonecommentcount"></span></a>);
  }

  let actions = [];
  if ("deployed-releases" in props.release.links) {
    actions.push(<a href={props.release.links["deployed-releases"].href}><img title="Einsatzinformationen anzeigen" class="e-info-icon" src="/modules/custom/hzd_release_management/images/e-icon.png" />&nbsp;</a>);
  }
  if (props.release.attributes.field_link) {
    actions.push(<a href={props.release.attributes.field_link}><img src="/modules/custom/hzd_release_management/images/download_icon.png" title="Release herunterladen" />&nbsp;</a>);
  }
  if (props.release.attributes.field_documentation_link) {
    actions.push(<a href={'/release-management/releases/documentation/' + props.release.serviceNid + '/' + props.release.attributes.drupal_internal__nid}><img src="/modules/custom/hzd_release_management/images/document-icon.png" title="Dokumentation ansehen" /></a>);
  }

  return (
    <tr>
      <td>{props.release.serviceName}</td>
      <td>{props.release.attributes.title}</td>
      <td>{props.release.attributes.field_status}</td>
      <td>{humanDateFormat}</td>
      <td>{comments}</td>
      <td>{actions}</td>
    </tr>
  );
}
