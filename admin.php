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
 * @author   DEVMOUNT <mail@devmount.de>
 * @license  GPL v3+
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
 * @author   DEVMOUNT <mail@devmount.de>
 * @license  GPL v3+
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

    // counter elements / flag
    private $_elements = array(
        'all',
        'online',
        'today',
        'yesterday',
        'maximum',
        'maximumdate',
        'average',
        'total',
    );

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
        foreach ($this->_elements as $element) {
            if (isset($postresult['reset_' . $element])) {
                if ($postresult['reset_' . $element]) {
                    $msg = $this->throwSuccess(
                        $this->admin_lang->getLanguageValue(
                            'msg_success_reset_element',
                            $this->admin_lang->getLanguageValue('data_' . $element)
                        )
                    );
                } else {
                    $msg = $this->throwError(
                        $this->admin_lang->getLanguageValue(
                            'msg_error_reset_element',
                            $this->admin_lang->getLanguageValue('data_' . $element)
                        )
                    );
                }
            }
        }

        // get current counter data
        $datalist = CounterDatabase::loadArray($this->_filedata);

        // get current ip data
        $iplist = CounterDatabase::loadArray($this->_fileips);

        // build table data
        $table_rows = array(
            'online'      => count($iplist),
            'today'       => $datalist['today'],
            'yesterday'   => $datalist['yesterday'],
            'maximum'     => $datalist['max'],
            'maximumdate' => ($datalist['maxdate'] != 0)
                                ? date('d.m.Y, H:i:s', $datalist['maxdate'])
                                : '-',
            'average'     => $datalist['average'],
            'total'       => $datalist['total'],
        );

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
                            'confirm_reset_all'
                        )
                    )
                    . '\'))
                    document.getElementById(\'reset_all\')
                        .submit()"
                ></a>

            </div>
            <table class="data" cellspacing="0">
                <tr>
                    <th>'
                        . $this->admin_lang->getLanguageValue('data_label')
                    . '</th>
                    <th>'
                        . $this->admin_lang->getLanguageValue('data_value')
                    . '</th>
                    <th>'
                        . $this->admin_lang->getLanguageValue('data_action')
                    . '</th>
                </tr>';
        foreach ($table_rows as $label => $value) {
            $content .= '
                <tr>
                    <td>'
                    . $this->admin_lang->getLanguageValue('data_' . $label)
                    . '</td>
                    <td>' . $value . '</td>
                    <td>
                    <form
                        id="reset_' . $label . '"
                        action="' . URL_BASE . ADMIN_DIR_NAME . '/index.php"
                        method="post"
                    >
                        <input type="hidden" name="pluginadmin"
                            value="' . PLUGINADMIN . '"
                        />
                        <input type="hidden" name="action" value="' . ACTION . '" />
                        <input type="hidden" name="reset_' . $label . '" value="1" />
                    </form>
                    <a
                        class="img-button icon-reset"
                        title="'
                        . $this->admin_lang->getLanguageValue('icon_reset') . ' '
                        . $this->admin_lang->getLanguageValue('data_' . $label)
                        . '"
                        onclick="if(confirm(\''
                        . $this->admin_lang->getLanguageValue(
                            'confirm_reset',
                            $this->admin_lang->getLanguageValue(
                                'data_' . $label
                            )
                        )
                        . '\'))
                        document.getElementById(\'reset_' . $label . '\')
                            .submit()"
                    ></a>
                    </td>
                </tr>
            ';
        }
        $content .= '
                <tr>
                    <td>'
                    . $this->admin_lang->getLanguageValue('data_date')
                    . '</td>
                    <td>'
                    . date(
                        $this->_settings->get('dateformat'),
                        strtotime($this->_settings->get('resetdate'))
                    )
                    . '</td>
                    <td></td>
                </tr>
            </table>';

        $content .= '
            <table class="ip" cellspacing="0">
                <tr>
                    <th>'
                    . $this->admin_lang->getLanguageValue('data_ips')
                    . '</th>
                    <th></th>
                </tr>';

        // check ip list
        if (count($iplist) > 0) {
            foreach ($iplist as $ip => $tstamp) {
                $content .= '
                        <tr>
                            <td>' . date('d.m.Y, H:i:s', $tstamp) . '</td>
                            <td>' . $ip . '</td>
                        </tr>';
            }
        } else {
            $content .= '<tr>
                            <td colspan="2">'
                            . $this->admin_lang->getLanguageValue('data_no_ips')
                            . '</td>
                        </tr>';
        }

        $content .= '
            </table>
            <br style="clear: both;" />
            </li>
        </ul>';

        return $content;
    }

    /**
     * checks and handles post variables
     *
     * @return boolean success
     */
    function checkPost()
    {
        // handle actions
        foreach ($this->_elements as $element) {
            $reset = getRequestValue('reset_' . $element, "post", false);
            if ($reset != '') {
                return array('reset_' . $element => $this->resetCounter($element));
            }
        }

        return array();
    }

    /**
     * resets data from counter
     *
     * @param string $flag counterelement to reset
     *
     * @return boolean success
     */
    protected function resetCounter($flag)
    {
        switch ($flag) {
        case 'all':
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
            break;

        case 'online':
            return CounterDatabase::saveArray($this->_fileips, array());
            break;

        case 'today':
            $datalist = CounterDatabase::loadArray($this->_filedata);
            $datalist['today'] = 0;
            return CounterDatabase::saveArray($this->_filedata, $datalist);
            break;

        case 'yesterday':
            $datalist = CounterDatabase::loadArray($this->_filedata);
            $datalist['yesterday'] = 0;
            return CounterDatabase::saveArray($this->_filedata, $datalist);
            break;

        case 'maximum':
            $datalist = CounterDatabase::loadArray($this->_filedata);
            $datalist['max'] = 0;
            return CounterDatabase::saveArray($this->_filedata, $datalist);
            break;

        case 'maximumdate':
            $datalist = CounterDatabase::loadArray($this->_filedata);
            $datalist['maxdate'] = 0;
            return CounterDatabase::saveArray($this->_filedata, $datalist);
            break;

        case 'average':
            $datalist = CounterDatabase::loadArray($this->_filedata);
            $datalist['average'] = '-';
            return CounterDatabase::saveArray($this->_filedata, $datalist);
            break;

        case 'total':
            $datalist = CounterDatabase::loadArray($this->_filedata);
            $datalist['total'] = 0;
            return CounterDatabase::saveArray($this->_filedata, $datalist);
            break;

        default:
            return false;
            break;
        }
        return false;
    }
}

?>