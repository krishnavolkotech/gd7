<?php

function anonymize_tables_list() {
  return array(
      'group__field_group_body' => array(
          'säulen' => array(
              array('säule' => 'field_group_body_value', 'replace' => '')
          )
      ),
      'node_field_data' => array(
          'säulen' => array(
              array('säule' =>'title', 'replace' => '')
          )
      ),
      'node_field_revision' => array(
          'säulen' => array(
              array('säule' =>'title', 'replace' => '')
          )
      ),
      'node__body' => array(
          'säulen' => array(
              array('säule' =>'body_value', 'replace' => '')
          )
      ),
      'node__field_abnormality_description' => array('säulen' => array( array('säule' =>'field_abnormality_description_value', 'replace' => ''))),
      'node_revision__field_abnormality_description' => array('säulen' => array( array('säule' =>'field_abnormality_description_value', 'replace' => ''))),
      'node__field_installation_duration' => array('säulen' => array( array('säule' =>'field_installation_duration_value', 'replace' => ''))),
      'node_revision__field_installation_duration' => array('säulen' => array( array('säule' =>'field_installation_duration_value', 'replace' => ''))),
      'node__field_custom_title' => array('säulen' => array( array('säule' =>'field_custom_title_value', 'replace' => ''))),
      'node_revision__field_custom_title' => array('säulen' => array( array('säule' =>'field_custom_title_value', 'replace' => ''))),
      'node__field_related_transfer_number' => array('säulen' => array( array('säule' =>'field_related_transfer_number_value', 'replace' => ''))),
      'node_revision__field_related_transfer_number' => array('säulen' => array( array('säule' =>'field_related_transfer_number_value', 'replace' => ''))),
      'node__field_intial_situation' => array('säulen' => array( array('säule' =>'field_intial_situation_value', 'replace' => ''))),
      'node_revision__field_intial_situation' => array('säulen' => array( array('säule' =>'field_intial_situation_value', 'replace' => ''))),
      'node__field_professional_conditions' => array('säulen' => array( array('säule' =>'field_professional_conditions_value', 'replace' => ''))),
      'node_revision__field_professional_conditions' => array('säulen' => array( array('säule' =>'field_professional_conditions_value', 'replace' => ''))),
      'node__field_tech_requirements' => array('säulen' => array( array('säule' =>'field_tech_requirements_value', 'replace' => ''))),
      'node_revision__field_tech_requirements' => array('säulen' => array( array('säule' =>'field_tech_requirements_value', 'replace' => ''))),
      'node__field_depend_other_services' => array('säulen' => array( array('säule' =>'field_depend_other_services_value', 'replace' => ''))),
      'node_revision__field_depend_other_services' => array('säulen' => array( array('säule' =>'field_depend_other_services_value', 'replace' => ''))),
      'node__field_business_impact' => array('säulen' => array( array('säule' =>'field_business_impact_value', 'replace' => ''))),
      'node_revision__field_business_impact' => array('säulen' => array( array('säule' =>'field_business_impact_value', 'replace' => ''))),
      'node__field_technical_impact' => array('säulen' => array( array('säule' =>'field_technical_impact_value', 'replace' => ''))),
      'node_revision__field_technical_impact' => array('säulen' => array( array('säule' =>'field_technical_impact_value', 'replace' => ''))),
      'node__field_business_impact_services' => array('säulen' => array( array('säule' =>'field_business_impact_services_value', 'replace' => ''))),
      'node_revision__field_business_impact_services' => array('säulen' => array( array('säule' =>'field_business_impact_services_value', 'replace' => ''))),
      'node__field_technical_impact_services' => array('säulen' => array( array('säule' =>'field_technical_impact_services_value', 'replace' => ''))),
      'node_revision__field_technical_impact_services' => array('säulen' => array( array('säule' =>'field_technical_impact_services_value', 'replace' => ''))),
      'node__field_special_notes' => array('säulen' => array( array('säule' =>'field_special_notes_value', 'replace' => ''))),
      'node_revision__field_special_notes' => array('säulen' => array( array('säule' =>'field_special_notes_value', 'replace' => ''))),
      'node__field_dates' => array('säulen' => array( array('säule' =>'field_dates_value', 'replace' => ''))),
      'node_revision__field_dates' => array('säulen' => array( array('säule' =>'field_dates_value', 'replace' => ''))),
      'node__field_creators' => array('säulen' => array( array('säule' =>'field_creators_value', 'replace' => ''))),
      'node_revision__field_creators' => array('säulen' => array( array('säule' =>'field_creators_value', 'replace' => ''))),
      'node__field_contact' => array('säulen' => array( array('säule' =>'field_contact_value', 'replace' => ''))),
      'node_revision__field_contact' => array('säulen' => array( array('säule' =>'field_contact_value', 'replace' => ''))),
      'node__field_additional_email_recipient' => array('säulen' => array( array('säule' =>'field_additional_email_recipient_value', 'replace' => ''))),
      'node_revision__field_additional_email_recipient' => array('säulen' => array( array('säule' =>'field_additional_email_recipient_value', 'replace' => ''))),
      'node__field_author_state' => array('säulen' => array( array('säule' =>'field_author_state_value', 'replace' => ''))),
      'node_revision__field_author_state' => array('säulen' => array( array('säule' =>'field_author_state_value', 'replace' => ''))),
      'node__field_author_name' => array('säulen' => array( array('säule' =>'field_author_name_value', 'replace' => ''))),
      'node_revision__field_author_name' => array('säulen' => array( array('säule' =>'field_author_name_value', 'replace' => ''))),
      'node__field_closed' => array('säulen' => array( array('säule' =>'field_closed_value', 'replace' => ''))),
      'node_revision__field_closed' => array('säulen' => array( array('säule' =>'field_closed_value', 'replace' => ''))),
      'node__field_comments' => array('säulen' => array( array('säule' =>'field_comments_value', 'replace' => ''))),
      'node_revision__field_comments' => array('säulen' => array( array('säule' =>'field_comments_value', 'replace' => ''))),
      'node__field_eroffnet' => array('säulen' => array( array('säule' =>'field_eroffnet_value', 'replace' => ''))),
      'node_revision__field_eroffnet' => array('säulen' => array( array('säule' =>'field_eroffnet_value', 'replace' => ''))),
      'node__field_attachment' => array('säulen' => array( array('säule' =>'field_attachment_value', 'replace' => ''))),
      'node_revision__field_attachment' => array('säulen' => array( array('säule' =>'field_attachment_value', 'replace' => ''))),
      'node__field_diagnose' => array('säulen' => array( array('säule' =>'field_diagnose_value', 'replace' => ''))),
      'node_revision__field_diagnose' => array('säulen' => array( array('säule' =>'field_diagnose_value', 'replace' => ''))),
      'node__field_function' => array('säulen' => array( array('säule' =>'field_function_value', 'replace' => ''))),
      'node_revision__field_function' => array('säulen' => array( array('säule' =>'field_function_value', 'replace' => ''))),
      'node__field_processing' => array('säulen' => array( array('säule' =>'field_processing_value', 'replace' => ''))),
      'node_revision__field_processing' => array('säulen' => array( array('säule' =>'field_processing_value', 'replace' => ''))),
      'node__field_priority' => array('säulen' => array( array('säule' =>'field_priority_value', 'replace' => ''))),
      'node_revision__field_priority' => array('säulen' => array( array('säule' =>'field_priority_value', 'replace' => ''))),
      'node__field_problem_status' => array('säulen' => array( array('säule' =>'field_problem_status_value', 'replace' => ''))),
      'node_revision__field_problem_status' => array('säulen' => array( array('säule' =>'field_problem_status_value', 'replace' => ''))),
      'node__field_problem_symptoms' => array('säulen' => array( array('säule' =>'field_problem_symptoms_value', 'replace' => ''))),
      'node_revision__field_problem_symptoms' => array('säulen' => array( array('säule' =>'field_problem_symptoms_value', 'replace' => ''))),
      'node__field_release' => array('säulen' => array( array('säule' =>'field_release_value', 'replace' => ''))),
      'node_revision__field_release' => array('säulen' => array( array('säule' =>'field_release_value', 'replace' => ''))),
      'node__field_solution' => array('säulen' => array( array('säule' =>'field_solution_value', 'replace' => ''))),
      'node_revision__field_solution' => array('säulen' => array( array('säule' =>'field_solution_value', 'replace' => ''))),
      'node__field_task_force' => array('säulen' => array( array('säule' =>'field_task_force_value', 'replace' => ''))),
      'node_revision__field_task_force' => array('säulen' => array( array('säule' =>'field_task_force_value', 'replace' => ''))),
      'node__field_ticketstore_link' => array('säulen' => array( array('säule' =>'field_ticketstore_link_value', 'replace' => ''))),
      'node_revision__field_ticketstore_link' => array('säulen' => array( array('säule' =>'field_ticketstore_link_value', 'replace' => ''))),
      'node__field_version' => array('säulen' => array( array('säule' =>'field_version_value', 'replace' => ''))),
      'node_revision__field_version' => array('säulen' => array( array('säule' =>'field_version_value', 'replace' => ''))),
      'node__field_work_around' => array('säulen' => array( array('säule' =>'field_work_around_value', 'replace' => ''))),
      'node_revision__field_work_around' => array('säulen' => array( array('säule' =>'field_work_around_value', 'replace' => ''))),
      'node__field_documentation_link' => array('säulen' => array( array('säule' =>'field_documentation_link_value', 'replace' => ''))),
      'node_revision__field_documentation_link' => array('säulen' => array( array('säule' =>'field_documentation_link_value', 'replace' => ''))),
      'node__field_link' => array('säulen' => array( array('säule' =>'field_link_value', 'replace' => ''))),
      'node_revision__field_link' => array('säulen' => array( array('säule' =>'field_link_value', 'replace' => ''))),
      'node__field_release_comments' => array('säulen' => array( array('säule' =>'field_release_comments_value', 'replace' => ''))),
      'node_revision__field_release_comments' => array('säulen' => array( array('säule' =>'field_release_comments_value', 'replace' => ''))),
      'node__field_calculated_title' => array('säulen' => array( array('säule' =>'field_calculated_title_value', 'replace' => ''))),
      'node_revision__field_calculated_title' => array('säulen' => array( array('säule' =>'field_calculated_title_value', 'replace' => ''))),
      'node__field_comments' => array('säulen' => array( array('säule' =>'field_comments_value', 'replace' => ''))),
      'node_revision__field_comments' => array('säulen' => array( array('säule' =>'field_comments_value', 'replace' => ''))),
      'node__field_trend' => array('säulen' => array( array('säule' =>'field_trend_value', 'replace' => ''))),
      'node_revision__field_trend' => array('säulen' => array( array('säule' =>'field_trend_value', 'replace' => ''))),
      'node__field_arbeitslog' => array('säulen' => array( array('säule' =>'field_arbeitslog_value', 'replace' => ''))),
      'node_revision__field_arbeitslog' => array('säulen' => array( array('säule' =>'field_arbeitslog_value', 'replace' => ''))),
      'node__field_remarks' => array('säulen' => array( array('säule' =>'field_remarks_value', 'replace' => ''))),
      'node_revision__field_remarks' => array('säulen' => array( array('säule' =>'field_remarks_value', 'replace' => ''))),
      'node__field_affected_oes' => array('säulen' => array( array('säule' =>'field_affected_oes_value', 'replace' => ''))),
      'node_revision__field_affected_oes' => array('säulen' => array( array('säule' =>'field_affected_oes_value', 'replace' => ''))),
      'node__field_risk_category' => array('säulen' => array( array('säule' =>'field_risk_category_value', 'replace' => ''))),
      'node_revision__field_risk_category' => array('säulen' => array( array('säule' =>'field_risk_category_value', 'replace' => ''))),
      'node__field_arbeitslog' => array('säulen' => array( array('säule' =>'field_arbeitslog_value', 'replace' => ''))),  
      'node_revision__field_arbeitslog' => array('säulen' => array( array('säule' =>'field_arbeitslog_value', 'replace' => ''))),  
      'node__field_remarks' => array('säulen' => array( array('säule' =>'field_remarks_value', 'replace' => ''))),
      'node_revision__field_remarks' => array('säulen' => array( array('säule' =>'field_remarks_value', 'replace' => ''))),
      'node__field_expected_result' => array('säulen' => array( array('säule' =>'field_expected_result_value', 'replace' => ''))),
      'node_revision__field_expected_result' => array('säulen' => array( array('säule' =>'field_expected_result_value', 'replace' => ''))),
      'node__field_impact' => array('säulen' => array( array('säule' =>'field_impact_value', 'replace' => ''))),
      'node_revision__field_impact' => array('säulen' => array( array('säule' =>'field_impact_value', 'replace' => ''))),
      'comment__comment_body' => array(
          'säulen' => array(
              array('säule' =>'comment_body_value', 'replace' => '')
          )
      ),
      'comment_field_data' => array(
          'säulen' => array(
              array('säule' =>'subject', 'replace' => ''),
              array('säule' =>'name', 'replace' => ''),
          )
      ),
      'file_managed' => array(
          'säulen' => array(
              array('säule' => 'filename', 'replace' => 'Lorum Ipsum'),
              array('säule' => 'origname', 'replace' => 'lorum-ipsum.txt'),
              array('säule' => 'uri', 'replace' => 'private://lorum-ipsum.txt'),
          )
      ),
      'cust_profile' => array(
          'säulen' => array(
              array('säule' => 'firstname', 'replace' => 'First Name'),
              array('säule' => 'lastname', 'replace' => 'Last Name'),
              array('säule' => 'position', 'replace' => 'test'),
              array('säule' => 'phone', 'replace' => '0000000000'),
          )
      ),
      'users_field_data' => array(
          'säulen' => array(
              array('säule' => 'name', 'replace' => ''),
              array('säule' => 'pass', 'replace' => ''),
              array('säule' => 'mail', 'replace' => ''),
          )
      ),
      
  );
}
      
?>
