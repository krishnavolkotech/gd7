import React, {useState, useEffect, useRef} from 'react';
import DeploymentForm from './DeploymentForm';
import ArchiveDeploymentForm from './ArchiveDeploymentForm';
import FailedDeploymentForm from './FailedDeploymentForm';
import { Button } from 'react-bootstrap';
import { fetchWithCSRFToken } from "../../utils/fetch";


export default function FormManager(props) {
  /** @const {number} fetchCountForm - Ensures that the latest fetch gets processed. */
  const fetchCountForm = useRef(0);

  const [showDeploymentForm, setShowDeploymentForm] = useState(false);

  // Formular zum Archivieren von Einsatzmeldungen anzeigen.
  const [showArchiveForm, setShowArchiveForm] = useState(false);

  // Formular zum Markieren einer Fehlmeldung.
  const [showFailedForm, setShowFailedForm] = useState(false);

  // Loading Spinner für Neues Release und Vorgängerrelease.
  const [isLoading, setIsLoading] = useState(false);

  // disabled state for Relase Selectors.
  const [disabled, setDisabled] = useState(true);

  // Infotext während Releaseauswahl befüllt wird.
  const [loadingMessage, setLoadingMessage] = useState(<p />);

  // Für SelectRelease Komponente. Die nicht-eingesetzten Releases.
  const [newReleases, setNewReleases] = useState([]);

  // Für SelectPreviousRelease Komponente. Die eingesetzten Releases.
  /**
   * @const {Object[]} prevReleases - Array der möglichen Vorgängerreleases.
   * @property {string} prevReleases[].nid - Die Release NID.
   * @property {string} prevReleases[].service - Die NID des Verfahrens.
   * @property {string} prevReleases[].title - Der Titel des Releases.
   * @property {string} prevReleases[].uuidRelease - Die UUID des Releases.
   * @property {string} prevReleases[].uuidDeployment - Die UUID der Einsatzmeldung.
   */
  const [prevReleases, setPrevReleases] = useState([]);

  const [submitMessage, setSubmitMessage] = useState(false);

  // The form action: "successor", "archive", "first-deployment", "edit-deployment". ?? wip
  /** @const {string|bool} formAction - The form action. */
  const [formAction, setFormAction] = useState(false);

  /**
   * @const {Object|bool} prevDeploymentData - Array der Einsatzmeldungsobjekte.
   * @property {string} prevDeploymentData.uuid - Die UUID der Einsatzmeldung.
   * @property {string} prevDeploymentData.releaseName - Der Name des eingesetzten Release.
   */
  const [prevDeploymentData, setPrevDeploymentData] = useState(false);

  /**
   * @const {Object[]} deploymentData - Array der Einsatzmeldungsobjekte.
   * @property {string} deploymentData[].date - Das Einsatzdatum.
   * @property {string} deploymentData[].environment - Die Einsatzumgebung.
   * @property {string} deploymentData[].nid - Die Node ID der Einsatzmeldung.
   * @property {string} deploymentData[].release - Der Release Name.
   * @property {string} deploymentData[].releaseNid - Die Node ID des Release.
   * @property {string} deploymentData[].service - Der Verfahrensname.
   * @property {string} deploymentData[].serviceNid - Die Node ID des Verfahrens.
   * @property {string} deploymentData[].state - Die Landes ID des Einsatzes.
   * @property {string} deploymentData[].title - Der Titel der Einsatzmeldung.
   * @property {string} deploymentData[].uuid - Die UUID der Einsatzmeldung.
   * @property {string} deploymentData[].status - Der Status der Einsatzmeldung.
   */
  const [deploymentData, setDeploymentData] = useState([]);

  const initialFormState = {
    "uuid": "",
    "status": "1",
    "state": props.state,
    "environment": "0",
    "service": "0",
    "date": "",
    "installationTime": "",
    "isAutomated": false,
    "abnormalities": false,
    "description": "",
    "releaseNid": "0",
    "previousRelease": "0",
    "archivePrevRelease": "null",
    "archiveThis": false,
    "firstDeployment": true,
    "product": "",
    "action": "first",
    "pCount": 1,
    "previousReleases": [],
  };

  /**
   * The form state object.
   * @property {Object} formState - The form state object.
   * @property {string} formState.uuid - The uuid of the deployment (if editing existing deployment).
   * @property {string} formState.status - The deployment status number (1, 2, 3).
   * @property {string} formState.state - The state of the deployment.
   * @property {string} formState.environment - The environment of the deployment.
   * @property {string} formState.service - The service of the deployment.
   * @property {string} formState.date - The deployment date.
   * @property {string} formState.installationTime - The deployment installation time.
   * @property {bool}   formState.isAutomated - The employee's department.
   * @property {bool}   formState.abnormalities - Does the deployment have abnormalities?
   * @property {string} formState.description - The abnormality description.
   * @property {string} formState.releaseNid - The nid of the deployed release.
   * @property {array}  formState.previousRelease - Array containing nids of previous releases.
   * @property {array}  formState.archivePrevRelease - Array containing booleans to indicate, if the previous releases should be archived.
   * @property {bool}   formState.archiveThis - Should this deployment be archived?
   * @property {bool}   formState.firstDeployment - First deployment of this product?
   * @property {bool}   formState.product - First deployment of this product?
   * @property {bool}   formState.action - First deployment of this product?
   * @property {number}   formState.pCount - Deep Scan workaround for previousReleases (detects changes).
   * @property {array}   formState.previousReleases - Array of selected previous releases.
   * @property {string}   formState.previousReleases[].uuidRelease - Uuid of previous release.
   * @property {string}   formState.previousReleases[].uuidDeployment - Uuid of previous release deployment.
   * @property {bool}   formState.previousReleases[].archive - Bool indicating, if prev release should be archived.
   * @property {string}   formState.previousReleases[].title - The release name.
   * @property {string}   formState.previousReleases[].release - The release nid.
   */
  const [formState, setFormState] = useState(initialFormState);
  // Trigger 1/3 release-selector filtering.
  useEffect(() => {
    if (formState.environment !== "0" && formState.service !== "0") {
      setDisabled(true);
      setIsLoading(true);
      setLoadingMessage(<p>Releases werden geladen ... <span className="glyphicon glyphicon-refresh glyphicon-spin" role="status" /></p>);
      props.fetchReleases(formState.service);
    }
  }, [formState.environment, formState.service])
  
  // Effect 2/3 for release selector filtering.
  useEffect(() => {
    loadDeploymentData(formState);
  }, [props.triggerReleaseSelectCount])
  
  // Effect 3/3 for release selector filtering.
  useEffect(() => {
    // setTriggerReleaseSelect(true);
    populateReleaseSelectors();
  }, [deploymentData])

  useEffect(() => {
    setSubmitMessage([]);
    if (props.triggerAction.action == "successor") {
      setFormState(prev => ({
        ...prev,
        "state": props.triggerAction.args.state,
        "environment": props.triggerAction.args.environment,
        "service": props.triggerAction.args.service,
        "previousReleases": [
          {
            "uuidRelease": "",
            "uuidDeployment": "",
            "archive": "",
            "title": props.triggerAction.args.releaseName,
            "release": props.triggerAction.args.release,
          },
        ],
        "product": props.triggerAction.args.product,
        "action": props.triggerAction.action,
        "firstDeployment": false,
      }));
      setPrevDeploymentData(prev => ({...prev, "uuid": props.triggerAction.args.uuid}));
      // setFormAction(props.triggerAction.action); // ??? Noch nicht verwendet
      setShowDeploymentForm(true);
      props.setTriggerAction(false);
    }
    if (props.triggerAction.action == "archive") {
      setPrevDeploymentData(props.triggerAction.args);
      setShowArchiveForm(true);
      props.setTriggerAction(false);
    }
    if (props.triggerAction.action == "failed") {
      setPrevDeploymentData(props.triggerAction.args);
      setShowFailedForm(true);
      props.setTriggerAction(false);
    }
    if (props.triggerAction.action == "edit") {
      setFormState(prev => ({
        ...prev,
        "uuidDeployment": props.triggerAction.uuidDeployment,
        "action": props.triggerAction.action,
      }));
      setShowDeploymentForm(true);
      fetchDeployment(props.triggerAction.args.uuidDeployment);
      setSubmitMessage([<FormSkeleton />]);
      // setSubmitMessage(<li>Daten werden geladen ... <span className="glyphicon glyphicon-refresh glyphicon-spin" role="status" /></li>);
    }
  }, [props.triggerAction])

  const fetchDeployment = (uuid) => {
    const url = '/jsonapi/node/deployed_releases/'
      + uuid
      +'?include=field_deployed_release,field_prev_release,field_service&fields[node--release]=id,drupal_internal__nid,title&fields[node--services]=drupal_internal__nid';
    const headers = new Headers({
      Accept: 'application/vnd.api+json',
    });
    fetch(url, { headers })
      .then(response => response.json())
      .then(results => {
        setSubmitMessage([]);
        // Service.
        const service = results.included.find(element => {
          return element.id == results.data.relationships.field_service.data.id;
        });
        // Release.
        const release = results.included.find(element => {
          return element.id == results.data.relationships.field_deployed_release.data.id;
        });
        // Previous Releases.
        let previousReleases = [];
        if (results.data.relationships.field_prev_release.data.length > 0) {
          const includedPrevReleases = results.included.filter(element => {
            return element.id == results.data.relationships.field_prev_release.data[0].id;
          });
          for (const val of includedPrevReleases) {
            previousReleases.push({
              "uuidRelease": "",
              "uuidDeployment": "",
              "title": val.attributes.title,
              "archive": "",
              "release": val.attributes.drupal_internal__nid,
            });
          }
        }

        const abnormalityDescription = results.data.attributes.field_abnormality_description ? results.data.attributes.field_abnormality_description.value : "";

        const installationTime = results.data.attributes.field_installation_time !== null ? results.data.attributes.field_installation_time : "";

        setFormState(prev => ({
          ...prev,
          "uuid": results.data.id,
          "status": results.data.attributes.field_deployment_status,
          "state": results.data.attributes.field_state_list,
          "environment": results.data.attributes.field_environment,
          "service": service.attributes.drupal_internal__nid,
          "date": results.data.attributes.field_date_deployed,
          "installationTime": installationTime,
          "isAutomated": results.data.attributes.field_automated_deployment_bool,
          "abnormalities": results.data.attributes.field_abnormalities_bool,
          "description": abnormalityDescription,
          "releaseNid": release.attributes.drupal_internal__nid,
          "previousReleases": previousReleases,
          "archivePrevRelease": "",
          "archiveThis": false,
          "firstDeployment": results.data.attributes.field_first_deployment,
        }));
      })
      .catch(error => console.log("error", error));
  }

  /**
   * Loads deployment data asnychronous to filter selection.
   * 
   * Should speed up the filtering of selectable releases for reporting new 
   * deployments, especially for follow up deployments.
   */
  const loadDeploymentData = (params) => {
    // setLoadingReleasesSpinner(true);
    // JsonAPI Fetch vorbereiten.
    // Fehlmeldungen sollen rausgefiltert werden.
    let url = '/api/v1/deployments';
    url += '?status[]=1&status[]=2';

    // Landes-Filter (nur für Gruppen- und Site-Admins)
    if (params.state && params.state !== "1") {
      url += '&states=' + params.state;
    }

    // Umgebung.
    if (params.environment !== "0") {
      url += '&environment=' + params.environment;
    }

    // Verfahren.
    if (params.service !== "0") {
      url += '&service=' + params.service;
    }

    if (params.status === "1" || !(status in params)) {
      url += '&items_per_page=All';
    }

    fetchCountForm.current++;
    const runner = fetchCountForm.current;

    const headers = new Headers({
      Accept: 'application/vnd.api+json',
    });
    setLoadingMessage(<p>Lade Einsatzmeldungen um Releases zu filtern ... <span className="glyphicon glyphicon-refresh glyphicon-spin" role="status" /></p>);
    fetch(url, { headers })
      .then(response => response.json())
      .then(results => {
        if (runner === fetchCountForm.current) {
          // setLoadingReleasesSpinner(false);
          setDeploymentData(results);
        }
      })
      .catch(error => console.log("error", error));
  }

  /**
   * Prepares the selectable releases (Release and Previous Release).
   */
  const populateReleaseSelectors = () => {
    // if (triggerReleaseSelect === false) {
    //   return;
    // }
    // // Trigger zurücksetzen.
    // setTriggerReleaseSelect(false);
    let filteredDeployments = deploymentData.filter(deployment => {
      let result = true;
      if (deployment.state != formState.state) {
        result = false;
      }
      if (deployment.environment != formState.environment) {
        result = false;
      }
      if (deployment.serviceNid != formState.service) {
        result = false;
      }
      return result;
    });

    let releasesWithDeployments = {};
    filteredDeployments.map((deployment) => {
      releasesWithDeployments[deployment.releaseNid] = deployment.uuid;
    });

    if (formState.service in props.releases) {
      // All provided releases for the selected service.
      var releaseArray = props.releases[formState.service];
    }

    // Releases filtern: Eingesetzt (Vorgängerreleases).
    let filteredPrevReleases = { ...releaseArray };
    // Filter nur anwenden, wenn uuid leer ist. Dann ist es eine neue Einsatzmeldung.
    if (formState.service === "27111") {
      let test = props.releases[formState.service]["73354"];
    }
    if (formState.uuid === "") {
      for (const nid in releaseArray) {
        if (nid in releasesWithDeployments) {
          filteredPrevReleases[nid]["uuidDeployment"] = releasesWithDeployments[nid];
        }
        else {
          delete filteredPrevReleases[nid];
        }
        // if (releaseArray[nid].title.indexOf(formState.product)) {
        //   delete filteredPrevReleases[nid];
        // }
      }
    }

    let deployedReleases = [];
    let product = false;
    for (const release in filteredPrevReleases) {
      deployedReleases.push(filteredPrevReleases[release]);
      // if (filteredPrevReleases[release].nid.toString() == formState.previousRelease) {
      //   const title = filteredPrevReleases[release].title;
      //   product = title.substring(0, title.indexOf('_') + 1);
      // }
    }
    deployedReleases.sort(function (a, b) {
      return compareReleases(a, b);
    });
    // Releases filtern: Nicht Eingesetzt (Neue Einsatzmeldung).
    let filteredNewReleases = { ...releaseArray };
    // Filter nur anwenden, wenn uuid leer ist. Dann ist es eine neue Einsatzmeldung.
    if (formState.uuid === "") {
      for (const nid in releaseArray) {
        if (nid in releasesWithDeployments) {
          delete filteredNewReleases[nid];
        }
        // if (releaseArray[nid].title.indexOf(formState.product)) {
        //   delete filteredNewReleases[nid];
        // }
      }
    }


    let undeployedReleases = [];
    for (const release in filteredNewReleases) {
      undeployedReleases.push(filteredNewReleases[release]);
    }
    // undeployedReleases.sort((a, b) => b - a);
    undeployedReleases.sort(function (a, b) {
      return compareReleases(a, b);
    });

    if (undeployedReleases.length === 0) {
      setLoadingMessage(<p>Es stehen keine Releases zur Auswahl zur Verfügung.</p>);
    }
    else {
      setLoadingMessage(<p>Releaseauswahl bereit.</p>);
    }
    setNewReleases(undeployedReleases);
    setPrevReleases(deployedReleases);
    setDisabled(false);
    setIsLoading(false);
  }

  /**
   * Compares two release names for sorting.
   * 
   * Sorting criteria hierarchy:
   *  1. Matching product on top.
   *  2. Descending product name.
   *  3. Ascending version number.
   * 
   * @param {object} a - The first release object.
   * @param {object} b - The second release object.
   * @returns {number} - 1, -1 or 0.
   */
  const compareReleases = (a, b) => {
    const productA = a.title.substring(0, a.title.indexOf('_') + 1);
    const productB = b.title.substring(0, b.title.indexOf('_') + 1);
    const versionA = a.title.substring(a.title.indexOf('_') + 1);
    const versionB = b.title.substring(b.title.indexOf('_') + 1);
    // Matching product should be on top of the list.
    if (productA == formState.product && productA != productB) {
      return -1;
    }
    if (productB == formState.product && productA != productB) {
      return 1;
    }
    // Product should be alphabetically.
    if (productA < productB) {
      return -1;
    }
    if (productA > productB) {
      return 1;
    }
    // Version should be descending.
    const partsA = versionA.split('.')
    const partsB = versionB.split('.')
    for (var i = 0; i < partsB.length; i++) {
      const vA = ~~partsA[i] // parse int
      const vB = ~~partsB[i] // parse int
      if (vA > vB) return -1
      if (vA < vB) return 1
    }
    if (versionA < versionB) {
      return 1;
    }
    if (versionA > versionB) {
      return -1;
    }
    return 0;
  }

  const handleFirstDeployment = () => {
    setSubmitMessage([]);
    setFormState(initialFormState);
    setShowDeploymentForm(true);
  }

  const handleClose = () => {
    setFormState(initialFormState);
    setNewReleases([]);
    setPrevReleases([]);
    setShowDeploymentForm(false);
  }

  function handleSave() {
    postDeployment();
  }

  // Returns time in minutes from string in the following format: 0:01 - 999:59.
  const calculateInstallationTime = (text) => {
    const pieces = text.split(":");
    const hours = parseInt(pieces[0]);
    const minutes = parseInt(pieces[1]);
    const time = hours * 60 + minutes;
    return time;
  }

  const postDeployment = () => {
    // UUID des gemeldeten Release.
    // @todo releases aus Manager beziehen
    const allReleases = props.releases;
    let currentRelease = false;
    if (formState.releaseNid in allReleases[formState.service]) {
      currentRelease = allReleases[formState.service][formState.releaseNid];
    }
    // let currentRelease = allReleases[formState.service].filter(element => {
    //   return release === element.nid;
    // })

    if (!currentRelease) {
      // props.setError(<li>Die Einsatzmeldung konnte nicht erstellt werden, weil die zugehörige UUID nicht ermittelt werden konnte.</li>);
      setSubmitMessage([<li>Die Einsatzmeldung konnte nicht erstellt werden, weil die zugehörige UUID nicht ermittelt werden konnte.</li>]);
      return;
    }
    const uuidRelease = currentRelease.uuidRelease;
    const releaseName = currentRelease.title;
    setSubmitMessage([<p>Einsatzmeldung für {releaseName} wird gespeichert ... <span className="glyphicon glyphicon-refresh glyphicon-spin" role="status" /></p>]);
    // const product = releaseName.substring(0, releaseName.indexOf('_'));

    // UUID des Verfahrens.
    const allServices = global.drupalSettings.services;
    if (formState.service in allServices) {
      var uuidService = allServices[formState.service][1];
    }
    const deploymentTitle = formState.state + "_" + formState.environment + "_" + formState.service + "_" + releaseName;

    const installationTime = calculateInstallationTime(formState.installationTime);

    var postData = {
      "data": {
        "type": "node--deployed_releases",
        "attributes": {
          "title": deploymentTitle,
          "field_deployment_status": formState.status,
          "field_first_deployment": formState.firstDeployment,
          "field_abnormalities_bool": formState.abnormalities,
          "field_automated_deployment_bool": formState.isAutomated,
          "field_abnormality_description": {
            "value": formState.description,
            "format": "plain_text",
          },
          "field_date_deployed": formState.date,
          "field_installation_time": installationTime,
          "field_state_list": formState.state,
          "field_environment": formState.environment,
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
    // @todo umbauen auf previousReleases Array.
    if (formState.previousReleases.length > 0) {
      // UUID des Vorgängerrelease.
      const field_prev_release = formState.previousReleases.map(pr => {
        if (pr.uuidRelease.length <= 1) {
          props.setError(<li>Die Einsatzmeldung konnte nicht gespeichert werden, weil die zugehörige UUID nicht ermittelt werden konnte.</li>);
          setSubmitMessage([<li>Die Einsatzmeldung konnte nicht gespeichert werden, weil die UUID eines Vorgängerreleases nicht ermittelt werden konnte.</li>]);
          return;
        }
        return {
          "type": "node--release",
          "id": pr.uuidRelease,
        };
      });
      postData["data"]["relationships"] = { ...postData["data"]["relationships"], "field_prev_release": { "data": field_prev_release} };
    }
    
    let fetchUrl = "/jsonapi/node/deployed_releases";
    let method = "POST";

    if (formState.action == "edit") {
      method = "PATCH";
      fetchUrl += '/' + formState.uuid;
      postData["data"]["id"] = formState.uuid;
    }

    const csrfUrl = `/session/token?_format=json`;
    let fetchOptions = {
      "method": method,
      "headers": new Headers({
        'Accept': 'application/vnd.api+json',
        'Content-Type': 'application/vnd.api+json',
        'Cache': 'no-cache',
      }),
      "body": JSON.stringify(postData),
    }

    fetchWithCSRFToken(csrfUrl, fetchUrl, fetchOptions)
      .then(antwort => antwort.json())
      .then(antwort => {
        if ("errors" in antwort) {
          props.setError(<li>Die Einsatzmeldung konnte nicht gespeichert werden. <span className="glyphicon glyphicon-exclamation-sign" role="status" /></li>);
          setSubmitMessage([<li>Die Einsatzmeldung konnte nicht gespeichert werden. <span className="glyphicon glyphicon-exclamation-sign" role="status" /></li>]);
          props.setCount(props.count + 1);
        }
        else {
          props.setDeploymentHistory(prev => [...prev, parseInt(antwort.data.attributes.drupal_internal__nid)]);
          setSubmitMessage([<li>Einsatzmeldung gespeichert. <span className="glyphicon glyphicon-ok-circle" role="status" /></li>]);
          if (formState.action == "successor") {
            patchDeployment();
          }
          // No further action needed, reset everything.
          else {
            handleClose();
            setPrevDeploymentData(false);
            props.setCount(props.count + 1);
          }
        }
      })
      .catch(error => {
        console.log('fehler:', error);
        props.setError(<li>Die Einsatzmeldung konnte nicht erstellt werden. <span className="glyphicon glyphicon-exclamation-sign" role="status" /></li>);
        setSubmitMessage([<li>Die Einsatzmeldung konnte nicht erstellt werden. <span className="glyphicon glyphicon-exclamation-sign" role="status" /></li>]);
      });
  }

  // PATCH: Vorgängerrelease archivieren.
  const patchDeployment = () => {
    let csrfUrl = `/session/token?_format=json`;
    let fetchUrl = '';
    let fetchOptions = {};
    formState.previousReleases.forEach((p, index) => {
      let archiveBody = {
        "data": {
          "type": "node--deployed_releases",
          "id": p.uuidDeployment,
          "relationships": {
            "field_successor_release": {
              "data": {
                "type": "node--release",
                "id": p.uuidRelease,
              }
            }
          }
        }
      }
      if (p.archive) {
        const attributes = {"attributes": {"field_deployment_status": "2"}};
        archiveBody["data"] = { ...archiveBody["data"], ...attributes };
      }
      fetchUrl = '/jsonapi/node/deployed_releases/' + p.uuidDeployment;
      fetchOptions = {
        method: 'PATCH',
        headers: new Headers({
          'Accept': 'application/vnd.api+json',
          'Content-Type': 'application/vnd.api+json',
          'Cache': 'no-cache',
        }),
        body: JSON.stringify(archiveBody),
      }
      // @todo abbauen.
      setPrevDeploymentData(false);
      setSubmitMessage(prev => {
        let messages = prev;
        messages[index] = <p>{p.title} wird aktualisiert ... <span className="glyphicon glyphicon-refresh glyphicon-spin" role="status" /></p>;
        return messages;
      });
      fetchWithCSRFToken(csrfUrl, fetchUrl, fetchOptions)
        .then(antwort => antwort.json())
        .then(antwort => {
          props.setCount(props.count + 1);
          if ("errors" in antwort) {
            setSubmitMessage(prev => {
              let messages = prev;
              messages[index] = <p>{p.title} konnte nicht aktualisiert werden. <span className="glyphicon glyphicon-exclamation-sign" role="status" /></p>;
              return messages;
            });
            props.setError(<li>Fehler beim Aktualisieren des Vorgängerrelease.</li>);
          }
          else {
            props.setDeploymentHistory(prev => [...prev, parseInt(antwort.data.attributes.drupal_internal__nid)]);
            setSubmitMessage(prev => {
              let messages = prev;
              messages[index] = <p>{p.title} wurde aktualisiert. <span className="glyphicon glyphicon-ok-circle" role="status" /></p>;
              return messages;
            });
          }
        })
        .catch(error => {
          console.log('fehler:', error);
          setSubmitMessage(prev => {
            let messages = prev;
            messages[index] = <p>{p.title} konnte nicht aktualisiert werden. <span className="glyphicon glyphicon-exclamation-sign" role="status" /></p>;
            return messages;
          });
          props.setError(<li>Das Vorgängerrelease konnte nicht archiviert werden.</li>);
        });
    });

  }

  return (
    <div>
      { props.status == "1" &&
        <div>
          <p />
          <Button bsStyle="primary" bsSize="large" onClick={handleFirstDeployment}>
            <span className="glyphicon glyphicon-plus" /> Ersteinsatz melden
          </Button>
        </div>
      }
      <DeploymentForm
        count={props.count}
        setCount={props.setCount}
        formState={formState}
        setFormState={setFormState}
        releases={props.releases}
        isLoading={isLoading}
        setIsLoading={setIsLoading}
        disabled={disabled}
        setDisabled={setDisabled}
        setDeploymentHistory={props.setDeploymentHistory}
        loadingMessage={loadingMessage}
        setLoadingMessage={setLoadingMessage}
        newReleases={newReleases}
        prevReleases={prevReleases}
        handleFirstDeployment={handleFirstDeployment}
        showDeploymentForm={showDeploymentForm}
        setShowDeploymentForm={setShowDeploymentForm}
        handleClose={handleClose}
        handleSave={handleSave}
        submitMessage={submitMessage}
        setSubmitMessage={setSubmitMessage}
      />
      <ArchiveDeploymentForm
        showArchiveForm={showArchiveForm}
        setShowArchiveForm={setShowArchiveForm}
        prevDeploymentData={prevDeploymentData}
        setPrevDeploymentData={setPrevDeploymentData}
        setDeploymentHistory={props.setDeploymentHistory}
        count={props.count}
        setCount={props.setCount}
      />
      <FailedDeploymentForm
        showFailedForm={showFailedForm}
        setShowFailedForm={setShowFailedForm}
        prevDeploymentData={prevDeploymentData}
        setPrevDeploymentData={setPrevDeploymentData}
        count={props.count}
        setCount={props.setCount}
      />
    </div>
  )
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

      <div className="panel panel-default">
        <div className="panel-body">
          <div className="skeleton-label loading"></div>
          <div className="skeleton-select loading"></div>
          <div className="skeleton-textbody loading"></div>
          <div className="skeleton-textbody loading"></div>
        </div>
      </div>
    </div>
  );
}
