<?php
/**
 * Manage migration tasks related to merging Catalyst Chicago with Chicago Reporter
 * This assumes that Catalyst Chicago is the site ID given in CR_ID.
 * Chicago Reporter, on the other hand, has no site ID, having been converted from a multisite to a singlesite first, using the procedure in https://github.com/INN/migration-scripts/blob/master/sql-utils/prepare_for_export.sql.md
 * Usage:
 *     wp usen perform_all_migrations
 */
define( 'MWEN_ID', 64 );
define( 'SEEN_ID', 58 );

/**
 * Create migration commands for Catalyst
 */
class MWEN_Migrator_CLI extends WP_CLI_Command {

	/**
	 * Contains an array(
	 *     (int) old id => (int) new id,
	 * );
	 */
	private $oldnew = array();

	private function log( $stuff ) {
		WP_CLI::line( var_export( $stuff, true ) );
	}

	/**
	 */
	public function update_catalyst_posts() {
		global $wpdb;
		$highest_reporter = $wpdb->get_var(
			"
				SELECT ID
				from $wpdb->posts
				ORDER BY ID DESC limit 0,1
			"
		);
		$highest_catalyst = $wpdb->get_var(
			"
				SELECT ID
				FROM wp_" . CR_ID . "_posts
				ORDER BY ID DESC limit 0,1
			"
		);
		// find out which is truly the higher
		$highest = max( $highest_catalyst, $highest_reporter );

		// round this value up to the next ten thousand
		$highest = (int)  ceil( (int) $highest / 10000 ) * 10000;

		// Find ids of all Catalysts
		$olds = $wpdb->get_col(
			"
				SELECT ID
				FROM wp_" . CR_ID . "_posts
				ORDER BY ID ASC
			"
		);

		$progress = \WP_CLI\Utils\make_progress_bar(
			"Updating IDs of catalyst posts...",
			count( $olds )
		);

		foreach ( $olds as $old ) {
			$new = $old + $highest;

			// update ID in posts
			$wpdb->update(
				'wp_' . CR_ID . '_posts',
				array( 'ID' => $new ),
				array( 'ID' => $old )
			);

			// update wp_57_posts where post_parent = old
			$wpdb->update(
				'wp_' . CR_ID . '_posts',
				array( 'post_parent' => $new ),
				array( 'post_parent' => $old )
			);

			// update wp_57_term_relationships with new object_id
			$wpdb->update(
				'wp_' . CR_ID . '_term_relationships',
				array( 'object_id' => $new ),
				array( 'object_id' => $old )
			);

			// update wp_57_postmeta with new post_id
			$wpdb->update(
				'wp_' . CR_ID . '_postmeta',
				array( 'post_id' => $new ),
				array( 'post_id' => $old )
			);

			// update wp_57_postmeta _thumbnail_id with new thumbnail_id
			$wpdb->update(
				'wp_' . CR_ID . '_postmeta',
				array( 'meta_value' => $new ),
				array(
					'meta_value' => $old,
					'meta_key' => '_thumbnail_id'
				)
			);

			// update wp_57_postmeta featured_media with new featured media
			$rows = $wpdb->get_results(
				"
					SELECT * FROM wp_" . CR_ID . "_postmeta
						WHERE meta_key = 'featured_media'
						AND meta_value LIKE '%$old%'
				",
				'ARRAY_A'
			);
			if ( is_array( $rows ) ) {
				foreach ( $rows as $row ) {
					$meta_value = maybe_unserialize($row['meta_value']);

					if ( $meta_value['attachment'] == $old ) {
						$meta_value['attachment'] = $new;
					}

					/**
					 * things that are potentially of interest in $meta_value['attachment_data']
					 *
					 * The attachment data in general is created by wp_prepare_attachment_for_js( $old )
					 * Here's what-all can be in it:
					 * - id
					 * - title
					 * - filename
					 * - link
					 * - alt
					 * - author
					 * - description
					 * - caption
					 * - name
					 * - status
					 * - uploadedTo
					 * - uploadedToLink
					 * - uploadedToTitle
					 * - date
					 * - modified
					 * - menuOrder
					 * - mime
					 * - type
					 * - subtype
					 * - icon
					 * - dateFormatted
					 * - nonces => array( update, delete, edit )
					 * - editLink
					 * - meta
					 * - filesizeInBytes
					 * - filesizeHumanReadable
					 * - the array 'sizes' if it's an image
					 * - height
					 * - width
					 * - fileLength (Audio)
					 * - image
					 * - thumb
					 * - compat from get_compat_media_markup( $attachment->ID, array( 'in_modal' => true ) );
					 *
					 * Of those, the following change during this migration
					 * - id
					 * - compat (the IDs here change)
					 * So instead of running wp_prepare_attachment_for_js( $new ); we just update the ID.
					 */
					if ( $meta_value['attachment_data']['id'] == $old ) {
						$meta_value['attachment_data']['id'] = $new;
					}

					if ( is_string( $meta_value['attachment_data']['compat']['item'] ) ) {
						$meta_value['attachment_data']['compat']['item'] = str_replace( $old, $new, $meta_value['attachment_data']['compat']['item'], $count);
						// $count is usually 1 + ( 3 * number of input fields )
					}

					$wpdb->update(
						"wp_" . CR_ID . "_postmeta",
						array(
							'meta_value' => serialize($meta_value)
						),
						array(
							'meta_key' => 'featured_media',
							'meta_id' => $row['meta_id']
						)
					);
				}
			} // end is_array( $rows )

			$progress->tick();
		} // end foreach ( $olds as $old )

		$progress->finish();

	}

	/**
	 */
	public function update_catalyst_postmeta() {
		global $wpdb;
		$highest_reporter = $wpdb->get_var(
			"
				SELECT meta_id
				from $wpdb->postmeta
				ORDER BY meta_id DESC limit 0,1
			"
		);
		$highest_catalyst = $wpdb->get_var(
			"
				SELECT meta_id
				FROM wp_" . CR_ID . "_postmeta
				ORDER BY meta_id DESC limit 0,1
			"
		);
		// find out which is truly the higher
		$highest = max( $highest_catalyst, $highest_reporter );

		// round this value up to the next ten thousand
		$highest = (int)  ceil( (int) $highest / 10000 ) * 10000;

		// Find ids of all Catalysts
		$olds = $wpdb->get_col(
			"
				SELECT meta_id
				FROM wp_" . CR_ID . "_postmeta
				ORDER BY meta_id ASC
			"
		);

		$progress = \WP_CLI\Utils\make_progress_bar(
			"Updating meta_ids of catalyst postmeta...",
			count( $olds )
		);


		foreach ( $olds as $old ) {
			$new = $old + $highest;

			// increment meta_id
			$ret = $wpdb->update(
				'wp_' . CR_ID . '_postmeta',
				array( 'meta_id' => $new ),
				array( 'meta_id' => $old )
			);

			$progress->tick();
		}

		$progress->finish();
	}

	/**
	 * note: term taxonomy ids are not the ids of taxonomies, but the ids of the relationships between a term and its taxonomy
	 * there are no taxonomy ids to be incremented
	 * but we must increment these term_taxonomy_ids
	 *
	 * Also, we're going to move all Catalyst terms in the 'series' taxonomy to the 'catalyst-issues' taxonomy.
	 */
	public function update_catalyst_term_taxonomy() {
		global $wpdb;
		$highest_reporter = $wpdb->get_var(
			"
				SELECT term_taxonomy_id
				from $wpdb->term_taxonomy
				ORDER BY term_taxonomy_id DESC limit 0,1
			"
		);
		$highest_catalyst = $wpdb->get_var(
			"
				SELECT term_taxonomy_id
				FROM wp_" . CR_ID . "_term_taxonomy
				ORDER BY term_taxonomy_id DESC limit 0,1
			"
		);
		// find out which is truly the higher
		$highest = max( $highest_catalyst, $highest_reporter );

		// round this value up to the next ten thousand
		$highest = (int)  ceil( (int) $highest / 10000 ) * 10000;

		// Find ids of all Catalysts
		$olds = $wpdb->get_col(
			"
				SELECT term_taxonomy_id
				FROM wp_" . CR_ID . "_term_taxonomy
				ORDER BY term_taxonomy_id ASC
			"
		);

		$progress = \WP_CLI\Utils\make_progress_bar(
			"Updating term_taxonomy_ids of catalyst term_taxonomies...",
			count( $olds ) + 1 // +1 for the series -> catalyst-issue migration
		);

		// convert all series into catalyst issues
		$ret = $wpdb->update(
			'wp_' . CR_ID . '_term_taxonomy',
			array(
				'taxonomy' => 'catalyst-issues'
			),
			array(
				'taxonomy' => 'series'
			)
		);
		$progress->tick();

		foreach ( $olds as $old ) {
			$new = $old + $highest;

			// increment term_taxonomy_id
			$ret = $wpdb->update(
				'wp_' . CR_ID . '_term_taxonomy',
				array( 'term_taxonomy_id' => $new ),
				array( 'term_taxonomy_id' => $old )
			);

			// update wp_57_term_relationships with new term_taxonomy_id
			// increment term_taxonomy_id
			$ret = $wpdb->update(
				'wp_' . CR_ID . '_term_relationships',
				array( 'term_taxonomy_id' => $new ),
				array( 'term_taxonomy_id' => $old )
			);
			
			$progress->tick();
		}

		$progress->finish();
	}

	/**
	 */
	public function update_catalyst_terms() {
		global $wpdb;
		$highest_reporter = $wpdb->get_var(
			"
				SELECT term_id
				from $wpdb->terms
				ORDER BY term_id DESC limit 0,1
			"
		);
		$highest_catalyst = $wpdb->get_var(
			"
				SELECT term_id
				FROM wp_" . CR_ID . "_terms
				ORDER BY term_id DESC limit 0,1
			"
		);
		// find out which is truly the higher
		$highest = max( $highest_catalyst, $highest_reporter );

		// round this value up to the next ten thousand
		$highest = (int)  ceil( (int) $highest / 10000 ) * 10000;

		// Find ids of all Catalysts
		$olds = $wpdb->get_col(
			"
				SELECT term_id
				FROM wp_" . CR_ID . "_terms
				ORDER BY term_id ASC
			"
		);

		$progress = \WP_CLI\Utils\make_progress_bar(
			"Updating term_ids of catalyst terms...",
			count( $olds )
		);

		// Keep track of strange things
		$oddballs = array();

		foreach ( $olds as $old ) {
			$new = $old + $highest;

			// increment term_id
			$ret = $wpdb->update(
				'wp_' . CR_ID . '_terms',
				array( 'term_id' => $new ),
				array( 'term_id' => $old )
			);

			// update wp_57_term_taxonomy with new term_id
			$ret = $wpdb->update(
				'wp_' . CR_ID . '_term_taxonomy',
				array( 'term_id' => $new ),
				array( 'term_id' => $old )
			);

			// update wp_57_termmeta with new term_id
			$ret = $wpdb->update(
				'wp_' . CR_ID . '_termmeta',
				array( 'term_id' => $new ),
				array( 'term_id' => $old )
			);

			// update wp_57_postmeta with top term's new term_id
			$ret = $wpdb->update(
				'wp_' . CR_ID . '_postmeta',
				array( 'meta_value' => $new ),
				array(
					'meta_value' => $old,
					'meta_key' => 'top_term'
				)
			);

			// update wp_57_postmeta series_order with new term_id
			$ret = $wpdb->update(
				'wp_' . CR_ID . '_postmeta',
				array( 'meta_key' => 'series_' . $new . '_order' ),
				array( 'meta_key' => 'series_' . $old . '_order' )
			);

			// update wp_57_posts where post_title = $taxonomy:$old_id with new $taxonomy:$new_id
			// this is the term meta post


			// first, figure out what taxonomy this is
			$full_term = get_term_by( 'id', $new, '', 'ARRAY_A' );
			$full_term = $wpdb->get_results(
				"
					SELECT a.taxonomy
					FROM wp_" . CR_ID . "_term_taxonomy a
					WHERE a.term_id = $new
				",
				'ARRAY_A'
			);
			$taxonomy = $full_term[0]['taxonomy'];

			// get the post ids for the term meta posts
			$term_meta_posts = $wpdb->get_results(
				"
					SELECT a.ID
					FROM wp_" . CR_ID . "_posts a
					INNER JOIN wp_" . CR_ID . "_term_relationships b
						ON a.ID = b.object_ID
					INNER JOIN wp_" . CR_ID . "_term_taxonomy c
						ON b.term_taxonomy_id = c.term_taxonomy_id
					WHERE c.term_id = $new
					AND a.post_type = '_term_meta'
				",
				'ARRAY_A'
			);

			foreach ( $term_meta_posts as $post ) {
				// $post = array( 'ID' => '####' );
				// create new titles
				$ret = $wpdb->update(
					'wp_' . CR_ID . '_posts',
					array(
						'post_title' => $taxonomy . ':' . $new
					),
					array(
						'ID' => (int) $post['ID']
					)
				);

				if ( $ret == false ) {
					$oddballs[] = $new;
				}
			}

			$progress->tick();
		}

		if ( !empty( $oddballs ) ) {
			$this->log("Here's a list of terms that exist, that have term meta posts, but were not able to update the post_title of the term meta post");
			$this->log($oddballs);
		}

		$progress->finish();

	}

	/**
	 */
	public function update_catalyst_termmeta() {
		global $wpdb;
		$highest_reporter = $wpdb->get_var(
			"
				SELECT meta_id
				from $wpdb->termmeta
				ORDER BY meta_id DESC limit 0,1
			"
		);
		$highest_catalyst = $wpdb->get_var(
			"
				SELECT meta_id
				FROM wp_" . CR_ID . "_termmeta
				ORDER BY meta_id DESC limit 0,1
			"
		);
		// find out which is truly the higher
		$highest = max( $highest_catalyst, $highest_reporter );

		// round this value up to the next ten thousand
		$highest = (int)  ceil( (int) $highest / 10000 ) * 10000;

		// Find ids of all Catalysts
		$olds = $wpdb->get_col(
			"
				SELECT meta_id
				FROM wp_" . CR_ID . "_termmeta
				ORDER BY meta_id ASC
			"
		);

		$progress = \WP_CLI\Utils\make_progress_bar(
			"Updating meta_ids of catalyst termmettermmeta...",
			count( $olds )
		);


		foreach ( $olds as $old ) {
			$new = $old + $highest;

			// increment meta_id in wp_57_termmeta
			$wpdb->update(
				'wp_' . CR_ID . '_termmeta',
				array( 'ID' => $new ),
				array( 'ID' => $old )
			);

			$progress->tick();
		}

		$progress->finish();
	}

	/**
	 * I stubbed this out while planning this migration out, but it's not actually needed.
	 * By following the instructions in INN/chicagoreporter/migration-notes.md,
	 * the combined users table will have users from both sites, without needing
	 * to increment user ids.
	 *
	 * 1. Initial import: wp_users has all largoproject install users
	 * 2. Run reporter_to_singlesite.sql: Nothing done to users.
	 * 3. Run wp-cli commands: merge all non-user tables.
	 * 4. Run prune_wp_users.sql: Removes all users that are in neither site's list.
	 *
	 * And that's how it goes.
	 *
	 * @unused
	 */
	private function update_catalyst_users() {
		// otherwise:
		// increment ID in wp_57_users
		// update post_author in wp_57_posts
		// update wp_57_usermeta with new user_id
		// update wp_57_comments with new user_id
	}

	/**
	 * Convert user roles to match the new site's singlesite nature
	 * 
	 * This fixes a shortfall in INN/sql-utils/prepare_for_export.sql
	 */
	public function update_all_usermeta() {
		global $wpdb;

		$site_ids = array(
			CR_ID,
			'44'
		);

		$keys = array(
			// 'capabilities', // this is handled by prune_wp_users.sql
			'user-settings',
			'user-settings-time',
			'tablepress_user_options',
			'media_library_mode'
		);

		foreach ( $keys as $key ) {
			// delete stuff for site 1, formerly the primary site of the Largo umbrella
			$wpdb->delete(
				'wp_usermeta',
				array( 'meta_key' => 'wp_' . $key )
			);

			foreach ( $site_ids as $site_id ) {
				$wpdb->update(
					'wp_usermeta',
					array( 'meta_key' => 'wp_' . $key ),
					array( 'meta_key' => 'wp_' . $site_id . '_' . $key )
				);
			}
		}

	}

	/**
	 */
	public function update_catalyst_comments() {
		global $wpdb;
		$highest_reporter = $wpdb->get_var(
			"
				SELECT comment_id
				from $wpdb->comments
				ORDER BY comment_id DESC limit 0,1
			"
		);
		$highest_catalyst = $wpdb->get_var(
			"
				SELECT comment_id
				FROM wp_" . CR_ID . "_comments
				ORDER BY comment_id DESC limit 0,1
			"
		);
		// find out which is truly the higher
		$highest = max( $highest_catalyst, $highest_reporter );

		// round this value up to the next ten thousand
		$highest = (int)  ceil( (int) $highest / 10000 ) * 10000;

		// Find ids of all Catalysts
		$olds = $wpdb->get_col(
			"
				SELECT comment_id
				FROM wp_" . CR_ID . "_comments
				ORDER BY comment_id ASC
			"
		);

		$progress = \WP_CLI\Utils\make_progress_bar(
			"Updating comment_ids of catalyst comments...",
			count( $olds )
		);


		foreach ( $olds as $old ) {
			$new = $old + $highest;

			// increment comment_id in wp_57_comments
			$wpdb->update(
				'wp_' . CR_ID . '_comments',
				array( 'comment_id' => $new ),
				array( 'comment_id' => $old )
			);
			// update comment_id in wp_57_commentmeta
			$wpdb->update(
				'wp_' . CR_ID . '_commentmeta',
				array( 'comment_id' => $new ),
				array( 'comment_id' => $old )
			);

			$progress->tick();
		}

		$progress->finish();
	}

	/**
	 */
	public function update_catalyst_commentmeta() {
		global $wpdb;
		$highest_reporter = $wpdb->get_var(
			"
				SELECT meta_id
				from $wpdb->commentmeta
				ORDER BY meta_id DESC limit 0,1
			"
		);
		$highest_catalyst = $wpdb->get_var(
			"
				SELECT meta_id
				FROM wp_" . CR_ID . "_commentmeta
				ORDER BY meta_id DESC limit 0,1
			"
		);
		// find out which is truly the higher
		$highest = max( $highest_catalyst, $highest_reporter );

		// round this value up to the next ten thousand
		$highest = (int)  ceil( (int) $highest / 10000 ) * 10000;

		// Find ids of all Catalysts
		$olds = $wpdb->get_col(
			"
				SELECT meta_id
				FROM wp_" . CR_ID . "_commentmeta
				ORDER BY meta_id ASC
			"
		);

		$progress = \WP_CLI\Utils\make_progress_bar(
			"Updating meta_ids of catalyst commentmeta...",
			count( $olds )
		);

		foreach ( $olds as $old ) {
			$new = $old + $highest;

			// increment meta_id in wp_57_commentmeta
			$wpdb->update(
				'wp_' . CR_ID . '_commentmeta',
				array( 'meta_id' => $new ),
				array( 'meta_id' => $old )
			);

			$progress->tick();
		}

		$progress->finish();
	}

	/**
	 */
	public function update_catalyst_redirection_items() {
		global $wpdb;
		$highest_reporter = $wpdb->get_var(
			"
				SELECT id
				from wp_redirection_items
				ORDER BY id DESC limit 0,1
			"
		);
		$highest_catalyst = $wpdb->get_var(
			"
				SELECT id
				FROM wp_" . CR_ID . "_redirection_items
				ORDER BY id DESC limit 0,1
			"
		);
		// find out which is truly the higher
		$highest = max( $highest_catalyst, $highest_reporter );

		// round this value up to the next ten thousand
		$highest = (int)  ceil( (int) $highest / 10000 ) * 10000;

		// Find ids of all Catalysts
		$olds = $wpdb->get_col(
			"
				SELECT id
				FROM wp_" . CR_ID . "_redirection_items
				ORDER BY id ASC
			"
		);

		$progress = \WP_CLI\Utils\make_progress_bar(
			"Updating ids of catalyst redirection items...",
			count( $olds )
		);

		foreach ( $olds as $old ) {
			$new = $old + $highest;

			// increment id in wp_57_commentmeta
		// increment id in wp_57_redirection_items
			$wpdb->update(
				'wp_' . CR_ID . '_redirection_items',
				array( 'id' => $new ),
				array( 'id' => $old )
			);

			$progress->tick();
		}

		$progress->finish();
	}

	/**
	 * This is not implemented, because the tables from Catalyst and Reporter are the same.
	 * @since 2016-09-19
	 * @author benlk
	 */
	private function update_catalyst_redirection_groups() {
		// increment id in wp_57_redirection_groups
		// update group_id in wp_57_redirection_items
	}

	/**
	 * This command runs the following commands:
	 * - update_catalyst_posts
	 * - update_catalyst_postmeta
	 * - update_catalyst_term_taxonomy
	 * - update_catalyst_terms
	 * - update_catalyst_termmeta
	 * - update_catalyst_comments
	 * - update_catalyst_commentmeta
	 * - update_catalyst_redirection_items
	 */
	public function adjust_all_ids() {
		$this->update_catalyst_posts();
		$this->update_catalyst_postmeta();
		$this->update_catalyst_term_taxonomy();
		$this->update_catalyst_terms();
		$this->update_catalyst_termmeta();
		$this->update_catalyst_comments();
		$this->update_catalyst_commentmeta();
		$this->update_catalyst_redirection_items();
		$this->update_all_usermeta();
		# $this->update_catalyst_redirection_groups();
	}

	/**
	 * Copy rows from Catalyst's tables into Reporter's tables
	 */
	public function merge_catalyst_tables() {
		$tables = array(
			'wp_57_commentmeta' => 'wp_commentmeta',
			'wp_57_comments' => 'wp_comments',
			// 'wp_57_links' => '', // can be ignored because it is empty
			// 'wp_57_options' => 'wp_options', // we're keeping Reporter options
			'wp_57_postmeta' => 'wp_postmeta',
			'wp_57_posts' => 'wp_posts',
			// 'wp_57_redirection_404' => '', // can be ignored because it's useless
			// 'wp_57_redirection_groups' => 'wp_redirection_groups', // can be ignored because it's the same as Reporter
			'wp_57_redirection_items' => 'wp_redirection_items',
			// 'wp_57_redirection_logs' => '', // can be ignored because it's empty
			'wp_57_term_relationships' => 'wp_term_relationships',
			'wp_57_term_taxonomy' => 'wp_term_taxonomy',
			'wp_57_termmeta' => 'wp_termmeta',
			'wp_57_terms' => 'wp_terms',
		);

		global $wpdb;

		// this technique from http://sqlblog.com/blogs/merrill_aldrich/archive/2011/08/17/handy-trick-move-rows-in-one-statement.aspx
		foreach ( $tables as $catalyst => $reporter ) {
			$ret = $wpdb->query(
				"
					INSERT INTO $reporter
					SELECT * FROM $catalyst
				"
			);
			$this->log( "$ret rows affected when copying rows from $catalyst into $reporter." );
		}
	}

	/**
	 * Drop Catalyst's tables
	 */
	public function drop_catalyst_tables() {
		global $wpdb;
		$drop = $wpdb->query(
			"
				DROP TABLE IF EXISTS
					wp_57_commentmeta,
					wp_57_comments,
					wp_57_links,
					wp_57_options,
					wp_57_postmeta,
					wp_57_posts,
					wp_57_redirection_404,
					wp_57_redirection_groups,
					wp_57_redirection_items,
					wp_57_redirection_logs,
					wp_57_term_relationships,
					wp_57_term_taxonomy,
					wp_57_termmeta,
					wp_57_terms
			"
		);
		$this->log( $drop );
	}

	/**
	 * Move all Reporter series to Reporter Issues taxonomy
	 */
	public function reporter_series_to_reporter_issues() {
		global $wpdb;

		// they're all gonna go
		$ret = $wpdb->update(
			'wp_term_taxonomy',
			array( 'taxonomy' => 'reporter-issues' ),
			array( 'taxonomy' => 'series' )
		);
	}

	public function perform_all_migrations() {
		$this->adjust_all_ids();
		$this->reporter_series_to_reporter_issues();
		$this->merge_catalyst_tables();
		$this->drop_catalyst_tables();
	}
}
