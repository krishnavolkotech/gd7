import React, { useState } from "react";
import { Form, FormGroup, FormControl, ControlLabel, Checkbox, Button } from 'react-bootstrap';
import { fetchWithCSRFToken } from "../utils/fetch";

export default function EinsatzmeldungsFormular( {data, setData, count, setCount}) {

  const[state, setState] = useState({
    umgebung: "",
    verfahren: "",
    release: "",
    vorgaengerrelease: "",
    datum:"",
    installationsdauer: "",
    archiviert: false,
    automatisiert: false,
    auffaelligkeiten: false,
    beschreibung: "",
  });


  function handleChange(event) {
    let result = event.target.type === "checkbox" ? event.target.checked : event.target.value;
    setState({...state, [event.target.name] : result});
    //setState({...state, [event.target.name] : event.target.value});
  }
    
  //console.log(state);

  //lange Service id suchen:
  const serviceslangObject = global.drupalSettings.services;
  
  let serviceslangArray = Object.entries(serviceslangObject);
  var idVerfahren;
  for (var i = 0, len = serviceslangArray.length; i < len; i++) {
    if (serviceslangArray[i][0] === state.verfahren ) {
      idVerfahren = serviceslangArray[i][1][1];
      break;
    }
  }
  //console.log(idVerfahren);

  //lange vorgaengerid suchen:
  const prevreleaseslangObject = global.drupalSettings.prevreleaseslong;
  
  let prevreleaseslangArray = Object.entries(prevreleaseslangObject);
  var idPrevRelease;
  for (var i = 0, len = prevreleaseslangArray.length; i < len; i++) {
    if (prevreleaseslangArray[i][0] === state.vorgaengerrelease) {
      idPrevRelease = prevreleaseslangArray[i][1][0][0];
      break;
    }
  }
  console.log(idPrevRelease);

  //lange releaseid suchen:
  const releaseslangObject = global.drupalSettings.releaseslong;
  let releaseslangArray = Object.entries(releaseslangObject);
  var idRelease;
  for (var i = 0, len = releaseslangArray.length; i < len; i++) {
    if (releaseslangArray[i][0] === state.release) {
      idRelease = releaseslangArray[i][1][0][1];
      break;
    }
  }
  //console.log(idRelease)

  //     // in case a previous release has been selected in the release_deployment_form, var data should be completed with relationsship field_prev_release

  if (state.vorgaengerrelease != "") {
    var postdata = {
      "data": {
        "type": "node--release_deployment",
        "attributes": {
          "title": "title",
          "field_is_archived": state.archiviert,
          "field_has_abnormalities": state.auffaelligkeiten,
          "field_deployed_automatically": state.automatisiert,
          "field_description_abnormality": state.beschreibung,
          "field_date_deployed": state.datum,
          "field_installation_time": state.installationsdauer,
          "field_user_state": global.drupalSettings.userstate,
          "field_environment": state.umgebung,
          "field_einsatz_status": true,
        },
        "relationships": {
          "field_deployed_release": {
            "data": {
              "type": "node--release",
              "id": idRelease
            },
          },
          "field_service": {
            "data": {  
              "type": "node--services",
              "id": idVerfahren,
            },
          },
          "field_prev_release": {
            "data": {  
              "type": "node--services",
              "id": idPrevRelease,
            },
          },
        }
      }
    }
  }
  else {
    var postdata = {
      "data": {
        "type": "node--release_deployment",
        "attributes": {
          "title": "title",
          "field_is_archived": state.archiviert,
          "field_has_abnormalities": state.auffaelligkeiten,
          "field_deployed_automatically": state.automatisiert,
          "field_description_abnormality": state.beschreibung,
          "field_date_deployed": state.datum,
          "field_installation_time": state.installationsdauer,
          "field_user_state": global.drupalSettings.userstate,
          "field_environment": state.umgebung,
          "field_einsatz_status": true,
        },
        "relationships": {
          "field_deployed_release": {
          "data": {
            "type": "node--release",
            "id": idRelease
            },
          },
          "field_service": {
            "data": {  
              "type": "node--services",
              "id": idVerfahren,
            },
          },
        }
      }
    }
  }

    
  // if (state.vorgaengerrelease != "") {
  //     console.log('text:');
  //     Object.defineProperty (data.data.relationships, "field_prev_release", {
  //         "data": {  
  //             "type": "node--release",
  //             "id": idPrevRelease,
  //           },
  //     }
  //     );
  // }

  function handleSave() {
    const csrfUrl = `/session/token?_format=json`;
    const fetchUrl = "/jsonapi/node/release_deployment";
    const fetchOptions = {
      method: 'POST',
      headers: new Headers ({
        'Accept': 'application/vnd.api+json',
        'Content-Type': 'application/vnd.api+json',
        'Cache': 'no-cache',
      }),
      body: JSON.stringify(postdata),
    }
  
      
      fetchWithCSRFToken(csrfUrl, fetchUrl, fetchOptions)
        .then(antwort => antwort.json())
        .then(antwort => {
            console.log (antwort);
            setCount(count + 1);
          })
        .catch(error => {
          console.log ('fehler:', error);
        });

    setState({
      umgebung: "",
      verfahren: "",
      release: "",
      vorgaengerrelease: "",
      datum:"",
      installationsdauer: "",
      archiviert: false,
      automatisiert: false,
      auffaeligkeiten: false,
      beschreibung: "",
    });

     
    // data.push(postdata);
    // setData(data);

    // console.log(data);
    
    console.log(count);
  }
  

  //Umgebungen Drop Down
  const environments = global.drupalSettings.environments;
  let environmentsArray = Object.entries(environments);
  let optionsEnvironments = environmentsArray.map(environment => <option value={environment[0]}>{environment[1]}</option>)

  //Verfahren Drop Down
  const services = global.drupalSettings.services;
  
  let servicesArray = Object.entries(services);
  servicesArray.sort(function(a,b) {
    var serviceA = a[1][0].toUpperCase();
    var serviceB = b[1][0].toUpperCase();
    if (serviceA < serviceB) {
      return -1;
    }
    if (serviceA > serviceB) {
      return 1;
    }
      return 0;
  });

  let optionsServices = servicesArray.map(service => <option value={service[0]}>{service[1][0]}</option>)

  //Release Drop Down
  let defaultRelease = [<option value="0">&lt;Release&gt;</option>];
  let optionsReleases = [];
  //let disabled = true;
  if (state.verfahren != "") {
    //disabled = false;
    const releases = global.drupalSettings.releases;
    console.log(releases);
    if (state.verfahren in releases) {
      optionsReleases = releases[state.verfahren].map(releaseObject => {
      //service State anstatt service Filter?
      let release = Object.entries(releaseObject);
      return <option value={release[0][0]}>{release[0][1]}</option>;
      });
    }
  }
  optionsReleases = [...defaultRelease, ...optionsReleases];


   //Previous Release Drop Down
  let defaultPrevRelease = [<option value="0">&lt;Vorgängerrelease&gt;</option>];
  let optionsPrevReleases = [];
  let disabled = true;
  if (state.verfahren != "") {
    disabled = false;
    const prevreleases = global.drupalSettings.prevreleases;
    if (state.verfahren in prevreleases) {
      optionsPrevReleases = prevreleases[state.verfahren].map(releaseObject => {
        return <option value={releaseObject[0]}>{releaseObject[1]}</option>;
      });
    }
  }
  optionsPrevReleases = [...defaultPrevRelease, ...optionsPrevReleases];

  return (
    <Form>
      <h1>Einsatzmeldung</h1>
      <FormGroup controlId="1">
        <ControlLabel bsClass="control-label js-form-required form-required">Umgebung</ControlLabel>
        <FormControl
          componentClass="select"
          name="umgebung"
          value={state.umgebung}
          onChange={handleChange}
        >
          {optionsEnvironments}
        </FormControl>
      </FormGroup>

      <FormGroup controlId="2">
        <ControlLabel bsClass="control-label js-form-required form-required">Verfahren</ControlLabel>
        <FormControl
          componentClass="select"
          name= "verfahren"
          value={state.verfahren}
          onChange={handleChange}
        >
          {optionsServices}
        </FormControl>

      </FormGroup>
      <FormGroup controlId="3">
        <ControlLabel bsClass="control-label js-form-required form-required">Release</ControlLabel>
        <FormControl
          componentClass="select"
          name="release"
          value={state.release}
          onChange={handleChange}
        >
          {optionsReleases}
        </FormControl>
      </FormGroup>

      <FormGroup controlId="4">
        <ControlLabel>Vorgängerrelease</ControlLabel>
        <FormControl
          componentClass="select"
          name="vorgaengerrelease"
          value={state.vorgaengerrelease} 
          onChange={handleChange}
          disabled={disabled}
        >
          {optionsPrevReleases}
        </FormControl>
      </FormGroup>

      <FormGroup controlId="5">
        <ControlLabel bsClass="control-label js-form-required form-required">Datum</ControlLabel>
        <FormControl
          type ="date"
          name="datum"
          value={state.datum} 
          onChange={handleChange}
        >
        </FormControl>
      </FormGroup>

      <FormGroup controlId="6">
        <ControlLabel bsClass="control-label js-form-required form-required">Installationsdauer</ControlLabel>
        <FormControl
          componentClass="input"
          type= "number"
          step="1"
          min="1"
          name="installationsdauer"
          value={state.installationsdauer} 
          onChange={handleChange}
          placeholder= "in Minuten"
        >
        </FormControl>
      </FormGroup>

      <FormGroup controlId="7">
        <Checkbox name="archiviert" type="checkbox" checked={state.archiviert} onChange={handleChange} >
          Archiviert
        </Checkbox>
      </FormGroup>

      <FormGroup controlId="8">
        <Checkbox name="automatisiert" type="checkbox" checked={state.automatisiert} onChange={handleChange} >
          Automatisiertes Deployment
        </Checkbox>
      </FormGroup>

      <FormGroup controlId="9">
        <Checkbox  name="auffaelligkeiten" type ="checkbox" checked={state.auffaelligkeiten} onChange={handleChange} >
          Auffälligkeiten
        </Checkbox>
      </FormGroup>

      <FormGroup controlId="10">
        <ControlLabel>Beschreibung der Auffälligkeiten</ControlLabel>
        <FormControl
          componentClass="textarea"
          name="beschreibung"
          value={state.beschreibung}
          onChange={handleChange}
        >
        </FormControl>
      </FormGroup>
      <Button bsStyle="success" onClick={handleSave} >Speichern</Button>
    </Form> 
  );
}
