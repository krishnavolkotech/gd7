import React, { useState, useEffect } from 'react';
import { Tooltip, OverlayTrigger } from 'react-bootstrap';

function EarlyWarningIndication({ release }) {
  var action;

  const tooltipEarlyWarningCreate = (
    <Tooltip id="tooltip">
      Early Warning erstellen.
    </Tooltip>
  );

  let serviceNid = release.serviceNid;
  let releaseNid = release.attributes.drupal_internal__nid;
  let count = release.links.appendix.meta.linkParams.earlyWarningCount;

  if (count > 0) {
    const tooltipEarlyWarning = (
      <Tooltip id="tooltip">
        { count } Early Warning(s) vorhanden.
      </Tooltip>
    );

    action = (
      <div>
        <OverlayTrigger placement="top" overlay={tooltipEarlyWarning}>
          <a href={release.links.appendix.href} >
            <span className="glyphicon glyphicon-warning-sign" />
          </a>
        </OverlayTrigger>
        <OverlayTrigger placement="top" overlay={tooltipEarlyWarningCreate}>
          <a href={`/release-management/add/early-warnings?destination=group/1/releases&services=${serviceNid}&releases=${releaseNid}`} >
            <span className="glyphicon glyphicon-plus-sign" />
          </a>
        </OverlayTrigger>
      </div>
    );
  }
  else {
    action = (
      <OverlayTrigger placement="top" overlay={tooltipEarlyWarningCreate}>
        <a href={`/release-management/add/early-warnings?destination=group/1/releases&services=${serviceNid}&releases=${releaseNid}`} >
          <span className="glyphicon glyphicon-plus-sign" />
        </a>
      </OverlayTrigger>
    );
  }

  // /release-management/add/early-warnings?destination=group/1/releases&services=1164&releases=78697&type=released&release_type=459
  return (
    <td>
      { action }
    </td>
  );
}

export default EarlyWarningIndication;