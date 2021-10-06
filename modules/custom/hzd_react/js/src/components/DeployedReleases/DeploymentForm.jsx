import React, { useState, useEffect } from "react";
import { Form, FormGroup, FormControl, ControlLabel, Checkbox, Button, Modal, OverlayTrigger, Tooltip, Radio, Table, Row, Col, Well } from 'react-bootstrap';
import { fetchWithCSRFToken } from "../../utils/fetch";
import SelectRelease from "./SelectRelease";
import SelectPreviousRelease from "./SelectPreviousRelease";

const SelectReleaseSkeleton = () => {
  return (
    <div className="panel panel-default">
      <div className="panel-body">
        <div className="skeleton-label loading"></div>
        <div className="skeleton-select loading"></div>
        <div className="skeleton-textbody loading"></div>
        <div className="skeleton-textbody loading"></div>
      </div>
    </div>
  );
}

const FormSkeleton = () => {
  return (
    <div>
      <div className="skeleton-label loading"></div>
      <div className="skeleton-select loading"></div>
      <div className="skeleton-label loading"></div>
      <div className="skeleton-select loading"></div>
      <div className="skeleton-label loading"></div>
      <div className="skeleton-select loading"></div>
      <div className="skeleton-label loading"></div>
      <div className="skeleton-select loading"></div>
      <div>
        <div className="skeleton-select-box loading"></div>
        <div className="skeleton-select-box-label loading"></div>
      </div>
      <div>
        <div className="skeleton-select-box loading"></div>
        <div className="skeleton-select-box-label loading"></div>
      </div>
      <SelectReleaseSkeleton />
    </div>
  );
}


export default function DeploymentForm(props) {

  const [disabledPrevRelease, setDisabledPrevRelease] = useState(true);
  const [disableSubmit, setDisableSubmit] = useState(false);
  const [validateMessage, setValidateMessage] = useState([]);
  const [title, setTitle] = useState("");

  const defaultEnvironments = [
    <option value="0">&lt;Umgebung&gt;</option>,
    <option value="1">Produktion</option>,
    <option value="2">Pilot</option>,
  ];
  const [environmentOptions, setEnvironmentOptions] = useState(defaultEnvironments);

  // let firstDeployment = false;
  // if (props.previousRelease == "0") {
  //   firstDeployment = true;
  // }

  // Neue Meldung wird erstellt (POST) und/oder eine Meldung archiviert (PATCH).

  // POST Request zum anlegen einer neuen Einsatzmeldung.

  // 10.09.2021 - Nicht mehr beötigt, da handleClose das übernimmt?
  // useEffect(() => {
  //   if (props.showDeploymentForm === true) {
  //     props.setSubmitMessage(false);
  //   }
  // }, [props.showDeploymentForm])

  //Umgebungen Drop Down
  // const environments = global.drupalSettings.environments;
  // let environmentsArray = Object.entries(environments);
  // let optionsEnvironments = environmentsArray.map(environment => <option value={environment[0]}>{environment[1]}</option>);
  useEffect(() => {
    let url = '/jsonapi/node/non_production_environment';
    url += '?fields[node--non_production_environment]=drupal_internal__nid,field_non_production_state,title';
    url += '&filter[field_non_production_state]=' + props.formState.state;
    const headers = new Headers({
      Accept: 'application/vnd.api+json',
    });
    fetch(url, {headers})
      .then(results => results.json())
      .then(results => {
        const environments = results.data.map(result => {
          return (<option value={result.attributes.drupal_internal__nid}>{result.attributes.title}</option>);
        });
        setEnvironmentOptions([...defaultEnvironments, ...environments]);
      })
      .catch(error => console.log(error));
  }, [props.formState.state])

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

  // Tooltip für Button "Neue Einsatzmeldung".
  // const ttNewReport = (
  //   <Tooltip id="ttNewReport">
  //     Einen neuen Ersteinsatz melden.<br/>
  //     <strong>Hinweis: </strong> Möchten Sie ein Nachfolgerelease melden? Dies können Sie tun, indem sie in der untenstehenden Tabelle auf  <span className="glyphicon glyphicon-forward" /> klicken.
  //   </Tooltip>
  // );

  const ttValidateMessage = (
    <Tooltip id="ttValidateMessage">
      <ul>
        {validateMessage}
      </ul>
      Erstellt die neue Einsatzmeldung.
    </Tooltip>
  );

  // Disables SelectPreviousRelease.
  useEffect(() => {
    if (props.firstDeployment) {
      setDisabledPrevRelease(true);
    }
    else {
      setDisabledPrevRelease(false);
    }

    if (props.disabled) {
      setDisabledPrevRelease(true);
    }
  }, [props.formState.firstDeployment, props.disabled])

  // Validate form.
  useEffect(() => {
    setValidateMessage([]);
    setDisableSubmit(false);

    if (props.formState.environment === "0") {
      setDisableSubmit(true);
      setValidateMessage(prev => [...prev, <p><span className="glyphicon glyphicon-exclamation-sign" /> <strong>Bitte eine Umgebung auswählen</strong></p>])
    }

    if (props.formState.service == "0") {
      setDisableSubmit(true);
      setValidateMessage(prev => [...prev, <p><span className="glyphicon glyphicon-exclamation-sign" /> <strong>Bitte ein Verfahren auswählen</strong></p>])
    }

    if (props.formState.releaseNid == "0") {
      setDisableSubmit(true);
      setValidateMessage(prev => [...prev, <p><span className="glyphicon glyphicon-exclamation-sign" /> <strong>Bitte ein Release auswählen</strong></p>])
    }

    if (props.formState.previousRelease == "0" || props.formState.previousRelease == "0") {
      props.setFormState(prev => ({...prev, "archivePrevRelease": "null"}));
      // setArchivePrevRelease('null');
    }

    if (props.formState.previousRelease != "0") {
      if (props.formState.archivePrevRelease == 'null') {
        setDisableSubmit(true);
        setValidateMessage(prev => [...prev, <p><span className="glyphicon glyphicon-exclamation-sign" /> <strong>Bitte Auswählen: Vorgängerrelease archivieren?</strong></p>])
      }
    }

    if (!props.formState.date) {
      setDisableSubmit(true);
      setValidateMessage(prev => [...prev, <p><span className="glyphicon glyphicon-exclamation-sign" /> <strong>Bitte ein Einsatzdatum auswählen</strong></p>])
    }

    if (props.formState.abnormalities) {
      if (props.formState.description.length == 0) {
        setDisableSubmit(true);
        setValidateMessage(prev => [...prev, <p><span className="glyphicon glyphicon-exclamation-sign" /> <strong>Bitte beschreiben Sie die Auffälligkeiten</strong></p>])
      }
    }

    if (!props.formState.abnormalities) {
      props.setFormState(prev => ({ ...prev, "description": "" }));

    }

  }, [props.formState.environment, props.formState.service, props.formState.releaseNid, props.formState.previousRelease, props.formState.date, props.formState.abnormalities, props.formState.description, props.formState.archivePrevRelease])

  // Set title.
  useEffect(() => {
    let state = props.formState.state;
    if (typeof props.formState.state === 'string') {
      state = parseInt(props.formState.state);
    }
    switch (props.formState.action) {
      case "first":
        setTitle("Neuer Ersteinsatz - " + global.drupalSettings.states[state]);
        break;
      case "successor":
        setTitle("Nachfolgerelease melden - " + global.drupalSettings.states[state]);
        break;
      case "edit":
        setTitle("Einsatzmeldung bearbeiten - " + global.drupalSettings.states[state]);
        break;
    }
  }, [props.formState.action])

  const handleRadio = (e) => {
    let val = {};
    if (e.target.value == "ja") {
      val[e.target.name] = true;
      props.setFormState(prev => ({ ...prev, ...val }));
    }
    if (e.target.value == "nein") {
      val[e.target.name] = false;
      props.setFormState(prev => ({ ...prev, ...val }));
    }
  }

  const handleChange = (e) => {
    let val = {};
    if (e.target.type == "checkbox") {
      val[e.target.name] = e.target.checked;
    }
    else {
      val[e.target.name] = e.target.value;
    }
    props.setFormState(prev => ({ ...prev, ...val }));
  }

  const submitClass = disableSubmit ? "glyphicon glyphicon-floppy-remove" : "glyphicon glyphicon-floppy-saved";

  var formBody = (
    <div>
        <Modal.Body>
          <Form>
            <FormGroup controlId="1">
              <ControlLabel bsClass="control-label js-form-required form-required">Umgebung</ControlLabel>
              <div className="select-wrapper">
                <FormControl
                  componentClass="select"
                  name="environment"
                  value={props.formState.environment}
                  onChange={handleChange}
                >
                  {environmentOptions}
                </FormControl>
              </div>
            </FormGroup>

            <FormGroup controlId="2">
              <ControlLabel bsClass="control-label js-form-required form-required">Verfahren</ControlLabel>
              <div className="select-wrapper">
                <FormControl
                  componentClass="select"
                  name="service"
                  value={props.formState.service}
                  onChange={handleChange}
                  disabled={!props.formState.firstDeployment}
                >
                  {optionsServices}
                </FormControl>
              </div>
            </FormGroup>
              <FormGroup controlId="6">
                <ControlLabel bsClass="control-label js-form-required form-required">Datum</ControlLabel>
                <FormControl
                  type="date"
                  name="date"
                  value={props.formState.date}
                  onChange={handleChange}
                >
                </FormControl>
              </FormGroup>
            <FormGroup controlId="7">
              <ControlLabel bsClass="control-label">Installationsdauer</ControlLabel>
              <FormControl
                componentClass="input"
                type="number"
                step="1"
                min="1"
                name="installationTime"
                value={props.formState.installationTime}
                onChange={handleChange}
                placeholder="in Minuten"
              >
              </FormControl>
            </FormGroup>

            <FormGroup controlId="9">
              <Checkbox
                name="isAutomated"
                type="checkbox"
                checked={props.formState.isAutomated}
                onChange={handleChange}
              >
                Automatisiertes Deployment
              </Checkbox>
            </FormGroup>

            <FormGroup controlId="10">
              <Checkbox
                name="abnormalities"
                type="checkbox"
                checked={props.formState.abnormalities}
                onChange={handleChange}
              >
                Auffälligkeiten
              </Checkbox>
            </FormGroup>
          {props.formState.abnormalities &&
            <FormGroup controlId="11">
              <ControlLabel bsClass="control-label js-form-required form-required">Beschreibung der Auffälligkeiten</ControlLabel>
              <FormControl
                componentClass="textarea"
                name="description"
                value={props.formState.description}
                onChange={handleChange}
              >
              </FormControl>
            </FormGroup>
          }
          {!props.isLoading &&
          <div className="panel panel-default">
            <div className="panel-body">
              {/* <Well bsSize="small">
              {props.loadingMessage}
              </Well> */}
              <SelectRelease
                formState={props.formState}
                handleChange={handleChange}
                newReleases={props.newReleases}
                isLoading={props.isLoading}
                setIsLoading={props.setIsLoading}
                disabled={props.disabled}
                setDisabled={props.setDisabled}
              />
              <Table>
                <thead>
                  <tr>
                    <th><ControlLabel>Vorgängerrelease</ControlLabel></th>
                    <th>
                      {props.formState.action != 'edit' &&
                      <ControlLabel bsClass="control-label js-form-required form-required">Vorgängerrelease archivieren?</ControlLabel>
                      }
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>
                      <SelectPreviousRelease
                        formState={props.formState}
                        handleChange={handleChange}
                        prevReleases={props.prevReleases}
                        isLoading={props.isLoading}
                        setIsLoading={props.setIsLoading}
                        disabled={disabledPrevRelease}
                      />
                    </td>
                    <td>
                    {props.formState.previousRelease > 0 && props.formState.action != 'edit' &&
                      <FormGroup onChange={handleRadio} controlId="5">
                        <Radio
                          name="archivePrevRelease"
                          value="ja"
                          checked={props.formState.archivePrevRelease === true}
                        >
                          Ja
                        </Radio>
                        <Radio
                          name="archivePrevRelease"
                          value="nein"
                          checked={props.formState.archivePrevRelease === false}
                        >
                          Nein
                        </Radio>
                      </FormGroup>
                    }
                    </td>
                  </tr>
                </tbody>
              </Table>
            </div>
          </div>
          }
          {props.isLoading === true &&
            <SelectReleaseSkeleton />
          }
          </Form>
        </Modal.Body>
        <Modal.Footer>
          <OverlayTrigger placement="top" overlay={ttValidateMessage}>
            <Button disabled={disableSubmit} bsStyle="primary" onClick={props.handleSave} ><span className={submitClass} /> Speichern</Button>
          </OverlayTrigger>
        <Button bsStyle="danger" onClick={props.handleClose}><span className="glyphicon glyphicon-remove" /> Abbrechen</Button>
        {/* <Button bsStyle="danger" onClick={() => props.setSubmitMessage(<FormSkeleton />)}><span className="glyphicon glyphicon-remove" /> Skeleton</Button> */}
        </Modal.Footer>
    </div>
    );

  if (props.submitMessage !== false) {
    formBody = (
      <div>
        <Modal.Body>
          {props.submitMessage}
        </Modal.Body>
        <Modal.Footer>
          <Button bsStyle="danger" onClick={props.handleClose}><span className="glyphicon glyphicon-remove" /> Schließen</Button>
          {/* <Button bsStyle="danger" onClick={() => props.setSubmitMessage(false)}><span className="glyphicon glyphicon-remove" /> Skeleton</Button> */}
        </Modal.Footer>
      </div>
    );
  }


  return (
    <div>
      <Modal show={props.showDeploymentForm} onHide={props.handleClose}>
        <Modal.Header closeButton>
          <Modal.Title>{title}</Modal.Title>
        </Modal.Header>
      {formBody}
      </Modal>
    </div>
  );
}



 