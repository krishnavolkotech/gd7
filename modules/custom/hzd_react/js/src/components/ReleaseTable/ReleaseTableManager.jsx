import React, {useState, useEffect} from 'react';
import ReleaseFilter from './ReleaseFilter';
import ReleaseTable from './ReleaseTable';

export default function ReleaseTableManager() {
  /** @const {array} releases - Array mit den Release-Objekten. */
  const [releases, setReleases] = useState([]);

  useEffect(() => {
    console.log(global.drupalSettings);
    fetchReleases();
  }, []);

  function fetchReleases() {
    let url = '/jsonapi/node/release?filter[field_release_type]=2&sort=-field_date';
    url += '&include=field_relese_services';

    // Apply group specific service filter.
    // @todo Unterscheidung KONSENS / BestFakt
    if ("459" in global.drupalSettings.services) {
      url += '&filter[service-filter][condition][path]=field_relese_services.drupal_internal__nid';
      url += '&filter[service-filter][condition][operator]=IN';
      let count = 0;
      for (const key in global.drupalSettings.services['459']) {
        count++;
        url += '&filter[service-filter][condition][value]['+ count + ']=' + key;
      }
    }
    console.log(url);

    const headers = new Headers({
      Accept: 'application/vnd.api+json',
    });

    fetch(url, { headers })
      .then(response => response.json())
      .then(response => {
        // console.log(response);
        const result = addRelationshipData(response);
        setReleases(result);
      })
      .catch(error => console.log(error));
    }

  function addRelationshipData(response) {
    return response.data.map(release => {
      // Add Service name.
      const serviceObject = response.included.find(relationship => relationship.id == release.relationships.field_relese_services.data.id);
      const serviceName = serviceObject.attributes.title;
      const serviceNid = serviceObject.attributes.drupal_internal__nid;
      release.serviceName = serviceName;
      release.serviceNid = serviceNid;
      return release;
    })
  }
  console.log(releases);
  return (
    <div>
      ReleaseTableManager
      <ReleaseFilter />
      <ReleaseTable 
        releases={releases}
      />
    </div>
  )
}
