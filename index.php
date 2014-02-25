<?php

/**
 * moziloCMS Plugin: Counter
 *
 * Counts, stores and analyzes current page visits.
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_MoziloPlugins
 * @author   HPdesigner <kontakt@devmount.de>
 * @license  GPL v3
 * @version  GIT: v1.1.2013-09-19
 * @link     https://github.com/devmount/Counter
 * @link     http://devmount.de/Develop/Mozilo%20Plugins/Counter.html
 * @see      Now faith is being sure of what we hope for
 *           and certain of what we do not see.
 *            — The Bible
 *
 * Plugin created by DEVMOUNT
 * www.devmount.de
 *
 */

// only allow moziloCMS environment
if (!defined('IS_CMS')) {
    die();
}

// add database class
require_once "database.php";

/**
 * Counter Class
 *
 * @category PHP
 * @package  PHP_MoziloPlugins
 * @author   HPdesigner <kontakt@devmount.de>
 * @license  GPL v3
 * @link     https://github.com/devmount/Counter
 */
class Counter extends Plugin
{
    // language
    private $_admin_lang;
    private $_cms_lang;

    // plugin information
    const PLUGIN_AUTHOR  = 'HPdesigner';
    const PLUGIN_DOCU
        = 'http://devmount.de/Develop/Mozilo%20Plugins/Counter.html';
    const PLUGIN_TITLE   = 'Counter';
    const PLUGIN_VERSION = 'v1.1.2013-09-19';
    const MOZILO_VERSION = '2.0';
    private $_plugin_tags = array(
        'tag'      => '{Counter}',
    );
    private $_template_elements = array(
        '#ONLINE#',
        '#TODAY#',
        '#YESTERDAY#',
        '#MAXIMUM#',
        '#AVERAGE#',
        '#TOTAL#',
        '#DATE#',
    );

    const LOGO_URL = 'http://media.devmount.de/logo_pluginconf.png';

    /**
     * set configuration elements, their default values and their configuration
     * parameters
     *
     * @var array $_confdefault
     *      text     => default, type, maxlength, size, regex
     *      textarea => default, type, cols, rows, regex
     *      password => default, type, maxlength, size, regex, saveasmd5
     *      check    => default, type
     *      radio    => default, type, descriptions
     *      select   => default, type, descriptions, multiselect
     */
    private $_confdefault = array(
        'resetdate' => array(
            '20140101',
            'text',
            '50',
            '15',
            '',
        ),
        'mintime' => array(
            '60',
            'text',
            '6',
            '3',
            "/^[0-9]{0,6}$/",
        ),
        'reload' => array(
            '120',
            'text',
            '6',
            '3',
            "/^[0-9]{0,6}$/",
        ),
        'template' => array(
            '#ONLINE# | #TODAY# | #TOTAL#',
            'textarea',
            '70',
            '8',
            '',
        ),
    );

    /**
     * creates plugin content
     *
     * @param string $value Parameter divided by '|'
     *
     * @return string HTML output
     */
    function getContent($value)
    {
        global $CMS_CONF;

        $this->_cms_lang = new Language(
            $this->PLUGIN_SELF_DIR
            . 'lang/cms_language_'
            . $CMS_CONF->get('cmslanguage')
            . '.txt'
        );

        // get conf and set default
        $conf = array();
        foreach ($this->_confdefault as $elem => $default) {
            $conf[$elem] = ($this->settings->get($elem) == '')
                ? $default[0]
                : $this->settings->get($elem);
        }

        // initialize basic values
        $online    = 0;
        $time      = time();
        $date      = date('d.m.y');
        $ip        = getenv(REMOTE_ADDR);
        $fileips   = $this->PLUGIN_SELF_DIR . 'data/ips.conf.php';
        $filedata  = $this->PLUGIN_SELF_DIR . 'data/data.conf.php';
        $iplist    = CounterDatabase::loadArray($fileips);
        $datalist  = CounterDatabase::loadArray($filedata);
        $setdate   = false;
        $locked_ip = false;
        $max       = 1;
        $average   = 0;

        // initialize counter database
        if (empty($datalist)) {
            $datalist = array(
                'date' => 0,
                'today' => 0,
                'yesterday' => 0,
                'total' => 0,
                'max' => 0,
            );
            CounterDatabase::saveArray($filedata, $datalist);
        }
        // initialize ip database
        if (empty($iplist)) {
            $iplist = array(
                $ip => $time,
            );
            CounterDatabase::saveArray($fileips, $iplist);
        } else {
            // ip does not exist yet: append it
            if (!array_key_exists($ip, $iplist)) {
                CounterDatabase::appendArray($fileips, array($ip => $time));
            } else {
                // ip is locked, when still in reload time
                if ($iplist[$ip] > $time - $conf['reload']) {
                    $locked_ip = true;
                } else {
                    // otherwise its time will be updated
                    $iplist[$ip] = $time;
                    CounterDatabase::saveArray($fileips, $iplist);
                }
            }
        }

        // handle day switch
        if ($datalist['date'] != $date) {
            $datalist['date'] = $date;
            $setdate = true;
        }
        // increment total count when ip not already registered
        if (!$locked_ip) {
            ++$datalist['total'];
            // increment today count when it's still today
            if (!$setdate) {
                ++$datalist['today'];
            } else {
                // new day
                $datalist['yesterday'] = $datalist['today'];
                $datalist['today'] = 1;
            }
        }

        // build maximum
        if ($datalist['today'] > $datalist['max']) {
            $datalist['max'] = $datalist['today'];
        }

        // write current values to database
        CounterDatabase::saveArray($filedata, $datalist);

        // if ip not online anymore, delete ip
        foreach ($iplist as $ipdata => $iptime) {
            if ($iptime < $time - $conf['mintime']) {
                CounterDatabase::deleteEntry($fileips, $ipdata);
            }
        }

        // evaluate average
        $dayspan = bcdiv(
            (strtotime($date) - strtotime($conf['resetdate'])), 86400, 0
        );
        if ($dayspan > 0) {
            $average
                = round((($datalist['total'] - $datalist['today'])/$dayspan), 1);
        } else {
            $average = 0;
        }

        // get online count
        $online = count($iplist);

        // initialize return content, begin plugin content
        $counter = '<!-- BEGIN ' . self::PLUGIN_TITLE . ' plugin content --> ';

        // fill template
        $counter .= $conf['template'];
        $counter = str_replace(
            $this->_template_elements,
            array($online,
                $datalist['today'],
                $datalist['yesterday'],
                $datalist['max'],
                $average,
                $datalist['total'],
                $conf['resetdate'],
            ),
            $counter
        );


        // end plugin content
        $counter .= '<!-- END ' . self::PLUGIN_TITLE . ' plugin content --> ';

        return $counter;
    }

    /**
     * sets backend configuration elements and template
     *
     * @return Array configuration
     */
    function getConfig()
    {
        $config = array();

        // read configuration values
        foreach ($this->_confdefault as $key => $value) {
            // handle each form type
            switch ($value[1]) {
            case 'text':
                $config[$key] = $this->confText(
                    $this->_admin_lang->getLanguageValue('config_' . $key),
                    $value[2],
                    $value[3],
                    $value[4],
                    $this->_admin_lang->getLanguageValue(
                        'config_' . $key . '_error'
                    )
                );
                break;

            case 'textarea':
                $config[$key] = $this->confTextarea(
                    $this->_admin_lang->getLanguageValue('config_' . $key),
                    $value[2],
                    $value[3],
                    $value[4],
                    $this->_admin_lang->getLanguageValue(
                        'config_' . $key . '_error'
                    )
                );
                break;

            case 'password':
                $config[$key] = $this->confPassword(
                    $this->_admin_lang->getLanguageValue('config_' . $key),
                    $value[2],
                    $value[3],
                    $value[4],
                    $this->_admin_lang->getLanguageValue(
                        'config_' . $key . '_error'
                    ),
                    $value[5]
                );
                break;

            case 'check':
                $config[$key] = $this->confCheck(
                    $this->_admin_lang->getLanguageValue('config_' . $key)
                );
                break;

            case 'radio':
                $descriptions = array();
                foreach ($value[2] as $label) {
                    $descriptions[$label] = $this->_admin_lang->getLanguageValue(
                        'config_' . $key . '_' . $label
                    );
                }
                $config[$key] = $this->confRadio(
                    $this->_admin_lang->getLanguageValue('config_' . $key),
                    $descriptions
                );
                break;

            case 'select':
                $descriptions = array();
                foreach ($value[2] as $label) {
                    $descriptions[$label] = $this->_admin_lang->getLanguageValue(
                        'config_' . $key . '_' . $label
                    );
                }
                $config[$key] = $this->confSelect(
                    $this->_admin_lang->getLanguageValue('config_' . $key),
                    $descriptions,
                    $value[3]
                );
                break;

            default:
                break;
            }
        }

        // read admin.css
        $admin_css = '';
        $lines = file('../plugins/' . self::PLUGIN_TITLE. '/admin.css');
        foreach ($lines as $line_num => $line) {
            $admin_css .= trim($line);
        }

        // build template elements string
        $template_elements = '';
        foreach ($this->_template_elements as $elem) {
            $template_elements .= $elem . ' ';
        }

        // add template CSS
        $template = '<style>' . $admin_css . '</style>';

        // build Template
        $template .= '
            <div class="counter-admin-header">
            <span>'
                . $this->_admin_lang->getLanguageValue(
                    'admin_header',
                    self::PLUGIN_TITLE
                )
            . '</span>
            <a href="' . self::PLUGIN_DOCU . '" target="_blank">
            <img style="float:right;" src="' . self::LOGO_URL . '" />
            </a>
            </div>
        </li>
        <li class="mo-in-ul-li ui-widget-content counter-admin-li">
            <div class="counter-admin-subheader">'
            . $this->_admin_lang->getLanguageValue('admin_date_times')
            . '</div>
            <div style="margin-bottom:5px;">
                {resetdate_text}
                {resetdate_description}
                <span class="counter-admin-default">
                    [' . $this->_confdefault['resetdate'][0] .']
                </span>
            </div>
            <div style="margin-bottom:5px;">
                {mintime_text}
                {mintime_description}
                <span class="counter-admin-default">
                    [' . $this->_confdefault['mintime'][0] .']
                </span>
            </div>
            <div style="margin-bottom:5px;">
                {reload_text}
                {reload_description}
                <span class="counter-admin-default">
                    [' . $this->_confdefault['reload'][0] .']
                </span>
            </div>
        </li>
        <li class="mo-in-ul-li ui-widget-content counter-admin-li">
            <div class="counter-admin-subheader">'
            . $this->_admin_lang->getLanguageValue('admin_template')
            . '</div>
            <div style="margin-bottom:5px;">
                <div style="float:left;margin-right: 10px;">
                    {template_textarea}
                </div>
                {template_description}<br />
                <pre>' . $template_elements . '</pre>
                <span class="counter-admin-default">
                    [' . $this->_confdefault['template'][0] .']
                </span>
                <br style="clear:both;" />
        ';

        $config['--template~~'] = $template;

        return $config;
    }

    /**
     * sets default backend configuration elements, if no plugin.conf.php is
     * created yet
     *
     * @return Array configuration
     */
    function getDefaultSettings()
    {
        $config = array('active' => 'true');
        foreach ($this->_confdefault as $elem => $default) {
            $config[$elem] = $default[0];
        }
        return $config;
    }

    /**
     * sets backend plugin information
     *
     * @return Array information
     */
    function getInfo()
    {
        global $ADMIN_CONF;
        $this->_admin_lang = new Language(
            $this->PLUGIN_SELF_DIR
            . 'lang/admin_language_'
            . $ADMIN_CONF->get('language')
            . '.txt'
        );

        // build plugin tags
        $tags = array();
        foreach ($this->_plugin_tags as $key => $tag) {
            $tags[$tag] = $this->_admin_lang->getLanguageValue('tag_' . $key);
        }

        $info = array(
            '<b>' . self::PLUGIN_TITLE . '</b> ' . self::PLUGIN_VERSION,
            self::MOZILO_VERSION,
            $this->_admin_lang->getLanguageValue(
                'description',
                htmlspecialchars($this->_plugin_tags['tag'])
            ),
            self::PLUGIN_AUTHOR,
            self::PLUGIN_DOCU,
            $tags
        );

        return $info;
    }

    /**
     * creates configuration for text fields
     *
     * @param string $description Label
     * @param string $maxlength   Maximum number of characters
     * @param string $size        Size
     * @param string $regex       Regular expression for allowed input
     * @param string $regex_error Wrong input error message
     *
     * @return Array  Configuration
     */
    protected function confText(
        $description,
        $maxlength = '',
        $size = '',
        $regex = '',
        $regex_error = ''
    ) {
        // required properties
        $conftext = array(
            'type' => 'text',
            'description' => $description,
        );
        // optional properties
        if ($maxlength != '') {
            $conftext['maxlength'] = $maxlength;
        }
        if ($size != '') {
            $conftext['size'] = $size;
        }
        if ($regex != '') {
            $conftext['regex'] = $regex;
        }
        if ($regex_error != '') {
            $conftext['regex_error'] = $regex_error;
        }
        return $conftext;
    }

    /**
     * creates configuration for textareas
     *
     * @param string $description Label
     * @param string $cols        Number of columns
     * @param string $rows        Number of rows
     * @param string $regex       Regular expression for allowed input
     * @param string $regex_error Wrong input error message
     *
     * @return Array  Configuration
     */
    protected function confTextarea(
        $description,
        $cols = '',
        $rows = '',
        $regex = '',
        $regex_error = ''
    ) {
        // required properties
        $conftext = array(
            'type' => 'textarea',
            'description' => $description,
        );
        // optional properties
        if ($cols != '') {
            $conftext['cols'] = $cols;
        }
        if ($rows != '') {
            $conftext['rows'] = $rows;
        }
        if ($regex != '') {
            $conftext['regex'] = $regex;
        }
        if ($regex_error != '') {
            $conftext['regex_error'] = $regex_error;
        }
        return $conftext;
    }
}

?>