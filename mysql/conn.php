<?php
  $servername = "localhost";
  $username = "root";
  $password_db = "";
  $database = "platform_api";

  $conn = new mysqli($servername, $username, $password_db, $database);

  if ($conn->connect_error) {
      die("Erro na conexão com o banco de dados: " . $conn->connect_error);
  }
?>
