import React, {useState, useEffect} from 'react'
import EinsatzmeldungsTabelle from './EinsatzmeldungsTabelle';
import { Nav, NavItem, NavDropdown, MenuItem } from 'react-bootstrap';
import { Link, useHistory } from 'react-router-dom';
import EinsatzmeldungsFilter from './EinsatzmeldungsFilter';
import useQuery from '../hooks/hooks';

export default function ReleaseEinsatzmeldungsManager() {
  // Wird verwendet, um URL Parameter auszulesen.
  const query = useQuery();

  /** @const {(string|false)} stateSelection - Gewähltes Land aus URL Parameter. */
  const stateSelection = query.has("state") ? query.get("state") : false;

  // Benötigt, um URL Änderungen durchführen zu können und URL Parameter zu setzen.
  const history = useHistory();

  // Benötigt für die Initiale Befüllung der isArchived-State-Variablen. Wurde
  // die Seite "archiviert" initial aufgerufen?
  let archivedUrl = false;
  if (history.location.pathname.indexOf('archiviert') > 0) {
    archivedUrl = "1";
  }

  /** @const {array} data - Daten der Einsatzmeldungen. */
  const [data, setData] = useState([]);
  console.log(setData);

  /** @const {string} isArchived - Archiviert oder im Einsatz? */
  const [isArchived, setIsArchived] = useState(archivedUrl);

  //let activeKey = isArchived ? "1" : "0";
  let activeKey
  if (isArchived == 1) {
    activeKey = "1";
  }
  else {
    activeKey = "0";
  }

  // gewählte Umgebung aus URL Parametern
  const environmentSelection = query.has("environment") ? query.get("environment") : "0";
  // Filter Environments
  const [environmentFilter, setEnvironmentFilter] = useState(environmentSelection);
  
  // gewähltes Verfahren aus URL Parametern
  const serviceSelection = query.has("service") ? query.get("service") : "0";
  // Filter Environments
  const [serviceFilter, setServiceFilter] = useState(serviceSelection);


  // Filter State 
  const [stateFilter, setStateFilter] = useState(stateSelection);

  // gewähltes Release aus URL Parametern
  const releaseSelection = query.has("release") ? query.get("release") : "0";
  //Filter Release 
  const [releaseFilter, setReleaseFilter] = useState(releaseSelection);


  useEffect(() => {
    const fetchUrl = '/jsonapi/node/release_deployment';
    const defaultFilter = '?include=field_deployed_release,field_prev_release,field_service,field_service.release_type&page[limit]=20&sort[sort-date][path]=field_date_deployed&sort[sort-date][direction]=DESC';
    // Always apply default filter.
    let url = fetchUrl + defaultFilter;
    // Archiv-Filter
    let archivedFilter = "&filter[field_is_archived]=0";
    if (isArchived == "1" ) {
      archivedFilter = "&filter[field_is_archived]=1";
    }
    url += archivedFilter;
    // Landes-Filter (nur für Gruppen- und Site-Admins)
    if (stateFilter && stateFilter !== "1") {
      url += "&filter[field_user_state]=" + stateFilter;
    }
    if (environmentFilter !== "0") {
      url += "&filter[field_environment]=" + environmentFilter;
    }
    if (serviceFilter !== "0") {
      //url += "&filter[relationship.field_service]=" + serviceFilter;
      url += "&filter[field_service.drupal_internal__nid]=" + serviceFilter;
    }
    if (releaseFilter !== "0") {
      url += "&filter[field_deployed_release.drupal_internal__nid]=" + releaseFilter;
    }

    const headers = new Headers({
      Accept: 'application/vnd.api+json',
    });

    fetch(url, { headers })
      .then(response => response.json())
      .then(results => {
        // console.log(results);
        let deploymentData = results.data.map((deployment) => {
          // Search included relationship for associated service name.
          const serviceId = deployment.relationships.field_service.data.id;
          const relatedServiceObject = results.included.find(({ id }) => id === serviceId);
          const relatedServiceName = relatedServiceObject.attributes.title;
          const relatedServiceNid = relatedServiceObject.attributes.drupal_internal__nid;
          deployment.service = relatedServiceName;
          deployment.serviceNid = relatedServiceNid;

          // Search included relationship for associated release name.
          const releaseId = deployment.relationships.field_deployed_release.data.id;
          const relatedReleaseObject = results.included.find(({ id }) => id === releaseId);
          const relatedRelaseName = relatedReleaseObject.attributes.title;
          const relatedRelaseNid = relatedReleaseObject.attributes.drupal_internal__nid;
          deployment.release = relatedRelaseName;
          deployment.releaseNid = relatedRelaseNid;

          return deployment;
        });
        // console.log('Läufer angekommen: ' + runner + ". Insgesamt: " + fetchCount.current);
        // if (runner === fetchCount.current) {
          // if (releaseData.length === 0) setTimeout(true);
          setData(deploymentData);
        // }
      })
      .catch(error => console.log("error", error));
  }, [isArchived, stateFilter, environmentFilter, serviceFilter, releaseFilter]);

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
      search: params.toString() 
    });
  }, [stateFilter, isArchived, environmentFilter, serviceFilter, releaseFilter]);

  // Experiment: Releases für Filter via jsonapi-calls holen? Nachteil: Bei vielen
  // Releases (>50) sind mehrere calls erforderlich.
  // useEffect(() => {
  //   if (serviceFilter !== "0") {
  //     const headers = new Headers({
  //       Accept: 'application/vnd.api+json',
  //     });
  //     const fetchUrl = '/jsonapi/node/release';
  //     const defaultFilter = '?include=field_relese_services&page[limit]=20&sort[sort-date][path]=field_date_deployed&sort[sort-date][direction]=DESC';
  //     fetch(url, { headers });
  //   }
  // }, [serviceFilter]);

  return (
    <div>
      <Nav bsStyle="tabs" activeKey={activeKey} onSelect={k => setIsArchived(k)}>
        <NavItem eventKey="0">
          Eingesetzt
        </NavItem>
        <NavItem eventKey="1">
          Archiv
        </NavItem>
      </Nav>
      <EinsatzmeldungsFilter 
        stateFilter={stateFilter} 
        setStateFilter={setStateFilter} 
        environmentFilter={environmentFilter} 
        setEnvironmentFilter={setEnvironmentFilter} 
        serviceFilter={serviceFilter} 
        setServiceFilter={setServiceFilter} 
        releaseFilter={releaseFilter} 
        setReleaseFilter={setReleaseFilter}
      />
      <EinsatzmeldungsTabelle data={data} />
    </div>
  )
}
