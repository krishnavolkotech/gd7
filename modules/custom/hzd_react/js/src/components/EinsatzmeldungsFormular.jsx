import React, { useState, useEffect } from "react";
import { Form, FormGroup, FormControl, ControlLabel, Checkbox, Button, Modal, OverlayTrigger, Tooltip } from 'react-bootstrap';
import { fetchWithCSRFToken } from "../utils/fetch";
import SelectRelease from "./SelectRelease";
import SelectPreviousRelease from "./SelectPreviousRelease";

export default function EinsatzmeldungsFormular(props) {

  const [release, setRelease] = useState(false);
  const [date, setDate] = useState(false);
  const [installationTime, setInstallationTime] = useState(false);
  const [isArchived, setIsArchived] = useState(false);
  const [isAutomated, setIsAutomated] = useState(false);
  const [abnormalities, setAbnormalities] = useState(false);
  const [description, setDescription] = useState("");
  const [archivePrevRelease, setArchivePrevRelease] = useState(false);
  // Für SelectRelease Komponente. Die nicht-eingesetzten Releases.
  const [newReleases, setNewReleases] = useState([]);
  // Für SelectPreviousRelease Komponente. Die eingesetzten Releases.
  const [prevReleases, setPrevReleases] = useState([]);


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
  //   isArchived,
  //   "\n Ist Automatisch: ",
  //   isAutomated,
  //   "\n Hat Aufälligkeiten: ",
  //   abnormalities,
  //   "\n Auffälligkeiten: ",
  //   description,
  //   "\n Vorgänger archivieren: ",
  //   archivePrevRelease
  // );

  // Release und Vorgängerrelease zurücksetzen, sobald ein anderes Verfahren 
  // gewählt wird.
  // useEffect(() => {
  //   setRelease("0");
  //   props.setPreviousRelease("0");
  // }, [props.service])

  // Fetches and filters deployed releases from releases.
  useEffect(() => {
    if (props.triggerReleaseSelect === false) {
      return;
    }
    // Trigger zurücksetzen.
    props.setTriggerReleaseSelect(false);

    console.log("Eingesetzte Releases werden geholt, damit Releases für den Dropdown gefiltert werden können...");
    // setNewReleases(false); ???
    // Service und Umgebung müssen ausgewählt sein.
    if (props.environment == 0 || props.service == 0) {
      return;
    }
    // JsonAPI Fetch vorbereiten.
    // @todo Umbauen auf REST API. Vorteil: Mehr als 50 Elemente auf einmal fetchen.
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
    url += "&filter[field_environment]=" + props.environment;
    url += "&filter[field_service.drupal_internal__nid]=" + props.service;

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
        if (props.service in props.releases) {
          // All provided releases for the selected service.
          var releaseArray = props.releases[props.service];
        }

        
        // Releases filtern: Eingesetzt (Vorgängerreleases).
        let filteredPrevReleases = releaseArray.filter(release => {
          return deployedReleaseNids.indexOf(parseInt(release.nid)) >= 0;
        })
        let deployedReleases = [];
        let product = false;
        for (const release in filteredPrevReleases) {
          deployedReleases.push(filteredPrevReleases[release]);
          // console.log(filteredPrevReleases[release].nid.toString(), props.previousRelease);
          if (filteredPrevReleases[release].nid.toString() == props.previousRelease) {
            const title = filteredPrevReleases[release].title;
            product = title.substring(0, title.indexOf('_')+1);
          }
        }
        deployedReleases.sort((a, b) => b - a);

        // Releases filtern: Nicht Eingesetzt (Neue Einsatzmeldung).
        let filteredNewReleases = releaseArray.filter(release => {
          let result = false;
          if (deployedReleaseNids.indexOf(parseInt(release.nid)) === -1) {
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


  let firstDeployment = false;
  if (props.previousRelease == "0") {
    firstDeployment = true;
  }

  // Neue Meldung wird erstellt (POST) und/oder eine Meldung archiviert (PATCH).
  function handleSave() {
    console.log("!##### HANDLE SAVE START #####!")
    // UUID des gemeldeten Release.
    // @todo releases aus Manager beziehen
    const allReleases = props.releases;
    // console.log(props.service);
    // console.log(allReleases);
    // if (release in allReleases[props.service]) {
    //   console.log(allReleases[props.service][release]);
    //   var uuidRelease = allReleases[props.service][release][0];
    // }
    let uuidRelease = allReleases[props.service].filter(element => {
      return release === element.nid;
    })

    if (uuidRelease.length == 0) {
      props.setError(<li>Die Einsatzmeldung konnte nicht erstellt werden, weil die zugehörige UUID nicht ermittelt werden konnte.</li>);
      return;
    }
    uuidRelease = uuidRelease[0].uuid;
    
    // UUID des Verfahrens.
    const allServices = global.drupalSettings.services;
    if (props.service in allServices) {
      console.log(allServices[props.service][1]);
      var uuidService = allServices[props.service][1];
    }

    // UUID des Vorgängerrelease.
    // @todo prevReleases aus Manager ziehen
    // const allPreviousReleases = global.drupalSettings.prevReleases;
    // if (props.previousRelease in allPreviousReleases[props.service]) {
    //   var uuidPrevRelease = allPreviousReleases[props.service][props.previousRelease][0];
    // }

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
      let field_prev_release = {
        "data": {
          "type": "node--release",
          "id": uuidPrevRelease,
        },
      }
      postdata["data"]["relationships"] = { ...postdata["data"]["relationships"], field_prev_release };
    }

    console.log(postdata);
    const csrfUrl = `/session/token?_format=json`;
    let fetchUrl = "/jsonapi/node/deployed_releases";
    let fetchOptions = {
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
        props.setCount(props.count + 1);
        if ("errors" in antwort) {
          props.setError(<li>Die Einsatzmeldung konnte nicht erstellt werden.</li>);
        }
        else {
          props.setDeploymentHistory(prev => [...prev, release]);
        }
      })
      .catch(error => {
        console.log('fehler:', error);
        props.setError(<li>Die Einsatzmeldung konnte nicht erstellt werden.</li>);
      });
    
    if (props.prevDeploymentId && archivePrevRelease === true) {
      let archiveBody = {
        "data": {
          "type": "node--deployed_releases",
          "id": props.prevDeploymentId,
          "attributes": {
            "field_deployment_status": "2"
          }
        }
      }
      fetchUrl = '/jsonapi/node/deployed_releases/' + props.prevDeploymentId;
      fetchOptions = {
        method: 'PATCH',
        headers: new Headers({
          'Accept': 'application/vnd.api+json',
          'Content-Type': 'application/vnd.api+json',
          'Cache': 'no-cache',
        }),
        body: JSON.stringify(archiveBody),
      }
      fetchWithCSRFToken(csrfUrl, fetchUrl, fetchOptions)
        .then(antwort => antwort.json())
        .then(antwort => {
          console.log(antwort);
          props.setCount(props.count + 1);
          if ("errors" in antwort) {
            props.setError(<li>Das Vorgängerrelease konnte nicht archiviert werden.</li>);
          }
        })
        .catch(error => {
          console.log('fehler:', error);
          props.setError(<li>Das Vorgängerrelease konnte nicht archiviert werden.</li>);
        });
    }

    // Nach Absendung des Formulars alles zurücksetzen.
    props.setEnvironment(1);
    props.setService(0);
    setRelease(false);
    props.setPreviousRelease(false);
    setDate(false);
    setInstallationTime(false);
    setIsArchived(false);
    setIsAutomated(false);
    setAbnormalities(false);
    setDescription("");
    setArchivePrevRelease(false);
  }
  
  // Handler für Button "Neue Einsatzmeldung".
  const handleClick = () => {
    // Für neue Einsatzmeldung alles zurücksetzen.
    props.setEnvironment(1);
    props.setService(0);
    setRelease(false);
    props.setPreviousRelease(false);
    setDate(false);
    setInstallationTime(false);
    setIsArchived(false);
    setIsAutomated(false);
    setAbnormalities(false);
    setDescription("");
    setArchivePrevRelease(false);
    // Formular anzeigen.
    props.setShow(!props.show);
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

  // Tooltip für Button "Neue Einsatzmeldung".
  const ttNewReport = (
    <Tooltip id="ttNewReport">
      Einen neuen Ersteinsatz melden.<br/>
      <strong>Hinweis: </strong> Möchten Sie ein Nachfolgerelease melden? Dies können Sie tun, indem sie in der untenstehenden Tabelle auf  <span className="glyphicon glyphicon-forward" /> klicken.
    </Tooltip>
  );

  return (
    <div>
      <OverlayTrigger placement="top" overlay={ttNewReport}>
        <Button bsStyle="primary" bsSize="large" onClick={handleClick}>
          <span className="glyphicon glyphicon-plus" /> Ersteinsatz melden
        </Button>
      </OverlayTrigger>
      <Modal show={props.show} onHide={handleClick}>
        <Modal.Header closeButton>
          <Modal.Title>Einsatzmeldung</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <Form>
            <FormGroup controlId="1">
              <ControlLabel bsClass="control-label js-form-required form-required">Umgebung</ControlLabel>
              <div className="select-wrapper">
                <FormControl
                  componentClass="select"
                  name="umgebung"
                  value={props.environment}
                  onChange={(e) => props.setEnvironment(e.target.value)}
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
                  name="verfahren"
                  value={props.service}
                  onChange={(e) => props.setService(e.target.value)}
                >
                  {optionsServices}
                </FormControl>
              </div>
            </FormGroup>

            <SelectRelease
              release={release}
              setRelease={setRelease}
              newReleases={newReleases}
              isLoading={props.isLoading}
              setIsLoading={props.setIsLoading}
              disabled={props.disabled}
              setDisabled={props.setDisabled}
            />

            <SelectPreviousRelease
              previousRelease={props.previousRelease}
              setPreviousRelease={props.setPreviousRelease}
              prevReleases={prevReleases}
              isLoading={props.isLoading}
              setIsLoading={props.setIsLoading}
              disabled={props.disabled}
              setDisabled={props.setDisabled}
            />

            <FormGroup controlId="5">
              <Checkbox
                name="archivieren"
                type="checkbox"
                checked={archivePrevRelease}
                onChange={(e) => setArchivePrevRelease(e.target.checked)}
              >
                Vorgängerrelease archivieren
              </Checkbox>
            </FormGroup>

            <FormGroup controlId="6">
              <ControlLabel bsClass="control-label js-form-required form-required">Datum</ControlLabel>
              <FormControl
                type="date"
                name="datum"
                value={date}
                onChange={(e) => setDate(e.target.value)}
              >
              </FormControl>
            </FormGroup>

            <FormGroup controlId="7">
              <ControlLabel bsClass="control-label js-form-required form-required">Installationsdauer</ControlLabel>
              <FormControl
                componentClass="input"
                type="number"
                step="1"
                min="1"
                name="installationsdauer"
                value={installationTime}
                onChange={(e) => setInstallationTime(e.target.value)}
                placeholder="in Minuten"
              >
              </FormControl>
            </FormGroup>

            <FormGroup controlId="8">
              <Checkbox
                name="archiviert"
                type="checkbox"
                checked={isArchived}
                onChange={(e) => setIsArchived(e.target.checked)}
              >
                Archiviert
              </Checkbox>
            </FormGroup>

            <FormGroup controlId="9">
              <Checkbox
                name="automatisiert"
                type="checkbox"
                checked={isAutomated}
                onChange={(e) => setIsAutomated(e.target.checked)}
              >
                Automatisiertes Deployment
              </Checkbox>
            </FormGroup>

            <FormGroup controlId="10">
              <Checkbox
                name="auffaelligkeiten"
                type="checkbox"
                checked={abnormalities}
                onChange={(e) => setAbnormalities(e.target.checked)}
              >
                Auffälligkeiten
              </Checkbox>
            </FormGroup>

            <FormGroup controlId="11">
              <ControlLabel>Beschreibung der Auffälligkeiten</ControlLabel>
              <FormControl
                componentClass="textarea"
                name="beschreibung"
                value={description}
                onChange={(e) => setDescription(e.target.value)}
              >
              </FormControl>
            </FormGroup>
          </Form>
        </Modal.Body>
        <Modal.Footer>
          <Button bsStyle="success" onClick={handleSave} >Speichern</Button>
          <Button onClick={() => props.setShow(!props.show)}>Schließen</Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
}
