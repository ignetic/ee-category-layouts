<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EEHarbor\Wygwam\Helper;

class Wygwam_helper extends Helper
{

	protected static $_globalSettings;
	protected static $_toolbarButtonGroups;
	protected static $_themeUrl;
	protected static $_includedConfigs;
	
	protected static $_includedJs;


	public static function getJs()
	{
		return implode(' ', static::$_includedJs);
	}
	public static function getincludedConfigs()
	{
		return static::$_includedConfigs;
	}

    /**
     * Gets the Wygwam config JS in the page foot by config ID.
	 * adapted from insertConfigJsById
     *
     * @param $configId
     *
     * @return $configHandle The handle for config used by Wygwam JS
     */
    public static function getConfigJsById($configId)
    {
        $globalSettings = static::getGlobalSettings();

        // starting point
        $baseConfig = static::baseConfig();

        // -------------------------------------------
        //  Editor Config
        // -------------------------------------------


        if (ee()->db->table_exists('wygwam_configs')
            && is_numeric($configId)
            && $config = ee('Model')->get('wygwam:Config')->filter('config_id', '==', $configId)->first()
        ) {
            /**
             * @var $config \EEHarbor\Wygwam\Model\Config
             */
            // merge custom settings into config
            $customSettings = $config->settings;
            $configHandle = preg_replace('/[^a-z0-9]/i', '_', $config->config_name).$configId;
            $config = array_merge($baseConfig, $customSettings);
        } else {
            $customSettings = array();
            $config = $baseConfig;
            $configHandle = 'default0';
        }

        // skip if already included
        if (isset(static::$_includedConfigs) && in_array($configHandle, static::$_includedConfigs)) {
            return $configHandle;
        }
/*
        // language
        if (! isset($config['language']) || ! $config['language']) {
            $langMap = static::languageMap();
            $language = ee()->session->userdata('language');
            $config['language'] = isset($langMap[$language]) ? $langMap[$language] : 'en';
        }
*/
        // toolbar
        if (is_array($config['toolbar'])) {
            $config['toolbar'] = static::createToolbar($config['toolbar']);
        }

        // css
        if (! $config['contentsCss']) {
            unset($config['contentsCss']);
        }

        // set the autoGrow_minHeight to the height
        $config['autoGrow_minHeight'] = $config['height'];

        // allowedContent
        if ($config['restrict_html'] == 'n') {
            $config['allowedContent'] = true;
        }

        unset($config['restrict_html']);

        // extraPlugins
        if (!empty($config['extraPlugins'])) {
            $extraPlugins = array_map('trim', explode(',', $config['extraPlugins']));
        } else {
            $extraPlugins = array();
        }

        //$extraPlugins[] = 'autosave';
        $extraPlugins[] = 'wygwam';
        $extraPlugins[] = 'readmore';

        if ($config['parse_css'] === 'y') {
            if (!in_array('stylesheetparser', $extraPlugins)) {
                $extraPlugins[] = 'stylesheetparser';
            }

            unset($config['parse_css']);
        }

        $config['extraPlugins'] = implode(',', $extraPlugins);

        // -------------------------------------------
        //  File Browser Config
        // -------------------------------------------
/*
        $userGroup = ee()->session->userdata('group_id');
        $uploadDir = isset($config['upload_dir']) ? $config['upload_dir'] : null;
        $uploadDestination = static::getUploadDestinations($userGroup, $uploadDir);

        $fileBrowser = isset($globalSettings['file_browser']) ? $globalSettings['file_browser'] : 'ee';

        switch ($fileBrowser) {
            case 'assets':

                // make sure Assets is actually installed
                // (otherwise, just use the EE File Manager)
                if (static::isAssetsInstalled()) {
                    // include sheet resources
                    \Assets_helper::include_sheet_resources();

                    // if no upload directory was set, just default to "all"
                    if (! $uploadDir) {
                        $uploadDir = '"all"';
                    }

                    // If this has a source type passed in as well, wrap it in quotes.
                    if (strpos($uploadDir, ":")) {
                        $uploadDir = '"'.$uploadDir.'"';
                    }

                    $config['filebrowserBrowseFunc']      = 'function(params) { Wygwam.loadAssetsSheet(params, '.$uploadDir.', "any"); }';
                    $config['filebrowserImageBrowseFunc'] = 'function(params) { Wygwam.loadAssetsSheet(params, '.$uploadDir.', "image"); }';
                    $config['filebrowserFlashBrowseFunc'] = 'function(params) { Wygwam.loadAssetsSheet(params, '.$uploadDir.', "flash"); }';

                    break;
                }

                // no break
            default:

                if (! $uploadDestination) {
                    break;
                }

                // load the file browser
                // pass in the uploadDir to limit the directory to the one choosen
                static::insertJs(NL."\t"."Wygwam.fpUrl = '" . ee('CP/FilePicker')->make($uploadDir)->getUrl() ."';".NL);

                // if no upload directory was set, just default to "all"
                if (! $uploadDir) {
                    $uploadDir = '"all"';
                }

                $config['filebrowserBrowseFunc']      = 'function(params) { Wygwam.loadEEFileBrowser(params, '.$uploadDir.', "any"); }';
                $config['filebrowserImageBrowseFunc'] = 'function(params) { Wygwam.loadEEFileBrowser(params, '.$uploadDir.', "image"); }';
        }
*/
        // add any site page data to wygwam config
        if ($pages = static::getAllPageData()) {
            ee()->lang->loadfile('wygwam');
            $sitePageString = lang('wygwam_site_page');

            foreach ($pages as $page) {
                $config['link_types'][$sitePageString][] = array(
                    'label' => $page[2],
                    'url'   => $page[4]
                );
            }
        }
/*
        // -------------------------------------------
        //  'wygwam_config' hook
        //   - Override any of the config settings
        //
        if (ee()->extensions->active_hook('wygwam_config')) {
            $config = ee()->extensions->call('wygwam_config', $config, $customSettings);
        }
        //
        // -------------------------------------------
*/
        unset($config['upload_dir']);

        // -------------------------------------------
        //  JSONify Config and Return
        // -------------------------------------------

        $configLiterals = static::configLiterals();
        $configBooleans = static::configBooleans();

        $js = '';

        foreach ($config as $setting => $value) {
            if (! in_array($setting, $configLiterals)) {
                if (in_array($setting, $configBooleans)) {
                    $value = ($value == 'y' ? true : false);
                }

                $value = json_encode($value);

                // Firefox gets an "Unterminated string literal" error if this line gets too long,
                // so let's put each new value on its own line
                if ($setting == 'link_types') {
                    $value = str_replace('","', "\",\n\t\t\t\"", $value);
                }
            }

            $js .= ($js ? ','.NL : '')
                . "\t\t".'"'.$setting.'": '.$value;
        }

        // Strip out any non-space whitespace chars
        $js = str_replace(array(chr(10), chr(11), chr(12), chr(13)), ' ', $js);
		
		static::$_includedJs[] = NL."\t".'Wygwam.configs["'.$configHandle.'"] = {'.NL.$js.NL."\t".'};'.NL;
        static::$_includedConfigs[] = $configHandle;

        return $configHandle;
		
	}

}

