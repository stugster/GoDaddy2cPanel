<script src="https://code.jquery.com/jquery-1.12.4.min.js"  integrity="sha256-ZosEbRLbNQzLpnKIkEdrPv7lOy9C27hHQ+Xp8a4MxAQ="  crossorigin="anonymous"></script>

<style>
 body { font-size: 14px; }
 h4 { font-size: 14px; background: #000; color: #fff; padding: 10px; }
 h5 { font-size: 14px; background: #ff0000; color: #fff; padding: 10px; }
 h6 { font-size: 14px; color: #ff0000;  }
</style>

<div id="output">

<?php

// Cpanel Details
$cpanel_ip = "123.123.123.123";
$cpanel_user = "root";
$cpanel_pass = "myRootPassHere";

// GoDaddy Details
$godaddy_key = "INSERT_GODADDY_API_KEY";
$godaddy_secret = ""INSERT_GODADDY_API_KEY";
$godaddy_shopper_id = "123456789";

// Other Options
$domain_txt = "domains.txt";    // Name of domains.txt file
$livemode = 1;                  // Set to '1' for Live Mode.
$output = 1;                    // Set to '1' to Show Output.

// DNS Types supported by GoDaddy
$types = array();
$types[] = "A";
$types[] = "AAAA";
$types[] = "CNAME";
$types[] = "MX";
$types[] = "SRV";
$types[] = "TXT";
// $types[] = "SOA";
// $types[] = "NS";

// Read in Domains from domains.txt
$domains = array();
$domains = file($domain_txt, FILE_IGNORE_NEW_LINES);

// Create WHM Link
include("xmlapi.php");
$xmlapi = new xmlapi($cpanel_ip);
$xmlapi->password_auth($cpanel_user, $cpanel_pass);
$xmlapi->set_debug(0);

// Create GoDaddy Link
$ch = curl_init();
$headers = array();
$headers[] = 'Authorization: sso-key ' . $godaddy_key . ":" . $godaddy_secret;
$headers[] = 'X-Shopper-Id: ' . $godaddy_shopper_id ;
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$domain_count = count($domains);
$domain_counter = 0;

foreach ($domains as &$domain) {

        $domain_counter++;
        $domain = strtolower($domain);

        if ($output == "1") {
                echo "<h4>Processing " . $domain . " (" . $domain_counter . "/" . $domain_count . ")";
                if ($livemode != "1") { echo " (TEST MODE)"; }
                echo "</h4>";
        }

        $err = "TOO_MANY_REQUESTS";

        echo '<div id="error' . $domain_counter . '"></div>';

        while ($err == "TOO_MANY_REQUESTS") {

                curl_setopt($ch, CURLOPT_URL,"https://api.godaddy.com/v1/domains/" . $domain . "/records/A");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $server_output = curl_exec ($ch);
                $array = json_decode($server_output, true);
                $err = $array['code'];

                if (isset($array['code'])) {
                        echo '<script>$("#error' . $domain_counter . '").html("<h6>' . $array['code'] . ' - ' .  $array['message'] . '</h6>"); </script>';
                }

        }

        if (isset($array['code'])) {
                // echo "<h6>" . $array['code'] . " - " .  $array['message'] . "</h6>";
        } else {
                echo '<script>$("#error' . $domain_counter . '").hide(); </script>';

                $args = array();
                $mainip = "";

                foreach ($types as &$type) {

                        curl_setopt($ch, CURLOPT_URL,"https://api.godaddy.com/v1/domains/" . $domain . "/records/" . $type);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        $server_output = curl_exec ($ch);
                        $array = json_decode($server_output, true);

                        foreach ($array as $key => $value) {
                                $name = $value['name'];
                                $data = $value['data'];
                                $ttl = $value['ttl'];
                                $priority = $value['priority'];
                                $service = $value['service'];
                                $protocol = $value['protocol'];
                                $port = $value['port'];
                                $weight = $value['weight'];
                        
                                if ($name == "@") { $name = $domain . "."; }
                                if ($data == "@") { $data = $domain . "."; }

                                switch ($type) {
                                        case "A":
                                                if ( ($value['name'] == "@") && ($type == "A") ) {              // Main A Record
                                                        $mainip = $value['data'];
                                                } else {
                                                        $args[] = array(
                                                          'domain' => $domain,
                                                          'name'=> $name,
                                                          'class'=>'IN',
                                                          'ttl'=> $ttl,
                                                          'type'=> $type,
                                                          'address' => $data
                                                        );
                                                }
                                                break;
                                        case "AAAA":
                                                $args[] = array(
                                                  'domain' => $domain,
                                                  'name'=> $name,
                                                  'class'=>'IN',
                                                  'ttl'=> $ttl,
                                                  'type'=> $type,
                                                  'address' => $data
                                                );
                                                break;
                                        case "CNAME":
                                                $args[] = array(
                                                  'domain' => $domain,
                                                  'name'=> $name,
                                                  'cname'=> $data,
                                                  'class'=>'IN',
                                                  'ttl'=> $ttl,
                                                  'type'=> $type,
                                                );
                                                break;
                                        case "MX":
                                                $args[] = array(
                                                  'domain' => $domain,
                                                  'name'=> $name,
                                                  'class'=>'IN',
                                                  'ttl'=> $ttl,
                                                  'type'=> $type,
                                                  'preference' => $priority,
                                                  'exchange' => $data
                                                );
                                                break;
                                case "NS":
                                                $args[] = array(
                                                  'domain' => $domain,
                                                  'name'=> $name,
                                                  'class'=>'IN',
                                                  'ttl'=> $ttl,
                                                  'type'=> $type,
                                                  'nsdname' => $data
                                                );
                                                break;
                                        case "SOA":
                                                break;
                                        case "SRV":
                                                $args[] = array(
                                                  'domain' => $domain,
                                                  'name'=> $service . "." . $protocol,
                                                  'class'=>'IN',
                                                  'ttl'=> $ttl,
                                                  'type'=> $type,
                                                  'priority' => $priority,
                                                  'weight' => $weight,
                                                  'port' => $port,
                                                  'target' => $data
                                                );
                                                break;
                                        case "TXT":
                                                $args[] = array(
                                                  'domain' => $domain,
                                                  'name'=> $name,
                                                  'class'=>'IN',
                                                  'ttl'=> $ttl,
                                                  'type' => $type,
                                                  'txtdata' => '"' . $data . '"'
                                                );
                                                break;
                                }
                        }

                }
               // Kill existing DNS Zone
                if ($livemode == "1") {
                        print $xmlapi->killdns($domain);
                }

                // Create New DNS Zone using "simple" template
                if ($livemode == "1") {
                        print $xmlapi->adddns($domain, $mainip, "simple");
                }

                // Add DNS Zone Records
                foreach ($args as &$arg) {
                                if ($livemode == "1") {
                                        print $xmlapi->addzonerecord( $domain, $arg );
                                }
                                if ($output == "1") {
                                        print_r($arg); echo "<br/>";
                                }
                }
        }

}

echo "<h5>COMPLETED</h5>";

curl_close ($ch);
?>
</div>
