import React from 'react';
import FilterableReleaseTable from './FilterableReleaseTable';
import { Link } from "react-router-dom";
import ReleaseNavigation from './ReleaseNavigation';
import { Row, Grid } from 'react-bootstrap';

function ReleaseManagementDashboard() {
  return (
    <div>
        <FilterableReleaseTable />
    </div>
  );
}

export default ReleaseManagementDashboard;