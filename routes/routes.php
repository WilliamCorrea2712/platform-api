<?php
require_once __DIR__ . "/../security/token.php"; 

$routes = array(
  "addUser" => "addUser",
  "editUser" => "editUser",
  "deleteUser" => "deleteUser",
  "getUsers" => "getUsers"
);
if (isset($_GET['route']) && isset($routes[$_GET['route']])) {
  $route = $_GET['route'];
  $handler = $routes[$route];

  if($route !== 'addUser'){
    $user_id = verifyToken()->user_id;
  }
  
  require_once __DIR__ . "/../controllers/user.php";

  if(isset($user_id) && $user_id > 0){
    $handler($user_id);
  } else {
    $handler();
  }
  $conn->close();
} else {
  echo "Rota não encontrada!";
}
