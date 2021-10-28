import React, {useState, useEffect} from 'react';
import { Row, Col, FormGroup, FormControl, Button, OverlayTrigger, Tooltip, Form, ControlLabel } from 'react-bootstrap';

export default function ReleaseFilter(props) {

  const defaultReleaseOption = <option value="0">&lt;Release&gt;</option>;
  const [releaseOptions, setReleaseOptions] = useState(defaultReleaseOption);

  /**
   * Handles the filter selection events.
   * @param {Object} e - The event object.
   */
  const handleFilterSelect = (e) => {
    let val = {};
    val[e.target.name] = e.target.value;
    if (e.target.name == "type") {
      val["service"] = "0";
      val["release"] = "0";
      setReleaseOptions(defaultReleaseOption);
    }
    if (e.target.name == "service") {
      val["release"] = "0";
      setReleaseOptions(defaultReleaseOption);
    }
    props.setFilterState(prev => ({ ...prev, ...val }));
    // if (e.target.name == "service") {
    //   // @todo Releasefilter deaktivieren, wenn Verfahren gewählt wird
    //   // setDisableReleaseFilter(true);
    //   props.setFilterState(prev => ({ ...prev, "product": "0" }));
    // }
    // if (props.filterState.status === "2" && e.target.name === "product") {
    //   props.setCount(props.count + 1);
    // }
  }

  const optionsTypes = [
    <option key="type-459" value="459">KONSENS</option>,
    <option key="type-460" value="460">Best/Fakt</option>,
  ];

  // Verfahren Filter
  const services = global.drupalSettings.services[props.filterState.type];
  let servicesArray = Object.entries(services);
  servicesArray.sort(function (a, b) {
    const serviceA = a[1].toUpperCase();
    const serviceB = b[1].toUpperCase();
    if (serviceA < serviceB) {
      return -1;
    }
    if (serviceA > serviceB) {
      return 1;
    }
    return 0;
  });
  const optionsServices = [<option key="service-0" value="0">&lt;Verfahren&gt;</option>, servicesArray.map(service => <option key={"service-" + service[0]} value={service[0]}>{service[1]}</option>)];


  useEffect(() => {
    let options = [
      <option key="release-0" value="0">&lt;Release&gt;</option>,
    ];
    for (let i = 0; i < props.filterReleases.length; i++) {
      options.push(<option key={"release-" + props.filterReleases[i].nid} value={props.filterReleases[i].nid}>{props.filterReleases[i].title}</option>);
    }
    setReleaseOptions(options);
  }, [props.filterReleases])

  const ttReset = (
    <Tooltip id="ttReset">
      Filter zurücksetzen.
    </Tooltip>);

  return (
    <div>
      <Row>
        <Col sm={3}>
          <FormGroup bsClass="select-wrapper hzd-form-element" controlId="type-filter">
            <FormControl
              name="type"
              componentClass="select"
              onChange={handleFilterSelect}
              value={props.filterState.type}
            >
              {optionsTypes}
            </FormControl>
          </FormGroup>
        </Col>
        <Col sm={3}>
          <FormGroup bsClass="select-wrapper hzd-form-element" controlId="service-filter" >
            <FormControl
              name="service"
              componentClass="select"
              onChange={handleFilterSelect}
              value={props.filterState.service}
            >
              {optionsServices}
            </FormControl>
          </FormGroup>
        </Col>
        <Col sm={3}>
          <FormGroup bsClass="select-wrapper hzd-form-element" controlId="release-filter" >
            <FormControl
              name="release"
              disabled={props.disableReleaseFilter}
              componentClass="select"
              onChange={handleFilterSelect}
              value={props.filterState.release}
            >
              {releaseOptions}
            </FormControl>
          </FormGroup>
        </Col>
      </Row>
      <Row>
        <Col sm={6}>
          <Form inline>
            <FormGroup bsClass="select-wrapper hzd-form-element" controlId="sortBy">
              <ControlLabel>Sortieren nach&nbsp;</ControlLabel>
              <FormControl
                name="sortBy"
                componentClass="select"
                placeholder="select"
                onChange={handleFilterSelect}
                value={props.filterState.sortBy}
              >
                <option key="sort-1" value="field_date">Datum</option>
                <option key="sort-2" value="field_relese_services.title">Verfahren</option>
                <option key="sort-3" value="title">Release</option>
                <option key="sort-4" value="field_status">Status</option>
              </FormControl>
            </FormGroup>
            <FormGroup bsClass="select-wrapper hzd-form-element" controlId="sortOrder">
              <FormControl
                name="sortOrder"
                componentClass="select"
                placeholder="select"
                onChange={handleFilterSelect}
                value={props.filterState.sortOrder}
              >
                <option key="order-asc" value="">Aufsteigend</option>
                <option key="order-desc" value="-">Absteigend</option>
              </FormControl>
            </FormGroup>
          </Form>
        </Col>
      </Row>
      <Row>
        <Col sm={6}>
          <div>
            <OverlayTrigger placement="top" overlay={ttReset}>
              <Button onClick={props.handleReset} bsStyle="danger"><span className="glyphicon glyphicon-repeat" /></Button>
            </OverlayTrigger>
            &nbsp;
          </div>
        </Col>
      </Row>
    </div>
  )
}
