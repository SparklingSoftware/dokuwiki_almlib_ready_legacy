<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Stephan Dekker <stephan@sparklingsoftware.com.au>
 */
 
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
 require_once(DOKU_INC.'inc/search.php');
 
/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_branches_manage extends DokuWiki_Syntax_Plugin {

    var $branch_helper = null;
    
    function syntax_plugin_branches_manage(){        
        $this->branch_helper =& plugin_load('helper', 'branches');
        if (is_null($this->branch_helper)) {
            msg('The branches plugin needs the the branches helper which cannot be loaded', -1);
            return false;
        }                
    }
    
    /**
     * return some info
     */
    function getInfo(){
        return array(
            'author' => 'Stephan Dekker',
            'email'  => 'Stephan@SparklingSoftware.com.au',
            'date'   => @file_get_contents(dirname(__FILE__) . '/VERSION'),
            'name'   => 'branches',
            'desc'   => 'allows the user to switch between multiple working branches',
            'url'    => 'http://dokuwiki.org/plugin:branches',
        );
    }
 
    /**
     * What kind of syntax are we?
     */
    function getType(){
        return 'substition';
    }
 
    /**
     * What about paragraphs?
     */
    function getPType(){
        return 'normal';
    }
 
    /**
     * Where to sort in?
     */
    function getSort(){
        return 990;     //was 990
    }
 
 
    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('~~branches_manage~~',$mode,'plugin_branches_manage');
    }
 
    /**
     * Handle the match
     */
 
    function handle($match, $state, $pos, &$handler){
        $match_array = array();
        return $match_array;
    }    
    
    /**
     * Create output
     */
    function render($format, &$renderer, $data) {
        global $INFO, $conf;

        if($format == 'xhtml'){
        
            try
            {
                $improvements = $this->branch_helper->getInProgressInitiatives();
            }                    
            catch(Exception $e)
            {
                msg($e->getMessage());
            }
            
        
            $branches = $this->branch_helper->getBranches();
            
            $renderer->doc .= '<div class="table"><table>';
            if ($INFO["isadmin"] )
            {
                $renderer->doc .=  "<tr><th>Improvement</th><th>Create</th><th>Edit</th><th>Remove</th></tr>";
            }
            else
            {
                $renderer->doc .=  "<tr><th>Improvement</th><th>Create</th><th>Edit</th></tr>";
            }
            
            foreach ($improvements as $improvement)
            {
                
                $ip_url = $conf['jira_url'].$improvement["key"];
                $go_to_branch_url = "/".$improvement["key"]."/doku.php";
                $create_branch_url = DOKU_URL."doku.php?create_branch=".$improvement["key"];
                $remove_branch_url = DOKU_URL."doku.php?remove_branch=".$improvement["key"];
                
                $renderer->doc .= "<tr>";

                $renderer->doc .= "<td><a href=\"".$ip_url."\">".$improvement["key"]."   ".$improvement["title"]."</a></td>";
                if (in_array($improvement["key"], $branches))
                {
                    // The improvement has a branch
                    $renderer->doc .= "<td></td>";
                    $renderer->doc .= "<td><a href=\"".$go_to_branch_url."\">Contribute to this initiative</a></td>";
                    if ($INFO["isadmin"] )
                    {                        
                        $renderer->doc .= "<td><a href=\"".$remove_branch_url."\">Remove the branch</a></td>";
                    }
                }
                else
                {
                    // No branch exists for the improvement yet
                    $renderer->doc .= "<td><a href=\"".$create_branch_url."\">Create a new workspace</a></td>";
                    $renderer->doc .= "<td></td>";
                    $renderer->doc .= "<td></td>";
                }
                $renderer->doc .= "</tr>";                
            }
            $renderer->doc .= "</table></div>";
            
            
            
            return true;
        }
        return false;
    }
 
}
 
 
//Setup VIM: ex: et ts=4 enc=utf-8 :
?>
