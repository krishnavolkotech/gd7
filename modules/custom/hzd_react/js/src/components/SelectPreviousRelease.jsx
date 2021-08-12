import React, { useState, useEffect } from 'react'
import { FormGroup, FormControl, ControlLabel } from 'react-bootstrap';

export default function SelectPreviousRelease({ service, previousRelease, setPreviousRelease }) {
  const [options, setOptions] = useState(false);

  useEffect(() => {
    setOptions(false);
    // Service und Umgebung müssen ausgewählt sein.
    if ( service == 0) {
      return;
    }
    const fetchUrl = '/jsonapi/node/deployed_releases';
    const defaultFilter = '?include=field_deployed_release&page[limit]=50&sort[sort-date][path]=field_date_deployed&sort[sort-date][direction]=DESC';
    // Always apply default filter.
    let url = fetchUrl + defaultFilter;

    // Fehlmeldungen sollen rausgefiltert werden.
    let archivedFilter = '&filter[deployed-releases][condition][path]=field_deployment_status'
      + '&filter[deployed-releases][condition][operator]=%3C%3E'
      + '&filter[deployed-releases][condition][value]=3';
    url += archivedFilter;

    const userState = global.drupalSettings.userstate;
    url += "&filter[field_user_state]=" + userState;
    url += "&filter[field_service.drupal_internal__nid]=" + service;

    const headers = new Headers({
      Accept: 'application/vnd.api+json',
    });

    fetch(url, { headers })
      .then(response => response.json())
      .then(results => {
        let deployedReleaseNids = results.data.map((deployment) => {
          const releaseId = deployment.relationships.field_deployed_release.data.id;
          const relatedReleaseObject = results.included.find(({ id }) => id === releaseId);
          const relatedRelaseNid = relatedReleaseObject.attributes.drupal_internal__nid;
          return relatedRelaseNid;
        });
        let allReleases = [...global.drupalSettings.releases[service]];
        let found = [];
        deployedReleaseNids.map(nid => {
          allReleases.filter((release, index) => {
            if (nid in release) {
              found.push(index);
            }
          });
        });
        found.sort((a, b) => b - a);
        found = [...new Set(found)];
        let deployedReleases = [];
        found.forEach(index => {
          deployedReleases.push(allReleases.splice(index, 1)[0]);
        });
        deployedReleases.sort();
        // console.log(found);
        // console.log(allReleases);
        // console.log(deployedReleases);
        setOptions(deployedReleases);
      })
      .catch(error => console.log("error", error));
  }, [service])

  //Previous Release Drop Down
  let defaultPrevRelease = [<option value="0">Ersteinsatz</option>];
  let optionsPrevReleases = [];
  let disabled = true;
  if (options.length > 0) {
    disabled = false;
    optionsPrevReleases = options.map(optionObject => {
      let release = Object.entries(optionObject);
      return <option value={release[0][0]}>{release[0][1]}</option>;
    });
  }
  optionsPrevReleases = [...defaultPrevRelease, ...optionsPrevReleases];

  return (
    <FormGroup controlId="4">
      <ControlLabel>Vorgängerrelease</ControlLabel>
      <FormControl
        componentClass="select"
        name="vorgaengerrelease"
        value={previousRelease}
        onChange={(e) => setPreviousRelease(e.target.value)}
        disabled={disabled}
      >
        {optionsPrevReleases}
      </FormControl>
    </FormGroup>
  )
}
