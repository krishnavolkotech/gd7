import React from 'react'
import { Button } from 'react-bootstrap';

export default function EinsatzmeldungsZeile({ deployment, handleAction }) {
  const date = new Date(deployment.attributes.field_date_deployed);
  const localeDate = date.toLocaleDateString('de-DE', {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
  });
  const userState = deployment.attributes.field_user_state;
  const environment = deployment.attributes.field_environment;
  const service = deployment.serviceNid;
  const release = deployment.releaseNid;
  const deploymentId = deployment.id;
  console.log(deployment);

  return (
    <tbody>
      <td>{global.drupalSettings.states[deployment.attributes.field_user_state]}</td>
      <td>{global.drupalSettings.environments[deployment.attributes.field_environment]}</td>
      <td>{deployment.service}</td>
      <td>{deployment.release}</td>
      <td>{localeDate}</td>
      <td><Button onClick={() => handleAction(userState, environment, service, release, deploymentId)}>Nachfolgerelease melden</Button></td>
    </tbody>
  )
}
