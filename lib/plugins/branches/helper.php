<?php
/**
 * DokuWiki plugin for branches
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Stephan Dekker <Stephan@SparklingSoftware.com.au>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

/**
 * This is the base class for all syntax classes, providing some general stuff
 */
class helper_plugin_branches extends DokuWiki_Plugin {

    var $jira = null;
    var $git = null;
    
    function helper_plugin_branches(){        
        $this->jira =& plugin_load('helper', 'jiradata');
        if (is_null($this->jira)) {
            msg('The branches plugin needs the jiradata plugin which cannot be loaded', -1);
            return false;
        }     
        
        $this->git =& plugin_load('helper', 'git');
        if (is_null($this->git)) {
            msg('The branches plugin needs the git plugin which cannot be loaded', -1);
            return false;
        }   
    }
    
    function createBranch($branch_id)
    {
        $destination = dirname(DOKU_INC).DIRECTORY_SEPARATOR.$branch_id;     
        $origin = '"'.dirname(DOKU_INC).DIRECTORY_SEPARATOR.'Origin"';

        $this->git->cloneRepo($origin, $destination);    
        
        $configfiles = array();
        $configfiles[] = 'local.protected.php';
        foreach ($configfiles as $file)
        {
            copy(dirname(DOKU_INC).DIRECTORY_SEPARATOR.'Config\conf\\'.$file,
                 dirname(DOKU_INC).DIRECTORY_SEPARATOR.$branch_id.'\conf\\'.$file);
        }        
    }

    function getBranches()
    {
        global $conf;
    
        $path = dirname(DOKU_INC); // Look at the root of this website, which is one above this instance
        $fulldirs = glob($path.'/*', GLOB_ONLYDIR);
        
        $dirs = array();
        foreach ($fulldirs as $dirname)
        {
            $prefix = $this->getConf('branch_prefix');   // for instance: "IP-"
            $dir = basename($dirname);
            if (stripos($dir, $prefix) !== 0) continue;
            array_push($dirs, $dir);
        }
        
        return $dirs;
    }
    
    function getInProgressInitiatives()
    {
        if ($this->jira === null) return;
        $jql = 'project = ALM ORDER BY key';
        $improvements = $this->jira->getData($jql);
        return $improvements;
    }

}