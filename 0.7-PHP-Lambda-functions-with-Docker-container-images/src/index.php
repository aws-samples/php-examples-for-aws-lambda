<?php

//hello function
function index($data)
{
    return APIResponse("Hello, ". $data['queryStringParameters']['name']);
}

function APIResponse($body)
{
    $headers = array(
        "Content-Type"=>"application/json",
        "Access-Control-Allow-Origin"=>"*",
        "Access-Control-Allow-Headers"=>"Content-Type",
        "Access-Control-Allow-Methods" =>"OPTIONS,POST"
    );
    return json_encode(array(
        "statusCode"=>200,
        "headers"=>$headers,
        "body"=>$body
    ));
}