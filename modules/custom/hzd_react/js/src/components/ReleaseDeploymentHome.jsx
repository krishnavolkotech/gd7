import React from 'react';
import { Row, Col, Panel, Button, Alert } from 'react-bootstrap';
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
    <div>
      <p>Hier können die Zentralen Release Manager der Länder (ZRML) oder von diesen benannte Stellvertreter in ihrem Land eingesetzte Releases melden. Dadurch wird das bisherige <a href="http://betrieb.konsens.ktz.testa-de.net/einsatz.htm">Meldeverfahren über den KTZ-Server</a> abgelöst (vgl. VA RM 5.1.12). Es müssen alle in den Ländern produktiv eingesetzten Releases gemeldet werden (auch ZPS). Die Meldungen sind von den ZRML jeweils unmittelbar nach Produktivsetzung zu tätigen.</p>
      <p>Um in Ihrem Land eingesetzte Releases melden zu können, müssen Sie Mitglied der Gruppe ZRML sein. Initial sind dies alle ZRML, weitere Stllv. werden auf Antrag beim ZRMK aufgenommen. Wählen Sie dann Verfahren und Release sowie Einsatzdatum aus und speichern Ihre Auswahl. Durch Speichern wird der Release-Einsatz gemeldet und in der <a href="/release-management/releases/deployed">Übersicht der eingesetzten Releases</a> angezeigt. Zur Meldung von Updates (z.B. von Kap-ESt-KEStAnw_3.0.0.0-1 auf Kap-ESt-KEStAnw_3.0.0.2-1) muss das neue Release (im Bsp. Kap-ESt-KEStAnw_3.0.0.2-1) gemeldet und das alte Release (im Bsp. Kap-ESt-KEStAnw_3.0.0.0-1) archiviert werden. Zum Archivieren wählen Sie in der unten stehenden Tabelle die Aktion „Archivieren“. Sollten Sie den Einsatz älterer Releases melden, die nicht in der Auswahl (bzw. in der DSL KONSENS, woher die Daten der Auswahlliste stammen) vorhanden sind, wenden Sie sich bitte an den <a href="mailto:zrmk@hzd.hessen.de">Zentrale Release Manager KONSENS</a> (ZRMK). Das entsprechende Release wird dann im Auswahlmenü verfügbar gemacht. Versehentliche Fehlmeldungen können durch den ZRMK korrigiert werden.</p>
      <p>Über die Länderzugehörigkeit des Nutzerprofils ist sichergestellt, dass ZRML‘s nur Meldungen für Ihr Land machen können.</p>
      <p>Für Rückfragen steht Ihnen der <a href="mailto:zrmk@hzd.hessen.de">Zentrale Release Manager KONSENS</a> (ZRMK) zur Verfügung.</p>
      <Row>
        {/* <Col sm={4}>
          <Panel>
            <Panel.Heading><b>Geschäftsservices / Sonstige Projekte</b></Panel.Heading>
            <Panel.Body>Einsatzmeldungen zu <b>Geschäftsservice</b>-Releases</Panel.Body>
            <br />
            <Panel.Body><Button bsStyle="primary"><span className="glyphicon glyphicon-arrow-right" /> Zu den Einsatzmeldungen</Button></Panel.Body>
          </Panel>
        </Col> */}
        <Col sm={6}>
          <Panel>
            <Panel.Heading><b>KONSENS Verfahren</b></Panel.Heading>
            <Panel.Body>Hier können Sie Einsatzmeldungen zu <b>KONSENS</b>-Releases verwalten.</Panel.Body>
            <br />
            <Panel.Body>
              <Link to="/zrml/einsatzmeldungen/eingesetzt">
                <Button bsStyle="primary"><span className="glyphicon glyphicon-arrow-right" /> Zu den Einsatzmeldungen</Button>
              </Link>
            </Panel.Body>
          </Panel>
        </Col>
        <Col sm={6}>
          <Panel>
            <Panel.Heading><b>Bestehende / Fakultative Verfahren</b></Panel.Heading>
            <Panel.Body>Hier können Sie Einsatzmeldungen zu <b>Best/Fakt</b>-Releases verwalten.</Panel.Body>
            <br />
            <Panel.Body>
              <Link to="/zrml/einsatzmeldungen/eingesetzt?type=460">
                <Button bsStyle="primary"><span className="glyphicon glyphicon-arrow-right" /> Zu den Einsatzmeldungen</Button>
              </Link>
            </Panel.Body>
          </Panel>
        </Col>
      </Row>
    </div>
  );
}

export default ReleaseDeploymentHome;