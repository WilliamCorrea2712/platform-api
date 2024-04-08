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
    "account/deleteAddress" => "deleteAddress",
    "product/addProduct" => "addProduct",
    "product/editProduct" => "editProduct",
    "product/deleteProduct" => "deleteProduct",
    "product/addProductImages" => "addProductImages",
    "product/getProducts" => "getProducts",
    "product/addCategory" => "addCategory",
    "product/editCategory" => "editCategory",
    "product/deleteCategory" => "deleteCategory",
    "product/getCategories" => "getCategories",
    "product/addBrand" => "addBrand",
    "product/editBrand" => "editBrand",
    "product/deleteBrand" => "deleteBrand",
    "product/getBrands" => "getBrands",
  );

  if (isset($_GET['route']) && isset($routes[$_GET['route']])) {
    $route = $_GET['route'];
    $handler = $routes[$route];

    if (strpos($route, "account/") === 0) {
      require_once __DIR__ . "/../controllers/account/account.php";
    } else if (strpos($route, "user/") === 0) {
      require_once __DIR__ . "/../controllers/user/user.php";
    } else if (strpos($route, "product/") === 0) {
      require_once __DIR__ . "/../controllers/product/product.php";
      require_once __DIR__ . "/../controllers/product/category.php";
      require_once __DIR__ . "/../controllers/product/brand.php";
    }

    if(strpos($route, "user/addUser") !== false){
      $handler();
    } else if(strpos($route, "account/getCustomers") !== false || 
              strpos($route, "product/getCategories") !== false || 
              strpos($route, "product/getBrands") !== false ||
              strpos($route, "product/getProducts") !== false){

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
