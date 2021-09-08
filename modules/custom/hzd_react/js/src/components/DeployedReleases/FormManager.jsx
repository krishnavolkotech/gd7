import React, {useState, useEffect, useRef} from 'react';
import DeploymentForm from './DeploymentForm';
import ArchiveForm from './ArchiveForm';
import { Button } from 'react-bootstrap';
import { fetchWithCSRFToken } from "../../utils/fetch";


export default function FormManager(props) {
  /** @const {number} fetchCountForm - Ensures that the latest fetch gets processed. */
  const fetchCountForm = useRef(0);

  const [showDeploymentForm, setShowDeploymentForm] = useState(false);

  // Formular zum Archivieren von Einsatzmeldungen anzeigen.
  const [showArchiveForm, setShowArchiveForm] = useState(false);

  // Loading Spinner für Neues Release und Vorgängerrelease.
  const [isLoading, setIsLoading] = useState(false);

  // disabled state for Relase Selectors.
  const [disabled, setDisabled] = useState(true);

  const [triggerReleaseSelect, setTriggerReleaseSelect] = useState(false);

  // Infotext während Releaseauswahl befüllt wird.
  const [loadingMessage, setLoadingMessage] = useState("");

  const [showEditForm, setShowEditForm] = useState(false);

  // Für SelectRelease Komponente. Die nicht-eingesetzten Releases.
  const [newReleases, setNewReleases] = useState([]);

  // Für SelectPreviousRelease Komponente. Die eingesetzten Releases.
  const [prevReleases, setPrevReleases] = useState([]);

  /** @const {bool} triggerForm - Triggers deployment form. */
  const [triggerForm, setTriggerForm] = useState(false);

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
  const [deploymentData, setDeploymentData] = useState([false, false, false]);

  const initialFormState = {
    "uuid": "",
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
  };

  /**
   * The form state object.
   * @property {Object} formState - The form state object.
   * @property {string} formState.uuid - The uuid of the deployment (if editing existing deployment).
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
   */
  const [formState, setFormState] = useState(initialFormState);

  // Trigger 1/3 release-selector filtering.
  useEffect(() => {
    if (formState.environment !== "0" && formState.service !== "0") {
      setDisabled(true);
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
    console.log(props.triggerAction);
    if (props.triggerAction.action == "successor") {
      setFormState(prev => ({
        ...prev,
        "state": props.triggerAction.args.userState,
        "environment": props.triggerAction.args.environment,
        "service": props.triggerAction.args.service,
        "previousRelease": props.triggerAction.args.release,
        "product": props.triggerAction.args.product,
      }));
      setPrevDeploymentData(prev => ({...prev, "uuid": props.triggerAction.args.deploymentId}));
      setFormAction(props.triggerAction.action); // ??? Noch nicht verwendet
      setShowDeploymentForm(true);
      props.setTriggerAction(false);
    }
    if (props.triggerAction.action == "archive") {
      setPrevDeploymentData({ "uuid": props.triggerAction.args.uuid, "releaseName": props.triggerAction.args.releaseName});
      setShowArchiveForm(true);
      props.setTriggerAction(false);
    }
  }, [props.triggerAction])


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
    console.log("Einsatzmeldungen laden: " + url);
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

    let deployedReleaseNids = filteredDeployments.map((deployment) => {
      return deployment.releaseNid;
    });

    if (formState.service in props.releases) {
      // All provided releases for the selected service.
      var releaseArray = props.releases[formState.service];
    }

    // Releases filtern: Eingesetzt (Vorgängerreleases).
    // let filteredPrevReleases = releaseArray.filter(release => {
    //   return deployedReleaseNids.indexOf(release.nid) >= 0;
    // })

    // Releases filtern: Eingesetzt (Vorgängerreleases).
    let filteredPrevReleases = { ...releaseArray };
    for (const nid in releaseArray) {
      if (deployedReleaseNids.indexOf(nid) === -1) {
        delete filteredPrevReleases[nid];
      }
    }
    let deployedReleases = [];
    let product = false;
    for (const release in filteredPrevReleases) {
      deployedReleases.push(filteredPrevReleases[release]);
      // console.log(filteredPrevReleases[release].nid.toString(), props.previousRelease);
      if (filteredPrevReleases[release].nid.toString() == formState.previousRelease) {
        const title = filteredPrevReleases[release].title;
        product = title.substring(0, title.indexOf('_') + 1);
      }
    }
    // deployedReleases.sort((a, b) => b - a);
    deployedReleases.sort(function (a, b) {
      var releaseA = a.title.toUpperCase();
      var releaseB = b.title.toUpperCase();
      if (releaseA < releaseB) {
        return -1;
      }
      if (releaseA > releaseB) {
        return 1;
      }
      return 0;
    });

    // Releases filtern: Nicht Eingesetzt (Neue Einsatzmeldung).
    // let filteredNewReleases = releaseArray.filter(release => {
    //   let result = false;
    //   if (deployedReleaseNids.indexOf(release.nid) === -1) {
    //     result = true;
    //   }
    //   if (product && release.title.indexOf(product) == -1) {
    //     result = false;
    //   }
    //   return result;
    // });
    let filteredNewReleases = { ...releaseArray };
    for (const nid in releaseArray) {
      if (deployedReleaseNids.indexOf(nid) >= 0) {
        delete filteredNewReleases[nid];
      }
      if (releaseArray[nid].title.indexOf(formState.product)){
        delete filteredNewReleases[nid];
      }
    }


    let undeployedReleases = [];
    for (const release in filteredNewReleases) {
      undeployedReleases.push(filteredNewReleases[release]);
    }
    // undeployedReleases.sort((a, b) => b - a);
    undeployedReleases.sort(function (a, b) {
      var releaseA = a.title.toUpperCase();
      var releaseB = b.title.toUpperCase();
      if (releaseA < releaseB) {
        return -1;
      }
      if (releaseA > releaseB) {
        return 1;
      }
      return 0;
    });

    console.log("Eingesetzte Releases wurden geholt und Releaseoptionen gefiltert.");
    if (undeployedReleases.length === 0) {
      setLoadingMessage(<p>Es stehen keine Releases zur Auswahl zur Verfügung.</p>);
    }
    else {
      setLoadingMessage("");
    }
    setNewReleases(undeployedReleases);
    setPrevReleases(deployedReleases);
    setDisabled(false);
    setIsLoading(false);
  }

  const handleFirstDeployment = () => {
    setFormState(initialFormState);
    setShowDeploymentForm(true);
  }

  // Handler für Button "Ersteinsatz melden".
  const handleClose = () => {
    setFormState(initialFormState);
    setNewReleases([]);
    setPrevReleases([]);
    setShowDeploymentForm(false);
  }

  function handleSave() {
    console.log("##### HANDLE SAVE START #####")
    postDeployment();

    // Nach Absendung des Formulars alles zurücksetzen.
    // setFormState(initialFormState);
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
      setSubmitMessage(<li>Die Einsatzmeldung konnte nicht erstellt werden, weil die zugehörige UUID nicht ermittelt werden konnte.</li>);
      return;
    }
    const uuidRelease = currentRelease.uuid;
    const releaseName = currentRelease.title;
    setSubmitMessage(<p>Einsatzmeldung für {releaseName} wird gespeichert ... <span className="glyphicon glyphicon-refresh glyphicon-spin" role="status" /></p>);
    // const product = releaseName.substring(0, releaseName.indexOf('_'));

    // UUID des Verfahrens.
    const allServices = global.drupalSettings.services;
    if (formState.service in allServices) {
      var uuidService = allServices[formState.service][1];
    }
    const deploymentTitle = formState.state + "_" + formState.environment + "_" + formState.service + "_" + releaseName;

    var postdata = {
      "data": {
        "type": "node--deployed_releases",
        "attributes": {
          "title": deploymentTitle,
          "field_deployment_status": '1',
          "field_first_deployment": formState.firstDeployment,
          "field_abnormalities_bool": formState.abnormalities,
          "field_automated_deployment_bool": formState.isAutomated,
          "field_abnormality_description": formState.description,
          "field_date_deployed": formState.date,
          "field_installation_time": formState.installationTime,
          "field_user_state": formState.state,
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
    if (formState.previousRelease != "0") {
      // UUID des Vorgängerrelease.
      // @todo prevReleases aus Manager ziehen
      // let pRelease = allReleases[formState.service].filter(element => {
      //   return formState.previousRelease === element.nid;
      // })
      let pRelease = false;
      if (formState.previousRelease in allReleases[formState.service]) {
        pRelease = allReleases[formState.service][formState.previousRelease];
      }

      if (!pRelease) {
        // props.setError(<li>Die Einsatzmeldung konnte nicht erstellt werden, weil die zugehörige UUID nicht ermittelt werden konnte.</li>);
        setSubmitMessage(<li>Die Einsatzmeldung konnte nicht erstellt werden, weil die UUID eines Vorgängerreleases nicht ermittelt werden konnte.</li>);
        return;
      }
      const uuidPrevRelease = pRelease.uuid;
      setPrevDeploymentData(prev => ({ ...prev, "uuid": uuidPrevRelease }));
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
        if ("errors" in antwort) {
          // props.setError(<li>Die Einsatzmeldung konnte nicht erstellt werden.</li>);
          setSubmitMessage(<li>Die Einsatzmeldung konnte nicht erstellt werden.</li>);
          props.setCount(props.count + 1);
        }
        else {
          props.setDeploymentHistory(prev => [...prev, formState.releaseNid]);
          setSubmitMessage(<li>Einsatzmeldung gespeichert.</li>);
          // Is the previous deployment supposed to be archived?
          if (prevDeploymentData && formState.archivePrevRelease === true) {
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
        // props.setError(<li>Die Einsatzmeldung konnte nicht erstellt werden.</li>);
        setSubmitMessage(<li>Die Einsatzmeldung konnte nicht erstellt werden.</li>);
      });
  }

  // PATCH: Vorgängerrelease archivieren.
  const patchDeployment = () => {
    const archiveBody = {
      "data": {
        "type": "node--deployed_releases",
        "id": prevDeploymentData.uuid,
        "attributes": {
          "field_deployment_status": "2"
        }
      }
    }
    const csrfUrl = `/session/token?_format=json`;
    const fetchUrl = '/jsonapi/node/deployed_releases/' + prevDeploymentData.uuid;
    const fetchOptions = {
      method: 'PATCH',
      headers: new Headers({
        'Accept': 'application/vnd.api+json',
        'Content-Type': 'application/vnd.api+json',
        'Cache': 'no-cache',
      }),
      body: JSON.stringify(archiveBody),
    }
    setPrevDeploymentData(false);
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
          props.setDeploymentHistory(prev => [...prev, formState.previousRelease]);
          setSubmitMessage(<li>Einsatzmeldung gespeichert.</li>);
          handleClose();
        }
      })
      .catch(error => {
        console.log('fehler:', error);
        setSubmitMessage(<li>Das Vorgängerrelease konnte nicht archiviert werden.</li>);
        props.setError(<li>Das Vorgängerrelease konnte nicht archiviert werden.</li>);
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
      triggerForm={triggerForm}
      setTriggerForm={setTriggerForm}
      releases={props.releases}
      triggerReleaseSelect={triggerReleaseSelect}
      setTriggerReleaseSelect={setTriggerReleaseSelect}
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
    <ArchiveForm
      showArchiveForm={showArchiveForm}
      setShowArchiveForm={setShowArchiveForm}
      prevDeploymentData={prevDeploymentData}
      count={props.count}
      setCount={props.setCount}
    />
    </div>
  )
}
