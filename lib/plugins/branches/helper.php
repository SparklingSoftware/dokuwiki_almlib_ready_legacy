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
        global $conf;
        $this->getConf('');
        $debug = false;

        // Get config
        $origin_wiki = $conf['plugin']['branches']['origin_wiki_dir'];
        $origin_data = $conf['plugin']['branches']['origin_data_dir'];

        // Clone wiki
        $origin = '"'.dirname(DOKU_INC).DIRECTORY_SEPARATOR.$origin_wiki.'"';
        $destination = dirname(DOKU_INC).DIRECTORY_SEPARATOR.$branch_id;     
        if ($debug) msg('Cloning from: '.$origin.' To: '.$destination);        
        $this->git->cloneRepo($origin, $destination);    
      
        // Clone data
        $origin = '"'.dirname(DOKU_INC).DIRECTORY_SEPARATOR.$origin_data.'"';
        $destination = dirname(DOKU_INC).DIRECTORY_SEPARATOR.$branch_id.'-Data';        
        if ($debug) msg('Cloning from: '.$origin.' To: '.$destination);  
        $this->git->cloneRepo($origin, $destination);    

        // Apply config
        $configDir = $conf['plugin']['branches']['config_dir'];        
        $configfiles = array();
        $configfiles[] = 'local.protected.php';
        $configfiles[] = 'acl.auth.php';
        $configfiles[] = 'local.php';
        if ($debug) msg('Config dir: '.$configDir);  
        foreach ($configfiles as $file)
        {
            $source = dirname(DOKU_INC).DIRECTORY_SEPARATOR.$configDir.DIRECTORY_SEPARATOR.'conf'.DIRECTORY_SEPARATOR.$file;
            $dest = dirname(DOKU_INC).DIRECTORY_SEPARATOR.$branch_id.DIRECTORY_SEPARATOR.'conf'.DIRECTORY_SEPARATOR.$file;
            if ($debug) msg('Copying configs from: '.$source.' To: '.$dest);  
            copy($source, $dest);

            if ($file === 'local.protected.php' ) {
              if ($debug) msg('replace dest: '.$dest.' branch_id: '.$branch_id);
              $this->replaceConfigSetting($dest, '<<WORKSPACE>>', $branch_id);
            }
        }        
    }

    function replaceConfigSetting($filename, $oldValue, $newValue)
    {
       //read the entire string
       $str=implode("\n",file($filename));

       $fp=fopen($filename,'w');
       $str=str_replace($oldValue, $newValue, $str);

       // rewrite the file
       fwrite($fp,$str,strlen($str));
    }

    function getBranches()
    {
        global $conf;
        $this->getConf('');
    
        $path = dirname(DOKU_INC); // Look at the root of this website, which is one above this instance
        $fulldirs = glob($path.'/*', GLOB_ONLYDIR);
        
        $dirs = array();
        foreach ($fulldirs as $dirname)
        {
            $prefix = $conf['plugin']['branches']['branch_prefix'];   // for instance: "IP-"
            $dir = basename($dirname);
            if (stripos($dir, $prefix) !== 0) continue;
            if (stripos($dir, '-data') !== false) continue;

            array_push($dirs, $dir);
        }
        
        return $dirs;
    }
    
    function getInProgressInitiatives()
    {
        global $conf;
        $this->getConf('');

        if ($this->jira === null) return;
        $jql = $conf['plugin']['branches']['jql'];
        $improvements = $this->jira->getData($jql);
        return $improvements;
    }

}
