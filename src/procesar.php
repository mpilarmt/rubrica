<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
//echo __DIR__ . '/vendor/autoload.php';  
// /var/www/html/mp7_backend/mp7-php/ALUMNES/correccions/vendor/autoload.php
// /var/www/html/mp7_backend/mp7-php/ALUMNES/correccions/src/vendor/autoload.php

require __DIR__ . '/../vendor/autoload.php';

use Fpdf\Fpdf;

class PDF extends Fpdf
{
    public $header_title = "Informe d'Avaluacio - Backend NodeJS";
    // Método para cambiar el título
    function SetHeaderTitle($title)
    {
        $this->header_title = $title;
    }

    function CheckMark($x, $y)
    {
        $this->SetFont('ZapfDingbats', '', 10);
        $this->SetXY($x, $y);
        $this->Cell(10, 10, '4', 0, 0);
        $this->SetFont('Arial', '', 10);
    }

    function Tick($x, $y, $checked = true)
    {
        $this->SetXY($x, $y);
        $this->SetFont('Arial', 'B', 8); // Fem la font una mica més petita per l'OK/KO
        if ($checked) {
            $this->SetTextColor(0, 128, 0); // Verd per OK
            $this->Cell(10, 10, 'OK', 0, 0);
        } else {
            $this->SetTextColor(255, 0, 0); // Vermell per KO
            $this->Cell(10, 10, 'KO', 0, 0);
        }
        $this->SetTextColor(0, 0, 0); // Tornem al color negre
        $this->SetFont('Arial', '', 10); // Tornem a la font normal
    }

    function Header()
    {
        if ($this->PageNo() == 1) {
            $this->SetFont('Arial', 'B', 16);
            $title = $this->header_title ?? "Informe d'Avaluacio";
            $title = mb_convert_encoding($title, 'ISO-8859-1', 'UTF-8');
            $this->Cell(0, 10, $title, 0, 1, 'C');
            $this->Ln(10);
        }
    }

    function Cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = '')
    {
        $txt = mb_convert_encoding($txt, 'ISO-8859-1', 'UTF-8');
        parent::Cell($w, $h, $txt, $border, $ln, $align, $fill, $link);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Pàgina ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

// Classe RubricaValidator
class RubricaValidator
{
    private $rubrica;

    public function __construct()
    {
        $this->rubrica = json_decode(file_get_contents('rubrica.json'), true);
    }

    public function calcularNota($dades)
    {
        $resultat = [
            'puntuacio_total' => 0,
            'detalls' => []
        ];

        foreach ($this->rubrica['seccions'] as $seccio) {
            $puntuacio_seccio = 0;
            $items_marcats = [];

            // if (isset($dades[$seccio['id']])) {
            //     foreach ($dades[$seccio['id']] as $puntuacio) {
            //         $puntuacio_seccio += floatval($puntuacio);
            //     }
            // }
            if (isset($dades[$seccio['id']])) {
                foreach ($seccio['items'] as $item) {
                    if (in_array($item['id'], $dades[$seccio['id']] ?? [])) {
                        // Marcat, suma puntuació
                        $puntuacio_seccio += floatval($item['puntuacio']);
                    }
                }
            }

            $puntuacio_seccio = min($puntuacio_seccio, $seccio['puntuacio_maxima']);

            $resultat['detalls'][$seccio['id']] = [
                'puntuacio' => $puntuacio_seccio,
                'maxim' => $seccio['puntuacio_maxima'],
                'observacions' => $dades['obs_' . $seccio['id']] ?? '',
                'items_marcats' => $dades[$seccio['id']] ?? []
            ];

            $resultat['puntuacio_total'] += $puntuacio_seccio;
        }

        return $resultat;
    }

    public function generarPDF($dades)
    {
        //   "modul": "M7 Backend",
        //   "curs": "Curs 2024-2025",
        //   "uf": "UF 4",
        //   "tasca": "Projecte Node.js",
        //   "grup": "2n DAW",
        $titol = $this->rubrica['modul'] . " - " . $this->rubrica['uf'] . " - " . $this->rubrica['tasca'];
        $pdf = new PDF();
        $pdf->SetHeaderTitle($titol);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->AliasNbPages();
        $pdf->AddPage();


        // Informació de l'alumne
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, "Alumne: " . $dades['nom'], 0, 1);
        $pdf->Cell(0, 10, "Grup: " . $this->rubrica['grup'] . " - " . $this->rubrica['curs'], 0, 1);
        $pdf->Cell(0, 10, "Data: " . $dades['data'], 0, 1);
        $pdf->Ln(5);  // Reduït de 10 a 5

        // Detall de l'avaluació
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, "Detall de l'avaluació", 0, 1);
        $pdf->Ln(5);

        foreach ($this->rubrica['seccions'] as $seccio) {
            // Nova pàgina només si no hi ha espai pel títol i almenys un ítem
            if ($pdf->GetY() > $pdf->GetPageHeight() - 40) {
                $pdf->AddPage();
            }

            // Títol de la secció
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 8, $seccio['titol'] . " (" . $seccio['puntuacio_maxima'] . " punts)", 0, 1);

            $pdf->SetFont('Arial', '', 10);
            foreach ($seccio['items'] as $item) {

                // Nova pàgina si no hi ha prou espai
                if ($pdf->GetY() > ($pdf->GetPageHeight() - 20 - 6 - 4)) { // Més precís: $pdf->bMargin és el marge inferior (20 en el teu cas)
                    // O més simple i generalment segur:
                    // if ($pdf->GetY() > $pdf->GetPageHeight() - 30) {
                    $pdf->AddPage();
                }

                $currentY = $pdf->GetY();

                // OK/KO checkbox
                
                $checked = in_array($item['id'], $dades[$seccio['id']] ?? []);
                //echo $checked ? "-OK-" : "-KO-";


                //$checked = in_array($item['puntuacio'], $dades[$seccio['id']] ?? []);
                //echo $checked;
                $pdf->SetXY(10, $currentY);
                $pdf->Tick($pdf->GetX(), $currentY - 2, $checked);

                // Text de l'ítem
                $pdf->SetXY(25, $currentY);
                $text = $item['text'] . " (" . $item['puntuacio'] . ")";
                $pdf->MultiCell($pdf->GetPageWidth() - 35, 6, $text, 0, 'L');

                $pdf->Ln(1); // Reduït l'espai entre ítems
            }

            // Observacions
            $obs_key = 'obs_' . $seccio['id'];
            if (isset($dades[$obs_key]) && !empty($dades[$obs_key])) {
                if ($pdf->GetY() > $pdf->GetPageHeight() - 20) {
                    $pdf->AddPage();
                }
                $pdf->SetFont('Arial', 'I', 10);
                $pdf->MultiCell(0, 6, "Observacions: " . $dades[$obs_key], 0, 'L');
            }
            $pdf->Ln(1); // Reduït l'espai entre seccions
        }

        // Nota final
        if ($pdf->GetY() > $pdf->GetPageHeight() - 30) {
            $pdf->AddPage();
        }
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 10, "Observacions: " . $dades['observacions'], 0, 1);
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, "Nota Final: " . number_format($dades['resultat']['puntuacio_total'], 2) . " / 10.00", 0, 1);

        return $pdf;
    }
}

// Funció auxiliar per netejar noms d'arxius
function netejarNomArxiu($nom)
{
    $nom = mb_strtolower($nom, 'UTF-8');
    $nom = iconv('UTF-8', 'ASCII//TRANSLIT', $nom);
    $nom = preg_replace('/[^a-z0-9]/', '_', $nom);
    $nom = preg_replace('/_+/', '_', $nom);
    return trim($nom, '_');
}

// Processament principal
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // echo "<pre>";
    // var_dump($_POST);
    // echo "</pre>";
    // exit;
    try {
        $validator = new RubricaValidator();
        $resultat = $validator->calcularNota($_POST);

        $dades = [
            'nom' => $_POST['nom'] ?? 'No especificat',
            'grup' => $_POST['grup'] ?? 'No especificat',
            'data' => date('d/m/Y'),
            'resultat' => $resultat,
            'observacions' => $_POST['observacions'] ?? '',
        ];
        // Afegir totes les dades del POST
        foreach ($_POST as $key => $value) {
            if (!isset($dades[$key])) {
                $dades[$key] = $value;
            }
        }


        $pdf = $validator->generarPDF($dades);

        $nom_arxiu = 'informe_' . netejarNomArxiu($_POST['nom']) . '.pdf';
        $pdf->Output('D', $nom_arxiu);
    } catch (Exception $e) {
        http_response_code(500);
        echo "Error: " . $e->getMessage();
    }
} else {
    http_response_code(405);
    echo "Mètode no permès";
}
