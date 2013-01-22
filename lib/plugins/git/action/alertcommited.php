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
		$controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, '_hook_header');
 	}
    
	function _hook_header(&$event, $param) {
        global $conf, $INFO;
        $this->getConf();

        $git_exe_path = $conf['plugin']['git']['git_exe_path'];        
        $gitStatusUrl = DOKU_URL.'doku.php?id='.$conf['plugin']['git']['git_exe_path'];
        $datapath = $conf['savedir'];    
        
        $repo = new GitRepo($datapath);
        $repo->git_path = $git_exe_path;        
        $show = $repo->ChangesAwaitingApproval();

        if ($show && $INFO['isadmin']) {
           msg('Changes waiting to be approved. <a href="'.$gitStatusUrl.'">click here to view changes.</a>');		
        }
	}    
           
}
