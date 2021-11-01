import React, { useState, useEffect }from 'react';
import ERTable from './ERTable';
import ERFilter from './ERFilter';

const loading = <p>Daten werden geladen. Bitte haben Sie einen Moment Gedult ... <span className="glyphicon glyphicon-refresh glyphicon-spin" role="status"><span className="sr-only">Lade...</span></span></p>;

function Ers() {

   /** @const {array} eingesetzte - Array that contains all deployed releases as ob. */
  const [eingesetzte, setEingesetzte] = useState([]);

   /** @const {array} landFilter A filter which shows the selected options for Land. */
  const [landFilter, setLandFilter] = useState([]);

   /** @const {array} landGefiltert - Contains only the Länder selected. */
  const [landGefiltert, setLandGefiltert] = useState([]);

  /** @const {array} verfahrenFilter A filter which shows the selected options for Services. */
  const [verfahrenFilter, setVerfahrenFilter] = useState([]);

   /** @const {array} verfahrenGefiltert - Contains the Services selected. */
  const [verfahrenGefiltert, setVerfahrenGefiltert] = useState([]);

  /** @const {string} typeFilter - Default value for the Type Filter is (459 = KONSENS) */
  const [typeFilter, setTypeFilter] = useState("459");

  /** @const {object} checkedLand - Object with an property for each Land. Default = no Land is selected in the Filter &all properties = false */
  const [checkedLand, setCheckedLand] = useState({
    2:false,
    3:false,
    4:false,
    5:false,
    6:false,
    7:false,
    8:false,
    9:false,
    10:false,
    11:false,
    12:false,
    13:false,
    14:false,
    15:false,
    16:false,
    17:false,
    18:false,
  });

  /** @const {array} checkedServices - Shows which checkboxes of the Services options are checked. */
  const [checkedServices, setCheckedServices] = useState([]);

   /** @const {boolean} show - Indicates wheather the Modal with the filter options is shown, default = not shown */
  const [show, setShow] = useState(false);

  const [isLoading, setIsLoading] = useState(true);

  //Get all deployed releases of the selected type.
  useEffect(() => {
    setIsLoading(true);
    let url = "/api/v1/deployments?status[]=1&environment=1&items_per_page=All&type=" + typeFilter ;
    fetch(url)
      .then(results=> results.json())
      .then( post=> {
        setIsLoading(false);
        setEingesetzte(post)
      });
  }, [typeFilter]);


  function applyLandFilter() {
    const LandAlle = [];
    eingesetzte.map(eingesetzt => LandAlle[LandAlle.length] = eingesetzt.state);
    // Duplicates of states are removed.
    const uniqueLand = [...new Set(LandAlle)];
    var landKurz = uniqueLand;
    // Sort Länder.
    landKurz.sort(function(a, b) {
      return a - b;
    });
    let isFiltered = false;
    for (const value in checkedLand) {
      if (checkedLand[value] === true) {
        isFiltered = true;
      }
    }
    //If there is a filter for Land, the following should happen: Each land included in "landkurz" is removed if it is not contained in the Filter as well.
    if (isFiltered) {
      landKurz = landKurz.filter(function (a) {
        return checkedLand[a] === true;
      });
    }
    setLandGefiltert(landKurz);
  }

  function applyVerfahrenFilter() {
    const VerfahrenAlle = [];
    eingesetzte.map(eingesetzt => VerfahrenAlle[VerfahrenAlle.length] = eingesetzt.service);
    // Duplicates of services are removed.
    const uniqueVerfahren = [...new Set(VerfahrenAlle)];
    var verfahrenKurz = uniqueVerfahren;
    // Sort Services.
    verfahrenKurz.sort(function(a, b) {
      return a - b;
    });
    // If there is a filter for services selected, the following should happen: Each service included in "verfahrenkurz" is removed if it is not contained in the Filter as well.
    if (verfahrenFilter.length !== 0) {
      verfahrenKurz = verfahrenKurz.filter( function (a) {
        return verfahrenFilter.indexOf (a) >= 0;
      });
      setVerfahrenGefiltert(verfahrenKurz);
    }
    else {
      setVerfahrenGefiltert(verfahrenKurz);
    }
  }

  //Function used later to sort Releases in the Table cells.
  function compare(a, b) {
    if (a.release.toLowerCase() < b.release.toLowerCase()) {
      return -1;
    }
    if (a.release.toLowerCase() > b.release.toLowerCase()) {
      return 1;
    }
    return 0;
  }

  useEffect(() => {
    eingesetzte.sort(compare);
    applyLandFilter();
    applyVerfahrenFilter();
 }, [landFilter, verfahrenFilter, eingesetzte]);


  return (
    <div>
      <h1>Eingesetzte Releases</h1>
        <ERFilter
          eingesetzte={eingesetzte} 
          landFilter={landFilter} setLandFilter={setLandFilter} 
          verfahrenFilter={verfahrenFilter} setVerfahrenFilter={setVerfahrenFilter}
          checkedLand={checkedLand} setCheckedLand={setCheckedLand}
          checkedServices={checkedServices} setCheckedServices={setCheckedServices}
          show={show} setShow={setShow}
          typeFilter={typeFilter} setTypeFilter={setTypeFilter}
          isLoading={isLoading}
        />
        <p></p>
        { isLoading ? loading : <ERTable eingesetzte={eingesetzte} landGefiltert={landGefiltert} verfahrenGefiltert={verfahrenGefiltert} /> }
        
    </div>
  );
}
  
export default Ers;

