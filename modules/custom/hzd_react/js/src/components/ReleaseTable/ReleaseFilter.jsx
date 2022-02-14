import React, {useState, useEffect} from 'react';
import { Row, Col, FormGroup, FormControl, Button, OverlayTrigger, Tooltip, Form, ControlLabel } from 'react-bootstrap';

export default function ReleaseFilter(props) {

  const defaultReleaseOption = <option value="0">&lt;Release&gt;</option>;
  /** @const {Object[]} productOptions - The product option components array. */
  const [productOptions, setProductOptions] = useState(<option key="product-0" value="0">&lt;Komponente&gt;</option>);
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
      val["product"] = "";
      setReleaseOptions(defaultReleaseOption);
    }
    if (e.target.name == "service") {
      val["release"] = "0";
      val["product"] = "";
      setReleaseOptions(defaultReleaseOption);
    }
    if (e.target.name == "product") {
      val["release"] = "0";
      // setReleaseOptions(defaultReleaseOption);
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

  /**
   * Populates the release filter based on the selected service and product.
   */
  useEffect(() => {
    let options = [
      <option key="release-0" value="0">&lt;Release&gt;</option>,
    ];
    for (let i = 0; i < props.filterReleases.length; i++) {
      if (props.filterState.product) {
        if (props.filterReleases[i].title.indexOf(props.filterState.product) !== -1) {
          options.push(<option key={"release-" + props.filterReleases[i].nid} value={props.filterReleases[i].nid}>{props.filterReleases[i].title}</option>);
        }
      }
      else {
        options.push(<option key={"release-" + props.filterReleases[i].nid} value={props.filterReleases[i].nid}>{props.filterReleases[i].title}</option>);
      }
    }
    setReleaseOptions(options);
    populateProductFilter();
  }, [props.filterReleases, props.filterState.product])

  /**
   * Populates the product filter based on the selected service and releases.
   */
  const populateProductFilter = () => {
    // Product Filter.
    const defaultProduct = [<option key="product-default" value="">&lt;Komponente&gt;</option>];
    let optionsProducts = [];
    if (props.filterState.service != "0" && Object.keys(props.filterReleases).length > 0) {
      // Verify that the releases for the selected service are loaded.
      let serviceReleases = {};
      serviceReleases = props.filterReleases;

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

  const ttReset = (
    <Tooltip id="ttReset">
      Filter zurücksetzen.
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
              value={props.filterState.type}
            >
              {optionsTypes}
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
              disabled={props.disableReleaseFilter}
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
            <FormGroup bsClass="select-wrapper hzd-form-element" controlId="releaseSortBy">
              <ControlLabel>Sortieren nach&nbsp;</ControlLabel>
              <FormControl
                name="releaseSortBy"
                alt="Sortieren nach"
                componentClass="select"
                placeholder="select"
                onChange={handleFilterSelect}
                value={props.filterState.releaseSortBy}
              >
                <option key="sort-1" value="field_date">Datum</option>
                <option key="sort-2" value="field_relese_services.title">Verfahren</option>
                <option key="sort-3" value="title">Release</option>
                <option key="sort-4" value="field_status">Status</option>
              </FormControl>
            </FormGroup>
            <FormGroup bsClass="select-wrapper hzd-form-element" controlId="releaseSortOrder">
              <FormControl
                name="releaseSortOrder"
                alt="Sortierreihenfolge"
                componentClass="select"
                placeholder="select"
                onChange={handleFilterSelect}
                value={props.filterState.releaseSortOrder}
              >
                <option key="order-asc" value="">Aufsteigend</option>
                <option key="order-desc" value="-">Absteigend</option>
              </FormControl>
            </FormGroup>
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
                <option key="items_per_page-4" value="All">Alle</option>
              </FormControl>
            </FormGroup>
          </Form>
        </Col>
      </Row>
      <Row>
        <Col sm={6}>
          <div>
            <OverlayTrigger placement="top" overlay={ttReset}>
              <Button name="Zurücksetzen" alt="Zurücksetzen" onClick={props.handleReset} bsStyle="danger"><span className="glyphicon glyphicon-repeat" /></Button>
            </OverlayTrigger>
            &nbsp;
          </div>
        </Col>
      </Row>
    </div>
  )
}
