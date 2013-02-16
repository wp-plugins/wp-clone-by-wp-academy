<script type="text/javascript">
	
	jQuery(function($) {
	
        $(".copy-button").zclip({
            path: "<?php echo WPCLONE_URL_PLUGIN ?>lib/js/ZeroClipboard.swf",
            copy: function(){
                return $(this).prev().val();
            }
        });
        $('a#copy-description').zclip({
            path:'<?php echo WPCLONE_URL_PLUGIN ?>lib/js/ZeroClipboard.swf',
            copy:$('p#description').text()
        });

        $('a#copy-dynamic').zclip({
            path:'<?php echo WPCLONE_URL_PLUGIN ?>lib/js/ZeroClipboard.swf',
            copy:function(){
                return $('input#dynamic').val();
            }
        });

    });
	
</script>

<?php
if (wpa_wpfs_init()) return;
global $wpdb;

$result = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wpclone ORDER BY id DESC", ARRAY_A);

?>
<div id="wrapper">
<div id="MainView">

    <h2>Welcome to WP Clone by WP Academy</h2>

    <p>You can use this tool to create a backup of this site and (optionally) restore it to another server, or another WordPress installation on the same server.</p>

    <p><strong>Here is how it works:</strong> the "Backup" function will give you a URL that you can then copy and paste
        into the "Restore" dialog of a new WordPress site, which will clone the original site to the new site. You must
        install the plugin on the new site and then run the WP Clone > Restore function.</p>

    <p><strong>Choose your selection below:</strong> either create a backup of this site, or choose which backup you
        would like to restore.</p>

    <p>&nbsp;</p>

    <form id="backupForm" name="backupForm" action="#" method="post">
<?php 
    if ( isset($_GET['mode']) && 'advanced' == $_GET['mode'] ) { ?>
        <div class="info">
            <table>
                <tr align="left"><th><label for="zipmode">Alternate zip method</label></th><td colspan="2"><input type="checkbox" name="zipmode" value="alt" /></td></tr>
                <tr align="left"><th><label for="maxmem">Maximum memory limit</label></th><td colspan="2"><input type="text" name="maxmem" /></td></tr>
                <tr align="left"><th><label for="maxexec">Script execution time</label></th><td><input type="text" name="maxexec" /></td></tr>
            </table>
        </div>
<?php
}
?>        
        <strong>Create Backup</strong>
        <input id="createBackup" name="createBackup" type="radio" value="fullBackup"/><br/><br/>

        <?php if (count($result) > 0) : ?>

        <div class="try">

            <?php foreach ($result AS $row) :

            $filename = convertPathIntoUrl(WPCLONE_DIR_BACKUP . $row['backup_name']);
			$url = wp_nonce_url( get_bloginfo('wpurl') . '/wp-admin/options-general.php?page=wp-clone&del=' . $row['id'], 'wpclone-submit');
			?>
            <div class="restore-backup-options">
                <strong>Restore backup </strong>

                <input class="restoreBackup" name="restoreBackup" type="radio"
                       value="<?php echo $filename ?>" />&nbsp;

                <a href="<?php echo $filename ?>">
                    (&nbsp;<?php echo bytesToSize($row['backup_size']);?>&nbsp;)&nbsp; <?php echo $row['backup_name'] ?>
                </a>&nbsp;|&nbsp;

                <input type="hidden" name="backup_name" value="<?php echo $filename ?>" />

                <a class="copy-button" href="#">Copy URL</a> &nbsp;|&nbsp;
                <a href="<?php echo $url; ?>">Delete</a>
            </div>

            <?php endforeach ?>

        </div>

        <?php endif ?>

        <strong>Restore from URL:</strong><input id="backupUrl" name="backupUrl" type="radio" value="backupUrl"/>

        <input type="text" name="restore_from_url" class="Url" value="" size="80px"/><br/><br/>

        <div class="RestoreOptions" id="RestoreOptions">

            <input type="checkbox" name="approve" id="approve" /> I AGREE (Required for "Restore" function):<br/>

            1. You have nothing of value in your current site <strong>[<?php echo site_url() ?>]</strong><br/>

            2. Your current site at <strong>[<?php echo site_url() ?>]</strong> may become unusable in case of failure,
            and you will need to re-install WordPress<br/>

            <?php

            require_once(WPCLONE_ROOT . "wp-config.php");

            $dbInfo = getDbInfo(get_defined_vars());

            ?>

            3. Your WordPress database <strong>[<?php if (isset($dbInfo['dbname'])) {
            echo $dbInfo['dbname'];
        }?>]</strong> will be overwritten from the database in the backup file. <br/>

        </div>

        <input id="submit" name="submit" class="btn-primary btn" type="submit" value="Create Backup"/>
        <?php wp_nonce_field('wpclone-submit')?>
    </form>
</div>
<div id="sidebar">

		<ul>
			<h2>WP Academy Resources</h2>
			<li><a href="http://wpacademy.tv/ww3.htm" title="WP Encyclopedia">Free WP Encyclopedia</a></li>
			<li><a href="http://wpacademy.com" title="WP Live">WP Live</a></li>
			<li><a href="http://wpacademy.com/software#rtmp" title="RTMP Player Plugin">RTMP Player</a></li>
			<li><a href="http://wpacademy.tv/host" title="Managed WordPress Hosting">Managed WordPress Hosting</a></li>
		</ul>
		
		<ul>
			<h2>WP Academy News</h2>
			<h3>WPAcademy.com</h3>			
			<?php wpa_fetch_feed ('http://wpacademy.com/feed', 3); ?>
			<h3>Twitter @wpacademytv</h3>			
			<?php wpa_fetch_feed ('http://api.twitter.com/1/statuses/user_timeline.rss?screen_name=wpacademytv', 5); ?>
		</ul>
		
		<ul>
			<h2>Help & Support</h2>
			<li><a href="http://wpacademy.tv/wpclone-faq" title="WP Clone FAQ">Visit the WP Clone FAQ Page</a></li>
			<li><a href="http://wordpress.org/support/plugin/wp-clone-by-wp-academy" title="Support Forum">Support forum at WordPress.org</a></li>
		</ul>
    
	</div>
</div> <!--wrapper-->
<p style="clear: both;" ></p>
<?php
    if ( isset($_GET['mode']) && 'advanced' == $_GET['mode'] ) {
        global $wpdb;
        echo '<div class="info">';
        echo '<h3>System Info:</h3>';
        echo 'Memory limit: ' . ini_get('memory_limit') . '</br>';
        echo 'Maximum execution time: ' . ini_get('max_execution_time') . ' seconds</br>';
        echo 'PHP version : ' . phpversion() . '</br>';
        echo 'MySQL version : ' . $wpdb->db_version() . '</br>';
        if (ini_get('safe_mode')) { echo '<span style="color:#f11">PHP is running in safemode!</span></br>'; }
        echo '<h4>Directory list:</h4>';
        echo 'Uploads path : <pre>' . WPCLONE_DIR_UPLOADS . '</pre></br>';
        echo 'Plugin path : <pre>' . WPCLONE_DIR_PLUGIN . '</pre></br>';
        echo 'Plugin URL : <pre>' . WPCLONE_URL_PLUGIN . '</pre></br>';
        echo 'Backup path : <pre>' . WPCLONE_DIR_BACKUP . '</pre></br>';
        echo 'wp-content path : <pre>' . WPCLONE_WP_CONTENT . '</pre></br>';
        echo 'Site Root : <pre>' . WPCLONE_ROOT . '</pre></br>';
        if (!is_writable(WPCLONE_DIR_BACKUP)) { echo '<span style="color:#f11">Cannot write to the backup directory!</span></br>'; }
        if (!is_writable(WPCLONE_ROOT)) { echo '<span style="color:#f11">Cannot write to the root directory!</span></br>'; }
        echo '</div>';
    }

	if(!isset($_GET['mode'])){
    $link = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=wp-clone&mode=advanced';
    echo "<p style='padding:5px;'><a href='{$link}' style='margin-top:10px'>Advanced Settings</a></p>";
    }

	function wpa_fetch_feed ($feed, $limit) {
		include_once(ABSPATH . WPINC . '/feed.php');
		$rss = fetch_feed($feed);
		if (!is_wp_error( $rss ) ) :
			$maxitems = $rss->get_item_quantity($limit);
			$rss_items = $rss->get_items(0, $maxitems); 
		endif;
		if ($maxitems == 0) echo '<li>No items.</li>';
		else
		// Loop through each feed item and display each item as a hyperlink.
		foreach ( $rss_items as $item ) : ?>
		<li>
			<a href='<?php echo esc_url( $item->get_permalink() ); ?>'
			title='<?php echo 'Posted '.$item->get_date('j F Y | g:i a'); ?>'>
			<?php echo esc_html( $item->get_title() ); ?></a>
		</li>
		<?php endforeach;
	}
	
// it all ends here folks.