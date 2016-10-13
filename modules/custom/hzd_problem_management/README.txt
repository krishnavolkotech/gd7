
Problem management is a custom module which implements following functionalities.

1. Reads about the problems content from csv files and saves as nodes in drupal.
2. Displays the problems in the table format with in the group and also for the public.
3. Provides settings form for storing path, form where the csv file is imported.
4  And also provides the dispaly of current and archived problems.


Importing CSV
*************

1. Importes problems csv file at path "read_excel"

Functions used for the importing csv file.
  1. read_problem_csv : Callback for reading the problems data from the csv file.
  2. importing_problem_csv: Reads the data from the csv file and creates problem nodes.
  3. insert_import_status: stores the status of the import in the table "problem_import_history".

  4. validate_csv: validates the csv file format, and sends out an email if the service given in the csv file does not exist.
  5. saving_problem_node: saves the problems data as nodes.	     
  6. send_problems_notification: sends out notification about the status of csv import.



Settings Form For problems
**************************
 Settings Form is available at path "node/%node/problem_settings" with the callback "problem_setting" accesable only to group admin and site admins.

Functions Used:
  1. problem_setting: Settings form for Problem to select services for each group.Only those selected services will be displayed in the releses table display.
  2. problem_setting_submit: Submit handler for problems settings.

Admin settings Form for problems
********************************
  Admin settings form for releases is at path "admin/settings/problem" with callback "problem_management_settings".

Functions Used:
  1.problem_management_settings: callback for the admin settings form for relaeses.
  2.problems_configure_form: Provides admin settings form for releases settings.
  3.problems_configure_form_submit: submit handler for the admin settings form.
  4.problems_configure_form_validate: validate handler for the admin settings form.


Table Display of Problems
*************************

  1.Problems are displayed as two tabs (Current , Archived).

  Current:
  ---------
    Problems which are having status value not equal to "geschlossen" are displayed in this tab.
    Problems are accesed by group members.

    Functions used:
      1.problems_display: call back for the problems table display.
      2.problems_tabs_callback_data: Returns filter form and the table display of problems.
      3.problems_filter_form: Provides the form for filtering the problems.
      4.problems_default_display: displays the table display of problems.


TODO FROM HERE:


  Archived:
  ------------
     Problems which are having status value equal to "geschlossen" are displayed in this tab.
     imported form the csv file containing status like  "in progress" are displed in this tab.

    Functions used:
      1. progress_releases: call back for the in progress table display.
      2.releases_tabs_callback_data: Returns filter form and the table display of releases in group and for public.
      3.release_filter_form: Provides the form for filtering the releases.
      4.releases_dispaly_table: displays the table display of in progress releases.

     
NOTE: For Detailed functional flow check module file.
