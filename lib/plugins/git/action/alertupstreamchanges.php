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
		$controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'handler');
 	}
    
	function handler(&$event, $param) {
        global $conf, $ID;
        $this->getConf('');

        $gitRemoteStatusUrl = DOKU_URL.'doku.php?id='.$conf['plugin']['git']['origin_status_page'];
        if ($gitRemoteStatusUrl === wl($ID,'',true)) return;  // Skip remote GIT status page, no notification needed when the user is looking at the details.
        if (strpos(strtolower($gitRemoteStatusUrl), strtolower('mediamanager.php')) !== false) return;  // Skip media manager page as well

        if ($this->CheckForUpdates())
            msg('Other improvements have been approved. <a href="'.$gitRemoteStatusUrl.'">click here to merge changes into this workspace.</a>');		
	}

    function CheckForUpdates() {
        global $conf;
        $this->getConf('');

        $git_exe_path = $conf['plugin']['git']['git_exe_path'];        
        $datapath = $conf['savedir'];    

        $updatesAvailable = false;
        if ($this->hasCacheTimedOut())
        {
            $repo = new GitRepo($datapath);
            $repo->git_path = $git_exe_path;        
            $repo->fetch();
            $log = $repo->get_log();
                        
            if ($log !== "") $updatesAvailable = true;
        }
        else
        {
            $updatesAvailable = $this->readUpdateStatusFromCache();
        }
        return $updatesAvailable;
    }
    
    function hasCacheTimedOut()
    {
       $hasCacheTimedOut = true;
       return $hasCacheTimedOut;
    }
    
    function readUpdateStatusFromCache() {
        return true;
    }
        
}
