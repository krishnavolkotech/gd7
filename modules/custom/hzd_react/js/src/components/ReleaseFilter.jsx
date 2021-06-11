import React, { useState, useEffect } from 'react';
import { FormGroup, ControlLabel, FormControl, Button } from 'react-bootstrap';

function ReleaseFilter({
  serviceFilter,
  handleServiceFilter,
  handleReset,
  serviceType,
  releaseFilter,
  handleReleaseFilter,
}) {
  const services = global.drupalSettings.services;
  
  
  const servicesArray = [];
  for (const [key, value] of Object.entries(services[serviceType])) {
    servicesArray.push(value);
  }
  
  servicesArray.sort((a, b) => a.localeCompare(b, undefined, { sensitivity: 'base' }));
  
  const options = [];
  for (const val of servicesArray) {
    options.push(<option value={ val }>{ val }</option>)
  }

  const releases = ["GeCo", "Biene"];
  const releaseOptions = [];
  for (const val of releases) {
    releaseOptions.push(<option value={val}>{val}</option>)
  }


  return (
    <form>
      <FormGroup controlId="service">
        <FormControl
          componentClass="select"
          placeholder="select"
          onChange={handleServiceFilter}
          value={serviceFilter}
        >
          <option value="default">&lt;Verfahren&gt;</option>
          {options}

        </FormControl>
      </FormGroup>
      <FormGroup controlId="release">
        <FormControl
          componentClass="select"
          placeholder="select"
          onChange={handleReleaseFilter}
          value={releaseFilter}
        >
          <option value="default">&lt;Release&gt;</option>
          {releaseOptions}

        </FormControl>
      </FormGroup>
      <Button bsStyle="primary" type="submit"><span class="glyphicon glyphicon-ok" /> Anwenden</Button>
      <span>&nbsp;</span>
      <Button bsStyle="primary" onClick={handleReset}><span class="glyphicon glyphicon-remove" /> Filter Zurücksetzen</Button>
    </form>
  );
}

export default ReleaseFilter;