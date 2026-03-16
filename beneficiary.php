<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'MSWDO') {
    echo "<tr><td colspan='8' class='text-center'>Access Denied</td></tr>";
    exit();
}

/* FETCH BARANGAYS */
$barangays = [];
$bQuery = "SELECT DISTINCT barangay FROM beneficiaries WHERE status='active' ORDER BY barangay ASC";
$bResult = mysqli_query($conn, $bQuery);

while($bRow = mysqli_fetch_assoc($bResult)){
    $barangays[] = $bRow['barangay'];
}

/* FETCH SECTORS */
$sectors = [];

$sQuery = "SELECT DISTINCT sector_name 
           FROM beneficiary_sectors 
           ORDER BY sector_name ASC";

$sResult = mysqli_query($conn,$sQuery);

while($sRow = mysqli_fetch_assoc($sResult)){
    $sectors[] = $sRow['sector_name'];
}

/* FETCH SUBCATEGORIES */
$subcategories = [];

$scQuery = "SELECT DISTINCT subcategory_name 
            FROM beneficiary_subcategories
            ORDER BY subcategory_name ASC";

$scResult = mysqli_query($conn,$scQuery);

while($scRow = mysqli_fetch_assoc($scResult)){
    $subcategories[] = $scRow['subcategory_name'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Beneficiaries - MSWDO</title>

<link rel="stylesheet" href="css/bootstrap.min.css">
<link rel="stylesheet" href="css/bootstrap-icons-1.11.0/bootstrap-icons.css">
<link rel="stylesheet" href="css/style.css">

<script src="js/jquery-4.0.0.min.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>

<style>

#sectorMenu{
    max-height:250px;
    overflow-y:auto;
}

#sectorMenu .dropdown-item{
    font-size:10px;
    padding:4px 10px;
}

</style>

</head>

<body>

<div class="content">

<?php include 'includes/topbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="container-fluid mt-3">

<div class="d-flex justify-content-between align-items-center mb-2">

<h4>Beneficiary List</h4>

<div class="d-flex gap-2">

<a href="add_beneficiary.php" class="btn btn-primary btn-sm">
<i class="bi bi-person-plus"></i> Add Beneficiary
</a>

<button id="btnPrint" class="btn btn-secondary btn-sm">
<i class="bi bi-printer"></i> Print List
</button>

</div>
</div>

<!-- ALERTS -->

<?php if(isset($_GET['success'])){ ?>
<div class="alert alert-success alert-dismissible fade show">
<i class="bi bi-check-circle"></i>
Beneficiary successfully added.
<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php } ?>

<?php if(isset($_GET['duplicate'])){ ?>
<div class="alert alert-warning alert-dismissible fade show">
<i class="bi bi-exclamation-triangle"></i>
Warning: A beneficiary with the same name and birthdate already exists in the system. Please verify the records to avoid duplicates.
<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php } ?>


<div class="card shadow p-3">

<!-- FILTERS -->
<div class="row g-2 mb-3">

<div class="col-md-3">
<input type="text" id="search" placeholder="Search name or barangay..." class="form-control">
</div>

<div class="col-md-2">
<select id="barangay" class="form-control">
<option value="">All Barangays</option>
<?php foreach($barangays as $bName): ?>
<option value="<?= htmlspecialchars($bName) ?>">
<?= htmlspecialchars($bName) ?>
</option>
<?php endforeach; ?>
</select>
</div>

<div class="col-md-2">
<div class="dropdown w-100">
    <button class="btn btn-outline-secondary dropdown-toggle w-100 text-start"
            type="button"
            id="sectorDropdown"
            data-bs-toggle="dropdown">

        Select Sectors
    </button>

    <ul class="dropdown-menu p-2" id="sectorMenu" style="min-width:300px;">

<li class="dropdown-header">Sectors</li>

<?php foreach($sectors as $sectorName): ?>

<li>
<a class="dropdown-item sector-item"
href="#"
data-value="<?= htmlspecialchars($sectorName) ?>">

<?= htmlspecialchars($sectorName) ?>

</a>
</li>

<?php endforeach; ?>

<li><hr class="dropdown-divider"></li>

<li class="dropdown-header">Sub Categories</li>

<?php foreach($subcategories as $sub): ?>

<li>
<a class="dropdown-item sector-item"
href="#"
data-value="<?= htmlspecialchars($sub) ?>">

<?= htmlspecialchars($sub) ?>

</a>
</li>

<?php endforeach; ?>

</ul>
</div>
</div>

<div class="col-md-1">
<input type="number" id="income_from" class="form-control" placeholder="Min ₱">
</div>

<div class="col-md-1">
<input type="number" id="income_to" class="form-control" placeholder="Max ₱">
</div>

<div class="col-md-1">
<select id="fourps" class="form-control">
<option value="">4Ps</option>
<option value="1">Yes</option>
<option value="0">No</option>
</select>
</div>

<div class="col-md-1">
<select id="philhealth" class="form-control">
<option value="">PhilHealth</option>
<option value="1">Yes</option>
<option value="0">No</option>
</select>
</div>

<div class="col-md-1">
<select id="sortBy" class="form-control">
<option value="">Sort</option>
<option value="az">Name A-Z</option>
<option value="za">Name Z-A</option>
<option value="high_priority">High Priority</option>
<option value="low_priority">Low Priority</option>
</select>
</div>

</div>

<!-- TABLE -->
<div id="beneficiaryTable"></div>

</div>

</div>
</div>


<script>

    let selectedSectors = [];

$(document).on('click','.sector-item',function(e){

e.preventDefault();
e.stopPropagation(); // keeps dropdown open

let value = $(this).data('value');

if(selectedSectors.includes(value)){

    selectedSectors = selectedSectors.filter(v => v !== value);
    $(this).removeClass('active');

}else{

    selectedSectors.push(value);
    $(this).addClass('active');

}

updateSectorButton();

saveFilters();

loadBeneficiaries(
$('#search').val(),
$('#barangay').val(),
selectedSectors,
$('#income_from').val(),
$('#income_to').val(),
$('#fourps').val(),
$('#philhealth').val(),
$('#sortBy').val(),
1
);

});

function updateSectorButton(){

if(selectedSectors.length === 0){

$('#sectorDropdown').text("Select Sectors");

}else if(selectedSectors.length === 1){

$('#sectorDropdown').text(selectedSectors[0]);

}else{

$('#sectorDropdown').text(selectedSectors.length + " Sectors Selected");

}

}

    function saveFilters(){

sessionStorage.setItem('search',$('#search').val());
sessionStorage.setItem('barangay',$('#barangay').val());
sessionStorage.setItem('sector', JSON.stringify(selectedSectors));
sessionStorage.setItem('income_from',$('#income_from').val());
sessionStorage.setItem('income_to',$('#income_to').val());
sessionStorage.setItem('fourps',$('#fourps').val());
sessionStorage.setItem('philhealth',$('#philhealth').val());
sessionStorage.setItem('sort',$('#sortBy').val());

}

function loadSavedFilters(){

if(sessionStorage.getItem('search')){
$('#search').val(sessionStorage.getItem('search'));
}

if(sessionStorage.getItem('barangay')){
$('#barangay').val(sessionStorage.getItem('barangay'));
}

let savedSector = sessionStorage.getItem('sector');

if(savedSector){

    selectedSectors = JSON.parse(savedSector);

    selectedSectors.forEach(function(sec){

        $('.sector-item[data-value="'+sec+'"]').addClass('active');

    });

    updateSectorButton();

}

if(sessionStorage.getItem('income_from')){
$('#income_from').val(sessionStorage.getItem('income_from'));
}

if(sessionStorage.getItem('income_to')){
$('#income_to').val(sessionStorage.getItem('income_to'));
}

if(sessionStorage.getItem('fourps')){
$('#fourps').val(sessionStorage.getItem('fourps'));
}

if(sessionStorage.getItem('philhealth')){
$('#philhealth').val(sessionStorage.getItem('philhealth'));
}

if(sessionStorage.getItem('sort')){
$('#sortBy').val(sessionStorage.getItem('sort'));
}

}

/* LOAD BENEFICIARIES */
function loadBeneficiaries(search='',barangay='',sector=[],income_from='',income_to='',fourps='',philhealth='',sort='',page=1){

$.ajax({
url:'beneficiary_table.php',
type:'GET',

data:{
search:search,
barangay:barangay,
sector: Array.isArray(sector) ? sector : [],
income_from:income_from,
income_to:income_to,
fourps:fourps,
philhealth:philhealth,
sort:sort,
page:page
},

success:function(data){

$('#beneficiaryTable').html(data);

/* ROW CLICK → VIEW PAGE */
$('.beneficiary-row').click(function(){

window.location =
'view_beneficiary_info.php?id=' + $(this).data('id');

});

}

});

}


/* INITIAL LOAD */
loadSavedFilters();

loadBeneficiaries(
$('#search').val(),
$('#barangay').val(),
selectedSectors,
$('#income_from').val(),
$('#income_to').val(),
$('#fourps').val(),
$('#philhealth').val(),
$('#sortBy').val(),
1
);


/* FILTER EVENTS */

$('#search,#barangay,#income_from,#income_to,#fourps,#philhealth,#sortBy')
.on('input change',function(){

saveFilters();

loadBeneficiaries(

$('#search').val(),
$('#barangay').val(),
selectedSectors,
$('#income_from').val(),
$('#income_to').val(),
$('#fourps').val(),
$('#philhealth').val(),
$('#sortBy').val(),
1

);

});


/* PAGINATION */

$(document).on('click','.pagination a',function(e){

e.preventDefault();

loadBeneficiaries(

$('#search').val(),
$('#barangay').val(),
selectedSectors,
$('#income_from').val(),
$('#income_to').val(),
$('#fourps').val(),
$('#philhealth').val(),
$('#sortBy').val(),
$(this).data('page')

);

});


/* PRINT FILTERED LIST */

$('#btnPrint').on('click',function(){

const params = new URLSearchParams({

search: $('#search').val(),
barangay: $('#barangay').val(),
sector: selectedSectors,
income_from: $('#income_from').val(),
income_to: $('#income_to').val(),
fourps: $('#fourps').val(),
philhealth: $('#philhealth').val(),
sort: $('#sortBy').val()

});

/* OPEN PRINT PAGE */

window.open('print_beneficiaries.php?' + params.toString(),'_blank');

});

/* AUTO HIDE ALERTS AFTER 2 SECONDS */

setTimeout(function(){

$('.alert').fadeOut('slow');

},2000);

</script>

</body>
</html>