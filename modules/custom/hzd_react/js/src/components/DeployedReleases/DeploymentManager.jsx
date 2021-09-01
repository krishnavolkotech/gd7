import React, {useState, useEffect, useRef} from 'react'
import EinsatzmeldungsTabelle from '../EinsatzmeldungsTabelle';
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

  /** @const {URLSearchParams} query - Read URL Params. */
  const query = useQuery();

  /** @const {object} history - The history object (URL modifications). */
  const history = useHistory();

  /** @const {number} count - Changing this triggers fetch of deployed releases. */
  const [count, setCount] = useState(0);
  
  /** @const {array} data - Daten der Einsatzmeldungen. */
  const [data, setData] = useState([]);
  
  // Benötigt für die Initiale Befüllung der isArchived-State-Variablen. Wurde
  // die Seite "archiviert" initial aufgerufen?
  let archivedUrl = "0";
  if (history.location.pathname.indexOf('archiviert') > 0) {
    archivedUrl = "1";
  }
  /** @const {string} isArchived - Archiviert oder im Einsatz? */
  const [isArchived, setIsArchived] = useState(archivedUrl);

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


  // Welcher Reiter ist gewählt? Eingesetzt / Archiv.
  let activeKey
  if (isArchived == "1") {
    activeKey = "1";
  }
  else {
    activeKey = "0";
  }

  const initialFilterState = {
    "state": query.has("state") ? query.get("state") : global.drupalSettings.userstate,
    "environment": query.has("environment") ? query.get("environment") : "0",
    "service": query.has("service") ? query.get("service") : "0",
    "product": query.has("product") ? query.get("product") : "",
  };
  /**
   * The filter state object.
   * @property {Object} filterState - The object holding the filter state.
   * @property {string} filterState.state - The state id.
   * @property {string} filterState.environment - The environment id.
   * @property {string} filterState.service - The service id.
   * @property {string} filterState.product - The product name.
   */
  const [filterState, setFilterState] = useState(initialFilterState);

  const initialFormState = {
    "uuid": "",
    "state": "0",
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

  /** @const {bool} show - Show deployment form. */
  const [show, setShow] = useState(false);

  /** @const {string} prevDeploymentId  ???? */
  const [prevDeploymentId, setPrevDeploymentId] = useState(false);
  const [triggerReleaseSelect, setTriggerReleaseSelect] = useState(false);
  // Loading Spinner für Neues Release und Vorgängerrelease.
  const [isLoading, setIsLoading] = useState(false);
  const [disabled, setDisabled] = useState(true);
  const [deploymentHistory, setDeploymentHistory] = useState([]);
  // Infotext während Releaseauswahl befüllt wird.
  const [loadingMessage, setLoadingMessage] = useState("");

  // Modus des Meldeformulars (Integer):
  // true: Ersteinsatz
  // false: Nachfolgerelease
  const [firstDeployment, setFirstDeployment] = useState(true);

  // Formular zum Archivieren von Einsatzmeldungen anzeigen.
  const [showArchiveForm, setShowArchiveForm] = useState(false);

  const [prevName, setPrevName] = useState("");

  const [showEditForm, setShowEditForm] = useState(false);

  // Für Formular zum Bearbeiten der Einsatzmeldung.
  const [deploymentUuid, setDeploymentUuid] = useState(false);

  /**
   * Implements hook useEffect().
   * Fetches the deployed releases.
   */
  useEffect(() => {
    let url = '/api/v1/deployments/';
    
    // Status-Filter
    let archivedFilter = "1/";
    if (isArchived == "1" ) {
      archivedFilter = "2/";
    }

    url += archivedFilter;

    // Landes-Filter (nur für Gruppen- und Site-Admins)
    let stateUrl = 'all/'
    if (filterState.state && filterState.state !== "1") {
      stateUrl = filterState.state + '/';
    }
    url += stateUrl;

    // Umgebung.
    let environmentUrl = 'all/'
    if (filterState.environment !== "0") {
      environmentUrl = filterState.environment + '/';
    }
    url += environmentUrl;

    // Verfahren.
    let serviceUrl = 'all'
    if (filterState.service !== "0") {
      serviceUrl = filterState.service;
    }
    url += serviceUrl;

    if (isArchived === "0") {
      url += '?items_per_page=All';
    }

    if (isArchived === "1") {
      url += '?page=' + (page -1);
    }


    // Release-Filter unnötig? Filterung direkt in React besser?
    // if (releaseFilter !== "0") {
    //   url += "&filter[field_deployed_release.drupal_internal__nid]=" + releaseFilter;
    // }
    setData([]);
    setTimeout(false);
    fetchCount.current++;
    const runner = fetchCount.current;

    const headers = new Headers({
      Accept: 'application/vnd.api+json',
    });

    fetch(url, { headers })
      .then(response => response.json())
      .then(results => {
        console.log('Läufer angekommen: ' + runner + ". Insgesamt: " + fetchCount.current);
        if (runner === fetchCount.current) {
          if (results.length === 0) setTimeout(true);
          setData(results);
        }
      })
      .catch(error => {
        console.log("error", error);
        setError(<li>Fehler beim Laden der Einsatzmeldungen. Bitte kontaktieren Sie das BpK-Team.</li>);
        setTimeout(true)
      });
  }, [isArchived, filterState, count]);

  /**
   * Changes URL-Params depending on Nav / Filters, resets Pagination.
   * 
   * Implements hook useEffect().
   */
  useEffect(() => {
    let pathname;
    switch (isArchived) {
      case "0":
        pathname = '/zrml/r/einsatzmeldungen/eingesetzt';
        break;
      case "1":
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
  }, [isArchived, filterState]);

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
    console.log("Service in Filter gewählt: ", filterState.service);
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
  useEffect(() => {
    console.log("Service in Formular gewählt: ", formState.service);
    // Aktiviert den Spinner für Release und Vorgängerrelease-Dropdowns im Formular.
    if (formState.service != "0") {
      setIsLoading(true);
    }
    setDisabled(true);
    fetchReleases(formState.service);
  }, [formState.service])

  /**
   * Fetches and appends releases (as state) for a given service nid.
   * 
   * @param {string|number} mixedService - The service nid.
   */
  const fetchReleases = (mixedService) => {
    if (Number.isInteger(mixedService)) {
      mixedService = mixedService.toString();
    }
    console.log("Fetchen für: ", mixedService);
    if (mixedService in releases) {
      console.log("Service schon vorhanden - Releases werden nicht nochmal geladen.");
      setTriggerReleaseSelect(true);
      return;
    }
    if (mixedService == "0") {
      return;
    }

    let url = '/api/v1/releases/' + mixedService;
    const headers = new Headers({
      Accept: 'application/vnd.api+json',
    });

    fetch(url, { headers })
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
        console.log('Releases geladen.');
        setReleases(releaseData);
        setTriggerReleaseSelect(true);
        console.log(releaseData);
      })
      .catch(error => {
        console.log(error);
        setError(<li>Fehler beim Laden der Releases. Bitte kontaktieren Sie das BpK-Team.</li>);
      });
  }

  const handleReset = () => {
    setPage(1);
    setFilterState({
      "state": global.drupalSettings.userstate,
      "environment": "0",
      "service": "0",
      "product": "",
    });
  }

  const handleAction = (userState, environment, service, release, deploymentId) => {
    setFirstDeployment(false);
    setShow(!show);
    setUserState(userState);
    setEnvironment(environment);
    setService(service);
    setPreviousRelease(release);
    setPrevDeploymentId(deploymentId);
  }

  const handleNav = (k) => {
    setIsArchived(k)
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
  console.log("Filter State:", filterState);

  return (
    <div>
      <Nav bsStyle="tabs" activeKey={activeKey} onSelect={handleNav}>
        <NavItem eventKey="0">
          Eingesetzt
        </NavItem>
        <NavItem eventKey="1">
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
      />
      {/* <DeploymentForm
        count={count}
        setCount={setCount}
        environment={environment}
        setEnvironment={setEnvironment}
        service={service}
        setService={setService}
        previousRelease={previousRelease}
        setPreviousRelease={setPreviousRelease}
        userState={userState}
        setUserState={setUserState}
        show={show}
        setShow={setShow}
        prevDeploymentId={prevDeploymentId}
        setError={setError}
        releases={releases}
        triggerReleaseSelect={triggerReleaseSelect}
        setTriggerReleaseSelect={setTriggerReleaseSelect}
        isLoading={isLoading}
        setIsLoading={setIsLoading}
        disabled={disabled}
        setDisabled={setDisabled}
        setDeploymentHistory={setDeploymentHistory}
        firstDeployment={firstDeployment}
        setFirstDeployment={setFirstDeployment}
        serviceFilter={serviceFilter}
        environmentFilter={environmentFilter}
        isArchived={isArchived}
        loadingMessage={loadingMessage}
        setLoadingMessage={setLoadingMessage}
      />
      <EinsatzmeldungsTabelle
        data={data}
        timeout={timeout}
        handleAction={handleAction}
        deploymentHistory={deploymentHistory}
        isArchived={isArchived}
        page={page}
        setPage={setPage}
        count={count}
        setCount={setCount}
        handleArchive={handleArchive}
        handleEdit={handleEdit}
      />
      <EinsatzmeldungArchivieren
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
