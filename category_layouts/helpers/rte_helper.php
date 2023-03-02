<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use ExpressionEngine\Addons\Rte\RteHelper;

class Rte_helper //extends Helper
{

    /**
     * Gets the Rte config JS form field toolsets.
     *
     * @param $rteFields
     *
     * @return $data as JS
     */
    public static function getJsFromFieldToolsets($rteFields)
    {
		$data = '';
		
		ee()->load->library('cp');
		ee()->load->library('javascript');
		
		// Get assets 
		$data .= file_get_contents( PATH_THEMES_GLOBAL_ASSET . 'javascript/' . PATH_JS . '/fields/rte/ckeditor/ckeditor.js')."\n\n";
		$data .= file_get_contents( PATH_THEMES_GLOBAL_ASSET . 'javascript/' . PATH_JS . '/fields/rte/redactor/redactor.min.js')."\n\n";
		$data .= file_get_contents( PATH_THEMES_GLOBAL_ASSET . 'javascript/' . PATH_JS . '/fields/rte/rte.js')."\n\n";
		
		foreach($rteFields as $fieldId => $toolsetId)
		{
			// Fields require IDs					
			$data .= NL.'$("textarea[name='.$fieldId.']").attr("id", "'.$fieldId.'");'.NL;
			
			if ( ! empty($toolsetId)) {
				$toolset = ee('Model')->get('rte:Toolset')->filter('toolset_id', $toolsetId)->first();
			} else {
				$toolset = ee('Model')->get('rte:Toolset')->first();
			}

			$configHandle = RteHelper::insertConfigJsById($toolsetId);
			
			// capture the scripts
			$globalJs = ee()->javascript->get_global();
			preg_match('/<script[^>]*?>([\s\S]*?)<\/script>/', $globalJs, $matches);
			$data .= $matches[1];
			
			$defer = false;
			$data .= 'new Rte("' . $fieldId . '", "' . $configHandle . '", ' . ($defer ? 'true' : 'false') . ');';	

			// enable image browser
			$data .= "window.Rte_browseImages = function(sourceElement, params) {
						Rte.loadEEFileBrowser(sourceElement, params, false, 'image');
					};";

		}

        return $data;
		
	}

}

