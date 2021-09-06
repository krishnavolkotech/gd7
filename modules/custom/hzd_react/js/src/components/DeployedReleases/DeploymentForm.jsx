import React, { useState, useEffect } from "react";
import { Form, FormGroup, FormControl, ControlLabel, Checkbox, Button, Modal, OverlayTrigger, Tooltip, Radio, Table, Row } from 'react-bootstrap';
import { fetchWithCSRFToken } from "../../utils/fetch";
import SelectRelease from "./SelectRelease";
import SelectPreviousRelease from "./SelectPreviousRelease";

export default function DeploymentForm(props) {

  // const [release, setRelease] = useState(false);
  // const [date, setDate] = useState(false);
  // const [installationTime, setInstallationTime] = useState(false);
  // const [archive, setArchive] = useState(false);
  // const [isAutomated, setIsAutomated] = useState(false);
  // const [abnormalities, setAbnormalities] = useState(false);
  // const [description, setDescription] = useState("");
  // const [archivePrevRelease, setArchivePrevRelease] = useState('null');
  const [disabledPrevRelease, setDisabledPrevRelease] = useState(true);
  const [disableSubmit, setDisableSubmit] = useState(false);
  const [validateMessage, setValidateMessage] = useState([]);
  const [submitMessage, setSubmitMessage] = useState(false);


  // console.log(
  //   " Umgebung: ",
  //   props.environment,
  //   "\n Verfahren: ",
  //   props.service,
  //   "\n Release: ",
  //   release,
  //   "\n Vorgängerrelease: ",
  //   props.previousRelease,
  //   "\n Datum: ",
  //   date,
  //   "\n Installationsdauer: ",
  //   installationTime,
  //   "\n Ist Archiviert: ",
  //   archive,
  //   "\n Ist Automatisch: ",
  //   isAutomated,
  //   "\n Hat Aufälligkeiten: ",
  //   abnormalities,
  //   "\n Auffälligkeiten: ",
  //   description,
  //   "\n archivePrevRelease: ",
  //   archivePrevRelease
  // );

  // let firstDeployment = false;
  // if (props.previousRelease == "0") {
  //   firstDeployment = true;
  // }

  // Neue Meldung wird erstellt (POST) und/oder eine Meldung archiviert (PATCH).
  function handleSave() {
    console.log("##### HANDLE SAVE START #####")
    postDeployment();
    
    // Nach Absendung des Formulars alles zurücksetzen.
    props.setEnvironment(1);
    props.setService("0");
    setRelease(false);
    props.setPreviousRelease("0");
    setDate(false);
    setInstallationTime(false);
    setArchive(false);
    setIsAutomated(false);
    setAbnormalities(false);
    setDescription("");
    setArchivePrevRelease('null');
  }

  // POST Request zum anlegen einer neuen Einsatzmeldung.
  const postDeployment = () => {
    // UUID des gemeldeten Release.
    // @todo releases aus Manager beziehen
    const allReleases = props.releases;
    let currentRelease = allReleases[props.service].filter(element => {
      return release === element.nid;
    })
    
    if (currentRelease.length == 0) {
      props.setError(<li>Die Einsatzmeldung konnte nicht erstellt werden, weil die zugehörige UUID nicht ermittelt werden konnte.</li>);
      setSubmitMessage(<li>Die Einsatzmeldung konnte nicht erstellt werden, weil die zugehörige UUID nicht ermittelt werden konnte.</li>);
      return;
    }
    const uuidRelease = currentRelease[0].uuid;
    const releaseName = currentRelease[0].title;
    setSubmitMessage(<p>Einsatzmeldung für {releaseName} wird gespeichert ... <span className="glyphicon glyphicon-refresh glyphicon-spin" role="status" /></p>);
    const product = releaseName.substring(0, releaseName.indexOf('_'));

    // UUID des Verfahrens.
    const allServices = global.drupalSettings.services;
    if (props.service in allServices) {
      var uuidService = allServices[props.service][1];
    }
    const deploymentTitle = props.userState + "_" + props.environment + "_" + props.service + "_" + product;

    var postdata = {
      "data": {
        "type": "node--deployed_releases",
        "attributes": {
          "title": deploymentTitle,
          "field_deployment_status": archive ? '2' : '1',
          "field_first_deployment": props.firstDeployment,
          "field_abnormalities_bool": abnormalities,
          "field_automated_deployment_bool": isAutomated,
          "field_abnormality_description": description,
          "field_date_deployed": date,
          "field_installation_time": installationTime,
          "field_user_state": props.userState,
          "field_environment": props.environment,
        },
        "relationships": {
          "field_deployed_release": {
            "data": {
              "type": "node--release",
              "id": uuidRelease
            },
          },
          "field_service": {
            "data": {
              "type": "node--services",
              "id": uuidService,
            },
          },
        }
      }
    }

    // in case a previous release has been selected in the deployed_releases_form,
    // var data should be completed with relationsship field_prev_release
    if (props.previousRelease != "0") {
      // UUID des Vorgängerrelease.
      // @todo prevReleases aus Manager ziehen
      let pRelease = allReleases[props.service].filter(element => {
        return props.previousRelease === element.nid;
      })

      if (pRelease.length == 0) {
        props.setError(<li>Die Einsatzmeldung konnte nicht erstellt werden, weil die zugehörige UUID nicht ermittelt werden konnte.</li>);
        return;
      }
      const uuidPrevRelease = pRelease[0].uuid;

      let field_prev_release = {
        "data": {
          "type": "node--release",
          "id": uuidPrevRelease,
        },
      }
      postdata["data"]["relationships"] = { ...postdata["data"]["relationships"], field_prev_release };
    }

    console.log("POST");
    console.log(postdata);
    const csrfUrl = `/session/token?_format=json`;
    let fetchUrl = "/jsonapi/node/deployed_releases";
    let fetchOptions = {
      method: 'POST',
      headers: new Headers({
        'Accept': 'application/vnd.api+json',
        'Content-Type': 'application/vnd.api+json',
        'Cache': 'no-cache',
      }),
      body: JSON.stringify(postdata),
    }

    fetchWithCSRFToken(csrfUrl, fetchUrl, fetchOptions)
      .then(antwort => antwort.json())
      .then(antwort => {
        console.log(antwort);
        props.setCount(props.count + 1);
        if ("errors" in antwort) {
          props.setError(<li>Die Einsatzmeldung konnte nicht erstellt werden.</li>);
          setSubmitMessage(<li>Die Einsatzmeldung konnte nicht erstellt werden.</li>);
        }
        else {
          props.setDeploymentHistory(prev => [...prev, release]);
          if (props.prevDeploymentId && archivePrevRelease === true) {
            patchDeployment();
          }
          else {
            setSubmitMessage(<li>Einsatzmeldung gespeichert.</li>);
            props.handleClose();
          }
        }
      })
      .catch(error => {
        console.log('fehler:', error);
        props.setError(<li>Die Einsatzmeldung konnte nicht erstellt werden.</li>);
        setSubmitMessage(<li>Die Einsatzmeldung konnte nicht erstellt werden.</li>);
      });
  }

  // PATCH: Vorgängerrelease archivieren.
  const patchDeployment = () => {
      const archiveBody = {
        "data": {
          "type": "node--deployed_releases",
          "id": props.prevDeploymentId,
          "attributes": {
            "field_deployment_status": "2"
          }
        }
      }
      const csrfUrl = `/session/token?_format=json`;
      const fetchUrl = '/jsonapi/node/deployed_releases/' + props.prevDeploymentId;
      const fetchOptions = {
        method: 'PATCH',
        headers: new Headers({
          'Accept': 'application/vnd.api+json',
          'Content-Type': 'application/vnd.api+json',
          'Cache': 'no-cache',
        }),
        body: JSON.stringify(archiveBody),
      }
      setSubmitMessage(<p>Vorgängerrelease wird archiviert ... <span className="glyphicon glyphicon-refresh glyphicon-spin" role="status" /></p>);
      fetchWithCSRFToken(csrfUrl, fetchUrl, fetchOptions)
        .then(antwort => antwort.json())
        .then(antwort => {
          console.log(antwort);
          props.setCount(props.count + 1);
          if ("errors" in antwort) {
            setSubmitMessage(<li>Das Vorgängerrelease konnte nicht archiviert werden.</li>);
            props.setError(<li>Das Vorgängerrelease konnte nicht archiviert werden.</li>);
          }
          else {
            setSubmitMessage(<li>Einsatzmeldung gespeichert.</li>);
            props.handleClose();
          }
        })
        .catch(error => {
          console.log('fehler:', error);
          setSubmitMessage(<li>Das Vorgängerrelease konnte nicht archiviert werden.</li>);
          props.setError(<li>Das Vorgängerrelease konnte nicht archiviert werden.</li>);
        });

  }

  useEffect(() => {
    if (props.showDeploymentForm === true) {
      setSubmitMessage(false);
    }
  }, [props.showDeploymentForm])

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

    if (props.formState.service == 0) {
      setDisableSubmit(true);
      setValidateMessage(prev => [...prev, <p><span className="glyphicon glyphicon-exclamation-sign" /> <strong>Bitte ein Verfahren auswählen</strong></p>])
    }

    if (props.formState.release == 0) {
      setDisableSubmit(true);
      setValidateMessage(prev => [...prev, <p><span className="glyphicon glyphicon-exclamation-sign" /> <strong>Bitte ein Release auswählen</strong></p>])
    }

    if (props.formState.previousRelease == 0 || props.formState.previousRelease == "0") {
      props.setFormState(prev => ({...prev, "archivePrevRelease": "null"}));
      // setArchivePrevRelease('null');
    }

    if (props.formState.previousRelease != 0) {
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
      if (description.length == 0) {
        setDisableSubmit(true);
        setValidateMessage(prev => [...prev, <p><span className="glyphicon glyphicon-exclamation-sign" /> <strong>Bitte beschreiben Sie die Auffälligkeiten</strong></p>])
      }
    }

    if (!props.formState.abnormalities) {
      props.setFormState(prev => ({ ...prev, "description": "" }));

    }

  }, [props.formState.environment, props.formState.service, props.formState.release, props.formState.previousRelease, props.formState.date, props.formState.abnormalities, props.formState.description, props.formState.archivePrevRelease])

  const handleRadio = (e) => {
    if (e.target.value == "ja") {
      setArchivePrevRelease(true);
    }
    if (e.target.value == "nein") {
      setArchivePrevRelease(false);
    }
  }

  const handleChange = (e) => {
    let val = {};
    val[e.target.name] = e.target.value;
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
                  {optionsEnvironments}
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
                onChange={(e) => props.setFormState(prev => ({...prev, "isAutomated": e.target.checked}))}
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
          <div className="panel panel-default">
            <div className="panel-body">
              {props.loadingMessage}
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
                    <th><ControlLabel bsClass="control-label js-form-required form-required">Vorgängerrelease archivieren?</ControlLabel></th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>
              {/* <SelectPreviousRelease
                previousRelease={props.previousRelease}
                setPreviousRelease={props.setPreviousRelease}
                prevReleases={prevReleases}
                isLoading={props.isLoading}
                setIsLoading={props.setIsLoading}
                disabled={disabledPrevRelease}
                setArchivePrevRelease={setArchivePrevRelease}
              /> */}
                    </td>
                    <td>
              {props.previousRelease > 0 &&
                <FormGroup onChange={handleRadio} controlId="5">
                  <Radio
                    value="ja"
                    checked={archivePrevRelease === true}
                  >
                    Ja
                  </Radio>
                  <Radio
                    value="nein"
                    checked={archivePrevRelease === false}
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
          </Form>
        </Modal.Body>
        <Modal.Footer>
          <OverlayTrigger placement="top" overlay={ttValidateMessage}>
            <Button disabled={disableSubmit} bsStyle="primary" onClick={handleSave} ><span className={submitClass} /> Speichern</Button>
          </OverlayTrigger>
          <Button bsStyle="danger" onClick={props.handleClose}><span className="glyphicon glyphicon-remove" /> Abbrechen</Button>
        </Modal.Footer>  
    </div>
    );

  if (submitMessage !== false) {
    formBody = (
      <div>
        <Modal.Body>
          {submitMessage}
        </Modal.Body>
        <Modal.Footer>
          <Button bsStyle="danger" onClick={props.handleClose}><span className="glyphicon glyphicon-remove" /> Schließen</Button>
        </Modal.Footer>
      </div>
    );
  }


  return (
    <div>
      { props.filterState.status == "1" &&
      <div>
        <p />
        <Button bsStyle="primary" bsSize="large" onClick={props.handleFirstDeployment}>
          <span className="glyphicon glyphicon-plus" /> Ersteinsatz melden
        </Button>
      </div>
      }
      <Modal show={props.showDeploymentForm} onHide={props.handleClose}>
        <Modal.Header closeButton>
          {props.firstDeployment ? <Modal.Title>Ersteinsatz melden - {global.drupalSettings.states[props.userState]}</Modal.Title> : <Modal.Title>Nachfolger melden - {global.drupalSettings.states[props.userState]}</Modal.Title>}
        </Modal.Header>
      {formBody}
      </Modal>
    </div>
  );
}
