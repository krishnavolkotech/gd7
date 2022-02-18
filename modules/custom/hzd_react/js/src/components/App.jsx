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
            <Route exact path="/*/releases">
              <ReleaseViewNavigator />
            </Route>
            <Route exact path="/*/releases/bereitgestellt">
              <ReleaseViewNavigator />
            </Route>
            <Route exact path="/*/releases/in-bearbeitung">
              <ReleaseViewNavigator />
            </Route>
            <Route exact path="/*/releases/gesperrt">
              <ReleaseViewNavigator />
            </Route>
            <Route exact path="/*/releases/archiviert">
              <ReleaseViewNavigator />
            </Route>
            <Route exact path="/*/releases/eingesetzt">
              <ReleaseViewNavigator />
            </Route>
            <Route exact path="/*/releases/eingesetzt-uebersicht">
              <ReleaseViewNavigator />
            </Route>
            <Route exact path="/*/releases/einsatzinformationen">
              <ReleaseViewNavigator />
            </Route>
            <Route exact path="/*/eingesetzt">
              <DeploymentManager />
            </Route>
            <Route exact path="/*/archiviert">
              <DeploymentManager />
            </Route>
          </Switch>
        </div>
      </Router>
    );
  }
}

export default App;