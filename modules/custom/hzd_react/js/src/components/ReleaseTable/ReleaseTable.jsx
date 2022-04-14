import React, { useState, useEffect } from 'react';
import { Table } from 'react-bootstrap';
import ReleaseRow from './ReleaseRow';
import SimplePager from '../SimplePager';

export default function ReleaseTable(props) {
  const [count, setCount] = useState(1);

  var reA = /[^a-zA-Z]/g;
  var reN = /[^0-9]/g;

  //Funktion um ReleaseTitel in der Tablle alphanumerisch zu sortieren
  function sortAlphaNum(a, b) {
    
    //behebt Fehler, falls attributes.field_calculated_title bei alten releases = null ist
    if (a.attributes.field_calculated_title == null) {
      a.attributes.field_calculated_title = " _ ";
    }
    if (b.attributes.field_calculated_title == null) {
      b.attributes.field_calculated_title = " _ ";
    }

    var vorletzteA = a.attributes.field_calculated_title.slice(-3);
    var vorletzteB = b.attributes.field_calculated_title.slice(-3);

    if (vorletzteA.includes("-")) {
      var aa = a.attributes.field_calculated_title.substr(0, a.attributes.field_calculated_title.lastIndexOf("-"));
    }
    else {
      var aa = a.attributes.field_calculated_title
    }
    if (vorletzteB.includes("-")) {
      var bb = b.attributes.field_calculated_title.substr(0, b.attributes.field_calculated_title.lastIndexOf("-"));
    }
    else {
      var bb = b.attributes.field_calculated_title
    }

    var aZ = a.attributes.field_calculated_title.split('_')[1];
    var bZ = b.attributes.field_calculated_title.split('_')[1];
    var aA = aa.replace(reA, "");
    var bA = bb.replace(reA, "");

    if (aA === bA) {
      var aN = parseInt(a.attributes.field_calculated_title.replace(reN, ""), 10);
      var bN = parseInt(b.attributes.field_calculated_title.replace(reN, ""), 10);
  
     //Version should be descending.
      const partsA = aZ.split('.');
      const partsB = bZ.split('.');
      for (var i = 0; i < partsB.length; i++) {
        const vA = ~~partsA[i] // parse int
        const vB = ~~partsB[i] // parse int
        if (vA > vB) return -1;
        if (vA < vB) return 1;
      }
      if (aN < bN) {
        return 1;
      }
      if (aN > bN) {
        return -1;
      }
      return 0;
    }
  }

  useEffect(() => {
      props.releases.sort(sortAlphaNum);
    },);

  return (
    <div>
      <Table className="releases released">
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
          { props.releases.length === 0 && !props.loadingReleases &&
            <tr>
              <td colSpan="6"><center>Keine Daten gefunden.</center></td>
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

