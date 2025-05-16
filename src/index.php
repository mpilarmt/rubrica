<?php
// Llegim el JSON
$rubrica = json_decode(file_get_contents('rubrica.json'), true);
?>
<!DOCTYPE html>
<html lang="ca">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rúbrica d'Avaluació - Backend NodeJS</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .section {
            margin-bottom: 30px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .subsection {
            margin: 10px 0;
            padding-left: 20px;
        }

        textarea {
            width: 100%;
            margin: 10px 0;
        }

        .total {
            font-weight: bold;
            font-size: 1.2em;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <!-- 
    "modul": "M7 Backend",
    "curs": "Curs 2024-2025",
    "uf": "UF 4",
    "tasca": "Projecte Node.js",
    "grup": "2n DAW",
    -->

    <h1>Rúbrica d'Avaluació - <?php echo $rubrica['modul'] . " - " . $rubrica['uf']; ?></h1>
    <h2><?php echo $rubrica['tasca']; ?></h2>
    <h4><?php echo $rubrica['grup'] . " - " . $rubrica['curs']; ?></h4>
    <form action="procesar.php" method="POST">
        <!-- Dades de l'alumne -->
        <div class="section student-info">
            <h2>Dades de l'Alumne</h2>
            <div class="subsection">
                <label for="nom">Nom complet:</label>
                <input type="text" id="nom" name="nom" required>
                <br><br>

                <label for="data">Data d'avaluació:</label>
                <input type="date" id="data" name="data" required value="<?php echo date('Y-m-d'); ?>">
            </div>
        </div>
        <?php foreach ($rubrica['seccions'] as $seccio): ?>
            <div class="section">
                <h2><?php echo $seccio['titol'] ?> (<?php echo $seccio['puntuacio_maxima'] ?> punts)</h2>
                <div class="subsection">
                    <?php foreach ($seccio['items'] as $item): ?>
                        <input type="checkbox" checked
                            name="<?php echo $seccio['id'] ?>[]"
                            value="<?php echo $item['id'] ?>"
                            <?php if (in_array($item['id'], $dades[$seccio['id']] ?? [])) echo 'checked'; ?>>
                        <?php echo $item['text'] ?> (<?php echo $item['puntuacio'] ?>)<br>

                    <?php endforeach; ?>


                    <div class="observacions">
                        <label>Observacions:</label>
                        <textarea name="obs_<?php echo $seccio['id'] ?>"></textarea>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="section">
            <h2>Observacions Generals</h2>
            <div class="subsection">
                <textarea name="observacions" rows="5" placeholder="Observacions generals sobre l'avaluació..."></textarea>
            </div>
        </div>

        <div class="total">
            Total màxim possible: 10 punts
        </div>

        <button type="submit" style="margin-top: 20px; padding: 10px 20px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;">
            Generar Informe PDF
        </button>
    </form>
</body>

</html>