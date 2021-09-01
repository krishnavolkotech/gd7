import React, {useState, useEffect} from 'react'
import { FormGroup, FormControl, Grid, Row, Col, Button, Tooltip, OverlayTrigger } from 'react-bootstrap'

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
  const [productOptions, setProductOptions] = useState(<option value="0">&lt;Produkt&gt;</option>);

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

  // Umgebungen Filter
  const environments = global.drupalSettings.environments;
  const environmentsArray = Object.entries(environments);
  const optionsEnvironments = environmentsArray.map(environment => <option value={environment[0]}>{environment[1]}</option>)

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
    const defaultProduct = [<option value="0">&lt;Produkt&gt;</option>];
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

  return (
    <form>
      <Grid>
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
                {optionsEnvironments}
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
        </Row>
        <Row>
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
          <Col sm={3}>
            <div>
              <OverlayTrigger placement="top" overlay={ttReset}>
                <Button onClick={props.handleReset} bsStyle="danger"><span className="glyphicon glyphicon-repeat" /></Button>
              </OverlayTrigger>
              &nbsp;
              <OverlayTrigger placement="top" overlay={ttRefresh}>
                <Button onClick={() => props.setCount(count + 1)} bsStyle="primary"><span className="glyphicon glyphicon-refresh" /></Button>
              </OverlayTrigger>
            </div>
          </Col>
        </Row>
      </Grid>
    </form>
  )
}
