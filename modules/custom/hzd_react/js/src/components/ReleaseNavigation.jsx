import React, { useState, useEffect } from 'react';
import { Nav, NavItem, Navbar, NavDropdown, MenuItem, ButtonToolbar, ButtonGroup, Button, Col, Row } from 'react-bootstrap';
import { Link, NavLink } from "react-router-dom";

function ReleaseNavigation({ serviceType, handleServiceType, releaseType, handleReleaseType }) {
  
  let disabled = false;
  if (serviceType === 460) {
    disabled = true;
  }

  return (
    <div>
      <Row>
        <Col sm={12}>
        <Nav bsStyle="pills" activeKey={serviceType} onSelect={handleServiceType}>
          <NavItem eventKey={459}>
            KONSENS
          </NavItem>
          <NavItem eventKey={460}>
            Best / Fakt
          </NavItem>
        </Nav>
        </Col>
      </Row>
      <p />
      <Row>
        <Col sm={6}>
          <Nav bsStyle="pills" activeKey={releaseType} onSelect={handleReleaseType}>
            <NavItem eventKey={1}>
              Bereitgestellt
            </NavItem>
            <NavItem eventKey={2} disabled={disabled}>
              In Bearbeitung
            </NavItem>
            <NavItem eventKey={3} disabled={disabled}>
              Gesperrt
            </NavItem>
          </Nav>
        </Col>
        <Col sm={6}>
          <Nav pullRight>
            <Link to="/r/home"><Button bsStyle="link">Zurück zur Übersicht</Button></Link>
          </Nav>
        </Col>
      </Row>
    </div>
  );
}

export default ReleaseNavigation;