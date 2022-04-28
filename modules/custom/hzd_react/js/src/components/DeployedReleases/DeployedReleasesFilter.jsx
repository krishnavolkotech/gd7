import React, {useState, useEffect} from 'react'
import { Form, FormGroup, FormControl, ControlLabel, Row, Col, Button, Tooltip, OverlayTrigger } from 'react-bootstrap'
import { useHistory } from 'react-router-dom';

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

  /** @const {bool} disableProductFilter - Enable / Disable the product and release filter. */
  const [disableProductFilter, setDisableProductFilter] = useState(true);

  /** @const {Object[]} productOptions - The product option components array. */
  const [productOptions, setProductOptions] = useState(<option key="product-0" value="0">&lt;Komponente&gt;</option>);
  const [releaseOptions, setReleaseOptions] = useState([<option key="select-release-0" value="0">&lt;Release&gt;</option>]);

  const defaultEnvironments = [
    <option key="env-0" value="0">&lt;Umgebung&gt;</option>,
    <option key="env-1" value="1">Produktion</option>,
    <option key="env-2" value="2">Pilot</option>,
  ];
  const [environmentOptions, setEnvironmentOptions] = useState(defaultEnvironments);

  /** @const {object} history - The history object (URL modifications). */
  const history = useHistory();

  var disabledState = global.drupalSettings.role === "ZRML" ? true : false;
  /** @var {bool} manage - Indicates, if filter is used in manage (tool) context. */ 
  var manage = true;
  // @todo Landesauswahl nur dann verhindern, wenn Einsatzmeldungs-Tool verwendet wird -> TESTEN
  if (history.location.pathname.indexOf('releases') > 0) {
    disabledState = false;
    manage = false;
  }

  useEffect(() => {
    let url = '/jd7kfn9dm32ni/node/non_production_environment';
    url += '?fields[node--non_production_environment]=drupal_internal__nid,field_non_production_state,title';
    url += '&filter[field_non_production_state]=' + props.filterState.state;
    const headers = new Headers({
      Accept: 'application/vnd.api+json',
    });
    fetch(url, { headers })
      .then(results => results.json())
      .then(results => {
        const environments = results.data.map(result => {
          return (<option key={"env-" + result.attributes.drupal_internal__nid} value={result.attributes.drupal_internal__nid}>{result.attributes.title}</option>);
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

  // Befüllt Release Releaseoptionen basierend auf newReleases (prop).
  useEffect(() => {
    //Release Drop Down
    // setReleaseOptions([<option value="0">&lt;Release&gt;</option>]);
    let defaultRelease = [<option key="select-release-0" value="0">&lt;Release&gt;</option>];
    let optionsReleases = [];
    // Dropdown deaktivieren, bevor die Optionen gefüllt sind..
    // if (typeof props.releases !== 'object') {
    //   setIsLoading(false);
    //   return;
    // }
    // Options befüllen.
    // console.log(props.releases);
    // console.log();
    if (props.filterState.service in props.releases) {
      var releaseArray = props.releases[props.filterState.service];
    }
    // Objekte müssen "geklont" werden.
    let spreadReleases = { ...releaseArray };
    // Vorbereitung für Liste von Release Objekten. Release-Objekte benötigen
    // die Eigenschaften "nid" und "title".
    let selectReleases = [];
    // console.log(spreadReleases);
    for (const release in spreadReleases) {
      // Erzeugt Liste mit Release-Objekten.
      selectReleases.push(spreadReleases[release]);
    }
    selectReleases.sort(function (a, b) {
      const productA = a.title.substring(0, a.title.indexOf('_') + 1);
      const productB = b.title.substring(0, b.title.indexOf('_') + 1);
      const versionA = a.title.substring(a.title.indexOf('_') + 1);
      const versionB = b.title.substring(b.title.indexOf('_') + 1);
      // Product should be alphabetically.
      if (productA < productB) {
        return -1;
      }
      if (productA > productB) {
        return 1;
      }
      // Version should be descending.
      const partsA = versionA.split('.')
      const partsB = versionB.split('.')
      for (var i = 0; i < partsB.length; i++) {
        const vA = ~~partsA[i] // parse int
        const vB = ~~partsB[i] // parse int
        if (vA > vB) return -1;
        if (vA < vB) return 1;
      }
      if (versionA < versionB) {
        return 1;
      }
      if (versionA > versionB) {
        return -1;
      }
      return 0;
    });

    if (selectReleases.length > 0) {
      optionsReleases = selectReleases.map(option => {
        for (const nid in option) {
          if (props.filterState.product) {
            if (option.title.indexOf(props.filterState.product + "_") !== -1) {
              return <option key={"select-release-" + option.nid} value={option.nid}>{option.title}</option>;
            }
          } else {
            return <option key={"select-release-" + option.nid} value={option.nid}>{option.title}</option>;
          }
        }
      });
    }
    optionsReleases = [...defaultRelease, ...optionsReleases];
    setReleaseOptions(optionsReleases);

  }, [props.releases, props.filterState.product])

  // States Filter
  const statesObject = global.drupalSettings.states;
  const statesArray = Object.entries(statesObject);
  const optionsStates = statesArray.map(state => <option key={"state-" + state[0]} value={state[0]}>{state[1]}</option>)

  // Verfahren Filter, Abhängig von Typ
  var optionsServices = [<option key="service-0" value="0">&lt;Verfahren&gt;</option>];
  if (props.filterState.type in global.drupalSettings.services) {
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
    optionsServices = [<option key="service-0" value="0">&lt;Verfahren&gt;</option>, servicesArray.map(service => <option key={"service-" + service[0]} value={service[0]}>{service[1]}</option>)];
  }

  /**
   * Populates the product filter based on the selected service and releases.
   */
  const populateProductFilter = () => {
    // Product Filter.
    const defaultProduct = [<option key="product-default" value="">&lt;Komponente&gt;</option>];
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
        return <option key={"product-" + product} value={product}>{product}</option>;
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
    switch (e.target.name) {
      case "type":
        // Verfahren, Release und Produktauswahl zurücksetzen, wenn Typ geändert.
        val["type"] = e.target.value;
        val["service"] = "0";
        val["release"] = "0";
        val["product"] = "";
        props.setFilterState(prev => ({ ...prev, ...val }));
        break;
      case "service":
        val["service"] = e.target.value;
        val["release"] = "0";
        val["product"] = "";
        props.setFilterState(prev => ({ ...prev, ...val }));
        setDisableProductFilter(true);
        break;
      case "state":
        val["state"] = e.target.value;
        val["environment"] = "0";
        props.setFilterState(prev => ({ ...prev, ...val }));
        break;
      case "product":
        val["product"] = e.target.value;
        val["release"] = "0";
        props.setFilterState(prev => ({ ...prev, ...val }));
        break;
      default:
        val[e.target.name] = e.target.value;
        props.setFilterState(prev => ({ ...prev, ...val }));
        break;
    }


    // Product filtering should trigger fetch only when deploymentStatus is "2".
    if (props.filterState.deploymentStatus === "2" && e.target.name === "product") {
      props.setCount(props.count + 1);
      return;
    }

    if (e.target.name === "deploymentSortOrder") {
      if (props.filterState.deploymentStatus === "2") {
        props.setCount(props.count + 1);
        return;
      }
      // if (props.filterState.deploymentSortBy !== "title") {
      //   props.setCount(props.count + 1);
      //   return;
      // }
    }

    if (e.target.name === "deploymentSortBy") {
      if (props.filterState.deploymentStatus === "2") {
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
        <Col sm={4}>
          <FormGroup bsClass="select-wrapper hzd-form-element" controlId="type-filter">
            <FormControl
              name="type"
              alt="Typfilter"
              componentClass="select"
              onChange={handleFilterSelect}
              disabled={props.disableTypeFilter}
              value={props.filterState.type}
            >
              <option value="459">KONSENS</option>
              <option value="460">Best/Fakt</option>
            </FormControl>
          </FormGroup>
        </Col>
        <Col sm={4}>
          <FormGroup bsClass="select-wrapper hzd-form-element" controlId="state-filter">
            <FormControl
              name="state"
              alt="Landesfilter"
              componentClass="select"
              onChange={handleFilterSelect}
              value={props.filterState.state}
              disabled={disabledState}
            >
              {optionsStates}
            </FormControl>
          </FormGroup>
        </Col>
        <Col sm={4}>
          <FormGroup bsClass="select-wrapper hzd-form-element" controlId="environment-filter" >
            <FormControl
              name="environment"
              alt="Umgebungsfilter"
              componentClass="select"
              onChange={handleFilterSelect}
              value={props.filterState.environment}
            >
              {environmentOptions}
            </FormControl>
          </FormGroup>
        </Col>
      </Row>
      <Row>
        <Col sm={4}>
          <FormGroup bsClass="select-wrapper hzd-form-element" controlId="service-filter" >
            <FormControl
              name="service"
              alt="Verfahrensfilter"
              componentClass="select"
              onChange={handleFilterSelect}
              value={props.filterState.service}
            >
              {optionsServices}
            </FormControl>
          </FormGroup>
        </Col>
        <Col sm={4}>
          <FormGroup bsClass="select-wrapper hzd-form-element" controlId="product-filter" >
            <FormControl
              name="product"
              alt="Produktfilter"
              disabled={disableProductFilter}
              componentClass="select"
              onChange={handleFilterSelect}
              value={props.filterState.product}
            >
              {productOptions}
            </FormControl>
          </FormGroup>
        </Col>
        <Col sm={4}>
          <FormGroup bsClass="select-wrapper hzd-form-element" controlId="release-filter" >
            <FormControl
              name="release"
              alt="Releasefilter"
              disabled={disableProductFilter}
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
        <Col sm={8}>
          <Form inline>
                <FormGroup bsClass="select-wrapper hzd-form-element" controlId="deploymentSortBy">
                  <ControlLabel>Sortieren nach&nbsp;</ControlLabel>
                  <FormControl
                    name="deploymentSortBy"
                    alt="Sortieren nach"
                    componentClass="select"
                    placeholder="select"
                    onChange={handleFilterSelect}
                    value={props.filterState.deploymentSortBy}
                  >
                    <option key="sort-1" value="field_date_deployed_value">Einsatzdatum</option>
                    <option key="sort-2" value="field_environment_value">Umgebung</option>
                    <option key="sort-3" value="title">Verfahren</option>
                    <option key="sort-4" value="title_1">Release</option>
                    {!disabledState &&
                      <option key="sort-5" value="field_state_list_value">Land</option>
                    }
                  </FormControl>
                </FormGroup>
                <FormGroup bsClass="select-wrapper hzd-form-element" controlId="deploymentSortOrder">
                  <FormControl
                    name="deploymentSortOrder"
                    alt="Sortierreihenfolge"
                    componentClass="select"
                    placeholder="select"
                    onChange={handleFilterSelect}
                    value={props.filterState.deploymentSortOrder}
                  >
                    <option key="order-asc" value="ASC">Aufsteigend</option>
                    <option key="order-desc" value="DESC">Absteigend</option>
                  </FormControl>
                </FormGroup>
              { !manage &&
                <FormGroup bsClass="select-wrapper hzd-form-element" controlId="items_per_page">
                  <ControlLabel>Elemente pro Seite&nbsp;</ControlLabel>
                  <FormControl
                    name="items_per_page"
                    alt="Elemente pro Seite"
                    componentClass="select"
                    placeholder="select"
                    onChange={handleFilterSelect}
                    value={props.filterState.items_per_page}
                  >
                    <option key="items_per_page-1" value="20">20</option>
                    <option key="items_per_page-2" value="50">50</option>
                    <option key="items_per_page-3" value="100">100</option>
                    <option key="items_per_page-4" value="All">Alle</option>
                  </FormControl>
                </FormGroup>
            }
          </Form>
        </Col>
      </Row>
      <Row>
        <Col sm={6}>
          <div>
            {/* <OverlayTrigger placement="top" overlay={ttFilter}>
              <Button onClick={props.fetchDeployments} bsClass="btn follow-up-button"><span className="glyphicon glyphicon-filter" /></Button>
              </OverlayTrigger>
            &nbsp; */}
            <OverlayTrigger placement="top" overlay={ttReset}>
              <Button onClick={props.handleReset} bsStyle="danger" alt="Filter zurücksetzen"><span className="glyphicon glyphicon-repeat" /></Button>
            </OverlayTrigger>
            &nbsp;
            <OverlayTrigger placement="top" overlay={ttRefresh}>
              <Button onClick={() => props.setCount(props.count + 1)} bsStyle="primary" alt="Tabelle aktualisieren"><span className="glyphicon glyphicon-refresh" /></Button>
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
