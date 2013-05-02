<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Stephan Dekker <Stephan@SparklingSoftware.com.au>
 */

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'action.php';
require_once(DOKU_PLUGIN.'git/lib/Git.php');

class action_plugin_git_alertupstreamchanges extends DokuWiki_Action_Plugin {

    var $sqlite = null;
    
    function action_plugin_git_alertupstreamchanges(){  
        $this->sqlite =& plugin_load('helper', 'sqlite');
        if (is_null($this->sqlite)) {
            msg('The sqlite plugin could not loaded from the GIT Plugin (Alert Upstream)', -1);
            return false;
        }
        if($this->sqlite->init('git',DOKU_PLUGIN.'git/db/')){
            return $this->sqlite;
        }else{
            return false;
        }
    }

	function register(&$controller) {
		$controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'handler');
 	}
    
    function current_url(){
        $pageURL = 'http';
        if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
        $pageURL .= "://";
        if ($_SERVER["SERVER_PORT"] != "80") {
            $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
        } else {
            $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
        }
        return $pageURL;        
    }
    
	function handler(&$event, $param) {
        global $conf, $ID;
        $this->getConf('');

        $gitRemoteStatusUrl = DOKU_URL.'doku.php?id='.$conf['plugin']['git']['origin_status_page'];
        
        $currentURL = $this->current_url();
        if ($gitRemoteStatusUrl === wl($ID,'',true)) return;  // Skip remote GIT status page, no notification needed when the user is looking at the details.
        if (strpos(strtolower($currentURL), strtolower('mediamanager.php')) > 0) return;  // Skip media manager page as well
        
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

            if ($repo->test_origin() === false) {
               msg('Repository seems to have an invalid remote (origin)');
               return $updatesAvailable;
            }
  
            $repo->fetch();
            $log = $repo->get_log();
                        
            if ($log !== "")
            {   
                $updatesAvailable = true;
                $sql = "INSERT OR REPLACE INTO git (repo, timestamp, status ) VALUES ('upstream', ".time().", 'alert');";
                $this->sqlite->query($sql);
            }
            else
            {
                $sql = "INSERT OR REPLACE INTO git (repo, timestamp, status ) VALUES ('upstream', ".time().", 'clean');";
                $this->sqlite->query($sql);
            }
        }
        else
        {
            $updatesAvailable = $this->readUpdateStatusFromCache();
        }
        return $updatesAvailable;
    }
    
    function hasCacheTimedOut()
    {
        $hasCacheTimedOut = false;

        $res = $this->sqlite->query("SELECT timestamp FROM git WHERE repo = 'upstream';");
        $timestamp = (int) sqlite_fetch_single($res);
        if ($timestamp < time() - (60 * 60))  // 60 seconds x 60 minutes = 1 hour
        { 
            $hasCacheTimedOut = true; 
        }
        
        return $hasCacheTimedOut;
    }
    
    function readUpdateStatusFromCache() {
        $updatesAvailable = true;

        $res = $this->sqlite->query("SELECT status FROM git WHERE repo = 'upstream'");
        $status = sqlite_fetch_single($res);
        if ($status === 'clean') $updatesAvailable = false;
        
        return $updatesAvailable;
    }
        
}
