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
    // file paths
    private $_fileips;
    private $_filedata;

    /**
     * constructor
     *
     * @param object $plugin Counter plugin object
     */
    function CounterAdmin($plugin)
    {
        $this->admin_lang = $plugin->admin_lang;
        $this->_settings  = $plugin->settings;
        $this->_self_dir  = $plugin->PLUGIN_SELF_DIR;
        $this->_self_url  = $plugin->PLUGIN_SELF_URL;
        $this->_fileips   = $plugin->PLUGIN_SELF_DIR . 'data/ips.conf.php';
        $this->_filedata  = $plugin->PLUGIN_SELF_DIR . 'data/data.conf.php';
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
        if (isset($postresult['reset_all'])) {
            if ($postresult['reset_all']) {
                $msg = $this->throwSuccess(
                    $this->admin_lang->getLanguageValue('msg_success_reset_all')
                );
            } else {
                $msg = $this->throwError(
                    $this->admin_lang->getLanguageValue('msg_error_reset_all')
                );
            }
        }

        // get current counter data
        $datalist = CounterDatabase::loadArray($this->_filedata);

        // get current ip data
        $online  = count(CounterDatabase::loadArray($this->_fileips));

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
                . '
                <form
                    id="reset_all"
                    action="' . URL_BASE . ADMIN_DIR_NAME . '/index.php"
                    method="post"
                >
                    <input type="hidden" name="pluginadmin"
                        value="' . PLUGINADMIN . '"
                    />
                    <input type="hidden" name="action" value="' . ACTION . '" />
                    <input type="hidden" name="reset_all" value="1" />
                </form>
                <a
                    class="img-button icon-reset"
                    title="'
                    . $this->admin_lang->getLanguageValue('icon_reset')
                    . '"
                    onclick="if(confirm(\''
                    . $this->admin_lang->getLanguageValue(
                        'confirm_reset',
                        $this->admin_lang->getLanguageValue(
                            'confirm_whole_counter'
                        )
                    )
                    . '\'))
                    document.getElementById(\'reset_all\')
                        .submit()"
                ></a>

                </div>
                <table cellspacing="0">
                    <tr>
                        <th>Label</th>
                        <th>Wert</th>
                    </tr>
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
                        <td>';
                        $content .= ($datalist['maxdate'] != 0)
                            ? date('d.m.Y, H:i:s', $datalist['maxdate'])
                            : '-';
                        $content .= '</td>
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
        $reset = getRequestValue('reset_all', "post", false);
        if ($reset != '') {
            $success['reset_all'] = $this->resetCounter();
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
        return
            CounterDatabase::saveArray(
                $this->_filedata,
                array(
                    'date' => 0,
                    'today' => 0,
                    'yesterday' => 0,
                    'total' => 0,
                    'max' => 0,
                    'maxdate' => 0,
                    'average' => '-',
                )
            ) and CounterDatabase::saveArray($this->_fileips, array());
    }
}

?>