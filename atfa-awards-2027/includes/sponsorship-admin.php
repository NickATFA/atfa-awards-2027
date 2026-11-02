<?php
/**
 * wp-admin > Awards sponsorship: category availability, Platinum access codes, requests and settings for the
 * /awards/sponsorship/ page (includes/sponsorship.php). Editors and Administrators can use it.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Who can use this screen: administrators, until the page is open to everyone (then Editors too). It shows the codes,
 * so it stays with the people who can see the page.
 */
function atfa_aws_admin_cap() {
	return 'public' === atfa_aws_settings()['access'] ? 'edit_pages' : 'manage_options';
}

add_action( 'admin_menu', function () {
	add_menu_page( 'Awards sponsorship', 'Awards sponsorship', atfa_aws_admin_cap(), 'atfa-aws', 'atfa_aws_admin_page', 'dashicons-awards', 27 );
} );

function atfa_aws_admin_url( $tab = 'categories', $args = array() ) {
	return add_query_arg( array_merge( array( 'page' => 'atfa-aws', 'tab' => $tab ), $args ), admin_url( 'admin.php' ) );
}

function atfa_aws_admin_check( $action ) {
	if ( ! current_user_can( atfa_aws_admin_cap() ) ) {
		wp_die( 'Not allowed.' );
	}
	check_admin_referer( $action );
}

function atfa_aws_admin_done( $tab, $msg, $type = 'success' ) {
	wp_safe_redirect( atfa_aws_admin_url( $tab, array( 'aws_msg' => rawurlencode( $msg ), 'aws_type' => $type ) ) );
	exit;
}

function atfa_aws_when( $ts ) {
	return $ts ? wp_date( 'D j M, g:i a', (int) $ts ) : '';
}

/* ---------------------------------------------------------------------------
 * The screen
 * ------------------------------------------------------------------------ */

function atfa_aws_admin_page() {
	if ( ! current_user_can( atfa_aws_admin_cap() ) ) {
		return;
	}
	$tab  = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'categories'; // phpcs:ignore WordPress.Security.NonceVerification
	$tabs = array(
		'categories' => 'Categories',
		'codes'      => 'Access codes',
		'requests'   => 'Requests',
		'settings'   => 'Settings',
	);
	if ( ! isset( $tabs[ $tab ] ) ) {
		$tab = 'categories';
	}
	$settings = atfa_aws_settings();
	$page_url = atfa_aws_page_url();
	?>
	<div class="wrap atfa-aws">
		<h1 class="wp-heading-inline">2027 Awards category sponsorship</h1>
		<a class="page-title-action" href="<?php echo esc_url( $page_url ); ?>" target="_blank" rel="noopener">View the page</a>
		<hr class="wp-header-end">
		<p class="aws-lead">
			The page is at <a href="<?php echo esc_url( $page_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( preg_replace( '#^https?://#', '', $page_url ) ); ?></a>.
			<?php if ( 'public' === $settings['access'] ) : ?>
				<strong>Open to everyone.</strong>
			<?php elseif ( 'admin' === $settings['access'] ) : ?>
				<strong>Hidden for now:</strong> only administrators can see it, and this screen. Everyone else, staff and Platinum Members with a code included, is sent to the awards page. Open it under Settings when you're ready.
			<?php else : ?>
				<strong>Platinum first look:</strong> only Platinum Members with their access code, and administrators, can see it. Everyone else, staff included, gets the code screen.
			<?php endif; ?>
			<?php echo '' !== $settings['book_url'] ? 'Bookings go to EPOXY.' : 'Requests come to ' . esc_html( $settings['to'] ) . ' and are booked by hand.'; ?>
		</p>
		<?php
		if ( isset( $_GET['aws_msg'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$type = isset( $_GET['aws_type'] ) && 'error' === $_GET['aws_type'] ? 'error' : 'success'; // phpcs:ignore WordPress.Security.NonceVerification
			echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . esc_html( wp_unslash( rawurldecode( (string) $_GET['aws_msg'] ) ) ) . '</p></div>'; // phpcs:ignore WordPress.Security.NonceVerification
		}
		?>
		<nav class="nav-tab-wrapper">
			<?php foreach ( $tabs as $k => $label ) : ?>
				<a class="nav-tab<?php echo $k === $tab ? ' nav-tab-active' : ''; ?>" href="<?php echo esc_url( atfa_aws_admin_url( $k ) ); ?>"><?php echo esc_html( $label ); ?><?php
				if ( 'requests' === $k ) {
					$new = atfa_aws_request_count( 'new' );
					if ( $new ) {
						echo ' <span class="awaiting-mod">' . (int) $new . '</span>';
					}
				}
				?></a>
			<?php endforeach; ?>
		</nav>
		<?php
		if ( 'codes' === $tab ) {
			atfa_aws_admin_codes();
		} elseif ( 'requests' === $tab ) {
			atfa_aws_admin_requests();
		} elseif ( 'settings' === $tab ) {
			atfa_aws_admin_settings( $settings );
		} else {
			atfa_aws_admin_categories();
		}
		?>
	</div>
	<style>
	.atfa-aws .aws-lead{font-size:14px;max-width:900px}
	.atfa-aws .aws-sec{margin-top:18px}
	.atfa-aws table.widefat td,.atfa-aws table.widefat th{vertical-align:middle}
	.atfa-aws .aws-pill{display:inline-block;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.04em}
	.atfa-aws .aws-available{background:#e5f5ea;color:#1e6b38}.atfa-aws .aws-hold{background:#fdf0dc;color:#8a4b00}.atfa-aws .aws-taken{background:#e7e7e7;color:#333}
	.atfa-aws .aws-new{background:#fdf0dc;color:#8a4b00}.atfa-aws .aws-confirmed{background:#e5f5ea;color:#1e6b38}.atfa-aws .aws-released{background:#e7e7e7;color:#333}.atfa-aws .aws-test{background:#e8eefc;color:#1d3f99}
	.atfa-aws .aws-stream td{background:#f6f7f7;font-weight:600;text-transform:uppercase;font-size:11px;letter-spacing:.06em;color:#50575e}
	.atfa-aws code.aws-code{font-size:14px;letter-spacing:.06em;padding:3px 6px}
	.atfa-aws .aws-off td{opacity:.55}
	.atfa-aws .aws-actions{display:flex;gap:6px;flex-wrap:wrap}
	.atfa-aws .aws-actions form{margin:0}
	.atfa-aws .aws-muted{color:#646970}
	.atfa-aws textarea.aws-bulk{width:100%;max-width:760px;min-height:150px;font-family:monospace}
	.atfa-aws .aws-copied{color:#1e6b38;font-weight:600;margin-left:6px}
	.atfa-aws .aws-two{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:24px;max-width:1200px}
	@media (max-width:960px){.atfa-aws .aws-two{grid-template-columns:1fr}}
	</style>
	<script>
	document.addEventListener('click',function(e){
		var b=e.target.closest('[data-aws-copy]'); if(!b) return;
		e.preventDefault();
		var t=b.getAttribute('data-aws-copy'), done=function(){ var s=document.createElement('span'); s.className='aws-copied'; s.textContent='Copied'; b.after(s); setTimeout(function(){ s.remove(); },1600); };
		if(navigator.clipboard&&navigator.clipboard.writeText){ navigator.clipboard.writeText(t).then(done,function(){ window.prompt('Copy this:',t); }); }
		else { window.prompt('Copy this:',t); }
	});
	</script>
	<?php
}

/* ------------------------------------------------------------------ Categories */

function atfa_aws_admin_categories() {
	$cats     = atfa_aws_categories();
	$statuses = atfa_aws_statuses();
	$codes    = atfa_aws_codes();
	$labels   = array(
		'member'     => 'Member voted · project awards',
		'panel'      => 'Expert panel · project awards',
		'excellence' => 'Excellence awards',
		'newcomers'  => 'Newcomers',
	);
	$counts   = array( 'available' => 0, 'hold' => 0, 'taken' => 0 );
	foreach ( $statuses as $s ) {
		$counts[ $s['status'] ]++;
	}
	$stream = '';
	?>
	<div class="aws-sec">
		<p><strong><?php echo (int) $counts['available']; ?></strong> available · <strong><?php echo (int) $counts['hold']; ?></strong> on hold · <strong><?php echo (int) $counts['taken']; ?></strong> secured, of <?php echo count( $cats ); ?> categories.
		Visitors see the status, never the name. <em>On hold</em> is what a request does on its own; set <em>Secured</em> once the sponsor has confirmed. Floor of the Year isn't listed: it went with the major awards sponsorship.</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="atfa_aws_save_status">
			<?php wp_nonce_field( 'atfa_aws_save_status' ); ?>
			<table class="widefat striped" style="max-width:1200px">
				<thead><tr><th style="width:32%">Category</th><th style="width:14%">Status</th><th style="width:24%">Sponsor</th><th>Note</th></tr></thead>
				<tbody>
				<?php foreach ( $cats as $key => $c ) : ?>
					<?php
					$s = $statuses[ $key ];
					if ( $c['stream'] !== $stream ) {
						$stream = $c['stream'];
						echo '<tr class="aws-stream"><td colspan="4">' . esc_html( isset( $labels[ $stream ] ) ? $labels[ $stream ] : $stream ) . '</td></tr>';
					}
					$locked = ! empty( $c['sponsor']['img'] ) && empty( $c['sponsor']['atfa'] );
					$via    = ( '' !== $s['member'] && isset( $codes[ $s['member'] ] ) ) ? 'Access code: ' . $codes[ $s['member'] ]['company'] : '';
					?>
					<tr>
						<td><strong><?php echo esc_html( $c['name'] ); ?></strong><?php echo $s['updated'] ? '<br><span class="aws-muted">Changed ' . esc_html( atfa_aws_when( $s['updated'] ) ) . '</span>' : ''; ?></td>
						<td>
							<?php if ( $locked ) : ?>
								<span class="aws-pill aws-taken">Secured</span><br><span class="aws-muted">Logo set in data/categories.php</span>
							<?php else : ?>
								<select name="status[<?php echo esc_attr( $key ); ?>]">
									<option value="available" <?php selected( $s['status'], 'available' ); ?>>Available</option>
									<option value="hold" <?php selected( $s['status'], 'hold' ); ?>>On hold</option>
									<option value="taken" <?php selected( $s['status'], 'taken' ); ?>>Secured</option>
								</select>
							<?php endif; ?>
						</td>
						<td>
							<?php if ( $locked ) : ?>
								<?php echo esc_html( $s['sponsor'] ); ?>
							<?php else : ?>
								<input type="text" class="regular-text" style="width:100%" name="sponsor[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $s['sponsor'] ); ?>" placeholder="Company">
								<?php echo $via ? '<br><span class="aws-muted">' . esc_html( $via ) . '</span>' : ''; ?>
							<?php endif; ?>
						</td>
						<td>
							<?php if ( ! $locked ) : ?>
								<input type="text" style="width:100%" name="note[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $s['note'] ); ?>" placeholder="Staff only">
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<p><button type="submit" class="button button-primary">Save categories</button> <span class="aws-muted">Setting a category back to Available clears its sponsor.</span></p>
		</form>
	</div>
	<?php
}

add_action( 'admin_post_atfa_aws_save_status', function () {
	atfa_aws_admin_check( 'atfa_aws_save_status' );
	$statuses = atfa_aws_statuses();
	$changes  = array();
	// phpcs:disable WordPress.Security.NonceVerification
	foreach ( atfa_aws_categories() as $key => $c ) {
		if ( ! isset( $_POST['status'][ $key ] ) ) {
			continue;
		}
		$status  = sanitize_key( wp_unslash( $_POST['status'][ $key ] ) );
		$status  = in_array( $status, array( 'available', 'hold', 'taken' ), true ) ? $status : 'available';
		$sponsor = isset( $_POST['sponsor'][ $key ] ) ? atfa_aws_text( wp_unslash( $_POST['sponsor'][ $key ] ), 120 ) : '';
		$note    = isset( $_POST['note'][ $key ] ) ? atfa_aws_text( wp_unslash( $_POST['note'][ $key ] ), 200 ) : '';
		$old     = $statuses[ $key ];
		if ( $old['status'] === $status && $old['sponsor'] === $sponsor && $old['note'] === $note ) {
			continue;
		}
		$changes[ $key ] = array_merge( $old, array( 'status' => $status, 'sponsor' => $sponsor, 'note' => $note ) );
		// A sponsor typed by hand is no longer the code holder who asked.
		if ( $old['sponsor'] !== $sponsor ) {
			$changes[ $key ]['member'] = '';
		}
	}
	// phpcs:enable
	if ( $changes ) {
		atfa_aws_set_statuses( $changes );
	}
	atfa_aws_admin_done( 'categories', $changes ? 'Saved ' . count( $changes ) . ' ' . _n( 'change', 'changes', count( $changes ) ) . '. The page shows it straight away.' : 'Nothing had changed.' );
} );

/* ------------------------------------------------------------------ Access codes */

function atfa_aws_invite_text( $c ) {
	$offer = atfa_aws_offer();
	$user  = wp_get_current_user();
	$me    = $user->first_name ? $user->first_name : $user->display_name;
	$first = '' !== trim( (string) $c['contact'] ) ? trim( strtok( $c['contact'], ' ' ) ) : 'there';
	$from  = atfa_aws_settings()['public_from'];
	return 'Hi ' . $first . ",\n\n"
		. 'As an ATFA Platinum Member, ' . $c['company'] . ' gets first pick of the 2027 ATFA Flooring Awards category sponsorships, before they are listed on the ATFA website' . ( '' !== $from ? ' on ' . $from : '' ) . ".\n\n"
		. 'Each category is ' . atfa_aws_price() . ' and includes two tickets to the Awards Dinner on ' . $offer['dinner']['date'] . ' at ' . $offer['dinner']['venue'] . '. Initial allocations are limited to ' . atfa_aws_words( atfa_aws_settings()['limit'] ) . ' ' . _n( 'category', 'categories', atfa_aws_settings()['limit'] ) . " per sponsor, on a first-confirmed basis.\n\n"
		. "See the categories that are still available and send your request here:\n"
		. atfa_aws_personal_link( $c['code'] ) . "\n\n"
		. 'If it asks for a code, use ' . $c['code'] . ".\n\n"
		. "Regards,\n" . $me;
}

function atfa_aws_admin_codes() {
	$codes    = atfa_aws_codes();
	$requests = array();
	foreach ( atfa_aws_requests() as $r ) {
		$mid = (string) get_post_meta( $r->ID, 'member', true );
		if ( '' !== $mid && ! get_post_meta( $r->ID, 'test', true ) ) {
			$requests[ $mid ] = isset( $requests[ $mid ] ) ? $requests[ $mid ] + 1 : 1;
		}
	}
	uasort( $codes, function ( $a, $b ) {
		return strcasecmp( $a['company'], $b['company'] );
	} );
	?>
	<div class="aws-two aws-sec">
		<div>
			<h2>Add Platinum Members</h2>
			<p>One company per line: <code>Company, contact name, email</code>. The contact and email are optional and only used for the invitation text. Each new company gets its own code. A company that is already here gets its contact and email updated instead.</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="atfa_aws_add_codes">
				<?php wp_nonce_field( 'atfa_aws_add_codes' ); ?>
				<textarea name="lines" class="aws-bulk" placeholder="Hurford's, Jane Citizen, jane@example.com&#10;Bostik"></textarea>
				<p><button type="submit" class="button button-primary">Add and make codes</button></p>
			</form>
		</div>
		<div>
			<h2>How members get in</h2>
			<p>Send each member their personal link (it opens the page in one tap) or their code. A code works on any device, and the page remembers each device for 90 days.</p>
			<p><strong>Stop</strong> shuts a code out straight away, including devices already let in. <strong>New code</strong> replaces a code that has been passed around. Stopped codes can be restarted.</p>
			<p><a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=atfa_aws_export_codes' ), 'atfa_aws_export_codes' ) ); ?>">Download codes and links (CSV)</a></p>
		</div>
	</div>
	<h2 class="aws-sec">Codes (<?php echo count( $codes ); ?>)</h2>
	<?php if ( ! $codes ) : ?>
		<p>No codes yet. Add the Platinum Members above.</p>
	<?php else : ?>
	<table class="widefat striped" style="max-width:1400px">
		<thead><tr><th>Company</th><th>Contact</th><th>Code</th><th>Send them</th><th>Visits</th><th>Requests</th><th>Actions</th></tr></thead>
		<tbody>
		<?php foreach ( $codes as $id => $c ) : ?>
			<?php $link = atfa_aws_personal_link( $c['code'] ); ?>
			<tr class="<?php echo empty( $c['active'] ) ? 'aws-off' : ''; ?>">
				<td><strong><?php echo esc_html( $c['company'] ); ?></strong><?php echo empty( $c['active'] ) ? '<br><span class="aws-pill aws-taken">Stopped</span>' : ''; ?></td>
				<td><?php echo esc_html( $c['contact'] ); ?><?php echo '' !== $c['email'] ? '<br><a href="mailto:' . esc_attr( $c['email'] ) . '">' . esc_html( $c['email'] ) . '</a>' : ''; ?></td>
				<td><code class="aws-code"><?php echo esc_html( $c['code'] ); ?></code></td>
				<td class="aws-actions">
					<button type="button" class="button button-small" data-aws-copy="<?php echo esc_attr( $link ); ?>">Copy link</button>
					<button type="button" class="button button-small" data-aws-copy="<?php echo esc_attr( atfa_aws_invite_text( $c ) ); ?>">Copy invitation</button>
				</td>
				<td><?php echo (int) ( $c['visits'] ?? 0 ); ?><?php echo ! empty( $c['last'] ) ? '<br><span class="aws-muted">Last ' . esc_html( atfa_aws_when( $c['last'] ) ) . '</span>' : '<br><span class="aws-muted">Not yet</span>'; ?></td>
				<td><?php echo isset( $requests[ $id ] ) ? (int) $requests[ $id ] : 0; ?></td>
				<td class="aws-actions">
					<?php
					$acts = ! empty( $c['active'] ) ? array( 'stop' => 'Stop', 'renew' => 'New code' ) : array( 'start' => 'Restart' );
					$acts['delete'] = 'Delete';
					foreach ( $acts as $do => $label ) :
						?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"<?php echo 'delete' === $do ? ' onsubmit="return confirm(\'Delete the code for ' . esc_attr( esc_js( $c['company'] ) ) . '? Anyone using it is shut out.\')"' : ''; ?>>
							<input type="hidden" name="action" value="atfa_aws_code_action">
							<input type="hidden" name="id" value="<?php echo esc_attr( $id ); ?>">
							<input type="hidden" name="do" value="<?php echo esc_attr( $do ); ?>">
							<?php wp_nonce_field( 'atfa_aws_code_action' ); ?>
							<button type="submit" class="button button-small<?php echo 'delete' === $do ? ' button-link-delete' : ''; ?>"><?php echo esc_html( $label ); ?></button>
						</form>
					<?php endforeach; ?>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>
	<?php
}

add_action( 'admin_post_atfa_aws_add_codes', function () {
	atfa_aws_admin_check( 'atfa_aws_add_codes' );
	$lines = isset( $_POST['lines'] ) ? explode( "\n", (string) wp_unslash( $_POST['lines'] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification
	$codes = atfa_aws_codes();
	$added = 0;
	$moved = 0;
	foreach ( $lines as $line ) {
		$parts   = array_map( 'trim', preg_split( '/\t|,/', $line ) );
		$company = atfa_aws_text( $parts[0] ?? '', 120 );
		if ( '' === $company ) {
			continue;
		}
		$contact = atfa_aws_text( $parts[1] ?? '', 100 );
		$email   = sanitize_email( $parts[2] ?? '' );
		$found   = '';
		foreach ( $codes as $id => $c ) {
			if ( 0 === strcasecmp( $c['company'], $company ) ) {
				$found = $id;
				break;
			}
		}
		if ( $found ) {
			if ( '' !== $contact ) {
				$codes[ $found ]['contact'] = $contact;
			}
			if ( '' !== $email ) {
				$codes[ $found ]['email'] = $email;
			}
			$moved++;
			continue;
		}
		$id           = wp_generate_password( 12, false, false );
		$codes[ $id ] = array(
			'company' => $company,
			'contact' => $contact,
			'email'   => $email,
			'code'    => atfa_aws_make_code( $codes ),
			'active'  => true,
			'created' => time(),
			'by'      => get_current_user_id(),
			'visits'  => 0,
			'last'    => 0,
		);
		$added++;
	}
	atfa_aws_save_codes( $codes );
	$msg = array();
	if ( $added ) {
		$msg[] = 'Made ' . $added . ' ' . _n( 'code', 'codes', $added ) . '.';
	}
	if ( $moved ) {
		$msg[] = 'Updated ' . $moved . ' ' . _n( 'company', 'companies', $moved ) . ' that already had a code.';
	}
	atfa_aws_admin_done( 'codes', $msg ? implode( ' ', $msg ) : 'Nothing to add. Put one company on each line.', $msg ? 'success' : 'error' );
} );

add_action( 'admin_post_atfa_aws_code_action', function () {
	atfa_aws_admin_check( 'atfa_aws_code_action' );
	// phpcs:disable WordPress.Security.NonceVerification
	$id = isset( $_POST['id'] ) ? preg_replace( '/[^A-Za-z0-9]/', '', (string) wp_unslash( $_POST['id'] ) ) : '';
	$do = isset( $_POST['do'] ) ? sanitize_key( wp_unslash( $_POST['do'] ) ) : '';
	// phpcs:enable
	$codes = atfa_aws_codes();
	if ( '' === $id || empty( $codes[ $id ] ) ) {
		atfa_aws_admin_done( 'codes', 'That code has gone.', 'error' );
	}
	$company = $codes[ $id ]['company'];
	if ( 'stop' === $do ) {
		$codes[ $id ]['active'] = false;
		$msg                    = 'Stopped the code for ' . $company . '.';
	} elseif ( 'start' === $do ) {
		$codes[ $id ]['active'] = true;
		$msg                    = 'Restarted the code for ' . $company . '.';
	} elseif ( 'renew' === $do ) {
		$codes[ $id ]['code'] = atfa_aws_make_code();
		$msg                  = 'New code for ' . $company . ': ' . $codes[ $id ]['code'] . '. The old one no longer works.';
	} elseif ( 'delete' === $do ) {
		unset( $codes[ $id ] );
		$msg = 'Deleted the code for ' . $company . '.';
	} else {
		atfa_aws_admin_done( 'codes', 'Unknown action.', 'error' );
	}
	atfa_aws_save_codes( $codes );
	atfa_aws_admin_done( 'codes', $msg );
} );

add_action( 'admin_post_atfa_aws_export_codes', function () {
	if ( ! current_user_can( atfa_aws_admin_cap() ) ) {
		wp_die( 'Not allowed.' );
	}
	check_admin_referer( 'atfa_aws_export_codes' );
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="awards-2027-sponsorship-codes-' . wp_date( 'Y-m-d' ) . '.csv"' );
	$out = fopen( 'php://output', 'w' );
	fputcsv( $out, array( 'Company', 'Contact', 'Email', 'Code', 'Personal link', 'Active', 'Visits', 'Last visit' ) );
	foreach ( atfa_aws_codes() as $c ) {
		fputcsv( $out, array( $c['company'], $c['contact'], $c['email'], $c['code'], atfa_aws_personal_link( $c['code'] ), empty( $c['active'] ) ? 'No' : 'Yes', (int) ( $c['visits'] ?? 0 ), ! empty( $c['last'] ) ? wp_date( 'Y-m-d H:i', (int) $c['last'] ) : '' ) );
	}
	fclose( $out );
	exit;
} );

/* ------------------------------------------------------------------ Requests */

function atfa_aws_requests() {
	return get_posts( array(
		'post_type'   => ATFA_AWS_REQUEST,
		'post_status' => 'private',
		'numberposts' => 300,
		'orderby'     => 'date',
		'order'       => 'DESC',
	) );
}

function atfa_aws_request_count( $state ) {
	$n = 0;
	foreach ( atfa_aws_requests() as $r ) {
		if ( get_post_meta( $r->ID, 'state', true ) === $state && ! get_post_meta( $r->ID, 'test', true ) ) {
			$n++;
		}
	}
	return $n;
}

function atfa_aws_admin_requests() {
	$requests = atfa_aws_requests();
	$cats     = atfa_aws_categories();
	$statuses = atfa_aws_statuses();
	$labels   = array( 'available' => 'Available', 'hold' => 'On hold', 'taken' => 'Secured' );
	?>
	<div class="aws-sec">
		<p>Every request is also emailed to <?php echo esc_html( atfa_aws_settings()['to'] ); ?>. <strong>Confirm</strong> marks its categories Secured for that company. <strong>Release</strong> puts them back on the page as Available. Requests sent from a staff login are tests: they hold nothing and can be deleted.</p>
		<?php if ( ! $requests ) : ?>
			<p>No requests yet.</p>
		<?php else : ?>
		<table class="widefat striped" style="max-width:1400px">
			<thead><tr><th>Received</th><th>Company</th><th>Contact</th><th>Categories (status now)</th><th>Notes</th><th>State</th><th>Actions</th></tr></thead>
			<tbody>
			<?php foreach ( $requests as $r ) : ?>
				<?php
				$m     = function ( $k ) use ( $r ) {
					return (string) get_post_meta( $r->ID, $k, true );
				};
				$test  = (bool) get_post_meta( $r->ID, 'test', true );
				$state = $test ? 'test' : ( $m( 'state' ) ? $m( 'state' ) : 'new' );
				$names = array();
				foreach ( (array) get_post_meta( $r->ID, 'categories', true ) as $k ) {
					$st      = isset( $statuses[ $k ] ) ? $statuses[ $k ]['status'] : '';
					$names[] = '<strong>' . esc_html( isset( $cats[ $k ] ) ? $cats[ $k ]['name'] : $k ) . '</strong>' . ( $st ? ' <span class="aws-pill aws-' . esc_attr( $st ) . '">' . esc_html( $labels[ $st ] ) . '</span>' : '' );
				}
				?>
				<tr>
					<td><?php echo esc_html( atfa_aws_when( get_post_time( 'U', true, $r ) ) ); ?></td>
					<td><strong><?php echo esc_html( $m( 'company' ) ); ?></strong><?php echo '' !== $m( 'code' ) ? '<br><span class="aws-muted">Code ' . esc_html( $m( 'code' ) ) . '</span>' : ''; ?></td>
					<td><?php echo esc_html( $m( 'name' ) ); ?><br><a href="mailto:<?php echo esc_attr( $m( 'email' ) ); ?>"><?php echo esc_html( $m( 'email' ) ); ?></a><?php echo '' !== $m( 'phone' ) ? '<br>' . esc_html( $m( 'phone' ) ) : ''; ?></td>
					<td><?php echo implode( '<br>', $names ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
					<td style="max-width:280px"><?php echo nl2br( esc_html( $m( 'notes' ) ) ); ?></td>
					<td><span class="aws-pill aws-<?php echo esc_attr( $state ); ?>"><?php echo esc_html( ucfirst( $state ) ); ?></span></td>
					<td class="aws-actions">
						<?php
						$acts = $test ? array( 'delete' => 'Delete' ) : array( 'confirm' => 'Confirm', 'release' => 'Release' );
						foreach ( $acts as $do => $label ) :
							if ( ( 'confirm' === $do && 'confirmed' === $state ) || ( 'release' === $do && 'released' === $state ) ) {
								continue;
							}
							?>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"<?php echo 'release' === $do ? ' onsubmit="return confirm(\'Release these categories? They show as Available on the page again.\')"' : ''; ?>>
								<input type="hidden" name="action" value="atfa_aws_request_action">
								<input type="hidden" name="id" value="<?php echo (int) $r->ID; ?>">
								<input type="hidden" name="do" value="<?php echo esc_attr( $do ); ?>">
								<?php wp_nonce_field( 'atfa_aws_request_action' ); ?>
								<button type="submit" class="button button-small<?php echo 'confirm' === $do ? ' button-primary' : ''; ?>"><?php echo esc_html( $label ); ?></button>
							</form>
						<?php endforeach; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>
	</div>
	<?php
}

add_action( 'admin_post_atfa_aws_request_action', function () {
	atfa_aws_admin_check( 'atfa_aws_request_action' );
	// phpcs:disable WordPress.Security.NonceVerification
	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	$do = isset( $_POST['do'] ) ? sanitize_key( wp_unslash( $_POST['do'] ) ) : '';
	// phpcs:enable
	$r = $id ? get_post( $id ) : null;
	if ( ! $r || ATFA_AWS_REQUEST !== $r->post_type ) {
		atfa_aws_admin_done( 'requests', 'That request has gone.', 'error' );
	}
	if ( 'delete' === $do ) {
		wp_delete_post( $id, true );
		atfa_aws_admin_done( 'requests', 'Deleted.' );
	}
	$company  = (string) get_post_meta( $id, 'company', true );
	$member   = (string) get_post_meta( $id, 'member', true );
	$statuses = atfa_aws_statuses();
	$changes  = array();
	$skipped  = array();
	$cats     = atfa_aws_categories();
	foreach ( (array) get_post_meta( $id, 'categories', true ) as $k ) {
		if ( ! isset( $statuses[ $k ] ) ) {
			continue;
		}
		$s = $statuses[ $k ];
		// Only touch a category that is free or still with this request's company.
		$theirs = 'available' === $s['status'] || ( '' !== $member ? $s['member'] === $member : 0 === strcasecmp( $s['sponsor'], $company ) );
		if ( ! $theirs ) {
			$skipped[] = $cats[ $k ]['name'];
			continue;
		}
		if ( 'confirm' === $do ) {
			$changes[ $k ] = array_merge( $s, array( 'status' => 'taken', 'sponsor' => $company, 'member' => $member, 'note' => 'Confirmed ' . wp_date( 'j M' ) ) );
		} elseif ( 'release' === $do ) {
			$changes[ $k ] = array_merge( $s, array( 'status' => 'available', 'note' => '' ) );
		}
	}
	if ( $changes ) {
		atfa_aws_set_statuses( $changes );
	}
	update_post_meta( $id, 'state', 'confirm' === $do ? 'confirmed' : 'released' );
	$msg = ( 'confirm' === $do ? 'Confirmed: ' : 'Released: ' ) . ( $changes ? implode( ', ', array_map( function ( $k ) use ( $cats ) {
		return $cats[ $k ]['name'];
	}, array_keys( $changes ) ) ) : 'no categories changed' ) . '.';
	if ( $skipped ) {
		$msg .= ' Left alone because another sponsor has them now: ' . implode( ', ', $skipped ) . '.';
	}
	atfa_aws_admin_done( 'requests', $msg, $skipped ? 'error' : 'success' );
} );

/* ------------------------------------------------------------------ Settings */

function atfa_aws_admin_settings( $s ) {
	?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="aws-sec">
		<input type="hidden" name="action" value="atfa_aws_save_settings">
		<?php wp_nonce_field( 'atfa_aws_save_settings' ); ?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">Who can see the page</th>
				<td>
					<label><input type="radio" name="access" value="admin" <?php checked( $s['access'], 'admin' ); ?>> Hidden for now: administrators only. Staff, members with a code and visitors are sent to the awards page.</label><br>
					<label><input type="radio" name="access" value="codes" <?php checked( $s['access'], 'codes' ); ?>> Platinum first look: Platinum Members with their access code, and administrators. Staff and visitors get the code screen.</label><br>
					<label><input type="radio" name="access" value="public" <?php checked( $s['access'], 'public' ); ?>> Everyone (no code needed). Also puts a "Sponsor a category" link on the awards pages.</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="aws-from">Categories go on the ATFA website</label></th>
				<td><input type="text" id="aws-from" name="public_from" class="regular-text" value="<?php echo esc_attr( $s['public_from'] ); ?>" placeholder="Monday 9 November">
					<p class="description">Optional. Shown on the page and in the invitation text: "before they are listed on the ATFA website on ...".</p></td>
			</tr>
			<tr>
				<th scope="row"><label for="aws-limit">Categories per sponsor</label></th>
				<td><input type="number" id="aws-limit" name="limit" min="1" max="30" value="<?php echo (int) $s['limit']; ?>" class="small-text">
					<p class="description">How many each sponsor can ask for in this round. Phil's flyer: two in the first round, more later if categories are left.</p></td>
			</tr>
			<tr>
				<th scope="row"><label for="aws-book">EPOXY booking link</label></th>
				<td><input type="url" id="aws-book" name="book_url" class="large-text" value="<?php echo esc_attr( $s['book_url'] ); ?>" placeholder="https://epoxy.atfa.com.au/sponsorship">
					<p class="description">Leave empty while bookings are taken by hand: the page has a request form that emails the address below. Once the categories are on EPOXY, put the link here and the page sends people there instead.</p></td>
			</tr>
			<tr>
				<th scope="row"><label for="aws-to">Requests go to</label></th>
				<td><input type="email" id="aws-to" name="to" class="regular-text" value="<?php echo esc_attr( $s['to'] ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="aws-cc">Copy to</label></th>
				<td><input type="text" id="aws-cc" name="cc" class="regular-text" value="<?php echo esc_attr( $s['cc'] ); ?>">
					<p class="description">Optional. Separate more than one address with commas.</p></td>
			</tr>
		</table>
		<p><button type="submit" class="button button-primary">Save settings</button></p>
	</form>
	<?php
}

add_action( 'admin_post_atfa_aws_save_settings', function () {
	atfa_aws_admin_check( 'atfa_aws_save_settings' );
	$old = atfa_aws_settings();
	// phpcs:disable WordPress.Security.NonceVerification
	$to  = isset( $_POST['to'] ) ? sanitize_email( wp_unslash( $_POST['to'] ) ) : '';
	$cc  = array();
	foreach ( explode( ',', isset( $_POST['cc'] ) ? (string) wp_unslash( $_POST['cc'] ) : '' ) as $addr ) {
		$addr = sanitize_email( trim( $addr ) );
		if ( $addr && is_email( $addr ) ) {
			$cc[] = $addr;
		}
	}
	$new = array(
		'access'      => ( isset( $_POST['access'] ) && in_array( $_POST['access'], array( 'admin', 'codes', 'public' ), true ) ) ? sanitize_key( $_POST['access'] ) : 'admin',
		'public_from' => isset( $_POST['public_from'] ) ? atfa_aws_text( wp_unslash( $_POST['public_from'] ), 60 ) : '',
		'limit'       => isset( $_POST['limit'] ) ? max( 1, min( 30, (int) $_POST['limit'] ) ) : 2,
		'book_url'    => isset( $_POST['book_url'] ) ? esc_url_raw( trim( (string) wp_unslash( $_POST['book_url'] ) ) ) : '',
		'to'          => ( $to && is_email( $to ) ) ? $to : $old['to'],
		'cc'          => implode( ', ', $cc ),
	);
	// phpcs:enable
	update_option( ATFA_AWS_SETTINGS, $new );
	// The awards pages carry the "Sponsor a category" link once the page is open to everyone: clear WP Rocket's
	// cached copies so visitors get it (or lose it) straight away.
	if ( $new['access'] !== $old['access'] && function_exists( 'rocket_clean_domain' ) ) {
		rocket_clean_domain();
	}
	$said = array(
		'admin'  => ' The page is hidden: administrators only.',
		'codes'  => ' The page is live for the Platinum first look: Platinum Members with their code, and administrators.',
		'public' => ' The page is now open to everyone.',
	);
	if ( 'public' !== $new['access'] && ! current_user_can( 'manage_options' ) ) {
		wp_safe_redirect( admin_url() );
		exit;
	}
	atfa_aws_admin_done( 'settings', 'Saved.' . ( $new['access'] !== $old['access'] ? $said[ $new['access'] ] : '' ) );
} );
