## Setup instructions

This repository is designed to be set up in accordance with the VVV install instructions in INN/docs, that were introduced with https://github.com/INN/docs/pull/148


```
vv create
```

Prompt | Text to enter 
------------ | -------------
Name of new site directory: | freshenergy
Blueprint to use (leave blank for none or use largo): | largo
Domain to use (leave blank for largo-umbrella.dev): | freshenergy.dev
WordPress version to install (leave blank for latest version or trunk for trunk/nightly version): | *hit [Enter]*
Install as multisite? (y/N): | **Y**
Install as subdomain or subdirectory? : | subdomain
Git repo to clone as wp-content (leave blank to skip): | *hit [Enter]*
Local SQL file to import for database (leave blank to skip): | *This directory must be an absolute path, so the easiest thing to do on a Mac is to drag your mysql file into your terminal window here: the absolute filepath with fill itself in.*
Remove default themes and plugins? (y/N): | N
Add sample content to site (y/N): | N
Enable WP_DEBUG and WP_DEBUG_LOG (y/N): | N

After reviewing the options and creating the new install, partake in the following steps:

1. `cd` to the directory `freshenergy/` in your VVV setup
2. `git clone git@github.com:INN/umbrella-freshenergy.git`
3. Copy the contents of the new directory `umbrella-freshenergy/` into `htdocs/`, including all hidden files whose names start with `.` periods.

## Initial database setup notes

This is a series of notes of what had to be done to get the db working.

1. `fab vagrant.reload_db:freshenergydb_dev__2016-09-19__3-21\ PM.sql,freshenergy`
2. open `wp-config.php`, disable all multisite lines
3. `wp user` command to update our user with the password from 1pass
4. in Sequel Pro, change the site's domain and home in `wp_options` to http://freshenergy.dev/
4. Follow instructions on https://codex.wordpress.org/Create_A_Network to convert the singlesite database to a multisite database, including progressively enabling the lines in `wp-config.php`
5. Contact WPE with ??? about their db stuff because the database import didn't affect the wp-users table.
