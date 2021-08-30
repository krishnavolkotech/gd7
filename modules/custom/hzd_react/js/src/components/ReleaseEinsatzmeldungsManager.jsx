import React, {useState, useEffect, useRef} from 'react'
import EinsatzmeldungsTabelle from './EinsatzmeldungsTabelle';
import { Nav, NavItem, NavDropdown, MenuItem, Alert, Button } from 'react-bootstrap';
import { Link, useHistory } from 'react-router-dom';
import EinsatzmeldungsFilter from './EinsatzmeldungsFilter';
import EinsatzmeldungsFormular from './EinsatzmeldungsFormular';
import EinsatzmeldungArchivieren from './EinsatzmeldungArchivieren';
import EinsatzmeldungBearbeiten from './EinsatzmeldungBearbeiten';
import useQuery from '../hooks/hooks';

export default function ReleaseEinsatzmeldungsManager() {
  // @var fetchCount {number} Der aktuelle fetch counter, um zu verhindern, dass
  // ein neuer Request von einem Älteren überholt wird.
  const fetchCount = useRef(0);

  // Wird verwendet, um URL Parameter auszulesen.
  const query = useQuery();

  // Benötigt, um URL Änderungen durchführen zu können und URL Parameter zu setzen.
  const history = useHistory();

  // Benötigt für die Initiale Befüllung der isArchived-State-Variablen. Wurde
  // die Seite "archiviert" initial aufgerufen?
  let archivedUrl = "0";
  if (history.location.pathname.indexOf('archiviert') > 0) {
    archivedUrl = "1";
  }

  // Für die Aktualisierung der Liste nach dem Speichern.
  const [count, setCount] = useState(0);

  /** @const {array} data - Daten der Einsatzmeldungen. */
  const [data, setData] = useState([]);
  //console.log(setData);

  /** @const {string} isArchived - Archiviert oder im Einsatz? */
  const [isArchived, setIsArchived] = useState(archivedUrl);

  // True, wenn keine Daten geladen werden können.
  const [timeout, setTimeout] = useState(false);

  // Wird verwendet, um Fehlermeldungen anzuzeigen.
  const [error, setError] = useState(false);

  // Alle bereitgestellten Releases.
  const [releases, setReleases] = useState([]);

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

  // Gewählte Umgebung aus URL Parametern.
  const environmentSelection = query.has("environment") ? query.get("environment") : "0";
  const [environmentFilter, setEnvironmentFilter] = useState(environmentSelection);
  // Gewähltes Verfahren aus URL Parametern.
  const serviceSelection = query.has("service") ? query.get("service") : "0";
  const [serviceFilter, setServiceFilter] = useState(serviceSelection);
  // Filter State.
  /** @const {(string|false)} stateSelection - Gewähltes Land aus URL Parameter. */
  const stateSelection = query.has("state") ? query.get("state") : false;
  const [stateFilter, setStateFilter] = useState(global.drupalSettings.userstate);
  // Gewähltes Release aus URL Parametern.
  const releaseSelection = query.has("release") ? query.get("release") : "0";
  const [releaseFilter, setReleaseFilter] = useState(releaseSelection);

  // Kontrollierter State für EinsatzmeldungsFormular.
  const [environment, setEnvironment] = useState("1");
  const [service, setService] = useState("0");
  const [previousRelease, setPreviousRelease] = useState("0");
  const [userState, setUserState] = useState(global.drupalSettings.userstate);
  // Formular anzeigen.
  const [show, setShow] = useState(false);
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
    if (stateFilter && stateFilter !== "1") {
      stateUrl = stateFilter + '/';
    }
    url += stateUrl;

    // Umgebung.
    let environmentUrl = 'all/'
    if (environmentFilter !== "0") {
      environmentUrl = environmentFilter + '/';
    }
    url += environmentUrl;

    // Verfahren.
    let serviceUrl = 'all'
    if (serviceFilter !== "0") {
      serviceUrl = serviceFilter;
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
  }, [isArchived, stateFilter, environmentFilter, serviceFilter, releaseFilter, count]);

  /**
   * Implements hook useEffect().
   * Changes URL-Params depending on Nav / Filters.
   * Resets Pagination.
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
    if (stateFilter !== "1" && stateFilter) {
      params.append("state", stateFilter);
    } else {
      params.delete("state");
    }

    if (environmentFilter !== "0") {
      params.append("environment", environmentFilter);
    } else {
      params.delete("environment");
    }

    if (serviceFilter !== "0") {
      params.append("service", serviceFilter);
    } else {
      params.delete("service");
    }

    if (releaseFilter !== "0") {
      params.append("release", releaseFilter);
    } else {
      params.delete("release");
    }

    history.push({
      pathname: pathname,
      search: params.toString(),
    });

    // Reset Pagination.
    setPage(1);
  }, [stateFilter, isArchived, environmentFilter, serviceFilter, releaseFilter]);

  /**
   * Implements hook useEffect().
   * Fetcht alle Releases, dient zur Befüllung von:
   *  - Release Filter
   *  - Release Auswahl
   *  - Auswahl Vorgängerrelease
   */
  useEffect(() => {
    // Prevent multiple fetches for the same serviceFilter.
    console.log("Service in Filter gewählt: ", serviceFilter);
    setLoadingMessage(<p>Bereitgestellte Releases werden geladen ... <span className="glyphicon glyphicon-refresh glyphicon-spin" role="status" /></p>);
    fetchReleases(serviceFilter);
  }, [serviceFilter])

  /**
   * Implements hook useEffect().
   * Fetcht alle Releases, dient zur Befüllung von:
   *  - Release Filter
   *  - Release Auswahl (Für Meldung)
   *  - Auswahl Vorgängerrelease
   */
  useEffect(() => {
    console.log("Service in Formular gewählt: ", service);
    // Aktiviert den Spinner für Release und Vorgängerrelease-Dropdowns im Formular.
    if (service != "0") {
      setIsLoading(true);
    }
    setDisabled(true);
    fetchReleases(service);
  }, [service])

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
        releaseData[mixedService] = [];
        results.map((result) => {
          let release = {
            "uuid": result.uuid,
            "nid": result.nid,
            "title": result.title,
            "service": result.service,
          };
          releaseData[mixedService].push(release);
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
    setEnvironmentFilter("0");
    setReleaseFilter("0");
    setServiceFilter("0");
    setStateFilter(global.drupalSettings.userstate);
  }

  const handleAction = (userState, environment, service, release, deploymentId) => {
    setFirstDeployment(false);
    setShow(!show);
    setUserState(userState);
    setEnvironment(environment);
    setService(service);
    setPreviousRelease(release);
    setPrevDeploymentId(deploymentId);
    console.log(userState, environment, service, release, deploymentId);
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
      <EinsatzmeldungsFilter 
        stateFilter={stateFilter} 
        setStateFilter={setStateFilter} 
        environmentFilter={environmentFilter} 
        setEnvironmentFilter={setEnvironmentFilter} 
        serviceFilter={serviceFilter} 
        setServiceFilter={setServiceFilter} 
        releaseFilter={releaseFilter} 
        setReleaseFilter={setReleaseFilter}
        handleReset={handleReset}
        count={count}
        setCount={setCount}
        releases={releases}
      />
      <EinsatzmeldungsFormular
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
        stateFilter={stateFilter}
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
      />
    </div>
  )
}
