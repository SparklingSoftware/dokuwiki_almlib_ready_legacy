<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Stephan Dekker <Stephan@SparklingSoftware.com.au>
 */

if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'git/lib/Git.php');

class helper_plugin_git extends DokuWiki_Plugin {

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

    
    function cloneRepo($origin, $destination) {
        global $conf;
        
        try
        {
            $repo = new GitRepo($destination, true, false);
            $repo->clone_from($origin);
        }
        catch (Exception $e)
        {
            msg($e->getMessage());
        }
    }
}
