import React, { useState, useEffect } from 'react'
import { FormGroup, FormControl, ControlLabel } from 'react-bootstrap';

export default function SelectRelease({ service, release, setRelease, environment}) {
  const [options, setOptions] = useState(false);

  useEffect(() => {
    setOptions(false);
    // Service und Umgebung müssen ausgewählt sein.
    if (environment == 0 || service == 0) {
      return;
    }
    // Nur wenn beides gewählt ist, soll der API Call erfolgen.
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
    url += "&filter[field_environment]=" + environment;
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
        let releases = [];
        if (service in global.drupalSettings.releases) {
          releases = [...global.drupalSettings.releases[service]];
        }
        let found = [];
        deployedReleaseNids.map(nid => {
          releases.filter((release, index) => {
            if (nid in release) {
              found.push(index);
            }
          });
        });
        found.sort((a, b) => b - a);
        found.forEach(index => {
          // console.log(index);
          releases.splice(index, 1);
        });
        releases.sort((a, b) => b - a);
        // console.log(found);
        // console.log(releases);
        setOptions(releases);
      })
      .catch(error => console.log("error", error));
    }, [service, environment])
    
  //Release Drop Down
  let defaultRelease = [<option value="0">&lt;Release&gt;</option>];
  let optionsReleases = [];
  let disabled = true;
  if (options.length > 0) {
    disabled = false;
    optionsReleases = options.map(optionObjekt => {
      let release = Object.entries(optionObjekt);
      return <option value={release[0][0]}>{release[0][1]}</option>;
    });
  }
  optionsReleases = [...defaultRelease, ...optionsReleases];

  return (
    <FormGroup controlId="3">
      <ControlLabel bsClass="control-label js-form-required form-required">Release</ControlLabel>
      <div className="select-wrapper">
        <FormControl
          disabled={disabled}
          componentClass="select"
          name="release"
          value={release}
          onChange={(e) => setRelease(e.target.value)}
        >
          {optionsReleases}
        </FormControl>
      </div>
    </FormGroup>
  )
}
