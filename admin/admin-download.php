<?php
include '../db/conn.php';
include '../fpdf/fpdf.php';

// Database query voor gebruikers
$sql = "SELECT user_id, name, achternaam FROM users"; 
$result = $pdo->query($sql);
$gebruikers = $result->fetchAll(PDO::FETCH_ASSOC);

// PDF Generatie
if (isset($_GET['user_id']) && isset($_GET['month']) && isset($_GET['year'])) {
    $user_id = intval($_GET['user_id']);
    $month = intval($_GET['month']);
    $year = intval($_GET['year']);

    // Haal uren op
    $stmt = $pdo->prepare("SELECT date, hours FROM hours 
                          WHERE user_id = :user_id 
                          AND accord = 'Approved' 
                          AND MONTH(date) = :month
                          AND YEAR(date) = :year
                          ORDER BY date ASC");
    $stmt->execute([':user_id' => $user_id, ':month' => $month, ':year' => $year]);
    $hours = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Maak PDF
    $pdf = new FPDF();
    $pdf->AddPage();
    
    // Logo en header
    $pdf->Image('../img/logo.png', 75, 6, 50);
    $pdf->SetFont('Arial','B',16);
    $pdf->SetXY(10, 30);
    $pdf->Cell(0,10,'Maandelijkse Uren - '.date('F Y', mktime(0,0,0,$month,1,$year)),0,1,'L');
    
    // Gebruikersinfo
    $stmt_user = $pdo->prepare("SELECT name, achternaam FROM users WHERE user_id = :user_id");
    $stmt_user->execute([':user_id' => $user_id]);
    $user = $stmt_user->fetch();
    $pdf->SetFont('Arial','',12);
    $pdf->SetXY(10,50);
    $pdf->Cell(0,10,'Naam: '.$user['name'].' '.$user['achternaam'],0,1,'L');
    
    // Tabel
    $pdf->SetFillColor(109, 15, 16);
    $pdf->SetTextColor(255);
    $pdf->SetXY(10,60);
    $pdf->Cell(60,10,'Datum',1,0,'C',true);
    $pdf->Cell(60,10,'Uren',1,1,'C',true);
    
    $pdf->SetTextColor(0);
    $total = 0;
    foreach($hours as $row) {
        $pdf->Cell(60,10,date('d-m-Y', strtotime($row['date'])),1);
        $pdf->Cell(60,10,$row['hours'],1);
        $pdf->Ln();
        $total += $row['hours'];
    }
    
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(60,10,'Totaal:',1);
    $pdf->Cell(60,10,$total,1);

    // Output PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="'.$user['name'].'_'.$user['achternaam'].'_'.$month.'_'.$year.'.pdf"');
    echo $pdf->Output('S');
    
    // Sluit popup
    echo '<script>window.close();</script>';
    exit;
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Urenoverzicht Downloaden</title>
    <link rel="stylesheet" href="../css/downloads.css">
</head>
<body>
    <h1>Download Urenoverzicht</h1>
    
    <ul class="user-list">
        <?php foreach($gebruikers as $gebruiker): ?>
            <li class="user-item">
                <?= htmlspecialchars($gebruiker['name'].' '.$gebruiker['achternaam']) ?>
                <button onclick="openModal(<?= $gebruiker['user_id'] ?>, '<?= htmlspecialchars($gebruiker['name']) ?>')">
                    Selecteer periode
                </button>
            </li>
        <?php endforeach; ?>
    </ul>

    <div id="downloadModal" class="modal">
        <h3 id="modalTitle">Periode selecteren voor <span id="userName"></span></h3>
        <div id="inputsModal">
        <select id="monthSelect">
            <option value="1">Januari</option>
            <option value="2">Februari</option>
            <option value="3">Maart</option>
            <option value="4">April</option>
            <option value="5">Mei</option>
            <option value="6">Juni</option>
            <option value="7">Juli</option>
            <option value="8">Augustus</option>
            <option value="9">September</option>
            <option value="10">Oktober</option>
            <option value="11">November</option>
            <option value="12">December</option>
        </select>
        <select id="yearSelect">
    <?php
    $currentYear = date('Y');
    for ($i = $currentYear; $i >= $currentYear - 20; $i--) {
        echo "<option value=\"$i\">$i</option>";
    }
    ?>
</select>

        </div>
        <div class="download-btns-div">
        <button onclick="downloadPDF()" class="downloadModalButton">Download PDF</button>
        <button onclick="closeModal()"class="downloadModalButton">Sluiten</button>
        </div>
    </div>

    <script>
        let currentUserId = null;
        
        function openModal(userId, userName) {
            currentUserId = userId;
            document.getElementById('userName').textContent = userName;
            document.getElementById('downloadModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('downloadModal').style.display = 'none';
        }

        function downloadPDF() {
    const month = document.getElementById('monthSelect').value;
    const year = document.getElementById('yearSelect').value;

    if (!month || !year) {
        alert('Selecteer een maand en jaar');
        return;
    }

    const url = `?user_id=${currentUserId}&month=${month}&year=${year}`;

    const link = document.createElement('a');
    link.href = url;
    link.download = 'urenoverzicht.pdf';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    closeModal();
}


        window.onclick = function(event) {
            if(event.target === document.getElementById('downloadModal')) {
                closeModal();
            }
        }
    </script>
</body>
</html>