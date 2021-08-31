import React from 'react'
import { FormGroup, FormControl, Grid, Row, Col, Button, Tooltip, OverlayTrigger } from 'react-bootstrap'

export default function DeployedReleasesFilter(props) {
  
  //States Filter
  const statesObject = global.drupalSettings.states;
  const statesArray = Object.entries(statesObject);
  const optionsStates = statesArray.map(state => <option value={state[0]}>{state[1]}</option>)

  //Umgebungen Filter
  const environments = global.drupalSettings.environments;
  const environmentsArray = Object.entries(environments);
  const optionsEnvironments = environmentsArray.map(environment => <option value={environment[0]}>{environment[1]}</option>)

  //Verfahren Filter
  const services = global.drupalSettings.services;
  let servicesArray = Object.entries(services);
  servicesArray.sort(function(a,b) {
    const serviceA = a[1][0].toUpperCase();
    const serviceB = b[1][0].toUpperCase();
    if (serviceA < serviceB) {
      return -1;
    }
    if (serviceA > serviceB) {
      return 1;
    }
    return 0;
  });
  const optionsServices = servicesArray.map(service => <option value={service[0]}>{service[1][0]}</option>)



  // Release Filter
  const defaultRelease = [<option value="0">&lt;Release&gt;</option>];
  let optionsReleases = [];
  let disabled = true;
  if (props.filterState.service != "0" && props.releases.length > 0) {
    disabled = false;
    optionsReleases = releases.map(release => {
      return <option value={release["nid"]}>{release["title"]}</option>;
    });
  }
  optionsReleases = [...defaultRelease, ...optionsReleases];

  const handleFilterSelect = (e) => {
    let val = {};
    val[e.target.name] = e.target.value;
    props.setFilterState(prev => ({ ...prev, ...val }))
  }

  const ttReset = (
    <Tooltip id="ttReset">
      Filter zurücksetzen.
    </Tooltip>);

  const ttRefresh = (
    <Tooltip id="ttRefresh">
      Einsatzmeldungen neu laden.
    </Tooltip>);

  return (
    <form>
      <Grid>
        <Row>
          <Col sm={3}>
            <FormGroup bsClass="select-wrapper hzd-form-element" controlId="state-filter">
              <FormControl
                name="state"
                componentClass="select"
                onChange={handleFilterSelect}
                value={props.filterState.state}
              >
                {optionsStates}
              </FormControl>
            </FormGroup>
          </Col>
          <Col sm={3}>
            <FormGroup bsClass="select-wrapper hzd-form-element" controlId="environment-filter" >
              <FormControl
                name="environment"
                componentClass="select"
                onChange={handleFilterSelect}
                value={props.filterState.environment}
              >
                {optionsEnvironments}
              </FormControl>
            </FormGroup>
          </Col>
          <Col sm={3}>
            <FormGroup bsClass="select-wrapper hzd-form-element" controlId="service-filter" >
              <FormControl
                name="service"
                componentClass="select"
                onChange={handleFilterSelect}
                value={props.filterState.service}
              >
                {optionsServices}
              </FormControl>
            </FormGroup>
          </Col>
        </Row>
        <Row>
          <Col sm={3}>
            <FormGroup bsClass="select-wrapper hzd-form-element" controlId="release-filter" >
              <FormControl
                name="release"
                disabled={disabled}
                componentClass="select"
                onChange={handleFilterSelect}
                value={props.filterState.release}
              >
                {optionsReleases}
              </FormControl>
            </FormGroup>
          </Col>
          <Col sm={3}>
            <div>
              <OverlayTrigger placement="top" overlay={ttReset}>
                <Button onClick={props.handleReset} bsStyle="danger"><span className="glyphicon glyphicon-repeat" /></Button>
              </OverlayTrigger>
              &nbsp;
              <OverlayTrigger placement="top" overlay={ttRefresh}>
                <Button onClick={() => props.setCount(count + 1)} bsStyle="primary"><span className="glyphicon glyphicon-refresh" /></Button>
              </OverlayTrigger>
            </div>
          </Col>
        </Row>
      </Grid>
    </form>
  )
}
