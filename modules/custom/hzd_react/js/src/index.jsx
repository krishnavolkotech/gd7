import React from 'react';
import ReactDOM, { render } from 'react-dom';
import { hot } from 'react-hot-loader/root';
import {
  Button, Table, OverlayTrigger, Tooltip, Pagination, FormGroup,
  ControlLabel, FormControl, Form
} from 'react-bootstrap';
import { fetchWithCSRFToken } from "./utils/fetch";
import App from "./components/App";


const Main = hot(() => (
  <App />
));

ReactDOM.render(
  <Main />,
  document.getElementById('react-app')
);

