import React from 'react'
import { Button, OverlayTrigger, Tooltip } from 'react-bootstrap';

export default function ManageDeployedReleasesTableRow({ deployment, handleAction, highlight, handleArchive, handleEdit, status, handleView }) {
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
  const uuidDeployment = deployment.uuid;
  const nid = deployment.nid;

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

  const ttView = (
    <Tooltip id="ttView">
      Einsatzmeldung ansehen.
    </Tooltip>
  );

  // Apply row highlighting class.
  // "info": deployment.changed younger than 10 hours.
  // "success": Deployment has been updated in the current session.
  const now = Date.now() / 1000;
  const recent = now - 60 * 60 * 10;
  const changed = parseInt(deployment.changed);
  let rowClass = "";

  if (changed > recent) {
    rowClass = "info";
  }
  if (highlight != "") {
    rowClass = highlight;
  }

  return (
    <tr className={rowClass}>
      <td>{global.drupalSettings.states[deployment.state]}</td>
      <td>{global.drupalSettings.environments[deployment.environment]}</td>
      <td>{deployment.service}</td>
      <td>{deployment.release}</td>
      <td>{localeDate}</td>
      <td>
        { status == "1" &&
        <span>
          <OverlayTrigger placement="top" overlay={ttReportSuccessor}>
            <Button bsStyle="success" onClick={(e) => handleAction(e, "successor", { state, environment, service, release, product, releaseName })} alt="Nachfolgerelease melden" name="Nachfolgerelease melden"><span className="glyphicon glyphicon-forward" /></Button>
          </OverlayTrigger>
          &nbsp;
        </span>
        }
        { status == "1" &&
        <span>
          <OverlayTrigger placement="top" overlay={ttArchive}>
            <Button bsStyle="info" onClick={(e) => handleAction(e, "archive", { nid, uuidDeployment, releaseName })} alt="Archivieren" name="Archivieren"><span className="glyphicon glyphicon-folder-close" /></Button>
          </OverlayTrigger>
          &nbsp;
        </span>
        }
        { global.drupalSettings.role !== "ZRML" &&
        <span>
          <OverlayTrigger placement="top" overlay={ttEdit}>
            {/* <Button href={"/node/" + deployment.nid + "/edit?destination=/zrml/einsatzmeldungen/eingesetzt"} bsStyle="primary"><span className="glyphicon glyphicon-edit" /></Button> */}
            <Button bsStyle="primary" onClick={(e) => handleAction(e, "edit", { nid, uuidDeployment, releaseName })} alt="Bearbeiten" name="Bearbeiten"><span className="glyphicon glyphicon-edit" /></Button>
          </OverlayTrigger>
          &nbsp;
        </span>
        }
        { global.drupalSettings.role !== "ZRML" && ["1", "2"].includes(status) &&
        <span>
          <OverlayTrigger placement="top" overlay={ttFail}>
            <Button bsStyle="danger" onClick={(e) => handleAction(e, "failed", { nid, uuidDeployment, releaseName })} alt="Fehlmeldung kennzeichnen" name="Fehlmeldung kennzeichnen"><span className="glyphicon glyphicon-fire" /></Button>
          </OverlayTrigger>
          &nbsp;
        </span>
        }
        {/* <Button bsStyle="link" href={"/node/" + nid}><span className="glyphicon glyphicon-eye-open" /></Button> */}
          <OverlayTrigger placement="top" overlay={ttView}>
            <Button bsStyle="primary" onClick={() => handleView(nid)} alt="Einsatzmeldung aufrufen" name="Einsatzmeldung aufrufen"><span className="glyphicon glyphicon-eye-open" /></Button>
          </OverlayTrigger>
      </td>
    </tr>
  );
}
