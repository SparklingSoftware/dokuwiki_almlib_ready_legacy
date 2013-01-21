<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Stephan Dekker <Stephan@SparklingSoftware.com.au>
 */
 
require_once(DOKU_PLUGIN.'syntax.php'); 
  
class syntax_plugin_branches_select extends DokuWiki_Syntax_Plugin {

    var $branch_helper = null;
    
    function syntax_plugin_branches_select(){        
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
            'desc'   => 'allows the user to switch between multiple DokuWiki instances',
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
        $this->Lexer->addSpecialPattern('~~branches_select~~',$mode,'plugin_branches_select');
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

            $renderer->doc .= "<select id='Improvement' onchange='ChangeBranche();'>";
            $renderer->doc .= "<option>Select a different branch</option>";
            $renderer->doc .= "<option>master</option>";
            $branches = $this->branch_helper->getBranches();
            foreach ($branches as $branche)
            {
                $renderer->doc .= "<option>".$branche."</option>";
            }
            $renderer->doc .= "<option>Create new</option>";
            $renderer->doc .= "</select></br>";

            return true;
        }
        return false;
    }
 
}
 
 
//Setup VIM: ex: et ts=4 enc=utf-8 :
?>
