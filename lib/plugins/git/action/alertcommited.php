<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Stephan Dekker <Stephan@SparklingSoftware.com.au>
 */

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'action.php';
require_once(DOKU_PLUGIN.'git/lib/Git.php');


class action_plugin_git_alertcommited extends DokuWiki_Action_Plugin {
        
	function register(&$controller) {
		$controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'handler');
 	}
    
    function current_url(){
        $url = (!empty($_SERVER['HTTPS'])) ? "https://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'] : "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
        return $url;
    }
     
    function handler(&$event, $param) {
        global $conf, $INFO;
        $this->getConf('');

        $git_exe_path = $conf['plugin']['git']['git_exe_path'];        
        $gitLocalStatusUrl = wl($conf['plugin']['git']['local_status_page'],'',true);
        $datapath = $conf['savedir'];    

        $currentURL = $this->current_url();
        if ($gitLocalStatusUrl === $currentURL) return;  // Skip local GIT status page, no notification needed when the user is looking at the details.
        if (strpos(strtolower($currentURL), strtolower('mediamanager.php')) > 0) return;  // Skip media manager page as well
        
        $repo = new GitRepo($datapath);
        $repo->git_path = $git_exe_path; 
        if ($repo->test_origin() === false) {
           msg('Repository seems to have an invalid remote (origin)');
           return;
        }
        $show = $repo->ChangesAwaitingApproval();

        if ($show) {
            msg('Changes waiting to be approved. <a href="'.$gitLocalStatusUrl.'">click here to view changes.</a>');		
        }
	}    
           
}
