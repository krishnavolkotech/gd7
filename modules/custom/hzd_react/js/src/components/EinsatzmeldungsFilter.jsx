import React from 'react'
import { FormGroup, ControlLabel, FormControl } from 'react-bootstrap'

export default function EinsatzmeldungsFilter({ stateFilter, setStateFilter}) {
  const statesObject = global.drupalSettings.states;

  let statesArray = Object.entries(statesObject);
  // console.log(statesArray);
  let options = statesArray.map(state => <option value={state[0]}>{state[1]}</option>)
  // let options = statesArray.map(function(state) {
  //   return (
  //     <option value={state[0]}>{state[1]}</option>
  //   );
  // });
  return (
    <form>
      <FormGroup controlId="formControlsSelect">
        <FormControl 
          componentClass="select"
          onChange={(e) => setStateFilter(e.target.value)}
          value={stateFilter}
        >
          {options}
        </FormControl>
      </FormGroup>
    </form>
  )
}
