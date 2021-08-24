import React, { useState, useEffect } from 'react'
import { FormGroup, FormControl, ControlLabel } from 'react-bootstrap';

export default function SelectPreviousRelease({ previousRelease, setPreviousRelease, prevReleases, isLoading, setIsLoading, disabledPrevRelease }) {
  const [releaseOptions, setReleaseOptions] = useState([<option value="0">&lt;Release&gt;</option>]);

  useEffect(() => {
    //Previous Release Drop Down
    setReleaseOptions([<option value="0">&lt;Release&gt;</option>]);
    let defaultPrevRelease = [<option value="0">Ersteinsatz</option>];
    let optionsPrevReleases = [];

    if (typeof prevReleases !== 'object') {
      setIsLoading(false);
      return;
    }

    if (prevReleases.length > 0) {
      optionsPrevReleases = prevReleases.map(option => {
        for (const nid in option) {
          return <option value={option.nid}>{option.title}</option>;
        }
      });
    }
    optionsPrevReleases = [...defaultPrevRelease, ...optionsPrevReleases];
    setReleaseOptions(optionsPrevReleases);
  }, [prevReleases])

  let loading = "";
  if (isLoading) {
    loading = <span> <span className="glyphicon glyphicon-refresh glyphicon-spin" role="status" /></span>;
  }
console.log("Disabled? ", disabledPrevRelease);
  return (
    <FormGroup controlId="4">
      <ControlLabel>Vorgängerrelease{loading}</ControlLabel>
      <div className="select-wrapper">
        <FormControl
          componentClass="select"
          name="vorgaengerrelease"
          value={previousRelease}
          onChange={(e) => setPreviousRelease(e.target.value)}
          disabled={disabledPrevRelease}
        >
          {releaseOptions}
        </FormControl>
      </div>
    </FormGroup>
  )
}
