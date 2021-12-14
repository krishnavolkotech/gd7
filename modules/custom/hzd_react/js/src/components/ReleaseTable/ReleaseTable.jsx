import React, { useState } from 'react';
import { Table } from 'react-bootstrap';
import ReleaseRow from './ReleaseRow';
import SimplePager from '../SimplePager';

export default function ReleaseTable(props) {
  const [count, setCount] = useState(1);
  
  return (
    <div>
      <Table>
        <thead>
          <tr>
            <th>Verfahren</th>
            <th>Release</th>
            <th>Status</th>
            <th>Datum</th>
            {props.filterState.releaseStatus !== "3" &&
            <th>Early Warnings</th>
            }
            {props.filterState.releaseStatus !== "3" &&
            <th>Ei/D/L</th>
            }
            {props.filterState.releaseStatus === "3" &&
            <th>Kommentar</th>
            }
          </tr>
        </thead>
        <tbody>
          { props.loadingReleases &&
          <tr><td colSpan="6"><center>Daten werden geladen ... <span className="glyphicon glyphicon-refresh glyphicon-spin" role="status"><span className="sr-only">Lade...</span></span></center></td></tr>
          }
          {props.releases.map(release => <ReleaseRow key={"row-" + release.attributes.drupal_internal__nid} release={release} filterState={props.filterState} />)}
        </tbody>
      </Table>
      {props.filterState.items_per_page !== "All" &&
      <SimplePager
        page={props.page}
        setPage={props.setPage}
        count={props.releases.length}
        setCount={setCount}
        items_per_page={props.filterState.items_per_page}
      />
      }
    </div>
  )
}

