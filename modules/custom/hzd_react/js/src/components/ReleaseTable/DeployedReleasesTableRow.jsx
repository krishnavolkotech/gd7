
import React, { useRef, useState, useEffect } from 'react'
import { Popover, Overlay } from 'react-bootstrap';

/**
 * The DeployedReleasesTableRow React component.
 */
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
      // Do nothing if detailed table is active.
      return;
    }
    if (info) {
      // Show popover, if info text is available (loaded). Prevents alignment
      // issues, when popover is shown before the info-text has been loaded.
      setShow(true);
    }
  }, [info])

  /**
   * Handles info-icon press. Toggles Info Popover.
   */
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

  /**
   * Handles closing of the info-popover.
   */
  const hide = () => {
    setShow(false);
  }

  if (!detail) {
    // Returns the default view.
    return (
      <tr>
        {deployment.status === "1" &&
          <td>{global.drupalSettings.states[deployment.state]}</td>
        }
        {deployment.status === "2" &&
          <td><i>{global.drupalSettings.states[deployment.state]}</i></td>
        }
        {deployment.status === "1" &&
          <td>{global.drupalSettings.environments[deployment.environment]}</td>
        }
        {deployment.status === "2" &&
          <td><i>{global.drupalSettings.environments[deployment.environment]}</i></td>
        }
        {deployment.status === "1" &&
          <td>{deployment.service}</td>
        }
        {deployment.status === "2" &&
          <td><i>{deployment.service}</i></td>
        }
        {deployment.status === "1" &&
          <td>{deployment.release}</td>
        }
        {deployment.status === "2" &&
          <td><i>{deployment.release}</i></td>
        }
        {deployment.status === "1" &&
          <td>{localeDate}</td>
        }
        {deployment.status === "2" &&
          <td><i>{localeDate}</i></td>
        }
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
    // Returns the detailed view ("Einsatzinformationen").
    return (
      <tr>
        {deployment.status === "1" &&
          <td>{global.drupalSettings.states[deployment.state]}</td>
        }
        {deployment.status === "2" &&
          <td><i>{global.drupalSettings.states[deployment.state]}</i></td>
        }
        {deployment.status === "1" &&
          <td>{global.drupalSettings.environments[deployment.environment]}</td>
        }
        {deployment.status === "2" &&
          <td><i>{global.drupalSettings.environments[deployment.environment]}</i></td>
        }
        {deployment.status === "1" &&
          <td>{deployment.release}</td>
        }
        {deployment.status === "2" &&
          <td><i>{deployment.release}</i></td>
        }
        {deployment.status === "1" &&
          <td>{localeDate}</td>
        }
        {deployment.status === "2" &&
          <td><i>{localeDate}</i></td>
        }
        {deployment.previousRelease &&
        <td>
          {deployment.status === "1" &&
            deployment.previousRelease
          }
          {deployment.status === "2" &&
            <i>{deployment.previousRelease}</i>
          }
        </td>
        }
        {!deployment.previousRelease &&
          <td>
            {deployment.status === "1" &&
              "Ersteinsatz"
            }
            {deployment.status === "2" &&
              <i>Ersteinsatz</i>
            }
          </td>
        }
        {deployment.installationTime && 
          <td>
            {deployment.status === "1" &&
              deployment.installationTime + " min"
            }
            {deployment.status === "2" &&
              <i>{deployment.installationTime} min</i>
            }
          </td>
        }
        {!deployment.installationTime &&
          <td></td>
        }
        {deployment.status === "1" &&
          <td>{["Nein", "Ja"][deployment.automatedDeployment]}</td>
        }
        {deployment.status === "2" &&
          <td><i>{["Nein", "Ja"][deployment.automatedDeployment]}</i></td>
        }
        {deployment.status === "1" &&
          <td>{deployment.abnormalityDescription}</td>
        }
        {deployment.status === "2" &&
          <td><i>{deployment.abnormalityDescription}</i></td>
        }
      </tr>
    );
  }
}
