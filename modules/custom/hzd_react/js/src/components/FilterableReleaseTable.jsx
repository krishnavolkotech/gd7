import React, { useState, useEffect, useRef } from 'react';
import useQuery from '../hooks/hooks';
import { fetchWithCSRFToken } from "../utils/fetch";
import ReleaseTable from './ReleaseTable';
import ReleaseFilter from './ReleaseFilter';
import ReleasePager from './ReleasePager';
import { useHistory } from 'react-router-dom';
import ReleaseNavigation from './ReleaseNavigation';

function FilterableReleaseTable() {
  const fetchCount = useRef(0);
  // Um URL Parameter ändern zu können.
  const history = useHistory();
  const query = useQuery();
  // Release-Daten.
  const [data, setData] = useState([]);
  // True, wenn keine Daten geladen werden können.
  const [timeout, setTimeout] = useState(false);
  // Verfahrenstypfilter basierend auf URL Parameter. (KONSENS / BestFakt)
  const serviceTypeSelection = query.has("service_type") ? query.get("service_type") : 459;
  // Filter für Releasetyp (1 - Bereitgestellt usw.) aus URL Parameter.
  const releaseTypeSelection = query.has("release_type") ? query.get("release_type") : 1;
  // Verfahrensfilter aus URL Parameter.
  const serviceSelection = query.has("service") ? query.get("service") : "default";
  // Verfahrensfilter aus URL Parameter.
  const releaseSelection = query.has("release") ? query.get("release") : "default";

  const [serviceType, setServiceType] = useState(serviceTypeSelection);
  const [releaseType, setReleaseType] = useState(releaseTypeSelection);
  const [serviceFilter, setServiceFilter] = useState(serviceSelection);
  const [releaseFilter, setReleaseFilter] = useState(releaseSelection);

  const handleServiceType = (e) => {
    setServiceType(e);
    setReleaseType(1);
    setServiceFilter('default');
  }

  const handleReleaseType = (e) => {
    setReleaseType(e);
  }
  // Funktion zum ändern des Verfahrensfilter. Ausgelöst durch
  // ReleaseFilter-Komponente.
  const handleServiceFilter = (e) => {
    setServiceFilter(e.target.value);
  }

  const handleReleaseFilter = (e) => {
    setReleaseFilter(e.target.value);
  }

  const handleReset = () => {
    setServiceFilter('default');
    setReleaseFilter('default');
  }

  const serviceTypeUrl = '&filter[field_relese_services.release_type.drupal_internal__tid]=' + serviceType;
  const releaseTypeUrl = '&filter[field_release_type]=' + releaseType;

  if (serviceFilter !== 'default') {
    var serviceFilterUrl = '&filter[field_relese_services.title][value]=' + serviceFilter;
  }

  const fetchData = () => {
    const fetchUrl = '/jd7kfn9dm32ni/node/release';
    const defaultFilter = '?include=field_relese_services,field_relese_services.release_type&page[limit]=20&sort[sort-date][path]=field_date&sort[sort-date][direction]=DESC';
    // Always apply default filter.
    let url = fetchUrl + defaultFilter;
    // Always apply service type filter.
    url += serviceTypeUrl;
    // Always apply release type filter.
    url += releaseTypeUrl;
    // Apply service filter, if service filter is set.
    url += (typeof serviceFilterUrl !== 'undefined') ? serviceFilterUrl : '';

    const headers = new Headers({
      Accept: 'application/vnd.api+json',
    });

    const csrfUrl = `/session/token?_format=json`;
    setData([]);
    setTimeout(false);
    fetchCount.current++;
    const runner = fetchCount.current;
    fetch(url, { headers })
      .then(response => response.json())
      .then(results => {
        let releaseData = results.data.map((release) => {
          // Search included relationship for associated service name.
          const relId = release.relationships.field_relese_services.data.id;
          const relatedServiceObject = results.included.find(({ id }) => id === relId);
          const relatedServiceName = relatedServiceObject.attributes.field_release_name;
          const relatedServiceNid = relatedServiceObject.attributes.drupal_internal__nid;
          release.service = relatedServiceName;
          release.serviceNid = relatedServiceNid;
          return release;
        });
        if (runner === fetchCount.current) {
          if (releaseData.length === 0) setTimeout(true);
          setData(releaseData);
        }
      })
      .catch(error => console.log("error", error));
  }

  useEffect(() => {
    // Change URL Params
    const params = new URLSearchParams();
    if (serviceFilter !== "default") {
      params.append("service", serviceFilter);
    } else {
      params.delete("service");
    }
    if (releaseFilter !== "default") {
      params.append("release", releaseFilter);
    } else {
      params.delete("release");
    }
    if (serviceType !== 459) {
      params.append("service_type", serviceType);
    } else {
      params.delete("service_type");
    }
    if (releaseType !== 1) {
      params.append("release_type", releaseType);
    } else {
      params.delete("release_type");
    }
    history.push({ search: params.toString() });

    fetchData();
  }, [serviceType, releaseType, serviceFilter, releaseFilter]);

  return (
    <div>
      <ReleaseNavigation
        serviceType={serviceType}
        handleServiceType={handleServiceType}
        releaseType={releaseType}
        handleReleaseType={handleReleaseType}
      />
      <p />
      <ReleaseFilter 
        serviceFilter={serviceFilter} 
        handleServiceFilter={handleServiceFilter}
        handleReset={handleReset}
        serviceType={serviceType}
        releaseFilter={releaseFilter}
        handleReleaseFilter={handleReleaseFilter}
      />
      <ReleaseTable releases={data} timeout={timeout} />
      <ReleasePager />
    </div>
  );
}

    // fetchWithCSRFToken(csrfUrl, fetchUrl)
    //   .then(response => response.json())
    //   .then(results => this.setState({ data: results.data }))
    //   // .then(results.map(result => {
    //   //   dataArray[result.id] = result;
    //   // })
    //   .catch(error => console.log("error", error));
    
  // fetchData() {
  //   var dataArray = [];
  //   fetch('/jd7kfn9dm32ni/node/business_service_release')
  //     .then(response => response.json())
  //     .then(results => this.setState({ data: results.data }))
  //     // .then(results.map(result => {
  //     //   dataArray[result.id] = result;
  //     // })
  //     .then()
  //     .catch(error => console.log("error", error));
  //   this.render();
  // }
  // create() {
  //   let data = {
  //     "data": {
  //       "type": "node--business_service_release",
  //       "attributes": {
  //         "title": 'Ginsterdaten_1.0.0',
  //         "field_business_service": 'Ginsterdaten',
  //         "field_business_service_release": 'Ginsterdaten_1.0.0',
  //         "field_documentation_link": 'http://localhost/',
  //         "field_processing_status": "Bereitgestellt ÜnL",
  //         "field_provision_date": 1584862492,
  //       }
  //     }
  //   };
  //   var fetchOptions = {
  //     // Use HTTP PATCH for edits, and HTTP POST to create new articles.
  //     method: 'POST',
  //     credentials: 'same-origin',
  //     headers: new Headers({
  //       'Accept': 'application/vnd.api+json',
  //       'Content-Type': 'application/vnd.api+json',
  //       'Cache': 'no-cache'
  //     }),
  //     body: JSON.stringify(data),
  //   };
  // }


export default FilterableReleaseTable;