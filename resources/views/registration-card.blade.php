<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fiche d'Inscription - {{ $school->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
            background: #fff;
        }
        
        .container {
            width: 100%;
            max-width: 210mm;
            margin: 0 auto;
            padding: 10mm;
            position: relative;
        }
        
        .header {
            border: 3px solid #000;
            padding: 15px;
            margin-bottom: 20px;
            position: relative;
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .header-left, .header-right {
            width: 35%;
            text-align: center;
            font-size: 10px;
            line-height: 1.2;
        }
        
        .header-center {
            width: 30%;
            text-align: center;
            position: relative;
        }
        
        .logo-placeholder {
            width: 80px;
            height: 80px;
            border: 2px solid #ccc;
            border-radius: 50%;
            margin: 0 auto 10px;
            display: flex;
            align-