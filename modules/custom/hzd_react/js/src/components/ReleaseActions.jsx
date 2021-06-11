import React, { useState } from 'react';
import { Modal, Button, Tooltip, OverlayTrigger } from 'react-bootstrap';
import DeploymentReport from './DeploymentReport';

function ReleaseActions({release}) {
  const [show, setShow] = useState(false);

  const tooltipEM = (
    <Tooltip id="tooltip">
      <strong>Releaseeinsatzmeldung</strong> durchführen.
    </Tooltip>
  );

  const tooltipEI = (
    <Tooltip id="tooltip">
      Eingsatzmeldungen ansehen.
    </Tooltip>
  );

  const tooltipDL = (
    <Tooltip id="tooltip">
      Release herunterladen.
    </Tooltip>
  );

  const tooltipDO = (
    <Tooltip id="tooltip">
      Dokumentation ansehen.
    </Tooltip>
  );

  const handleShow = () => {
    setShow(true);
  }

  const handleClose = () => {
    setShow(false);
  }

  return (
    <div>
      <a onClick={handleShow}>
        <OverlayTrigger placement="top" overlay={tooltipEM}>
          <span className="glyphicon glyphicon-check"></span>
        </OverlayTrigger>
        <span>&nbsp;</span>
      </a>
        <DeploymentReport release={release} show={show} handleClose={handleClose} />
      <a>
        <OverlayTrigger placement="top" overlay={tooltipEI}>
          <span className="glyphicon glyphicon-eye-open"></span>
        </OverlayTrigger>
        <span>&nbsp;</span>
      </a>
      <a>
        <OverlayTrigger placement="top" overlay={tooltipDL}>
          <span className="glyphicon glyphicon-floppy-disk"></span>
        </OverlayTrigger>
        <span>&nbsp;</span>
      </a>
      <a>
        <OverlayTrigger placement="top" overlay={tooltipDO}>
          <span className="glyphicon glyphicon-folder-open"></span>
        </OverlayTrigger>
      </a>
    </div>
  );
}

export default ReleaseActions;