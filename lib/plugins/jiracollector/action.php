<?php
/**
 * DokuWiki plugin for JiraCollector
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     stephan dekker <stephan@sparklingsoftware.com.au>
 */

if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'action.php';

class action_plugin_jiracollector extends DokuWiki_Action_Plugin {

	function register(&$controller) {
		$controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'generate_script');
	}

     function getJiraCollector()
     {  
        global $conf;
        $this->getConf('');
        $url = $conf['plugin']['jiracollector']['JiraCollectorURL'];
          
        $script = '<script type="text/javascript" src="'.$url.'"></script>';

        return $script;
     }

     function generate_script(&$event, $param) {
		$script = $this->getJiraCollector();
        ptln($script);
     }
}
