import React from 'react'
import { FormGroup, FormControl, Grid, Row, Col, Button, Tooltip, OverlayTrigger } from 'react-bootstrap'

export default function EinsatzmeldungsFilter({ stateFilter, setStateFilter, environmentFilter, setEnvironmentFilter, serviceFilter, setServiceFilter, releaseFilter, setReleaseFilter, handleReset, count, setCount, releases}) {
  
  //States Filter
  const statesObject = global.drupalSettings.states;
  let statesArray = Object.entries(statesObject);
  let optionsStates = statesArray.map(state => <option value={state[0]}>{state[1]}</option>)

  //Verfahren Filter
  const services = global.drupalSettings.services;
  let servicesArray = Object.entries(services);
  servicesArray.sort(function(a,b) {
    var serviceA = a[1][0].toUpperCase();
    var serviceB = b[1][0].toUpperCase();
    if (serviceA < serviceB) {
      return -1;
    }
    if (serviceA > serviceB) {
      return 1;
    }
    return 0;
  });
  let optionsServices = servicesArray.map(service => <option value={service[0]}>{service[1][0]}</option>)


  //Umgebungen Filter
  //Objekt mit Umgebungen suchen
  const environments = global.drupalSettings.environments;
  //Objekt in Array ändern
  let environmentsArray = Object.entries(environments);
  let optionsEnvironments = environmentsArray.map(environment => <option value={environment[0]}>{environment[1]}</option>)

  // Release Filter
  let defaultRelease = [<option value="0">&lt;Release&gt;</option>];
  let optionsReleases = [];
  let disabled = true;
  if (serviceFilter != "0" && releases.length > 0) {
    disabled = false;
    optionsReleases = releases.map(release => {
      return <option value={release["nid"]}>{release["title"]}</option>;
    });
  }
  optionsReleases = [...defaultRelease, ...optionsReleases];

  const ttReset = (
    <Tooltip id="ttReset">
      Filter <strong>zurücksetzen</strong>.
    </Tooltip>);

  const ttRefresh = (
    <Tooltip id="ttRefresh">
      Einsatzmeldungen <strong>neu laden</strong>.
    </Tooltip>);

  return (
    <form>
      <Grid>
        <Row>
          <Col sm={3}>
            <FormGroup bsClass="select-wrapper hzd-form-element" controlId="formControlsSelect">
              <FormControl
                componentClass="select"
                onChange={(e) => setStateFilter(e.target.value)}
                value={stateFilter}
              >
                {optionsStates}
              </FormControl>
            </FormGroup>
          </Col>
          <Col sm={3}>
            <FormGroup bsClass="select-wrapper hzd-form-element" controlId="formControlsSelect2" >
              <FormControl
                componentClass="select"
                onChange={(e) => setEnvironmentFilter(e.target.value)}
                value={environmentFilter}
              >
                {optionsEnvironments}
              </FormControl>
            </FormGroup>
          </Col>
          <Col sm={3}>
            <FormGroup bsClass="select-wrapper hzd-form-element" controlId="formControlsSelect3" >
              <FormControl
                componentClass="select"
                onChange={(e) => setServiceFilter(e.target.value)}
                value={serviceFilter}
              >
                {optionsServices}
              </FormControl>
            </FormGroup>
          </Col>
        </Row>
        <Row>
          <Col sm={3}>
            <FormGroup bsClass="select-wrapper hzd-form-element" controlId="formControlsSelect4" >
              <FormControl
                disabled={disabled}
                componentClass="select"
                onChange={(e) => setReleaseFilter(e.target.value)}
                value={releaseFilter}
              >
                {optionsReleases}
              </FormControl>
            </FormGroup>
          </Col>
          <Col sm={3}>
            <div>
              <OverlayTrigger placement="top" overlay={ttReset}>
                <Button onClick={() => handleReset()} bsStyle="danger"><span className="glyphicon glyphicon-repeat" /></Button>
              </OverlayTrigger>
              &nbsp;
              <OverlayTrigger placement="top" overlay={ttRefresh}>
                <Button onClick={() => setCount(count + 1)} bsStyle="primary"><span className="glyphicon glyphicon-refresh" /></Button>
              </OverlayTrigger>
            </div>
          </Col>
        </Row>
      </Grid>
    </form>
  )
}
