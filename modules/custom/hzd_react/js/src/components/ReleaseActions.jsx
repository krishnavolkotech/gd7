import React, { useState } from 'react';
import { Modal, Button, Tooltip, OverlayTrigger } from 'react-bootstrap';

function ReleaseActions(props) {
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
      <Modal show={show} onHide={handleClose}>
        <Modal.Header closeButton>
          <Modal.Title>Modal heading</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <h4>Text in a modal</h4>
          <p>
            Duis mollis, est non commodo luctus, nisi erat porttitor ligula.
          </p>

          <h4>Tooltips in a modal</h4>

          <hr />

          <h4>Overflowing text to show scroll behavior</h4>
          <p>
            Cras mattis consectetur purus sit amet fermentum. Cras justo odio,
            dapibus ac facilisis in, egestas eget quam. Morbi leo risus, porta
            ac consectetur ac, vestibulum at eros.
          </p>
          <p>
            Praesent commodo cursus magna, vel scelerisque nisl consectetur
            et. Vivamus sagittis lacus vel augue laoreet rutrum faucibus dolor
            auctor.
          </p>
          <p>
            Aenean lacinia bibendum nulla sed consectetur. Praesent commodo
            cursus magna, vel scelerisque nisl consectetur et. Donec sed odio
            dui. Donec ullamcorper nulla non metus auctor fringilla.
          </p>
          <p>
            Cras mattis consectetur purus sit amet fermentum. Cras justo odio,
            dapibus ac facilisis in, egestas eget quam. Morbi leo risus, porta
            ac consectetur ac, vestibulum at eros.
          </p>
          <p>
            Praesent commodo cursus magna, vel scelerisque nisl consectetur
            et. Vivamus sagittis lacus vel augue laoreet rutrum faucibus dolor
            auctor.
          </p>
          <p>
            Aenean lacinia bibendum nulla sed consectetur. Praesent commodo
            cursus magna, vel scelerisque nisl consectetur et. Donec sed odio
            dui. Donec ullamcorper nulla non metus auctor fringilla.
          </p>
          <p>
            Cras mattis consectetur purus sit amet fermentum. Cras justo odio,
            dapibus ac facilisis in, egestas eget quam. Morbi leo risus, porta
            ac consectetur ac, vestibulum at eros.
          </p>
          <p>
            Praesent commodo cursus magna, vel scelerisque nisl consectetur
            et. Vivamus sagittis lacus vel augue laoreet rutrum faucibus dolor
            auctor.
          </p>
          <p>
            Aenean lacinia bibendum nulla sed consectetur. Praesent commodo
            cursus magna, vel scelerisque nisl consectetur et. Donec sed odio
            dui. Donec ullamcorper nulla non metus auctor fringilla.
          </p>
        </Modal.Body>
        <Modal.Footer>
          <Button onClick={handleClose}>Close</Button>
        </Modal.Footer>
      </Modal>
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