# USEN Migrator #
**Contributors:**      innlabs  
**Donate link:**       https://labs.inn.org/donate  
**Tags:**  
**Requires at least:** 4.4  
**Tested up to:**      4.8.1 
**Stable tag:**        0.1.0  
**License:**           GPLv2  
**License URI:**       http://www.gnu.org/licenses/gpl-2.0.html  

Registers a WP-CLI command for handling migration of US Energy News content

## Description ##

This plugin presents no GUI, but adds a WP CLI command to migrate stuff from one site to another.

The decision was made to do this as a plugin because it doesn't need to live in the theme, and can be deactivated and removed afterwards. it doesn't need to live on in the child theme.

If you're looking at this in the future (after, say, February 2018), please:

- rewrite it as a standalone wp-cli command
- give it a sensible name
- publish it on its own on github
- publish it to the wp-cli package repository

## Installation ##

### Manual Installation ###

1. Upload the entire `/usen-migrator` directory to the `/wp-content/plugins/` directory.
2. Activate USEN Migrator through the 'Plugins' menu in WordPress.

## Frequently Asked Questions ##


## Screenshots ##


## Changelog ##

### 0.1.0 ###
* First release

## Upgrade Notice ##

### 0.1.0 ###
First Release
