<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Stephan Dekker <stephan@sparklingsoftware.com.au>
 */

if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'action.php';

class action_plugin_branches_createnewbranch extends DokuWiki_Action_Plugin {

    var $branch_helper = null;
    
    function action_plugin_branches_createnewbranch(){        
        $this->branch_helper =& plugin_load('helper', 'branches');
        if (is_null($this->branch_helper)) {
            msg('The branches plugin needs the the branches helper which cannot be loaded', -1);
            return false;
        }                
    }

    function register(&$controller){
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'handle', array());
    }
 
    function handle(&$event, $param){
        $branch_id = $_GET['create_branch'];
        if ($branch_id)
        {
            $this->branch_helper->createBranch($branch_id);
            msg('Created branch: '.$branch_id);
            
            ptln('<script>url="/'.$branch_id.'";setTimeout("location.href=url",15);</script>');
        }
    }
        
}
