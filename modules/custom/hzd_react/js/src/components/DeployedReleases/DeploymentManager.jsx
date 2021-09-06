import React, {useState, useEffect, useRef} from 'react'
import DeployedReleasesTable from './DeployedReleasesTable';
import { Nav, NavItem, NavDropdown, MenuItem, Alert, Button } from 'react-bootstrap';
import { Link, useHistory } from 'react-router-dom';
import DeployedReleasesFilter from './DeployedReleasesFilter';
import DeploymentForm from './DeploymentForm';
import EinsatzmeldungArchivieren from '../EinsatzmeldungArchivieren';
import EinsatzmeldungBearbeiten from '../EinsatzmeldungBearbeiten';
import useQuery from '../../hooks/hooks';

export default function DeploymentManager() {
  /** @const {number} fetchCount - Ensures that the latest fetch gets processed. */
  const fetchCount = useRef(0);

  /** @const {number} fetchCountForm - Ensures that the latest fetch gets processed. */
  const fetchCountForm = useRef(0);

  /** @const {URLSearchParams} query - Read URL Params. */
  const query = useQuery();

  /** @const {object} history - The history object (URL modifications). */
  const history = useHistory();

  /** @const {number} count - Changing this triggers fetch of deployed releases. */
  const [count, setCount] = useState(0);
  
  /**
   * @const {Object[]} data - Array der Einsatzmeldungsobjekte.
   * @property {string} data[].date - Das Einsatzdatum.
   * @property {string} data[].environment - Die Einsatzumgebung.
   * @property {string} data[].nid - Die Node ID der Einsatzmeldung.
   * @property {string} data[].release - Der Release Name.
   * @property {string} data[].releaseNid - Die Node ID des Release.
   * @property {string} data[].service - Der Verfahrensname.
   * @property {string} data[].serviceNid - Die Node ID des Verfahrens.
   * @property {string} data[].state - Die Landes ID des Einsatzes.
   * @property {string} data[].title - Der Titel der Einsatzmeldung.
   * @property {string} data[].uuid - Die UUID der Einsatzmeldung.
   * @property {string} data[].status - Der Status der Einsatzmeldung.
   */
  const [data, setData] = useState([]);

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

  // Benötigt für die Initiale Befüllung der isArchived-State-Variablen. Wurde
  // die Seite "archiviert" initial aufgerufen?
  let status = "1";
  if (history.location.pathname.indexOf('archiviert') > 0) {
    status = "2";
  }
  /** @const {string} isArchived - Archiviert oder im Einsatz? */
  // const [isArchived, setIsArchived] = useState(archivedUrl);

  /** @const {bool} timeout - True triggers display of "No data found.". */
  const [timeout, setTimeout] = useState(false);

  // Wird verwendet, um Fehlermeldungen anzuzeigen.
  /** @const {React.Component|bool} error - React Component with error message or false. */
  const [error, setError] = useState(false);

  /**
   * @typedef {{
   *    [nid: nid of release]: {
   *      uuid: string,
   *      nid: string,
   *      title: string,
   *      service: string,
   *    }
   *  }} release
   * @property {{[nid: nid of service]: release}} releases - The provided releases.
   */
  const [releases, setReleases] = useState({});

  // Pagination.
  const [page, setPage] = useState(1);

  const initialFilterState = {
    "state": query.has("state") ? query.get("state") : global.drupalSettings.userstate,
    "environment": query.has("environment") ? query.get("environment") : "0",
    "service": query.has("service") ? query.get("service") : "0",
    "product": query.has("product") ? query.get("product") : "",
    "status": status,
  };
  /**
   * The filter state object.
   * @property {Object} filterState - The object holding the filter state.
   * @property {string} filterState.state - The state id.
   * @property {string} filterState.environment - The environment id.
   * @property {string} filterState.service - The service id.
   * @property {string} filterState.product - The product name.
   * @property {string} filterState.status - The deployment status.
   */
  const [filterState, setFilterState] = useState(initialFilterState);

  const initialFormState = {
    "uuid": "",
    "state": filterState.state,
    "environment": "0",
    "service": "0",
    "date": "",
    "installationTime": "",
    "isAutomated": false,
    "abnormalities": false,
    "description": "",
    "releaseNid": "0",
    "previousRelease": [],
    "archivePrevRelease": [],
    "archiveThis": false,
    "firstDeployment": true,
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
   */
  const [formState, setFormState] = useState(initialFormState);

  /** @const {bool} triggerForm - Triggers deployment form. */
  const [triggerForm, setTriggerForm] = useState(false);

  /** @const {string} prevDeploymentId  ???? */
  const [prevDeploymentId, setPrevDeploymentId] = useState(false);
  const [triggerReleaseSelect, setTriggerReleaseSelect] = useState(false);
  // Loading Spinner für Neues Release und Vorgängerrelease.
  const [isLoading, setIsLoading] = useState(false);
  // Loading Spinner für Release-Filterung (Meldbare Releases) im 
  // Filter.
  const [loadingReleasesSpinner, setLoadingReleasesSpinner] = useState(false);
  const [disabled, setDisabled] = useState(true);
  const [deploymentHistory, setDeploymentHistory] = useState([]);
  // Infotext während Releaseauswahl befüllt wird.
  const [loadingMessage, setLoadingMessage] = useState("");

  // Modus des Meldeformulars (Integer):
  // true: Ersteinsatz
  // false: Nachfolgerelease
  const [firstDeployment, setFirstDeployment] = useState(true);

  const [showDeploymentForm, setShowDeploymentForm] = useState(false);

  // Formular zum Archivieren von Einsatzmeldungen anzeigen.
  const [showArchiveForm, setShowArchiveForm] = useState(false);

  const [prevName, setPrevName] = useState("");

  const [showEditForm, setShowEditForm] = useState(false);

  // Für Formular zum Bearbeiten der Einsatzmeldung.
  const [deploymentUuid, setDeploymentUuid] = useState(false);

  // Für SelectRelease Komponente. Die nicht-eingesetzten Releases.
  const [newReleases, setNewReleases] = useState([]);
  // Für SelectPreviousRelease Komponente. Die eingesetzten Releases.
  const [prevReleases, setPrevReleases] = useState([]);

  /**
   * Implements hook useEffect().
   * Fetches the deployed releases.
   */
  useEffect(() => {
    fetchDeployments();
    if (filterState.service !== "0") {
      preloadDeploymentData(filterState);
    }
  }, [filterState.status, filterState.state, filterState.environment, filterState.service, count]);

  /**
   * Changes URL-Params depending on Nav / Filters, resets Pagination.
   * 
   * Implements hook useEffect().
   */
  useEffect(() => {
    let pathname;
    switch (filterState.status) {
      case "1":
        pathname = '/zrml/r/einsatzmeldungen/eingesetzt';
        break;
      case "2":
        pathname = '/zrml/r/einsatzmeldungen/archiviert';
        break;
      default:
        pathname = '/zrml/r/einsatzmeldungen/eingesetzt';
        break;
    }
    // Change URL Params
    const params = new URLSearchParams();
    if (filterState.state !== "1" && filterState.state) {
      params.append("state", filterState.state);
    } else {
      params.delete("state");
    }

    if (filterState.environment !== "0") {
      params.append("environment", filterState.environment);
    } else {
      params.delete("environment");
    }

    if (filterState.service !== "0") {
      params.append("service", filterState.service);
    } else {
      params.delete("service");
    }

    if (filterState.product !== "") {
      params.append("product", filterState.product);
    } else {
      params.delete("product");
    }

    history.push({
      pathname: pathname,
      search: params.toString(),
    });

    // Reset Pagination.
    setPage(1);
  }, [filterState]);

  /**
   * Fetcht alle Releases, dient zur Befüllung von:
   *  - Release Filter
   *  - Release Auswahl
   *  - Auswahl Vorgängerrelease
   * 
   * Implements hook useEffect().
   */
  useEffect(() => {
    // Prevent multiple fetches for the same serviceFilter.
    setLoadingMessage(<p>Bereitgestellte Releases werden geladen ... <span className="glyphicon glyphicon-refresh glyphicon-spin" role="status" /></p>);
    fetchReleases(filterState.service);
  }, [filterState.service])

  /**
   * Fetcht alle Releases, dient zur Befüllung von:
   *  - Release Filter
   *  - Release Auswahl (Für Meldung)
   *  - Auswahl Vorgängerrelease
   * 
   * Implements hook useEffect().
   */
  // useEffect(() => {
  //   console.log("Service in Formular gewählt: ", formState.service);
  //   // Aktiviert den Spinner für Release und Vorgängerrelease-Dropdowns im Formular.
  //   if (formState.service != "0" && formState.environment != "0") {
  //     setIsLoading(true);
  //   }
  //   setDisabled(true);
  //   fetchDeployments();
  // }, [formState.service, formState.environment])

  // Trigger release-selector filtering.
  useEffect(() => {
    if (formState.environment !== "0" && formState.service !== "0") {
      // Check, if data for selected environment and service is available (preloaded).
      const dataService = deploymentData.pop();
      const dataEnvironment = deploymentData.pop();
      const dataState = deploymentData.pop();
      let triggerFetch = false;
      // Check service.
      if (dataService !== formState.service) {
        triggerFetch = true;
      }
      // Check environment.
      if (dataEnvironment !== formState.environment && dataEnvironment !== "0") {
        triggerFetch = true;
      }
      // Check state.
      if (dataState !== formState.state && dataState !== "0") {
        triggerFetch = true;
      }
      // Check if releases are already loaded.
      if (!(formState.service in releases)) {
        triggerFetch = true;
      }

      if (triggerFetch === true) {
        console.log("Daten für Formular müssen neu geladen werden ...");
        fetchReleases(formState.service);
      }
      else {
        console.log("Daten für Einsatzmeldung vorhanden. Releaseauswahl wird befüllt.");
        populateReleaseSelectors();
      }
    }
  }, [formState.environment, formState.service, deploymentData])

  /**
   * Fetches and appends release deployments (as state).
   */
  const fetchDeployments = () => {
    let url = '/api/v1/deployments';

    // Status-Filter
    // @todo Status "Fehlmeldung" fehlt noch.
    url += '?status[]=' + filterState.status;

    // Landes-Filter (nur für Gruppen- und Site-Admins)
    if (filterState.state && filterState.state !== "1") {
      url += '&states=' + filterState.state;
    }

    // Umgebung.
    if (filterState.environment !== "0") {
      url += '&environment=' + filterState.environment;
    }

    // Verfahren.
    if (filterState.service !== "0") {
      url += '&service=' + filterState.service;
    }

    if (filterState.status === "1") {
      url += '&items_per_page=All';
    }

    if (filterState.status === "2") {
      url += '&page=' + (page - 1);
      url += '&releaseTitle=' + filterState.product;
    }

    setData([]);
    setTimeout(false);
    fetchCount.current++;
    const runner = fetchCount.current;

    const headers = new Headers({
      Accept: 'application/vnd.api+json',
    });
    return fetch(url, { headers })
      .then(response => response.json())
      .then(results => {
        if (runner === fetchCount.current) {
          if (results.length === 0) setTimeout(true);
          // console.log(results);
          setData(results);
        }
      })
      .catch(error => {
        console.log("error", error);
        setError(<li>Fehler beim Laden der Einsatzmeldungen. Bitte kontaktieren Sie das BpK-Team.</li>);
        setTimeout(true)
      });
  }

  /**
   * Fetches and appends releases (as state) for a given service nid.
   * 
   * @param {string|number} mixedService - The service nid.
   */
  const fetchReleases = (mixedService) => {
    if (Number.isInteger(mixedService)) {
      mixedService = mixedService.toString();
    }
    if (mixedService in releases) {
      setTriggerReleaseSelect(true);
      preloadDeploymentData(formState);
      return;
    }
    if (mixedService == "0") {
      return;
    }

    let url = '/api/v1/releases/' + mixedService;
    const headers = new Headers({
      Accept: 'application/vnd.api+json',
    });

    return fetch(url, { headers })
      .then(response => response.json())
      .then(results => {
        let releaseData = { ...releases };
        releaseData[mixedService] = {};
        results.map((result) => {
          let release = {
            "uuid": result.uuid,
            "nid": result.nid,
            "title": result.title,
            "service": result.service,
          };
          releaseData[mixedService][result.nid] = release;
        });
        setReleases(releaseData);
        setTriggerReleaseSelect(true);
        preloadDeploymentData(formState);
      })
      .catch(error => {
        console.log(error);
        setError(<li>Fehler beim Laden der Releases. Bitte kontaktieren Sie das BpK-Team.</li>);
      });
  }

  /**
   * Preloads deployment data asnychronous to filter selection.
   * 
   * Should speed up the filtering of selectable releases for reporting new 
   * deployments, especially for follow up deployments.
   */
  const preloadDeploymentData = (params) => {
    setLoadingReleasesSpinner(true);
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
          setLoadingReleasesSpinner(false);
          console.log(params);
          setDeploymentData([...results, params.state, params.environment, params.service]);
        }
      })
      .catch(error => console.log("error", error));
  }

  /**
   * Prepares the selectable releases (Release and Previous Release).
   */
  const populateReleaseSelectors = () => {
    if (triggerReleaseSelect === false) {
      return;
    }
    // Trigger zurücksetzen.
    setTriggerReleaseSelect(false);

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

    if (formState.service in releases) {
      // All provided releases for the selected service.
      var releaseArray = releases[formState.service];
    }

    // Releases filtern: Eingesetzt (Vorgängerreleases).
    // let filteredPrevReleases = releaseArray.filter(release => {
    //   return deployedReleaseNids.indexOf(release.nid) >= 0;
    // })

    // Releases filtern: Eingesetzt (Vorgängerreleases).
    // @todo WIP: Umbauen auf Objekt Iteration.
    let filteredPrevReleases = {...releaseArray};
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
    deployedReleases.sort((a, b) => b - a);

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
    let filteredNewReleases = {...releaseArray};
    for (const nid in releaseArray) {
      if (deployedReleaseNids.indexOf(nid) >= 0) {
        delete filteredNewReleases[nid];
      }
    }


    let undeployedReleases = [];
    for (const release in filteredNewReleases) {
      undeployedReleases.push(filteredNewReleases[release]);
    }
    undeployedReleases.sort((a, b) => b - a);

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

  const handleReset = () => {
    setPage(1);
    setFilterState({
      "state": global.drupalSettings.userstate,
      "environment": "0",
      "service": "0",
      "product": "",
      "status": filterState.status,
    });
  }

  const handleFirstDeployment = () => {
    setFormState(initialFormState);
    setShowDeploymentForm(true);
  }

  const handleAction = (userState, environment, service, release, deploymentId) => {
    setFirstDeployment(false);
    setTriggerForm(!triggerForm);
    setUserState(userState);
    setEnvironment(environment);
    setService(service);
    setPreviousRelease(release);
    setPrevDeploymentId(deploymentId);
  }

  const handleNav = (k) => {
    setFilterState(prev => ({ ...prev, "status": k }));
    setPage(1);
  }

  const handleArchive = (deploymentId, releaseName) => {
    setPrevDeploymentId(deploymentId);
    setPrevName(releaseName);
    setShowArchiveForm(true);
  }

  const handleEdit = (deploymentId) => {
    setDeploymentUuid(deploymentId);
    setShowEditForm(true);
  }

  // Handler für Button "Ersteinsatz melden".
  const handleClose = () => {
    setFormState(initialFormState);
    setShowDeploymentForm(false);
  }

  return (
    <div>
      <Nav bsStyle="tabs" activeKey={filterState.status} onSelect={handleNav}>
        <NavItem eventKey="1">
          Eingesetzt
        </NavItem>
        <NavItem eventKey="2">
          Archiv
        </NavItem>
      </Nav>
      {error &&
      <Alert bsStyle="danger" onDismiss={() => setError(false)}>
        <h4>Fehler</h4>
        <ul>
          {error}
        </ul>
        <p></p>
        <p>
          <Button onClick={() => setError(false)} bsStyle="danger">Meldung schließen</Button>
        </p>
      </Alert>}
      <DeployedReleasesFilter
        filterState={filterState}
        setFilterState={setFilterState}
        handleReset={handleReset}
        count={count}
        setCount={setCount}
        releases={releases}
        fetchDeployments={fetchDeployments}
        loadingReleasesSpinner={loadingReleasesSpinner}
      />
      <DeploymentForm
        count={count}
        setCount={setCount}
        filterState={filterState}
        formState={formState}
        setFormState={setFormState}
        triggerForm={triggerForm}
        setTriggerForm={setTriggerForm}
        setError={setError}
        releases={releases}
        triggerReleaseSelect={triggerReleaseSelect}
        setTriggerReleaseSelect={setTriggerReleaseSelect}
        isLoading={isLoading}
        setIsLoading={setIsLoading}
        disabled={disabled}
        setDisabled={setDisabled}
        setDeploymentHistory={setDeploymentHistory}
        loadingMessage={loadingMessage}
        setLoadingMessage={setLoadingMessage}
        newReleases={newReleases}
        prevReleases={prevReleases}
        handleFirstDeployment={handleFirstDeployment}
        showDeploymentForm={showDeploymentForm}
        setShowDeploymentForm={setShowDeploymentForm}
        handleClose={handleClose}
      />
      <DeployedReleasesTable
        data={data}
        timeout={timeout}
        setTimeout={setTimeout}
        handleAction={handleAction}
        deploymentHistory={deploymentHistory}
        page={page}
        setPage={setPage}
        count={count}
        setCount={setCount}
        handleArchive={handleArchive}
        handleEdit={handleEdit}
        filterState={filterState}
      />
      {/*<EinsatzmeldungArchivieren
        showArchiveForm={showArchiveForm}
        setShowArchiveForm={setShowArchiveForm}
        prevDeploymentId={prevDeploymentId}
        count={count}
        setCount={setCount}
        prevName={prevName}
      />
      <EinsatzmeldungBearbeiten 
        showEditForm={showEditForm}
        setShowEditForm={setShowEditForm}
        prevDeploymentId={prevDeploymentId}
        triggerReleaseSelect={triggerReleaseSelect}
        setTriggerReleaseSelect={setTriggerReleaseSelect}
        releases={releases}
        setService={setService}
        isLoading={isLoading}
        setIsLoading={setIsLoading}
        disabled={disabled}
        setDisabled={setDisabled}
        deploymentUuid={deploymentUuid}
        setDeploymentUuid={setDeploymentUuid}
      /> */}
    </div>
  )
}
