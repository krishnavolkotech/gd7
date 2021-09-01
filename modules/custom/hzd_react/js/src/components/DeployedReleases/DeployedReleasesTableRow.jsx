import React from 'react'
import { Button, OverlayTrigger, Tooltip } from 'react-bootstrap';

export default function DeployedReleasesTableRow({ deployment, handleAction, highlight, handleArchive, handleEdit, isArchived }) {
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
  const releaseName = deployment.release;
  const deploymentId = deployment.uuid;

  const ttReportSuccessor = (
    <Tooltip id="ttReportSuccessor">
      Nachfolgerelease melden.
    </Tooltip>
  );

  const ttArchive = (
    <Tooltip id="ttArchive">
      Einsatzmeldung archivieren.
    </Tooltip>
  );

  const ttEdit = (
    <Tooltip id="ttEdit">
      Einsatzmeldung bearbeiten.
    </Tooltip>
  );

  const ttFail = (
    <Tooltip id="ttFail">
      Fehlmeldung markieren.
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
        { isArchived == "0" &&
        <span>
          <OverlayTrigger placement="top" overlay={ttReportSuccessor}>
            <Button bsStyle="primary" onClick={() => handleAction(userState, environment, service, release, deploymentId)}><span className="glyphicon glyphicon-forward" /></Button>
          </OverlayTrigger>
          &nbsp;
        </span>
        }
        { isArchived == "0" &&
        <span>
          <OverlayTrigger placement="top" overlay={ttArchive}>
            <Button bsStyle="info" onClick={() => handleArchive(deploymentId, releaseName)}><span className="glyphicon glyphicon-folder-close" /></Button>
          </OverlayTrigger>
          &nbsp;
        </span>
        }
        { global.drupalSettings.role !== "ZRML" &&
        <span>
          <OverlayTrigger placement="top" overlay={ttEdit}>
            {/* <Button href={"/node/" + deployment.nid + "/edit?destination=/zrml/r/einsatzmeldungen/eingesetzt"} bsStyle="primary"><span className="glyphicon glyphicon-edit" /></Button> */}
            <Button bsStyle="primary" onClick={() => handleEdit(deploymentId)}><span className="glyphicon glyphicon-edit" /></Button>
          </OverlayTrigger>
          &nbsp;
        </span>
        }
        { global.drupalSettings.role !== "ZRML" &&
        <OverlayTrigger placement="top" overlay={ttFail}>
          <Button bsStyle="danger" onClick={() => handleEdit(deploymentId)}><span className="glyphicon glyphicon-fire" /></Button>
        </OverlayTrigger>
        }
      </td>
    </tr>
  );
}
