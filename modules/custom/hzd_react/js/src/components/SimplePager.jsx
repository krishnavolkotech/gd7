import React from "react";
import { Pagination } from "react-bootstrap";

export default function SimplePager(props) {
  return (
    <Pagination>
      <Pagination.First
        disabled={props.page == 1}
        onClick={() => {
          if (props.page > 1) {
            props.setPage(1);
            props.setCount(prev => prev + 1);
          }
        }}
      />
      <Pagination.Prev
        disabled={props.page == 1}
        onClick={() => {
          if (props.page > 1) {
            props.setPage(prev => prev - 1);
            props.setCount(prev => prev + 1);
          }
        }}
      />
      {props.page > 1 &&
        <Pagination.Item disabled>...</Pagination.Item>
      }
      <Pagination.Item disabled>{props.page}</Pagination.Item>
      {props.count == props.items_per_page &&
        <Pagination.Item disabled>...</Pagination.Item>
      }
      <Pagination.Next
        disabled={props.count < props.items_per_page}
        onClick={() => {
          if (props.count == props.items_per_page) {
            props.setPage(prev => prev + 1);
            props.setCount(prev => prev + 1);
          }
        }}
      />
    </Pagination>
  );
}