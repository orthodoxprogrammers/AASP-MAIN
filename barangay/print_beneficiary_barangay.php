<?php
session_start();
include __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Barangay') {
    die("Access Denied.");
}

if (!isset($_GET['id'])) {
    die("Beneficiary ID not found.");
}

$id = intval($_GET['id']);

/* ── BASE64 IMAGES (embedded so they always print correctly) ── */
function imgBase64($path) {
    $abs = __DIR__ . '/' . ltrim($path, '/');
    if (file_exists($abs)) {
        $mime = mime_content_type($abs) ?: 'image/png';
        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($abs));
    }
    return ''; // returns empty string if file not found — img just won't show
}
$imgDSWD      = imgBase64('../images/dswd_seal.png');
$imgSocWel    = imgBase64('../images/social_welfare_seal.png');
$imgBayanihan = imgBase64('../images/bayanihan_seal.png');

/* ── HEADER HTML (reused on each page) ── */
function makeHeader($imgDSWD, $imgSocWel, $imgBayanihan) {
    $d = $imgDSWD      ? '<img src="' . $imgDSWD      . '" alt="DSWD" />' : '';
    $s = $imgSocWel    ? '<img src="' . $imgSocWel    . '" alt="Social Welfare" />' : '';
    $b = $imgBayanihan ? '<img src="' . $imgBayanihan . '" alt="Bayanihan" />' : '';
    return '
<div class="header-flex">
  <div class="logo-group">
    ' . $d . '
    <div><div><strong>DSWD</strong></div><div style="font-size:9px;">Department of Social Welfare and Development</div></div>
    ' . $s . '
    ' . $b . '
  </div>';
}
$headerLogos = makeHeader($imgDSWD, $imgSocWel, $imgBayanihan);

/* BENEFICIARY */
$stmt = $conn->prepare("SELECT * FROM beneficiaries WHERE beneficiary_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$beneficiary = $stmt->get_result()->fetch_assoc();
if (!$beneficiary) die("Beneficiary not found.");

/* CLIENT */
$client = null;
if (!empty($beneficiary['client_id'])) {
    $q = $conn->prepare("SELECT * FROM clients WHERE client_id=?");
    $q->bind_param("i", $beneficiary['client_id']);
    $q->execute();
    $client = $q->get_result()->fetch_assoc();
}

/* FAMILY */
$q = $conn->prepare("SELECT * FROM family_composition WHERE beneficiary_id=?");
$q->bind_param("i", $id);
$q->execute();
$familyRows = [];
$fr = $q->get_result();
while ($row = $fr->fetch_assoc()) $familyRows[] = $row;

/* INTAKE (latest) */
$q = $conn->prepare("SELECT * FROM general_intake WHERE beneficiary_id=? ORDER BY created_at DESC LIMIT 1");
$q->bind_param("i", $id);
$q->execute();
$intake = $q->get_result()->fetch_assoc();

/* FINANCIAL ASSISTANCE */
$fa = null;
if (!empty($intake['intake_id'])) {
    $q = $conn->prepare("SELECT * FROM financial_assistance WHERE intake_id=?");
    $q->bind_param("i", $intake['intake_id']);
    $q->execute();
    $fa = $q->get_result()->fetch_assoc();
}

/* MATERIAL ASSISTANCE */
$ma = null;
if (!empty($intake['intake_id'])) {
    $q = $conn->prepare("SELECT * FROM material_assistance WHERE intake_id=?");
    $q->bind_param("i", $intake['intake_id']);
    $q->execute();
    $ma = $q->get_result()->fetch_assoc();
}

/* PSYCHOSOCIAL SUPPORT */
$ps = null;
if (!empty($intake['intake_id'])) {
    $q = $conn->prepare("SELECT * FROM psychosocial_support WHERE intake_id=?");
    $q->bind_param("i", $intake['intake_id']);
    $q->execute();
    $ps = $q->get_result()->fetch_assoc();
}

/* SECTORS */
$q = $conn->prepare("SELECT sector_name FROM beneficiary_sectors WHERE beneficiary_id=?");
$q->bind_param("i", $id);
$q->execute();
$sectors = [];
$sr = $q->get_result();
while ($row = $sr->fetch_assoc()) $sectors[] = $row['sector_name'];

/* SUBCATEGORIES */
$q = $conn->prepare("SELECT subcategory_name FROM beneficiary_subcategories WHERE beneficiary_id=?");
$q->bind_param("i", $id);
$q->execute();
$subcategories = [];
$scr = $q->get_result();
while ($row = $scr->fetch_assoc()) $subcategories[] = $row['subcategory_name'];

/* DSWD ASSISTANCE */
$q = $conn->prepare("SELECT assistance_type, date_received FROM dswd_assistance WHERE beneficiary_id=?");
$q->bind_param("i", $id);
$q->execute();
$assistanceRows = [];
$ar = $q->get_result();
while ($row = $ar->fetch_assoc()) $assistanceRows[] = $row;

/* ── HELPERS ── */
function e($v) { return htmlspecialchars($v ?? '', ENT_QUOTES); }
function chk($arr, $val) { return in_array($val, (array)$arr) ? 'checked disabled' : 'disabled'; }
function tinyChk($val) { return !empty($val) ? 'checked disabled' : 'disabled'; }

/* ── DATE PARTS ── */
$intakeDateRaw = $intake['intake_date'] ?? '';
// Handle 0000-00-00 as empty
if ($intakeDateRaw === '0000-00-00' || empty($intakeDateRaw)) {
    $intakeMM   = date('m');
    $intakeDD   = date('d');
    $intakeYYYY = date('Y');
} else {
    $dp = explode('-', $intakeDateRaw);
    $intakeMM   = $dp[1] ?? date('m');
    $intakeDD   = $dp[2] ?? date('d');
    $intakeYYYY = $dp[0] ?? date('Y');
}

/* ── BENEFICIARY ── */
$benLast     = e($beneficiary['last_name']    ?? '');
$benFirst    = e($beneficiary['first_name']   ?? '');
$benMid      = e($beneficiary['middle_name']  ?? '');
$benExt      = e($beneficiary['ext']          ?? '');
$benFullName = trim("$benFirst $benMid $benLast" . ($benExt ? " $benExt" : ''));
$benBDraw    = $beneficiary['birthdate'] ?? '';
$benBDparts  = ($benBDraw && $benBDraw !== '0000-00-00') ? explode('-', $benBDraw) : [];
$benBD       = isset($benBDparts[1]) ? "{$benBDparts[1]}-{$benBDparts[2]}-{$benBDparts[0]}" : '';
$benAddress  = e(trim(implode(', ', array_filter([
    $beneficiary['street_address'] ?? '',
    $beneficiary['barangay']       ?? '',
    $beneficiary['city']           ?? '',
    $beneficiary['province']       ?? '',
]))));

/* ── CLIENT ── */
$cliLast    = e($client['last_name']   ?? '');
$cliFirst   = e($client['first_name']  ?? '');
$cliMid     = e($client['middle_name'] ?? '');
$cliExt     = e($client['ext']         ?? '');
$cliBDraw   = $client['birthdate'] ?? '';
$cliBDparts = ($cliBDraw && $cliBDraw !== '0000-00-00') ? explode('-', $cliBDraw) : [];
$cliBD      = isset($cliBDparts[1]) ? "{$cliBDparts[1]}-{$cliBDparts[2]}-{$cliBDparts[0]}" : '';
$sameAsAbove = (!$client || (
    strtolower($client['last_name']  ?? '') === strtolower($beneficiary['last_name']  ?? '') &&
    strtolower($client['first_name'] ?? '') === strtolower($beneficiary['first_name'] ?? '')
));

/* ── INTAKE VALUES (corrected column names from actual DB) ── */
$purpose    = e($intake['purpose_of_assistance']       ?? '');
$diagnosis  = e($intake['diagnosis_or_cause_of_death'] ?? '');
$amount     = e(number_format((float)($intake['amount_needed'] ?? 0), 2));
$modeOfAsst = e($intake['mode_of_assistance']          ?? '');
$othProg    = e($intake['others_program']              ?? '');
$aics       = !empty($intake['aics']);
$akap       = !empty($intake['akap']);
// visit_type = 'New' or 'Returning'
$isNew      = ($intake['visit_type'] ?? '') === 'New';
$isReturn   = ($intake['visit_type'] ?? '') === 'Returning';
// mode columns are separate tinyints
$isOnsite       = !empty($intake['onsite']);
$isMalasakit    = !empty($intake['malasakit_center']);
$isOffsite      = !empty($intake['offsite']);
$source         = $intake['client_source'] ?? '';
$isWalkin       = $source === 'Walk-in';
$isReferral     = $source === 'Referral';
// Income fields
$incEmployed    = e($intake['income_employed']         ?? '');
$incSeasonal    = e($intake['income_seasonal']         ?? '');
$combIncome     = e($intake['combined_family_income']  ?? '');
$insurance      = e($intake['insurance']               ?? '');
$savings        = e($intake['savings']                 ?? '');
$hasMonthlyExp  = !empty($intake['monthly_expenses']);
$hasEmergFund   = !empty($intake['emergency_fund']);
$severityCrisis = e($intake['severity_crisis']         ?? '');
$expCrisis      = !empty($intake['experienced_crisis']);
$crisisDetails  = e($intake['crisis_details']          ?? '');
$supportSystems = e($intake['support_systems']         ?? '');
$extResources   = e($intake['external_resources']      ?? '');
$selfHelp       = e($intake['self_help']               ?? '');
$vulnRisk       = e($intake['vulnerability_risk']      ?? '');
$sourceIncome   = e($intake['source_of_income']        ?? '');
$total6months   = e(number_format((float)($intake['total_income_6months'] ?? 0), 2));
$problemPresented     = e($intake['problem_presented']       ?? '');
$swAssessment         = e($intake['social_worker_assessment'] ?? '');
$amountReleased       = e(number_format((float)($intake['amount_released'] ?? 0), 2));

/* ── FINANCIAL ASSISTANCE FLAGS ── */
$faFood       = !empty($fa['food_assistance']);
$faCash       = !empty($fa['cash_relief']);
$faMedical    = !empty($fa['medical']);
$faFuneral    = !empty($fa['funeral']);
$faTransport  = !empty($fa['transportation']);
$faEduc       = !empty($fa['educational']);

/* ── MATERIAL ASSISTANCE FLAGS ── */
$maFFP        = !empty($ma['family_food_packs']);
$maOFI        = !empty($ma['other_food_items']);
$maHSK        = !empty($ma['hygiene_sleeping_kits']);
$maADT        = !empty($ma['assistive_devices']);
$maRice       = !empty($ma['rice']);

/* ── PSYCHOSOCIAL FLAGS ── */
$psPFA        = !empty($ps['pfa']);
$psCounsel    = !empty($ps['counseling']);

/* ── DISABILITY / SUB-CATEGORY LISTS ── */
$disabilityList = ['Mental Disability','Visual Disability','Intellectual Disability',
    'Physical Disability','Rare Disease','Speech Impairment','Learning Disability',
    'Psychosocial Disability','Deaf/Hard-of-Hearing','Cancer'];
$subCatList = ['Solo Parent','Indigenous People','Street Dwellers','KIA/KWA',
    '4PS Beneficiary','Stateless Person','Asylum Seekers','Refugees',
    'Recovering PWUD','Below Minimum Wage','No Regular Income'];
$incomeSectorList = ['PHONA','WEDC','DSWD','DPA','CNSP','SC','NSNP','PLHV'];

/* ── DEBUG MODE: add ?debug=1 to URL to see raw DB data ── */
if (isset($_GET['debug']) && $_GET['debug'] == '1') {
    echo '<pre style="background:#f8f8f8;border:1px solid #ccc;padding:15px;font-size:12px;">';
    echo "<strong>=== BENEFICIARY ===</strong>\n"; print_r($beneficiary);
    echo "\n<strong>=== CLIENT ===</strong>\n"; print_r($client);
    echo "\n<strong>=== INTAKE ===</strong>\n"; print_r($intake);
    echo "\n<strong>=== FINANCIAL ASSISTANCE ===</strong>\n"; print_r($fa);
    echo "\n<strong>=== MATERIAL ASSISTANCE ===</strong>\n"; print_r($ma);
    echo "\n<strong>=== PSYCHOSOCIAL ===</strong>\n"; print_r($ps);
    echo "\n<strong>=== FAMILY ROWS ===</strong>\n"; print_r($familyRows);
    echo "\n<strong>=== SECTORS ===</strong>\n"; print_r($sectors);
    echo "\n<strong>=== SUBCATEGORIES ===</strong>\n"; print_r($subcategories);
    echo "\n<strong>=== ASSISTANCE ===</strong>\n"; print_r($assistanceRows);
    echo '</pre>';
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Print – <?= e($benFullName) ?></title>
<style>
  /* ── SCREEN ── */
  body {
    font-family: Arial, sans-serif;
    font-size: 11px;
    margin: 0;
    padding: 10px;
    background: #f0f0f0;
    color: #000;
  }

  /* Controls bar */
  .controls {
    width: 210mm;
    margin: 0 auto 10px auto;
    display: flex;
    justify-content: flex-end;
    gap: 8px;
  }
  .btn {
    padding: 6px 16px;
    font-size: 12px;
    font-family: Arial, sans-serif;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
  }
  .btn-primary   { background: #0d6efd; color: white; }
  .btn-primary:hover  { background: #0b5ed7; }
  .btn-secondary { background: #6c757d; color: white; }
  .btn-secondary:hover { background: #5c636a; }

  /* Each form = one A4 page */
  .page {
    width: 210mm;
    min-height: 297mm;
    border: 1px solid black;
    padding: 8mm;
    box-sizing: border-box;
    background: white;
    margin: 0 auto 20px auto;
    page-break-after: always;
  }
  .page:last-child { page-break-after: avoid; }

  /* ── HEADER ── */
  .header-flex { display: flex; justify-content: space-between; align-items: center; }
  .logo-group  { display: flex; gap: 10px; align-items: center; }
  .logo-group img { width: 40px; height: auto; }

  h3 { text-align: center; margin-top: 2px; margin-bottom: 4px; font-size: 13px; }

  label { user-select: none; }

  input[type="text"], input[type="number"] {
    font-size: 10px; padding: 1px 3px;
    box-sizing: border-box; border: 1px solid black; background: white;
  }
  input[type="checkbox"], input[type="radio"] {
    transform: scale(1.1); margin-right: 4px; vertical-align: middle;
  }

  /* ── TABLES ── */
  table { width: 100%; border-collapse: collapse; margin-top: 2px; }
  th, td { border: 1px solid black; padding: 2px 4px; vertical-align: middle; font-size: 10px; }
  th { background: #ccc; font-weight: bold; font-size: 10px; text-align: center; }
  td input[type="text"] { width: 100%; border: none; height: 16px; font-size: 10px; background: transparent; }
  td.is-input input[type="text"] { border: 1px solid black; height: 18px; }

  /* ── SECTION BARS ── */
  .section-title {
    background: #999; color: white; padding: 4px 5px;
    font-weight: bold; font-size: 11px;
  }
  .section-subtitle { font-style: italic; font-weight: normal; font-size: 10px; }
  .col-header {
    background: #ccc; font-weight: bold; font-size: 10px;
    text-align: center; padding: 2px 4px; border: 1px solid black; vertical-align: middle;
  }

  /* ── CHECKBOXES ── */
  .cb { display: block; margin: 0; font-size: 10px; line-height: 1.4; }
  .cb-inline { display: inline-flex; align-items: center; margin-right: 10px; font-size: 10px; line-height: 1.6; }
  .cb input, .cb-inline input { margin-right: 3px; flex-shrink: 0; }

  /* ── INPUTS ── */
  .ul-input { border: none; border-bottom: 1px solid black; font-size: 10px; background: transparent; padding: 0 2px; display: inline-block; }
  .line-input { display: inline-block; border-bottom: 1px solid #000; width: 110px; height: 13px; margin-left: 4px; vertical-align: bottom; }
  .section-block { border: 1px solid black; border-top: none; padding: 4px 6px; }

  /* ── PAGE 1 SPECIFIC ── */
  .pcn-box input[type="text"] { width: 18px; margin-right: 2px; text-align: center; border: 1px solid black; }
  .consent-text { font-size: 9.5px; margin-top: 3px; margin-bottom: 3px; text-align: justify; line-height: 1.3; }
  .consent-section { page-break-inside: avoid; break-inside: avoid; }
  .signature-container { display: flex; justify-content: space-between; margin-top: 10px; align-items: flex-end; }
  .signature-box { width: calc(100% - 65px); }
  .sig-line-top { border-top: 1px solid black; margin-bottom: 3px; height: 0; }
  .sig-label { font-size: 9px; text-align: center; }
  .thumbmark-box { width: 58px; height: 58px; border: 1px solid black; font-size: 9px; font-style: italic; text-align: center; line-height: 58px; }
  .assistance-checkboxes { display: flex; gap: 12px; }
  .assistance-checkboxes label { font-weight: normal; }
  .assistance-table th, .assistance-table td { border: 1px solid black; padding: 2px 4px; font-size: 10px; }
  .assistance-table input[type="text"] { border: none; width: 100%; height: 18px; font-size: 10px; padding: 1px 2px; }

  /* ── PAGE 3 SPECIFIC ── */
  table.fixed-layout { table-layout: fixed; }
  .vertical-text { writing-mode: vertical-rl; transform: rotate(180deg); text-align: center; font-weight: bold; font-size: 10px; padding: 0 4px; width: 20px; vertical-align: middle; background: #eee; border: 1px solid black; }
  .purpose-input { width: 92%; border: none; border-bottom: 1px solid black; font-size: 10px; margin: 2px 0; background: transparent; }
  .signature-cell { text-align: center; font-size: 10px; vertical-align: bottom; height: 85px; padding: 3px; }
  hr.sig-hr { border: none; border-top: 1px solid black; width: 80%; margin: 28px auto 3px auto; }

  /* ── PAGE 4 SPECIFIC ── */
  .pcn-box-coe input[type="text"] { width: 14px; height: 16px; padding: 0; margin: 0 1px; font-size: 9px; text-align: center; border: 1px solid black; box-sizing: border-box; display: inline-block; }
  .box-container { display: flex; gap: 6px; margin-top: 4px; }
  .box { border: 1px solid black; padding: 5px 7px; flex: 1; font-size: 10px; }
  .box-title { background: #ccc; font-weight: bold; font-size: 10px; padding: 2px 4px; border: 1px solid black; margin: -5px -7px 5px -7px; }
  .box p { margin: 3px 0; font-size: 10px; }
  .acknowledgment { background: #999; color: white; padding: 4px 5px; font-weight: bold; font-size: 11px; text-align: center; margin-top: 6px; }
  .sig-section { display: flex; justify-content: space-between; margin-top: 8px; }
  .sig-block { width: 45%; text-align: center; font-size: 10px; }
  .sig-block-center { width: 280px; text-align: center; font-size: 10px; margin: 8px auto 0 auto; }
  .sig-line-bottom { border-bottom: 1px solid black; margin-bottom: 2px; min-height: 22px; }

  /* ── FOOTER ── */
  .footer-text { font-size: 9px; margin-top: 6px; text-align: center; border-top: 1px solid #999; padding-top: 3px; page-break-inside: avoid; break-inside: avoid; }

  /* ── PRINT ── */
  @media print {
    @page { size: A4 portrait; margin: 8mm; }
    html, body { margin: 0; padding: 0; background: white; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .controls { display: none !important; }
    .page { width: 100%; min-height: unset; border: none; padding: 0; margin: 0; box-shadow: none; }
    .section-title, .acknowledgment { background: #999 !important; color: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    th, .col-header, .box-title { background: #ccc !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .vertical-text { background: #eee !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    a[href]:after { content: none !important; }
  }
</style>
</head>
<body>

<!-- ── CONTROLS (screen only) ── -->
<div class="controls">
  <button class="btn btn-primary" onclick="window.print()">&#128438;&nbsp; Print</button>
  <button class="btn btn-secondary" onclick="window.close()">&#10005;&nbsp; Close</button>
</div>


<!-- ══════════════════════════════════════════════════
     PAGE 1 — INFORMATION SHEET
     ══════════════════════════════════════════════════ -->
<div class="page">
  <?= $headerLogos ?>
    <div>
      <div><input type="checkbox" disabled /> <label># Central Office</label></div>
      <div><input type="checkbox" disabled /> <label># Field Office I</label></div>
    </div>
  </div>

  <div style="margin-bottom:4px;">
    <label>PCN:</label>
    <span class="pcn-box">
      <?php for($i=0;$i<12;$i++): ?><input type="text" maxlength="1" /><?php endfor; ?>
    </span>
    <span style="float:right;">
      Date:
      <input type="text" value="<?= $intakeMM ?>"   style="width:22px; text-align:center;" /> /
      <input type="text" value="<?= $intakeDD ?>"   style="width:22px; text-align:center;" /> /
      <input type="text" value="<?= $intakeYYYY ?>" style="width:32px; text-align:center;" />
    </span>
  </div>

  <!-- CLIENT INFO -->
  <div class="section-title">IMPORMASYON NG KINATAWAN/KLIENTE <span class="section-subtitle">(Authorized Representative / Client's Identifying Information)</span></div>
  <table><thead><tr>
    <th>Apelyido (Last Name)</th><th>Unang Pangalan (First Name)</th><th>Gitnang Pangalan (Middle Name)</th><th style="width:40px;">Ext.</th>
  </tr></thead><tbody><tr>
    <td class="is-input"><input type="text" value="<?= $cliLast ?>" /></td>
    <td class="is-input"><input type="text" value="<?= $cliFirst ?>" /></td>
    <td class="is-input"><input type="text" value="<?= $cliMid ?>" /></td>
    <td class="is-input"><input type="text" value="<?= $cliExt ?>" style="text-align:center;" /></td>
  </tr></tbody></table>

  <table><thead><tr>
    <th>Street Address</th><th>Barangay</th><th>City/Municipality</th><th style="width:54px;">Province</th><th>Lalawigan/Distrito</th><th>Rehiyon</th>
  </tr></thead><tbody><tr>
    <td class="is-input"><input type="text" value="<?= e($client['street_address'] ?? '') ?>" /></td>
    <td class="is-input"><input type="text" value="<?= e($client['barangay']       ?? '') ?>" /></td>
    <td class="is-input"><input type="text" value="<?= e($client['city']           ?? '') ?>" /></td>
    <td class="is-input"><input type="text" value="<?= e($client['province']       ?? '') ?>" style="text-align:center;" /></td>
    <td class="is-input"><input type="text" value="<?= e($client['province']       ?? '') ?>" /></td>
    <td class="is-input"><input type="text" value="<?= e($client['region']         ?? '') ?>" /></td>
  </tr></tbody></table>

  <table><thead><tr>
    <th>Mobile No.</th><th>Birthdate (MM-DD-YYYY)</th><th style="width:28px;">Age</th><th style="width:36px;">Sex</th><th style="width:48px;">Civil Status</th><th>Occupation</th><th>Monthly Income</th>
  </tr></thead><tbody><tr>
    <td class="is-input"><input type="text" value="<?= e($client['mobile_number']  ?? '') ?>" /></td>
    <td class="is-input"><input type="text" value="<?= e($cliBD) ?>" /></td>
    <td class="is-input"><input type="text" value="<?= e($client['age']            ?? '') ?>" style="text-align:center;" /></td>
    <td class="is-input"><input type="text" value="<?= e($client['sex']            ?? '') ?>" style="text-align:center;" /></td>
    <td class="is-input"><input type="text" value="<?= e($client['civil_status']   ?? '') ?>" style="text-align:center;" /></td>
    <td class="is-input"><input type="text" value="<?= e($client['occupation']     ?? '') ?>" /></td>
    <td class="is-input"><input type="text" value="<?= e($client['monthly_income'] ?? '') ?>" /></td>
  </tr></tbody></table>

  <table><thead><tr>
    <th colspan="7" style="text-align:left;">Relasyon sa Benepisyaryo (Relationship to the Beneficiary)</th>
  </tr></thead><tbody><tr>
    <td colspan="7" class="is-input"><input type="text" value="<?= e($client['relationship_to_beneficiary'] ?? '') ?>" style="width:100%;" /></td>
  </tr></tbody></table>

  <!-- BENEFICIARY INFO -->
  <div style="margin-top:4px;">
    <div class="section-title">IMPORMASYON NG BENEPISYARYO <span class="section-subtitle">(Beneficiary's Identifying Information)</span>
      <label style="float:right; font-weight:normal; font-size:10px;">
        <input type="checkbox" <?= $sameAsAbove ? 'checked' : '' ?> disabled /> KATULAD NG NASA ITAAS
      </label>
    </div>

    <table><thead><tr>
      <th>Apelyido (Last Name)</th><th>Unang Pangalan (First Name)</th><th>Gitnang Pangalan (Middle Name)</th><th style="width:40px;">Ext.</th>
    </tr></thead><tbody><tr>
      <td class="is-input"><input type="text" value="<?= $benLast ?>" /></td>
      <td class="is-input"><input type="text" value="<?= $benFirst ?>" /></td>
      <td class="is-input"><input type="text" value="<?= $benMid ?>" /></td>
      <td class="is-input"><input type="text" value="<?= $benExt ?>" style="text-align:center;" /></td>
    </tr></tbody></table>

    <table><thead><tr>
      <th>Street Address</th><th>Barangay</th><th>City/Municipality</th><th style="width:54px;">Province</th><th>Lalawigan/Distrito</th><th>Rehiyon</th>
    </tr></thead><tbody><tr>
      <td class="is-input"><input type="text" value="<?= e($beneficiary['street_address'] ?? '') ?>" /></td>
      <td class="is-input"><input type="text" value="<?= e($beneficiary['barangay']       ?? '') ?>" /></td>
      <td class="is-input"><input type="text" value="<?= e($beneficiary['city']           ?? '') ?>" /></td>
      <td class="is-input"><input type="text" value="<?= e($beneficiary['province']       ?? '') ?>" style="text-align:center;" /></td>
      <td class="is-input"><input type="text" value="<?= e($beneficiary['province']       ?? '') ?>" /></td>
      <td class="is-input"><input type="text" value="<?= e($beneficiary['region']         ?? '') ?>" /></td>
    </tr></tbody></table>

    <table><thead><tr>
      <th>Mobile No.</th><th>Birthdate (MM-DD-YYYY)</th><th style="width:28px;">Age</th><th style="width:36px;">Sex</th><th style="width:48px;">Civil Status</th><th>Occupation</th><th>Monthly Income</th>
    </tr></thead><tbody><tr>
      <td class="is-input"><input type="text" value="<?= e($beneficiary['mobile_number']  ?? '') ?>" /></td>
      <td class="is-input"><input type="text" value="<?= e($benBD) ?>" /></td>
      <td class="is-input"><input type="text" value="<?= e($beneficiary['age']            ?? '') ?>" style="text-align:center;" /></td>
      <td class="is-input"><input type="text" value="<?= e($beneficiary['sex']            ?? '') ?>" style="text-align:center;" /></td>
      <td class="is-input"><input type="text" value="<?= e($beneficiary['civil_status']   ?? '') ?>" style="text-align:center;" /></td>
      <td class="is-input"><input type="text" value="<?= e($beneficiary['occupation']     ?? '') ?>" /></td>
      <td class="is-input"><input type="text" value="<?= e($beneficiary['monthly_income'] ?? '') ?>" /></td>
    </tr></tbody></table>

    <!-- DSWD ASSISTANCE -->
    <table style="margin-top:3px;"><tbody><tr>
      <td style="width:30%;">Nakatanggap ba kayo ng tulong mula sa DSWD?<br/><small>(Have you received any assistance from the DSWD?)</small></td>
      <td style="width:15%;"><div class="assistance-checkboxes">
        <label><input type="checkbox" <?= empty($assistanceRows) ? '' : 'checked' ?> disabled /> <?= empty($assistanceRows) ? 'Hindi' : 'Oo' ?></label>
      </div></td>
      <td style="width:55%;">
        <table class="assistance-table"><thead><tr>
          <th>Natanggap na tulong sa DSWD</th><th>Petsa ng tulong</th>
        </tr></thead><tbody>
          <?php for ($i = 0; $i < 5; $i++): $ar = $assistanceRows[$i] ?? null; ?>
          <tr>
            <td><input type="text" value="<?= e($ar['assistance_type'] ?? '') ?>" /></td>
            <td><input type="text" value="<?= e($ar['date_received']   ?? '') ?>" /></td>
          </tr>
          <?php endfor; ?>
        </tbody></table>
      </td>
    </tr></tbody></table>
  </div>

  <!-- FAMILY COMPOSITION -->
  <div style="margin-top:4px;">
    <div class="section-title">KOMPOSISYON NG PAMILYA <span class="section-subtitle">(Family Composition)</span></div>
    <table><thead><tr>
      <th>Buong Pangalan (Full Name)</th><th>Relasyon sa Benepisyaryo</th><th style="width:40px;">Edad</th><th>Hanapbuhay</th><th>Buwanang Kita</th>
    </tr></thead><tbody>
      <?php for ($i = 0; $i < 4; $i++): $fr = $familyRows[$i] ?? null; ?>
      <tr>
        <td class="is-input"><input type="text" value="<?= e($fr['full_name']                    ?? '') ?>" /></td>
        <td class="is-input"><input type="text" value="<?= e($fr['relationship_to_beneficiary']  ?? '') ?>" /></td>
        <td class="is-input"><input type="text" value="<?= e($fr['age']                          ?? '') ?>" style="text-align:center;" /></td>
        <td class="is-input"><input type="text" value="<?= e($fr['occupation']                   ?? '') ?>" /></td>
        <td class="is-input"><input type="text" value="<?= e($fr['monthly_income']               ?? '') ?>" /></td>
      </tr>
      <?php endfor; ?>
    </tbody></table>
  </div>

  <!-- CONSENT -->
  <div class="consent-section" style="margin-top:4px;">
    <div class="section-title">DEKLARASYON NG PAHINTULOT <span class="section-subtitle">(Consent Form)</span></div>
    <p class="consent-text">Ako ay nagdedeklara sa ilalim ng parusa ng pagsisinungaling (perjury), na ang lahat ng impormasyon sa aplikasyon na ito ay totoo at tama batay sa aking personal na kaalaman at mga autetikong rekord na iasinumite sa Department of Social Welfare and Development (DSWD). Anumang mali o mapanlinlang na impormasyon na ibinigay, o paggawa ng pekeng/pinagwaglit na mga dokumento ay magiging sanhi ng nararapat na hakbang na legal laban sa akin at awtomatikong magpapawalang-bisa sa anumang tulong na ibibigay kaugnay ng aplikasyon na ito.</p>
    <p class="consent-text">Ako ay sumasang-ayon na ang lahat ng personal na datos (ayon sa depinisyon sa ilalim ng Republic Act 10173 o Data Privacy Law ng 2012 at mga patnubay nito) at impormasyon o mga rekord ng mga transaksyon sa account sa DSWD ay maaaring iproseso, iprofile, o ibahagi sa mga humihiling na partido o para sa layunin ng anumang hukuman, proseso ng batas, pagsisuri, inquiry, audit, o imbestigasyon ng anumang awtoridad.</p>
    <div class="signature-container">
      <div class="signature-box"><div class="sig-line-top"></div>
        <div class="sig-label">Lagda sa ibaba ng Buong Pangalan ng Kinatawan/Kliyente<br/><small>(Signature over Printed Name of the Authorized Representative/Client)</small></div>
      </div>
      <div class="thumbmark-box">Thumbmark</div>
    </div>
  </div>

  <div class="footer-text">PAGE 1 of 4<br/>DSWD Field Office 1, Quezon Avenue, City of San Fernando, La Union, Philippines 2500<br/>Website: fo1.dswd.gov.ph &nbsp; Tel Nos.: (072)687-8000 &nbsp; Telefax: (072)888-2184</div>
</div>


<!-- ══════════════════════════════════════════════════
     PAGE 2 — GENERAL INTAKE SHEET
     ══════════════════════════════════════════════════ -->
<div class="page">
  <?= $headerLogos ?>
    <div style="font-size:10px; border:1px solid black; padding:4px 8px; line-height:1.9;">
      <div>
        <label class="cb-inline"><input type="checkbox" <?= $aics    ? 'checked' : '' ?> disabled /> AICS</label>
        <label class="cb-inline"><input type="checkbox" <?= $akap    ? 'checked' : '' ?> disabled /> AKAP</label>
        <label class="cb-inline"><input type="checkbox" <?= $othProg ? 'checked' : '' ?> disabled /> Others</label>
      </div>
      <div>
        <label class="cb-inline"><input type="radio" name="nr2" <?= $isNew    ? 'checked' : '' ?> disabled /> New</label>
        <label class="cb-inline"><input type="radio" name="nr2" <?= $isReturn ? 'checked' : '' ?> disabled /> Returning</label>
      </div>
    </div>
  </div>

  <h3>GENERAL INTAKE SHEET</h3>

  <table style="margin-top:0;"><thead><tr>
    <th>Mode</th><th>Type</th><th style="width:34%;">Date (MM / DD / YYYY)</th>
  </tr></thead><tbody><tr>
    <td>
      <label class="cb-inline"><input type="checkbox" <?= $isOnsite    ? 'checked':'' ?> disabled /> Onsite</label>
      <label class="cb-inline"><input type="checkbox" <?= $isMalasakit ? 'checked':'' ?> disabled /> Malasakit Center</label>
      <label class="cb-inline"><input type="checkbox" <?= $isOffsite   ? 'checked':'' ?> disabled /> Offsite</label>
    </td>
    <td>
      <label class="cb-inline"><input type="radio" name="wr2" <?= $isWalkin   ? 'checked':'' ?> disabled /> Walk-in</label>
      <label class="cb-inline"><input type="radio" name="wr2" <?= $isReferral ? 'checked':'' ?> disabled /> Referral</label>
    </td>
    <td style="text-align:center;">
      <input type="text" value="<?= $intakeMM ?>"   maxlength="2" style="width:26px; text-align:center;" /> /
      <input type="text" value="<?= $intakeDD ?>"   maxlength="2" style="width:26px; text-align:center;" /> /
      <input type="text" value="<?= $intakeYYYY ?>" maxlength="4" style="width:40px; text-align:center;" />
    </td>
  </tr></tbody></table>

  <!-- CLIENT NAME -->
  <div style="margin-top:5px;">
    <div class="section-title">CLIENT'S NAME</div>
    <table><thead><tr>
      <th>Last Name (Apelyido)</th><th>First Name (Unang Pangalan)</th><th>Middle Name (Gitnang Pangalan)</th><th style="width:44px;">Ext.</th>
    </tr></thead><tbody><tr>
      <td><input type="text" value="<?= $cliLast ?>" /></td>
      <td><input type="text" value="<?= $cliFirst ?>" /></td>
      <td><input type="text" value="<?= $cliMid ?>" /></td>
      <td><input type="text" value="<?= $cliExt ?>" style="text-align:center;" /></td>
    </tr></tbody></table>
  </div>

  <!-- BENEFICIARY NAME -->
  <div style="margin-top:4px;">
    <div class="section-title">BENEFICIARY'S NAME
      <label style="float:right; font-weight:normal; font-size:10px;">
        <input type="checkbox" <?= $sameAsAbove ? 'checked':'' ?> disabled /> SAME AS ABOVE
      </label>
    </div>
    <table><thead><tr>
      <th>Last Name (Apelyido)</th><th>First Name (Unang Pangalan)</th><th>Middle Name (Gitnang Pangalan)</th><th style="width:44px;">Ext.</th>
    </tr></thead><tbody><tr>
      <td><input type="text" value="<?= $benLast ?>" /></td>
      <td><input type="text" value="<?= $benFirst ?>" /></td>
      <td><input type="text" value="<?= $benMid ?>" /></td>
      <td><input type="text" value="<?= $benExt ?>" style="text-align:center;" /></td>
    </tr></tbody></table>
  </div>

  <table style="margin-top:4px;"><thead><tr>
    <th style="text-align:left;">Purpose of Assistance</th>
    <th style="text-align:left;">Diagnosis / Cause of Death <small style="font-weight:normal; font-style:italic;">(if funeral)</small></th>
  </tr></thead><tbody><tr>
    <td><input type="text" value="<?= $purpose ?>" /></td>
    <td><input type="text" value="<?= $diagnosis ?>" /></td>
  </tr></tbody></table>

  <table style="margin-top:3px;"><thead><tr>
    <th style="text-align:left;">Mode of Assistance</th>
    <th style="text-align:left; width:30%;">Amount Needed</th>
  </tr></thead><tbody><tr>
    <td><input type="text" value="<?= $modeOfAsst ?>" style="width:100%; border:none; height:16px; font-size:10px; background:transparent;" /></td>
    <td><strong style="font-size:10px;">PhP&nbsp;</strong>
      <input type="text" value="<?= $amount ?>" style="width:110px; border:none; border-bottom:1.5px solid #000; font-weight:bold; font-size:10px; text-align:right; background:transparent;" />
    </td>
  </tr></tbody></table>

  <div style="margin-top:4px;">
    <div class="section-title">I. &nbsp;INCOME AND FINANCIAL RESOURCES</div>
    <div class="section-block">
      <div style="font-weight:bold; font-size:10px; margin-bottom:2px;">Occupation/s of family member</div>
      <div><label class="cb-inline"><input type="checkbox" <?= $incEmployed ? 'checked':'' ?> disabled /> Employed</label><span style="font-style:italic; font-size:9.5px;">(indicate number of members working)&nbsp;<input type="text" class="ul-input" style="width:90px;" value="<?= $incEmployed ?>" /></span></div>
      <div><label class="cb-inline"><input type="checkbox" <?= $incSeasonal ? 'checked':'' ?> disabled /> Seasonal Employee</label><span style="font-style:italic; font-size:9.5px;">(indicate number of members working)&nbsp;<input type="text" class="ul-input" style="width:90px;" value="<?= $incSeasonal ?>" /></span></div>
      <div><label class="cb-inline"><input type="checkbox" <?= $combIncome ? 'checked':'' ?> disabled /> Combined family income&nbsp;</label><input type="text" class="ul-input" style="width:140px;" value="<?= $combIncome ?>" /></div>
      <div style="margin-top:2px;">
        <label class="cb-inline"><input type="checkbox" <?= $insurance ? 'checked':'' ?> disabled /> Insurance coverage</label>
        <label class="cb-inline"><input type="checkbox" <?= $savings   ? 'checked':'' ?> disabled /> Savings</label>
      </div>
    </div>
  </div>

  <div style="margin-top:4px;">
    <div class="section-title">II. &nbsp;BUDGET AND EXPENSES</div>
    <div class="section-block">
      <label class="cb-inline"><input type="checkbox" <?= $hasMonthlyExp ? 'checked':'' ?> disabled /> Monthly expenses of the family</label>
      <div style="font-style:italic; font-size:9.5px; margin-left:18px; margin-bottom:2px;">(Utility bills, Maintenance and Medication, Mortgage/Rent, Debt, and Others)</div>
      <label class="cb-inline"><input type="checkbox" <?= $hasEmergFund ? 'checked':'' ?> disabled /> Availability of emergency fund</label>
    </div>
  </div>

  <div style="margin-top:4px;">
    <div class="section-title">III. &nbsp;SEVERITY OF THE CRISIS</div>
    <div class="section-block">
      <div style="font-weight:bold; font-size:10px; margin-bottom:2px;">How long does the patient suffer from the disease?</div>
      <label class="cb-inline"><input type="checkbox" <?= $severityCrisis==='Recently diagnosed (3mos & below)' ?'checked disabled':'disabled' ?> /> Recently diagnosed (3mos &amp; below)</label>
      <label class="cb-inline"><input type="checkbox" <?= $severityCrisis==='3 months to a year'               ?'checked disabled':'disabled' ?> /> 3 months to a year</label>
      <label class="cb-inline"><input type="checkbox" <?= $severityCrisis==='Chronic or lifelong'              ?'checked disabled':'disabled' ?> /> Chronic or lifelong</label>
      <label class="cb-inline"><input type="checkbox" <?= $severityCrisis==='Not applicable'                   ?'checked disabled':'disabled' ?> /> Not applicable</label>
      <div style="font-weight:bold; font-size:10px; margin-top:3px; margin-bottom:2px;">In the past three (3) months, did the family experience at least one crisis?</div>
      <label class="cb-inline"><input type="checkbox" <?= $expCrisis  ?'checked disabled':'disabled' ?> /> YES</label>
      <label class="cb-inline"><input type="checkbox" <?= !$expCrisis ?'checked disabled':'disabled' ?> /> NO</label>
      <div style="font-weight:bold; font-size:10px; margin-top:3px; margin-bottom:2px;">If yes, which among the following crises did the family experience (check all that apply):</div>
      <?php
      $crisisArr = array_map('trim', explode(',', $intake['crisis_details'] ?? ''));
      $crisisList = ['Hospitalization','Death of a family member','Catastrophic Event (fire, earthquake, flooding, etc.)','Disablement','Loss of Livelihood'];
      foreach ($crisisList as $c):
      ?><label class="cb-inline"><input type="checkbox" <?= in_array($c,$crisisArr)?'checked disabled':'disabled' ?> /> <?= e($c) ?></label><?php endforeach; ?>
      <label class="cb-inline"><input type="checkbox" disabled /> Others, specify <span class="line-input"></span></label>
    </div>
  </div>

  <div style="margin-top:4px;">
    <div class="section-title">IV. &nbsp;AVAILABILITY OF SUPPORT SYSTEMS</div>
    <div class="section-block">
      <?php
      $suppArr = array_map('trim', explode(',', $intake['support_systems'] ?? ''));
      foreach (['Family','Relatives','Friend/s','Employer','Church/Community Organization'] as $s):
      ?><label class="cb-inline"><input type="checkbox" <?= in_array($s,$suppArr)?'checked disabled':'disabled' ?> /> <?= e($s) ?></label><?php endforeach; ?>
    </div>
  </div>

  <div style="margin-top:4px;">
    <div class="section-title">V. &nbsp;EXTERNAL RESOURCES TAPPED BY THE FAMILY</div>
    <div class="section-block">
      <?php
      $extArr = array_map('trim', explode(',', $intake['external_resources'] ?? ''));
      foreach (['Philhealth','Health Card','Guarantee Letter from other agencies','MSS Discount','Senior Citizen Discount','PWD Discount','Not applicable'] as $r):
      ?><label class="cb-inline"><input type="checkbox" <?= in_array($r,$extArr)?'checked disabled':'disabled' ?> /> <?= e($r) ?></label><?php endforeach; ?>
      <label class="cb-inline"><input type="checkbox" disabled /> Others, specify <span class="line-input"></span></label>
    </div>
  </div>

  <div style="margin-top:4px;">
    <div class="section-title">VI. &nbsp;SELF HELP AND CLIENT EFFORTS</div>
    <div class="section-block">
      <?php
      $shArr = array_map('trim', explode(',', $intake['self_help'] ?? ''));
      $sh1 = 'Successfully sought employment opportunities or explored additional income sources';
      $sh2 = 'Successfully reached out to relevant organization';
      ?>
      <label class="cb-inline"><input type="checkbox" <?= in_array($sh1,$shArr)?'checked disabled':'disabled' ?> /> <?= e($sh1) ?></label>
      <label class="cb-inline"><input type="checkbox" <?= in_array($sh2,$shArr)?'checked disabled':'disabled' ?> /> <?= e($sh2) ?></label>
    </div>
  </div>

  <div style="margin-top:4px;">
    <div class="section-title">VII. &nbsp;VULNERABILITY AND RISK FACTORS</div>
    <div class="section-block">
      <?php
      $vulnArr = array_map('trim', explode(',', $intake['vulnerability_risk'] ?? ''));
      $v1 = 'There are elderly / Child in need / PWD / Pregnant in the household';
      $v2 = 'A member is physically or mentally incapacitated to work';
      $v3 = 'Inability to secure stable employment';
      ?>
      <label class="cb-inline"><input type="checkbox" <?= in_array($v1,$vulnArr)?'checked disabled':'disabled' ?> /> <?= e($v1) ?></label>
      <label class="cb-inline"><input type="checkbox" <?= in_array($v2,$vulnArr)?'checked disabled':'disabled' ?> /> <?= e($v2) ?></label>
      <label class="cb-inline"><input type="checkbox" <?= in_array($v3,$vulnArr)?'checked disabled':'disabled' ?> /> <?= e($v3) ?></label>
    </div>
  </div>

  <div class="footer-text">PAGE 2 of 4<br/>DSWD Field Office 1, Quezon Avenue, City of San Fernando, La Union, Philippines 2500<br/>Website: fo1.dswd.gov.ph &nbsp; Tel Nos.: (072)607-6000 &nbsp; Telefax: (072)888-2184</div>
</div>


<!-- ══════════════════════════════════════════════════
     PAGE 3 — GENERAL INTAKE SHEET (Continuation)
     ══════════════════════════════════════════════════ -->
<div class="page">
  <div style="font-size:9px; color:#555; margin-bottom:4px; text-align:right; font-style:italic;">General Intake Sheet — Continuation</div>

  <!-- VII. CLIENT SECTOR -->
  <div class="section-title">VII. CLIENT SECTOR</div>
  <table class="fixed-layout"><tbody><tr>
    <td style="width:30%; vertical-align:top;">
      <?php foreach ($incomeSectorList as $sec): ?>
      <label class="cb"><input type="checkbox" <?= chk($sectors, $sec) ?> /> <?= e($sec) ?></label>
      <?php endforeach; ?>
    </td>
    <td style="width:28%; vertical-align:top;">
      <label class="cb"><input type="checkbox" <?= chk($sectors, 'Recovering Person Who Used Drugs') ?> /> Recovering Person Who Used Drugs</label>
      <label class="cb"><input type="checkbox" <?= chk($sectors, 'Minimum Wage Earner') ?> /> Minimum Wage Earner</label>
      <label class="cb" style="display:flex; align-items:center; flex-wrap:wrap;">
        <input type="checkbox" <?= chk($sectors, 'Below Minimum Wage') ?> style="flex-shrink:0;" /> Below Minimum Wage &nbsp;
        <input type="text" style="width:60px;" />
      </label>
      <label class="cb" style="display:flex; align-items:center; flex-wrap:wrap;">
        <input type="checkbox" disabled style="flex-shrink:0;" /> Earning (specify approx. monthly) &nbsp;
        <input type="text" style="width:55px;" />
      </label>
    </td>
    <td style="width:2%; padding:0;" class="vertical-text">Specify Sub-Category:</td>
    <td style="width:20%; vertical-align:top;">
      <?php foreach ($subCatList as $tag): ?>
      <label class="cb"><input type="checkbox" <?= chk($subcategories, $tag) ?> /> <?= e($tag) ?></label>
      <?php endforeach; ?>
    </td>
    <td style="width:20%; vertical-align:top; border-left:1px solid black;">
      <div class="col-header" style="margin:-2px -5px 3px -5px;">Type of Disability</div>
      <?php foreach ($disabilityList as $dis): ?>
      <label class="cb"><input type="checkbox" <?= chk($subcategories, $dis) ?> /> <?= e($dis) ?></label>
      <?php endforeach; ?>
    </td>
  </tr></tbody></table>

  <!-- VIII. SOURCE OF INCOME -->
  <div class="section-title" style="margin-top:3px;">VIII. SOURCE OF INCOME AND AMOUNT</div>
  <?php
  $srcArr = array_map('trim', explode(',', $intake['source_of_income'] ?? ''));
  $srcList = [
    'Salaries/Wages from Employment',
    'Entrepreneurial Income/Profits',
    'Cash Remittances from domestic source',
    'Cash assistance from abroad',
    'Pensioners from the government (e.g., AFP\'s)',
    'Transfers',
    'Other income',
  ];
  ?>
  <table class="fixed-layout"><thead><tr>
    <th class="col-header" style="width:42%;">Source of Income</th>
    <th class="col-header" style="width:28%;">Other</th>
    <th class="col-header" style="width:30%; text-align:right;">Amount (Php)</th>
  </tr></thead><tbody><tr>
    <td style="vertical-align:top;">
      <?php foreach ($srcList as $src): ?>
      <label class="cb"><input type="checkbox" <?= in_array($src,$srcArr)?'checked disabled':'disabled' ?> /> <?= e($src) ?></label>
      <?php endforeach; ?>
    </td>
    <td style="vertical-align:top;">
      <label class="cb"><input type="checkbox" <?= in_array('No Regular Income',$srcArr)?'checked disabled':'disabled' ?> /> No Regular Income</label>
      <div style="margin-top:6px; font-size:10px;">Other Regular Income:</div>
      <input type="text" style="width:95%; border:none; border-bottom:1px solid black;" />
    </td>
    <td style="vertical-align:top; text-align:right;">
      <?php for ($i = 0; $i < 7; $i++): ?>
      <input type="number" step="any" style="width:90px; text-align:right; border:none; border-bottom:1px solid black;" /><br/>
      <?php endfor; ?>
    </td>
  </tr>
  <tr>
    <td colspan="2" style="text-align:right; font-weight:bold; font-size:10px; vertical-align:middle;">Total income in the past 6 months</td>
    <td style="text-align:right; font-weight:bold; vertical-align:middle;">Php <input type="number" step="any" value="<?= e($total6months) ?>" style="width:90px; text-align:right; border:none; border-bottom:1px solid black;" /></td>
  </tr></tbody></table>

  <!-- IX. PROBLEM PRESENTED -->
  <div class="section-title" style="margin-top:3px;">IX. PROBLEM PRESENTED</div>
  <table class="fixed-layout"><tbody><tr>
    <td style="min-height:62px; padding:4px; font-size:10px; vertical-align:top;"><?= $problemPresented ?></td>
  </tr></tbody></table>

  <!-- X. SOCIAL WORKER'S ASSESSMENT -->
  <div class="section-title" style="margin-top:3px;">X. SOCIAL WORKER'S ASSESSMENT</div>
  <table class="fixed-layout"><tbody><tr>
    <td style="min-height:75px; padding:4px; font-size:10px; vertical-align:top;"><?= $swAssessment ?></td>
  </tr></tbody></table>

  <!-- ASSISTANCE TYPES -->
  <table class="fixed-layout" style="margin-top:3px;"><thead><tr>
    <th class="col-header" style="width:22%;">FINANCIAL ASSISTANCE</th>
    <th class="col-header" style="width:24%;">MATERIAL ASSISTANCE</th>
    <th class="col-header" style="width:20%;">PSYCHOSOCIAL SUPPORT</th>
    <th class="col-header" style="width:34%;">Purpose of Assistance</th>
  </tr></thead><tbody><tr>
    <td style="vertical-align:top;">
      <label class="cb"><input type="checkbox" <?= $faFood      ? 'checked disabled':'disabled' ?> /> Food Assistance</label>
      <label class="cb"><input type="checkbox" <?= $faCash      ? 'checked disabled':'disabled' ?> /> Cash Relief Assistance</label>
      <label class="cb"><input type="checkbox" <?= $faMedical   ? 'checked disabled':'disabled' ?> /> Medical</label>
      <label class="cb"><input type="checkbox" <?= $faFuneral   ? 'checked disabled':'disabled' ?> /> Funeral</label>
      <label class="cb"><input type="checkbox" <?= $faTransport ? 'checked disabled':'disabled' ?> /> Transportation</label>
      <label class="cb"><input type="checkbox" <?= $faEduc      ? 'checked disabled':'disabled' ?> /> Educational</label>
    </td>
    <td style="vertical-align:top;">
      <label class="cb"><input type="checkbox" <?= $maFFP  ? 'checked disabled':'disabled' ?> /> Family Food Packs</label>
      <label class="cb"><input type="checkbox" <?= $maOFI  ? 'checked disabled':'disabled' ?> /> Other Food Items</label>
      <label class="cb"><input type="checkbox" <?= $maHSK  ? 'checked disabled':'disabled' ?> /> Hygiene &amp; Sleeping Kits</label>
      <label class="cb"><input type="checkbox" <?= $maADT  ? 'checked disabled':'disabled' ?> /> Assistive Devices &amp; Technologies</label>
      <label class="cb"><input type="checkbox" <?= $maRice ? 'checked disabled':'disabled' ?> /> Rice</label>
    </td>
    <td style="vertical-align:top;">
      <label class="cb"><input type="checkbox" <?= $psPFA     ? 'checked disabled':'disabled' ?> /> Psychosocial First Aid (PFA)</label>
      <label class="cb"><input type="checkbox" <?= $psCounsel ? 'checked disabled':'disabled' ?> /> Social Work Counseling</label>
    </td>
    <td style="vertical-align:top; padding-left:8px;">
      1. <input type="text" class="purpose-input" value="<?= $purpose ?>" /><br/>
      2. <input type="text" class="purpose-input" value="<?= $diagnosis ?>" /><br/>
      3. <input type="text" class="purpose-input" />
    </td>
  </tr></tbody></table>

  <!-- XI. DAILY NEEDS -->
  <div class="section-title" style="margin-top:3px;">XI. DAILY NEEDS</div>
  <table class="fixed-layout"><thead><tr>
    <th class="col-header" style="width:60%;">Description</th>
    <th class="col-header" style="width:40%; text-align:right; padding-right:10px;">Amount</th>
  </tr></thead><tbody>
    <tr>
      <td style="height:44px;"></td>
      <td style="text-align:right; font-weight:bold; padding-right:8px; vertical-align:middle;">
        Php <input type="text" value="<?= $amount ?>" style="width:100px; text-align:right; border:none; border-bottom:1px solid black;" />
      </td>
    </tr>
    <tr><td colspan="2" style="text-align:center; font-weight:bold; font-size:10px;">Fund Source</td></tr>
    <tr><td colspan="2" style="text-align:center; font-weight:bold; font-size:10px;"><?= $intakeYYYY ?></td></tr>
  </tbody></table>

  <!-- SIGNATURES -->
  <table class="fixed-layout" style="margin-top:3px;"><tbody><tr>
    <td class="signature-cell" style="width:33%;">
      Reviewed &amp; Approved by:<br/><hr class="sig-hr"/>Approving Authority<br/><small>(Signature over Printed Name)</small>
    </td>
    <td class="signature-cell" style="width:33%;">
      Interviewed by:<br/><hr class="sig-hr"/>Social Worker<br/><small>(Signature over Printed Name)</small><br/>License No. _____
    </td>
    <td style="width:34%; vertical-align:bottom; padding:4px 6px; font-size:9px; text-align:right;">
      DSWD Field Office 1, Gawad Kalinga, Luzon Avenue, Quezon City<br/>
      Tel.no. (02) 9292-5300<br/>
      Website: icdd.dswd.gov.ph | Email: icdd.dswd@gmail.com<br/>
      PAGE 8 of 8
    </td>
  </tr></tbody></table>

  <div class="footer-text">PAGE 3 of 4<br/>DSWD Field Office 1, Quezon Avenue, City of San Fernando, La Union, Philippines 2500<br/>Website: fo1.dswd.gov.ph &nbsp; Tel Nos.: (072)687-8000 &nbsp; Telefax: (072)888-2184</div>
</div>


<!-- ══════════════════════════════════════════════════
     PAGE 4 — CERTIFICATE OF ELIGIBILITY
     ══════════════════════════════════════════════════ -->
<div class="page">
  <?= $headerLogos ?>
    <div style="font-size:10px; border:1px solid black; padding:4px 8px; line-height:1.9;">
      <div><input type="checkbox" disabled /> <label># Central Office</label></div>
      <div><input type="checkbox" disabled /> <label># Field Office I</label></div>
    </div>
  </div>

  <h3>CERTIFICATE OF ELIGIBILITY</h3>

  <table style="margin-top:0;"><thead><tr>
    <th style="width:12%;">QN</th><th style="width:58%;">PCN</th><th style="width:30%;">Date (MM / DD / YYYY)</th>
  </tr></thead><tbody><tr>
    <td><input type="text" style="width:100%; border:none; height:16px;" /></td>
    <td><span class="pcn-box-coe"><?php for($i=0;$i<20;$i++): ?><input type="text" maxlength="1" /><?php endfor; ?></span></td>
    <td style="text-align:center;">
      <input type="text" value="<?= $intakeMM ?>"   maxlength="2" style="width:26px; text-align:center;" /> /
      <input type="text" value="<?= $intakeDD ?>"   maxlength="2" style="width:26px; text-align:center;" /> /
      <input type="text" value="<?= $intakeYYYY ?>" maxlength="4" style="width:40px; text-align:center;" />
    </td>
  </tr></tbody></table>

  <table style="margin-top:2px;"><thead><tr>
    <th>Program</th><th>Client Type</th><th>Mode</th><th>Source</th>
  </tr></thead><tbody><tr>
    <td>
      <label class="cb-inline"><input type="checkbox" <?= $aics    ? 'checked':'' ?> disabled /> AICS</label>
      <label class="cb-inline"><input type="checkbox" <?= $akap    ? 'checked':'' ?> disabled /> AKAP</label>
      <label class="cb-inline"><input type="checkbox" <?= $othProg ? 'checked':'' ?> disabled /> Others</label>
    </td>
    <td>
      <label class="cb-inline"><input type="radio" name="nr4" <?= $isNew    ? 'checked':'' ?> disabled /> New</label>
      <label class="cb-inline"><input type="radio" name="nr4" <?= $isReturn ? 'checked':'' ?> disabled /> Returning</label>
    </td>
    <td>
      <label class="cb-inline"><input type="checkbox" <?= $isOnsite    ? 'checked':'' ?> disabled /> Onsite</label>
      <label class="cb-inline"><input type="checkbox" <?= $isMalasakit ? 'checked':'' ?> disabled /> Malasakit</label>
      <label class="cb-inline"><input type="checkbox" <?= $isOffsite   ? 'checked':'' ?> disabled /> Offsite</label>
    </td>
    <td>
      <label class="cb-inline"><input type="radio" name="wr4" <?= $source==='Walk-in'  ? 'checked':'' ?> disabled /> Walk-in</label>
      <label class="cb-inline"><input type="radio" name="wr4" <?= $source==='Referral' ? 'checked':'' ?> disabled /> Referral</label>
    </td>
  </tr></tbody></table>

  <!-- CERTIFICATION -->
  <div style="margin-top:5px;">
    <div class="section-title">CERTIFICATION</div>
    <table><tbody>
      <tr><td style="font-size:10px; line-height:1.8;">
        This is to certify that
        <input type="text" class="ul-input" style="width:300px;" value="<?= e($benFullName) ?>" />,
        and presently residing at
        <input type="text" class="ul-input" style="width:270px;" value="<?= $benAddress ?>" />.
      </td></tr>
      <tr><td>
        <table style="margin-top:0; border:none;"><tr>
          <td style="border:none; padding:2px 4px 2px 0; width:50%;"><span style="font-size:10px;">Sex: </span><input type="text" class="ul-input" style="width:140px;" value="<?= e($beneficiary['sex'] ?? '') ?>" /></td>
          <td style="border:none; padding:2px 0; width:50%;"><span style="font-size:10px;">Age: </span><input type="text" class="ul-input" style="width:140px;" value="<?= e($beneficiary['age'] ?? '') ?>" /></td>
        </tr><tr>
          <td colspan="2" style="border:none; padding:2px 4px 2px 0;">
            <span style="font-size:10px;">Birthdate: </span>
            <input type="text" style="width:30px; text-align:center;" value="<?= e($benBDparts[1] ?? '') ?>" maxlength="2" placeholder="MM" /> /
            <input type="text" style="width:30px; text-align:center;" value="<?= e($benBDparts[2] ?? '') ?>" maxlength="2" placeholder="DD" /> /
            <input type="text" style="width:44px; text-align:center;" value="<?= e($benBDparts[0] ?? '') ?>" maxlength="4" />
          </td>
        </tr></table>
      </td></tr>
      <tr><td style="font-size:10px;">has been found eligible for assistance after the assessment and validation conducted, for him/herself or in representation of his/her family.</td></tr>
    </tbody></table>
  </div>

  <!-- RECORDS OF THE CASE -->
  <div style="margin-top:4px;">
    <div class="section-title">RECORDS OF THE CASE <span class="section-subtitle">(confidentially filed at the Crisis Intervention Program)</span></div>
    <table><tbody><tr>
      <td style="width:22%; vertical-align:top;">
        <label class="cb"><input type="checkbox" disabled /> General Intake Sheet</label>
        <label class="cb"><input type="checkbox" disabled /> Justification</label>
        <label class="cb"><input type="checkbox" disabled /> Valid I.D. Presented</label>
      </td>
      <td style="width:30%; vertical-align:top;">
        <label class="cb"><input type="checkbox" disabled /> Medical Certificate / Abstract</label>
        <label class="cb"><input type="checkbox" disabled /> Prescriptions</label>
        <label class="cb"><input type="checkbox" disabled /> Statement of Account</label>
        <label class="cb"><input type="checkbox" disabled /> Treatment Protocol</label>
        <label class="cb"><input type="checkbox" disabled /> Quotation/Charge Slip</label>
        <label class="cb"><input type="checkbox" disabled /> Discharge Summary</label>
        <label class="cb"><input type="checkbox" disabled /> Social Case Study Report</label>
        <label class="cb"><input type="checkbox" disabled /> Case Summary Report</label>
      </td>
      <td style="width:27%; vertical-align:top;">
        <label class="cb"><input type="checkbox" disabled /> Laboratory Request</label>
        <label class="cb"><input type="checkbox" disabled /> Promissory Note / Cert. of Balance</label>
        <label class="cb"><input type="checkbox" disabled /> Funeral Contract</label>
        <label class="cb"><input type="checkbox" disabled /> Transfer Permit</label>
        <label class="cb"><input type="checkbox" disabled /> Death Certificate</label>
        <label class="cb"><input type="checkbox" disabled /> Death Summary</label>
        <label class="cb"><input type="checkbox" disabled /> Referral Letter</label>
      </td>
      <td style="width:21%; vertical-align:top;">
        <label class="cb"><input type="checkbox" disabled /> Contract of Employment</label>
        <label class="cb"><input type="checkbox" disabled /> Certificate of Employment</label>
        <label class="cb"><input type="checkbox" disabled /> Certificate of Attestation</label>
        <label class="cb"><input type="checkbox" disabled /> Income Tax Return</label>
        <label class="cb"><input type="checkbox" disabled /> Others</label>
      </td>
    </tr></tbody></table>
  </div>

  <!-- OUTRIGHT CASH / GL -->
  <div class="box-container">
    <div class="box">
      <div class="box-title">IF OUTRIGHT CASH</div>
      <p>The client is hereby recommended to receive
        <input type="text" class="ul-input" style="width:70px;" placeholder="FOOD" /> assistance for
        <input type="text" class="ul-input" style="width:120px;" placeholder="DAILY NEEDS" /> in the amount of
        <input type="text" class="ul-input" style="width:110px;" placeholder="THOUSAND PESOS" />
      </p>
      <p>PhP <input type="text" style="width:90px; border:1px solid black; text-align:right;" value="<?= $amount ?>" /> <small>Amount in figures</small></p>
    </div>
    <div class="box">
      <div class="box-title">IF GUARANTEE LETTER</div>
      <p>GL No. <input type="text" class="ul-input" style="width:100px;" /></p>
      <p>The client is hereby recommended to receive
        <input type="text" class="ul-input" style="width:80px;" /> assistance for
        <input type="text" class="ul-input" style="width:100px;" placeholder="THOUSAND PESOS" />
        PhP <input type="text" style="width:70px; border:1px solid black; text-align:right;" placeholder=".000.00" />
      </p>
      <p>payable to <input type="text" class="ul-input" style="width:170px;" /></p>
      <p><input type="text" class="ul-input" style="width:220px;" placeholder="Name of Service Provider" /></p>
      <p><input type="text" class="ul-input" style="width:220px;" placeholder="Address of Service Provider" /></p>
    </div>
  </div>

  <!-- SIGNATURES -->
  <div class="sig-section" style="margin-top:10px;">
    <div class="sig-block">
      <div class="sig-line-bottom"></div>
      <div>Social Worker</div>
      <div style="font-size:9px; font-style:italic;">(Signature over Printed Name)</div>
      <div style="font-size:9px; font-style:italic;">License Number: ___________</div>
    </div>
    <div class="sig-block">
      <div class="sig-line-bottom"></div>
      <div>Approving Authority</div>
      <div style="font-size:9px; font-style:italic;">(Signature over Printed Name)</div>
    </div>
  </div>

  <!-- ACKNOWLEDGMENT RECEIPT -->
  <div class="acknowledgment">ACKNOWLEDGMENT RECEIPT</div>

  <table style="margin-top:2px;"><thead><tr>
    <th style="width:40%; text-align:left;">Date (MM / DD / YYYY)</th>
    <th style="text-align:left;">Amount Received</th>
  </tr></thead><tbody><tr>
    <td>
      <input type="text" maxlength="2" style="width:28px; text-align:center;" placeholder="MM" /> /
      <input type="text" maxlength="2" style="width:28px; text-align:center;" placeholder="DD" /> /
      <input type="text" maxlength="4" style="width:42px; text-align:center;" value="<?= $intakeYYYY ?>" />
    </td>
    <td style="font-size:10px;">
      I acknowledge receipt of assistance in the amount of
      <input type="text" class="ul-input" style="width:130px;" /> THOUSAND PESOS
      PhP <input type="text" style="width:80px; border:1px solid black; text-align:right;" placeholder=".000.00" />
    </td>
  </tr></tbody></table>

  <div class="sig-block-center" style="margin-top:18px;">
    <div class="sig-line-bottom"></div>
    <div>Client</div>
    <div style="font-size:9px; font-style:italic;">(Signature over Printed Name)</div>
  </div>

  <div class="footer-text">PAGE 4 of 4<br/>DSWD Field Office 1, Quezon Avenue, City of San Fernando, La Union, Philippines 2500<br/>Website: fo1.dswd.gov.ph &nbsp; Tel Nos.: (072)687-8000 &nbsp; Telefax: (072)888-2184</div>
</div>

</body>
</html>