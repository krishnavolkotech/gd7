import React, {useState} from 'react'
import {Table, Button, Pagination, Pager} from 'react-bootstrap';
import DeployedReleasesTableRow from "./DeployedReleasesTableRow";

export default function DeployedReleasesTable(props) {

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
  
  if (props.timeout === true) {
    return(
      <Table hover>
        { tableHead }
        <tbody>
          <tr>
            <td colSpan="6"><center>Keine Daten gefunden.</center></td>
          </tr>
        </tbody>
      </Table>
    );
  }
  const limit = 50;
  const dataLength = props.data.length;
  const pageCount = Math.ceil(dataLength / limit);
  const offset = (props.page - 1) * limit;
  const end = offset + limit;
  let tableData = dataLength > limit ? props.data.slice(offset, end) : props.data;

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
              isArchived={props.isArchived}
            />
          );
        }) : <tr><td colSpan="6"><center>Daten werden geladen ... <span className="glyphicon glyphicon-refresh glyphicon-spin" role="status"><span className="sr-only">Lade...</span></span></center></td></tr> }
        </tbody>
      </Table>
    { (props.isArchived === "0") &&
      <Pagination bsSize="small">{items}</Pagination>
    }
    { (props.isArchived === "1") &&
      <div>
        <Pager>
          <Pager.Item disabled={props.page == 1} previous name="previous" onClick={handlePagination}>
            &larr; Vorherige Seite
          </Pager.Item>
          <Pager.Item next name="next" onClick={handlePagination}>
            Nächste Seite &rarr;
          </Pager.Item>
        </Pager>
        <p>Aktuelle Seite: {props.page}</p>
      </div>
    }
    </div>
  );
}
