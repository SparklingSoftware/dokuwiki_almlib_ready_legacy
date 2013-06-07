<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Stephan Dekker <Stephan@SparklingSoftware.com.au>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
require_once(DOKU_INC.'inc/infoutils.php');

require_once(DOKU_PLUGIN.'jiradata/lib/Autoloader.php');

/**
 * This is the base class for all syntax classes, providing some general stuff
 */
class helper_plugin_jiradata extends DokuWiki_Plugin {

    var $sqlite = null;

    function getMethods(){
        $result = array();
        $result[] = array(
          'name'   => 'getJiraData',
          'desc'   => 'returns jira data based on the jql statement',
          'params' => array(
            'Jira Query (jql)' => 'string'),
          'return' => array('Query result' => 'array'),
        );
        // and more supported methods...
        return $result;
    }
        
    // The summary is the issue title
    function getSummary($key)
    {
        $issue = $this->getIssue($key);
        if (!$summary) return $key;        

        $summary = $issue["title"];
        if (!$summary) return $key;        
        return $summary;        
    }
    
    function getIssue($key) {
    
    
        $cache_has_timed_out = $this->hasLocalCacheTimedOut($key);
        if ($cache_has_timed_out) {
            global $conf;
            $this->getConf('');
            $jira_project = $conf['plugin']['jiradata']['jira_project'];
            $issue_id_prefix = $conf['plugin']['jiradata']['jira_issue_id_prefix'];    

            // Does this key start with the jira issue id prefix ?
            if (strncmp($key, $issue_id_prefix, strlen($issue_id_prefix))) {
               return null; // No               
            }
            
            $jql = "project = ".$jira_project." and key = ".$key;
            msg("jql:".$jql);
            $issues =  $this->getIssues($jql);
        }
        else {
            $issues = $this->getIssuesFromCache($key);
        }
        
        // There should only be one
        return $issues[0];        
    }

    function getAllIssues($flush_cache = false) {
        if ($flush_cache) $this->flushCache();
        
        $cache_has_timed_out = $this->hasLocalCacheTimedOut();
        if ($cache_has_timed_out)
        {
            global $conf;
            $this->getConf('');

            $jira_project = $conf['plugin']['jiradata']['jira_project'];
            $issue_id_prefix = $conf['plugin']['jiradata']['jira_issue_id_prefix'];
            $jql = "project = ".$jira_project." ORDER BY KEY";
            $improvements = $this->getIssues($jql);
        }
        else
        {
            $improvements = $this->getIssuesFromCache();
        }

        return $improvements;
    }
    
    function flushCache() {
    
        $res = $this->loadSqlite();
        if (!$res) 
        {
            msg('Error loading sqlite');
            return;
        }
            
        // Delete all rows in the cache
        $sql = "DELETE FROM jiradata";
        $this->sqlite->query($sql);
    }
    
    // Provide the key when checking a single issue, leave blank to check the oldest issue in the cache
    function getIssuesFromCache($key = "") {
        $result = array();
        
        $res = $this->loadSqlite();
        if (!$res) 
        {
            msg('Error loading sqlite');
            return;
        }
        
        // Get all issue from sqlite
        if ($key === "") $sql = "SELECT key, summary, description FROM jiradata";
        else $sql = "SELECT key, summary, description FROM jiradata WHERE key='".$key."'";        
        
        $res = $this->sqlite->query($sql);
        $datarows = $this->sqlite->res2arr($res);
        foreach($datarows as $datarow) {
               
            $resultrow = array(
                            "key" => $datarow['key'], 
                            "title" => $datarow['summary'], 
                            "description" => $datarow['description']
                        );
            array_push($result, $resultrow); 
        }
        
        return $result;
    }

        
    function getIssues($jql) {
        global $conf;
        $this->getConf('');
        
        $prefix = $conf['plugin']['jiradata']['jira_issue_id_prefix'];        
        $integrationEnabled = $conf['plugin']['jiradata']['jira_integration_enabled'];
        if ($integrationEnabled === 0) {
            $table = array();
            $row = array( "key" => $prefix.'-9999',  "title" => 'JIRA Integration disabled', "description" => 'JIRA Integration disabled');
            array_push($table, $row);                                
            $row = array( "key" => $prefix.'-9998',  "title" => 'JIRA Integration disabled', "description" => 'JIRA Integration disabled');
            array_push($table, $row);                                
            $row = array( "key" => $prefix.'-9997',  "title" => 'JIRA Integration disabled', "description" => 'JIRA Integration disabled');
            array_push($table, $row);                                
            return $table;
        }
                
        $jiraURL = $conf['plugin']['jiradata']['jira_url'];    
        $username = $conf['plugin']['jiradata']['jira_username'];    
        $password = $conf['plugin']['jiradata']['jira_password'];    

        $headers = @get_headers($jiraURL."/rest/api/latest/serverInfo");
        if(strpos($headers[0],'200')===false) {        
            throw new Exception("Error connecting to JIRA: ".$jiraURL);
        }
        
        // Debug info only:
        // $msg = 'Username: '.$username.' Password: '.$password;
        // msg($msg);

        Jira_Autoloader::register();
        $api = new Jira_Api(
            $jiraURL,
            new Jira_Api_Authentication_Basic($username, $password)
        );

        $walker = new Jira_Issues_Walker($api);
        $walker->push($jql, "key, summary, description");
        $walker->valid();        
        
        $table = array();
        foreach ($walker as $issue) {
            $key = $walker->current()->getKey();             
            $summary = $walker->current()->getSummary(); 
            $description = $walker->current()->getDescription(); 
            
            $row = array(
                "key" => $key, 
                "title" => $summary, 
                "description" => $description
            );
            array_push($table, $row);                    

            $this->updateCache($key, $summary, $description);
        }        

        return $table;
    }


    function loadSqlite()
    {
        // Columns:
        // key
        // summary
        // description
        // timestamp
    
        if ($this->sqlite) return true;

        $this->sqlite =& plugin_load('helper', 'sqlite');
        if (is_null($this->sqlite)) {
            msg('The sqlite plugin could not loaded from the jiradata Plugin helper', -1);
            return false;
        }
        if($this->sqlite->init('jiradata',DOKU_PLUGIN.'jiradata/db/')){
            return true;
        }else{
             msg('The jiradata cache failed to initialise.', -1);
            return false;
        }                 
    }
    
    // Provide the key when checking a single issue, leave blank to check the oldest issue in the cache
    function hasLocalCacheTimedOut($key = "")
    {
        $hasCacheTimedOut = false;

        $res = $this->loadSqlite();
        if (!$res) return;
        
        $safe_key = sqlite_escape_string($key);
        if ($safe_key !== "") $sql = "SELECT timestamp FROM jiradata WHERE key = '".$safe_key."' ORDER BY timestamp ASC";
        else $sql = "SELECT timestamp FROM jiradata ORDER BY timestamp ASC";
        
        $res = $this->sqlite->query($sql);
        $rows = $this->sqlite->res2arr($res);
        $timestamp = $rows[0]['timestamp'];
        if ($timestamp < time() - (60 * 300))  // 60 seconds x 5 minutes
        { 
            $hasCacheTimedOut = true; 
        }
        
        return $hasCacheTimedOut;
    }
    
    
    function updateCache($key, $summary, $description) {
        $res = $this->loadSqlite();
        if (!$res) 
        {
            msg('Error loading sqlite');
            return;
        }
        
        $safe_key = sqlite_escape_string($key);
        $safe_summary = sqlite_escape_string($summary);
        $safe_descriptoin = sqlite_escape_string($description);

        // Set the time to zero, so the first alert msg will set the correct status
        $sql = "INSERT OR REPLACE INTO jiradata (key, summary, description, timestamp) VALUES ('".$safe_key."', '".$safe_summary."', '".$safe_description."', ".time().");";
        msg($sql);
        $this->sqlite->query($sql);
    }        

}
