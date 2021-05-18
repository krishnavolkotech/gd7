import React from 'react';
import {
  BrowserRouter as Router,
  Switch,
  Route,
  Link
} from "react-router-dom";
import ReleaseManagementDashboard from "./ReleaseManagement";

class App extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {

    return(
      <Router>
        <div>
          <nav>
            <ul>
              <li>
                <Link to="/r">Dashboard</Link>
              </li>
              <li>
                <Link to="/r/home">Home</Link>
              </li>
              <li>
                <Link to="/r/releases">Releases</Link>
              </li>
            </ul>
          </nav>

          <Switch>
            <Route path="/r/home">
              <Home />
            </Route>
            <Route path="/r/releases">
              <ReleaseManagementDashboard />
              {/* <Bla /> */}
            </Route>
          </Switch>
        </div>
      </Router>
      );
  }

}
function Home() {
  console.log(global);

  return (
    <div>
      <h2>Home</h2>
    </div>
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