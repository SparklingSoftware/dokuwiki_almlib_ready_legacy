<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Stephan Dekker <Stephan@SparklingSoftware.com.au>
 */

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'action.php';
require_once(DOKU_PLUGIN.'git/lib/Git.php');


class action_plugin_git_commit extends DokuWiki_Action_Plugin {
    
    var $helper = null;
    var $sqlite = null;
    
    function action_plugin_git_commit(){  
        $this->helper =& plugin_load('helper', 'git');
        if (is_null($this->helper)) {
            msg('The GIT plugin could not load its helper class', -1);
            return false;
        } 
    }
    
	function register(&$controller) {
		$controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, '_handle');
    }
    
	function _handle(&$event, $param) {

        if ($_REQUEST['cmd'] === null) return;
        
        $commit_message = trim($_POST['CommitMessage']);
        
        // verify valid values
        switch (key($_REQUEST['cmd'])) {
            case 'commit_current' : 
                if ($this->commit($commit_message) === false) return; 
                break;
            case 'commit_submit' : 
                if ($this->commit($commit_message) === false) return; 
                $this->helper->changeReadOnly(true);
                $this->sendNotificationEMail();
                break;
        }   
  	}       
      
    function sendNotificationEMail()
    {
        global $conf;
        $this->getConf('');
        
        $notify = $conf['plugin']['git']['commit_notifcations']; 
        $local_status_page = wl($conf['plugin']['git']['local_status_page'],'',true);
        
        $mail = new Mailer();
        $mail->to($notify);
        $mail->subject('An improvement has been submitted for approval!');
        $mail->setBody('Please review the proposed changes before the next meeting: '.$local_status_page);
        
        return $mail->send();
    }
    
    function commit($commit_message)
    {
        try
        {
            global $conf;
            $this->getConf('');
            
            if (!$this->sqlite)
            {
                $this->initCache();
                if (!$this->sqlite)
                {
                   msg('Commiting changes failed as the cache failed to initialise.', -1);
                   return;
                }
            }

            $git_exe_path = $conf['plugin']['git']['git_exe_path'];        
            $datapath = $conf['savedir'];    
            
            $repo = new GitRepo($datapath);
            $repo->git_path = $git_exe_path;        
            $result = $repo->commit($commit_message);
            
            $sql = "INSERT OR REPLACE INTO git (repo, timestamp, status ) VALUES ('local', ".time().", 'alert');";
            $this->sqlite->query($sql);
            
            return $result;
        }
        catch(Exception $e)
        {
            msg($e->getMessage());
            return false;
        }
    }
    
    function initCache()
    {
        $this->sqlite =& plugin_load('helper', 'sqlite');
        if (is_null($this->sqlite)) {
            msg('The sqlite plugin could not loaded from the GIT Plugin (Commit)', -1);
            return false;
        }
        if($this->sqlite->init('git',DOKU_PLUGIN.'git/db/')){
            return $this->sqlite;
        }else{
            return false;
        }
    }

}