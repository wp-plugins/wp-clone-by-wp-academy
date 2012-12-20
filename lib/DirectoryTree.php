<?php
class DirectoryTree
{
    /**
     * Create a Directory Map
     *
     * Reads the specified directory and builds an array
     * representation of it. Sub-folders contained with the
     * directory will be mapped as well.
     *
     * @access    public
     * @param     string    path to source
     * @param     int       depth of directories to traverse (0 = fully recursive, 1 = current dir, etc)
     * @param     bool
     * @return    array
     */
    public static function getDirectoryFiles($source_dir, $directory_depth = 0, $hidden = FALSE)
    {
        if ($fp = @opendir($source_dir)) {
            $fileData = array();
            $new_depth = $directory_depth - 1;
            $source_dir = rtrim($source_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

            while (FALSE !== ($file = readdir($fp))) {
                // Remove '.', '..', and hidden files [optional]
                if (!trim($file, '.') OR ($hidden == FALSE && $file[0] == '.')) {
                    continue;
                }

                if (($directory_depth < 1 OR $new_depth > 0) && @is_dir($source_dir . $file)) {
                    $fileData[$file] = self::getDirectoryFiles($source_dir . $file . DIRECTORY_SEPARATOR, $new_depth, $hidden);
                } else {
                    $fileData[] = $file;
                }
            }

            closedir($fp);
            return $fileData;
        }

        return FALSE;
    }

    /**
     * Create a Directory Folder Map
     *
     * Reads the specified directory and builds an array of sub-folders.
     * Sub-folders contained with the directory will be mapped as well.
     *
     * @access    public
     * @param     string    path to source
     * @param     int       depth of directories to traverse (0 = fully recursive, 1 = current dir, etc)
     * @param     bool
     * @return    array
     */
    public static function getDirectoryFolders($source_dir, $directory_depth = 0, $hidden = FALSE)
    {
        if ($fp = @opendir($source_dir)) {
            $fileData = array();
            $new_depth = $directory_depth - 1;
            $source_dir = rtrim($source_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

            while (FALSE !== ($file = readdir($fp))) {
                // Remove '.', '..', and hidden files [optional]
                if (!trim($file, '.') OR ($hidden == FALSE && $file[0] == '.')) {
                    continue;
                }

                if (($directory_depth < 1 OR $new_depth > 0) && @is_dir($source_dir . $file)) {
                    $fileData[$file] = self::getDirectoryFolders($source_dir . $file . DIRECTORY_SEPARATOR, $new_depth, $hidden);
                }
            }

            closedir($fp);
            return $fileData;
        }

        return FALSE;
    }

    public static function CopyDirectory($source, $destination)
    {
        if (is_dir($source)) {

            mkdir($destination, WPBACKUP_FILE_PERMISSION);
            $directory = dir($source);

            while (FALSE !== ($readDirectory = $directory->read())) {
                if ($readDirectory == '.' || $readDirectory == '..') {
                    continue;
                }

                $PathDir = $source . '/' . $readDirectory;

                if (is_dir($PathDir)) {
                    self::CopyDirectory($PathDir, $destination . '/' . $readDirectory);
                } else {
                    copy($PathDir, $destination . '/' . $readDirectory);
                }
            }

            $directory->close();

        } else {

         @ copy($source, $destination);

        }
    }

    public static function DeleteAllDirectoryFiles($directory, $empty = false)
    {
        try {
            $iterator = new RecursiveIteratorIterator(
                            new RecursiveDirectoryIterator($directory),
                            RecursiveIteratorIterator::CHILD_FIRST);

            foreach ($iterator as $path) {
                if ($path->isDir()) {
                    @rmdir($path->__toString());
                } else {
                    @unlink($path->__toString());
                }
            }

            unset($iterator);

            if ($empty == true) {
                if (!rmdir($directory)) {
                    return false;
                }
            }
            return true;
        } catch (Exception $e) {
            echo "Not Copied..." . $e->getMessage();
        }
    }

    public static function openFileSearchAndReplace($parentDirectory, $searchFor, $replaceWith)
    {
        if (($handle = opendir($parentDirectory))) {
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != "..") {
                    if (is_dir($file)) {
                        self::openFileSearchAndReplace(wpCloneDirectory("{$parentDirectory}/{$file}"), $searchFor, $replaceWith);
                    } else {

//                        chdir("$parentDirectory"); //to make sure you are always in right directory
//                        strpos($searchFor);
                        $holdContent = file_get_contents($file);
                        $holdContent = str_replace($searchFor, $replaceWith, $holdContent);
                        file_put_contents($file, $holdContent);
                    }
                }
            }

            closedir($handle);
        }
    }

    public static function createDirectory($path)
    {
        if (file_exists($path)) {
            return;
        }

        $serverRoot = rtrim(WPCLONE_ROOT, '/') . '/';
        $directoryPath = str_replace($serverRoot, '', $path);

        $directories = explode('/', $directoryPath);

        $path = $serverRoot;
        foreach($directories AS $folder) {
            $path .= "{$folder}/";
            file_exists($path) || mkdir($path, WPBACKUP_FILE_PERMISSION);
        }
    }
}