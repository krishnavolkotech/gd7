import React from 'react';
import ReleaseActions from './ReleaseActions';
import EarlyWarningIndication from './EarlyWarningIndication';

function ReleaseTable({ releases }) {
  if (releases.length === 0) {
    return <span>Daten werden geladen ...<span className="glyphicon glyphicon-refresh glyphicon-spin" role="status"><span className="sr-only">Lade...</span></span></span>;
  }

  return (
    <table>
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
      <tbody>
        {releases.map((release) => {
          const unixTimestamp = release.attributes.field_date

          const milliseconds = unixTimestamp * 1000 // 1575909015000

          const dateObject = new Date(milliseconds)

          const humanDateFormat = dateObject.toLocaleString('de-DE') //2019-12-9 10:30:15
          return (
            <tr>
              {/* <VerfahrenZelle url={release.relationships.field_relese_services.links.related.href} /> */}
              <td>{release.service}</td>
              <td>{release.attributes.title}</td>
              <td>{release.attributes.field_status}</td>
              <td>{humanDateFormat}</td>
              {/* <td>EW_PLATZHALTER</td> */}
              <EarlyWarningIndication releaseNid={release.attributes.drupal_internal__nid} service={release.serviceNid} />
              <td><ReleaseActions /></td>
            </tr>
          )
        })}
      </tbody>

    </table>
  );
}

export default ReleaseTable;