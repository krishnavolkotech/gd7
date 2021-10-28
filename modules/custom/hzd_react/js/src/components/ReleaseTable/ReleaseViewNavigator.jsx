import React, { useState, useEffect } from 'react';
import { Nav, NavItem } from 'react-bootstrap';
import { useHistory } from 'react-router-dom';
import Ers from './Ers';
import ReleaseTableManager from './ReleaseTableManager';

export default function ReleaseViewNavigator() {
  /** @const {object} history - The history object (URL modifications). */
  const history = useHistory()

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
  if (history.location.pathname.indexOf('archiviert') > 0) {
    active = "5";
  }
  if (history.location.pathname.indexOf('eingesetzt-uebersicht') > 0) {
    active = "6";
  }

  const [activeKey, setActiveKey] = useState(active);

  /**
   * Changes URL-Params depending on Nav / Filters, resets Pagination.
   * 
   * Implements hook useEffect().
  */
  useEffect(() => {
    let pathname = history.location.pathname;
    let explodedPath = pathname.split("/");
    explodedPath[explodedPath.length - 1] = "in-bearbeitung";

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
      default:
        explodedPath[explodedPath.length - 1] = "bereitgestellt";
        break;
    }
    pathname = explodedPath.join("/");

    history.push({
      pathname: pathname,
    });
  }, [activeKey]);


  const handleNav = (k) => {
    setActiveKey(k);
  }
  console.log(global.drupalSettings)
  return (
    <div>
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
      </Nav>
      { activeKey != "6" &&
        <ReleaseTableManager activeKey={activeKey} />
      }
      { activeKey == "6" &&
        <Ers />
      }
    </div>
  );
}
