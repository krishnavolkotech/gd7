import React from 'react';
import {
  BrowserRouter as Router,
  Switch,
  Route,
} from "react-router-dom";
import { Alert } from 'react-bootstrap';
import DeploymentManager from './DeployedReleases/DeploymentManager';
import ReleaseViewNavigator from './ReleaseTable/ReleaseViewNavigator';

class App extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    // Catch Internet Explorer users (No IE support).
    if (window.document.documentMode) {
      return (
        <Alert>
          <p><span className="glyphicon glyphicon-exclamation-sign" /> Der <strong>Internet Explorer</strong> wird für die Ansicht von Releases und Einsatzmeldungen vom BpK nicht unterstützt. Bitte nutzen Sie einen anderen Browser (z.b. Firefox, Chrome oder Edge) um Releases und Einsatzmeldungen ansehen zu können. Wir bedanken uns für Ihr Verständnis.</p>
        </Alert>
      );
    }

    return(
      <Router>
        <div>
          <Switch >
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
            <Route exact path="/zrml/eingesetzt">
              <DeploymentManager />
            </Route>
            <Route exact path="/zrml/archiviert">
              <DeploymentManager />
            </Route>
          </Switch>
        </div>
      </Router>
    );
  }
}

export default App;