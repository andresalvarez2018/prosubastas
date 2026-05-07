# Field Menu

The Field Menu module provides a field type that allows the selection
of a menu tree to display in page.

This is useful if for example you want a really customised sitemap.
Simply apply it to a Content Type or Paragraph entity so you can output
your own, dynamic, sitemap page with minimal configuration.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/field_menu).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/field_menu).


## Contents of this file

- Requirements
- Installation
- Configuration
- Use
- Maintainers


## Requirements

This module requires no modules outside of Drupal core.


## Installation

- Install as you would normally install a contributed Drupal module. For further
  information, see
  [Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

Navigate to your chosen entity type, and add a field:
- Click the add field button
- Select the 'Menu item' field type and give your field a Label
- Set the Field Settings to add the field to the entity type


## Use

A Menu item field appering in an entity edit form has the following options:
- Title: an optional title for the selected menu tree
- Root: the root menu item of the menu tree the user wishes to display
- Max depth: of the tree can be defined, or all child levels are displayed 
  if this value is left to the default of 0
- Include root: only the children of the selected Root item form the tree 
  by default. Here the user has the option to include the root menu link


## Maintainers

- code-brighton - [code-brighton](https://www.drupal.org/u/code-brighton)
- hdotnet - [hdotnet](https://www.drupal.org/u/hdotnet)
