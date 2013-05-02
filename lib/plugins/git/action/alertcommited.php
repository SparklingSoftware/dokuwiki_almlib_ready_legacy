<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Stephan Dekker <Stephan@SparklingSoftware.com.au>
 */

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'action.php';
require_once(DOKU_PLUGIN.'git/lib/Git.php');


class action_plugin_git_alertcommited extends DokuWiki_Action_Plugin {

    var $sqlite = null;
    
    function action_plugin_git_alertcommited(){  
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
        
        $changesAwaiting = false;
        if ($this->hasCacheTimedOut())
        {
            $repo = new GitRepo($datapath);
            $repo->git_path = $git_exe_path; 
            if ($repo->test_origin() === false) {
                msg('Repository seems to have an invalid remote (origin)');
                return;
            }
            
            $changesAwaiting = $repo->ChangesAwaitingApproval();
            if ($changesAwaiting)
            {
                $sql = "INSERT OR REPLACE INTO git (repo, timestamp, status ) VALUES ('local', ".time().", 'alert');";
                $this->sqlite->query($sql);
            }
            else
            {
                $sql = "INSERT OR REPLACE INTO git (repo, timestamp, status ) VALUES ('local', ".time().", 'clean');";
                $this->sqlite->query($sql);
            }
        }
        else
        {
            $changesAwaiting = $this->readChangesAwaitingFromCache();
        }

        if ($changesAwaiting) {
            msg('Changes waiting to be approved. <a href="'.$gitLocalStatusUrl.'">click here to view changes.</a>');		
        }
	}    
    
    function hasCacheTimedOut()
    {
        $hasCacheTimedOut = false;

        $res = $this->sqlite->query("SELECT timestamp FROM git WHERE repo = 'local';");
        $timestamp = (int) sqlite_fetch_single($res);
        if ($timestamp < time() - (60 * 30))  // 60 seconds x 5 minutes
        { 
            $hasCacheTimedOut = true; 
        }
        
        return $hasCacheTimedOut;
    }
    
    function readChangesAwaitingFromCache() {
        $changesAwaiting = true;

        $res = $this->sqlite->query("SELECT status FROM git WHERE repo = 'local'");
        $status = sqlite_fetch_single($res);
        if ($status === 'clean') $changesAwaiting = false;
        
        return $changesAwaiting;
    }
           
}
