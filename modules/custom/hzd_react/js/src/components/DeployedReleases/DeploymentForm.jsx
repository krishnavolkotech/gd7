import React, { useState, useEffect } from "react";
import { Form, FormGroup, FormControl, ControlLabel, Checkbox, Button, Modal, OverlayTrigger, Tooltip, Radio, Table, Row, Col, Well, Alert } from 'react-bootstrap';
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

// const FormSkeleton = () => {
//   return (
//     <div>
//       <div className="skeleton-label loading"></div>
//       <div className="skeleton-select loading"></div>
//       <div className="skeleton-label loading"></div>
//       <div className="skeleton-select loading"></div>
//       <div className="skeleton-label loading"></div>
//       <div className="skeleton-select loading"></div>
//       <div className="skeleton-label loading"></div>
//       <div className="skeleton-select loading"></div>
//       <div>
//         <div className="skeleton-select-box loading"></div>
//         <div className="skeleton-select-box-label loading"></div>
//       </div>
//       <div>
//         <div className="skeleton-select-box loading"></div>
//         <div className="skeleton-select-box-label loading"></div>
//       </div>
//       <SelectReleaseSkeleton />
//     </div>
//   );
// }


export default function DeploymentForm(props) {

  const [disabledPrevRelease, setDisabledPrevRelease] = useState(true);
  const [disableSubmit, setDisableSubmit] = useState(false);
  const [validateMessage, setValidateMessage] = useState([]);
  const [title, setTitle] = useState("");

  const defaultEnvironments = [
    <option key="form-env-0" value="0">&lt;Umgebung&gt;</option>,
    <option key="form-env-1" value="1">Produktion</option>,
    <option key="form-env-2" value="2">Pilot</option>,
  ];
  const [environmentOptions, setEnvironmentOptions] = useState(defaultEnvironments);

  const [addPrevReleaseDisabled, setAddPrevReleaseDisabled] = useState(false);

  // Validation State.
  const [environmentValidationState, setEnvironmentValidationState] = useState('');
  const [serviceValidationState, setServiceValidationState] = useState('');
  const [dateValidationState, setDateValidationState] = useState('');
  const [timeValidationState, setTimeValidationState] = useState('');
  const [abnormalityValidationState, setAbnormalityValidationState] = useState('');
  const [releaseValidationState, setReleaseValidationState] = useState('');
  const [prevReleaseValidationState, setPrevReleaseValidationState] = useState('');

  // Validation Alert
  const [showValidationAlert, setShowValidationAlert] = useState(false);

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
          return (<option key={"form-env-" + result.attributes.drupal_internal__nid} value={result.attributes.drupal_internal__nid}>{result.attributes.title}</option>);
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

  let optionsServices = servicesArray.map(service => <option key={"form-service-" + service[0]} value={service[0]}>{service[1][0]}</option>)

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

  const ttAddPrevRelease = (
    <Tooltip id="ttAddPrevRelease">
      Weiteres Vorgängerrelease hinzufügen
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
    else {
      setDisabledPrevRelease(false);
    }
  }, [props.formState.firstDeployment, props.disabled])

  // Validate the form.
  useEffect(() => {
    // Resets validation state before re-evaluating the validation state.
    setValidateMessage([]);
    setDisableSubmit(false);
    setEnvironmentValidationState("success");
    setServiceValidationState("success");
    setDateValidationState("success");
    setTimeValidationState("");
    setAbnormalityValidationState("success");
    setReleaseValidationState("success");
    setPrevReleaseValidationState("success");

    // Verify environment selection.
    if (props.formState.environment === "0") {
      setDisableSubmit(true);
      setValidateMessage(prev => [...prev, <p key="val-environment"><span className="glyphicon glyphicon-exclamation-sign" /> <strong>Bitte eine Umgebung auswählen</strong></p>])
      setEnvironmentValidationState("error");
    }

    // Verify service selection.
    if (props.formState.service == "0") {
      setDisableSubmit(true);
      setValidateMessage(prev => [...prev, <p key="val-service"><span className="glyphicon glyphicon-exclamation-sign" /> <strong>Bitte ein Verfahren auswählen</strong></p>])
      setServiceValidationState("error");
    }

    // Verify date selection.
    if (!props.formState.date) {
      setDisableSubmit(true);
      setValidateMessage(prev => [...prev, <p key="val-date"><span className="glyphicon glyphicon-exclamation-sign" /> <strong>Bitte das Einsatzdatum auswählen</strong></p>])
      setDateValidationState("error");
    }

    // Validates, that the selected date is not in the future.
    if (props.formState.date) {
      const formDate = new Date(props.formState.date);
      const now = Date.now();
      if (formDate > now) {
        setDisableSubmit(true);
        setValidateMessage(prev => [...prev, <p key="val-date-error"><span className="glyphicon glyphicon-exclamation-sign" /> <strong>Das angegebene Einsatzdatum liegt in der Zukunft</strong></p>])
        setDateValidationState("error");
      }
    }

    // Validate format of field installation time.
    if (props.formState.installationTime.length > 0) {
      const allowedFormat = new RegExp('^[0-9]{1,3}:[0-5][0-9]$');
      if (!allowedFormat.test(props.formState.installationTime)) {
        setDisableSubmit(true);
        setValidateMessage(prev => [...prev, <p key="val-inst-time"><span className="glyphicon glyphicon-exclamation-sign" /> <strong>Unerlaubte Eingabe beim Feld "Installationsdauer". Mögliche Werte: 0:01 - 999:59</strong></p>]);
        setTimeValidationState("error");
      }
      else {
        setTimeValidationState("success");
      }
    }

    // Verify abnormality description.
    if (props.formState.abnormalities) {
      if (props.formState.description.length == 0) {
        setDisableSubmit(true);
        setValidateMessage(prev => [...prev, <p key="val-abnorm"><span className="glyphicon glyphicon-exclamation-sign" /> <strong>Bitte beschreiben Sie die Auffälligkeiten</strong></p>]);
        setAbnormalityValidationState("error");
      }
    }

    // Reset abnormality description, if abnormality checkbox is unchecked.
    if (!props.formState.abnormalities) {
      props.setFormState(prev => ({ ...prev, "description": "" }));
      setAbnormalityValidationState("");
    }
    
    // Verify release selection.
    if (props.formState.releaseNid == "0") {
      setDisableSubmit(true);
      setValidateMessage(prev => [...prev, <p key="val-release"><span className="glyphicon glyphicon-exclamation-sign" /> <strong>Bitte ein Release auswählen</strong></p>])
      setReleaseValidationState("error");
    }

    // if (props.formState.previousRelease == "0" || props.formState.previousRelease == "0") {
    //   props.setFormState(prev => ({...prev, "archivePrevRelease": "null"}));
    // }

    // Verify selection and addition of previous releases.
    let prevArchiveFailed = false;
    let prevSelectFailed = false;
    let addPrevDisable = false;
    props.formState.previousReleases.map(prev => {
      if (typeof prev.archive !== "boolean") {
        prevArchiveFailed = true;
      }
      if (prev.release.length <= 1) {
        addPrevDisable = true;
        prevSelectFailed = true;
      }
    });
    // Disables the add prev-release button, as long as no prev release has been selected.
    setAddPrevReleaseDisabled(addPrevDisable);

    // Disables submit, if archive radio has not been selected.
    if (prevArchiveFailed && props.formState.action != "edit") {
      setDisableSubmit(true);
      setValidateMessage(prev => [...prev, <p key="val-archive"><span className="glyphicon glyphicon-exclamation-sign" /> <strong>Bitte Auswählen: Vorgängerrelease archivieren?</strong></p>])
      setPrevReleaseValidationState("error");
    }
    // Disables Submit, if no previuos release has been selected.
    if (prevSelectFailed) {
      setDisableSubmit(true);
      setValidateMessage(prev => [...prev, <p key="val-select-prev"><span className="glyphicon glyphicon-exclamation-sign" /> <strong>Bitte ein Vorgängerrelease auswählen</strong></p>])
      setPrevReleaseValidationState("error");
    }


  }, [props.formState.environment, props.formState.service, props.formState.releaseNid, props.formState.previousReleases, props.formState.date, props.formState.abnormalities, props.formState.description, props.formState.archivePrevRelease, props.formState.installationTime, props.formState.pCount])

  // Hide Validation Message on form opening / closing.
  useEffect(() => {
    setShowValidationAlert(false);
  }, [props.showDeploymentForm])

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
    val.previousReleases = props.formState.previousReleases;
    val.pCount = props.formState.pCount + 1;
    if (e.target.value == "ja") {
      val.previousReleases[parseInt(e.target.name)].archive = true;
      props.setFormState(prev => ({ ...prev, ...val }));
    }
    if (e.target.value == "nein") {
      val.previousReleases[parseInt(e.target.name)].archive = false;
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

  const handleAddPrevRelease = () => {
    let val = {};
    val.previousReleases = props.formState.previousReleases;
    val.pCount = props.formState.pCount + 1;
    val.firstDeployment = false;
    val.previousReleases.push({
      "uuidRelease": "",
      "uuidDeployment": "",
      "archive": "",
      "title": "",
      "release": "",
    })
    props.setFormState(prev =>({ ...prev, ...val}));
  }

  const removePrevReleaseSelector = (i) => {
    let val = {};
    if (props.formState.previousReleases.length === 1) {
      val.firstDeployment = true;
    }
    val.previousReleases = props.formState.previousReleases;
    val.previousReleases.splice(i, 1);
    val.pCount = props.formState.pCount + 1;
    props.setFormState(prev => ({ ...prev, ...val }));
  }

  const submitClass = disableSubmit ? "glyphicon glyphicon-floppy-remove" : "glyphicon glyphicon-floppy-saved";

  var formBody = (
    <div>
        <Modal.Body>
          { showValidationAlert && validateMessage.length > 0 &&
          <Alert bsStyle="danger" onDismiss={() => setShowValidationAlert(false)}>
            <h4>Überprüfen Sie Ihre Eingaben</h4>
            <ul>
              {validateMessage}
            </ul>
          </Alert>
          }

            <FormGroup validationState={environmentValidationState} controlId="1">
              <ControlLabel bsClass="control-label js-form-required form-required">Umgebung</ControlLabel>
              <div className="select-wrapper">
                <FormControl
                  componentClass="select"
                  name="environment"
                  value={props.formState.environment}
                  onChange={handleChange}
                  bsClass={"form-control " + environmentValidationState}
                >
                  {environmentOptions}
                </FormControl>
              </div>
            </FormGroup>

            <FormGroup validationState={serviceValidationState} controlId="2">
              <ControlLabel bsClass="control-label js-form-required form-required">Verfahren</ControlLabel>
              <div className="select-wrapper">
                <FormControl
                  componentClass="select"
                  name="service"
                  value={props.formState.service}
                  onChange={handleChange}
                  disabled={!props.formState.firstDeployment}
                  bsClass={"form-control " + serviceValidationState}
                >
                  {optionsServices}
                </FormControl>
              </div>
            </FormGroup>
              <FormGroup validationState={dateValidationState} controlId="6">
                <ControlLabel bsClass="control-label js-form-required form-required">Datum</ControlLabel>
                <FormControl
                  type="date"
                  name="date"
                  value={props.formState.date}
                  onChange={handleChange}
                  bsClass={"form-control " + dateValidationState}
                >
                </FormControl>
              </FormGroup>
            <FormGroup validationState={timeValidationState} controlId="7">
              <ControlLabel bsClass="control-label">Installationsdauer</ControlLabel>
              <div className="custom-help-text">Mögliche Werte: 0:01 - 999:59, Format: hhh:mm </div>
              <FormControl
                componentClass="input"
                type="text"
                name="installationTime"
                value={props.formState.installationTime}
                onChange={handleChange}
                placeholder="hhh:mm"
                maxLength="6"
                bsClass={"form-control " + timeValidationState}
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
              <div>
                <FormGroup validationState={abnormalityValidationState} controlId="11">
                  <ControlLabel bsClass="control-label js-form-required form-required">Beschreibung der Auffälligkeiten</ControlLabel>
                  <div className="custom-help-text">{props.formState.description.length}/400 Zeichen verwendet</div>
                  <FormControl
                    componentClass="textarea"
                    name="description"
                    value={props.formState.description}
                    onChange={handleChange}
                    maxLength="400"
                    bsClass={"form-control " + abnormalityValidationState}
                  >
                  </FormControl>
                </FormGroup>
              </div>
            }
          {!props.isLoading &&
          <div>
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
                  releaseValidationState={releaseValidationState}
                />
                <Table className={"has-" + prevReleaseValidationState}>
                  <thead>
                    <tr>
                      <th></th>
                      <th><ControlLabel bsClass="control-label js-form-required form-required">Vorgängerrelease</ControlLabel></th>
                    </tr>
                  </thead>
                  <tbody>
                    {props.formState.previousReleases.map((r, i) => {
                      return (
                        <tr key={"pr-row-" + i}>
                          <td>
                          { r.release != "0" &&
                            <Button bsStyle="danger" onClick={() => removePrevReleaseSelector(i)} alt="Vorgängerrelease entfernen"><span className="glyphicon glyphicon-trash" /></Button>
                          }
                          </td>
                          <td>
                            <SelectPreviousRelease
                              formState={props.formState}
                              setFormState={props.setFormState}
                              handleChange={handleChange}
                              prevReleases={props.prevReleases}
                              isLoading={props.isLoading}
                              setIsLoading={props.setIsLoading}
                              disabled={disabledPrevRelease}
                              index={i}
                            />
                          {props.formState.action != 'edit' &&
                            <Form inline>
                              {/* <ControlLabel bsClass="control-label js-form-required form-required">Archivieren?</ControlLabel> */}
                              &nbsp;&nbsp;
                              <FormGroup onChange={handleRadio} controlId={"ja-" + i}>
                                &nbsp;
                                <Radio
                                  name={i}
                                  value="ja"
                                  checked={props.formState.previousReleases[i].archive === true}
                                  title="Ja"
                                  id={"ja-" + i}
                                >
                                  {/* <ControlLabel bsClass="control-label">Ja</ControlLabel> */}
                                  &nbsp;Archivieren
                                </Radio>
                              </FormGroup>
                                &nbsp;&nbsp;
                              <FormGroup onChange={handleRadio} controlId={"nein-" + i}>
                                &nbsp;
                                <Radio
                                  name={i}
                                  value="nein"
                                  checked={props.formState.previousReleases[i].archive === false}
                                  title="Nein"
                                  id={"nein-" + i}
                                >
                                  {/* <ControlLabel bsClass="control-label">Nein</ControlLabel> */}
                                  &nbsp;Nicht archivieren
                                </Radio>
                              </FormGroup>
                            </Form>
                          }
                          </td>
                        </tr>
                      );
                    })}
                    { props.formState.previousReleases.length === 0 &&
                  <tr>
                    <td></td>
                    <td>
                      <FormGroup controlId="4">
                        <div className="select-wrapper">
                          <FormControl
                            componentClass="select"
                            name="dummyPreviousRelease"
                            value={0}
                            disabled={true}
                          >
                            <option key="dummy-select-pr-1" value="0">Ersteinsatz</option>
                          </FormControl>
                        </div>
                      </FormGroup>
                    </td>
                  </tr>
                    }
                    { props.formState.action !== "first" &&
                    <tr>
                      <td colSpan="3">
                        <OverlayTrigger placement="top" overlay={ttAddPrevRelease}>
                          <Button disabled={addPrevReleaseDisabled} onClick={handleAddPrevRelease} bsStyle="success" alt="Vorgängerrelease hinzufügen"><span className="glyphicon glyphicon-plus" /></Button>
                        </OverlayTrigger>
                      </td>
                    </tr>
                    }
                  </tbody>
                </Table>
              </div>
            </div>
            {props.formState.action == "edit" &&
              <FormGroup controlId="6">
                <ControlLabel bsClass="control-label js-form-required form-required">Status</ControlLabel>
                <div className="select-wrapper">
                  <FormControl
                    componentClass="select"
                    name="status"
                    value={props.formState.status}
                    onChange={handleChange}
                  >
                    <option key="status-1" value="1">Im Einsatz</option>
                    <option key="status-2" value="2">Archiviert</option>
                    <option key="status-3" value="3">Fehlmeldung</option>
                  </FormControl>
                </div>
              </FormGroup>
            }
          </div>
          }
          {props.isLoading === true &&
            <SelectReleaseSkeleton />
          }
        </Modal.Body>
        <Modal.Footer>
          { !disableSubmit &&
          <OverlayTrigger placement="top" overlay={ttValidateMessage}>
            <Button disabled={disableSubmit} bsStyle="primary" onClick={props.handleSave} ><span className={submitClass} /> Speichern</Button>
          </OverlayTrigger>
          }
          { disableSubmit &&
          <OverlayTrigger placement="top" overlay={ttValidateMessage}>
            <Button bsStyle="primary" onClick={() => setShowValidationAlert(true)} ><span className={submitClass} /> Speichern</Button>
          </OverlayTrigger>
          }
        <Button bsStyle="danger" onClick={props.handleClose}><span className="glyphicon glyphicon-remove" /> Abbrechen</Button>
        {/* <Button bsStyle="danger" onClick={() => props.setSubmitMessage(<FormSkeleton />)}><span className="glyphicon glyphicon-remove" /> Skeleton</Button> */}
        </Modal.Footer>
    </div>
    );

  if (props.submitMessage.length > 0) {
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



 