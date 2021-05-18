import React, { useState, useEffect } from 'react';
import { Tooltip, OverlayTrigger } from 'react-bootstrap';

function EarlyWarningIndication({ releaseNid, serviceNid }) {
  const [earlyWarnings, setEarlyWarnings] = useState('');
  var action;


  useEffect(() => {
    const url = '/jsonapi/node/early_warnings';
    // const filter = '&fields[node--early_warnings]=field_release_name';
    const filter2 = '?filter[field_earlywarning_release]=' + releaseNid;
    const headers = new Headers({
      Accept: 'application/vnd.api+json',
    });
    const fetchUrl = url + filter2;
    // console.log(fetchUrl);

    // Fetch associated service title.
    fetch(fetchUrl, { headers })
      .then(response => response.json())
      .then(results => {
        // console.log(results.data);
        setEarlyWarnings(results.data);
      })
      .catch(error => console.log("error", error));
  }, [releaseNid]);

  const tooltipEarlyWarningCreate = (
    <Tooltip id="tooltip">
      Early Warning erstellen.
    </Tooltip>
  );

  if (!Array.isArray(earlyWarnings)) {
    action = <span className="glyphicon glyphicon-refresh glyphicon-spin" role="status"><span className="sr-only">Lade...</span></span>;
  }
  else if (earlyWarnings.length > 0) {

    const tooltipEarlyWarning = (
      <Tooltip id="tooltip">
        { earlyWarnings.length } Early Warning(s) vorhanden.
      </Tooltip>
    );

    action = (
      <div>
        <OverlayTrigger placement="top" overlay={tooltipEarlyWarning}>
          <a href={`/release-management/view-early-warnings?releases=${releaseNid}`} >
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
      { action}
    </td>
  );
}

export default EarlyWarningIndication;