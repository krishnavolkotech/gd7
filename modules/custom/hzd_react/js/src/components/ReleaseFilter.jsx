import React, { useState, useEffect } from 'react';
import { FormGroup, ControlLabel, FormControl, Button } from 'react-bootstrap';

function ReleaseFilter({ serviceFilter, handleServiceFilter, handleReset }) {
  const services = ["GeCo", "RMS-FB", "KDIALOG"];

  const options = services.map((service) => {
    return (
      <option value={ service }>{ service }</option>
    );
  });

  return (
    <form>
      <FormGroup controlId="service">
        <FormControl 
          componentClass="select"
          placeholder="select"
          onChange={ handleServiceFilter }
          value={serviceFilter}
        >
          <option value="default">&lt;Verfahren&gt;</option>
          { options }

        </FormControl>
      </FormGroup>
      <Button type="submit">Anwenden</Button>
      <Button onClick={ handleReset }>Filter Zurücksetzen</Button>
    </form>
  );
}

export default ReleaseFilter;