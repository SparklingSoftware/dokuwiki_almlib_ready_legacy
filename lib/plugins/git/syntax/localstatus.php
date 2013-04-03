<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Stephan Dekker <Stephan@SparklingSoftware.com.au>
 */
 
require_once(DOKU_PLUGIN.'syntax.php');
require_once(DOKU_PLUGIN.'git/lib/Git.php');


/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_git_localstatus extends DokuWiki_Syntax_Plugin {
    
    var $helper = null;

    function syntax_plugin_git_localstatus (){
        $this->helper =& plugin_load('helper', 'git');
        if(!$this->helper) msg('Loading the git helper in the git_localstatus class failed.', -1);
    }

    /**
     * return some info
     */
    function getInfo(){
        return array(
            'author' => 'Stephan Dekker',
            'email'  => 'Stephan@SparklingSoftware.com.au',
            'date'   => @file_get_contents(dirname(__FILE__) . '/VERSION'),
            'name'   => 'Git Remote Status Plugin',
            'desc'   => 'xml',
            'url'    => 'http://dokuwiki.org/plugin:git',
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
        $this->Lexer->addSpecialPattern('~~GITLocalStatus~~',$mode,'plugin_git_localstatus');
    }
 
    /**
     * Handle the match
     */
    function handle($match, $state, $pos, &$handler){
        $match_array = array();
        $match = substr($match,11,-2); //strip ~~REVISIONS: from start and ~~ from end
        // Wolfgang 2007-08-29 suggests commenting out the next line
        // $match = strtolower($match);
        //create array, using ! as separator
        $match_array = explode("!", $match);
        // $match_array[0] will be all, or syntax error
        // this return value appears in render() as the $data param there
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
                // If not logged in, go bugger off...
                if (is_array($INFO['userinfo']) === false)
                {
                    $renderer->doc .= "<br/><br/>You need to be logged in to view this page. Please login.";
                    return;
                }
            
                // Get GIT commits
                $this->getConf('');
                $git_exe_path = $conf['plugin']['git']['git_exe_path'];
                $datapath = $conf['savedir'];    
                $repo = new GitRepo($datapath);
                $repo->git_path = $git_exe_path;                

                // Is there anything waiting to be commited?
                $waiting_to_commit = $repo->get_status();
                if ($waiting_to_commit !== "") 
                {                    
                    $renderer->doc .= '<h3>These are the files that have been changed:</h3>';
                    $commits = array();
                    $commits[] = "new";
                    $this->helper->render_changed_files_table($renderer, $commits, $repo);                    
                    $this->helper->renderChangesMade($renderer, $repo, false);
                    
                    $this->renderCommitMessage($renderer, $repo);
                    
                    return;
                }
                
                // No local changes to be committed. Are we ready to push up?
                if ($repo->ChangesAwaitingApproval())
                {
                    $renderer->doc .= '<h3>There are commits awaiting to the be pushed to Live!</h3>';        

                    $renderer->doc .= '<p>One or more commits have been made to this workspace that are ready to be promoted to Live!<br/>';
                    $renderer->doc .= 'These can include merges with changes made in other workspaces and approved by the SEPG.</p>';        
                    $renderer->doc .= '<p>Please select a commit in the drop down list to view the changes contained in each.</p>';        

                    // Get the list of commits since the last push
                    $log = $repo->get_log('origin/master..HEAD');
                    $commits = $repo->get_commits($log);

                    // Render combo-box
                    $renderer->doc .= '<br/><h3>Commits:</h3>';     
                    $this->helper->render_commit_selector($renderer, $commits);

                    $renderer->doc .= '<br/><h3>This is the content of the selected commit:</h3>';
                    $this->helper->render_changed_files_table($renderer, $commits, $repo);
                    $this->helper->renderChangesMade($renderer, $repo, true);
                    
                    $renderer->doc .= '<br/><h3>Possible actions:</h3>';
                    $this->helper->renderAdminApproval($renderer);
                    return;
                }                    

                // None of the above, so there is nothing to do... bugger...
                $renderer->doc .= "No changes made in this workspace";
            }
            catch(Exception $e)
            {
                $renderer->doc .= 'Exception happened:<br/>';
                $renderer->doc .= $e->getMessage();
            }
 
            return true;
        }
        return false;
    }
    

    
    function renderCommitMessage(&$renderer, $repo)
    {
        $repo->fetch();
        $log = $repo->get_log();
        if ($log !== "") $updatesAvailable = true;
        else $updatesAvailable = false;
    
        $renderer->doc .= "<h3>Please provide a detailed summary of the changes to be submitted:</h3>";
        $renderer->doc .= '<form method="post">';
        $renderer->doc .= '  <textarea name="CommitMessage" style="width: 800px; height: 80px;" ></textarea></br>';
        $renderer->doc .= '  <input type="submit" name="cmd[commit_current]"  value="Commit Current Changes" />';
        $renderer->doc .= '  <input type="submit" name="cmd[commit_submit]"  value="Commit and Submit for approval" ';
        if ($updatesAvailable) $renderer->doc .= ' disabled />';
        else $renderer->doc .= '  />';
        $renderer->doc .= '</form><br/>';
        $renderer->doc .= '<p>(Please note that submitting the changes for approval will make this workspace read-only)</p>';                
    }
    


    
}
 
 
//Setup VIM: ex: et ts=4 enc=utf-8 :
?>
