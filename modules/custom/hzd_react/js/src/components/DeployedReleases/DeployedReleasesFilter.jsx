import React, {useState, useEffect} from 'react'
import { Form, FormGroup, FormControl, ControlLabel, Grid, Row, Col, Button, Tooltip, OverlayTrigger, Dropdown, MenuItem } from 'react-bootstrap'

/**
 * Deployed Releases Filter Component.
 * 
 * @param {Object} props - The inherited properties of this component.
 * @param {Object} props.filterState - The filter state object.
 * @param {Object} props.setFilterState - Method for altering the filter state object.
 * @param {Object} props.handleReset - Method for reseting the filter state.
 * @param {number} props.count - Count value for triggering an useEffect hook (Fetch deployed releases).
 * @param {Object} props.setCount - Method for setting the count variable.
 * @param {Object} props.releases - Releases.
 * 
 * @returns {Object} - The filter form.
 */
export default function DeployedReleasesFilter(props) {

  /** @const {bool} disableProductFilter - Enable / Disable the product filter. */
  const [disableProductFilter, setDisableProductFilter] = useState(true);

  /** @const {Object[]} productOptions - The product option components array. */
  const [productOptions, setProductOptions] = useState(<option value="0">&lt;Komponente&gt;</option>);

  const defaultEnvironments = [
    <option value="0">&lt;Umgebung&gt;</option>,
    <option value="1">Produktion</option>,
    <option value="2">Pilot</option>,
  ];
  const [environmentOptions, setEnvironmentOptions] = useState(defaultEnvironments);

  useEffect(() => {
    let url = '/jsonapi/node/non_production_environment';
    url += '?fields[node--non_production_environment]=drupal_internal__nid,field_non_production_state,title';
    url += '&filter[field_non_production_state]=' + props.filterState.state;
    const headers = new Headers({
      Accept: 'application/vnd.api+json',
    });
    fetch(url, { headers })
      .then(results => results.json())
      .then(results => {
        const environments = results.data.map(result => {
          return (<option value={result.attributes.drupal_internal__nid}>{result.attributes.title}</option>);
        });
        setEnvironmentOptions([...defaultEnvironments, ...environments]);
      })
      .catch(error => console.log(error));
  }, [props.filterState.state])

  /**
   * Changes to selected service filter or releases for the service will trigger
   * the population of the product filter.
   * 
   * Implements hook useEffect().
   */
  useEffect(() => {
    setDisableProductFilter(true);
    populateProductFilter();
  }, [props.releases[props.filterState.service], props.filterState.service])
  
  // States Filter
  const statesObject = global.drupalSettings.states;
  const statesArray = Object.entries(statesObject);
  const optionsStates = statesArray.map(state => <option value={state[0]}>{state[1]}</option>)

  // Verfahren Filter
  const services = global.drupalSettings.services;
  let servicesArray = Object.entries(services);
  servicesArray.sort(function(a,b) {
    const serviceA = a[1][0].toUpperCase();
    const serviceB = b[1][0].toUpperCase();
    if (serviceA < serviceB) {
      return -1;
    }
    if (serviceA > serviceB) {
      return 1;
    }
    return 0;
  });
  const optionsServices = servicesArray.map(service => <option value={service[0]}>{service[1][0]}</option>)

  /**
   * Populates the product filter based on the selected service and releases.
   */
  const populateProductFilter = () => {
    // Product Filter.
    const defaultProduct = [<option value="0">&lt;Komponente&gt;</option>];
    let optionsProducts = [];
    if (props.filterState.service != "0" && Object.keys(props.releases).length > 0) {
      // Verify that the releases for the selected service are loaded.
      let serviceReleases = {};
      if (props.filterState.service in props.releases) {
        serviceReleases = props.releases[props.filterState.service];
        // Enable filter input.
        setDisableProductFilter(false);
      }
  
      // Extract product names.
      let products = [];
      for (const key in serviceReleases) {
        const [product] = serviceReleases[key].title.split("_");
        products.push(product);
      }
      
      // Remove duplicate products.
      products = products.filter((value, index, self) => {
        return self.indexOf(value) === index;
      });

      // Sort products.
      products = products.sort((a, b) => {
        var nameA = a.toUpperCase(); // Groß-/Kleinschreibung ignorieren
        var nameB = b.toUpperCase(); // Groß-/Kleinschreibung ignorieren
        if (nameA < nameB) {
          return -1;
        }
        if (nameA > nameB) {
          return 1;
        }
        // Namen müssen gleich sein
        return 0;
      });
      
      // Populate options for selected input.
      optionsProducts = products.map(product => {
        return <option value={product}>{product}</option>;
      });
  
    }
    
    setProductOptions([...defaultProduct, ...optionsProducts]);
  }

  /**
   * Handles the filter selection events.
   * @param {Object} e - The event object.
   */
  const handleFilterSelect = (e) => {
    let val = {};
    val[e.target.name] = e.target.value;
    props.setFilterState(prev => ({ ...prev, ...val }));
    if (e.target.name == "service") {
      setDisableProductFilter(true);
      props.setFilterState(prev => ({ ...prev, "product": "0" }));
    }

    if (e.target.name == "state") {
      if (props.filterState.environment.length > 1) {
        props.setFilterState(prev => ({ ...prev, "environment": "0" }));
      }
    }

    // Product filtering should trigger fetch only when status is "2".
    if (props.filterState.status === "2" && e.target.name === "product") {
      props.setCount(props.count + 1);
      return;
    }

    if (e.target.name === "sortOrder") {
      if (props.filterState.status === "2") {
        props.setCount(props.count + 1);
        return;
      }
      // if (props.filterState.sortBy !== "title") {
      //   props.setCount(props.count + 1);
      //   return;
      // }
    }

    if (e.target.name === "sortBy") {
      if (props.filterState.status === "2") {
        props.setCount(props.count + 1);
        return;
      }
      // if (e.target.value !== "title") {
      //   props.setCount(props.count + 1);
      //   return;
      // }
    }
  }

  const ttReset = (
    <Tooltip id="ttReset">
      Filter zurücksetzen.
    </Tooltip>);

  const ttRefresh = (
    <Tooltip id="ttRefresh">
      Einsatzmeldungen neu laden.
    </Tooltip>);

  const ttFilter = (
    <Tooltip id="ttFilter">
      Filter anwenden.
    </Tooltip>);
    
  const ttWarning = (
    <Tooltip id="ttWarning">
      Keine Umgebung und/oder Verfahren gewählt - Performance eingeschränkt. <br /><strong>TIPP:</strong> Wählen Sie eine Umgebung und ein Verfahren aus, um die die Performance zu verbessern.
    </Tooltip>);

  const ttLoading = (
    <Tooltip id="ttLoading">
      Meldbare Releases werden identifiziert ...
    </Tooltip>);

  return (
    <div>
        <Row>
          <Col sm={3}>
            <FormGroup bsClass="select-wrapper hzd-form-element" controlId="state-filter">
              <FormControl
                name="state"
                componentClass="select"
                onChange={handleFilterSelect}
                value={props.filterState.state}
              >
                {optionsStates}
              </FormControl>
            </FormGroup>
          </Col>
          <Col sm={3}>
            <FormGroup bsClass="select-wrapper hzd-form-element" controlId="environment-filter" >
              <FormControl
                name="environment"
                componentClass="select"
                onChange={handleFilterSelect}
                value={props.filterState.environment}
              >
                {environmentOptions}
                <span className="glyphicon-warning-sign" />
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
            <FormGroup bsClass="select-wrapper hzd-form-element" controlId="product-filter" >
              <FormControl
                name="product"
                disabled={disableProductFilter}
                componentClass="select"
                onChange={handleFilterSelect}
                value={props.filterState.product}
              >
                {productOptions}
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
                  <option value="field_date_deployed_value">Einsatzdatum</option>
                  <option value="field_environment_value">Umgebung</option>
                  <option value="title">Verfahren</option>
                  <option value="title_1">Release</option>
                  <option value="field_state_list_value">Land</option>
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
                  <option value="ASC">Aufsteigend</option>
                  <option value="DESC">Absteigend</option>
                </FormControl>
              </FormGroup>
            </Form>
          </Col>
        </Row>
        <Row>
          <Col sm={6}>
            <div>
              {/* <OverlayTrigger placement="top" overlay={ttFilter}>
                <Button onClick={props.fetchDeployments} bsStyle="success"><span className="glyphicon glyphicon-filter" /></Button>
                </OverlayTrigger>
              &nbsp; */}
              <OverlayTrigger placement="top" overlay={ttReset}>
                <Button onClick={props.handleReset} bsStyle="danger"><span className="glyphicon glyphicon-repeat" /></Button>
              </OverlayTrigger>
              &nbsp;
              <OverlayTrigger placement="top" overlay={ttRefresh}>
                <Button onClick={() => props.setCount(props.count + 1)} bsStyle="primary"><span className="glyphicon glyphicon-refresh" /></Button>
              </OverlayTrigger>
              &nbsp;
            </div>
          </Col>
          <Col sm={6}>
            { props.loadingReleasesSpinner &&
              <OverlayTrigger placement="top" overlay={ttLoading}>
                <span className="pull-right glyphicon glyphicon-refresh glyphicon-spin" role="status" />
              </OverlayTrigger>
            }
            {/* (props.filterState.environment == "0" || props.filterState.service == "0") &&
              <OverlayTrigger placement="top" overlay={ttWarning}>
                <span className="pull-right glyphicon-warning-sign" />
              </OverlayTrigger>
            */}
          </Col>
        </Row>
    </div>
  )
}
