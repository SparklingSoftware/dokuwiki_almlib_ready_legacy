<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Stephan Dekker <stephan@sparklingsoftware.com.au>
 */

if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'action.php';

class action_plugin_rigrr_javascript extends DokuWiki_Action_Plugin {

    function register(&$controller) {
		$controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, '_hook_header');
	}

    function rigrr_javascript()
    {
        $rigrr = DOKU_URL.'lib/plugins/rigrr/lib/rigrr.nocache.js';
        $script = '<script src="'.$rigrr.'" ></script>';
        
        return $script;
    }
    
	function _hook_header(&$event, $param) {
		$data = $this->rigrr_javascript();
        ptln($data);

		//$event->data['script'][] = array(
		//	'type' => 'text/javascript',
		//	'charset' => 'utf-8',
		//	'_data' => $data,
		//);
	}
    
}
