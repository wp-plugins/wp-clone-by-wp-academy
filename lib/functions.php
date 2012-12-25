<?phpfunction getBackupFileName() {    $backupName = date('Y-m-d-H-i-s') . '_' . get_bloginfo('name', 'display');    $backupName = substr(str_replace(' ', '', $backupName), 0, 40);    $backupName = sanitize_file_name($backupName);    return $backupName;}function wpCloneSafePathMode($path) {    return str_replace("\\", "/", $path);}function wpCloneDirectory($path) {    return rtrim(str_replace("//", "/", wpCloneSafePathMode($path)), '/') . '/';}function convertPathIntoUrl($path) {    return str_replace(rtrim(WPCLONE_ROOT, "/\\"), site_url(), $path);}function convertUrlIntoPath($url) {    return str_replace(site_url(), rtrim(WPCLONE_ROOT, "/\\"), $url);}function wpBackupFullCopy($source, $target) {    if (is_readable($source)) {        if (is_dir($source)) {            if (!strstr(wpCloneSafePathMode($source), rtrim(WPCLONE_DIR_BACKUP, "/\\"))) {                if (!file_exists($target)) {                    mkdir($target, WPBACKUP_FILE_PERMISSION);                }                $d = dir($source);                while (FALSE !== ($entry = $d->read())) {                    if ($entry == '.' || $entry == '..') {                        continue;                    }                    $Entry = "{$source}/{$entry}";                    if (is_dir($Entry)) {                        wpBackupFullCopy($Entry, $target . '/' . $entry);                    } else {                        @ copy($Entry, $target . '/' . $entry);                    }                }                $d->close();            }        } else {            copy($source, $target);        }    }}function CreateDb($destination){    global $wpdb;    $WPCLONE_DB_ICONV_IN = "UTF-8";    $WPCLONE_DB_ICONV_OUT = "ISO-8859-1//TRANSLIT";    $return = '';    // Get all of the tables    $tables = $wpdb->get_col('SHOW TABLES');    // Cycle through each provided table    foreach ($tables as $table) {        // First part of the output � remove the table        $result = $wpdb->get_results("SELECT * FROM {$table}", ARRAY_N);        $numberOfFields = count($result[0]);        $numberOfItems = count($result);        // Second part of the output � create table        $row2 = $wpdb->get_row("SHOW CREATE TABLE {$table}", ARRAY_N);        $return .= "\n\n" . $row2[1] . ";\n\n";        // Third part of the output � insert values into new table        for ($currentRowNumber = 0; $currentRowNumber < $numberOfItems; $currentRowNumber++) {            $row = $result[$currentRowNumber];            $query = "INSERT INTO {$table} VALUES(";            for ($j = 0; $j < $numberOfFields; $j++) {                $row[$j] = iconv($WPCLONE_DB_ICONV_IN, $WPCLONE_DB_ICONV_OUT, $row[$j]);                $query .= (empty($row[$j])) ? '"", ' : '"' . mysql_real_escape_string($row[$j]) . '", ';            }            $return .= substr($query, 0, -2) .  ");\n";        }        $return .= "\n";    }    // Generate the filename for the sql file    $File_open = fopen($destination . '/database.sql', 'w+');    // Save the sql file    fwrite($File_open, $return);    //file close    fclose($File_open);    $wpdb->flush();}function InsertData($name, $size){    global $wpdb;    global $current_user;    $wpdb->insert($wpdb->prefix . "wpclone", array(        'backup_name' => $name,        'data_time' => current_time('mysql', get_option('gmt_offset')),        'creator' => $current_user->user_login,        'backup_size' => $size)    );    $wpdb->flush;}function CreateWPFullBackupZip($backupName, $zipmode){    $folderToBeZipped = WPCLONE_DIR_BACKUP . $backupName;    $destinationPath = $folderToBeZipped . '/' . basename(WPCLONE_WP_CONTENT);    $zipFileName = $backupName . '.zip';    DirectoryTree::createDirectory($destinationPath);    wpBackupFullCopy(rtrim(WPCLONE_WP_CONTENT, "/\\"), $destinationPath);//    wpBackupFullCopy(WPCLONE_ROOT . 'wp-config.php', $folderToBeZipped . '/wp-config.php');    wpa_save_prefix($folderToBeZipped);//    CreateDb($folderToBeZipped);    dw_backup_tables(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, $folderToBeZipped);//    new WPbackupZip("{$folderToBeZipped}.zip", $folderToBeZipped, '.svn');    wpa_zip("{$folderToBeZipped}.zip", $folderToBeZipped, $zipmode);    $zipSize = filesize("{$folderToBeZipped}.zip");    DirectoryTree::DeleteAllDirectoryFiles($folderToBeZipped, true);    return array($zipFileName, $zipSize);}function CreateWPCustomBackupZip($backupName, $directoryFiles){    $backupDirectory = rtrim(WPCLONE_DIR_BACKUP, "/\\");    $key = array_search($backupDirectory, $directoryFiles);    if ($directoryFiles[$key] === $backupDirectory) {        unset($directoryFiles[$key]);    }    $destinationPath = WPCLONE_DIR_BACKUP . $backupName;    $zipFileName = $backupName . '.zip';    mkdir($destinationPath, WPBACKUP_FILE_PERMISSION);    foreach($directoryFiles AS $directoryFolder) {        $destinationFolder = str_replace(rtrim(WPCLONE_ROOT, "/\\"), $destinationPath, $directoryFolder);        wpBackupFullCopy($directoryFolder, $destinationFolder);    }    CreateDb($destinationPath);//    new WPbackupZip("{$destinationPath}.zip", $destinationPath, '.svn');    wpa_zip("{$destinationPath}.zip", $destinationPath);    $zipSize = filesize("{$destinationPath}.zip");    DirectoryTree::DeleteAllDirectoryFiles($destinationPath, true);    return array($zipFileName, $zipSize);}function CreateWPClonePluginBackupZip($backupName, $zipFileName){    $installerBackupFile = "{$backupName}_wpclone";    $installerBackupPath = WPCLONE_DIR_BACKUP . $installerBackupFile;    $installerBackupFileZip = $installerBackupFile . '.zip';    wpBackupFullCopy(rtrim(WPCLONE_INSTALLER_PATH, "/\\"), $installerBackupPath);    $editBackupFilePath = $installerBackupPath . "/lib/file";    $backupZipPath = convertPathIntoUrl(WPCLONE_DIR_BACKUP . $zipFileName);    if (file_exists($editBackupFilePath)) {        $search = 'class="Url" value=""';        $replace = 'class="Url" value="' . $backupZipPath . '"';        chdir($editBackupFilePath);        DirectoryTree::openFileSearchAndReplace($editBackupFilePath, $search, $replace);        !file_exists($installerBackupPath . '/lib/view.php') || unlink($installerBackupPath . '/lib/view.php');        $copyFrom = $editBackupFilePath . '/view.php';        $copyTo = $installerBackupPath . '/lib/view.php';        DirectoryTree::CopyDirectory($copyFrom, $copyTo);    }    new WPbackupZip("{$installerBackupPath}.zip", $installerBackupPath, '.svn');    DirectoryTree::DeleteAllDirectoryFiles($installerBackupPath, true);    return $installerBackupFileZip;}function unzipBackupFile($zipFilename, $destinationFolder){        $zipFileObject = new ZipArchive;    $response = true;            if ( $zipFileObject->open($zipFilename) === TRUE ) {                $zipFileObject->extractTo($destinationFolder);        /* Remove htaccess file from directory. */        $folder = pathinfo($zipFilename, PATHINFO_FILENAME);        $htaccess = wpCloneSafePathMode($destinationFolder . $folder) . '/.htaccess';        !(file_exists($htaccess)) || unlink($htaccess);                }     else {        $response = false;    }    unset($zipFileObject);    return $response;}function DeleteWPBackupZip($nm){    global $wpdb;    $wp_backup = "{$wpdb->prefix}wpclone";    $deleteRow = $wpdb->get_row("SELECT * FROM {$wp_backup} WHERE id = '{$nm}'");    $wpdb->query("DELETE FROM {$wp_backup} WHERE id = '{$nm}' ");    if (file_exists(WPCLONE_DIR_BACKUP . $deleteRow->backup_name)) unlink(WPCLONE_DIR_BACKUP . $deleteRow->backup_name) or die ('unable to delete backup file.');    if (file_exists(WPCLONE_DIR_BACKUP . $deleteRow->installer_name)) @unlink(WPCLONE_DIR_BACKUP . $deleteRow->installer_name);    return $deleteRow;}function bytesToSize($bytes, $precision = 2){    $kilobyte = 1024;    $megabyte = $kilobyte * 1024;    $gigabyte = $megabyte * 1024;    $terabyte = $gigabyte * 1024;    if (($bytes >= 0) && ($bytes < $kilobyte)) {        return $bytes . ' B';    } elseif (($bytes >= $kilobyte) && ($bytes < $megabyte)) {        return round($bytes / $kilobyte, $precision) . ' KB';    } elseif (($bytes >= $megabyte) && ($bytes < $gigabyte)) {        return round($bytes / $megabyte, $precision) . ' MB';    } elseif (($bytes >= $gigabyte) && ($bytes < $terabyte)) {        return round($bytes / $gigabyte, $precision) . ' GB';    } elseif ($bytes >= $terabyte) {        return round($bytes / $terabyte, $precision) . ' TB';    } else {        return $bytes . ' B';    }}function remove_extension($name){    $ext = strrchr($name, '.');    if ($ext !== false) {        $name = substr($name, 0, -strlen($ext));    }    return $name;}if (!function_exists('file_put_contents')){    function file_put_contents($filename, $data)    {        $f = @fopen($filename, 'w');        if (!$f) {            return false;        } else {            $bytes = fwrite($f, $data);            fclose($f);            return $bytes;        }    }}/** Returns the contents of file name passed * * @param string $filename * @return string */function get_file_contents($filename){    if (!function_exists('file_get_contents')) {        $handle = fopen($filename, "r");        $contents = fread($handle, filesize($filename));        fclose($handle);    } else {        $contents = file_get_contents($filename);    }    return $contents;}function getDbInfo(){    $dbInfo = array();    $dbInfo["dbhost"] = DB_HOST;    $dbInfo["dbname"] = DB_NAME;    $dbInfo["dbuser"] = DB_USER;    $dbInfo["dbpassword"] = DB_PASSWORD;    return $dbInfo;}function getNumberOfTablesInDatabase($databaseName, $dbConn = null){    $selectQuery = "SELECT COUNT(*) AS number_of_tables                    FROM information_schema.tables                    WHERE table_schema = '{$databaseName}'";    $numOfTables = mysql_query($selectQuery, $dbConn);    $numOfTables = mysql_fetch_assoc($numOfTables);    return $numOfTables;}function replaceTablePrefixInConfigFile($currentConfigFile, $configInZipFile){    $backupFileVariables = getVariablesFromFile($configInZipFile);    $backupPrefix = $backupFileVariables["table_prefix"];    replaceTablePrefix($currentConfigFile, $backupPrefix);    return $backupPrefix;}function getVariablesFromFile($filename){    ob_start();    include($filename);    ob_end_clean();    return get_defined_vars();}function replaceTablePrefix($filename, $newPrefix){    $fileContent = file_get_contents($filename);    $pos = strpos($fileContent, '$table_prefix');    $str = substr($fileContent, $pos, strpos($fileContent, PHP_EOL, $pos) - $pos);    $fileContent = str_replace($str, '$table_prefix = "' . $newPrefix . '";', $fileContent);    file_put_contents($filename, $fileContent);}function replaceSiteUrlFromDatabaseFile($databaseFile){    $fileContent = file_get_contents($databaseFile, true);    $pos = strpos($fileContent, 'siteurl') + 8;    $urlStartPos = strpos($fileContent, '"', $pos) + 1;    $urlEndPos = strpos($fileContent, '"', $urlStartPos);    $backupSiteUrl = substr($fileContent, $urlStartPos, $urlEndPos - $urlStartPos);    return $backupSiteUrl;}function processConfigAndDatabaseFile($databaseFileInZip){    /* Replacing backup site url with the current one. */    $backupSiteUrl =  replaceSiteUrlFromDatabaseFile($databaseFileInZip);    $currentSiteUrl = site_url();    $backupSiteUrl = wpa_trailing_slash_check($backupSiteUrl);    $currentSiteUrl = wpa_trailing_slash_check($currentSiteUrl);    $dbInfo = getDbInfo();    $conn = mysql_connect($dbInfo['dbhost'], $dbInfo['dbuser'], $dbInfo['dbpassword']);    mysql_select_db($dbInfo['dbname'], $conn) or die(mysql_error());    $query = mysql_query("SHOW TABLES", $conn);    while (($fetch = mysql_fetch_array($query))) {        mysql_query("Drop table `{$fetch[0]}`") or die(mysql_error() . '<br> Acess denied');    }    $dbFileContent = file_get_contents($databaseFileInZip);//    $dbFileContent = str_ireplace("\xA0", " ", $dbFileContent);    $res = explode(";\n", $dbFileContent);    flush();    foreach ($res AS $query) {        mysql_query($query, $conn);    }    wpa_safe_replace_wrapper ( $dbInfo['dbhost'], $dbInfo['dbuser'], $dbInfo['dbpassword'], $dbInfo['dbname'], $backupSiteUrl, $currentSiteUrl );    mysql_close($conn);    return $currentSiteUrl;}/** * * @param type $host mysql host name,already defined in wp-config.php as DB_HOST. * @param type $user mysql username,                                  "" DB_USER. * @param type $pass mysql password,                                  "" DB_PASSWORD. * @param type $data mysql database name,                             "" DB_NAME. * @param type $search URL of the previous site. * @param type $replace URL of the current site. * @return type total time it took for the operation. */function wpa_safe_replace_wrapper ( $host, $user, $pass, $data, $search, $replace ) {    $connection = @mysql_connect( $host, $user, $pass );    $all_tables = array( );    @mysql_select_db( $data, $connection );    $all_tables_mysql = @mysql_query( 'SHOW TABLES', $connection );    if ( ! $all_tables_mysql ) {            $errors[] = mysql_error( );    } else {            while ( $table = mysql_fetch_array( $all_tables_mysql ) ) {                    $all_tables[] = $table[ 0 ];            }    }    $report = icit_srdb_replacer( $connection, $search, $replace, $all_tables );    return $report;}		/** * Take a serialised array and unserialise it replacing elements as needed and * unserialising any subordinate arrays and performing the replace on those too. * * @param string $from       String we're looking to replace. * @param string $to         What we want it to be replaced with * @param array  $data       Used to pass any subordinate arrays back to in. * @param bool   $serialised Does the array passed via $data need serialising. * * @return array	The original array with all elements replaced as needed. */function recursive_unserialize_replace( $from = '', $to = '', $data = '', $serialised = false ) {	// some unseriliased data cannot be re-serialised eg. SimpleXMLElements	try {		if ( is_string( $data ) && ( $unserialized = @unserialize( $data ) ) !== false ) {			$data = recursive_unserialize_replace( $from, $to, $unserialized, true );		}		elseif ( is_array( $data ) ) {			$_tmp = array( );			foreach ( $data as $key => $value ) {				$_tmp[ $key ] = recursive_unserialize_replace( $from, $to, $value, false );			}			$data = $_tmp;			unset( $_tmp );		}		else {			if ( is_string( $data ) )				$data = str_replace( $from, $to, $data );		}		if ( $serialised )			return serialize( $data );	} catch( Exception $error ) {	}	return $data;}/** * The main loop triggered in step 5. Up here to keep it out of the way of the * HTML. This walks every table in the db that was selected in step 3 and then * walks every row and column replacing all occurences of a string with another. * We split large tables into 50,000 row blocks when dealing with them to save * on memmory consumption. * * @param mysql  $connection The db connection object * @param string $search     What we want to replace * @param string $replace    What we want to replace it with. * @param array  $tables     The tables we want to look at. * * @return array    Collection of information gathered during the run. */function icit_srdb_replacer( $connection, $search = '', $replace = '', $tables = array( ) ) {	global $guid, $exclude_cols;	$report = array( 'tables' => 0,					 'rows' => 0,					 'change' => 0,					 'updates' => 0,					 'start' => microtime( ),					 'end' => microtime( ),					 'errors' => array( ),					 );	if ( is_array( $tables ) && ! empty( $tables ) ) {		foreach( $tables as $table ) {			$report[ 'tables' ]++;			$columns = array( );			// Get a list of columns in this table		    $fields = mysql_query( 'DESCRIBE ' . $table, $connection );			while( $column = mysql_fetch_array( $fields ) )				$columns[ $column[ 'Field' ] ] = $column[ 'Key' ] == 'PRI' ? true : false;			// Count the number of rows we have in the table if large we'll split into blocks, This is a mod from Simon Wheatley			$row_count = mysql_query( 'SELECT COUNT(*) FROM ' . $table, $connection );			$rows_result = mysql_fetch_array( $row_count );			$row_count = $rows_result[ 0 ];			if ( $row_count == 0 )				continue;			$page_size = 50000;			$pages = ceil( $row_count / $page_size );			for( $page = 0; $page < $pages; $page++ ) {				$current_row = 0;				$start = $page * $page_size;				$end = $start + $page_size;				// Grab the content of the table				$data = mysql_query( sprintf( 'SELECT * FROM %s LIMIT %d, %d', $table, $start, $end ), $connection );				if ( ! $data )					$report[ 'errors' ][] = mysql_error( );				while ( $row = mysql_fetch_array( $data ) ) {					$report[ 'rows' ]++; // Increment the row counter					$current_row++;					$update_sql = array( );					$where_sql = array( );					$upd = false;					foreach( $columns as $column => $primary_key ) {						if ( $guid == 1 && in_array( $column, $exclude_cols ) )							continue;						$edited_data = $data_to_fix = $row[ $column ];						// Run a search replace on the data that'll respect the serialisation.						$edited_data = recursive_unserialize_replace( $search, $replace, $data_to_fix );						// Something was changed						if ( $edited_data != $data_to_fix ) {							$report[ 'change' ]++;							$update_sql[] = $column . ' = "' . mysql_real_escape_string( $edited_data ) . '"';							$upd = true;						}						if ( $primary_key )							$where_sql[] = $column . ' = "' . mysql_real_escape_string( $data_to_fix ) . '"';					}					if ( $upd && ! empty( $where_sql ) ) {						$sql = 'UPDATE ' . $table . ' SET ' . implode( ', ', $update_sql ) . ' WHERE ' . implode( ' AND ', array_filter( $where_sql ) );						$result = mysql_query( $sql, $connection );						if ( ! $result )							$report[ 'errors' ][] = mysql_error( );						else							$report[ 'updates' ]++;					} elseif ( $upd ) {						$report[ 'errors' ][] = sprintf( '"%s" has no primary key, manual change needed on row %s.', $table, $current_row );					}				}			}		}	}	$report[ 'end' ] = microtime( );	return $report;}function processRestoringBackup($url, $zipmode) {    if (!is_string($url) || '' == $url) die('The provided path is not valid,aborting..');        $pathParts = pathinfo($url);    $urlDir = WPCLONE_DIR_BACKUP . 'url/';    file_exists($urlDir) || mkdir($urlDir, WPBACKUP_FILE_PERMISSION);            /* Copy the file found from url to plugin root */    $zipFilename = $urlDir . $pathParts['basename'];    DirectoryTree::CopyDirectory($url, $zipFilename);//    $result = unzipBackupFile($zipFilename, WPCLONE_ROOT);    $result = wpa_unzip($zipFilename, WPCLONE_ROOT, $zipmode);    if ($result) {        $unzippedFolderPath = wpCloneSafePathMode(WPCLONE_ROOT . $pathParts['filename']);//        $configFileInZip = $unzippedFolderPath . '/wp-config.php';        $old_db_prefix = $unzippedFolderPath . '/prefix.txt';                $prefix = wpa_check_prefix($old_db_prefix);        $databaseFile = $unzippedFolderPath . '/database.sql';        if ($prefix) {            wpa_replace_prefix( ABSPATH . 'wp-config.php', $prefix );        }                $currentSiteUrl = processConfigAndDatabaseFile($databaseFile);        !file_exists($databaseFile) || unlink($databaseFile);        !file_exists($old_db_prefix) || unlink($old_db_prefix);        !file_exists($unzippedFolderPath . '/wp-config.php') || unlink($unzippedFolderPath . '/wp-config.php');//            wpBackupFullCopy($unzippedFolderPath, WPCLONE_ROOT);        wpa_copy($unzippedFolderPath, WPCLONE_ROOT);        DirectoryTree::DeleteAllDirectoryFiles($unzippedFolderPath, true);        echo "<h1>Restore Successful!</h1>";        echo "Visit your restored site [ <a href='{$currentSiteUrl}' target=blank>here</a> ]<br><br>";        echo "<strong>You may need to re-save your permalink structure <a href='{$currentSiteUrl}/wp-admin/options-permalink.php' target=blank>Here</a></strong>";    } else {        echo "<h1>Restore unsuccessful!!!</h1>";        echo "Please try again.";    }    !file_exists($urlDir) || DirectoryTree::DeleteAllDirectoryFiles($urlDir, true);}function wpa_save_prefix($path) {    global $wpdb;    $prefix = $wpdb->prefix;    $file = $path . '/prefix.txt';    if ( is_dir($path) && is_writable($path) ) {        file_put_contents($file, $prefix);    }}/** * Checks to see whether the current destination site's table prefix matches that of the origin site.old prefix is returned in case of a mismatch. * * @param type $file path to the prefix.txt file. * @return type bool string */function wpa_check_prefix($file) {    global $wpdb;    $prefix = $wpdb->prefix;    if (file_exists($file) && is_readable($file)) {        $old_prefix = file_get_contents($file);        if ( $prefix !== $old_prefix ) {            return $old_prefix;        }        else {            return false;        }    }    return false;}/** * checks for a trailing slash at the end of the provided URL and strips it if found. * @param type $url * @return type  */function wpa_trailing_slash_check($url) {    if (substr($url, -1) == "/" ) {        $url = rtrim($url, "/");        return $url;            }    else {        return $url;    }    }/** * @link http://davidwalsh.name/backup-mysql-database-php *  * @param type $host * @param type $user * @param type $pass * @param type $name * @param type $tables  */function dw_backup_tables($host,$user,$pass,$name,$destination,$tables = '*'){		$link = mysql_connect($host,$user,$pass);	mysql_select_db($name,$link);		//get all of the tables	if($tables == '*')	{		$tables = array();		$result = mysql_query('SHOW TABLES');		while($row = mysql_fetch_row($result))		{			$tables[] = $row[0];		}	}	else	{		$tables = is_array($tables) ? $tables : explode(',',$tables);	}		//cycle through	foreach($tables as $table)	{		$result = mysql_query('SELECT * FROM '.$table);		$num_fields = mysql_num_fields($result);				$return.= 'DROP TABLE '.$table.';';		$row2 = mysql_fetch_row(mysql_query('SHOW CREATE TABLE '.$table));		$return.= "\n\n".$row2[1].";\n\n";				for ($i = 0; $i < $num_fields; $i++) 		{			while($row = mysql_fetch_row($result))			{				$return.= 'INSERT INTO '.$table.' VALUES(';				for($j=0; $j<$num_fields; $j++) 				{					$row[$j] = addslashes($row[$j]);					$row[$j] = ereg_replace("\n","\\n",$row[$j]);					if (isset($row[$j])) { $return.= '"'.$row[$j].'"' ; } else { $return.= '""'; }					if ($j<($num_fields-1)) { $return.= ','; }				}				$return.= ");\n";			}		}		$return.="\n\n\n";	}		//save file	$handle = fopen($destination . '/database.sql','w+');	fwrite($handle,$return);	fclose($handle);}/** * @since 2.0.6 *  * @param type $zipfile path to the zip file that needs to be extracted. * @param type $path the place to where the file needs to be extracted. * @return as false in the event of failure. */function wpa_unzip($zipfile, $path, $zipmode = false){    if ( $zipmode || (!in_array('ZipArchive', get_declared_classes()) || !class_exists('ZipArchive')) ) {        define('PCLZIP_TEMPORARY_DIR', WPCLONE_DIR_BACKUP);        require_once ( ABSPATH . 'wp-admin/includes/class-pclzip.php' );        $z = new PclZip($zipfile);        if ($z->extract(PCLZIP_OPT_PATH, $path) == 0) {            return false;        }        echo 'PclZip';        return true;    }    else {        $r = unzipBackupFile($zipfile, $path);        return $r;    }}/** * @since 2.0.6 *  * @param type $name name of the zip file. * @param type $file_list an array of files that needs to be archived. */function wpa_zip($name, $file_list, $zipmode = false){    if ( $zipmode || (!in_array('ZipArchive', get_declared_classes()) || !class_exists('ZipArchive')) ) {        define('PCLZIP_TEMPORARY_DIR', WPCLONE_DIR_BACKUP);        require_once ( ABSPATH . 'wp-admin/includes/class-pclzip.php');        $z = new PclZip($name);        $v_list = $z->create($file_list, PCLZIP_OPT_REMOVE_PATH, WPCLONE_DIR_BACKUP);        if ($v_list == 0) {            die("Error : ".$z->errorInfo(true));        }    }    else {        new WPbackupZip($name, $file_list, '.svn');    }}/** * just a simple function to increase PHP limits. * @since 2.0.6 */function wpa_bump_limits($mem, $time){    $time = isset($time) && $time != '' ? $time : 900;    $mem = isset ($mem) && $mem != '' ? $mem . 'M' : '512M';    @ini_set('memory_limit', $mem);    @ini_set('max_execution_time', $time); //900 seconds = 15 minutes}/** * @since 2.0.6 */function wpa_wpfs_init(){    if (!empty($_REQUEST['del'])) {         wpa_remove_backup();         return true;    }    if (isset($_POST['createBackup'])) {        wpa_create_backup();        return true;    }    if (empty($_POST)) return false;    check_admin_referer('wpclone-submit');    $form_post = wp_nonce_url('admin.php?page=wp-clone');    $extra_fields = array('restore_from_url', 'maxmem', 'maxexec', 'zipmode', 'restoreBackup');    $type = '';    if ( false === ($creds = request_filesystem_credentials($form_post, $type, false, false, $extra_fields)) ){        return true;    }    if (!WP_Filesystem($creds)) {        request_filesystem_credentials($form_post, $type, true, false, $extra_fields);        return true;    }    wpa_bump_limits($_POST['maxmem'], $_POST['maxexec']);    $url = isset($_POST['restoreBackup']) ? $_POST['restoreBackup'] : $_POST['restore_from_url'];    $zipmode = isset($_POST['zipmode']) ? true : false;    processRestoringBackup($url, $zipmode);    return true;}/** * @since 2.0.6 */function wpa_copy($source, $target) {    global $wp_filesystem;    if (is_readable($source)) {        if (is_dir($source)) {            if (!strstr(wpCloneSafePathMode($source), rtrim(WPCLONE_DIR_BACKUP, "/\\"))) {                if (!file_exists($target)) {                    $wp_filesystem->mkdir($target);                }                $d = dir($source);                while (FALSE !== ($entry = $d->read())) {                    if ($entry == '.' || $entry == '..') {                        continue;                    }                    $Entry = "{$source}/{$entry}";                    if (is_dir($Entry)) {                        wpa_copy($Entry, $target . '/' . $entry);                    } else {                        $wp_filesystem->copy($Entry, $target . '/' . $entry, true, FS_CHMOD_FILE);                    }                }                $d->close();            }        }         else {            $wp_filesystem->copy($source, $target, true);        }    }}/** * @since 2.0.6 */function wpa_replace_prefix($filename, $newPrefix){    if (!is_writable($filename)) die ("Unable to modify wp-config.php,please change the permissions to '0600'.Aborting..");    global $wp_filesystem;    $fileContent = file_get_contents($filename);    $pos = strpos($fileContent, '$table_prefix');    $str = substr($fileContent, $pos, strpos($fileContent, PHP_EOL, $pos) - $pos);    $fileContent = str_replace($str, '$table_prefix = "' . $newPrefix . '";', $fileContent);    $wp_filesystem->put_contents($filename, $fileContent, 0600);}/** * @since 2.0.6 */function wpa_create_backup (){    check_admin_referer('wpclone-submit');    get_currentuserinfo();    wpa_bump_limits($_POST['maxmem'], $_POST['maxexec']);    $backupName = getBackupFileName();    $zipmode = isset($_POST['zipmode']) ? true : false;    list($zipFileName, $zipSize) = CreateWPFullBackupZip($backupName, $zipmode);    InsertData($zipFileName, $zipSize);    $backZipPath = convertPathIntoUrl(WPCLONE_DIR_BACKUP . $zipFileName);    $zipSize = bytesToSize($zipSize);    echo <<<EOF<h1>Backup Successful!</h1><br />Here is your backup file : <br />    <a href='{$backZipPath}'><span>{$backZipPath}</span></a> ( {$zipSize} ) &nbsp;&nbsp;|&nbsp;&nbsp;    <input type='hidden' name='backupUrl' class='backupUrl' value="{$backZipPath}" />    <a class='copy-button' href='#'>Copy URL</a> &nbsp;<br /><br />    (Copy that link and paste it into the "Restore URL" of your new WordPress installation to clone this site)EOF;}/** * @since 2.0.6 */function wpa_remove_backup(){	check_admin_referer('wpclone-submit');    $deleteRow = DeleteWPBackupZip($_REQUEST['del']);    echo <<<EOT        <h1>Deleted Successful!</h1> <br />        {$deleteRow->backup_name} <br />        File deleted from backup folder and database...EOT;}