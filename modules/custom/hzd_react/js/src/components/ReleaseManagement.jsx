import React, { useState, useEffect } from 'react';
import { fetchWithCSRFToken } from "../utils/fetch";
import ReleaseTable from './ReleaseTable';
import ReleaseFilter from './ReleaseFilter';
import ReleasePager from './ReleasePager';

function FilterableReleaseTable() {
  const path = window.location.pathname;
  const [ data, setData ] = useState([]);
      
  useEffect(() => {
    console.log("Fetching data ...");
    // let fetchUrl = '/jsonapi/node/release';
//     sort[sort-created][path]=created
// sort[sort-created][direction]=DESC
    let fetchUrl = '/jsonapi/node/release?include=field_relese_services&page[offset]=0&page[limit]=20&sort[sort-changed][path]=changed&sort[sort-changed][direction]=DESC';
    let filter = '?';
    const headers = new Headers({
      Accept: 'application/vnd.api+json',
    });
    const csrfUrl = `/session/token?_format=json`;
    fetch(fetchUrl, {headers})
      .then(response => response.json())
      .then(results => {
        let releaseData = results.data.map(( release ) => {
          // Search included relationship for associated service name.
          const relId = release.relationships.field_relese_services.data.id;
          const relatedServiceObject = results.included.find(({ id }) => id === relId);
          const relatedServiceName = relatedServiceObject.attributes.field_release_name;
          const relatedServiceNid = relatedServiceObject.attributes.drupal_internal__nid;
          console.log(relatedServiceNid);
          release.service = relatedServiceName;
          release.serviceNid = relatedServiceNid;
          return release;
        });
        setData(releaseData);
      })
      .catch(error => console.log("error", error));
  }, []);

  return (
    <div>
      <ReleaseFilter />
      <ReleaseTable releases={data}/>
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