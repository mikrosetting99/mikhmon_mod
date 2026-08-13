<?php
if (!isset($_SESSION["mikhmon"])) {
    header("Location:../admin.php?id=login");
  } else {
        // Basis upstream 3.20 (06-30-2021), varian fork ROS7.
        $_SESSION["v"] = "3.20-ros7";
    
    }
