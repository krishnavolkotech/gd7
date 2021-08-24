import React from 'react'
import { Button, OverlayTrigger, Tooltip } from 'react-bootstrap';

export default function EinsatzmeldungsZeile({ deployment, handleAction, highlight }) {
  const date = new Date(deployment.date);
  const localeDate = date.toLocaleDateString('de-DE', {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
  });
  const userState = deployment.state;
  const environment = deployment.environment;
  const service = deployment.serviceNid;
  const release = deployment.releaseNid;
  const deploymentId = deployment.id;

  const ttReportSuccessor = (
    <Tooltip id="ttReportSuccessor">
      Nachfolgerelease melden
    </Tooltip>
  );

  return (
    <tr className={highlight}>
      <td>{global.drupalSettings.states[deployment.state]}</td>
      <td>{global.drupalSettings.environments[deployment.environment]}</td>
      <td>{deployment.service}</td>
      <td>{deployment.release}</td>
      <td>{localeDate}</td>
      <td>
        <OverlayTrigger placement="top" overlay={ttReportSuccessor}>
          <Button bsStyle="primary" onClick={() => handleAction(userState, environment, service, release, deploymentId)}><span className="glyphicon glyphicon-forward" /></Button>
        </OverlayTrigger>
      </td>
    </tr>
  );
}
