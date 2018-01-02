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

# export
export() {
	wp db export --add-drop-table export.sql
}

# do the things
main() {
	check
	destroy_posts
	destroy_terms
}

main;

