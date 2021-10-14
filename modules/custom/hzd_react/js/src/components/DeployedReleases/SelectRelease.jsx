import React, { useState, useEffect } from 'react'
import { FormGroup, FormControl, ControlLabel } from 'react-bootstrap';

export default function SelectRelease({ formState, handleChange, newReleases, isLoading, setIsLoading, disabled, setDisabled}) {
  const [releaseOptions, setReleaseOptions] = useState([<option value="0">&lt;Release&gt;</option>]);

  // Befüllt Release Releaseoptionen basierend auf newReleases (prop).
  useEffect(() => {
    //Release Drop Down
    // setReleaseOptions([<option value="0">&lt;Release&gt;</option>]);
    let defaultRelease = [<option value="0">&lt;Release&gt;</option>];
    let optionsReleases = [];
    // Dropdown deaktivieren, bevor die Optionen gefüllt sind..
    if (typeof newReleases !== 'object') {
      setIsLoading(false);
      return;
    }
    // Options befüllen.
    if (newReleases.length > 0) {
      optionsReleases = newReleases.map(option => {
        for (const nid in option) {
          return <option value={option.nid}>{option.title}</option>;
        }
      });
    }
    optionsReleases = [...defaultRelease, ...optionsReleases];
    setReleaseOptions(optionsReleases);
  }, [newReleases])
    

  let loading = "";
  if (isLoading) {
    loading = <span> <span className="glyphicon glyphicon-refresh glyphicon-spin" role="status" /></span>;
  }

  return (
    <FormGroup controlId="3">
      <ControlLabel bsClass="control-label js-form-required form-required">Release{loading}</ControlLabel>
      <div className="select-wrapper">
        <FormControl
          disabled={disabled}
          componentClass="select"
          name="releaseNid"
          value={formState.releaseNid}
          onChange={handleChange}
        >
          {releaseOptions}
        </FormControl>
      </div>
    </FormGroup>
  )
}
