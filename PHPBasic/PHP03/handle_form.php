<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POST请求的数据获取</title>
</head>
<body>

    <?php

     $title = $_POST['name'];  //获取表单的数据  前端数据传递到后端

     $response = $_POST['response'];


     print($response);

     print("\n");
     print($title);

    
    ?>

    
</body>
</html>