import React, { useState } from "react";
import { Row, Col, FormGroup, FormControl, Button, Modal, Checkbox, Glyphicon} from 'react-bootstrap';

function ERFilter ({eingesetzte, setLandFilter, verfahrenFilter, setVerfahrenFilter, checkedLand, setCheckedLand, typeFilter, setTypeFilter, setCheckedServices, show, setShow, isLoading, setFilterState}) {

  var ids2 = [];
  ids2[2] = ['Baden-Württemberg (BW)'];
  ids2[3] = ['Bayern (BY)'];
  ids2[4] = ['Berlin (BE)'];
  ids2[5] = ['Brandenburg (BB)'];
  ids2[6] = ['Bremen (HB)'];
  ids2[7] = ['Hamburg (HH)'];
  ids2[8] = ['Hessen (HE)'];
  ids2[9] = ['Mecklenburg-Vorpommern (MV)'];
  ids2[10] = ['Niedersachsen (NI)'];
  ids2[11] = ['Nordrhein-Westfalen (NW)'];
  ids2[12] = ['Rheinland-Pfalz (RP)'];
  ids2[13] = ['Saarland (SL)'];
  ids2[14] = ['Sachsen (SN)'];
  ids2[15] = ['Sachsen-Anhalt (ST)'];
  ids2[16] = ['Schleswig-Holstein (SH)'];
  ids2[17] = ['Thüringen (TH)'];
  ids2[18] = ['Bund (BU)'];

  // FilterOptions Land.
  const optionsLandAlle = [];
  eingesetzte.map(eingesetzt => optionsLandAlle[optionsLandAlle.length] = eingesetzt.state);
  // Doppelte entfernen.
  const unique1 = [...new Set(optionsLandAlle)];
  const optionsKurz1 = unique1;
 
  //Sortiert Landescheckboxen nach ID (Rheinfolge wie in Filtern)
  optionsKurz1.sort(function (a, b) { return a - b });
  //Mareile: Wenn man das hinzufügt, ist auch BU alphabetisch einsortiert.
  // optionsKurz1.splice(5, 0, optionsKurz1[16]);
  // optionsKurz1.pop();

  // FilterOptions Verfahren.
  const optionsVerfahrenAlle = [];
  eingesetzte.map(eingesetzt => optionsVerfahrenAlle[optionsVerfahrenAlle.length] = eingesetzt.service);
  // Doppelte entfernen.
  const unique2 = [...new Set(optionsVerfahrenAlle)];
  const optionsKurz2= unique2;
  optionsKurz2.sort();

  const optionsTypes = [
    <option value="459">KONSENS</option>,
    <option value="460">Best/Fakt</option>,
  ];

  function handleSelectLand(event) {
    let neu = {};
    neu[event.target.value] = event.target.checked;
    setCheckedLand(prev => ({ ...prev, ...neu }));
    setLandFilter(checkedLand);
  }

  function handleSelectVerfahren(event) {
    var selectedServices = verfahrenFilter;
    if (selectedServices.includes(event.target.value)) {
      selectedServices = selectedServices.filter(function(element) {
        return element !== event.target.value;
      });
    }
    else {
      selectedServices = [...selectedServices, event.target.value];
    }
    setVerfahrenFilter(selectedServices);
  }

  function handleTypeSelect(event) {
    setTypeFilter(event.target.value);
    let val = {};
    val["type"] = event.target.value;
    val["service"] = "0";
    val["release"] = "0";
    val["product"] = "";
    setFilterState(prev => ({ ...prev, ...val }));
    handleReset();
  }

  function handleReset() {
    setCheckedLand({
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
    setCheckedServices([]);
    setLandFilter([]);
    setVerfahrenFilter([]);
  }

  function handleShow() {
    setShow(true);
  }

  function handleClose() {
    setShow(false);
  }

  return (
    
    <div>
      <Row>
        <Col sm={3}>
          <FormGroup bsClass="select-wrapper hzd-form-element" controlId="type-filter">
            <FormControl
              name="type"
              componentClass="select"
              onChange={handleTypeSelect}
              value={typeFilter}
            >
              {optionsTypes}
            </FormControl>
        </FormGroup>
        </Col>
      </Row>

      {!isLoading && 
      <Row bsClass="reset_form">
        <Button bsStyle="primary" onClick={handleShow}>
          <Glyphicon glyph="glyphicon glyphicon-wrench" />
          &nbsp;Anzeige konfigurieren
        </Button> 
      </Row>
      }

      <Modal show={show} onHide={handleClose}>
        <Modal.Header closeButton>
          <Modal.Title>
            <h2>Bitte treffen Sie eine Auswahl, um das Ergebnis einzuschränken.</h2>
          </Modal.Title>
          <p> Ohne Auswahl werden alle Verfahren und alle Länder in der Tabelle angezeigt.</p>
        </Modal.Header>
        <Modal.Body>
          <form class="problem-settings-form">
            <legend>
              <p class="fieldset-legend"> Land:</p>
            </legend>
            <div class="form-checkboxes">
              {optionsKurz1.map((land) => <Checkbox bsClass="form-item js-form-item form-type-checkbox js-form-type-checkbox form-item-states-2 js-form-item-states-2 checkbox" 
              value={land} onClick={handleSelectLand} checked={checkedLand[land]}> {ids2[land]} </Checkbox > )}
            </div>
            <p></p>
            <legend>
              <p class="fieldset-legend"> Verfahren:</p>
            </legend>
            <div class="form-checkboxes">
              {optionsKurz2.map((verfahren) =>  <Checkbox bsClass="form-item js-form-item form-type-checkbox js-form-type-checkbox form-item-states-2 js-form-item-states-2 checkbox" checked={verfahrenFilter.includes(verfahren)} value={verfahren} onClick={handleSelectVerfahren}> {verfahren} </Checkbox >)}
            </div>
            <Row>
              <Button bsStyle="danger" colspan="3"  type="button" onClick={handleReset} >Zurücksetzen</Button>
            </Row>
          </form>
        </Modal.Body>
        <Modal.Footer>
          <Button bsStyle="primary" onClick={handleClose}>Schließen</Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
}

export default ERFilter;
