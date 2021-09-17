import React, {useState, useEffect} from 'react'
import { Button, Modal } from 'react-bootstrap';

const NodeSkeleton = () => {
  return (
    <div>
      <div className="panel panel-default">
        <div className="panel-heading">
          <div className="skeleton-label loading"></div>
        </div>
        <div className="panel-body">
          <div className="skeleton-label loading"></div>
          <div className="skeleton-textbody loading"></div>
          <div className="skeleton-label loading"></div>
          <div className="skeleton-textbody loading"></div>
          <div className="skeleton-label loading"></div>
          <div className="skeleton-textbody loading"></div>
          <div className="skeleton-label loading"></div>
          <div className="skeleton-textbody loading"></div>
        </div>
      </div>
      <div className="panel panel-default">
        <div className="panel-heading">
          <div className="skeleton-label loading"></div>
        </div>
        <div className="panel-body">
          <div className="skeleton-label loading"></div>
          <div className="skeleton-textbody loading"></div>
          <div className="skeleton-label loading"></div>
          <div className="skeleton-textbody loading"></div>
          <div className="skeleton-label loading"></div>
          <div className="skeleton-textbody loading"></div>
          <div className="skeleton-label loading"></div>
          <div className="skeleton-textbody loading"></div>
        </div>
      </div>
    </div>
  );
}
export default function NodeView({ nid, setViewNode}) {
  const [body, setBody] = useState(false);

  useEffect(() => {
    fetch('/node/' + nid + '?_wrapper_format=drupal_modal')
      .then(response => response.json())
      .then(result => {
        setBody(<div dangerouslySetInnerHTML={{__html: result[4].data}} />);
      })
      .catch(error => console.log(error));
  }, [])

  return (
    <div>
      <Modal show={true} onHide={() => setViewNode(false)}>
        <Modal.Header closeButton>
          <Modal.Title>Einsatzmeldung</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          { !body && 
          <NodeSkeleton />
          }
          {body}
        </Modal.Body>
        <Modal.Footer>
          <Button bsStyle="primary" onClick={() => setViewNode(false)}>Schließen</Button>
        </Modal.Footer>
      </Modal>
    </div>
  )
}
