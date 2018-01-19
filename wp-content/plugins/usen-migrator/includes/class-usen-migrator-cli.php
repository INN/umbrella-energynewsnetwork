<?php
/**
 * Manage migration tasks related to merging one multisite into a singlesite.
 *
 * Usage:
 *    wp help enn
 */

/**
 * Copy all content from single site within a multisite to the primary site.
 */
class USEN_Migrator_CLI extends WP_CLI_Command {

	/**
	 * Contains an array(
	 *     (int) old id => (int) new id,
	 * ); of post IDs from the Old ID to the new ID, where the new ID was bumped by the maximum ID of the site that posts from the old site are being imported to.
	 *
	 * @var array $oldnew array( int => int ) of post IDs
	 */
	private $oldnew = array();

	/**
	 * @var int $site_id the site ID
	 */
	private $site_id = null;

	/**
	 * @var bool $redirection Whether this site has tables from the Redirection plugin.
	 */
	private $redirection = false;

	/**
	 * @var Array $table_names An array of unprefixed table names for this site.
	 */
	private $table_names = null;

	/**
	 * @var array( 'term_id' => INT, 'term_taxonomy_id' => INT ) The return of wp_insert_term, for creating a term that is specific to the posts in this migration
	 */
	private $term = null;

	/**
	 * For logging things
	 *
	 * @private
	 */
	private function log( $stuff ) {
		WP_CLI::line( var_export( $stuff, true ) );
	}

	/**
	 * Update post IDs
	 */
	private function update_catalyst_posts() {
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
				FROM " . $wpdb->prefix . $this->site_id . "_posts
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
				FROM " . $wpdb->prefix . $this->site_id . "_posts
				ORDER BY ID ASC
			"
		);

		$progress = \WP_CLI\Utils\make_progress_bar(
			"Updating IDs of site " . $this->site_id . "'s posts...",
			count( $olds )
		);

		foreach ( $olds as $old ) {
			$new = $old + $highest;

			// remember this ID for further use
			$this->oldnew[$old] = $new;

			// update ID in posts
			$wpdb->update(
				$wpdb->prefix . $this->site_id . '_posts',
				array( 'ID' => $new ),
				array( 'ID' => $old )
			);

			// update _posts where post_parent = old
			$wpdb->update(
				$wpdb->prefix . $this->site_id . '_posts',
				array( 'post_parent' => $new ),
				array( 'post_parent' => $old )
			);

			// update _term_relationships with new object_id
			$wpdb->update(
				$wpdb->prefix . $this->site_id . '_term_relationships',
				array( 'object_id' => $new ),
				array( 'object_id' => $old )
			);

			// update _postmeta with new post_id
			$wpdb->update(
				$wpdb->prefix . $this->site_id . '_postmeta',
				array( 'post_id' => $new ),
				array( 'post_id' => $old )
			);

			// update _postmeta _thumbnail_id with new thumbnail_id
			$wpdb->update(
				$wpdb->prefix . $this->site_id . '_postmeta',
				array( 'meta_value' => $new ),
				array(
					'meta_value' => $old,
					'meta_key' => '_thumbnail_id'
				)
			);

			// update _postmeta featured_media with new featured media
			$rows = $wpdb->get_results(
				"
					SELECT * FROM " . $wpdb->prefix . $this->site_id . "_postmeta
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
						$wpdb->prefix . $this->site_id . "_postmeta",
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
	 * Update post slugs
	 */
	public function update_old_slugs() {
		global $wpdb;

		// select all posts with non-empty slugs and with slugs matching existing slugs in the posts table
		$rows = $wpdb->get_results(
			"
				SELECT a.ID, a.post_date, a.post_name
				FROM " . $wpdb->prefix . $this->site_id . "_posts a
				JOIN " . $wpdb->prefix . "posts b
				WHERE a.post_name <> ''
				AND a.post_name = b.post_name
			"
		);

		$progress = \WP_CLI\Utils\make_progress_bar(
			"Updating slugs of site " . $this->site_id . " where they conflict with the target site",
			count( $rows )
		);

		foreach ( $rows as $row ) {
			// reset
			$new_pattern = null;
			$old_pattern = null;
			$exec = null;
			$log = null;


			$new_name = $row->post_name . '-2';

			// cache this
			$time = strtotime( $row->post_date ); // ugh, $row is stdClass::__set_state(array()), not array()

			// generate the matching URL for this site, or fail.
			switch( $this->site_id ) {
				case 58:
					$new_pattern = sprintf (
						'southeastenergynews.com/%1$s/%2$s/%3$s/%4$s',
						date( 'y', $time ),
						date( 'm', $time ),
						date( 'd', $time ),
						$new_name
					);
					$old_pattern = sprintf (
						'southeastenergynews.com/%1$s/%2$s/%3$s/%4$s',
						date( 'y', $time ),
						date( 'm', $time ),
						date( 'd', $time ),
						$row->post_name
					);
					break;
				case 64:
					$new_pattern = sprintf (
						'midwestenergynews.com/%1$s/%2$s/%3$s/%4$s',
						date( 'y', $time ),
						date( 'm', $time ),
						date( 'd', $time ),
						$new_name
					);
					$old_pattern = sprintf (
						'midwestenergynews.com/%1$s/%2$s/%3$s/%4$s',
						date( 'y', $time ),
						date( 'm', $time ),
						date( 'd', $time ),
						$row->post_name
					);
					break;
				default:
					WP_CLI::error( 'The command update_old_slugs does not know the permalink structure for site ' . $this->site_id . ' and so cannot perform the search-and-replace portion of its mission. Please edit it to match the former site.' );
			}

			// update the link in all posts linking to this post, in the present table
			$exec = shell_exec( sprintf(
				'wp search-replace %1$s %2$s --quiet',
				$old_pattern,
				$new_pattern
			) );
			WP_CLI::log( $exec );


			// update the post_name in this row
			$log = $wpdb->update(
				$wpdb->prefix . $this->site_id . '_posts',
				array(
					'post_name' => $new_name,
				),
				array(
					'post_name' => $row->post_name,
					'ID' => $row->ID,
					'post_date' => $row->post_date,
				)
			);
			if ( false === $log ) {
				WP_CLI::log( "something went wrong with the row update for post $row->ID post_name $row->post_name" );
			}


			$progress->tick();
		} // end foreach of posts with duplicating post_name/slug entries
		$progress->finish();
	}

	/**
	 */
	private function update_catalyst_postmeta() {
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
				FROM " . $wpdb->prefix . $this->site_id . "_postmeta
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
				FROM " . $wpdb->prefix . $this->site_id . "_postmeta
				ORDER BY meta_id ASC
			"
		);

		$progress = \WP_CLI\Utils\make_progress_bar(
			"Updating meta_ids of site " . $this->site_id . "'s postmeta...",
			count( $olds )
		);


		foreach ( $olds as $old ) {
			$new = $old + $highest;

			// increment meta_id
			$ret = $wpdb->update(
				$wpdb->prefix . $this->site_id . '_postmeta',
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
	private function update_catalyst_term_taxonomy() {
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
				FROM " . $wpdb->prefix . $this->site_id . "_term_taxonomy
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
				FROM " . $wpdb->prefix . $this->site_id . "_term_taxonomy
				ORDER BY term_taxonomy_id ASC
			"
		);

		$progress = \WP_CLI\Utils\make_progress_bar(
			"Updating term_taxonomy_ids of site " . $this->site_id . "'s term_taxonomies...",
			count( $olds )
		);

		foreach ( $olds as $old ) {
			$new = $old + $highest;

			// increment term_taxonomy_id
			$ret = $wpdb->update(
				$wpdb->prefix . $this->site_id . '_term_taxonomy',
				array( 'term_taxonomy_id' => $new ),
				array( 'term_taxonomy_id' => $old )
			);

			// update _term_relationships with new term_taxonomy_id
			// increment term_taxonomy_id
			$ret = $wpdb->update(
				$wpdb->prefix . $this->site_id . '_term_relationships',
				array( 'term_taxonomy_id' => $new ),
				array( 'term_taxonomy_id' => $old )
			);
			
			$progress->tick();
		}

		$progress->finish();
	}

	/**
	 */
	private function update_catalyst_terms() {
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
				FROM " . $wpdb->prefix . $this->site_id . "_terms
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
				FROM " . $wpdb->prefix . $this->site_id . "_terms
				ORDER BY term_id ASC
			"
		);

		$progress = \WP_CLI\Utils\make_progress_bar(
			"Updating term_ids of site " . $this->site_id . "'s terms...",
			count( $olds )
		);

		// Keep track of strange things
		$oddballs = array();

		foreach ( $olds as $old ) {
			$new = $old + $highest;

			// increment term_id
			$ret = $wpdb->update(
				$wpdb->prefix . $this->site_id . '_terms',
				array( 'term_id' => $new ),
				array( 'term_id' => $old )
			);

			// update _term_taxonomy with new term_id
			$ret = $wpdb->update(
				$wpdb->prefix . $this->site_id . '_term_taxonomy',
				array( 'term_id' => $new ),
				array( 'term_id' => $old )
			);

			// update _termmeta with new term_id
			$ret = $wpdb->update(
				$wpdb->prefix . $this->site_id . '_termmeta',
				array( 'term_id' => $new ),
				array( 'term_id' => $old )
			);

			// update _postmeta with top term's new term_id
			$ret = $wpdb->update(
				$wpdb->prefix . $this->site_id . '_postmeta',
				array( 'meta_value' => $new ),
				array(
					'meta_value' => $old,
					'meta_key' => 'top_term'
				)
			);

			// update _57_postmeta series_order with new term_id
			$ret = $wpdb->update(
				$wpdb->prefix . $this->site_id . '_postmeta',
				array( 'meta_key' => 'series_' . $new . '_order' ),
				array( 'meta_key' => 'series_' . $old . '_order' )
			);

			// update _posts where post_title = $taxonomy:$old_id with new $taxonomy:$new_id
			// this is the term meta post


			// first, figure out what taxonomy this is
			$full_term = get_term_by( 'id', $new, '', 'ARRAY_A' );
			$full_term = $wpdb->get_results(
				"
					SELECT a.taxonomy
					FROM " . $wpdb->prefix . $this->site_id . "_term_taxonomy a
					WHERE a.term_id = $new
				",
				'ARRAY_A'
			);
			$taxonomy = $full_term[0]['taxonomy'];

			// get the post ids for the term meta posts
			$term_meta_posts = $wpdb->get_results(
				"
					SELECT a.ID
					FROM " . $wpdb->prefix . $this->site_id . "_posts a
					INNER JOIN " . $wpdb->prefix . $this->site_id . "_term_relationships b
						ON a.ID = b.object_ID
					INNER JOIN " . $wpdb->prefix . $this->site_id . "_term_taxonomy c
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
					$wpdb->prefix . $this->site_id . '_posts',
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
	private function update_catalyst_termmeta() {
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
				FROM " . $wpdb->prefix . $this->site_id . "_termmeta
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
				FROM " . $wpdb->prefix . $this->site_id . "_termmeta
				ORDER BY meta_id ASC
			"
		);

		$progress = \WP_CLI\Utils\make_progress_bar(
			"Updating meta_ids of site " . $this->site_id . "'s termmeta...",
			count( $olds )
		);


		foreach ( $olds as $old ) {
			$new = $old + $highest;

			// increment meta_id in _termmeta
			$wpdb->update(
				$wpdb->prefix . $this->site_id . '_termmeta',
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
		// increment ID in _users
		// update post_author in _posts
		// update _usermeta with new user_id
		// update _comments with new user_id
	}

	/**
	 * Convert user roles to match the new site's singlesite nature
	 *
	 * This fixes a shortfall in INN/sql-utils/prepare_for_export.sql
	 */
	private function update_all_usermeta() {
		global $wpdb;

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
				array( 'meta_key' => $wpdb->prefix . $key )
			);

			$wpdb->update(
				'wp_usermeta',
				array( 'meta_key' => $wpdb->prefix . $key ),
				array( 'meta_key' => $wpdb->prefix . $this->site_id . '_' . $key )
			);
		}
	}

	/**
	 */
	private function update_catalyst_comments() {
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
				FROM " . $wpdb->prefix . $this->site_id . "_comments
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
				FROM " . $wpdb->prefix . $this->site_id . "_comments
				ORDER BY comment_id ASC
			"
		);

		$progress = \WP_CLI\Utils\make_progress_bar(
			"Updating comment_ids of site " . $this->site_id . "'s comments...",
			count( $olds )
		);


		foreach ( $olds as $old ) {
			$new = $old + $highest;

			// increment comment_id in _comments
			$wpdb->update(
				$wpdb->prefix . $this->site_id . '_comments',
				array( 'comment_id' => $new ),
				array( 'comment_id' => $old )
			);
			// update comment_id in _commentmeta
			$wpdb->update(
				$wpdb->prefix . $this->site_id . '_commentmeta',
				array( 'comment_id' => $new ),
				array( 'comment_id' => $old )
			);

			$progress->tick();
		}

		$progress->finish();
	}

	/**
	 */
	private function update_catalyst_commentmeta() {
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
				FROM " . $wpdb->prefix . $this->site_id . "_commentmeta
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
				FROM " . $wpdb->prefix . $this->site_id . "_commentmeta
				ORDER BY meta_id ASC
			"
		);

		$progress = \WP_CLI\Utils\make_progress_bar(
			"Updating meta_ids of site " . $this->site_id . "'s commentmeta...",
			count( $olds )
		);

		foreach ( $olds as $old ) {
			$new = $old + $highest;

			// increment meta_id in _commentmeta
			$wpdb->update(
				$wpdb->prefix . $this->site_id . '_commentmeta',
				array( 'meta_id' => $new ),
				array( 'meta_id' => $old )
			);

			$progress->tick();
		}

		$progress->finish();
	}

	/**
	 * Determine whether to run redirection items
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the site from which to draw content
	 */
	public function detect_redirection_tables( $args = null ) {
		// because this is a public function
		if ( ! isset( $this->site_id ) ) {
			if ( is_array( $args ) ) {
				$this->site_id = $this->_test( $args );
			} else {
				WP_CLI::error( 'sorry, something went wrong when trying to detect the site. Here\'s the arguments:' );
				WP_CLI::log( var_export( $args, true ) );
				return false;
			}
		}

		// make sure that this is what we want it to do.
		// make sure that this is what we want it to do.
		try {
			global $wpdb;
			$table_like = $wpdb->prefix . $this->site_id . "_redirection";
			$tables = $wpdb->get_results("SHOW TABLES LIKE '$table_like%'");
		} catch ( Exception $e ) {
			WP_CLI::log( $e->getMessage() );
			WP_CLI::error( 'Looks like that was not a thing we were meant to see.' );
			return false;
		}

		$return = ! empty( $tables );
		return $return;
	}

	/**
	 */
	private function update_catalyst_redirection_items() {
		global $wpdb;
		$highest_reporter = $wpdb->get_var(
			"
				SELECT id
				from " . $wpdb->prefix . $this->site_id . "_redirection_items
				ORDER BY id DESC limit 0,1
			"
		);
		$highest_catalyst = $wpdb->get_var(
			"
				SELECT id
				FROM " . $wpdb->prefix . $this->site_id . "_redirection_items
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
				FROM " . $wpdb->prefix . $this->site_id . "_redirection_items
				ORDER BY id ASC
			"
		);

		$progress = \WP_CLI\Utils\make_progress_bar(
			"Updating ids of site " . $this->site_id . "'s redirection items...",
			count( $olds )
		);

		foreach ( $olds as $old ) {
			$new = $old + $highest;

		// increment id in _redirection_items
			$wpdb->update(
				$wpdb->prefix . $this->site_id . '_redirection_items',
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
		// increment id in _redirection_groups
		// update group_id in _redirection_items
	}

	/**
	 * Adjust every single ID in the site post DB
	 *
	 * This command runs the following commands, fixing their IDs:
	 * - update_catalyst_posts
	 * - update_catalyst_postmeta
	 * - update_catalyst_term_taxonomy
	 * - update_catalyst_terms
	 * - update_catalyst_termmeta
	 * - update_catalyst_comments
	 * - update_catalyst_commentmeta
	 * - update_catalyst_redirection_items
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the site from which to draw content
	 */
	public function adjust_all_ids( $args = null ) {
		// because this is a public function
		if ( ! isset( $this->site_id ) ) {
			if ( is_array( $args ) ) {
				$this->site_id = $this->_test( $args );
			} else {
				WP_CLI::error( 'sorry, something went wrong when trying to detect the site. Here\'s the arguments:' );
				WP_CLI::log( var_export( $args, true ) );
				return false;
			}
		}

		$this->update_catalyst_posts();
		$this->update_catalyst_postmeta();
		$this->update_catalyst_term_taxonomy();
		$this->update_catalyst_terms();
		$this->update_catalyst_termmeta();
		$this->update_catalyst_comments();
		$this->update_catalyst_commentmeta();
		if ( $this->redirection ) {
			$this->update_catalyst_redirection_items();
			# $this->update_catalyst_redirection_groups();
		}
		$this->update_all_usermeta();
		$this->update_old_slugs();
	}

	/**
	 * Create a new term for posts in this migration
	 *
	 * safety first; this doesn't play well if the term has already been created.
	 * the term name is set in stone; recreating it fails.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the site from which to draw content
	 *
	 * @return array( 'term_id' => INT, 'term_taxonomy_id' => INT, 'taxonomy' => string ) The return of wp_insert_term, for creating a term that is specific to the posts in this migration, plus the taxonomy
	 * @uses $this->site_id the ID of the site that is being migrated.
	 * @link https://codex.wordpress.org/Function_Reference/wp_insert_term
	 */
	public function create_term( $args = null ) {
		// because this is a public function
		if ( ! isset( $this->site_id ) ) {
			if ( is_array( $args ) ) {
				$this->site_id = $this->_test( $args );
			} else {
				WP_CLI::error( 'sorry, something went wrong when trying to detect the site. Here\'s the arguments:' );
				WP_CLI::log( var_export( $args, true ) );
				return false;
			}
		}

		$taxonomy = 'region';

		$return = wp_insert_term(
			'Posts from site ID ' . $this->site_id,
			$taxonomy,
			array(
				'description' => sprintf( 
					__( 'These posts were migrated from former site ID %1$s', 'usen-migrator' ),
					$this->site_id
				),
			)
		);
		$return['taxonomy'] = $taxonomy;
		return $return;
	}

	/**
	 * Apply a term to all posts in this migration
	 *
	 * @uses $oldnew = array( (int) old id => (int) new id )
	 * @param array( 'term_id' => INT, 'term_taxonomy_id' => INT, 'taxonomy' => string ) The return of $this->create_term
	 */
	private function add_term( $term ) {
		// if $term isn't what we were expecting, throw a fit.
		if (
			! is_array( $term )
			||
 			(
				! array_key_exists( 'term_id', $term )
				||
				! array_key_exists( 'term_taxonomy_id', $term )
				||
				! array_key_exists( 'taxonomy', $term )
			)
		) {
			WP_CLI::error( 'something is wrong with the $term argument on this call to add_term. It should have an integer term ID, integer term_taxonomy_id, and string taxonomy name:' );
			WP_CLI::error( var_export( $term , true ) );
		}

		$progress = \WP_CLI\Utils\make_progress_bar(
			"Updating " . count( $this->oldnew ) . " posts from site " . $this->site_id . "'s posts with the new term...",
			count( $this->oldnew )
		);

		foreach ( $this->oldnew as $new ) {
			$post_type = get_post_type( $new );

			// Not all post IDs are for the types of posts we want to have this term.
			if ( in_array( $post_type, array( 'post', 'page', 'roundup' ), true ) ) {
				wp_set_object_terms( $new, $term['term_taxonomy_id'], $term['taxonomy'] );
			}

			$progress->tick();
		}

		$progress->finish();
	}

	/**
	 * Return a list of unprefixed table names for this site
	 *
	 * @return Array $table_names An array of unprefixed table names for this site. By "unprefixed" I mean that they don't have $wpdb->prefix, the site ID, or any leading underscore.
	 * @param Array $args the WP-CLI arguments for the command that's running this command.
	 */
	private function generate_table_names( $args = null ) {
		// because this is a public function
		if ( ! isset( $this->site_id ) ) {
			if ( is_array( $args ) ) {
				$this->site_id = $this->_test( $args );
			} else {
				WP_CLI::error( 'sorry, something went wrong when trying to detect the site. Here\'s the arguments:' );
				WP_CLI::log( var_export( $args, true ) );
				return false;
			}
		}

		$table_names = array(
			'commentmeta',
			'comments',
			// 'links', // can be ignored because it is empty
			// 'options', // because we're keeping the new site's options
			'postmeta',
			'posts',
			'term_relationships',
			'term_taxonomy',
			'termmeta',
			'terms'
		);

		if ( $this->redirection ) {
			array_merge(
				$table_names,
				array(
					// 'redirection_404', // can be ignored because it's useless
					// 'redirection_logs', // can be ignored because it's empty
					'redirection_groups',
					'redirection_items'
				)
			);
		}

		return $table_names;
	}

	/**
	 * Copy rows from Catalyst's tables into Reporter's tables
	 *
	 * This should only be run once IDs have been updated
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the site from which to draw content
	 */
	public function merge_catalyst_tables( $args = null ) {
		global $wpdb;
		$tables = array();

		if ( ! isset( $this->tables_names ) || empty ( $this->table_names ) ) {
			$this->table_names = $this->generate_table_names( $args );
		}

		// generate the table list of from -> to for moving content
		foreach ( $this->table_names as $name ) {
			$tables[ $wpdb->prefix . $this->site_id . '_' . $name ] = $wpdb->prefix . $name ;
		}

		// this technique from http://sqlblog.com/blogs/merrill_aldrich/archive/2011/08/17/handy-trick-move-rows-in-one-statement.aspx
		foreach ( $tables as $old => $new ) {
			$ret = $wpdb->query(
				"
					INSERT INTO $new
					SELECT * FROM $old
				"
			);
			$this->log( "$ret rows affected when copying rows from $old into $new." );
		}
	}

	/**
	 * Drop Catalyst's tables
	 */
	private function drop_tables() {
		global $wpdb;

		if ( empty ( $this->table_names ) ) {
			WP_CLI::error( 'tried to run drop_tables but no tables were specified in $this->tables' );
		}
		foreach ( $this->table_names as $table ) {
		$drop = $wpdb->query(
			"DROP TABLE IF EXISTS " . $wpdb->prefix . $this->site_id . '_' . $table );
		}
		$this->log( $drop );
	}

	/**
	 * Perform all migration steps
	 */
	private function perform_all_migrations() {
		$this->adjust_all_ids();
		$this->merge_catalyst_tables();

		if ( ! isset( $this->term ) ) {
			// don't try to create the specific term name if it already exists;
			// it'll cause an error.
			$this->term = $this->create_term();
		}
		$this->add_term( $this->term );

		$this->drop_tables();
	}

	/**
	 * Test function to make sure that you're sending an ID that actually works
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the site from which to draw content
	 *
	 * @subcommand test
	 */
	public function _test( $args = null ) {
		if ( ! is_numeric( $args[0] ) || floatval( $args[0] ) != intval( $args[0] ) ) {
			WP_CLI::error( sprintf(
				'%1$s is not a site ID',
				var_export( $args[0], true )
			) );
		}
		$id = (int) $args[0];

		try {
			global $wpdb;
			$table_like = $wpdb->prefix . $id;
			$tables = $wpdb->get_results("SHOW TABLES LIKE '$table_like%'");
		} catch ( Exception $e ) {
			WP_CLI::log( $e->getMessage() );
			WP_CLI::error( 'Looks like that was not a thing we were meant to see.' );
		}

		// if there were no tables or if the table query did not run:
		if ( empty( $tables ) ) {
			WP_CLI::error( "$id does not appear to have any tables in this database." );
		}

		return $id;
	}

	/**
	 * Reset certain Largo theme options in the primary site
	 *
	 * @uses optionsframework_init from Largo/lib/options-framework/options-framework.php
	 * @uses of_get_default_values from Largo/options.php
	 * @since Largo 0.5.5.4
	 */
	public function largo_reset_options() {
		if ( ! function_exists( 'of_get_default_values' ) || ! function_exists( 'optionsframework_init' ) ) {
			WP_CLI::error( 'The command largo_partial_reset is depends upon the function of_get_default_values, optionsframework_options, and optionsframework_init  from the Largo theme. `wp enn largo_partial_reset` cannot find that function. Are you sure that the theme Largo is installed and active?' );
		}

		if ( ! function_exists( 'optionsframework_options' ) ) {
			optionsframework_init();

			if ( ! function_exists( 'optionsframework_options' ) ) {
				WP_CLI::error( 'The command largo_partial_reset is depends upon the function optionsframework_options from the Largo theme. `wp enn largo_partial_reset` cannot find that function. Are you sure that the theme Largo is installed and active?' );
			}
		}

		WP_CLI::log( 'Resetting the Largo theme options...' );

		// logic borrowed from largo_set_new_option_defaults, but in this case we're not merging them.
		$options = of_get_default_values();
		$config = get_option( 'optionsframework' );

		update_option( $config['id'], $options );
	}

	/**
	 * Given a site ID, move all content (posts, terms, comments) from that ID's db to the primary site
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the site from which to draw content
	 *
	 * @subcommand migrate
	 */
	public function migrate( $args ) {
		// make sure that this is what we want it to do.
		$this->site_id = $this->_test( $args );
		$this->redirection = $this->detect_redirection_tables( $args );
		$this->table_names = $this->generate_table_names( $args );
		$this->perform_all_migrations( $this->site_id );
	}
}
