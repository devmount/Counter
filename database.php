<?php

/**
 * moziloCMS additional function: Database
 *
 * offers basic database functions like load, save and append data
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_MoziloPlugins
 * @author   HPdesigner <kontakt@devmount.de>
 * @license  GPL v3
 * @version  GIT: v0.1
 * @link     none
 *
 * Plugin created by DEVMOUNT
 * www.devmount.de
 *
 */

/**
 * CounterDatabase Class
 *
 * @category PHP
 * @package  PHP_MoziloPlugins
 * @author   HPdesigner <kontakt@devmount.de>
 * @license  GPL v3
 * @link     none
 */
class CounterDatabase
{
    /**
     * saves file from multiple access
     *
     * @param string $filename file to be locked
     *
     * @return resource or false
     */
    private static function _lockFile($filename)
    {
        // check file and create it if it's not already existing
        if (!file_exists($filename)) {
            touch($filename);
        }

        $file = fopen($filename, "c+");

        // initialize number of tries
        $retries = 0;
        $max_retries = 100;

        if (!$file) {
            return false;
        }

        // keep trying to get a lock as long as possible
        do {
            if ($retries > 0) {
                usleep(rand(1, 10000));
            }
            $retries += 1;
        } while (!flock($file, LOCK_EX) and $retries <= $max_retries);

        // couldn't get the lock, give up
        if ($retries == $max_retries) {
            return false;
        }
        return $file;
    }

    /**
     * unlock given resource
     *
     * @param resource $resource file to unlock
     *
     * @return boolean success
     */
    private static function _unlockFile($resource)
    {
        flock($resource, LOCK_UN);
        fclose($resource);
        return true;
    }

    /**
     * delete a data entry
     *
     * @param int    $id       data entry id
     * @param string $filename file to delete data in
     *
     * @return boolean success
     */
    public static function deleteEntry($id,$filename)
    {
        $file = self::_lockFile($filename);
        if (!$file) {
            return false;
        }
        $data = array();
        if (filesize($filename) > 0) {
            $data = fread($file, filesize($filename));
            $data = trim(str_replace('<?php die(); ?>', '', $data));
            $data = unserialize($data);
        }

        for ($i = 0; $i < count($data); $i++) {
            $entry = $data[$i];
            if ($entry->ID == $id) {
                unset($data[$i]);
                break;
            }
        }
        $data = array_values($data);

        rewind($file);
        ftruncate($file, 0);

        fwrite($file, "<?php die(); ?>\n" . serialize($data));
        self::_unlockFile($file);

        return true;
    }

    /**
     * delete database file
     *
     * @param string $filename file to delete
     *
     * @return boolean success
     */
    public static function deletefile($filename)
    {
        if (!file_exists($filename)) {
            return false;
        }
        return unlink($filename);
    }

    /**
     * load all data entries
     *
     * @param string $filename file to load data from
     *
     * @return array data entries
     */
    public static function loadArray($filename)
    {
        if (!file_exists($filename)) {
            touch($filename);
            chmod($filename, 0777);
        }
        $data = file_get_contents($filename);
        $data = trim(str_replace('<?php die(); ?>', '', $data));
        return unserialize($data);
    }

    /**
     * save data entries
     *
     * @param string $filename file to save data in
     * @param array  $data     data entries to save
     *
     * @return boolean success
     */
    public static function saveArray($filename, $data)
    {
        $file = self::_lockFile($filename);
        if (!$file) {
            return false;
        }
        rewind($file);
        ftruncate($file, 0);

        fwrite($file, "<?php die(); ?>\n" . serialize($data));
        self::_unlockFile($file);

        return true;
    }

    /**
     * append data to existing
     *
     * @param string $filename file to save data in
     * @param array  $data     data entries to save
     *
     * @return boolean success
     */
    public static function appendArray($filename, $data)
    {
        $file = self::_lockFile($filename);
        if (!$file) {
            return false;
        }
        $newData = array();
        if (filesize($filename) > 0) {
            $newData = fread($file, filesize($filename));
            $newData = trim(str_replace('<?php die(); ?>', '', $newData));
            $newData = unserialize($newData);
        }

        $newData[] = $data;

        rewind($file);
        ftruncate($file, 0);

        fwrite($file, "<?php die(); ?>\n" . serialize($newData));
        self::_unlockFile($file);

        return true;
    }
}

?>