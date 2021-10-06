import React, {useState, useEffect} from 'react'
import {Table, Button, Pagination, Pager} from 'react-bootstrap';
import DeployedReleasesTableRow from "./DeployedReleasesTableRow";

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
    const direction = props.filterState.sortOrder == "ASC" ? 1 : -1;
    if (props.filterState.sortBy == "field_date_deployed_value") {
      newData.sort((a, b) => {
        if (a.date > b.date) return direction;
        if (a.date < b.date) return -direction;
      });
    }
    if (props.filterState.sortBy == "field_environment_value") {
      newData.sort((a, b) => {
        if (a.environment > b.environment) return direction;
        if (a.environment < b.environment) return -direction;
      });
    }
    // Sort releases too, if service was selected for sorting.
    if (props.filterState.sortBy == "title") {
      newData.sort((a, b) => {
        if (a.service > b.service) return direction;
        if (a.service < b.service) return -direction;
        if (a.release > b.release) return direction;
        if (a.release < b.release) return -direction;
      });
    }
    if (props.filterState.sortBy == "title_1") {
      newData.sort((a, b) => {
        if (a.release > b.release) return direction;
        if (a.release < b.release) return -direction;
      });
    }
    if (props.filterState.sortBy == "field_state_list_value") {
      newData.sort((a, b) => {
        if (parseInt(a.state) > parseInt(b.state)) return direction;
        if (parseInt(a.state) < parseInt(b.state)) return -direction;
      });
    }
    return newData;
  }

  const handlePagination = (e) => {
    if (e.target.name == "next") {
      props.setPage(props.page + 1);
      props.setCount(props.count + 1);
    }

    if (e.target.name == "previous") {
      props.setPage(props.page - 1);
      props.setCount(props.count + 1);
    }
  }

  const tableHead = (
    <thead>
      <tr>
        <th>Land</th>
        <th>Umgebung</th>
        <th>Verfahren</th>
        <th>Release</th>
        <th>Eingesetzt am</th>
        <th>Aktion</th>
      </tr>
    </thead>
  );

  const limit = 50;
  const dataLength = filteredData.length;
  const pageCount = Math.ceil(dataLength / limit);
  const offset = (props.page - 1) * limit;
  const end = offset + limit;
  let tableData = dataLength > limit ? filteredData.slice(offset, end) : filteredData;

  if (props.timeout === true) {
    return(
      <div>
        <Table hover>
          { tableHead }
          <tbody>
            <tr>
              <td colSpan="6"><center>Keine Daten gefunden.</center></td>
            </tr>
          </tbody>
        </Table>
        { (props.filterState.status === "2") &&
        <SimplePager
          page={props.page}
          count={tableData.length}
          handlePagination={handlePagination}
        />
        }
      </div>
    );
  }

  const items = [];
  for (let number = 1; number <= pageCount; number++) {
    items.push(
      <Pagination.Item active={number === props.page} onClick={() => props.setPage(number)}>{number}</Pagination.Item>
    );
  }
  return(
    <div>
      <Table hover>
        { tableHead }
        <tbody>
          {tableData.length ? tableData.map(deployment => {
          let highlight = "";
          if (props.deploymentHistory.indexOf(parseInt(deployment.nid)) >= 0) {
            highlight = "success";
          }
          return (
            <DeployedReleasesTableRow
              deployment={deployment}
              handleAction={props.handleAction}
              key={deployment.id}
              highlight={highlight}
              handleArchive={props.handleArchive}
              handleEdit={props.handleEdit}
              status={props.filterState.status}
              handleView={props.handleView}
            />
          );
        }) : <tr><td colSpan="6"><center>Daten werden geladen ... <span className="glyphicon glyphicon-refresh glyphicon-spin" role="status"><span className="sr-only">Lade...</span></span></center></td></tr> }
        </tbody>
      </Table>
    { (props.filterState.status === "1") &&
      <Pagination bsSize="small">{items}</Pagination>
    }
    { (props.filterState.status !== "1") &&
        <SimplePager
          page={props.page}
          count={tableData.length}
          handlePagination={handlePagination}
        />
    }
    </div>
  );
}

function SimplePager(props) {
  return (
    <div>
      <Pager>
        <Pager.Item disabled={props.page == 1} previous name="previous" onClick={props.handlePagination}>
          &larr; Vorherige Seite
        </Pager.Item>
        <Pager.Item disabled={props.count < 50} next name="next" onClick={props.handlePagination}>
          Nächste Seite &rarr;
        </Pager.Item>
      </Pager>
      <p>Aktuelle Seite: {props.page}</p>
    </div>
  );
}