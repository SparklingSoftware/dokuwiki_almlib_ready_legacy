<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Stephan Dekker <Stephan@SparklingSoftware.com.au>
*/
     
// must be run within DokuWiki
if(!defined('DOKU_INC')) die();
     
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'syntax.php';

    /**
    * All DokuWiki plugins to extend the parser/rendering mechanism
    * need to inherit from this class
    */
class syntax_plugin_rigrr extends DokuWiki_Syntax_Plugin {
     
    function getInfo() {
        return array('author' => 'Stephan Dekker',
                        'email'  => 'Stephan@SparklingSoftware.com.au',
                        'date'   => '2012-12-03',
                        'name'   => 'rigrr',
                        'desc'   => 'allows BPML to be rendered',
                        'url'    => 'http://www.dokuwiki.org/plugin:rigrr');
    }
     
    function getType() { return 'substition'; }
    function getSort() { return 32; }
     
    function connectTo($mode) {  $this->Lexer->addEntryPattern('<rigrr.*?>(?=.*?</rigrr>)',$mode,'plugin_rigrr'); }
    function postConnect() { $this->Lexer->addExitPattern('</rigrr>','plugin_rigrr'); }
     
    function handle($match, $state, $pos, &$handler) {
        return array($match, $state, $pos);
    }
     
    function render($mode, &$renderer, $data) {
    // $data is what the function handle return'ed.
        if($mode == 'xhtml'){
            list($match,$state,$pos) = $data;
            if ($state != DOKU_LEXER_UNMATCHED) return false; 

            $renderer->doc .= '<textarea id="rigrr_bpmn" style="visibility:hidden;">';
            $renderer->doc .= trim($match);
            $renderer->doc .= '</textarea>';
            $renderer->doc .= '<div id="rigrr_canvas"></div>';

            return true;
        }
        return false;
    }
}
