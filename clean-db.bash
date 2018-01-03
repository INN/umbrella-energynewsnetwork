#!/bin/bash

# Check that wp's installed and where we want it
check() {
	command -v wp >/dev/null 2>&1 || {
		echo >&2 "This command requires wp but it's not installed. Aborting.";
		exit 1;
	}
}

# destroy the database's post items
destroy_posts() {
	for post_type in $( wp post-type list --field=name )
	do
		echo "Deleting the posts of post_type $post_type"
		echo $post_type

		while [ $( wp post list --format=count --post_type=$post_type ) -gt 1 ]
		do
			wp post delete $(wp post list --format=ids --post_type=$post_type --posts_per_page=10000 ) --force
		done
	done
}

# destroy the database's terms
destroy_terms() {
	for taxonomy in $( wp taxonomy list --field=name )
	do
		echo "Deleting the terms of taxonomies $taxonomy"
		echo $taxonomy

		wp term delete $(wp term list $taxonomy --format=ids ) --force
	done
}

# prune users using the prune_wp_users.sql script
prune_wp_users() {
	if [ -f prune_wp_users.sql ]
	then
		wp db query < prune_wp_users.sql
	else
		echo >&2 "Not sure what happened; but prune_wp_users.sql is missing. Aborting.";
		exit 1;
	fi
}

# run the munging commands
munge() {
}

# export
export() {
	wp db export --add-drop-table export.sql
}

# reminders
reminders() {
	echo "Please remember to run cleamup tasks after you have imported the database."
	echo "You'll need to replace names."
	echo "   wp search-replace 'usenergynews.test' 'usenergynews.wpengine.com'"
	echo "   wp search-replace 'southeastenergynews.com' 'usenergynews.wpengine.com'"
	echo "   wp search-replace 'midwestsnergynews.com' 'usenergynews.wpengine.com'"
	echo ""
}

# do the things
main() {
	check
	destroy_posts
	destroy_terms
	prune_wp_users
	reminders
}

main;

