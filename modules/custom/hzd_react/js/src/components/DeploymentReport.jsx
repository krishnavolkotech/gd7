import React from 'react';
import { Modal, Button, Tooltip, OverlayTrigger } from 'react-bootstrap';

function DeploymentReport({release, show, handleClose}) {
  return (
    <Modal show={show} onHide={handleClose}>
      <Modal.Header closeButton>
        <Modal.Title>Modal heading</Modal.Title>
      </Modal.Header>
      <Modal.Body>
        <p>Test</p>
      </Modal.Body>
      <Modal.Footer>
        <Button onClick={handleClose}>Close</Button>
      </Modal.Footer>
    </Modal>  
  );
}

export default DeploymentReport;