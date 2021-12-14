
import React from 'react'
import { Button, OverlayTrigger, Tooltip } from 'react-bootstrap';

export default function DeployedReleasesTableRow({ deployment, handleAction, highlight, handleArchive, handleEdit, deploymentStatus, handleView }) {
  const date = new Date(deployment.date);
  const localeDate = date.toLocaleDateString('de-DE', {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
  });

  return (
    <tr>
      <td>{global.drupalSettings.states[deployment.state]}</td>
      <td>{global.drupalSettings.environments[deployment.environment]}</td>
      <td>{deployment.service}</td>
      <td>{deployment.release}</td>
      <td>{localeDate}</td>
      <td>
        Aktion
      </td>
    </tr>
  );
}
