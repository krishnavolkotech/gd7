<?php

function anonymize_tables_list() {
  return array(
      'group__field_group_body' => array(
          'columns' => array(
              array('column' => 'field_group_body_value', 'replace' => '')
          )
      ),
      'node_field_data' => array(
          'columns' => array(
              array('column' =>'title', 'replace' => '')
          )
      ),
      'node_field_revision' => array(
          'columns' => array(
              array('column' =>'title', 'replace' => '')
          )
      ),
      'node__body' => array(
          'columns' => array(
              array('column' =>'body_value', 'replace' => ''),
              array('column' =>'body_summary', 'replace' => 'Lorum Ipsum'),
          )
      ),
      'node_revision__body' => array(
          'columns' => array(
              array('column' =>'body_value', 'replace' => 'Lorum Ipsum'),
              array('column' =>'body_summary', 'replace' => 'Lorum Ipsum')
          )
      ),
      'node__field_abnormality_description' => array('columns' => array( array('column' =>'field_abnormality_description_value', 'replace' => ''))),
      'node_revision__field_abnormality_description' => array('columns' => array( array('column' =>'field_abnormality_description_value', 'replace' => ''))),
      'node__field_installation_duration' => array('columns' => array( array('column' =>'field_installation_duration_value', 'replace' => ''))),
      'node_revision__field_installation_duration' => array('columns' => array( array('column' =>'field_installation_duration_value', 'replace' => ''))),
      'node__field_custom_title' => array('columns' => array( array('column' =>'field_custom_title_value', 'replace' => ''))),
      'node_revision__field_custom_title' => array('columns' => array( array('column' =>'field_custom_title_value', 'replace' => ''))),
      'node__field_related_transfer_number' => array('columns' => array( array('column' =>'field_related_transfer_number_value', 'replace' => ''))),
      'node_revision__field_related_transfer_number' => array('columns' => array( array('column' =>'field_related_transfer_number_value', 'replace' => ''))),
      'node__field_intial_situation' => array('columns' => array( array('column' =>'field_intial_situation_value', 'replace' => ''))),
      'node_revision__field_intial_situation' => array('columns' => array( array('column' =>'field_intial_situation_value', 'replace' => ''))),
      'node__field_professional_conditions' => array('columns' => array( array('column' =>'field_professional_conditions_value', 'replace' => ''))),
      'node_revision__field_professional_conditions' => array('columns' => array( array('column' =>'field_professional_conditions_value', 'replace' => ''))),
      'node__field_tech_requirements' => array('columns' => array( array('column' =>'field_tech_requirements_value', 'replace' => ''))),
      'node_revision__field_tech_requirements' => array('columns' => array( array('column' =>'field_tech_requirements_value', 'replace' => ''))),
      'node__field_depend_other_services' => array('columns' => array( array('column' =>'field_depend_other_services_value', 'replace' => ''))),
      'node_revision__field_depend_other_services' => array('columns' => array( array('column' =>'field_depend_other_services_value', 'replace' => ''))),
      'node__field_business_impact' => array('columns' => array( array('column' =>'field_business_impact_value', 'replace' => ''))),
      'node_revision__field_business_impact' => array('columns' => array( array('column' =>'field_business_impact_value', 'replace' => ''))),
      'node__field_technical_impact' => array('columns' => array( array('column' =>'field_technical_impact_value', 'replace' => ''))),
      'node_revision__field_technical_impact' => array('columns' => array( array('column' =>'field_technical_impact_value', 'replace' => ''))),
      'node__field_business_impact_services' => array('columns' => array( array('column' =>'field_business_impact_services_value', 'replace' => ''))),
      'node_revision__field_business_impact_services' => array('columns' => array( array('column' =>'field_business_impact_services_value', 'replace' => ''))),
      'node__field_technical_impact_services' => array('columns' => array( array('column' =>'field_technical_impact_services_value', 'replace' => ''))),
      'node_revision__field_technical_impact_services' => array('columns' => array( array('column' =>'field_technical_impact_services_value', 'replace' => ''))),
      'node__field_special_notes' => array('columns' => array( array('column' =>'field_special_notes_value', 'replace' => ''))),
      'node_revision__field_special_notes' => array('columns' => array( array('column' =>'field_special_notes_value', 'replace' => ''))),
      'node__field_dates' => array('columns' => array( array('column' =>'field_dates_value', 'replace' => ''))),
      'node_revision__field_dates' => array('columns' => array( array('column' =>'field_dates_value', 'replace' => ''))),
      'node__field_creators' => array('columns' => array( array('column' =>'field_creators_value', 'replace' => ''))),
      'node_revision__field_creators' => array('columns' => array( array('column' =>'field_creators_value', 'replace' => ''))),
      'node__field_contact' => array('columns' => array( array('column' =>'field_contact_value', 'replace' => ''))),
      'node_revision__field_contact' => array('columns' => array( array('column' =>'field_contact_value', 'replace' => ''))),
      'node__field_additional_email_recipient' => array('columns' => array( array('column' =>'field_additional_email_recipient_value', 'replace' => ''))),
      'node_revision__field_additional_email_recipient' => array('columns' => array( array('column' =>'field_additional_email_recipient_value', 'replace' => ''))),
      'node__field_author_state' => array('columns' => array( array('column' =>'field_author_state_value', 'replace' => ''))),
      'node_revision__field_author_state' => array('columns' => array( array('column' =>'field_author_state_value', 'replace' => ''))),
      'node__field_author_name' => array('columns' => array( array('column' =>'field_author_name_value', 'replace' => ''))),
      'node_revision__field_author_name' => array('columns' => array( array('column' =>'field_author_name_value', 'replace' => ''))),
      'node__field_closed' => array('columns' => array( array('column' =>'field_closed_value', 'replace' => ''))),
      'node_revision__field_closed' => array('columns' => array( array('column' =>'field_closed_value', 'replace' => ''))),
      'node__field_comments' => array('columns' => array( array('column' =>'field_comments_value', 'replace' => ''))),
      'node_revision__field_comments' => array('columns' => array( array('column' =>'field_comments_value', 'replace' => ''))),
      'node__field_eroffnet' => array('columns' => array( array('column' =>'field_eroffnet_value', 'replace' => ''))),
      'node_revision__field_eroffnet' => array('columns' => array( array('column' =>'field_eroffnet_value', 'replace' => ''))),
      'node__field_attachment' => array('columns' => array( array('column' =>'field_attachment_value', 'replace' => ''))),
      'node_revision__field_attachment' => array('columns' => array( array('column' =>'field_attachment_value', 'replace' => ''))),
      'node__field_diagnose' => array('columns' => array( array('column' =>'field_diagnose_value', 'replace' => ''))),
      'node_revision__field_diagnose' => array('columns' => array( array('column' =>'field_diagnose_value', 'replace' => ''))),
      'node__field_function' => array('columns' => array( array('column' =>'field_function_value', 'replace' => ''))),
      'node_revision__field_function' => array('columns' => array( array('column' =>'field_function_value', 'replace' => ''))),
      'node__field_processing' => array('columns' => array( array('column' =>'field_processing_value', 'replace' => ''))),
      'node_revision__field_processing' => array('columns' => array( array('column' =>'field_processing_value', 'replace' => ''))),
      'node__field_priority' => array('columns' => array( array('column' =>'field_priority_value', 'replace' => ''))),
      'node_revision__field_priority' => array('columns' => array( array('column' =>'field_priority_value', 'replace' => ''))),
      'node__field_problem_symptoms' => array('columns' => array( array('column' =>'field_problem_symptoms_value', 'replace' => ''))),
      'node_revision__field_problem_symptoms' => array('columns' => array( array('column' =>'field_problem_symptoms_value', 'replace' => ''))),
      'node__field_release' => array('columns' => array( array('column' =>'field_release_value', 'replace' => ''))),
      'node_revision__field_release' => array('columns' => array( array('column' =>'field_release_value', 'replace' => ''))),
      'node__field_solution' => array('columns' => array( array('column' =>'field_solution_value', 'replace' => ''))),
      'node_revision__field_solution' => array('columns' => array( array('column' =>'field_solution_value', 'replace' => ''))),
      'node__field_task_force' => array('columns' => array( array('column' =>'field_task_force_value', 'replace' => ''))),
      'node_revision__field_task_force' => array('columns' => array( array('column' =>'field_task_force_value', 'replace' => ''))),
      'node__field_ticketstore_link' => array('columns' => array( array('column' =>'field_ticketstore_link_value', 'replace' => ''))),
      'node_revision__field_ticketstore_link' => array('columns' => array( array('column' =>'field_ticketstore_link_value', 'replace' => ''))),
      'node__field_version' => array('columns' => array( array('column' =>'field_version_value', 'replace' => ''))),
      'node_revision__field_version' => array('columns' => array( array('column' =>'field_version_value', 'replace' => ''))),
      'node__field_work_around' => array('columns' => array( array('column' =>'field_work_around_value', 'replace' => ''))),
      'node_revision__field_work_around' => array('columns' => array( array('column' =>'field_work_around_value', 'replace' => ''))),
      'node__field_documentation_link' => array('columns' => array( array('column' =>'field_documentation_link_value', 'replace' => ''))),
      'node_revision__field_documentation_link' => array('columns' => array( array('column' =>'field_documentation_link_value', 'replace' => ''))),
      'node__field_link' => array('columns' => array( array('column' =>'field_link_value', 'replace' => ''))),
      'node_revision__field_link' => array('columns' => array( array('column' =>'field_link_value', 'replace' => ''))),
      'node__field_release_comments' => array('columns' => array( array('column' =>'field_release_comments_value', 'replace' => ''))),
      'node_revision__field_release_comments' => array('columns' => array( array('column' =>'field_release_comments_value', 'replace' => ''))),
      'node__field_calculated_title' => array('columns' => array( array('column' =>'field_calculated_title_value', 'replace' => ''))),
      'node_revision__field_calculated_title' => array('columns' => array( array('column' =>'field_calculated_title_value', 'replace' => ''))),
      'node__field_comments' => array('columns' => array( array('column' =>'field_comments_value', 'replace' => ''))),
      'node_revision__field_comments' => array('columns' => array( array('column' =>'field_comments_value', 'replace' => ''))),
      'node__field_trend' => array('columns' => array( array('column' =>'field_trend_value', 'replace' => ''))),
      'node_revision__field_trend' => array('columns' => array( array('column' =>'field_trend_value', 'replace' => ''))),
      'node__field_arbeitslog' => array('columns' => array( array('column' =>'field_arbeitslog_value', 'replace' => ''))),
      'node_revision__field_arbeitslog' => array('columns' => array( array('column' =>'field_arbeitslog_value', 'replace' => ''))),
      'node__field_remarks' => array('columns' => array( array('column' =>'field_remarks_value', 'replace' => ''))),
      'node_revision__field_remarks' => array('columns' => array( array('column' =>'field_remarks_value', 'replace' => ''))),
      'node__field_affected_oes' => array('columns' => array( array('column' =>'field_affected_oes_value', 'replace' => ''))),
      'node_revision__field_affected_oes' => array('columns' => array( array('column' =>'field_affected_oes_value', 'replace' => ''))),
      'node__field_arbeitslog' => array('columns' => array( array('column' =>'field_arbeitslog_value', 'replace' => ''))),  
      'node_revision__field_arbeitslog' => array('columns' => array( array('column' =>'field_arbeitslog_value', 'replace' => ''))),  
      'node__field_remarks' => array('columns' => array( array('column' =>'field_remarks_value', 'replace' => ''))),
      'node_revision__field_remarks' => array('columns' => array( array('column' =>'field_remarks_value', 'replace' => ''))),
      'node__field_expected_result' => array('columns' => array( array('column' =>'field_expected_result_value', 'replace' => ''))),
      'node_revision__field_expected_result' => array('columns' => array( array('column' =>'field_expected_result_value', 'replace' => ''))),
      'node__field_impact' => array('columns' => array( array('column' =>'field_impact_value', 'replace' => ''))),
      'node_revision__field_impact' => array('columns' => array( array('column' =>'field_impact_value', 'replace' => ''))),
      'comment__comment_body' => array(
          'columns' => array(
              array('column' =>'comment_body_value', 'replace' => '')
          )
      ),
      'comment_field_data' => array(
          'columns' => array(
              array('column' =>'subject', 'replace' => ''),
              array('column' =>'name', 'replace' => ''),
          )
      ),
      'file_managed' => array(
          'columns' => array(
              array('column' => 'filename', 'replace' => 'Lorum Ipsum'),
              array('column' => 'origname', 'replace' => 'lorum-ipsum.txt'),
              array('column' => 'uri', 'replace' => 'private://lorum-ipsum.txt'),
          )
      ),
      'cust_profile' => array(
          'columns' => array(
              array('column' => 'firstname', 'replace' => 'First Name'),
              array('column' => 'lastname', 'replace' => 'Last Name'),
              array('column' => 'position', 'replace' => 'test'),
              array('column' => 'phone', 'replace' => '0000000000'),
          )
      ),
      'users_field_data' => array(
          'columns' => array(
              array('column' => 'name', 'replace' => ''),
              array('column' => 'pass', 'replace' => ''),
              array('column' => 'mail', 'replace' => ''),
          )
      ),
      'downtimes' => array(
          'columns' => array(
              array('column' => 'description', 'replace' => 'Lorum Ipsum'),
          ),
      ),
      'downtimes_logs' => array(
          'columns' => array(
              array('column' => 'log', 'replace' => 'a:0:{}'),
          ),
      ),
      'im_attachments_data' => array(
          'columns' => array(
              array('column' => 'description__value', 'replace' => ''),
              array('column' => 'ticket_id', 'replace' => '123456'),
          ),
      ),
      'group__field_description' => array(
          'columns' => array(
              array('column' => 'field_description_value', 'replace' => ''),
          ),
      ),
      'path_alias' => array(
          'columns' => array(
              array('column' => 'alias', 'replace' => ''),
          ),
      ),
      'resolve_cancel_incident' => array(
          'columns' => array(
              array('column' => 'comment', 'replace' => 'Lorum Ipsum'),
          ),
      ),
      'group__field_einfuehrung' => array(
          'columns' => array(
              array('column' => 'field_einfuehrung_value', 'replace' => 'Lorum Ipsum'),
          ),
      ),
  );
}
      
?>
