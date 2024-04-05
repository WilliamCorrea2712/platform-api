<?php
require_once __DIR__ . "/../security/token.php"; 

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

    if(strpos($route, "user/addUser") !== false || strpos($route, "account/addCustomer") !== false || strpos($route, "account/addAddress") !== false){
      $handler();
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
