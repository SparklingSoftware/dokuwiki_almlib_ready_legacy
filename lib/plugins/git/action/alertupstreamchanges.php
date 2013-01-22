<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Stephan Dekker <Stephan@SparklingSoftware.com.au>
 */

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'action.php';
require_once(DOKU_PLUGIN.'git/lib/Git.php');


class action_plugin_git_alertupstreamchanges extends DokuWiki_Action_Plugin {
        
	function register(&$controller) {
		$controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, '_hook_header');
 	}
    
	function _hook_header(&$event, $param) {
        global $conf;

        $gitStatusUrl = DOKU_URL.'doku.php?id=wiki:git:masterstatus';
        
        if ($this->CheckForUpdates())
            msg('Updates available from master. <a href="'.$gitStatusUrl.'">click here to merge changes into this workspace.</a>');		
	}

    function CheckForUpdates() {
        global $conf;
        $this->getConf();
        $hasCacheTimedOut = false;

        $git_exe_path = $conf['plugin']['git']['git_exe_path'];        
        $datapath = $conf['savedir'];    

        $updatesAvailable = false;
        if ($hasCacheTimedOut)
        {
            $repo = new GitRepo($datapath);
            $repo->git_path = $git_exe_path;        
            $repo->fetch();
            $log = $repo->get_log();
                        
            if ($log === "") $updatesAvailable = true;
            return $updatesAvailable;
        }
        
        $updatesAvailable = $this->readUpdateStatusFromCache();
    }
    
    function readUpdateStatusFromCache() {
        return true;
    }
        
}
