import React from 'react';
import { Grid, Row, Col, Panel, Button } from 'react-bootstrap';
import {Link} from 'react-router-dom';

function ReleaseDeploymentHome() {
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
              <Link to="/zrml/r/einsatzmeldungen/eingesetzt">
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