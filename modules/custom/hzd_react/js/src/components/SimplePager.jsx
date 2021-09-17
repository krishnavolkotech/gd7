import React from "react";
import { Pager } from "react-bootstrap";

export default function SimplePager(props) {
  return (
    <div>
      <Pager>
        <Pager.Item disabled={props.page == 1} previous name="previous" onClick={props.handlePagination}>
          &larr; Vorherige Seite
        </Pager.Item>
        <Pager.Item disabled={props.count < 50} next name="next" onClick={props.handlePagination}>
          Nächste Seite &rarr;
        </Pager.Item>
      </Pager>
      <p>Aktuelle Seite: {props.page}</p>
    </div>
  );
}