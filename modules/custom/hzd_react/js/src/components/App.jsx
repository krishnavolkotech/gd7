import React from 'react';
import {
  BrowserRouter as Router,
  Switch,
  Route,
  Link
} from "react-router-dom";
import ReleaseManagementDashboard from "./ReleaseManagementDashboard";
import { Jumbotron, Button, Panel, Grid, Row, Col } from 'react-bootstrap';
import ReleaseDeploymentHome from './ReleaseDeploymentHome';
import DeployedReleasesZrmlView from './DeployedReleasesZrmlView';
import DeploymentManager from './DeployedReleases/DeploymentManager';
import ReleaseViewNavigator from './ReleaseTable/ReleaseViewNavigator';

class App extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {

    return(
      <Router>
        <div>
          <Switch >
            <Route path="/r/home">
              <Home />
            </Route>
            <Route exact path="/:group/releases">
              <ReleaseViewNavigator />
            </Route>
            <Route exact path="/:group/releases/bereitgestellt">
              <ReleaseViewNavigator />
            </Route>
            <Route exact path="/:group/releases/in-bearbeitung">
              <ReleaseViewNavigator />
            </Route>
            <Route exact path="/:group/releases/gesperrt">
              <ReleaseViewNavigator />
            </Route>
            <Route exact path="/:group/releases/archiviert">
              <ReleaseViewNavigator />
            </Route>
            <Route exact path="/:group/releases/eingesetzt">
              <ReleaseViewNavigator />
            </Route>
            <Route exact path="/:group/releases/eingesetzt-uebersicht">
              <ReleaseViewNavigator />
            </Route>
            <Route exact path="/:group/releases/einsatzinformationen">
              <ReleaseViewNavigator />
            </Route>
            <Route exact path="/zrml/einsatzmeldungen">
              <ReleaseDeploymentHome />
            </Route>
            <Route exact path="/zrml/einsatzmeldungen/eingesetzt">
              <DeploymentManager />
            </Route>
            <Route exact path="/zrml/einsatzmeldungen/archiviert">
              <DeploymentManager />
            </Route>
          </Switch>
        </div>
      </Router>
      );
  }

}
function Home() {
  return (
    <Grid>
      <Row>
        <Col sm={3}>
          <Panel>
            <Panel.Heading><b>Geschäftsservices / Sonstige Projekte</b></Panel.Heading>
            <Panel.Body>Übersicht der <b>Geschäftsservice</b>-Releases</Panel.Body>
            <Panel.Body><Button bsStyle="primary"><span class="glyphicon glyphicon-arrow-right" /> Zu den Releases</Button></Panel.Body>
          </Panel>
        </Col>
        <Col sm={3}>
          <Panel>
            <Panel.Heading><b>KONSENS Verfahren</b></Panel.Heading>
            <Panel.Body>Übersicht der <b>KONSENS</b>-Releases</Panel.Body>
            <br />
            <Panel.Body>
              <Link to="/r/releases">
                <Button bsStyle="primary"><span class="glyphicon glyphicon-arrow-right" /> Zu den Releases</Button>
              </Link>
            </Panel.Body>
          </Panel>
        </Col>
        <Col sm={3}>
          <Panel>
            <Panel.Heading><b>Bestehende / Fakultative Verfahren</b></Panel.Heading>
            <Panel.Body>Übersicht der <b>Best/Fakt</b>-Releases</Panel.Body>
            <br />
            <Panel.Body><Button bsStyle="primary"><span class="glyphicon glyphicon-arrow-right" /> Zu den Releases</Button></Panel.Body>
          </Panel>
        </Col>
      </Row>
    </Grid>
  );
}

function Bla() {
  return (
    <div>
      <h2>Bla</h2>
    </div>
  );
}

export default App;