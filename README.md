## Setup instructions

### VVV instructions

This repository is designed to be set up in accordance with the VVV install instructions in INN/docs, that were introduced with https://github.com/INN/docs/pull/148

```
vv create
```

Prompt | Text to enter 
------------ | -------------
Name of new site directory: | usenergynews
Blueprint to use (leave blank for none or use largo): | largo
Domain to use (leave blank for largo-umbrella.test): | usenergynews.test
WordPress version to install (leave blank for latest version or trunk for trunk/nightly version): | *hit [Enter]*
Install as multisite? (y/N): | **N**
Install as subdomain or subdirectory? : | subdomain
Git repo to clone as wp-content (leave blank to skip): | *hit [Enter]*
Local SQL file to import for database (leave blank to skip): | *This directory must be an absolute path, so the easiest thing to do on a Mac is to drag your mysql file into your terminal window here: the absolute filepath with fill itself in.*
Remove default themes and plugins? (y/N): | N
Add sample content to site (y/N): | N
Enable WP_DEBUG and WP_DEBUG_LOG (y/N): | N

After reviewing the options and creating the new install, partake in the following steps:

1. `cd` to the directory `usenergynews/` in your VVV setup
2. `git clone git@github.com:INN/umbrella-usenergynews.git`
3. Copy the contents of the new directory `umbrella-usenergynews/` into `htdocs/`, including all hidden files whose names start with `.` periods.

This is a series of notes of what had to be done to get the db working:

1. `fab vagrant.reload_db:usenergynewsdb_dev__2016-09-19__3-21\ PM.sql,usenergynews`
2. open `wp-config.php`, disable all multisite lines
3. `wp user` command to update our user with the password from 1pass
4. in Sequel Pro, change the site's domain and home in `wp_options` to http://usenergynews.dev/
4. Follow instructions on https://codex.wordpress.org/Create_A_Network to convert the singlesite database to a multisite database, including progressively enabling the lines in `wp-config.php`
5. Perform database replacements:
	- in `wp_blogs`, set domain to `usenergynews.wpengine.com`
	- in `wp_site`, set domain to `usenergynews.wpengine.com`
	- in `wp_options`, set siteurl and home to `http://usenergynews.wpengine.com/`
	- in `wp_sitemeta`, set siteurl and home to `http://usenergynews.wpengine.com/`
5. Get WPE to upload the DB
6. Log into the network admin, and do the following:
	- Network Enable the Largo base theme
	- enable the Fresh Energy theme for the Fresh Energy site
7. Go to the Fresh Energy dashboard
	- Activate the Fresh Energy theme that's a Largo child
	- In Settings > Reading, change "Front page displays" to "Your latest posts"


### Laravel Valet setup notes

1. `cd` to the base directory of your Laravel setup
2. `git clone git@github.com:INN/umbrella-usenergynews.git usenergynews`
3. Install WordPress

```
yo wordpress
```

Prompt | Text to enter
------- | ------------
Wordpress URL: | usenergynews.test
Table prefix: wp_
Database host: | localhost
Database name: | usenergynews
Database user: |
Database password: |
Use Git: | No
Custom dir structure: | No
Custom theme: | No

After reviewing the options and creating the new install, partake in the following steps:

1. `wp core install`
2. `wp db import mysql.sql`

## Merging SEEN and MWEN

With a fresh copy of the database from fresh-energy.org saved as `mysql.sql`, do the following:

1. `wp db reset`
2. `wp db import mysql.sql`
3. `time ./clean-db.bash`

This will export the db for integration on wpengine, as `export.sql`, with the site named "usenergynews.test".

To import the db on wpengine:

1. upload it via ftp to the `_wpeprivate` directory of the install
2. ask support to import it, pretty please: "On the usenergynews install, can you drop all tables and import the SQL database dump stored in the install at usenergynews/_wpeprivate/wpengine.sql, please? I'd do it myself, but it's too large to import using consumer-facing tools."
3. In https://my.wpengine.com/installs/usenergynews/advanced , in the wp-cli box, run `wp search-replace usenergynews.test usenergynews.wpengine.com`

After importing:

1. Install and run the Term Debt Consolidator plugin: https://wordpress.org/plugins/term-debt-consolidator/
2. Ask WPE to copy the assets from -> to:
	- from the freshenergy install's `freshenergy/wp-content/uploads/sites/58/` to the usenergy install's `usenergynews/wp-content/uploads/`
	- from the freshenergy install's `freshenergy/wp-content/uploads/sites/64/` to the usenergy install's `usenergynews/wp-content/uploads/`
3. Change the site name and suchlike
