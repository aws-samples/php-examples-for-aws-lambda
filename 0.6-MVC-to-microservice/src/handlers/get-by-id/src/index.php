<?
 use Aws\DynamoDb\Exception\DynamoDbException;

function index ($event)
{
   $sdk = new Aws\Sdk([
       'region'   => 'eu-west-1',
       'version'  => 'latest'
   ]);
   $dynamodb = $sdk->createDynamoDb();
   $tableName = $_ENV['SAMPLE_TABLE']; // Get the DynamoDB table name from environment variables
   $id=$event['pathParameters']['id'];
   $params = 
   [
   'TableName' => $tableName,
   'Key'=>array('id'=>array('S'=>$id))
   ];

  try{
      $result = $dynamodb->getItem($params);
  }catch(DynamoDbException $e){
      echo 'Unable to get Item: \n';
      echo $e->getMessage().'\n';
  }
   return APIResponse(strval($result));

}

function APIResponse($body)
{
    $headers = array("Content-Type"=>"application/json", "Access-Control-Allow-Origin"=>"*", "Access-Control-Allow-Headers"=>"Content-Type" ,"Access-Control-Allow-Methods" =>"OPTIONS,POST");
    return json_encode(array(
        "statusCode"=>200,
        "headers"=>$headers,
        "body"=>$body
    ));
}
