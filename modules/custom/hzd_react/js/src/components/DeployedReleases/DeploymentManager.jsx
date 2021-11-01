import React, {useState, useEffect, useRef} from 'react'
import DeployedReleasesTable from './DeployedReleasesTable';
import { Nav, NavItem, NavDropdown, MenuItem, Alert, Button } from 'react-bootstrap';
import { Link, useHistory } from 'react-router-dom';
import DeployedReleasesFilter from './DeployedReleasesFilter';
import DeploymentForm from './DeploymentForm';
import ArchiveForm from './ArchiveDeploymentForm';
import useQuery from '../../hooks/hooks';
import FormManager from './FormManager';
import NodeView from './NodeView';

export default function DeploymentManager() {
  /** @const {number} fetchCount - Ensures that the latest fetch gets processed. */
  const fetchCount = useRef(0);


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

  let initialState = query.has("state") ? query.get("state") : global.drupalSettings.userstate;
  initialState = global.drupalSettings.role === "ZRML" ? global.drupalSettings.userstate : initialState;

  const initialFilterState = {
    "state": initialState,
    "environment": query.has("environment") ? query.get("environment") : "0",
    "service": query.has("service") ? query.get("service") : "0",
    "product": query.has("product") ? query.get("product") : "",
    "status": status,
    "sortBy": query.has("sortBy") ? query.get("sortBy") : "field_date_deployed_value",
    "sortOrder": query.has("sortOrder") ? query.get("sortOrder") : "DESC",
  };
  /**
   * The filter state object.
   * @property {Object} filterState - The object holding the filter state.
   * @property {string} filterState.state - The state id.
   * @property {string} filterState.environment - The environment id.
   * @property {string} filterState.service - The service id.
   * @property {string} filterState.product - The product name.
   * @property {string} filterState.status - The deployment status.
   * @property {string} filterState.sortBy - Field name for sorting.
   * @property {string} filterState.sortOrder - The sorting direction ('ASC', 'DESC').
   */
  const [filterState, setFilterState] = useState(initialFilterState);

  /** @const {string} prevDeploymentId - UUID of prev deployment */
  const [prevDeploymentId, setPrevDeploymentId] = useState(false);

  // Loading Spinner für Release-Filterung (Meldbare Releases) im 
  // Filter.
  const [loadingReleasesSpinner, setLoadingReleasesSpinner] = useState(false);
  const [deploymentHistory, setDeploymentHistory] = useState([]);

  const [prevName, setPrevName] = useState("");

  // Für Formular zum Bearbeiten der Einsatzmeldung.
  const [deploymentUuid, setDeploymentUuid] = useState(false);

  // Triggert Releaseauswahl befüllung.
  const [triggerReleaseSelectCount, setTriggerReleaseSelectCount] = useState(0);

  const [triggerAction, setTriggerAction] = useState(false);

  const [viewNode, setViewNode] = useState(false);

  /**
   * Implements hook useEffect().
   * Fetches the deployed releases.
   */
  useEffect(() => {
    fetchDeployments();
    // if (filterState.service !== "0") {
    //   preloadDeploymentData(filterState);
    // }
  }, [filterState.status, filterState.state, filterState.environment, filterState.service, count]);

  /**
   * Changes URL-Params depending on Nav / Filters, resets Pagination.
   * 
   * Implements hook useEffect().
   */
  useEffect(() => {
    // Change URL path based on status.
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
    
    // Change URL Params.
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

    if (filterState.sortBy !== "") {
      params.append("sortBy", filterState.sortBy);
    } else {
      params.delete("sortBy");
    }

    if (filterState.sortOrder !== "") {
      params.append("sortOrder", filterState.sortOrder);
    } else {
      params.delete("sortOrder");
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
    // setLoadingMessage(<p>Bereitgestellte Releases werden geladen ... <span className="glyphicon glyphicon-refresh glyphicon-spin" role="status" /></p>);
    fetchReleases(filterState.service);
  }, [filterState.service])


  /**
   * Fetches and appends release deployments (as state).
   */
  const fetchDeployments = () => {
    let url = '/api/v1/deployments';

    // Status-Filter
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

    // Nur im Status "im Einsatz" sollen alle Einsatzmeldungen auf einmal geladen
    // werden.
    if (filterState.status === "1") {
      url += '&items_per_page=All';
    }

    // Apply product filtering, if not on page "deployed".
    if (filterState.status !== "1") {
      url += '&page=' + (page - 1);
      url += '&releaseTitle=' + filterState.product;
    }

    // Apply sorting.
    url += '&sort_by=' + filterState.sortBy;
    url += '&sort_order=' + filterState.sortOrder;

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
          // Hier results sortieren :)
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
    if (mixedService == "0") {
      return;
    }
    if (mixedService in releases) {
      // setTriggerReleaseSelect(true);
      setTriggerReleaseSelectCount(triggerReleaseSelectCount + 1);
      // preloadDeploymentData(formState);
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
            "uuidRelease": result.uuid,
            "nid": result.nid,
            "title": result.title,
            "service": result.service,
          };
          releaseData[mixedService][result.nid] = release;
        });
        setReleases(releaseData);
        setTriggerReleaseSelectCount(triggerReleaseSelectCount + 1);
        // setTriggerReleaseSelect(true);
        // preloadDeploymentData(formState);
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
      "status": filterState.status,
      "sortBy": "field_date_deployed_value",
      "sortOrder": "DESC",
    });
  }

  // Nachfolgerelase melden
  const handleAction = (e, action, args) => {
    // e.preventDefault();
    setTriggerAction({ action, args });

    // setFirstDeployment(false);
    // setTriggerForm(!triggerForm);
    // setUserState(userState);
    // setEnvironment(environment);
    // setService(service);
    // setPreviousRelease(release);
    // setPrevDeploymentId(deploymentId);
  }

  const handleNav = (k) => {
    setFilterState(prev => ({ ...prev, "status": k }));
    setPage(1);
  }

  const handleArchive = (deploymentId, releaseName) => {
    // setPrevDeploymentId(deploymentId);
    // setPrevName(releaseName);
    // setShowArchiveForm(true);
  }

  const handleEdit = (deploymentId) => {
    // setDeploymentUuid(deploymentId);
    // setShowEditForm(true);
  }

  const handleView = (nid) => {
    setViewNode(nid);
  }

  return (
    <div>
      {/* <div className="skeleton-header loading"></div>
      <div className="skeleton-select"></div>
      <div className="skeleton-textbody loading"></div>
      <div className="skeleton-textbody loading"></div>
      <div className="skeleton-textbody loading"></div> */}
      <Nav bsStyle="tabs" activeKey={filterState.status} onSelect={handleNav}>
        <NavItem eventKey="1">
          Eingesetzt
        </NavItem>
        <NavItem eventKey="2">
          Archiv
        </NavItem>
        {global.drupalSettings.role !== "ZRML" &&
        <NavItem eventKey="3">
          Fehlmeldungen
        </NavItem>
        }
      </Nav>
      {viewNode &&
      <NodeView
        nid={viewNode}
        setViewNode={setViewNode}
      />
      }
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
      <FormManager
        releases={releases}
        fetchReleases={fetchReleases}
        state={filterState.state}
        status={filterState.status}
        count={count}
        setCount={setCount}
        setDeploymentHistory={setDeploymentHistory}
        triggerReleaseSelectCount={triggerReleaseSelectCount}
        prevDeploymentId={prevDeploymentId}
        triggerAction={triggerAction}
        setTriggerAction={setTriggerAction}
        setError={setError}
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
        handleView={handleView}
      />
      <p>Build v0.6</p>
    </div>
  )
}
