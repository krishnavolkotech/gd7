import React, { useState, useEffect }from 'react';
import ERTable from './ERTable';
import ERFilter from './ERFilter';
import ReleaseNav from './ReleaseNav';


function Ers() {

  const [eingesetzte, setEingesetzte] = useState([]);

  const [landFilter, setLandFilter] = useState([]);

  const [landGefiltert, setLandGefiltert] = useState([]);

  const [verfahrenFilter, setVerfahrenFilter] = useState([]);

  const [verfahrenGefiltert, setVerfahrenGefiltert] = useState([]);

  const [typeFilter, setTypeFilter] = useState("459");

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

  const [checkedServices, setCheckedServices] = useState([]);

  const [show, setShow] = useState(false);

 

  useEffect(() => {  
  let url = "/api/v1/deployments?status[]=1&environment=1&items_per_page=All&type=" + typeFilter ;


  fetch(url)
  .then(results=> results.json())
  .then(post=> setEingesetzte(post));

  }, [typeFilter]);


 


  function applyLandFilter() {
    const LandAlle=[];
    eingesetzte.map(eingesetzt => LandAlle[LandAlle.length] = eingesetzt.state) ;
    // doppelte entfernen
   
    const uniqueLand = [...new Set(LandAlle)];

    var landKurz= uniqueLand;
   


    landKurz.sort(function(a, b) {
      return a - b;
    });

      let isFiltered = false;
      for (const value in checkedLand) {
          if (checkedLand[value] === true) {
            isFiltered = true;
          }
      }
      // if (landFilter.length !== 0) {
      if (isFiltered) {
      landKurz = landKurz.filter( function (a) {
        return checkedLand[a]==true;
        // landFilter.indexOf (a) >= 0;
       }
      );
      }

      setLandGefiltert(landKurz);
  }


  function applyVerfahrenFilter() {
    const VerfahrenAlle=[];
    eingesetzte.map(eingesetzt => VerfahrenAlle[VerfahrenAlle.length] = eingesetzt.service) ;
    // doppelte entfernen
   
    const uniqueVerfahren = [...new Set(VerfahrenAlle)];

    var verfahrenKurz= uniqueVerfahren;
   
    verfahrenKurz.sort(function(a, b) {
      return a - b;
    });



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

function compare( a, b ) {
      if ( a.release.toLowerCase()< b.release.toLowerCase()){
        return -1;
      }
      if ( a.release.toLowerCase() > b.release.toLowerCase()){
        return 1;
      }
      return 0;
    }


  useEffect(() => {

    eingesetzte.sort( compare );
    applyLandFilter();
    applyVerfahrenFilter();

 }, [landFilter, verfahrenFilter, eingesetzte]);

 console.log(landFilter);
 console.log(checkedServices);
 console.log(verfahrenFilter);

  
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
          />
          <p></p>
          <ERTable
          eingesetzte={eingesetzte} landGefiltert={landGefiltert} verfahrenGefiltert={verfahrenGefiltert} />
        </div>
      );
}
  
  export default Ers;

