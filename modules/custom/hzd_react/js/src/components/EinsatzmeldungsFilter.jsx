import React from 'react'
import { FormGroup, ControlLabel, FormControl } from 'react-bootstrap'

export default function EinsatzmeldungsFilter({ stateFilter, setStateFilter, environmentFilter, setEnvironmentFilter, serviceFilter, setServiceFilter, releaseFilter, setReleaseFilter}) {
  
  //States Filter
  const statesObject = global.drupalSettings.states;
  let statesArray = Object.entries(statesObject);
  let optionsStates = statesArray.map(state => <option value={state[0]}>{state[1]}</option>)

  //Verfahren Filter
  const services = global.drupalSettings.services;
  
  let servicesArray = Object.entries(services);
  servicesArray.sort(function(a,b) {
    var serviceA = a[1].toUpperCase();
    var serviceB = b[1].toUpperCase();
    if (serviceA < serviceB) {
      return -1;
    }
    if (serviceA > serviceB) {
      return 1;
    }
    return 0;
  });

  let optionsServices = servicesArray.map(service => <option value={service[0]}>{service[1]}</option>)


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
  if (serviceFilter != "0") {
    disabled = false;
    const releases = global.drupalSettings.releases;
    optionsReleases = releases[serviceFilter].map(releaseObject => {
      let release = Object.entries(releaseObject);
      return <option value={release[0][0]}>{release[0][1]}</option>;
    });
  }
  optionsReleases = [...defaultRelease, ...optionsReleases];

  return (
    <form>
      <FormGroup controlId="formControlsSelect">
        <FormControl 
          componentClass="select"
          onChange={(e) => setStateFilter(e.target.value)}
          value={stateFilter}
        >
          {optionsStates}
        </FormControl>
      </FormGroup>
      <FormGroup controlId="formControlsSelect2" >
        <FormControl
          componentClass="select"
          onChange={(e) => setEnvironmentFilter(e.target.value)}
          value={environmentFilter}
        >
          {optionsEnvironments}
        </FormControl>
      </FormGroup>
      <FormGroup controlId="formControlsSelect3" >
        <FormControl
          componentClass="select"
          onChange={(e) => setServiceFilter(e.target.value)}
          value={serviceFilter}
        >
          {optionsServices}
        </FormControl>
      </FormGroup>
      <FormGroup controlId="formControlsSelect4" >
        <FormControl
          disabled={disabled}
          componentClass="select"
          onChange={(e) => setReleaseFilter(e.target.value)}
          value={releaseFilter}
        >
          {optionsReleases}
        </FormControl>
      </FormGroup>
    </form>
  )
}
