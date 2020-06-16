<?php
use Aws\Exception\AwsException;
use Aws\Rds\AuthTokenGenerator;
use Aws\Credentials\CredentialProvider;

function index($data){

$proxyHost=getenv('proxyHost');
$username = getenv('username');
$db=getenv('db');
$port=getenv('port');
$region=getenv('region');


// Use the default credential provider
$provider = CredentialProvider::defaultProvider();
$RdsAuthGenerator = new Aws\Rds\AuthTokenGenerator($provider);

//Get an access Token
$token = $RdsAuthGenerator->createToken($proxyHost. ":" .$port , $region, $username);

$mysqli = mysqli_init();

//Connect to Proxy using acces token
$mysqli->real_connect($proxyHost, $username, $token, $db, $port, NULL, MYSQLI_CLIENT_SSL);

if ($mysqli->connect_errno) {
    echo "Error: Failed to make a MySQL connection, here is why: <br />";
    echo "Errno: " . $mysqli->connect_errno . "<br />";
    echo "Error: " . $mysqli->connect_error . "<br />";
    exit;
}

/***** Example code to perform a query and return all tables in the DB *****/
$res = mysqli_query($mysqli,"SHOW TABLES");
while($cRow = mysqli_fetch_array($res))
{
    $tables[] = $cRow;
}
echo '<pre>';
print_r($tables);
echo '</pre>';
$mysqli -> close();

return json_encode($tables);

}