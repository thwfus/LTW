<?php

session_start();
require_once '../dbacc.php';

if (isset($_POST['register'])) {
    $name = $_POST['fullname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($raw_password !== $confirm_password) {
        echo "Passwords do not match.";
        exit();
    }

    $checkemail = $conn->query("SELECT * FROM userdata WHERE email='$email'");

    if ($checkemail->num_rows > 0) {
        echo "Email already exists.";
    } else {
        $conn->query("INSERT INTO userdata (name, email, password) VALUES ('$name', '$email', '$password')");
        header("Location: login.html");
        exit();
    }
}


if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $checkemail = $conn->query("SELECT * FROM userdata WHERE email='$email'");
    if ($checkemail->num_rows > 0) {
        $row = $checkemail->fetch_assoc();
        if ($password === $row['password']) {
            echo "Login successful.";
            if ($row['role'] === 'admin') {
                header("Location: admin_dashboard.html");
            } else {
                header("Location: ../task1/index.php");
            }
            exit();
        } else {
            echo "Incorrect password.";
        }
    } else {
        echo "Email not found.";
    }   
}

