<?php
require_once __DIR__ . "/../security/token.php"; 
date_default_timezone_set('America/Sao_Paulo');

  $routes = array(
    "user/addUser" => "addUser",
    "user/editUser" => "editUser",
    "user/deleteUser" => "deleteUser",
    "user/getUsers" => "getUsers",
    "user/login" => "login",
    "account/addCustomer" => "addCustomer",
    "account/editCustomer" => "editCustomer",
    "account/deleteCustomer" => "deleteCustomer",
    "account/getCustomers" => "getCustomers",
    "account/addAddress" => "addAddress",
    "account/editAddress" => "editAddress",
    "account/deleteAddress" => "deleteAddress"
  );

  if (isset($_GET['route']) && isset($routes[$_GET['route']])) {
    $route = $_GET['route'];
    $handler = $routes[$route];

    if (strpos($route, "account/") === 0) {
      require_once __DIR__ . "/../controllers/account/account.php";
    } else if(strpos($route, "user/") === 0) {
      require_once __DIR__ . "/../controllers/user/user.php";
    }

    if(strpos($route, "user/addUser") !== false){
      $handler();
    } else if(strpos($route, "account/getCustomers") !== false){
      if (isset($_GET['id'])){
          $handler($_GET['id']);
      } else{
          $handler();
      }
    } else {
      $user_id = verifyToken()->user_id;

      if(isset($user_id) && $user_id > 0){
        $handler($user_id);
      } else {
        echo json_encode(array("message" => "Usuário nao autenticado."));
      }
    }
  } else {
    echo "Rota não encontrada!";
  }
?>
