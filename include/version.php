<?php
if (!isset($_SESSION["mikhmon"])) {
    header("Location:../admin.php?id=login");
  } else {
        // Basis upstream 3.20 (06-30-2021), varian fork ROS7.
        $_SESSION["v"] = "3.20-ros7";

        // Versi fork ini sendiri (mikrosetting99/mikhmon_mod), terpisah dari
        // versi upstream di atas. Naikkan tiap ada rilis baru dan catat di
        // CHANGELOG.md — lihat file itu untuk aturan penomorannya.
        $_SESSION["fv"] = "2.2.1";

    }
