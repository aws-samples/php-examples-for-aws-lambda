<?php

// This is a PHP file example.
// Replace it with your application.

$name = $_GET['name'];

// Below is a welcome page written in HTML.

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Welcome to Serverless PHP</title>
    
    <link href="/assets/stylesheet.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Dosis:300&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/tailwindcss@^1.0/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="flex h-screen">
    <div class="topnav">
      <a style="width:100%;" class="active" href="#home">Home</a>
      <a style="width:100%;" href="#news">News</a>
      <a style="width:100%;" href="#contact">Contact</a>
      <a style="width:100%;" href="#about">About</a>
    </div> 
    
    <div class="rounded-full mx-auto self-center relative" style="height:400px; width: 600px;">
        <h1 class="font-light w-full text-center text-blue-900" style="font-family: Dosis; font-size: 45px;">Hi <?php echo ($name ? $name : 'Guest').'!'; ?> <br> Did you know the image below is stored in S3, and served via Amazon CloudFront</h1>
                <img src="/assets/serverless-lamp-stack.png">
        </div>
    </div>
</body>
</html>
 