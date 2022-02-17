import React from 'react';
import {Link} from 'react-router-dom';

function DeployedReleasesZrmlView() {
  return(
    <div>
      <Link to="/zrml/r/einsatzmeldungen">
        Zurück.
      </Link>
      <p>Eingesetzte Releases</p>
      </div>
  );
}

export default DeployedReleasesZrmlView;