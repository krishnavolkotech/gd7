import React from 'react'
import {Table} from 'react-bootstrap';

export default function EinsatzmeldungsTabelle({ data, timeout }) {

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
        const date = new Date(deployment.attributes.field_date_deployed);
        const localeDate = date.toLocaleDateString('de-DE', {
          year: "numeric",
          month: "2-digit",
          day: "2-digit",
        });
        return(
          <tbody>
            <td>{global.drupalSettings.states[deployment.attributes.field_user_state]}</td>
            <td>{global.drupalSettings.environments[deployment.attributes.field_environment]}</td>
            <td>{deployment.service}</td>
            <td>{deployment.release}</td>
            <td>{localeDate}</td>
            <td><button>Aktion</button></td>
          </tbody>
        );
      }) : <tbody><td colSpan="6"><center>Daten werden geladen ... <span className="glyphicon glyphicon-refresh glyphicon-spin" role="status"><span className="sr-only">Lade...</span></span></center></td></tbody> }
    </Table>
  );
}
