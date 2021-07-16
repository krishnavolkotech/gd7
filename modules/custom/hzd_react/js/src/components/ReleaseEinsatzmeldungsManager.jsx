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
    archivedUrl = true;
  }

  /** @const {array} data - Daten der Einsatzmeldungen. */
  const [data, setData] = useState([]);
  console.log(setData);

  /** @const {string} isArchived - Archiviert oder im Einsatz? */
  const [isArchived, setIsArchived] = useState(archivedUrl);

  let activeKey = isArchived ? "1" : "0";

  // Filter State Variablen
  const [stateFilter, setStateFilter] = useState(stateSelection);

  useEffect(() => {
    const fetchUrl = '/jsonapi/node/release_deployment';
    const defaultFilter = '?include=field_deployed_release,field_prev_release,field_service,field_service.release_type&page[limit]=20&sort[sort-date][path]=field_date_deployed&sort[sort-date][direction]=DESC';
    // Always apply default filter.
    let url = fetchUrl + defaultFilter;
    // Archiv-Filter
    let archivedFilter = "&filter[field_is_archived]=0";
    // HIER weitermachen: isArchived wurde umgebaut von true / false zu "0" / "1"
    // -> funktioniert aber noch nicht richtig.
    if (isArchived) {
      archivedFilter = "&filter[field_is_archived]=1";
    }
    url += archivedFilter;
    // Landes-Filter (nur für Gruppen- und Site-Admins)
    if (stateFilter && stateFilter !== "1") {
      url += "&filter[field_user_state]=" + stateFilter;
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
  }, [isArchived, stateFilter]);

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
    history.push({
      pathname: pathname,
      search: params.toString() 
    });
  }, [stateFilter, isArchived]);


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
      <EinsatzmeldungsFilter stateFilter={stateFilter} setStateFilter={setStateFilter}/>
      <EinsatzmeldungsTabelle data={data} />
    </div>
  )
}
