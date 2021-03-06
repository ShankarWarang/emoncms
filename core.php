<?php

/*

    All Emoncms code is released under the GNU Affero General Public License.
    See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org

*/

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

function get_application_path()
{
    // Default to http protocol
    $proto = "http";

    // Detect if we are running HTTPS or proxied HTTPS
    if (server('HTTPS') == 'on') {
        // Web server is running native HTTPS
        $proto = "https";
    } elseif (server('HTTP_X_FORWARDED_PROTO') == "https") {
        // Web server is running behind a proxy which is running HTTPS
        $proto = "https";
    }

    if( isset( $_SERVER['HTTP_X_FORWARDED_SERVER'] ))
        $path = dirname("$proto://" . server('HTTP_X_FORWARDED_SERVER') . server('SCRIPT_NAME')) . "/";
    else
        $path = dirname("$proto://" . server('HTTP_HOST') . server('SCRIPT_NAME')) . "/";

    return $path;
}

function db_check($mysqli,$database)
{
    $result = $mysqli->query("SELECT count(table_schema) from information_schema.tables WHERE table_schema = '$database'");
    $row = $result->fetch_array();
    if ($row['0']>0) return true; else return false;
}

function controller($controller_name)
{
    $output = array('content'=>"#UNDEFINED#");

    if ($controller_name)
    {
        $controller = $controller_name."_controller";
        $controllerScript = "Modules/".$controller_name."/".$controller.".php";
        if (is_file($controllerScript))
        {
            // Load language files for module
            $domain = "messages";
            bindtextdomain($domain, "Modules/".$controller_name."/locale");
            bind_textdomain_codeset($domain, 'UTF-8');
            textdomain($domain);

            require_once $controllerScript;
            $output = $controller();
            if (!is_array($output) || !isset($output["content"])) $output = array("content"=>$output);
        }
    }
    return $output;
}

function view($filepath, array $args)
{
    extract($args);
    ob_start();
    include "$filepath";
    $content = ob_get_clean();
    return $content;
}

function get($index)
{
    $val = null;
    if (isset($_GET[$index])) $val = $_GET[$index];
    
    if (get_magic_quotes_gpc()) $val = stripslashes($val);
    return $val;
}

function post($index)
{
    $val = null;
    if (isset($_POST[$index])) $val = $_POST[$index];
    
    if (get_magic_quotes_gpc()) $val = stripslashes($val);
    return $val;
}

function prop($index)
{
    $val = null;
    if (isset($_GET[$index])) $val = $_GET[$index];
    if (isset($_POST[$index])) $val = $_POST[$index];
    
    if (get_magic_quotes_gpc()) $val = stripslashes($val);
    return $val;
}


function server($index)
{
    $val = null;
    if (isset($_SERVER[$index])) $val = $_SERVER[$index];
    return $val;
}

function delete($index) {
    parse_str(file_get_contents("php://input"),$_DELETE);//create array with posted (DELETE) method) values
    $val = null;
    if (isset($_DELETE[$index])) $val = $_DELETE[$index];
    
    if (get_magic_quotes_gpc()) $val = stripslashes($val);
    return $val;
}
function put($index) {
    parse_str(file_get_contents("php://input"),$_PUT);//create array with posted (PUT method) values
    $val = null;
    if (isset($_PUT[$index])) $val = $_PUT[$index];
    
    if (get_magic_quotes_gpc()) $val = stripslashes($val);
    return $val;
}

function version(){
    $version_file = file_get_contents('./version.txt');
    $version = filter_var($version_file, FILTER_SANITIZE_STRING);
    return $version;
}


function load_db_schema()
{
    $schema = array();
    $dir = scandir("Modules");
    for ($i=2; $i<count($dir); $i++)
    {
        if (filetype("Modules/".$dir[$i])=='dir' || filetype("Modules/".$dir[$i])=='link')
        {
            if (is_file("Modules/".$dir[$i]."/".$dir[$i]."_schema.php"))
            {
               require "Modules/".$dir[$i]."/".$dir[$i]."_schema.php";
            }
        }
    }
    return $schema;
}

function load_menu()
{
    $menu_dashboard = array(); // Published Dashboards
    $menu_left = array();  // Left
    $menu_dropdown = array(); // Extra
    $menu_dropdown_config = array(); //Setup
    $menu_right = array(); // Right

    $dir = scandir("Modules");
    for ($i=2; $i<count($dir); $i++)
    {
        if (filetype("Modules/".$dir[$i])=='dir' || filetype("Modules/".$dir[$i])=='link')
        {
            if (is_file("Modules/".$dir[$i]."/".$dir[$i]."_menu.php"))
            {
                require "Modules/".$dir[$i]."/".$dir[$i]."_menu.php";
            }
        }
    }

    return array('dashboard'=>$menu_dashboard, 'left'=>$menu_left, 'dropdown'=>$menu_dropdown, 'dropdownconfig'=>$menu_dropdown_config, 'right'=>$menu_right);
}

function http_request($method,$url,$data) {

    $options = array();
    $urlencoded = http_build_query($data);
    
    if ($method=="GET") { 
        $url = "$url?$urlencoded";
    } else if ($method=="POST") {
        $options[CURLOPT_POST] = 1;
        $options[CURLOPT_POSTFIELDS] = $data;
    }
    
    $options[CURLOPT_URL] = $url;
    $options[CURLOPT_RETURNTRANSFER] = 1;
    $options[CURLOPT_CONNECTTIMEOUT] = 2;
    $options[CURLOPT_TIMEOUT] = 5;

    $curl = curl_init();
    curl_setopt_array($curl,$options);
    $resp = curl_exec($curl);
    curl_close($curl);
    return $resp;
}

function emoncms_error($message) {
    return array("success"=>false, "message"=>$message);
}

function call_hook($function_name, $args){
    $dir = scandir("Modules");
    for ($i=2; $i<count($dir); $i++)
    {
        if (filetype("Modules/".$dir[$i])=='dir' || filetype("Modules/".$dir[$i])=='link')
        {
            if (is_file("Modules/".$dir[$i]."/".$dir[$i]."_hooks.php"))
            {
                require "Modules/".$dir[$i]."/".$dir[$i]."_hooks.php";
                if (function_exists($dir[$i].'_'.$function_name)==true){
                    $hook = $dir[$i].'_'.$function_name;
                    return $hook($args);
                }
            }   
        }
    }
}