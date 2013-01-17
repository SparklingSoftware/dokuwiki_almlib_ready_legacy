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
        
        $text = trim($_POST['textareaname']);
        msg($text);
        
        // verify valid values
        switch (key($_REQUEST['cmd'])) {
            case 'commit' : 
                if ($this->commit() === false) return; 
                $this->helper->changeReadOnly(true);
                break;
        }   
  	}       
    
    function commit()
    {
        try
        {
            $path = DOKU_INC;
            $repo = new GitRepo($path);
            $result = $repo->commit($msg);
            return $result;
        }
        catch(Exception $e)
        {
            msg($e->getMessage());
            return false;
        }
    }
}