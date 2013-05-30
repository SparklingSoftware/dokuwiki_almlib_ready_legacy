<?php
/**
 * Keywords Plugin
 *
 * Specifies keywords list for the page
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author  Ilya Lebedev <ilya@lebedev.net>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_keywords_keywords extends DokuWiki_Syntax_Plugin {

  /**
   * return some info
   */
  function getInfo(){
      preg_match("#^.+Keywords[/.]([^\\/]+)#","$HeadURL", $v);
      $v = preg_replace("#.*?((trunk|\.v)[\d.]+)#","\\1",$v[1]);
      $b = preg_replace("/\\D/","", " $Rev: 104 $ ");
      return array( 'author' => "Ilya Lebedev"
                   ,'email'  => 'ilya@lebedev.net'
                   ,'date'   => preg_replace("#.*?(\d{4}-\d{2}-\d{2}).*#","\\1",'$Date: 2007-11-25 17:50:54 +0300 (Вск, 25 Ноя 2007) $')
                   ,'name'   => "Keywords {$v}.$b."
                   ,'desc'   => "Defines keywords for the page, using {{keywords>word1 word2 wordN}} syntax."
                   ,'url'    => 'http://wiki.splitbrain.org/plugin:keywords'
                  );
  }

  function getType(){ return 'substition'; }
  function getPType(){ return 'block'; }
  function getSort(){ return 110; }

  /**
   * Connect pattern to lexer
   */
  function connectTo($mode){
      if ($mode == 'base'){
          $this->Lexer->addSpecialPattern('{{keywords>.+?}}',$mode,'plugin_keywords_keywords');
      }
  }
  /**
   * Handle the match
   */
  function handle($match, $state, $pos, &$handler){
      return explode(" ",preg_replace("/{{keywords>(.*?)}}/","\\1",$match));
  }  
 
  /**
   *  Render output
   */
  function render($mode, &$renderer, $data) {
      switch ($mode) {
          case 'metadata' :
              /*
              *  mark metadata with found value
              */
              $renderer->meta['keywords'] = ",".join(",",$data);
              return true;
              break;
    }
    return false;
  }


}
