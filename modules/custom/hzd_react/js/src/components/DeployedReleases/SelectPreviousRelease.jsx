import React, { useState, useEffect } from 'react'
import { FormGroup, FormControl, ControlLabel } from 'react-bootstrap';

export default function SelectPreviousRelease({ formState, setFormState, handleChange, prevReleases, isLoading, setIsLoading, disabled, index }) {
  const [releaseOptions, setReleaseOptions] = useState([<option key="select-pr-0" value="0">&lt;Release&gt;</option>]);

  //Previous Release Drop Down
  useEffect(() => {
    // console.log(prevReleases);
    setReleaseOptions([<option key="select-pr-0" value="0">&lt;Release&gt;</option>]);
    let defaultPrevRelease = formState.firstDeployment ? 
      [<option key="select-pr-1" value="0">Ersteinsatz</option>] :
      [<option key="select-pr-2" value="0">&lt;Vorgängerrelease&gt;</option>];
    let optionsPrevReleases = [];

    if (typeof prevReleases !== 'object') {
      setIsLoading(false);
      return;
    }
    // console.log(prevReleases)
    if (prevReleases.length > 0) {
      optionsPrevReleases = prevReleases.map(option => {
        for (const val of formState.previousReleases) {
          // Erstes Element.
          if (formState.previousReleases.length === 1) {
            return <option key={"select-pr-" + option.nid} value={option.nid}>{option.title}</option>;
          }
          // Alle vorherigen Optionen entfernen.
          if (option.nid === val.release) {
            return;
          }
        }
        for (const nid in option) {
          return <option key={"select-pr-" + option.nid} value={option.nid}>{option.title}</option>;
        }
      });
    }
    optionsPrevReleases = [...defaultPrevRelease, ...optionsPrevReleases];
    setReleaseOptions(optionsPrevReleases);

    // formState um uuid (release und deployment) ergänzen (Nur erstes Element!)
    // Die folgenden Elemente müssen in handleSelect befüllt werden.
    if (formState.previousReleases.length === 1) {
      let val = {};
      val.previousReleases = formState.previousReleases;
      formState.previousReleases.forEach((prev, index) => {
        let matchingRelease = prevReleases.find(p => p.nid == val.previousReleases[index].release);
        if (typeof matchingRelease === "undefined") {
          return;
        }
        val.previousReleases[index].uuidRelease = matchingRelease.uuidRelease;
        val.previousReleases[index].uuidDeployment = matchingRelease.uuidDeployment;
      });
      setFormState(prev => ({ ...prev, ...val }));
    }
  }, [prevReleases])

  let loading = "";
  if (isLoading) {
    loading = <span> <span className="glyphicon glyphicon-refresh glyphicon-spin" role="status" /></span>;
  }

  const handleSelect = (e) => {
    const tIndex = e.nativeEvent.target.selectedIndex;
    const label = e.nativeEvent.target[tIndex].text;
    const release = prevReleases.find(release => {
      return release.title == label;
    });
    let val = {};
    val.previousReleases = formState.previousReleases;
    val.previousReleases[index].uuidDeployment = release.uuidDeployment;
    val.previousReleases[index].uuidRelease = release.uuidRelease;
    val.previousReleases[index].title = label;
    val.previousReleases[index].release = release.nid;
    val.pCount = formState.pCount + 1;
    setFormState(prev => ({ ...prev, ...val }));
  }

  return (
    <FormGroup controlId="4">
      {/* <ControlLabel>Vorgängerrelease{loading}</ControlLabel> */}
      <div className="select-wrapper">
        <FormControl
          componentClass="select"
          name="previousRelease"
          value={formState.previousReleases[index].release}
          onChange={handleSelect}
          disabled={false}
        >
          {releaseOptions}
        </FormControl>
      </div>
    </FormGroup>
  )
}
