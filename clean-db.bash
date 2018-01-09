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

		# gotta page through this, because more than ~10k arguments at a time is too many for wp-cli.
		while [ $( wp post list --format=count --post_type=$post_type ) -gt 1 ]
		do
			wp post delete $(wp post list --format=ids --post_type=$post_type --posts_per_page=10000 ) --force --quiet
		done
	done

	# because this doesn't happen in the above. >:|
	wp post delete $(wp post list --format=ids --post_type=wpcf7_contact_form ) --force --quiet
	wp post delete $(wp post list --format=ids --post_type=optionsframework ) --force --quiet
	wp db query "DELETE FROM wp_posts WHERE post_status = 'auto-draft';"
}

# destroy the database's terms
destroy_terms() {
	for taxonomy in $( wp taxonomy list --field=name )
	do
		echo "Deleting the terms of taxonomies $taxonomy"
		# This might cause some log output if there are no terms in the taxonomy
		wp term delete $taxonomy $(wp term list $taxonomy --format=ids ) --by=id --quiet
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

# activate the plugin that contains the command
activate() {
	wp plugin activate usen-migrator redirection --quiet
}

# run the munging commands
munge() {
	wp usen migrate 58
	#wp usen migrate 64
	wp usen largo_reset_options
}

# export
export() {
	wp db export --add-drop-table export.sql
}

# reminders
reminders() {
	echo "Please remember to run cleanup tasks after you have imported the database."
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
	activate
	munge
	prune_wp_users
	export
	reminders
}

main;
