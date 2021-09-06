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
    setFilteredData(newData);
  }, [props.filterState, props.data])


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
          if (props.deploymentHistory.indexOf(deployment.releaseNid.toString()) >= 0) {
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
              isArchived={props.filterState.status}
            />
          );
        }) : <tr><td colSpan="6"><center>Daten werden geladen ... <span className="glyphicon glyphicon-refresh glyphicon-spin" role="status"><span className="sr-only">Lade...</span></span></center></td></tr> }
        </tbody>
      </Table>
    { (props.filterState.status === "1") &&
      <Pagination bsSize="small">{items}</Pagination>
    }
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

function SimplePager(props) {
  console.log("Datacount: ", props.count);
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