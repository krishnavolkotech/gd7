import React, { useState, useEffect } from 'react'
import { FormGroup, FormControl, ControlLabel } from 'react-bootstrap';

export default function SelectPreviousRelease({ formState, handleChange, prevReleases, isLoading, setIsLoading, disabled }) {
  const [releaseOptions, setReleaseOptions] = useState([<option value="0">&lt;Release&gt;</option>]);

  //Previous Release Drop Down
  useEffect(() => {
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

  // const handleChange = (e) => {
  //   setPreviousRelease(e.target.value);
  //   setArchivePrevRelease(false);
  // }

  return (
    <FormGroup controlId="4">
      {/* <ControlLabel>Vorgängerrelease{loading}</ControlLabel> */}
      <div className="select-wrapper">
        <FormControl
          componentClass="select"
          name="previousRelease"
          value={formState.previousRelease}
          onChange={handleChange}
          disabled={true}
        >
          {releaseOptions}
        </FormControl>
      </div>
    </FormGroup>
  )
}
