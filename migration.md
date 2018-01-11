# Migration notes

## Running the merge operation SEEN and MWEN

Prerequisites for running the migration:

- wp-cli
- a copy of the db from fresh-energy.org's production
- this repository installed on your computer, preferably as a laravel valet installation (this was not tested on vvv)

With a fresh copy of the database from fresh-energy.org saved as `mysql.sql`, do the following:

1. `wp db reset`
2. `wp db import mysql.sql`
3. `time ./clean-db.bash`

This will export the db for integration on wpengine, as `export.sql`, with the site named "usenergynews.test".

## WP Engine tasks to import the site

To import the db on wpengine:

1. upload it via ftp to the `_wpeprivate` directory of the install
2. ask support to import it, pretty please: "On the usenergynews install, can you drop all tables and import the SQL database dump stored in the install at usenergynews/_wpeprivate/export.sql, please? I'd do it myself, but it's too large to import using consumer-facing tools."

After importing the DB:

1. Install and run the Term Debt Consolidator plugin: https://wordpress.org/plugins/term-debt-consolidator/
2. Ask WPE to copy the assets from -> to:
	- from the freshenergy install's `freshenergy/wp-content/uploads/sites/58/` to the usenergy install's `usenergynews/wp-content/uploads/`
	- from the freshenergy install's `freshenergy/wp-content/uploads/sites/64/` to the usenergy install's `usenergynews/wp-content/uploads/`
3. The WPE tech will probably push back.
	> The file moves would take _weeks_ at the speeds that WPE allows for downloading and uploading via SFTP. We've tried it in the past with a similar migration.
3. Change the site name and suchlike

## Prerequisites

Necessary plugins on the installed site:

- Chalkbeat MORI
- akismet
- better wordpress google xml sitemaps
- getsitecontrol widgets
- insert headers and footers
- Link Roundups
- no-nonsense google analytics
- pym shortcode
- redirection
- universal google analytics
- wordpress editorial calendar
