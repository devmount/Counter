<?php

/**
 * moziloCMS Plugin: CounterAdmin
 *
 * Shows all counter information
 * and offers administration tools like resetting counter data.
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_MoziloPlugins
 * @author   HPdesigner <kontakt@devmount.de>
 * @license  GPL v3
 * @link     https://github.com/devmount/Counter
 *
 * Plugin created by DEVMOUNT
 * www.devmount.de
 *
 */

// only allow moziloCMS administration environment
if (!defined('IS_ADMIN') or !IS_ADMIN) {
    die();
}

// instantiate CounterAdmin class
$CounterAdmin = new CounterAdmin($plugin);

// handle post input
$postresult = $CounterAdmin->checkPost();

// return admin content
return $CounterAdmin->getContentAdmin($postresult);

/**
 * CounterAdmin Class
 *
 * @category PHP
 * @package  PHP_MoziloPlugins
 * @author   HPdesigner <kontakt@devmount.de>
 * @license  GPL v3
 * @link     https://github.com/devmount/Counter
 */
class CounterAdmin extends Counter
{
    // language
    public $admin_lang;
    // plugin settings
    private $_settings;
    // PLUGIN_SELF_DIR from Counter
    private $_self_dir;
    // PLUGIN_SELF_URL from Counter
    private $_self_url;

    /**
     * constructor
     *
     * @param object $plugin Counter plugin object
     */
    function CounterAdmin($plugin)
    {
        $this->admin_lang = $plugin->admin_lang;
        $this->_settings = $plugin->settings;
        $this->_self_dir = $plugin->PLUGIN_SELF_DIR;
        $this->_self_url = $plugin->PLUGIN_SELF_URL;
    }

    /**
     * creates plugin administration area content
     *
     * @param array $postresult result of post action
     *
     * @return string HTML output
     */
    function getContentAdmin($postresult)
    {
        global $CatPage;

        // initialize message content
        $msg = '';

        // handle postresult
        if (isset($postresult['reset'])) {
            if ($postresult['reset']) {
                $msg = $this->throwSuccess(
                    $this->admin_lang->getLanguageValue('msg_success_reset')
                );
            } else {
                $msg = $this->throwError(
                    $this->admin_lang->getLanguageValue('msg_error_reset')
                );
            }
        }

        // get current counter data
        $filedata = $this->_self_dir . 'data/data.conf.php';
        $datalist = CounterDatabase::loadArray($filedata);

        // get current ip data
        $fileips = $this->_self_dir . 'data/ips.conf.php';
        $online  = count(CounterDatabase::loadArray($fileips));

        // read admin.css
        $admin_css = '';
        $lines = file('../plugins/Counter/admin.css');
        foreach ($lines as $line_num => $line) {
            $admin_css .= trim($line);
        }

        // add template CSS
        $content = '<style>' . $admin_css . '</style>';

        // build Template
        $content .= '
            <div class="counter-admin-header">
            <span>'
                . $this->admin_lang->getLanguageValue(
                    'admin_header',
                    self::PLUGIN_TITLE
                )
            . '</span>
            <a
                class="img-button icon-refresh"
                title="'
                . $this->admin_lang->getLanguageValue('icon_refresh')
                . '" onclick="window.location
                    = (String(window.location).indexOf(\'?\') != -1)
                    ? window.location
                    : String(window.location)
                    + \'?nojs=true&pluginadmin=Counter&action=plugins&multi=true\';"
            ></a>
            <a href="' . self::PLUGIN_DOCU . '" target="_blank">
                <img style="float:right;" src="' . self::LOGO_URL . '" />
            </a>
            </div>
        ';

        // add possible message to output content
        if ($msg != '') {
            $content .= '<div class="admin-msg">' . $msg . '</div>';
        }
        $content .= '
        <ul class="counter-ul">
            <li class="mo-in-ul-li ui-widget-content counter-admin-li">
                <div class="counter-admin-subheader">'
                . $this->admin_lang->getLanguageValue('admin_current_counter')
                . '</div>
                <table>
                    <tr>
                        <td>'
                        . $this->admin_lang->getLanguageValue('data_online')
                        . '</td>
                        <td>' . $online . '</td>
                    </tr>
                    <tr>
                        <td>'
                        . $this->admin_lang->getLanguageValue('data_today')
                        . '</td>
                        <td>' . $datalist['today'] . '</td>
                    </tr>
                    <tr>
                        <td>'
                        . $this->admin_lang->getLanguageValue('data_yesterday')
                        . '</td>
                        <td>' . $datalist['yesterday'] . '</td>
                    </tr>
                    <tr>
                        <td>'
                        . $this->admin_lang->getLanguageValue('data_maximum')
                        . '</td>
                        <td>' . $datalist['max'] . '</td>
                    </tr>
                    <tr>
                        <td>'
                        . $this->admin_lang->getLanguageValue('data_maximumdate')
                        . '</td>
                        <td>'
                        . date(
                            $this->_settings->get('dateformat'),
                            $datalist['maxdate']
                        )
                    . '</td>
                    </tr>
                    <tr>
                        <td>'
                        . $this->admin_lang->getLanguageValue('data_average')
                        . '</td>
                        <td>' . $datalist['average'] . '</td>
                    </tr>
                    <tr>
                        <td>'
                        . $this->admin_lang->getLanguageValue('data_total')
                        . '</td>
                        <td>' . $datalist['total'] . '</td>
                    </tr>
                    <tr>
                        <td>'
                        . $this->admin_lang->getLanguageValue('data_date')
                        . '</td>
                        <td>'
                        . date(
                            $this->_settings->get('dateformat'),
                            strtotime($this->_settings->get('resetdate'))
                        )
                    . '</td></tr>
                </table>';

        $content .= '</li></ul>';

        return $content;
    }

    /**
     * checks and handles post variables
     *
     * @return boolean success
     */
    function checkPost()
    {
        // initialize return array
        $success = array();

        // handle actions
        $reset = getRequestValue('r', "post", false);
        if ($reset != '') {
            $success['reset'] = $this->resetCounter();
        }

        return $success;
    }

    /**
     * resets data from counter
     *
     * @return boolean success
     */
    protected function resetCounter()
    {
        return Database::saveArray(
            $this->_self_dir . 'data/' . $catfile . '.php',
            '0'
        );
    }
}

?>