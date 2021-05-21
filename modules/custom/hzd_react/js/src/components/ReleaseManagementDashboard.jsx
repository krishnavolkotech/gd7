import React from 'react';
import FilterableReleaseTable from './FilterableReleaseTable';
class ReleaseManagementDashboard extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    return (
      <div>
        <div>Navigations-Tabs</div>
        <FilterableReleaseTable />
      </div>
    );
  }
}

export default ReleaseManagementDashboard;