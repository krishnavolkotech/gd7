import React, { useState, useEffect } from "react";
import { Form, FormGroup, FormControl, ControlLabel, Checkbox, Button } from 'react-bootstrap';
import { fetchWithCSRFToken } from "../utils/fetch";
import SelectRelease from "./SelectRelease";
import SelectPreviousRelease from "./SelectPreviousRelease";

export default function EinsatzmeldungsFormular({data, setData, count, setCount}) {

  const [environment, setEnvironment] = useState(1);
  const [service, setService] = useState(0);
  const [release, setRelease] = useState(false);
  const [previousRelease, setPreviousRelease] = useState("0");
  const [date, setDate] = useState(false);
  const [installationTime, setInstallationTime] = useState(false);
  const [isArchived, setIsArchived] = useState(false);
  const [isAutomated, setIsAutomated] = useState(false);
  const [abnormalities, setAbnormalities] = useState(false);
  const [description, setDescription] = useState("");
  const [userState, setUserState] = useState(global.drupalSettings.userstate);

  console.log(
    environment,
    service,
    release,
    previousRelease,
    date,
    installationTime,
    isArchived,
    isAutomated,
    abnormalities,
    description
  );
  // Release und Vorgängerrelease zurücksetzen, sobald ein anderes Verfahren 
  // gewählt wird.
  useEffect(() => {
    setRelease("0");
    setPreviousRelease("0");
  }, [service])

  let firstDeployment = false;
  if (previousRelease == "0") {
    firstDeployment = true;
  }

  // UUID des Verfahrens.
  const serviceslangObject = global.drupalSettings.services;
  
  let serviceslangArray = Object.entries(serviceslangObject);
  var idVerfahren;
  for (var i = 0, len = serviceslangArray.length; i < len; i++) {
    if (serviceslangArray[i][0] === service ) {
      idVerfahren = serviceslangArray[i][1][1];
      break;
    }
  }

  // UUID des Vorgängerrelease.
  const prevreleaseslangObject = global.drupalSettings.prevreleaseslong;
  
  let prevreleaseslangArray = Object.entries(prevreleaseslangObject);
  var idPrevRelease;
  for (var i = 0, len = prevreleaseslangArray.length; i < len; i++) {
    if (prevreleaseslangArray[i][0] === previousRelease) {
      idPrevRelease = prevreleaseslangArray[i][1][0][0];
      break;
    }
  }

  // UUID des gemeldeten Release.
  const releaseslangObject = global.drupalSettings.releaseslong;
  let releaseslangArray = Object.entries(releaseslangObject);
  var idRelease;
  for (var i = 0, len = releaseslangArray.length; i < len; i++) {
    if (releaseslangArray[i][0] === release) {
      idRelease = releaseslangArray[i][1][0][1];
      break;
    }
  }
  
  var postdata = {
    "data": {
      "type": "node--deployed_releases",
      "attributes": {
        "title": "title",
        "field_deployment_status": isArchived ? '2' : '1',
        "field_first_deployment": firstDeployment,
        "field_abnormalities_bool": abnormalities,
        "field_automated_deployment_bool": isAutomated,
        "field_abnormality_description": description,
        "field_date_deployed": date,
        "field_installation_time": installationTime,
        "field_user_state": userState,
        "field_environment": environment,
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
  
  // in case a previous release has been selected in the deployed_releases_form,
  // var data should be completed with relationsship field_prev_release
  if (previousRelease != "0") {
    let field_prev_release = {
      "data": {
        "type": "node--release",
        "id": idPrevRelease,
      },
    }
    postdata["data"]["relationships"] = { ...postdata["data"]["relationships"], field_prev_release };
  }
  function handleSave() {
    console.log(postdata);
    const csrfUrl = `/session/token?_format=json`;
    const fetchUrl = "/jsonapi/node/deployed_releases";
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

    // Nach Absendung des Formulars alles zurücksetzen.
    setEnvironment(1);
    setService(0);
    setRelease(false);
    setPreviousRelease(false);
    setDate(false);
    setInstallationTime(false);
    setIsArchived(false);
    setIsAutomated(false);
    setAbnormalities(false);
    setDescription("");

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

  return (
    <Form>
      <h1>Einsatzmeldung</h1>
      <FormGroup controlId="1">
        <ControlLabel bsClass="control-label js-form-required form-required">Umgebung</ControlLabel>
        <div className="select-wrapper">
          <FormControl
            componentClass="select"
            name="umgebung"
            value={environment}
            onChange={(e) => setEnvironment(e.target.value)}
          >
            {optionsEnvironments}
          </FormControl>
        </div>
      </FormGroup>

      <FormGroup controlId="2">
        <ControlLabel bsClass="control-label js-form-required form-required">Verfahren</ControlLabel>
        <div className="select-wrapper">
          <FormControl
            componentClass="select"
            name= "verfahren"
            value={service}
            onChange={(e) => setService(e.target.value) }
          >
            {optionsServices}
          </FormControl>
        </div>
      </FormGroup>

      <SelectRelease
        service={ service }
        release={ release }
        setRelease={ setRelease }
        environment={ environment }
      />

      <SelectPreviousRelease
        service={service}
        previousRelease={previousRelease}
        setPreviousRelease={setPreviousRelease}
      />

      <FormGroup controlId="5">
        <ControlLabel bsClass="control-label js-form-required form-required">Datum</ControlLabel>
        <FormControl
          type ="date"
          name="datum"
          value={date}
          onChange={(e) => setDate(e.target.value)}
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
          value={installationTime} 
          onChange={(e) => setInstallationTime(e.target.value)}
          placeholder= "in Minuten"
        >
        </FormControl>
      </FormGroup>

      <FormGroup controlId="7">
        <Checkbox
          name="archiviert"
          type="checkbox"
          checked={isArchived}
          onChange={(e) => setIsArchived(e.target.checked)}
        >
          Archiviert
        </Checkbox>
      </FormGroup>

      <FormGroup controlId="8">
        <Checkbox
          name="automatisiert"
          type="checkbox"
          checked={isAutomated}
          onChange={(e) => setIsAutomated(e.target.checked)}
        >
          Automatisiertes Deployment
        </Checkbox>
      </FormGroup>

      <FormGroup controlId="9">
        <Checkbox
          name="auffaelligkeiten"
          type ="checkbox"
          checked={abnormalities}
          onChange={(e) => setAbnormalities(e.target.checked)}
        >
          Auffälligkeiten
        </Checkbox>
      </FormGroup>

      <FormGroup controlId="10">
        <ControlLabel>Beschreibung der Auffälligkeiten</ControlLabel>
        <FormControl
          componentClass="textarea"
          name="beschreibung"
          value={description}
          onChange={(e) => setDescription(e.target.value)}
        >
        </FormControl>
      </FormGroup>
      <Button bsStyle="success" onClick={handleSave} >Speichern</Button>
    </Form>
  );
}
