import React, { useState, useEffect } from 'react';
import { Nav, NavItem } from 'react-bootstrap';
import { useHistory } from 'react-router-dom';
import DeployedReleasesManager from './DeployedReleasesManager';
import Ers from './Ers';
import ReleaseTableManager from './ReleaseTableManager';
import useQuery from '../../hooks/hooks';

export default function ReleaseViewNavigator() {
  /** @const {object} history - The history object (URL modifications). */
  const history = useHistory()

  /** @const {URLSearchParams} query - Read URL Params. */
  const query = useQuery();

  let active = "1";
  if (history.location.pathname.indexOf('bereitgestellt') > 0) {
    active = "1";
  }
  if (history.location.pathname.indexOf('in-bearbeitung') > 0) {
    active = "2";
  }
  if (history.location.pathname.indexOf('gesperrt') > 0) {
    active = "3";
  }
  if (history.location.pathname.indexOf('eingesetzt') > 0) {
    active = "4";
  }
  if (history.location.pathname.indexOf('archiviert') > 0) {
    active = "5";
  }
  if (history.location.pathname.indexOf('eingesetzt-uebersicht') > 0) {
    active = "6";
  }
  if (history.location.pathname.indexOf('einsatzinformationen') > 0) {
    active = "7";
  }

  const [activeKey, setActiveKey] = useState(active);

  // Pagination.
  const [page, setPage] = useState(1);

  const initialState = query.has("state") ? query.get("state") : global.drupalSettings.userstate;

  const initialFilterState = {
    "type": query.has("type") ? query.get("type") : "459",
    "state": initialState,
    "environment": query.has("environment") ? query.get("environment") : "0",
    "service": query.has("service") ? query.get("service") : "0",
    "product": query.has("product") ? query.get("product") : "",
    "release": query.has("release") ? query.get("release") : "0",
    "deploymentStatus": query.has("deploymentStatus") ? query.get("deploymentStatus") : "1",
    "releaseStatus": active,
    "releaseSortBy": "field_date",
    "releaseSortOrder": "-",
    "deploymentSortBy": "field_date_deployed_value",
    "deploymentSortOrder": "DESC",
    "items_per_page": "20",
  };

  /**
   * The filter state object.
   * @property {Object} filterState - The object holding the filter state.
   * @property {string} filterState.type - The service type.
   * @property {string} filterState.state - The state id.
   * @property {string} filterState.environment - The environment id.
   * @property {string} filterState.service - The service id.
   * @property {string} filterState.product - The product name.
   * @property {string} filterState.release - The release id.
   * @property {string} filterState.deploymentStatus - The deployment status.
   * @property {string} filterState.releaseStatus - The release status.
   * @property {string} filterState.releaseSortBy - Field name for sorting releases.
   * @property {string} filterState.releaseSortOrder - The sorting direction ('', '-').
   * @property {string} filterState.deploymentSortBy - Field name for sorting deployments.
   * @property {string} filterState.deploymentSortOrder - The sorting direction ('ASC', 'DESC').
   * @property {string} filterState.items_per_page - The items per page.
   */
  const [filterState, setFilterState] = useState(initialFilterState);

  // Removes the PDF Export Button.
  useEffect(() => {
    const pdf = jQuery('a').filter(function (index) { return jQuery(this).text() === "PDF"; });
    pdf.remove();
  }, []);

  /**
   * Changes URL-Params depending on Nav / Filters, resets Pagination.
   * 
   * Implements hook useEffect().
  */
  useEffect(() => {
    let pathname = history.location.pathname;
    let explodedPath = pathname.split("/");
    if (explodedPath.length === 3) {
      // If path ends on ".../releases", push.
      explodedPath.push("bereitgestellt");
    }
    else {
      explodedPath[explodedPath.length - 1] = "bereitgestellt";
    }

    switch (activeKey) {
      case "1":
        explodedPath[explodedPath.length - 1] = "bereitgestellt";
        break;
      case "2":
        explodedPath[explodedPath.length - 1] = "in-bearbeitung";
        break;
      case "3":
        explodedPath[explodedPath.length - 1] = "gesperrt";
        break;
      case "5":
        explodedPath[explodedPath.length - 1] = "archiviert";
        break;
      case "4":
        explodedPath[explodedPath.length - 1] = "eingesetzt";
        break;
      case "6":
        explodedPath[explodedPath.length - 1] = "eingesetzt-uebersicht";
        break;
      case "7":
        explodedPath[explodedPath.length - 1] = "einsatzinformationen";
        break;
      default:
        explodedPath[explodedPath.length - 1] = "bereitgestellt";
        break;
    }
    pathname = explodedPath.join("/");

    // Change URL Params. 
    const params = new URLSearchParams();
    if (filterState.type !== "459" && filterState.type) {
      params.append("type", filterState.type);
    // } else {
    //   params.delete("type");
    }
    if (filterState.state) {
      params.append("state", filterState.state);
    // } else {
    //   params.delete("state");
    }

    if (filterState.environment !== "0") {
      params.append("environment", filterState.environment);
    // } else {
    //   params.delete("environment");
    }

    if (filterState.service !== "0") {
      params.append("service", filterState.service);
    // } else {
    //   params.delete("service");
    }

    if (filterState.product !== "") {
      params.append("product", filterState.product);
    // } else {
    //   params.delete("product");
    }

    if (filterState.release !== "0") {
      params.append("release", filterState.release);
    // } else {
    //   params.delete("release");
    }

    if (filterState.deploymentStatus !== "0") {
      params.append("deploymentStatus", filterState.deploymentStatus);
    // } else {
    //   params.delete("deploymentStatus");
    }

    history.push({
      pathname: pathname,
      search: params.toString(),
    });

    // Compatiblity with favourite button.
    jQuery("#star-favorites-add").attr("action", pathname + "?" + params.toString());
    jQuery("input[name=path]").val(pathname);
    jQuery("input[name=query]").val(params.toString());
    // @todo Set Title correctly.
    // jQuery("input[name=title]").val("XYZ");

    // Reset Pagination.
    setPage(1);
  }, [filterState, activeKey]);

  // Changes active tab and sets releaseStatus accordingly.
  const handleNav = (k) => {
    setActiveKey(k);
    let val = {};
    val["items_per_page"] = "20";
    if (["1", "2", "3", "5"].includes(k)) {
      val["releaseStatus"] = k;
    }
    setFilterState(prev => ({ ...prev, ...val }));
  }

  return (
    <div>
      <p>Dev-Build 1.0-1</p>
      <Nav bsStyle="tabs" activeKey={activeKey}>
        <NavItem eventKey="1" onSelect={handleNav}>
          Bereitgestellt
        </NavItem>
        <NavItem eventKey="2" onSelect={handleNav}>
          In Bearbeitung
        </NavItem>
        <NavItem eventKey="3" onSelect={handleNav}>
          Gesperrt
        </NavItem>
        { ["ZRMK", "SITE-ADMIN"].includes(global.drupalSettings.role) && 
        <NavItem eventKey="5" onSelect={handleNav}>
          Archiviert
        </NavItem>
        }
        <NavItem eventKey="4" onSelect={handleNav}>
          Eingesetzt
        </NavItem>
        <NavItem eventKey="6" onSelect={handleNav}>
          Eingesetzt(Übersicht)
        </NavItem>
        <NavItem eventKey="7" onSelect={handleNav}>
          Einsatzinformationen
        </NavItem>
      </Nav>
      { ["1", "2", "3", "5"].includes(activeKey) &&
        <ReleaseTableManager
          filterState={filterState}
          setFilterState={setFilterState}
          page={page}
          setPage={setPage}
          activeKey={activeKey}
        />
      }
      {activeKey == "4" &&
        <DeployedReleasesManager
          filterState={filterState}
          setFilterState={setFilterState}
          page={page}
          setPage={setPage}
          detail={false}
          activeKey={activeKey}
        />
      }
      {activeKey == "7" &&
        <DeployedReleasesManager
          filterState={filterState}
          setFilterState={setFilterState}
          page={page}
          setPage={setPage}
          detail={true}
          activeKey={activeKey}
        />
      }
      { activeKey == "6" &&
        <Ers />
      }
    </div>
  );
}
