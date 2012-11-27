<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Stephan Dekker <stephan@sparklingsoftware.com.au>
 */

if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'action.php';

class action_plugin_branches_removebranch extends DokuWiki_Action_Plugin {

    var $basePath = "";

    function register(&$controller){
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'handle', array());
        $this->basePath = dirname(DOKU_INC);
    }
 
    function handle(&$event, $param){
        global $conf;
        
        $branch_id = $_GET['remove_branch'];
        $redirect_url = $conf['plugin']['branch']['manage_branches_url'];
        if ($redirect_url === '') $redirect_url = "/master"; 
        
        if ($branch_id)
        {
            msg('Removed branch: '.$branch_id);
            $this->rrmdir($this->basePath.DIRECTORY_SEPARATOR.$branch_id);
            
            ptln('<script>url="'.$redirect_url.'";setTimeout("location.href=url",15);</script>');
        }
    }
    
    function rrmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir.DIRECTORY_SEPARATOR.$object) == "dir") {
                        $this->rrmdir($dir.DIRECTORY_SEPARATOR.$object); 
                    }
                    else unlink($dir.DIRECTORY_SEPARATOR.$object);
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }    
}
