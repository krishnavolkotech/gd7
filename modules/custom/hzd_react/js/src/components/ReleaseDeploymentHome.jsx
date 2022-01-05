import React from 'react';
import { Grid, Row, Col, Panel, Button, Alert } from 'react-bootstrap';
import {Link} from 'react-router-dom';

function ReleaseDeploymentHome() {

  // Catch Internet Explorer users (No IE support).
  if (window.document.documentMode) {
    return (
      <Alert>
        <p>Der <strong>Internet Explorer</strong> wird für die Durchführung von Einsatzmeldungen vom BpK nicht unterstützt. Bitte nutzen Sie einen anderen Browser (z.b. Firefox, Chrome oder Edge) um Meldungen durchführen zu können. Wir bedanken uns für Ihr Verständnis.</p>
      </Alert>
    );
  }

  return (
      <Row>
        <Col sm={4}>
          <Panel>
            <Panel.Heading><b>Geschäftsservices / Sonstige Projekte</b></Panel.Heading>
            <Panel.Body>Einsatzmeldungen zu <b>Geschäftsservice</b>-Releases</Panel.Body>
            <br />
            <Panel.Body><Button bsStyle="primary"><span className="glyphicon glyphicon-arrow-right" /> Zu den Einsatzmeldungen</Button></Panel.Body>
          </Panel>
        </Col>
        <Col sm={4}>
          <Panel>
            <Panel.Heading><b>KONSENS Verfahren</b></Panel.Heading>
            <Panel.Body>Einsatzmeldungen zu <b>KONSENS</b>-Releases</Panel.Body>
            <br />
            <Panel.Body>
              <Link to="/zrml/einsatzmeldungen/eingesetzt">
                <Button bsStyle="primary"><span className="glyphicon glyphicon-arrow-right" /> Zu den Einsatzmeldungen</Button>
              </Link>
            </Panel.Body>
          </Panel>
        </Col>
        <Col sm={4}>
          <Panel>
            <Panel.Heading><b>Bestehende / Fakultative Verfahren</b></Panel.Heading>
            <Panel.Body>Einsatzmeldungen zu <b>Best/Fakt</b>-Releases</Panel.Body>
            <br />
            <Panel.Body><Button bsStyle="primary"><span className="glyphicon glyphicon-arrow-right" /> Zu den Einsatzmeldungen</Button></Panel.Body>
          </Panel>
        </Col>
      </Row>
  );
}

export default ReleaseDeploymentHome;