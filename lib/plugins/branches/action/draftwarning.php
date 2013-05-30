<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Stephan Dekker <stephan@sparklingsoftware.com.au>
 */

if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'action.php';

class action_plugin_branches_draftwarning extends DokuWiki_Action_Plugin {

    var $basePath = "";

    function register(&$controller){
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'handle', array());
        $this->basePath = dirname(DOKU_INC);
    }
 
    function current_url(){
        $url = (!empty($_SERVER['HTTPS'])) ? "https://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'] : "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
        return $url;
    }
    
    function handle(&$event, $param){
        global $conf;
        
        $this->getConf('');
        $live_virtual_dir = $conf['plugin']['branches']['live_virtual_dir'];
        
        $currentURL = $this->current_url();
        if (strpos(strtolower($currentURL), strtolower('mediamanager.php')) > 0) return;  // Skip media manager page as well
        
        $pos = strpos(strtolower(wl($ID)), strtolower($live_virtual_dir));        
        if ($pos === false) msg('You are looking at a draft!! Click <a href="/'.$live_virtual_dir.'/doku.php"/>here</a> to go to '.$live_virtual_dir.' content', -1);
    }
}
