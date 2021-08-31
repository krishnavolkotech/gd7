import React, { useState, useEffect } from "react";
import { Form, FormGroup, FormControl, ControlLabel, Checkbox, Button, Modal, OverlayTrigger, Tooltip, Radio } from 'react-bootstrap';
import { fetchWithCSRFToken } from "../utils/fetch";
import SelectRelease from "./DeployedReleases/SelectRelease";
import SelectPreviousRelease from "./DeployedReleases/SelectPreviousRelease";

export default function EinsatzmeldungBearbeiten(props) {
  const defaultValues = {
    "state": "0",
    "environment": "0",
    "service": "0",
    "release": "0",
    "firstDeployment": true,
    "previousRelease": "0",
    "date": "",
    "automatedDeployment": false,
    "abnormalities": false,
    "description": "",
    "installationTime": "",
    "status": "1",
  };

  const [values, setValues] = useState(defaultValues);
  const [newReleases, setNewReleases] = useState([]);
  const [prevReleases, setPrevReleases] = useState([]);

  // Land
  // Umgebung -> Bei Änderung: Validieren, ob Release noch nicht eingesetzt
  // Verfahren?
  // Release - Liste muss korrekt befüllt werden!
  // Ersteinsatz
  // Vorgängerrelease - Liste muss korrekt befüllt werden!
  // Einsatzdatum
  // Beschreibung der Auffälligkeiten
  // Auffälligkeiten
  // Automatisiertes Deployment
  // Installationsdauer
  // Status

  // props.triggerReleaseSelect
  // props.setTriggerReleaseSelect

  useEffect(() => {
    if (props.showEditForm) {
      fetchDeployment();
    }
    
  }, [props.showEditForm])

  // Release Auswahl befüllen.
  useEffect(() => {
    if (props.triggerReleaseSelect === false) {
      return;
    }
    // Trigger zurücksetzen.
    props.setTriggerReleaseSelect(false);

    // setNewReleases(false); ???
    // Service und Umgebung müssen ausgewählt sein.
    if (values.environment == "0" || values.service == "0") {
      return;
    }
    // JsonAPI Fetch vorbereiten.
    // @todo Umbauen auf REST API. Vorteil: Mehr als 50 Elemente auf einmal fetchen.
    // Fehlmeldungen sollen rausgefiltert werden.
    let url = '/api/v1/deployments/1+2/';

    url += values.state + '/';
    url += values.environment + '/';
    url += values.service;

    const headers = new Headers({
      Accept: 'application/vnd.api+json',
    });

    fetch(url, { headers })
      .then(response => response.json())
      .then(results => {
        let deployedReleaseNids = results.map((deployment) => {
          return deployment.releaseNid;
        });
        if (values.service in props.releases) {
          // All provided releases for the selected service.
          var releaseArray = props.releases[props.service];
        }


        // Releases filtern: Eingesetzt (Vorgängerreleases).
        let filteredPrevReleases = releaseArray.filter(release => {
          return deployedReleaseNids.indexOf(release.nid) >= 0;
        })
        let deployedReleases = [];
        let product = false;
        for (const release in filteredPrevReleases) {
          deployedReleases.push(filteredPrevReleases[release]);
          // console.log(filteredPrevReleases[release].nid.toString(), props.previousRelease);
          if (filteredPrevReleases[release].nid.toString() == props.previousRelease) {
            const title = filteredPrevReleases[release].title;
            product = title.substring(0, title.indexOf('_') + 1);
          }
        }
        deployedReleases.sort((a, b) => b - a);

        // Releases filtern: Nicht Eingesetzt (Neue Einsatzmeldung).
        let filteredNewReleases = releaseArray.filter(release => {
          let result = false;
          if (deployedReleaseNids.indexOf(release.nid) === -1) {
            result = true;
          }
          if (product && release.title.indexOf(product) == -1) {
            result = false;
          }
          return result;
        })
        let undeployedReleases = [];
        for (const release in filteredNewReleases) {
          undeployedReleases.push(filteredNewReleases[release]);
        }
        undeployedReleases.sort((a, b) => b - a);

        // Produktfilterung, wenn Vorgängerrelease gewählt ist.
        // if (props.previousRelease != "0") {
        //   let product = 
        //   filteredNewReleases = filteredNewReleases.filter(release => {

        //   })
        // }

        console.log("Eingesetzte Releases wurden geholt und Releaseoptionen gefiltert.");
        setNewReleases(undeployedReleases);
        setPrevReleases(deployedReleases);
        props.setDisabled(false);
        props.setIsLoading(false);
      })
      .catch(error => console.log("error", error));
  }, [props.triggerReleaseSelect])

  //States Auswahl
  const statesObject = global.drupalSettings.states;
  let statesArray = Object.entries(statesObject);
  let optionsStates = statesArray.map(state => <option value={state[0]}>{state[1]}</option>)

  // Umgebungsauswahl
  const environments = global.drupalSettings.environments;
  let environmentsArray = Object.entries(environments);
  let optionsEnvironments = environmentsArray.map(environment => <option value={environment[0]}>{environment[1]}</option>)

  //Verfahren Drop Down
  const services = global.drupalSettings.services;

  let servicesArray = Object.entries(services);
  servicesArray.sort(function (a, b) {
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

  const fetchDeployment = () => {
    const url = "/jsonapi/node/deployed_releases/" + props.deploymentUuid + "?include=field_deployed_release,field_prev_release,field_service";
    const headers = new Headers({
      Accept: 'application/vnd.api+json',
    });
    fetch(url, headers)
      .then(response => response.json())
      .then(result => {
        console.log(result);

        const releaseObj = result.included.find(element => element.id == result.data.relationships.field_deployed_release.data.id);
        const prevReleaseObj = result.included.find(element => element.id == result.data.relationships.field_prev_release.data.id);
        const prevReleaseId = prevReleaseObj === undefined ? "" : prevReleaseObj.attributes.drupal_internal__nid;
        const serviceObj = result.included.find(element => element.id == result.data.relationships.field_service.data.id);

        setValues({
          "state": result.data.attributes.field_user_state,
          "environment": result.data.attributes.field_environment,
          "release": releaseObj.attributes.drupal_internal__nid,
          "previousRelease": prevReleaseId,
          "service": serviceObj.attributes.drupal_internal__nid,
          "firstDeployment": result.data.attributes.field_first_deployment,
          "date": result.data.attributes.field_date_deployed,
          "automatedDeployment": result.data.attributes.field_automated_deployment_bool,
          "abnormalities": result.data.attributes.field_abnormalities_bool,
          "description": result.data.attributes.field_abnormality_description,
          "installationTime": result.data.attributes.field_installation_time,
          "status": result.data.attributes.field_deployment_status,
        });
      })
      .catch(error => console.log("error", error));
  }
  console.log(values);
  const handleHide = () => {
    props.setShowEditForm(false);
    setValues(defaultValues);
  }
  const handleSave = () => {
    console.log("handlesave");
  }
  
  const handleServiceSelect = (e) => {
    let val = {};
    val[e.target.name] = e.target.value;
    setValues(prev => ({ ...prev, ...val }))

    // Triggers Releases fetch.
    props.setService(e.target.value);
  }
  const handleReleaseSelect = (release) => {
    setValues(prev => ({...prev, "release": release}));
  }

  const handleChange = e => {
    let val = {};
    val[e.target.name] = e.target.value;
    setValues(prev => ({ ...prev, ...val}))
  }

  return (
    <div>
      <Modal show={props.showEditForm} onHide={handleHide}>
        <Modal.Header closeButton>
          <Modal.Title>
            Einsatzmeldung bearbeiten
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <p>{props.prevDeploymentId}</p>
          <Form>
            <FormGroup controlId="1">
              <ControlLabel bsClass="control-label js-form-required form-required">Umgebung</ControlLabel>
              <div className="select-wrapper">
                <FormControl
                  componentClass="select"
                  name="state"
                  value={values.state}
                  onChange={handleChange}
                >
                  {optionsStates}
                </FormControl>
              </div>
            </FormGroup>

            <FormGroup controlId="2">
              <ControlLabel bsClass="control-label js-form-required form-required">Umgebung</ControlLabel>
              <div className="select-wrapper">
                <FormControl
                  componentClass="select"
                  name="environment"
                  value={values.environment}
                  onChange={handleChange}
                >
                  {optionsEnvironments}
                </FormControl>
              </div>
            </FormGroup>

            <FormGroup controlId="3">
              <ControlLabel bsClass="control-label js-form-required form-required">Verfahren</ControlLabel>
              <div className="select-wrapper">
                <FormControl
                  componentClass="select"
                  name="service"
                  value={values.service}
                  onChange={handleServiceSelect}
                >
                  {optionsServices}
                </FormControl>
              </div>
            </FormGroup>

            <SelectRelease
              release={values.release}
              setRelease={handleReleaseSelect}
              newReleases={newReleases}
              isLoading={props.isLoading}
              setIsLoading={props.setIsLoading}
              disabled={props.disabled}
              setDisabled={props.setDisabled}
            />
            {/* {!props.firstDeployment &&
              <SelectPreviousRelease
                previousRelease={props.previousRelease}
                setPreviousRelease={props.setPreviousRelease}
                prevReleases={prevReleases}
                isLoading={props.isLoading}
                setIsLoading={props.setIsLoading}
                disabled={disabledPrevRelease}
                setArchivePrevRelease={setArchivePrevRelease}
              />
            } */}
            <FormGroup controlId="6">
              <Checkbox
                name="firstDeployment"
                type="checkbox"
                checked={values.firstDeployment}
                onChange={handleChange}
              >
                Ersteinsatz
              </Checkbox>
            </FormGroup>

            <FormGroup controlId="7">
              <ControlLabel bsClass="control-label js-form-required form-required">Datum</ControlLabel>
              <FormControl
                type="date"
                name="datum"
                value={values.date}
                onChange={(e) => setValues(prev => ({...prev, "date": e.target.value}))}
              >
              </FormControl>
            </FormGroup>
            <FormGroup controlId="8">
              <ControlLabel bsClass="control-label">Installationsdauer</ControlLabel>
              <FormControl
                componentClass="input"
                type="number"
                step="1"
                min="1"
                name="installationsdauer"
                value={values.installationTime}
                onChange={(e) => setValues(prev => ({...prev, "installationTime": e.target.value}))}
                placeholder="in Minuten"
              >
              </FormControl>
            </FormGroup>

            <FormGroup controlId="9">
              <Checkbox
                name="automatisiert"
                type="checkbox"
                checked={values.automatedDeployment}
                onChange={(e) => setValues(prev => ({ ...prev, "automatedDeployment": e.target.checked }))}
              >
                Automatisiertes Deployment
              </Checkbox>
            </FormGroup>

            <FormGroup controlId="10">
              <Checkbox
                name="auffaelligkeiten"
                type="checkbox"
                checked={values.abnormalities}
                onChange={(e) => setValues(prev => ({ ...prev, "abnormalities": e.target.checked }))}
              >
                Auffälligkeiten
              </Checkbox>
            </FormGroup>
            {values.abnormalities &&
              <FormGroup controlId="11">
                <ControlLabel bsClass="control-label js-form-required form-required">Beschreibung der Auffälligkeiten</ControlLabel>
                <FormControl
                  componentClass="textarea"
                  name="beschreibung"
                  value={values.description}
                onChange={(e) => setValues(prev => ({ ...prev, "description": e.target.value }))}
                >
                </FormControl>
              </FormGroup>
            }
          </Form>
        </Modal.Body>
        <Modal.Footer>
          <Button bsStyle="primary" onClick={handleSave} >Speichern</Button>
          <Button bsStyle="danger" onClick={handleHide}>Abbrechen</Button>
        </Modal.Footer>
      </Modal>
    </div>
  )
}
