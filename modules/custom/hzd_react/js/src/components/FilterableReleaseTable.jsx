import React, { useState, useEffect } from 'react';
import useQuery from '../hooks/hooks';
import { fetchWithCSRFToken } from "../utils/fetch";
import ReleaseTable from './ReleaseTable';
import ReleaseFilter from './ReleaseFilter';
import ReleasePager from './ReleasePager';
import { useHistory } from 'react-router-dom';

function FilterableReleaseTable() {
  // Um URL Parameter ändern zu können.
  const history = useHistory();

  // Release-Daten.
  const [data, setData] = useState([]);

  // True, wenn keine Daten geladen werden können.
  const [timeout, setTimeout] = useState(false);

  // Verfahrensfilter basierend auf URL Parameter.
  const query = useQuery();
  const serviceSelection = query.has("service") ? query.get("service") : "default";
  const [serviceFilter, setServiceFilter] = useState(serviceSelection);

  // Funktion zum ändern des Verfahrensfilter. Ausgelöst durch
  // ReleaseFilter-Komponente.
  const handleServiceFilter = (e) => {
    setServiceFilter(e.target.value);
  }

  const handleReset = () => {
    console.log("Reset-Handled");
    setServiceFilter('default');
  }

  if (serviceFilter !== 'default') {
    var serviceFilterUrl = '&filter[field_relese_services.title][value]=' + serviceFilter;
  }

  const fetchData = () => {
    const fetchUrl = '/jsonapi/node/release';
    const defaultFilter = '?include=field_relese_services&page[limit]=20&sort[sort-date][path]=field_date&sort[sort-date][direction]=DESC&filter[field_release_type]=1';
    let url = fetchUrl + defaultFilter;
    url += (typeof serviceFilterUrl !== 'undefined') ? serviceFilterUrl : '';
    const headers = new Headers({
      Accept: 'application/vnd.api+json',
    });
    const csrfUrl = `/session/token?_format=json`;
    setData([]);
    setTimeout(false);
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
        if (releaseData.length === 0) setTimeout(true);
        setData(releaseData);
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
    history.push({ search: params.toString() });

    fetchData();
  }, [serviceFilter]);

  return (
    <div>
      <ReleaseFilter 
        serviceFilter={serviceFilter} 
        handleServiceFilter={handleServiceFilter}
        handleReset={handleReset}
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
  //   fetch('/jsonapi/node/business_service_release')
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