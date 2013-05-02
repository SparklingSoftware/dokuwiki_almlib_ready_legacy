<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Stephan Dekker <Stephan@SparklingSoftware.com.au>
 */

if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'git/lib/Git.php');
require_once(DOKU_INC.'inc/search.php');

function git_callback_search_wanted(&$data,$base,$file,$type,$lvl,$opts) {
    global $conf;

	if($type == 'd'){
		return true; // recurse all directories, but we don't store namespaces
	}
    
    if(!preg_match("/.*\.txt$/", $file)) {  // Ignore everything but TXT
		return true;
	}
    
	// get id of this file
	$id = pathID($file);
    
    $item = &$data["$id"];
    if(! isset($item)) {
        $data["$id"]= array('id' => $id, 
                'file' => $file);
    }
}


class helper_plugin_git extends DokuWiki_Plugin {

    var $dt = null;

    function getMethods(){
        $result = array();
        $result[] = array(
          'name'   => 'cloneRepo',
          'desc'   => 'Creates a new clone of a repository',
          'params' => array(
            'destination' => 'string'),
          'return' => array('result' => 'array'),
        );

        // and more supported methods...
        return $result;
    }

    function rebuild_data_plugin_data() {
        // Load the data plugin only if we need to
        if(!$this->dt)
        {
            $this->dt =& plugin_load('syntax', 'data_entry');
            if(!$this->dt)
            {
                msg('Error loading the data table class from GIT Helper. Make sure the data plugin is installed.',-1);
                return;
            }
        }

        global $conf;
        $result = '';
        $data = array();
        search($data,$conf['datadir'],'git_callback_search_wanted',array('ns' => $ns));

        $output = array();        
        foreach($data as $entry) {
        
            // Get the content of the file
            $filename = $conf['datadir'].$entry['file'];
            if (strpos($filename, 'syntax') > 0) continue;  // Skip instructional pages
            $body = @file_get_contents($filename);
                       
            // Run the regular expression to get the dataentry section
            $pattern = '/----.*dataentry.*\R----/s';
            if (preg_match($pattern, $body, $matches) === false) {
                continue;
            }

            foreach ($matches as $match) {
                
                // Re-use the handle method to get the formatted data
                $cleanedMatch = htmlspecialchars($match);             
                $dummy = "";
                $formatted = $this->dt->handle($cleanedMatch, null, null, $dummy);
                $output['id'.count($output)] = $formatted;                  

                // Re-use the save_data method to .... (drum roll) save the data. 
                // Ignore the returned html, just move on to the next file
                $html = $this->dt->_saveData($formatted, $entry['id'], 'Title'.count($output));
            }
        }
        
        msg('Data entry plugin found and refreshed all '.count($output).' entries after merging.');
    }    
    
    function cloneRepo($origin, $destination) {
        global $conf;
        $this->getConf('');
        $git_exe_path = $conf['plugin']['git']['git_exe_path'];
        
        try
        {
            $repo = new GitRepo($destination, true, false);
            $repo->git_path = $git_exe_path;
            $repo->clone_from($origin);
        }
        catch (Exception $e)
        {
            msg($e->getMessage());
        }
    }
    
    function changeReadOnly($readonly = true)
    {
        global $config_cascade;
        
        $AUTH_ACL = file($config_cascade['acl']['default']);

        $lines = array();
        foreach($AUTH_ACL as $line){
            if(strpos(strtolower($line), strtolower('@USER')) === FALSE)
            {
                $lines[] = $line;
                continue;
            }

            if ($readonly)
            {
                $lines[] = '*               @user         '.AUTH_READ;
            }
            else
            {
                $lines[] = '*               @user         '.AUTH_DELETE;
            }
            
            $lines[] = $replaced;
        }

        // save it
        io_saveFile($config_cascade['acl']['default'], join('',$lines));
    }
}
