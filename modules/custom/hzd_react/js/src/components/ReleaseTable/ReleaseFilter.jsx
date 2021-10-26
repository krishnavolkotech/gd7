import React, {useState, useEffect} from 'react';
import { Row, Col, FormGroup, FormControl, Button, OverlayTrigger, Tooltip } from 'react-bootstrap';

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

  const optionsServices = [
    <option key="service-0" value="0">&lt;Verfahren&gt;</option>,
  ];
  if (props.filterState.type in global.drupalSettings.services) {
    for (const key in global.drupalSettings.services[props.filterState.type]) {
      optionsServices.push(<option key={"service-" + key} value={key}>{global.drupalSettings.services[props.filterState.type][key]}</option>)
    }
  }

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
    <form>
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
          <div>
            <OverlayTrigger placement="top" overlay={ttReset} trigger="hover">
              <Button onClick={props.handleReset} bsStyle="danger"><span className="glyphicon glyphicon-repeat" /></Button>
            </OverlayTrigger>
            &nbsp;
          </div>
        </Col>
      </Row>
    </form>
  )
}
