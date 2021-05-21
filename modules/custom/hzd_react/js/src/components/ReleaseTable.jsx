import React from 'react';
import ReleaseActions from './ReleaseActions';
import EarlyWarningIndication from './EarlyWarningIndication';
import { Table } from 'react-bootstrap';

function ReleaseTable({ releases, timeout }) {
  const tableHead = (
    <thead>
      <tr>
        <th>Verfahren</th>
        <th>Release</th>
        <th>Status</th>
        <th>Datum</th>
        <th>Early Warnings</th>
        <th>Aktionen</th>
      </tr>
    </thead>
  );

  if (timeout === true) {
    return (
      <Table condensed hover>
        { tableHead}
        <tbody>
          <td colSpan="6"><center>Keine Daten gefunden.</center></td>
        </tbody>
      </Table>
    );
  }

  if (releases.length === 0) {
    return (
      // <span>Daten werden geladen ...<span className="glyphicon glyphicon-refresh glyphicon-spin" role="status"><span className="sr-only">Lade...</span></span></span>;
    <Table condensed hover>
      { tableHead }
      <tbody>
          <td colSpan="6"><center>Daten werden geladen ... <span className="glyphicon glyphicon-refresh glyphicon-spin" role="status"><span className="sr-only">Lade...</span></span></center></td>
      </tbody>
    </Table>
    );
  }

  return (
    <Table condensed hover>
      { tableHead }
      <tbody>
        {releases.map((release) => {
          // Datumsformatierung von Unix.
          const unixTimestamp = release.attributes.field_date
          const milliseconds = unixTimestamp * 1000
          const dateObject = new Date(milliseconds)
          const humanDateFormat = dateObject.toLocaleString('de-DE')
          return (
            <tr>
              <td>{release.service}</td>
              <td>{release.attributes.title}</td>
              <td>{release.attributes.field_status}</td>
              <td>{humanDateFormat}</td>
              <EarlyWarningIndication releaseNid={release.attributes.drupal_internal__nid} service={release.serviceNid} />
              <td><ReleaseActions /></td>
            </tr>
          )
        })}
      </tbody>
    </Table>
  );
}

export default ReleaseTable;