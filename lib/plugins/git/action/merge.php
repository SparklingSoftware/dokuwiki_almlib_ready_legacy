<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Stephan Dekker <Stephan@SparklingSoftware.com.au>
 */

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'action.php';
require_once(DOKU_PLUGIN.'git/lib/Git.php');


class action_plugin_git_merge extends DokuWiki_Action_Plugin {
    
	function register(&$controller) {
		$controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, '_handle');
    }
    
	function _handle(&$event, $param) {

        if ($_REQUEST['cmd'] === null) return;
        
        // verify valid values
        switch (key($_REQUEST['cmd'])) {
//            case 'merge' : $this->merge(); break;
            case 'merge' : $this->pull(); break;
            case 'ignore' : $this->ignore(); break;
        }   
  	}       
    
    function merge()
    {
       try {
            global $conf;
            $this->getConf('');

            $git_exe_path = $conf['plugin']['git']['git_exe_path'];        
            $datapath = $conf['savedir'];    
            $hash = $_REQUEST['hash'];
            
            $repo = new GitRepo($datapath);
            $repo->git_path = $git_exe_path;   
            $repo->merge($hash);
       }
       catch(Exception $e)
       {
          msg($e->getMessage());
          return false;
       }
    }

    function pull()
    {
        try {
            global $conf;
            $this->getConf('');

            $git_exe_path = $conf['plugin']['git']['git_exe_path'];        
            $datapath = $conf['savedir'];    
            
            $repo = new GitRepo($datapath);
            $repo->git_path = $git_exe_path;   
            $repo->pull('', '');
        }
        catch(Exception $e)
        {
            msg($e->getMessage());
            return false;
        }
    }


    function ignore()
    {
        $hash = $_REQUEST['hash'];        
        //$this->output('Ignoring: '.$hash);
    }
}