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
    public static function getConfigJsById($configId, $allowedContent='')
    {
        $globalSettings = static::getGlobalSettings();

        // starting point
        $baseConfig = static::baseConfig();

        // -------------------------------------------
        //  Editor Config
        // -------------------------------------------

		$allowedContentHandle = (!empty($allowedContent) ? '_'.md5($allowedContent) : '');

        if (ee()->db->table_exists('wygwam_configs')
            && is_numeric($configId)
            && $config = ee('Model')->get('wygwam:Config')->filter('config_id', '==', $configId)->first()
        ) {
            /**
             * @var $config \EEHarbor\Wygwam\Model\Config
             */
            // merge custom settings into config
            $customSettings = $config->settings;
            $configHandle = preg_replace('/[^a-z0-9]/i', '_', $config->config_name).$configId.$allowedContentHandle;
            $config = array_merge($baseConfig, $customSettings);
        } else {
            $customSettings = array();
            $config = $baseConfig;
            $configHandle = 'default0'.$allowedContentHandle;
        }

        // skip if already included
        if (isset(static::$_includedConfigs) && in_array($configHandle, static::$_includedConfigs)) {
            return $configHandle;
        }

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
		if ( ! empty($allowedContent)) {
			$config['allowedContent'] = $allowedContent;
		}
        else if ($config['restrict_html'] == 'n') {
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

