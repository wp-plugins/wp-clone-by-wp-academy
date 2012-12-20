<script type="text/javascript">

    var folders = <?php echo json_encode(DirectoryTree::getDirectoryFolders(rtrim(WPCLONE_ROOT, "/\\"), 2)); ?>;
    var rootPath = '<?php echo dirname(rtrim(WPCLONE_ROOT, "/\\")) ?>';
    var root = '<?php echo basename(rtrim(WPCLONE_ROOT, "/\\")) ?>';

    jQuery(function($) {

        var fileTree = buildDirectoryFolderTreeWithCheckBox(folders, root, rootPath);

        fileTree.appendTo('#file_directory');

        $('.directory-component').click(function() {
            $(this).next().next().find('input[type="checkbox"]').attr('checked', $(this).is(':checked'));
            if ($(this).parent().attr('id') == 'file_directory') {
                return;
            }

            unCheckParent($(this));
            function unCheckParent(element) {
                if ($(element).parent().attr('id') == 'file_directory') {
                    return;
                }

                var parent = $(element).parent().parent().prev().prev().attr('checked', $(element).is(':checked')
                && ! ($(element).parent().siblings("li").children("li > input[type='checkbox']").not(':checked').length));

                if (parent.length) {
                    unCheckParent(parent);
                }
            }
        });

        $(".copy-button").zclip({
            path: "<?php echo WPCLONE_URL_PLUGIN ?>lib/js/ZeroClipboard.swf",
            copy: function(){
                return $(this).prev().val();
            }
        });

        $(".try pre.js").snippet("javascript",{
            style:'print',
            clipboard:'<?php echo WPCLONE_URL_PLUGIN ?>lib/js/ZeroClipboard.swf',
            collapse:'true',
            showMsg:'View Source Code',
            hideMsg:'Hide Source Code'
        });
        $("pre.js").snippet("javascript",{
            style:'print',
            clipboard:'<?php echo WPCLONE_URL_PLUGIN ?>lib/js/ZeroClipboard.swf'
        });
        $("pre.html").snippet("html",{
            style:'print',
            clipboard:'<?php echo WPCLONE_URL_PLUGIN ?>lib/js/ZeroClipboard.swf'
        });
        $("pre.css").snippet("css",{
            style:'print',
            clipboard:'<?php echo WPCLONE_URL_PLUGIN ?>lib/js/ZeroClipboard.swf'
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

        function buildDirectoryFolderTreeWithCheckBox(files, folderName, path) {

            var tree = $("<ul></ul>"), file, li;
            for (file in files) {

                if (typeof files[file] == "object") {

                    li = $('<li></li>').addClass('folder')
                                       .append(buildDirectoryFolderTreeWithCheckBox(files[file], file, path+'/'+folderName));

                }

                tree.append(li);
            }

            return $('<input />').attr({'type': 'checkbox', 'class': 'directory-component',
                                        'name': 'directory_folders[]', 'value': path+'/'+folderName})
                                 .after($('<span></span>').attr({'class': 'parent'}).html(folderName).click(function() {
                                        $(this).parent().find('ul:first').toggle();
                                    }))
                                 .after(tree.hide());
        }

    });

</script>
<?php
if (wpa_wpfs_init()) return;
global $wpdb;

$result = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wpclone ORDER BY id DESC", ARRAY_A);

?>

<div class="MainView">

    <h2>Welcome to WP Clone </h2>

    <p>You can use this tool to create a backup of this site and (optionally) restore it to another server, or another WordPress installation on the same server.</p>

    <p><strong>Here is how it works:</strong> the "Backup" function will give you a URL that you can then copy and paste
        into the "Restore" dialog of a new WordPress site, which will clone the original site to the new site. You must
        install the plugin on the new site and then run the WP Clone > Restore function.</p>

    <p><a href="http://wpacademy.tv/wpclone-faq" target="_blank">Click here</a> for help. Commercial
        license holders may also access support <a href="http://wpacademy.tv/wpclone-download" target="_blank">click
            here</a>.</p>

    <p><strong>Choose your selection below:</strong> either create a backup of this site, or choose which backup you
        would like to restore.</p>

    <p>&nbsp;</p>

    <form id="backupForm" name="backupForm" action="#" method="post">
<?php 
    if ( isset($_GET['mode']) && 'advanced' == $_GET['mode'] ) { ?>
        <div style="padding: 5px; margin: 5px; width: 530px; background: #fea;">
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
<!--        <div style="padding-left: 50px" id="backupChoices">-->
<!--            <strong>Full Backup</strong>-->
<!--            <input id="fullBackup" name="backupChoice" type="radio" value="fullBackup"/><br/>-->
<!---->
<!--<!--<!--            <strong>Custom Backup</strong> <input id="customBackup" name="backupChoice" type="radio" value="customBackup"/><br/>-->
<!--<!--<!--            <div id="file_directory"></div>-->
<!--        </div>-->

        <?php if (count($result) > 0) : ?>

        <div class="try">

            <?php foreach ($result AS $row) :

            $filename = convertPathIntoUrl(WPCLONE_DIR_BACKUP . $row['backup_name']) ?>

            <div class="restore-backup-options">
                <strong>Restore backup </strong>

                <input class="restoreBackup" name="restoreBackup" type="radio"
                       value="<?php echo $filename ?>" />&nbsp;

                <a href="<?php echo $filename ?>">
                    (&nbsp;<?php echo bytesToSize($row['backup_size']);?>&nbsp;)&nbsp; <?php echo $row['backup_name'] ?>
                </a>&nbsp;|&nbsp;

                <input type="hidden" name="backup_name" value="<?php echo $filename ?>" />

                <a class="copy-button" href="#">Copy URL</a> &nbsp;|&nbsp;
                <a href="<?php echo site_url()?>/wp-admin/options-general.php?page=wp-clone&del=<?php echo $row['id'];?>">Delete</a>
            </div>

            <?php endforeach ?>

        </div>

        <?php endif ?>

        <strong>Restore from URl:</strong><input id="backupUrl" name="backupUrl" type="radio" value="backupUrl"/>

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

        <input id="submit" name="submit" class="button-primary" type="submit" value="Create Backup"/>
        <?php wp_nonce_field('wpclone-submit')?>
    </form>

</div>

<?php
    if ( isset($_GET['mode']) && 'advanced' == $_GET['mode'] ) {
        global $wpdb;
        echo '<div style="padding: 5px; margin: 5px; width: 530px; background: #fea;">';
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

	