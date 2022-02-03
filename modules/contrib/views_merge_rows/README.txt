CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Similar modules
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

Sometimes when you use relationships in views you get a number of rows with the
same content in some of the fields. This results in a huge table (grid, list,
etc.) that affects the usability of your view.

The Views Merge Rows module provides a way to combine rows with the same content
in the specified fields.


REQUIREMENTS
------------

This module requires the following modules:
 * Views (Core)

Patches from the following issues are required:
 * https://www.drupal.org/project/drupal/issues/2824920
 * https://www.drupal.org/project/drupal/issues/2826755


SIMILAR MODULES
---------------

 * Views Aggregate Plus (https://www.drupal.org/project/views_aggregator):
   includes an Enumerate function. This builds a list of data from rows
   providing exactly the same sort of output as Views Merge Rows when rows are
   merged using the Group and compress function.
   This suffers from a similar pagination problem as Merge Rows, especially
   when a group crosses a paginated boundary.
 * Views Field View (https://www.drupal.org/project/views_field_view):
   allows you to include a subquery for each row in a table and insert the
   result in a field.
   The subquery is driven by tokens for the contextual filtering which allows
   you to create a list of related entities that match the row.
   This doesn't produce a comma delimited list without some styling
   manipulation, but is very flexible.
 * Views Distinct (https://www.drupal.org/project/views_distinct):
   D7 only.


INSTALLATION
------------

 * Install and enable this module like any other drupal 8 module.


CONFIGURATION
-------------

After installing the module you get the "Merge rows" item in the "Advanced"
section of the Views UI.

To configure the row merging click the "Settings" link next to the "Merge rows"
item.

In the configuration dialog you can enable/disable row merging with the
"Merge rows with the same content in the specified fields" checkbox.

You can specify the "Merge option" for each field.

The fields with "Merge option" set to "Use values of this field as a filter" are
used to check which rows should be merged. If several rows contain exactly the
same values in all of these fields, they are merged together. The values for
other fields are calculated as follows:
 * For fields with "Merge option" set to "Use the first value of this field"
   only the value from the first merged rows is used. The values in other rows
   are disregarded.
 * For fields with "Merge option" set to "Merge values of this field" all the
   values appears in the resulting row.


MAINTAINERS
-----------

Current maintainers:
 * Florent Torregrosa (Grimreaper) - https://www.drupal.org/user/2388214

Previous maintainers:
 * Dmitry Kulik (diolan) - https://www.drupal.org/user/2336786
 * Dominique Gagné (dgagne) - https://www.drupal.org/user/3504808
