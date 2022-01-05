import React, {useState, useEffect, useRef} from 'react'
import DeployedReleasesFilter from '../DeployedReleases/DeployedReleasesFilter'
import { useHistory } from 'react-router-dom';
import useQuery from '../../hooks/hooks';
import DeployedReleasesTable from './DeployedReleasesTable';
import { ButtonToolbar, ToggleButtonGroup, ToggleButton, Nav, NavItem } from 'react-bootstrap';
import ReleaseLegend from './ReleaseLegend';

export default function DeployedReleasesManager(props) {
  /** @const {number} fetchCount - Ensures that the latest fetch gets processed. */
  const fetchCount = useRef(0);

  /** @const {object} history - The history object (URL modifications). */
  const history = useHistory();

  /** @const {bool} timeout - True triggers display of "No data found.". */
  const [timeout, setTimeout] = useState(false);


  /** @const {number} count - Changing this triggers fetch of deployed releases. */
  const [count, setCount] = useState(0);

  // Benötigt für die Initiale Befüllung der isArchived-State-Variablen. Wurde
  // die Seite "archiviert" initial aufgerufen?
  // let status = "1";
  // if (history.location.pathname.indexOf('archiviert') > 0) {
  //   status = "2";
  // }

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

  // Wird verwendet, um Fehlermeldungen anzuzeigen.
  /** @const {React.Component|bool} error - React Component with error message or false. */
  const [error, setError] = useState(false);

  // Loading Spinner für Release-Filterung (Meldbare Releases) im 
  // Filter.
  const [loadingReleasesSpinner, setLoadingReleasesSpinner] = useState(false);

  /**
   * Implements hook useEffect().
   * Fetches the deployed releases.
   */
  useEffect(() => {
    fetchDeployments();
    // if (props.filterState.service !== "0") {
    //   preloadDeploymentData(props.filterState);
    // }
  }, [props.filterState, count]);

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
    fetchReleases(props.filterState.service);
  }, [props.filterState.service])

  /**
   * Fetches and appends release deployments (as state).
   */
  const fetchDeployments = () => {
    let url = '/api/v2/deployments';

    // console.log(services);
    if (props.filterState.service === "0") {
      // Add group-contextual service filter, if no service is selected.
      const services = Object.keys(global.drupalSettings.services[props.filterState.type]);
      if (services.length > 0) {
        url += '/';
        for (let key of services) {
          // Adds each service as contextual parameter.
          url += key + ','
        }
        // Removes final comma.
        url = url.slice(0, -1);
      }
    }
    // Status-Filter
    if (props.filterState.deploymentStatus == "all") {
      url += '?status[]=1&status[]=2';
    }
    else {
      url += '?status[]=' + props.filterState.deploymentStatus;
    }

    // Landes-Filter (nur für Gruppen- und Site-Admins)
    if (props.filterState.state && props.filterState.state !== "1") {
      url += '&states=' + props.filterState.state;
    }

    // Umgebung.
    if (props.filterState.environment !== "0") {
      url += '&environment=' + props.filterState.environment;
    }

    // Verfahren.
    if (props.filterState.service !== "0") {
      url += '&service=' + props.filterState.service;
    }
    
    // Release.
    if (props.filterState.release !== "0") {
      url += '&release=' + props.filterState.release;
    }

    // Typ.
    if (props.filterState.type) {
      url += '&type=' + props.filterState.type;
    }

    url += '&items_per_page=' + props.filterState.items_per_page;

    // Apply product filtering, if not on page "deployed".
    url += '&page=' + (props.page - 1);
    url += '&releaseTitle=' + props.filterState.product;

    // Apply sorting.
    url += '&sort_by=' + props.filterState.deploymentSortBy;
    url += '&sort_order=' + props.filterState.deploymentSortOrder;

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
      // setTriggerReleaseSelectCount(triggerReleaseSelectCount + 1);
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
        // setTriggerReleaseSelectCount(triggerReleaseSelectCount + 1);
        // setTriggerReleaseSelect(true);
        // preloadDeploymentData(formState);
      })
      .catch(error => {
        console.log(error);
        setError(<li>Fehler beim Laden der Releases. Bitte kontaktieren Sie das BpK-Team.</li>);
      });
  }

  // Resets the deployment filters.
  const handleReset = () => {
    props.setPage(1);
    let val = {};
    val["type"] = props.filterState.type;
    val["state"] = global.drupalSettings.userstate;
    val["environment"] = "0";
    val["service"] = "0";
    val["release"] = "0";
    val["product"] = "";
    val["deploymentStatus"] = props.filterState.deploymentStatus;
    val["deploymentSortBy"] = "field_date_deployed_value";
    val["deploymentSortOrder"] = "DESC";
    val["items_per_page"] = props.filterState.items_per_page;
    props.setFilterState(prev => ({ ...prev, ...val }));
  }

  const handlePill = (k) => {
    let val = {};
    val["deploymentStatus"] = k;
    props.setFilterState(prev => ({ ...prev, ...val }));
  }

  return (
    <div>
      <Nav bsStyle="pills" activeKey={props.filterState.deploymentStatus}>
        <NavItem onSelect={handlePill} eventKey={"1"}>
          Im Einsatz
        </NavItem>
        <NavItem onSelect={handlePill} eventKey={"2"}>
          Archiviert
        </NavItem>
        <NavItem onSelect={handlePill} eventKey={"all"}>
          Alle
        </NavItem>
      </Nav>
      <p></p>
      <DeployedReleasesFilter
        filterState={props.filterState}
        setFilterState={props.setFilterState}
        handleReset={handleReset}
        count={count}
        setCount={setCount}
        releases={releases}
        fetchDeployments={fetchDeployments}
        loadingReleasesSpinner={loadingReleasesSpinner}
      />
      <ReleaseLegend activeKey={props.activeKey} />
      <DeployedReleasesTable
        data={data}
        timeout={timeout}
        setTimeout={setTimeout}
        page={props.page}
        setPage={props.setPage}
        count={count}
        setCount={setCount}
        filterState={props.filterState}
        detail={props.detail}
      />
    </div>
  )
}
