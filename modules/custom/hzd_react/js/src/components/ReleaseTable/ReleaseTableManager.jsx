import React, {useState, useEffect, useRef} from 'react';
import ReleaseFilter from './ReleaseFilter';
import ReleaseTable from './ReleaseTable';

export default function ReleaseTableManager(props) {
  /** @const {number} fetchCount - Ensures that the latest fetch gets processed. */
  const fetchCount = useRef(0);

  /** @const {array} releases - Array mit den Release-Objekten. */
  const [releases, setReleases] = useState([]);

  /** @const {array} filterReleases - Array mit Releases zur Release-Filter-Befüllung. */
 const [filterReleases, setFilterReleases] = useState([]);

  const initialFilterState = {
    "type": "459",
    "service": "0",
    "release": "0",
    "status": props.activeKey,
    "sortBy": "field_date",
    "sortOrder": "-",
    "items_per_page": "20",
  };

  /**
   * The filter state object.
   * @property {Object} filterState - The object holding the filter state.
   * @property {string} filterState.type - The release type.
   * @property {string} filterState.service - The service id.
   * @property {string} filterState.release - The release id.
   * @property {string} filterState.status - The release status.
   * @property {string} filterState.sortBy - The release status.
   * @property {string} filterState.sortOrder - The release status.
   * @property {string} filterState.items_per_page - The items per page.
   */
  const [filterState, setFilterState] = useState(initialFilterState);

  const [disableReleaseFilter, setDisableReleaseFilter] = useState(true);

  const [loadingReleases, setLoadingReleases] = useState(true);

  const [page, setPage] = useState(1);

  useEffect(() => {
    setFilterState(prev => ({ ...prev, "status": props.activeKey}))
  }, [props.activeKey])

  /**
   * Fetches new releases if the filter or page is changed.
   */
  useEffect(() => {
    setLoadingReleases(true);
    setReleases([]);
    fetchReleases();
    // setPage(1);
  }, [filterState, page]);

  /**
   * Fetches the releases for the release filter, if a service is selected.
   */
  useEffect(() => {
    setDisableReleaseFilter(true);
    if (filterState.service != "0") {
      fetchReleasesForFilter();
    }
  }, [filterState.service])

  /**
   * Fetch the releases for the release table.
   */
  function fetchReleases() {
    let url = '/jsonapi/node/release';
    url += '?sort=' + filterState.sortOrder + filterState.sortBy;
    url += '&include=field_relese_services';

    url += '&filter[field_release_type]=' + filterState.type;

    const limit = filterState.items_per_page;
    url += '&page[offset]=' + ((page - 1) * limit);
    url += '&page[limit]=' + limit;



    // Apply group specific service filter.
    // @todo Unterscheidung KONSENS / BestFakt
    if (filterState.type in global.drupalSettings.services && filterState.service == "0") {
      url += '&filter[service-filter][condition][path]=field_relese_services.drupal_internal__nid';
      url += '&filter[service-filter][condition][operator]=IN';
      let count = 0;
      for (const key in global.drupalSettings.services[filterState.type]) {
        count++;
        url += '&filter[service-filter][condition][value]['+ count + ']=' + key;
      }
    }
    else {
      url += '&filter[field_relese_services.drupal_internal__nid]=' + filterState.service;
    }

    if (filterState.release != "0") {
      url += '&filter[drupal_internal__nid]=' + filterState.release;
    }

    url += '&filter[field_release_type]=' + filterState.status;

    const headers = new Headers({
      Accept: 'application/vnd.api+json',
    });

    fetchCount.current++;
    const runner = fetchCount.current;

    fetch(url, { headers })
      .then(response => response.json())
      .then(response => {
        // console.log(response);
        if (runner === fetchCount.current) {
          const result = addRelationshipData(response);
          setLoadingReleases(false);
          setReleases(result);
        }
      })
      .catch(error => {
        console.log(error);
        setLoadingReleases(false);
      });
    }

    /**
     * Fetches the releases for the release filter.
     */
  function fetchReleasesForFilter() {
    let url = "/api/v1/releases/" + filterState.service;
    const headers = new Headers({
      Accept: 'application/vnd.api+json',
    });
    fetch(url, { headers })
      .then(response => response.json())
      .then(result => {
        setFilterReleases(result);
        setDisableReleaseFilter(false);
      })
      .catch(error => console.log(error));
  }

  /**
   * Adds service information to the release object after fetching.
   */
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

  /**
   * Resets the filter state.
   */
  function handleReset() {
    setFilterState(initialFilterState);
  }

  return (
    <div>
      <p><a href="/release-management/beschreibung-des-status-der-dsl-konsens" target="_blank"><span class="glyphicon glyphicon-question-sign"></span> Erläuterung Status</a></p>
      <ReleaseFilter
        filterState={filterState}
        setFilterState={setFilterState}
        handleReset={handleReset}
        filterReleases={filterReleases}
        disableReleaseFilter={disableReleaseFilter}
      />
      <ReleaseTable 
        releases={releases}
        loadingReleases={loadingReleases}
        page={page}
        setPage={setPage}
        filterState={filterState}
      />
    </div>
  );
}
