<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Images Coming Together</title>
    <style>
        body {
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f0f0f0;
            overflow: hidden;
        }

        .container {
            position: relative;
            height: 150px;
            width: 400px;
            background-color: transparent;
        }

        .image {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 150px;
            height: auto;
            transition: all 0.5s ease;
            resize: both;
            overflow: auto;
        }

        .image-left {
            left: -200px;
            width: 100px;
            background-color: transparent;
            z-index: 9;
        }

        .image-right {
            right: -200px;
            width: 200px;
            background-color: transparent;
            z-index: 9;
        }

        .container:hover .image-left {
            left: 50px;
        }

        .container:hover .image-right {
            right: -30px;
        }

        .container:not(:hover) .image-left {
            left: calc(50% - 102px);
        }

        .container:not(:hover) .image-right {
            right: calc(50% - 150px);
        }

        .button {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            padding: 10px 20px;
            background-color: #630101;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: none;
        }

        .container:hover .button {
            display: block;
        }

        .button:hover {
            background-color: #813535;
        }
    </style>
</head>
<body>
<div class="container">
    <img src="img/Chief2.png" alt="Image Left" class="image image-left">
    <img src="img/chief1.png" alt="Image Right" class="image image-right">
    <button class="button">Inloggen</button>
</div>
</body>
</html>