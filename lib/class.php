<?php

class WPBackupZip
{
    /**
     * @var ZipArchive
     */
    protected $zip;
    protected $root = '';
    protected $ignored_names;

    function __construct($file, $folder, $ignored=null)
    {
        $this->zip = new ZipArchive();
        $this->ignored_names = is_array($ignored) ? $ignored : $ignored ? array($ignored) : array();

        if ($this->zip->open($file, ZIPARCHIVE::CREATE) !== TRUE) {
            throw new Exception("cannot open <$file>\n");
        }

        $folder = $this->getFolder($folder);
        $this->zip($folder);
        $this->zip->close();
    }

    protected function zip($folder, $parent=null)
    {
        $full_path = $this->root . $parent . $folder;
        $zip_path = $parent . $folder;
        $this->zip->addEmptyDir($zip_path);
        $dir = new DirectoryIterator($full_path);
        foreach ($dir as $file) {
            if (!$file->isDot()) {
                $filename = $file->getFilename();
                if (!in_array($filename, $this->ignored_names)) {
                    if ($file->isDir()) {
                        $this->zip($filename, $zip_path . '/');
                    } else {
                        $this->zip->addFile($full_path . '/' . $filename, $zip_path . '/' . $filename);
                    }
                }
            }
        }
    }

    protected function getFolder($folder)
    {
        $folder = rtrim($folder, "\\/");
        if (strstr($folder, '/')) {
            $this->root = substr($folder, 0, strrpos($folder, '/') + 1);
            $folder = substr($folder, strrpos($folder, '/') + 1);
        }

        return $folder;
    }
}