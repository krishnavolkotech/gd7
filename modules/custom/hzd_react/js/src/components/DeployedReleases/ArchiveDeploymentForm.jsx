import React, { useState } from "react";
import { Button, Modal } from 'react-bootstrap';
import { fetchWithCSRFToken } from "../../utils/fetch";

export default function ArchiveDeploymentForm(props) {
  const [showSaving, setShowSaving] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [hasError, setHasError] = useState(false);

  const handleHide = () => {
    props.setShowArchiveForm(false);
    props.setPrevDeploymentData(false);
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
        "id": props.prevDeploymentData.uuidDeployment,
        "attributes": {
          "field_deployment_status": "2"
        }
      }
    }
    const csrfUrl = `/session/token?_format=json`;
    const fetchUrl = '/jd7kfn9dm32ni/node/deployed_releases/' + props.prevDeploymentData.uuidDeployment;
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
        setIsLoading(false);
        if ("errors" in antwort) {
          setHasError(true);
        }
        else {
          props.setDeploymentHistory(prev => [...prev, parseInt(props.prevDeploymentData.nid)]);
          props.setCount(props.count + 1);
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
            <p>Die Einsatzmeldung konnte nicht archiviert werden. Bitte probieren Sie es erneut. Bitte wenden Sie sich an das BpK Team, sollte der Fehler bestehen bleiben. <span className="glyphicon glyphicon-exclamation-sign" /></p>
          }
        </Modal.Body>
        <Modal.Footer>
          <Button bsStyle="danger" onClick={handleHide}><span className="glyphicon glyphicon-remove" /> Schließen</Button>
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
          <Button bsStyle="primary" onClick={handleSave} name="Archivieren bestätigen"><span className="glyphicon glyphicon-ok" /> Archivieren</Button>
          <Button bsStyle="danger" onClick={handleHide} name="Abbrechen"><span className="glyphicon glyphicon-remove" /> Abbrechen</Button>
        </Modal.Footer>
      </Modal>
    </div>  )
}
