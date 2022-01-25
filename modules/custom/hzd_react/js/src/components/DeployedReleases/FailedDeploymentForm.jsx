import React, { useState } from "react";
import { Button, Modal } from 'react-bootstrap';
import { fetchWithCSRFToken } from "../../utils/fetch";

export default function FailedDeploymentForm(props) {
  const [showSaving, setShowSaving] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [hasError, setHasError] = useState(false);

  const handleHide = () => {
    props.setShowFailedForm(false);
    props.setPrevDeploymentData(false);
    setShowSaving(false);
    setIsLoading(false);
    setHasError(false);
  }

  const handleSave = () => {
    setShowSaving(true);
    setIsLoading(true);
    const body = {
      "data": {
        "type": "node--deployed_releases",
        "id": props.prevDeploymentData.uuidDeployment,
        "attributes": {
          "field_deployment_status": "3"
        }
      }
    }
    const csrfUrl = `/session/token?_format=json`;
    const fetchUrl = '/jd7kfn9dm32ni/node/deployed_releases/' + props.prevDeploymentData.uuidDeployment;
    const fetchOptions = {
      "method": 'PATCH',
      "headers": new Headers({
        'Accept': 'application/vnd.api+json',
        'Content-Type': 'application/vnd.api+json',
        'Cache': 'no-cache',
      }),
      "body": JSON.stringify(body),
    }

    fetchWithCSRFToken(csrfUrl, fetchUrl, fetchOptions)
      .then(antwort => antwort.json())
      .then(antwort => {
        setIsLoading(false);
        if ("errors" in antwort) {
          setHasError(true);
        }
        else {
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
          <Modal.Title>Fehlmeldung kennzeichnen</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {isLoading &&
            <p>Die Einsatzmeldung wird aktualisiert ... <span className="glyphicon glyphicon-refresh glyphicon-spin" role="status" /></p>
          }
          {!isLoading && !hasError &&
            <p>Die Einsatzmeldung wurde als Fehlmeldung markiert. <span className="glyphicon glyphicon-ok" /></p>
          }
          {hasError &&
            <p>Die Einsatzmeldung konnte nicht aktualisiert werden. Bitte probieren Sie es erneut. Wenden Sie sich an das BpK Team, sollte der Fehler bestehen bleiben. <span className="glyphicon glyphicon-exclamation-sign" /></p>
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
      <Modal show={props.showFailedForm} onHide={handleHide}>
        <Modal.Header closeButton>
          <Modal.Title>Fehlmeldung kennzeichnen</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <p>Möchten Sie <strong>{props.prevDeploymentData.releaseName}</strong> wirklich den Status "Fehlmeldung" zuweisen?</p>
          <p>Die Änderung kann nicht rückgängig gemacht werden.</p>
        </Modal.Body>
        <Modal.Footer>
          <Button bsStyle="primary" onClick={handleSave} ><span className="glyphicon glyphicon-ok" /> Speichern</Button>
          <Button bsStyle="danger" onClick={handleHide}><span className="glyphicon glyphicon-remove" /> Abbrechen</Button>
        </Modal.Footer>
      </Modal>
    </div>  )
}
