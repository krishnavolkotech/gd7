import React, { useState, useEffect } from 'react';
import { Table } from 'react-bootstrap';
import ReleaseRow from './ReleaseRow';
import SimplePager from '../SimplePager';

export default function ReleaseTable(props) {
  const [count, setCount] = useState(1);

  return (
    <div>
      <Table className="releases">
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
          {props.releases.map(release => <ReleaseRow key={"row-" + release.attributes.drupal_internal__nid} release={release} filterState={props.filterState} />)}
          { props.loadingReleases &&
            <tr>
              <td colSpan="6">
                <center>
                  <span>Releases werden geladen ... </span>
                  <span className="glyphicon glyphicon-refresh glyphicon-spin" role="status"><span className="sr-only">Lade...</span></span>
                </center>
              </td>
            </tr>
          }
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

