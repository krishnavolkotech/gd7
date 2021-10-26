import React from 'react';
import { Table } from 'react-bootstrap';
import ReleaseRow from './ReleaseRow';
import SimplePager from '../SimplePager';

export default function ReleaseTable(props) {
  
  const handlePagination = (e) => {
    if (e.target.name == "next") {
      props.setPage(props.page + 1);
      // props.setCount(props.count + 1);
    }

    if (e.target.name == "previous") {
      props.setPage(props.page - 1);
      // props.setCount(props.count + 1);
    }
  }

  return (
    <div>
      <Table>
        <thead>
          <tr>
            <th>Verfahren</th>
            <th>Release</th>
            <th>Status</th>
            <th>Datum</th>
            <th>Early Warnings</th>
            <th>Ei/D/L</th>
          </tr>
        </thead>
        <tbody>
          { props.loadingReleases &&
          <tr><td colSpan="6"><center>Daten werden geladen ... <span className="glyphicon glyphicon-refresh glyphicon-spin" role="status"><span className="sr-only">Lade...</span></span></center></td></tr>
          }
          {props.releases.map(release => <ReleaseRow key={"row-" + release.attributes.drupal_internal__nid} release={release} />)}
        </tbody>
      </Table>
      <SimplePager
        page={props.page}
        count={props.releases.length}
        handlePagination={handlePagination}
      />
    </div>
  )
}

