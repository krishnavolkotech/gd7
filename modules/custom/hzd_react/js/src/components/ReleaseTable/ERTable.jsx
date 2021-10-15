import React, { useEffect }  from 'react';
import { Table} from 'react-bootstrap';

function ERTable({ eingesetzte, landGefiltert, verfahrenGefiltert}) {


  var verfahrenKurz;
  verfahrenKurz = verfahrenGefiltert;
  verfahrenKurz.sort();

  var landKurz;
  landKurz=landGefiltert;

  let dtable = {};
  for (let i = 0; i < verfahrenKurz.length; i++) {
    dtable[verfahrenKurz[i]] = {};
    for (let j = 0; j < landKurz.length; j++) {
      let gefiltert = eingesetzte.filter(eingesetzt => {
        let result = true;
        if (eingesetzt.service !== verfahrenKurz[i]) {
          result = false;
        }
        if (eingesetzt.state !== landKurz[j]) {
          result = false;
        }
        return result;
      });

      let namen = [];
      for (let k = 0; k < gefiltert.length; k++) {
        namen.push(gefiltert[k].release);
      }
      // if (namen.length == 0) {
      //   continue;
      // }
      
      dtable[verfahrenKurz[i]][landKurz[j]] = namen;
    }
  }

  var ids2 = [];
      ids2[2] = ['BW'];
      ids2[3] = ['BY'];
      ids2[4] = ['BE'];
      ids2[5] = ['BB'];
      ids2[6] = ['HB'];
      ids2[7] = ['HH'];
      ids2[8] = ['HE'];
      ids2[9] = ['MV'];
      ids2[10] = ['NI'];
      ids2[11] = ['NW'];
      ids2[12] = ['RP'];
      ids2[13] = ['SL'];
      ids2[14] = ['SN'];
      ids2[15] = ['ST'];
      ids2[16] = ['SH'];
      ids2[17] = ['TH'];
      ids2[18] = ['BU'];

  const tableHeader = (
    <thead  class="sticky-row ">
        <tr  >
          <th >Verfahren</th>
          { landKurz.map((land) => {
            return (
              <th>{ids2[land]}</th>
            );
          })}
        </tr>
        </thead>
  );
  

  const tableBody = buildTableBody();

  function buildTableBody () {

    let result =[];

  
    for (const key in dtable) {
      let row =[<td class="sticky-col" >{key}</td>];
      var len = Object.keys(dtable[key]).length;
      for (const land in dtable[key]) {
        
        if (Object.keys(dtable[key]).indexOf(land) !== len - 1) {
        let list = dtable[key][land].map(release => <li>{release}</li>)
        row.push(<td class="deployed_overview_cell"><ul>{list}</ul></td>);
      }
      if (Object.keys(dtable[key]).indexOf(land) == len - 1) {
        let list = dtable[key][land].map(release => <li>{release}</li>)
        row.push(<td class="deployed_overview_last_cell"  ><ul>{list}</ul></td>);
      }
    }
      result.push(<tr  >{row}</tr>);
    }
    return result;
   
  }


  return(
    <div >
    <Table bsClass="sticky-table table deployed_overview_table">
    {tableHeader}
     <tbody >
     {tableBody}
     </tbody >
    </Table>
    </div>
  );
}

export default ERTable;


