<?php
require_once '../vendor/autoload.php';
require_once './buys/buys.php';

// Crear una instancia de TCPDF

function createTicketBuy($pdo)
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        /* Verify key _POST */
        $required_fields = ['id_buy'];
        $id_buy = $_POST['id_buy'] ?? null;

        /* Verify Data Complete */
        $verifyData = verifyData($required_fields, $_POST);

        if ($verifyData) {
            http_response_code(400);
            echo json_encode(["mensaje" => "El campo $verifyData es obligatorio"]);
            exit;
        }

        /* Search Data */
        $dataBuy = getDataBuy($pdo, $id_buy);
        if (!empty($dataBuy)) {

            $nameUser = $dataBuy['nameUser'] ?? '';
            $emailUser = $dataBuy['emailUser'] ?? '';
            $phoneUser = $dataBuy['phoneUser'] ?? '';
            $addressUser = $dataBuy['addressUser'] ?? '';
            $nameMovie = $dataBuy['nameMovie'] ?? '';
            $genreMovie = $dataBuy['genreMovie'] ?? '';
            $durationMovie = $dataBuy['durationMovie'] ?? '';
            $cardNumber = str_replace('-', ' ', $dataBuy['card_number']) ?? '';
            $nameCard = $dataBuy['name_card'] ?? '';
            $durationMovie = $dataBuy['durationMovie'] ?? '';
            $totalWithIva = number_format($dataBuy['monto'] ?? 0, 2, '.', ',');
            $percent = number_format($dataBuy['monto'] * 0.16, 2, '.', ',');
            $montoNotIva = number_format((float) $totalWithIva - (float) $percent, 2, '.', ',');

            ob_start(); // Start output buffering
            $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
            $pdf->SetCreator('SamyBuster');
            $pdf->SetAuthor('SamyBuster');
            $pdf->SetTitle('Ticket de Compra');
            $pdf->SetSubject('Compra en Linea');

            /* Headers */
            $pdf->SetHeaderData(null, 0, 'SAMYBUSTER', '');
            $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
            $pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));


            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

            /* Margins */
            $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
            $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

            $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

            $pdf->AddPage();

            $pdf->setCellPaddings(1, 1, 1, 1);
            $pdf->setCellMargins(1, 1, 1, 1);
            $pdf->SetFillColor(255, 255, 127);


            $pdf->SetFont('helvetica', 'B', 20);
            $pdf->Cell(0, 5, 'Comprobante de Compra', 0, 1, 'C');
            $pdf->Ln(10);

            /* Data Client */
            $pdf->SetFont('helvetica', 'B', 15);
            $pdf->Cell(0, 5, 'Datos del Cliente', 0, 1, 'L');
            $pdf->Ln(5);
            $pdf->SetFont('helvetica', '', 12);
            $pdf->MultiCell(80, 5, 'Nombre: ' . $nameUser, 0, 'L', 0, 0, '', '', true);
            $pdf->MultiCell(80, 5, 'Email: ' . $emailUser, 0, 'L', 0, 0, '', '', true);
            $pdf->Ln(15);
            $pdf->MultiCell(80, 5, 'Teléfono: ' . $phoneUser, 0, 'L', 0, 0, '', '', true);
            $pdf->MultiCell(80, 5, 'Domicilio: ' . $addressUser, 0, 'L', 0, 0, '', '', true);
            $pdf->Ln(15);

            /* Data Movie */
            $pdf->SetFont('helvetica', 'B', 15);
            $pdf->Cell(0, 5, 'Datos de la Pelicula: ', 0, 1, 'L');
            $pdf->Ln(5);
            $pdf->SetFont('helvetica', '', 12);
            $pdf->MultiCell(80, 5, 'Nombre: ' . $nameMovie, 0, 'L', 0, 0, '', '', true);
            $pdf->MultiCell(80, 5, 'Género: ' . $genreMovie, 0, 'L', 0, 0, '', '', true);
            $pdf->Ln(15);
            $pdf->MultiCell(80, 5, 'Duración: ' . $durationMovie, 0, 'L', 0, 0, '', '', true);
            $pdf->Ln(20);

            /* Data Payment */
            $pdf->SetFont('helvetica', 'B', 15);
            $pdf->Cell(0, 5, 'Datos del Método de Pago', 0, 1, 'L');
            $pdf->Ln(5);
            $pdf->SetFont('helvetica', '', 12);
            $pdf->MultiCell(80, 5, 'Tarjeta: ' . $cardNumber, 0, 'L', 0, 0, '', '', true);
            $pdf->MultiCell(80, 5, 'Propietario: ' . $nameCard, 0, 'L', 0, 0, '', '', true);
            $pdf->Ln(15);
            $pdf->MultiCell(80, 5, 'Monto: $' . $montoNotIva, 0, 'L', 0, 0, '', '', true);
            $pdf->MultiCell(80, 5, 'Iva: $' . $percent, 0, 'L', 0, 0, '', '', true);
            $pdf->Ln(15);
            $pdf->MultiCell(80, 5, 'Total: $' . $totalWithIva, 0, 'L', 0, 0, '', '', true);

            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="ticket_compra.pdf"');
            $pdf->Output('Ticket', "D");

        
        } else {
            http_response_code(404);
            echo json_encode(["message" => "No hay información registrada de la compra"]);
        }
    } else {
        http_response_code(405);
        echo json_encode(["message" => "Método no permitido"]);
    }
}


?>