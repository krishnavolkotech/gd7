import React from 'react';
import { Table } from 'react-bootstrap';
import ReleaseRow from './ReleaseRow';

export default function ReleaseTable(props) {

  return (
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
        {props.releases.map(release => <ReleaseRow release={release} />)}
      </tbody>
    </Table>
  )
}
