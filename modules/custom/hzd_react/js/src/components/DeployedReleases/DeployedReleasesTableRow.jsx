import React from 'react'
import { Button, OverlayTrigger, Tooltip } from 'react-bootstrap';

export default function DeployedReleasesTableRow({ deployment, handleAction, highlight, handleArchive, handleEdit, status }) {
  const date = new Date(deployment.date);
  const localeDate = date.toLocaleDateString('de-DE', {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
  });
  const state = deployment.state;
  const environment = deployment.environment;
  const service = deployment.serviceNid;
  const release = deployment.releaseNid;
  const releaseName = deployment.release;
  const product = releaseName.split("_")[0] + "_";
  const uuid = deployment.uuid;
  const nid= deployment.nid;

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
        { status == "1" &&
        <span>
          <OverlayTrigger placement="top" overlay={ttReportSuccessor}>
            <Button bsStyle="primary" onClick={() => handleAction("successor", {state, environment, service, release, product, uuid})}><span className="glyphicon glyphicon-forward" /></Button>
          </OverlayTrigger>
          &nbsp;
        </span>
        }
        { status == "1" &&
        <span>
          <OverlayTrigger placement="top" overlay={ttArchive}>
            <Button bsStyle="info" onClick={() => handleAction("archive", {nid, uuid, releaseName})}><span className="glyphicon glyphicon-folder-close" /></Button>
          </OverlayTrigger>
          &nbsp;
        </span>
        }
        { global.drupalSettings.role !== "ZRML" &&
        <span>
          <OverlayTrigger placement="top" overlay={ttEdit}>
            {/* <Button href={"/node/" + deployment.nid + "/edit?destination=/zrml/r/einsatzmeldungen/eingesetzt"} bsStyle="primary"><span className="glyphicon glyphicon-edit" /></Button> */}
            <Button bsStyle="primary" onClick={() => handleAction("edit", { nid, uuid, releaseName })}><span className="glyphicon glyphicon-edit" /></Button>
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
