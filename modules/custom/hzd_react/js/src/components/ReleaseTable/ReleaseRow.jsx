import React from 'react'
import { useHistory } from 'react-router-dom';

export default function ReleaseRow(props) {

  /** @const {object} history - The history object (URL modifications). */
  const history = useHistory()
  const pathname = history.location.pathname;
  const explodedPath = pathname.split("/");
  let humanDateFormat = "";
  if (props.release.attributes.field_date !== null) {
    const unixTimestamp = props.release.attributes.field_date;
  
    const milliseconds = unixTimestamp * 1000;
  
    const dateObject = new Date(milliseconds);
  
    const options = {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
    };
    humanDateFormat = dateObject.toLocaleDateString('de-DE', options);
  }
 
  // if ((props.filterState.type = 549) || ("early-warnings" in props.release.links)) {
  //   props.release.links["early-warnings"].href += "type=459"
  // }
  // console.log(props.release.links);

  let groupPath = history.location.pathname.split("/")[1];
  if (history.location.pathname.split("/")[1] === "group") {
    groupPath += "/" + history.location.pathname.split("/")[2];
  }
  // let groupName = "";
  // if (explodedPath[1]==="group") {
  //   groupName = "release-management";
  // }
  // The comment, if the release is locked.
  let comment = "";
  // console.log(props.release.attributes.field_release_comments)
  if (props.release.attributes.field_release_comments !== null) {
    comment = props.release.attributes.field_release_comments.value;
  }
  // Holds links to the early warnings, release comments and links to create them.
  let comments = [];
  if ( "early-warnings" in props.release.links){
    // Add link to early warnings.
    comments.push(<a key={"ew-" + props.release.attributes.drupal_internal__nid} href={props.release.links["early-warnings"].href} className="view-earlywarning" title="Early Warnings für dieses Release anzeigen"><span className="warningcount">{props.release.links["early-warnings"].meta.linkParams.earlyWarningCount}</span></a>);
  } 
  else {
    // Adds placeholder for early warnings.
    comments.push(<a key={"no-ew-" + props.release.attributes.drupal_internal__nid}><span className="nonewarningcount"></span></a>);
  }
  // Adds link to create new early warnings.
  comments.push(<a key={"ew-add" + props.release.attributes.drupal_internal__nid} href={'/release-management/add/early-warnings?services=' + props.release.serviceNid + '&releases=' + props.release.attributes.drupal_internal__nid + '&type=progress&release_type=459'} className="create_earlywarning" title="Early Warning für dieses Release erstellen"><img src="/modules/custom/hzd_release_management/images/create-icon.png" height="15" /></a>);

  if ("release-comments" in props.release.links && props.filterState.releaseStatus === "2") {
    // Add link to create new release comments.
    comments.push(<a key={"rc-add-" + props.release.attributes.drupal_internal__nid} href={'/release-management/add/release-comments?services=' + props.release.serviceNid + '&releases=' + props.release.attributes.drupal_internal__nid + '&type=progress&release_type=459'} className="create_comment" title="Release kommentieren"><img src="/modules/custom/hzd_release_inprogress_comments/images/create-green-icon.png" height="15" />&nbsp;</a>);
    if (props.release.links["release-comments"].meta.linkParams.releaseCommentCount > 0) {
      // Add link to to release comments.
      comments.push(
        <a key={"rc-" + props.release.attributes.drupal_internal__nid} href={props.release.links["release-comments"].href} className="view-comment" title="Releasekommentare für dieses Release anzeigen"><span className="commentcount">{props.release.links["release-comments"].meta.linkParams.releaseCommentCount}</span></a>
      );
    }
  }
  else {
    // Add placeholder for release comments.
    comments.push(<a key={"no-rc-" + props.release.attributes.drupal_internal__nid}><span className="nonecommentcount"></span></a>);
  }

  let actions = [];
  if ("deployed-releases" in props.release.links) {
    // Add link for deployment informations.
    // props.release.links["deployed-releases"].href+ "&type=460";
    var deploymentInfoLink = props.release.links["deployed-releases"].href;
    if (history.location.pathname.includes('release-management') == false) {
      deploymentInfoLink = deploymentInfoLink.replace('release-management', groupPath);
    }
    actions.push(<a key={"ei-" + props.release.attributes.drupal_internal__nid} href={deploymentInfoLink}><img title="Einsatzinformationen anzeigen" className="e-info-icon" src="/modules/custom/hzd_release_management/images/e-icon.png" />&nbsp;</a>);
  }
  
  if (props.release.attributes.field_link) {
    // Add link for release download.
    actions.push(<a key={"li-" + props.release.attributes.drupal_internal__nid} href={props.release.attributes.field_link}><img src="/modules/custom/hzd_release_management/images/download_icon.png" title="Release herunterladen" />&nbsp;</a>);
  }
  // if (props.release.attributes.field_documentation_link && explodedPath[1]!="group") {
  if (props.release.attributes.field_documentation_link) {
     // Add link for documentation download.
    actions.push(<a key={"dl-" + props.release.attributes.drupal_internal__nid} href={'/' + explodedPath[1] + '/releases/documentation/' + props.release.serviceNid + '/' + props.release.attributes.drupal_internal__nid}><img src="/modules/custom/hzd_release_management/images/document-icon.png" title="Dokumentation ansehen" /></a>);
  }
  // if (props.release.attributes.field_documentation_link && explodedPath[1] === "group") {
  //   // Add link for documentation download.
  //   actions.push(<a key={"dl-" + props.release.attributes.drupal_internal__nid} href={'/' + groupName + '/releases/documentation/' + props.release.serviceNid + '/' + props.release.attributes.drupal_internal__nid}><img src="/modules/custom/hzd_release_management/images/document-icon.png" title="Dokumentation ansehen" /></a>);
  // }
  return (
    <tr>
      <td>{props.release.serviceName}</td>
      <td>{props.release.attributes.title}</td>
      <td>{props.release.attributes.field_status}</td>
      <td>{humanDateFormat}</td>
      {props.filterState.releaseStatus !== "3" &&
      <td className="earlywarnings-cell inprogress-comment-cell">{comments}</td>
      }
      {props.filterState.releaseStatus !== "3" &&
      <td>{actions}</td>
      }
      { props.filterState.releaseStatus === "3" &&
      <td className="earlywarnings-cell inprogress-comment-cell">{comment}</td>
      }
    </tr>
  );
}
