import React, {useState, useEffect, useRef} from 'react';
import ReleaseFilter from './ReleaseFilter';
import ReleaseLegend from './ReleaseLegend';
import ReleaseTable from './ReleaseTable';

export default function ReleaseTableManager(props) {
  /** @const {number} fetchCount - Ensures that the latest fetch gets processed. */
  const fetchCount = useRef(0);
  
  /** @const {number} fetchReleasesCount - Ensures that the latest fetch gets processed. */
  const fetchReleasesCount = useRef(0);

  /** @const {array} releases - Array mit den Release-Objekten. */
  const [releases, setReleases] = useState([]);

  /** @const {array} filterReleases - Array mit Releases zur Release-Filter-Befüllung. */
 const [filterReleases, setFilterReleases] = useState([]);

  const [disableReleaseFilter, setDisableReleaseFilter] = useState(true);

  const [loadingReleases, setLoadingReleases] = useState(true);

  // Gets set to true when changing from archive to something else. Controls
  // when to refetch releases for the release filter in conjunction with an
  // effect hook.
  const [wasKeyFive, setWasKeyFive] = useState(false);

  /**
   * Fetches new releases if the filter or page is changed.
   */
  useEffect(() => {
    setLoadingReleases(true);
    setReleases([]);
    fetchReleases(0);
    // props.setPage(1);
  }, [props.filterState, props.page]);

  /**
   * Fetches the releases for the release filter, if a service is selected.
   */
  useEffect(() => {
    setDisableReleaseFilter(true);
    if (props.filterState.service != "0") {
      fetchReleasesForFilter();
    }
  }, [props.filterState.service])

  /**
   * Checks, if archived release are navigated to or from and fills product and 
   * release filters accordingly.
   */
  useEffect(() => {
    if (wasKeyFive === true) {
      // Fresh fetch for "active" releases.
      setDisableReleaseFilter(true);
      fetchReleasesForFilter();
      setWasKeyFive(false);
    }
    if (props.activeKey === "5") {
      // Fetch archived releases.
      setDisableReleaseFilter(true);
      fetchReleasesForFilter();
      setWasKeyFive(true);
    }
    return () => {
      if (props.activeKey === "5") {
        let val = {};
        val["release"] = "0";
        val["product"] = "";
        props.setFilterState(prev => ({ ...prev, ...val }));
      }
    }
  }, [props.activeKey]);
  
  /**
   * Fetch the releases for the release table.
   */
  function fetchReleases(counter = 0) {
    let url = '/jd7kfn9dm32ni/node/release';
    url += '?sort=' + props.filterState.releaseSortOrder + props.filterState.releaseSortBy;
    url += '&include=field_relese_services';

    url += '&filter[field_release_type]=' + props.filterState.type;

    if (props.filterState.items_per_page === "All") {
      url += '&page[offset]=' + counter * 50;
      url += '&page[limit]=' + 50;
    }
    else {
      const limit = props.filterState.items_per_page;
      url += '&page[offset]=' + ((props.page - 1) * limit);
      url += '&page[limit]=' + limit;
    }

    if (props.filterState.type in global.drupalSettings.services && props.filterState.service == "0") {
      // Apply group specific service filter.
      url += '&filter[service-filter][condition][path]=field_relese_services.drupal_internal__nid';
      url += '&filter[service-filter][condition][operator]=IN';
      let count = 0;
      for (const key in global.drupalSettings.services[props.filterState.type]) {
        count++;
        url += '&filter[service-filter][condition][value]['+ count + ']=' + key;
      }
    }
    else {
      url += '&filter[field_relese_services.drupal_internal__nid]=' + props.filterState.service;
    }

    if (props.filterState.product !== "") {
      // Apply the product filter (substring).
      url += '&filter[product-filter][condition][path]=title';
      url += '&filter[product-filter][condition][operator]=CONTAINS';
      url += '&filter[product-filter][condition][value]=' + props.filterState.product + "_";
    }

    if (props.filterState.release != "0") {
      url += '&filter[drupal_internal__nid]=' + props.filterState.release;
    }

    url += '&filter[field_release_type]=' + props.filterState.releaseStatus;

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
          setReleases(prev => [...prev, ...result]);
          if (props.filterState.items_per_page === "All") {
            if ('next' in response.links) {
              counter++;
              fetchReleases(counter);
            }
            else {
              // Stop spinner once all releases finished loading.
              setLoadingReleases(false);
            }
          }
          else {
            // Stop spinner on single fetch finish.
            setLoadingReleases(false);
          }
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
    let url = "/api/v1/releases/" + props.filterState.service + "?status[]=1&status[]=2";
    if (props.activeKey === "5") {
      // Fetches the archived releases.
      url += "&status[]=5";
    }
    const headers = new Headers({
      Accept: 'application/vnd.api+json',
    });

    fetchReleasesCount.current++;
    const releaseRunner = fetchReleasesCount.current;

    fetch(url, { headers })
      .then(response => response.json())
      .then(result => {
        if (releaseRunner === fetchReleasesCount.current) {
          setFilterReleases(result);
          setDisableReleaseFilter(false);
        }
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
    let val = {};
    val["service"] = "0";
    val["product"] = "";
    val["release"] = "0";
    val["releaseSortBy"] = "field_date";
    val["releaseSortOrder"] = "-";
    val["items_per_page"] = props.filterState.items_per_page;
    props.setFilterState(prev => ({ ...prev, ...val }));

  }

  return (
    <div>
      <p><a href="/release-management/beschreibung-des-status-der-dsl-konsens" target="_blank"><span class="glyphicon glyphicon-question-sign"></span> Erläuterung Status</a></p>
      <ReleaseFilter
        filterState={props.filterState}
        setFilterState={props.setFilterState}
        handleReset={handleReset}
        filterReleases={filterReleases}
        disableReleaseFilter={disableReleaseFilter}
      />
      <ReleaseLegend activeKey={props.activeKey} />
      <ReleaseTable 
        releases={releases}
        loadingReleases={loadingReleases}
        page={props.page}
        setPage={props.setPage}
        filterState={props.filterState}
      />
    </div>
  );
}
