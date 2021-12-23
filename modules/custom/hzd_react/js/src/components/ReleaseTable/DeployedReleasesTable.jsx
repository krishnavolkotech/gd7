import React, { useState, useEffect } from 'react'
import { Table, Button, Pagination, Pager } from 'react-bootstrap';
import DeployedReleasesTableRow from './DeployedReleasesTableRow';
import SimplePager from '../SimplePager';
import { useHistory } from 'react-router-dom';

export default function DeployedReleasesTable(props) {
  const [filteredData, setFilteredData] = useState([]);
  
  useEffect(() => {
    let newData = props.data;
    if (props.filterState.product.length > 1) {
      newData = props.data.filter(deployment => {
        return deployment.release.includes(props.filterState.product + "_");
      });
    }
    if (props.data.length > 0 && newData.length === 0) {
      props.setTimeout(true);
    }
    else if (newData.length > 0) {
      props.setTimeout(false);
    }
    newData = applySorting(newData);
    setFilteredData(newData);
  }, [props.filterState, props.data])

  // Applies javascript based sorting possibly on top of default view sorting.
  const applySorting = (newData) => {
    const direction = props.filterState.deploymentSortOrder == "ASC" ? 1 : -1;
    if (props.filterState.deploymentSortBy == "field_date_deployed_value") {
      newData.sort((a, b) => {
        if (a.date > b.date) return direction;
        if (a.date < b.date) return -direction;
      });
    }
    if (props.filterState.deploymentSortBy == "field_environment_value") {
      newData.sort((a, b) => {
        if (a.environment > b.environment) return direction;
        if (a.environment < b.environment) return -direction;
      });
    }
    // Sort by service - sorts releases too.
    if (props.filterState.deploymentSortBy == "title") {
      newData.sort((a, b) => {
        if (a.service > b.service) return direction;
        if (a.service < b.service) return -direction;
        return compareReleases(a, b, direction);
      });
    }
    // Sort by release.
    if (props.filterState.deploymentSortBy == "title_1") {
      newData.sort((a, b) => {
        return compareReleases(a, b, direction);
      });
    }
    if (props.filterState.deploymentSortBy == "field_state_list_value") {
      newData.sort((a, b) => {
        if (parseInt(a.state) > parseInt(b.state)) return direction;
        if (parseInt(a.state) < parseInt(b.state)) return -direction;
      });
    }
    return newData;
  }

  /**
   * Compares two release names for sorting.
   * 
   * Sorting criteria hierarchy:
   *  1. Matching product on top.
   *  2. Descending product name.
   *  3. Ascending version number.
   * 
   * @param {object} a - The first release object.
   * @param {object} b - The second release object.
   * @param {number} direction - The sorting direction (0 or 1).
   * @returns {number} - 1, -1 or 0.
   */
  const compareReleases = (a, b, direction) => {
    const productA = a.release.substring(0, a.release.indexOf('_') + 1);
    const productB = b.release.substring(0, b.release.indexOf('_') + 1);
    const versionA = a.release.substring(a.release.indexOf('_') + 1);
    const versionB = b.release.substring(b.release.indexOf('_') + 1);
    // First: sort by name.
    if (productA > productB) return direction;
    if (productA < productB) return -direction;
    // Second: sort by version number.
    const partsA = versionA.split('.')
    const partsB = versionB.split('.')
    for (var i = 0; i < partsB.length; i++) {
      const vA = ~~partsA[i] // parse int
      const vB = ~~partsB[i] // parse int
      if (vA > vB) return direction
      if (vA < vB) return -direction
    }
    if (versionA > versionB) {
      return direction;
    }
    if (versionA < versionB) {
      return -direction;
    }
    return 0;
  }

  const tableHead = (
    <thead>
    {!props.detail &&
      <tr>
        <th>Land</th>
        <th>Umgebung</th>
        <th>Verfahren</th>
        <th>Release</th>
        <th>Eingesetzt am</th>
        <th>Aktion</th>
      </tr>
      }
      {props.detail &&
        <tr>
          <th>Land</th>
          <th>Umgebung</th>
          <th>Release</th>
          <th>Eingesetzt am</th>
          <th>Vorgängerrelease</th>
          <th>ID</th>
          <th>AD</th>
          <th>Auffälligkeiten</th>
        </tr>
      }
    </thead>
  );

  if (props.timeout === true) {
    return (
      <div>
        <Table hover>
          {tableHead}
          <tbody>
            <tr>
              <td colSpan="6"><center>Keine Daten gefunden.</center></td>
            </tr>
          </tbody>
        </Table>
        <SimplePager
          page={props.page}
          setPage={props.setPage}
          count={filteredData.length}
          setCount={props.setCount}
          items_per_page={props.filterState.items_per_page}
        />
      </div>
    );
  }

  let span = 6;
  if (props.detail) {
    span = 8;
  }

  return (
    <div>
      <Table hover>
        {tableHead}
        <tbody>
          {filteredData.length ? filteredData.map(deployment => {
            return (
              <DeployedReleasesTableRow
                key={"row-" + deployment.nid}
                deployment={deployment}
                deploymentStatus={props.filterState.deploymentStatus}
                detail={props.detail}
              />
            );
          }) : <tr><td colSpan={span}><center>Daten werden geladen ... <span className="glyphicon glyphicon-refresh glyphicon-spin" role="status"><span className="sr-only">Lade...</span></span></center></td></tr>}
        </tbody>
      </Table>
      {props.filterState.items_per_page !== "All" &&
      <SimplePager
        page={props.page}
        setPage={props.setPage}
        count={filteredData.length}
        setCount={props.setCount}
        items_per_page={props.filterState.items_per_page}
        />
      }
    </div>
  );
}