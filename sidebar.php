<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sidebar Toggle</title>
    <style>

    

        .sidebar {
            height: 100%;
            width: 250px;
            position: fixed;
            top: 0;
            left: -250px;
            background-color: white;
            opacity: 0.9;
            overflow-x: hidden;
            transition: 0.5s;
            padding-top: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            border-right: outset 5px black;
            box-shadow: 4px 0px 10px rgba(0, 0, 0, 0.2);
        }

        .sidebar img {
            width: 150px;
            margin-bottom: 20px;
        }

        .sidebar a {
            width: 100%;
            text-align: center;
            padding: 12px 0;
            text-decoration: none;
            font-size: 20px;
            color: black;
            display: block;
            transition: 0.1s;
        }

        .sidebar a:hover {
            background-color: #940B10;
            color: white;
        }

        .close-btn {
            font-size: 18px;
            cursor: pointer;
            margin-bottom: 15px;
            position: absolute;
            margin-left: 210px;
            margin-top: -10px;
            border: none;
            background-color: transparent;
            transition: 0.2s;
        }


        .close-btn:hover {
            transform: scale(1.2);
        }

        .logout-btn {
            margin-top: auto;
            width: 100%;
            padding: 12px 0;
            text-align: center;
            background-color: white;
            color: black;
            border: none;
            font-size: 18px;
            cursor: pointer;
            margin-bottom: 20px;
        }

        .logout-btn:hover {
            background-color: #940B10;
            color: white;
        }

        .toggle-btn {
            position: absolute;
            top: 10px;
            left: 50px;
            font-size: 60px;
            cursor: pointer;
            color: white;
            background-color: transparent;
            border: none;
            transition: 0.3s;
        }

    </style>
</head>
<body>

<button class="toggle-btn" id="toggleBtn" onclick="toggleSidebar()">☰</button>

<div id="mySidebar" class="sidebar">
    <button class="close-btn" onclick="toggleSidebar()">❌</button>
    <img src="img/logo.png" alt="Profile Image">
    <a href="index.php">Home</a>
    <a href="uren-registreren.php">Uren</a>
    <a href="profiel.php">Profiel</a>
    <a href="#">Overzicht</a>
    <a href="uitloggen.php" class="logout-btn">Uitloggen</a>
    </div>

<script>
   
    function toggleSidebar() {
        var sidebar = document.getElementById("mySidebar");
        var toggleBtn = document.getElementById("toggleBtn");

        if (window.getComputedStyle(sidebar).left === "-250px") {
            sidebar.style.left = "0"; 
            toggleBtn.style.display = "none"; 
        } else {
            sidebar.style.left = "-250px"; 
            toggleBtn.style.display = "block"; 
        }
    }

    
    document.addEventListener("click", function(event) {
        var sidebar = document.getElementById("mySidebar");
        var toggleBtn = document.getElementById("toggleBtn");

        if (window.getComputedStyle(sidebar).left === "0px" &&
            !sidebar.contains(event.target) &&
            !toggleBtn.contains(event.target)) {

            sidebar.style.left = "-250px"; 
            toggleBtn.style.display = "block"; 
        }
    });

</script>

</body>
</html>
