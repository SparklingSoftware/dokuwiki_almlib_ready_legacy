<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Stephan Dekker <Stephan@SparklingSoftware.com.au>
 */

if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'git/lib/Git.php');
require_once(DOKU_INC.'inc/search.php');
require_once(DOKU_INC.'/inc/DifferenceEngine.php');

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
    
    function render_commit_selector($renderer, $commits)
    {
        $renderer->doc .= "<select id='git_commit' width=\"800\" style=\"width: 800px\" onchange='ChangeGitCommit();'>";
        $index = 1;
        foreach($commits as $commit)
        {
            $renderer->doc .= "<option value=\"".$commit['hash']."\">".$index." - ".$commit['message']."</option>";
            $index++;
        }
        $renderer->doc .= '</select>';    
    }
    
    function render_changed_files_table($renderer, $commits, $repo)
    {
        $divVisibility = ""; // Make the first div visible as thats the first item in the select box
        foreach($commits as $commit)
        {
            $renderer->doc .= "<div class=\"commit_div\" id='".$commit['hash']."' style=\"".$divVisibility." width: 100%;\">";
            
            // Changes waiting to be commited
            if ($commit == 'new')
            {
                $waiting_to_commit = $repo->get_status();
                $files = explode("\n", $waiting_to_commit);                   
            }
            else   
            {
                // Commits selected to show changes for
                $hash = $commit['hash'];
                $files = explode("\n", $repo->get_files_by_commit($hash));                   
            }            
            
            // No files
            if ($files === null)
            {
               $renderer->doc .= "No files were altered in this commit. Most likely a automated merge?";
            }
            
            $renderer->doc .= "<table><tr><th>Change type</th><th>Page</th><th>Changes</th></tr>";
            foreach ($files as $file)
            {                
                if ($file === "") continue;

                $renderer->doc .= "<tr><td>";
                
                $change = substr($file, 0, 2);
                if (strpos($change, '?') !== false)
                    $renderer->doc .= "Added:";
                else if (strpos($change, 'M') !== false)
                    $renderer->doc .= "Modified:";
                else if (strpos($change, 'A') !== false)
                    $renderer->doc .= "Added:";
                else if (strpos($change, 'D') !== false)
                    $renderer->doc .= "Removed:";
                else if (strpos($change, 'R') !== false)
                    $renderer->doc .= "Removed:";
                else if (strpos($change, 'r') !== false)
                    $renderer->doc .= "Removed:";
                
                $renderer->doc .= "</td><td>";
                $file = trim(substr($file, 2));
                $page = $this->getPageFromFile($file);            
                $renderer->doc .=  '<a href="'.DOKU_URL.'doku.php?id='.$page.'">'.$page.'</a>';
                
                $renderer->doc .= "</td><td>";
                $renderer->doc .= '   <form method="post">';
                $renderer->doc .= '      <input type="hidden" name="filename"  value="'.$file.'" />';
                $renderer->doc .= '      <input type="hidden" name="hash"  value="'.$commit['hash'].'" />';                        
                $renderer->doc .= '      <input type="submit" value="View Changes" />';
                $renderer->doc .= '   </form>';
                $renderer->doc .= "</td>";
                $renderer->doc .= "</tr>";
            }
            $renderer->doc .= "</table>";
            $renderer->doc .= "</div>\n";
            $divVisibility = " display:none;";
        }       
    }
    
    function getPageFromFile($file)
    {
        // If it's not a wiki page, just return the normal filename
        if (strpos($file, 'pages/') === false) return $file;

        // Replace all sorts of stuff so it makes sense to non-technical users.
        $page = str_replace('pages/', '', $file);
        $page = str_replace('.txt', '', $page);
        $page = str_replace('/', ':', $page);
        $page = trim($page);
        
        return $page;
    }
    
    
    function renderChangesMade(&$renderer, &$repo, $mode)
    {
        global $conf;
        $this->getConf('');
        
        $fileForDiff = trim($_REQUEST['filename']);                
        $page = $this->getPageFromFile($fileForDiff);
        $hash = trim($_REQUEST['hash']);    
        if ($fileForDiff !== '')
        {
            //Write header
            $renderer->doc .= '<h2>Changes to: '.$page.'</h2>';

            if ($mode == 'ApproveLocal') {
                $renderer->doc .= 'Left = The page before the selected commited retrieved from GIT <br/>';
                $renderer->doc .= 'Right = The page after the selected commit';
                
                // LEFT: Find the file before for the selected commit
                $l_text = $repo->getFile($fileForDiff, $hash."~1");

                // RIGHT: Find the file for the selected commit
                $r_text = $repo->getFile($fileForDiff, $hash);
            }
            else if ($mode == 'Commit local') {
                $renderer->doc .= 'Left = The last page commited to GIT <br/>';
                $renderer->doc .= 'Right = Current wiki content';
                     
                // LEFT: Latest in GIT
                $l_text = $repo->getFile($fileForDiff, 'HEAD');

                // RIGHT:  Current
                $current_filename = $conf['savedir'].'/'.$fileForDiff;
                $current_filename = str_replace("/", "\\", $current_filename);
                $r_text = $this->getFileContents($current_filename);
            }
            else if ($mode == 'Merge upstream') {
                $renderer->doc .= 'Left = Current wiki content<br/>';
                $renderer->doc .= 'Right = Upstream changes to be merged';

                // LEFT:  Current
                $current_filename = $conf['savedir'].'/'.$fileForDiff;
                $current_filename = str_replace("/", "\\", $current_filename);
                $l_text = $this->getFileContents($current_filename);                     

                // RIGHT: Latest in GIT to be merged
                $l_text = $repo->getFile($fileForDiff, 'HEAD');
            }
                        
            // Show diff
            $df = new Diff(explode("\n",htmlspecialchars($l_text)), explode("\n",htmlspecialchars($r_text)));
            $tdf = new TableDiffFormatter();                
            $renderer->doc .= '<div id="diff_table" class="table">';
            $renderer->doc .= '<table class="diff diff_inline">';
            $renderer->doc .= $tdf->format($df);
            $renderer->doc .= '</table>';
            $renderer->doc .= '</div>';
        }        
    }
    
    function renderAdminApproval(&$renderer)
    {
        $isAdmin = $this->isCurrentUserAnAdmin();
        if ($isAdmin)
        {
            $renderer->doc .= '<form method="post">';
            $renderer->doc .= '   <input type="submit" name="cmd[revert]" value="Reject and revert Approval Submission" />';
            $renderer->doc .= '   <input type="submit" name="cmd[push]" value="Push to live!" />';
            $renderer->doc .= '</form>';
        }
    }
    
    function isCurrentUserAnAdmin()
    {
        global $INFO;
        $grps=array();
        if (is_array($INFO['userinfo'])) {
            foreach($INFO['userinfo']['grps'] as $val) {
                $grps[]="@" . $val;
            }
        }

        
        return in_array("@admin", $grps);
    }
    
    function getFileContents($filename)
    {
        // get contents of a file into a string
        $handle = fopen($filename, "r");
        $contents = fread($handle, filesize($filename));
        fclose($handle);

        return $contents;
    }
}
