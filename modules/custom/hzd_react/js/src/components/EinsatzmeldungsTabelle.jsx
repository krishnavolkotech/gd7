import React from 'react'

export default function EinsatzmeldungsTabelle({ data }) {
  return (
    <table>
      <thead>
        <tr>
          <th>Land</th>
          <th>Umgebung</th>
          <th>Verfahren</th>
          <th>Release</th>
          <th>Eingesetzt am</th>
          <th>Aktion</th>
        </tr>
      </thead>
        { data.length ? data.map(deployment => {
          const date = new Date(deployment.attributes.field_date_deployed);
          const localeDate = date.toLocaleDateString('de-DE', {
            year: "numeric",
            month: "2-digit",
            day: "2-digit",
          });
          return(
            <tbody>
              <td>{global.drupalSettings.states[deployment.attributes.field_user_state]}</td>
              <td>{global.drupalSettings.environments[deployment.attributes.field_environment]}</td>
              <td>{deployment.service}</td>
              <td>{deployment.release}</td>
              <td>{localeDate}</td>
              <td><button>Aktion</button></td>
            </tbody>
          );
        }) : <tbody><td>Daten nicht da.</td></tbody> }
    </table>
  )
}
