import React, { useState, useEffect } from "react";
import { Form, FormGroup, FormControl, ControlLabel, Checkbox, Button, Modal, OverlayTrigger, Tooltip, Radio } from 'react-bootstrap';
import { fetchWithCSRFToken } from "../../utils/fetch";

export default function ArchiveForm(props) {
  const [showSaving, setShowSaving] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [hasError, setHasError] = useState(false);

  // props.showArchiveForm
  // props.setShowArchiveForm
  // props.prevDeploymentId
  // props.setCount

  const handleHide = () => {
    props.setShowArchiveForm(false);
    setShowSaving(false);
    setIsLoading(false);
    setHasError(false);
  }

  const handleSave = () => {
    setShowSaving(true);
    setIsLoading(true);
    const archiveBody = {
      "data": {
        "type": "node--deployed_releases",
        "id": props.prevDeploymentId,
        "attributes": {
          "field_deployment_status": "2"
        }
      }
    }
    const csrfUrl = `/session/token?_format=json`;
    const fetchUrl = '/jsonapi/node/deployed_releases/' + props.prevDeploymentId;
    const fetchOptions = {
      method: 'PATCH',
      headers: new Headers({
        'Accept': 'application/vnd.api+json',
        'Content-Type': 'application/vnd.api+json',
        'Cache': 'no-cache',
      }),
      body: JSON.stringify(archiveBody),
    }

    fetchWithCSRFToken(csrfUrl, fetchUrl, fetchOptions)
      .then(antwort => antwort.json())
      .then(antwort => {
        console.log(antwort);
        setIsLoading(false);
        console.log(props.count);
        props.setCount(props.count + 1);
        if ("errors" in antwort) {
          setHasError(true);
        }
      })
      .catch(error => {
        console.log('fehler:', error);
        setHasError(true);
      });

  }

  if (showSaving) {
    return (
      <Modal show={true} onHide={handleHide}>
        <Modal.Header closeButton>
          <Modal.Title>Einsatzmeldung archivieren</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {isLoading &&
            <p>Die Einsatzmeldung wird aktualisiert ... <span className="glyphicon glyphicon-refresh glyphicon-spin" role="status" /></p>
          }
          {!isLoading && !hasError &&
            <p>Die Einsatzmeldung wurde erfolgreich archiviert. <span className="glyphicon glyphicon-ok" /></p>
          }
          {hasError &&
            <p>Die Einsatzmeldung konnte nicht archiviert werden. Bitte wenden Sie sich an das BpK Team. <span className="glyphicon glyphicon-exclamation-sign" /></p>
          }

        </Modal.Body>
        <Modal.Footer>
          <Button bsStyle="danger" onClick={handleHide}>Schließen</Button>
        </Modal.Footer>
      </Modal>
    );
  }

  return (
    <div>
      <Modal show={props.showArchiveForm} onHide={handleHide}>
        <Modal.Header closeButton>
          <Modal.Title>Einsatzmeldung archivieren</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <p>Möchten Sie <strong>{props.prevDeploymentData.releaseName}</strong> wirklich archivieren?</p>
          <p><strong>Hinweis:</strong> Führen Sie die Archivierung bitte nur dann durch, wenn das Produkt in der entsprechenden Umgebung nicht mehr eingesetzt werden soll. Die Meldung eines Nachfolgereleases ist dann nicht mehr möglich.</p>
          <p>Die Aktion kann nicht rückgängig gemacht werden.</p>
        </Modal.Body>
        <Modal.Footer>
          <Button bsStyle="primary" onClick={handleSave} >Archivieren</Button>
          <Button bsStyle="danger" onClick={handleHide}>Abbrechen</Button>
        </Modal.Footer>
      </Modal>
    </div>  )
}
