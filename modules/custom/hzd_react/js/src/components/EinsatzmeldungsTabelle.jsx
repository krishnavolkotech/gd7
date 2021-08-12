import React from 'react'
import {Table, Button} from 'react-bootstrap';
import EinsatzmeldungsZeile from "./EinsatzmeldungsZeile";

export default function EinsatzmeldungsTabelle({ data, timeout, handleAction }) {

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
  
  if (timeout === true) {
    return(
      <Table hover>
        { tableHead }
        <tbody>
          <td colSpan="6"><center>Keine Daten gefunden.</center></td>
        </tbody>
      </Table>
    );
  }

  return(
    <Table hover>
      { tableHead }
      { data.length ? data.map(deployment => {
        return(
          <EinsatzmeldungsZeile deployment={deployment} handleAction={handleAction} />
        );
      }) : <tbody><td colSpan="6"><center>Daten werden geladen ... <span className="glyphicon glyphicon-refresh glyphicon-spin" role="status"><span className="sr-only">Lade...</span></span></center></td></tbody> }
    </Table>
  );
}
