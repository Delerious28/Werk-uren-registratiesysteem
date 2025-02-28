<?php
session_start();
include "db/conn.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: inloggen.php");
    exit();
}

$user_id = $_SESSION['user_id']; // Haal de user_id op uit de sessie

// Haal zowel de voornaam als de achternaam van de gebruiker op uit de database
$query = "SELECT name, achternaam FROM users WHERE user_id = :user_id"; 
$stmt = $pdo->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$first_name = $user ? $user['name'] : 'Gebruiker';
$last_name = $user ? $user['achternaam'] : ''; // Als achternaam leeg is, stel het dan in op een lege string

$username = $first_name . ' ' . $last_name; // Combineer voornaam en achternaam
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagina met Containers</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body{
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: white;
            overflow: hidden;
            background-image: url('img/achtergrond.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            height: 100vh;
        }

        .start {
            position: relative;
            background-color: transparent;
            width: 926px;
            height: 480px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            position: absolute;
            border-radius: 10px;
            opacity: 0;
            background: rgba(255, 255, 255, 0.1);
            z-index: 1; 
        }

        @keyframes slideInLeft {
            from {
                transform: translateX(-150%);
                opacity: 0;
                width: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideInRight {
            from {
                transform: translateX(150%);
                opacity: 0;
                width: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideInBottom {
            from {
                transform: translate(-50%);
                opacity: 0;
                width: 0;
            }
            to {
                transform: translate(-50%, 0);
                opacity: 1;
                width: 720px; 
            }
        }

        .boven-container-links {
            background: linear-gradient(to left, #8e0c0d, #FF847E);
            top: -60px;
            left: 105px;
            height: 300px;
            width: 340px;
            animation: slideInLeft 1s ease-out forwards;
            clip-path: polygon(0 0, 100% 0, 100% 100%, 0 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .boven-container-rechts {
            background: linear-gradient(to right, #8e0c0d, #FF847E);
            top: -60px;
            right: 105px;
            height: 300px;
            width: 340px;
            animation: slideInRight 1s ease-out forwards;
            clip-path: polygon(0 0, 100% 0, 100% 100%, 0 100%); 
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .onder-container {
            background: linear-gradient(to bottom, #8e0c0d, #FF847E);
            width: 0; 
            height: 300px;
            bottom: -90px;
            left: 50%;
            transform: translateX(-50%);
            position: absolute;
            animation: slideInBottom 1s ease-out 0.5s forwards;
            clip-path: polygon(0 0, 100% 0, 100% 100%, 0 100%); 
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .foto-container {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 250px;
            max-width: 100%;
            z-index: 10;
        }

        .midden-foto {
            width: 100%;
            height: auto;
            display: block;
            position: relative;
            z-index: 999;
            left: 10px;
            top: 10px;
        }

        .welkom-container {
            background: transparent;
            width: 100%;
            text-align: center;
            opacity: 0; 
            visibility: hidden; 
            transition: opacity 0.5s ease-in-out, visibility 0.5s ease-in-out;
        }

        .welkom-container.visible {
            opacity: 1; 
            visibility: visible; 
        }

        .welkom-container h2 {
            margin: 0 0 15px;
            color: white;
        }

        .progress-bar-container {
            background: rgba(255, 255, 255, 0.3);
            height: 20px;
            width: 100%;
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            width: 0%; 
            background: white;
            transition: width 1s ease-in-out;
        }

        h1 {
            position: absolute;
            color: white;
            font-size: 25px;
            margin-top: 60px;
            opacity: 0; 
            visibility: hidden; 
            transition: opacity 0.5s ease-in-out, visibility 0.5s ease-in-out;
        }

        h1.visible {
            opacity: 1; 
            visibility: visible; 
        }

        h4 {
            position: absolute;
            color: white;
            font-size: 40px;
            margin-top: -20px;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.5s ease-in-out, visibility 0.5s ease-in-out;
            padding: 20px;
            text-align: end;
            }
        

        h4.visible {
            opacity: 1; 
            visibility: visible; 
        }

        h2{
            font-size: 30px;
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>
    <div class="start">
        <div class="container boven-container-links">
            <h1 id="percentageText">Je hebt nog 70% te gaan</h1>
            <div class="progress-bar-container">
                <div class="progress-bar" id="progress"></div>
            </div>
        </div>
        <div class="container boven-container-rechts">
            <h4 id="workText">Vandaag werk je voor Rabbo bank</h4>
        </div>
        <div class="foto-container">
            <img src="img/logoindex-modified.png" alt="Foto" class="midden-foto">
        </div>
        <div class="container onder-container">
            <div class="welkom-container" id="welkomContainer">
                <h2>Welkom, <span id="username"><?php echo htmlspecialchars($username); ?></span></h2>
            </div>
        </div>
    </div>

    <script>
        const bovenContainerLinks = document.querySelector('.boven-container-links');
        const percentageText = document.getElementById('percentageText');

        bovenContainerLinks.addEventListener('animationend', () => {
            percentageText.classList.add('visible');
        });

        const bovenContainerRechts = document.querySelector('.boven-container-rechts');
        const workText = document.getElementById('workText');

        bovenContainerRechts.addEventListener('animationend', () => {
            workText.classList.add('visible');
        });

        const onderContainer = document.querySelector('.onder-container');
        const welkomContainer = document.getElementById('welkomContainer');

        onderContainer.addEventListener('animationend', () => {
            welkomContainer.classList.add('visible');
        });

    
        const progressBar = document.getElementById('progress');
        setTimeout(() => {
            progressBar.style.width = '70%';
        }, 1000); 
    </script>
</body>
</html>
