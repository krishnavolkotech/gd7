
import React, { useRef, useState, useEffect } from 'react'
import { Popover, Overlay } from 'react-bootstrap';

export default function DeployedReleasesTableRow({ deployment, detail }) {
  // Holds the popover text.
  const [info, setInfo] = useState(false);
  // Toggles the popover.
  const [show, setShow] = useState(false);
  // Target for the popover.
  const target = useRef(null);

  // Convert Unixtime to readable format.
  const date = new Date(deployment.date);
  const localeDate = date.toLocaleDateString('de-DE', {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
  });

  // Show popover after ajax call finished loading data.
  useEffect(() => {
    if (detail) {
      return;
    }
    if (info) {
      setShow(true);
    }
  }, [info])

const handleInfo = () => {
  if (show) {
    setShow(false);
  }
  if (!info) {
    fetch('/ajaxnode/deployed/' + deployment.nid)
      .then(response => response.json())
      .then(result => {
        setInfo(<div dangerouslySetInnerHTML={{ __html: result[1].data }} />);
      })
      .catch(error => console.log(error));
  }
  else if (!show) {
    setShow(true);
  }
}

const hide = () => {
  setShow(false);
}

if (!detail) {
  return (
    <tr>
      <td>{global.drupalSettings.states[deployment.state]}</td>
      <td>{global.drupalSettings.environments[deployment.environment]}</td>
      <td>{deployment.service}</td>
      <td>{deployment.release}</td>
      <td>{localeDate}</td>
      <td>
        <img
          ref={target}
          title="Einsatzinformationen anzeigen"
          className="deployed-info-icon deployed-tooltip"
          src="/modules/custom/hzd_release_management/images/notification-icon.png"
          onClick={handleInfo}>
        </img>
        <Overlay
          show={show}
          target={target.current}
          placement="left"
          rootClose
          onHide={hide}
        >
          <Popover id="popover-contained" title="Einsatzinformationen" className="margin-popover">
            {info}
          </Popover>
        </Overlay>
        <div className="other-links">
        {deployment.downloadLink &&
          <a href={deployment.downloadLink}><img title="Release herunterladen" src="/modules/custom/hzd_release_management/images/download_icon.png" />&nbsp;</a> 
        }
        {deployment.docuLink &&
          <a href={"/release-management/releases/documentation/" + deployment.serviceNid + "/" + deployment.releaseNid }><img title="Dokumentation ansehen" src="/modules/custom/hzd_release_management/images/document-icon.png" /></a>
        }
        </div>
      </td>
    </tr>
  );
}
else {
  return (
    <tr>
      <td>{global.drupalSettings.states[deployment.state]}</td>
      <td>{global.drupalSettings.environments[deployment.environment]}</td>
      <td>{deployment.release}</td>
      <td>{localeDate}</td>
      <td>{deployment.previousRelease}</td>
      {deployment.installationTime && 
        <td>{deployment.installationTime} Min</td>
      }
      {!deployment.installationTime &&
        <td></td>
      }
      <td>{["Nein", "Ja"][deployment.automatedDeployment]}</td>
      <td>{deployment.abnormalityDescription}</td>
    </tr>
  );
}
}
