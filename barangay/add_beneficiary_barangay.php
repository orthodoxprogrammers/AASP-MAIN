<?php
session_start();
include __DIR__ . '/../config/db.php';

/* SESSION BARANGAY */
$sessionBarangay = $_SESSION['barangay'] ?? '';

/* RBAC: Only MSWDO can access this page 
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'MSWDO') {
    echo "<tr><td colspan='8' class='text-center'>Access Denied</td></tr>";
    exit();
}
*/

/* FETCH BARANGAYS */
$barangays = [];
$bQuery = "SELECT DISTINCT barangay FROM users WHERE role_id = 2 AND barangay IS NOT NULL AND barangay != '' ORDER BY barangay ASC";
$bResult = mysqli_query($conn, $bQuery);

while($row = mysqli_fetch_assoc($bResult)){
    $barangays[] = $row['barangay'];
}

/* Ensure session barangay is in the list */
if($sessionBarangay && !in_array($sessionBarangay, $barangays)){
    $barangays[] = $sessionBarangay;
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Add Beneficiary Page</title>

        <link rel="stylesheet" href="../css/bootstrap.min.css">
        <link rel="stylesheet" href="../css/bootstrap-icons-1.11.0/bootstrap-icons.css">
        <link rel="stylesheet" href="../css/style.css">

        <style>
            .step{display:none;}
            .step.active{display:block;}
            .step-buttons{margin-top:20px;}
            .step-indicator{
                font-weight:bold;
                margin-bottom:10px;
            }

            .form-check{
                display:flex;
                align-items:center;
                gap:6px;
                margin-bottom:6px;
            }

            .sectionHeads{
                color: #061E29;
            }
            .clientCheck{
                color: #0000007d;
            }

            input[type="checkbox"].form-check-input{
                width:16px !important;
                height:16px !important;
                padding:0 !important;
                margin-top:0 !important;
            }

            .inputLabels{
                font-size: 0.7rem;
            }

            .inputLabels i{
                font-size: 0.6rem;
            }

            input::placeholder{
                font-size: 0.7rem;
                font-style: italic;
            }

            .form-check-label{
                margin-bottom:0;
                cursor:pointer;
            }

            .card{
                max-width:1300px;
                margin:auto;
            }

            .vertical-divider{
                border-left:2px solid #dee2e6;
                height:100%;
            }
        </style>

</head>
<body>
    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h4 class="mb-0">Add Beneficiary Information</h4>
            <a href="barangay_households.php" class="btn btn-secondary"> <i class="bi bi-arrow-left"></i> Back</a>
        </div>

        <div class="card shadow p-4">
            <div class="step-indicator">
                Step <span id="stepNumber">1</span> of 3
            </div>
            
            <form method="POST" action="save_beneficiary_barangay.php">

                <!-- STEP 1 CLIENT -->
                <div class="step active">
                    <div class="row">
                        
                        <!-- LEFT SIDE : CLIENT -->
                        <div class="col-lg-6">
                            <h6 class="sectionHeads">IMPORMASYON NG KINATAWAN / CLIENT</h6>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="samePerson">
                                <label class="clientCheck form-check-label">Client is also the Beneficiary</label>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="inputLabels">Apelyido</label>
                                    <input type="text" name="c_last" id="c_last" class="form-control" placeholder="last name">
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="inputLabels">Unang Pangalan</label>
                                    <input type="text" name="c_first" id="c_first" class="form-control" placeholder="first name">
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="inputLabels">Gitnang Pangalan</label>
                                    <input type="text" name="c_middle" id="c_middle" class="form-control" placeholder="middle name">
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="inputLabels">Ext</label>
                                    <input type="text" name="c_ext" id="c_ext" class="form-control" placeholder="jr/sr/I/II">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="inputLabels">Numero ng Bahay/Kalye</label>
                                    <input type="text" name="c_street" id="c_street" class="form-control" placeholder="street address">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="inputLabels">Barangay</label>
                                    <input type="text" name="c_barangay" id="c_barangay"
                                        class="form-control"
                                        value="<?= htmlspecialchars($barangay) ?>"
                                        readonly>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="inputLabels">Lungsod/Bayan<i>(City/Municipality)</i></label>
                                    <input type="text" name="c_city" id="c_city" class="form-control" value="Lidlidda">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="inputLabels">Lalawigan/Distrito<i>(Province/District)</i></label>
                                    <input type="text" name="c_province" id="c_province" class="form-control" value="Ilocos Sur">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="inputLabels">Rehiyon<i>(Region)</i></label>
                                    <input type="text" name="c_region" id="c_region" class="form-control" value="Region I">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="inputLabels">Mobile No</label>
                                    <input type="text" name="c_mobile" id="c_mobile" class="form-control" maxlength="11" placeholder="0900 000 0000">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="inputLabels">Petsa ng Kapanganakan<i>(Birthdate)</i></label>
                                    <input type="date" name="c_birthdate" id="c_birthdate" class="form-control">
                                </div>

                                <div class="col-md-2 mb-3">
                                    <label class="inputLabels">Edad</label>
                                    <input type="number" name="c_age" id="c_age" class="form-control" readonly placeholder="age">
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="inputLabels">Kasarian<i>(Sex)</i></label>
                                    <select name="c_sex" id="c_sex" class="form-control">
                                        <option>Male</option>
                                        <option>Female</option>
                                    </select>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="inputLabels">Katayuang Sibil</label>
                                    <input type="text" name="c_civil" id="c_civil" class="form-control" placeholder="civil status">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="inputLabels">Hanapbuhay<i>(Occupation)</i></label>
                                    <input type="text" name="c_occupation" id="c_occupation" class="form-control">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="inputLabels">Buwanang Kita<i>(Monthly Income)</i></label>
                                    <input type="number" name="c_income" id="c_income" class="form-control">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="inputLabels">Relationship to Beneficiary</label>
                                    <input type="text" name="c_relationship" class="form-control">
                                </div>

                            </div>
                        </div>

                        <!-- RIGHT SIDE : BENEFICIARY -->
                        <div class="col-lg-6 vertical-divider ps-4">

                            <h6 class="sectionHeads">IMPORMASYON NG BENEPISYARYO</h6>
                            <div style="height:40px;"></div>
                            
                            <div class="row">

                                <div class="col-md-3 mb-3">
                                    <label class="inputLabels">Last Name</label>
                                    <input type="text" name="b_last" id="b_last" class="form-control" required>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="inputLabels">First Name</label>
                                    <input type="text" name="b_first" id="b_first" class="form-control" required>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="inputLabels">Middle Name</label>
                                    <input type="text" name="b_middle" id="b_middle" class="form-control">
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="inputLabels">Ext</label>
                                    <input type="text" name="b_ext" id="b_ext" class="form-control" placeholder="Ex: jr,sr,I,II">
                                </div>

                                <!-- POSSIBLE DUPLICATE RESULTS -->
                                <div id="duplicateBox" class="alert alert-warning mt-2" style="display:none;">
                                    <strong>⚠ Possible Existing Beneficiaries</strong>
                                    <div id="duplicateResults" class="mt-2"></div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="inputLabels">Street Address</label>
                                    <input type="text" name="b_street" id="b_street" class="form-control">
                                </div>

                                <div class="col-md-4 mb-3">
    <label class="inputLabels">Barangay</label>
    <input type="text" name="b_barangay" id="b_barangay"
        class="form-control"
        value="<?= htmlspecialchars($sessionBarangay) ?>"
        readonly>
</div>

                                <div class="col-md-4 mb-3">
                                    <label class="inputLabels">City/Municipality</i></label>
                                    <input type="text" name="b_city" id="b_city" class="form-control" value="Lidlidda">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="inputLabels">Province/District</i></label>
                                    <input type="text" name="b_province" id="b_province" class="form-control" value="Ilocos Sur">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="inputLabels"><i>Region</i></label>
                                    <input type="text" name="b_region" id="b_region" class="form-control" value="Region I">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="inputLabels">Mobile No</label>
                                    <input type="text" name="b_mobile" id="b_mobile" class="form-control" placeholder="0900 000 0000">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="inputLabels">Birthdate</i></label>
                                    <input type="date" name="b_birthdate" id="b_birthdate" class="form-control">
                                </div>

                                <div class="col-md-2 mb-3">
                                    <label class="inputLabels">Age</label>
                                    <input type="number" name="b_age" id="b_age" class="form-control" readonly>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="inputLabels">Sex</i></label>
                                    <select name="b_sex" id="b_sex" class="form-control">
                                        <option>Male</option>
                                        <option>Female</option>
                                    </select>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="inputLabels">Civil Status</label>
                                    <input type="text" name="b_civil" id="b_civil" class="form-control">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="inputLabels">Occupation</label>
                                    <input type="text" name="b_occupation" id="b_occupation" class="form-control">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="inputLabels">Buwanang Kita<i>(Monthly Income)</i></label>
                                    <input type="number" name="b_income" id="b_income" class="form-control">
                                </div>

                            </div>

                        </div>
                    </div>
                </div>
                
                <!-- STEP 2 FAMILY + INTAKE -->
                <div class="step">

                    <!-- TOP SECTION -->
                    <div class="row">

                        <!-- LEFT : PREVIOUS ASSISTANCE -->
                        <div class="col-lg-6">

                            <h5>Previous Assistance from DSWD</h5>

                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="dswd_assistance" value="no" id="assistNo" checked>
                                <label class="form-check-label">No</label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="dswd_assistance" value="yes" id="assistYes">
                                <label class="form-check-label">Yes</label>
                            </div>

                            <div id="assistanceSection" style="display:none;" class="mt-3">

                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Assistance Received from DSWD</th>
                                            <th>Date of Assistance Received</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody id="assistance_table"></tbody>
                                </table>

                                <button type="button" class="btn btn-info btn-sm" onclick="addAssistance()">
                                    <i class="bi bi-plus-circle"></i> Add Row
                                </button>

                            </div>

                        </div>

                        <!-- RIGHT : GENERAL INTAKE -->
                        <div class="col-lg-6 vertical-divider ps-4">

                            <h5>General Intake</h5>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="aics">
                                <label class="form-check-label">AICS</label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="akap">
                                <label class="form-check-label">AKAP</label>
                            </div>

                            <input type="text" name="others_program" class="form-control mt-2" placeholder="Other Program">

                            <select name="visit_type" class="form-control mt-2">
                                <option>New</option>
                                <option>Returning</option>
                            </select>

                            <select name="client_source" class="form-control mt-2">
                                <option>Walk-in</option>
                                <option>Referral</option>
                            </select>

                            <input type="date" name="intake_date" class="form-control mt-2">

                            <textarea name="purpose" class="form-control mt-2" placeholder="Purpose of Assistance"></textarea>

                            <input type="number" name="amount" class="form-control mt-2" placeholder="Amount Needed">

                        </div>

                    </div>

                    <!-- FAMILY COMPOSITION FULL WIDTH -->
                    <hr class="my-4">

                    <h5>Family Composition</h5>

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th style="width:30%">Name</th>
                                <th>Relationship</th>
                                <th>Age</th>
                                <th>Occupation</th>
                                <th>Income</th>
                                <th style="width:60px"></th>
                            </tr>
                        </thead>
                        <tbody id="family_table"></tbody>
                    </table>

                    <button type="button" class="btn btn-info" onclick="addFamily()">
                        <i class="bi bi-plus-circle"></i> Add Member
                    </button>

                </div>
                
                <!-- STEP 3 SECTOR -->
                <div class="step">
                    <h5>Client Sector</h5>

                    <div class="row">

                        <!-- TARGET SECTOR -->
                        <div class="col-md-4">

                            <label class="mb-2"><strong>Target Sector</strong></label>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="sector[]" value="FHONA">
                                <label class="form-check-label">FHONA</label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="sector[]" value="WEDC">
                                <label class="form-check-label">WEDC</label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="sector[]" value="PWD">
                                <label class="form-check-label">PWD</label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="sector[]" value="CNSP">
                                <label class="form-check-label">CNSP</label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="sector[]" value="SC">
                                <label class="form-check-label">Senior Citizen</label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="sector[]" value="YNSP">
                                <label class="form-check-label">YNSP</label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="sector[]" value="PLHIV">
                                <label class="form-check-label">PLHIV</label>
                            </div>

                        </div>


                        <!-- SUBCATEGORY -->
                        <div class="col-md-4">

                            <label class="mb-2"><strong>Sub Category</strong></label>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="subcategory[]" value="Solo Parent">
                                <label class="form-check-label">Solo Parent</label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="subcategory[]" value="Indigenous People">
                                <label class="form-check-label">Indigenous People</label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="subcategory[]" value="Street Dwellers">
                                <label class="form-check-label">Street Dwellers</label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="subcategory[]" value="KIA/WIA">
                                <label class="form-check-label">KIA/WIA</label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="subcategory[]" value="4Ps Beneficiary">
                                <label class="form-check-label">4Ps Beneficiary</label>
                            </div>

                        </div>


                        <!-- SPECIAL TAGS -->
                        <div class="col-md-4">

                            <label class="mb-2"><strong>Special Tags</strong></label>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="tags[]" value="Stateless Person">
                                <label class="form-check-label">Stateless Person</label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="tags[]" value="Asylum Seekers">
                                <label class="form-check-label">Asylum Seekers</label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="tags[]" value="Refugees">
                                <label class="form-check-label">Refugees</label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="tags[]" value="Recovering PWUD">
                                <label class="form-check-label">Recovering Person Who Used Drugs</label>
                            </div>

                        </div>

                    </div>
                </div>
                
                <div class="step-buttons">
                    <button type="button" class="btn btn-secondary" onclick="prevStep()">Previous</button>
                    <button type="button" class="btn btn-primary" id="nextBtn" onclick="nextStep()">Next</button>
                    <button type="submit" class="btn btn-success" id="submitBtn" style="display:none;">Save Beneficiary</button>
                </div>

            </form>
        </div>
    </div>

    <script>

    /* STEP NAVIGATION */

    let currentStep=0;
    const steps=document.querySelectorAll(".step");

    function showStep(index){

    steps.forEach((step,i)=>{
    step.classList.remove("active");
    if(i===index) step.classList.add("active");
    });

    document.getElementById("stepNumber").innerText=index+1;

    let isLast=index===steps.length-1;

    document.getElementById("submitBtn").style.display =
    isLast?"inline-block":"none";

    document.getElementById("nextBtn").style.display =
    isLast?"none":"inline-block";

    }

    function nextStep(){

    let current = steps[currentStep];
    let requiredInputs = current.querySelectorAll("[required]");

    for(let input of requiredInputs){

    if(!input.value){

    input.focus();
    alert("Please complete required fields before continuing.");
    return;

    }

    }

    if(currentStep < steps.length - 1){
    currentStep++;
    showStep(currentStep);
    }

    }

    function prevStep(){
    if(currentStep>0){
    currentStep--;
    showStep(currentStep);
    }
    }


    /* AGE COMPUTATION */

    function computeAge(birthdate){

    if(!birthdate) return "";

    let today=new Date();
    let birth=new Date(birthdate);

    let age=today.getFullYear()-birth.getFullYear();
    let m=today.getMonth()-birth.getMonth();

    if(m<0 || (m===0 && today.getDate()<birth.getDate())){
    age--;
    }

    return age;

    }

    function updateAge(birthField,ageField){

    let birth=document.getElementById(birthField).value;

    document.getElementById(ageField).value =
    computeAge(birth);

    }


    /* CLIENT AGE */

    document.getElementById("c_birthdate")
    .addEventListener("change",function(){

    updateAge("c_birthdate","c_age");

    });


    /* BENEFICIARY AGE */

    document.getElementById("b_birthdate")
    .addEventListener("change",function(){

    updateAge("b_birthdate","b_age");

    });


    /* AUTO COPY CLIENT → BENEFICIARY */

    document.getElementById("samePerson")
    .addEventListener("change",function(){

    if(this.checked){

    document.getElementById("b_last").value=document.getElementById("c_last").value;
    document.getElementById("b_first").value=document.getElementById("c_first").value;
    document.getElementById("b_middle").value=document.getElementById("c_middle").value;
    document.getElementById("b_ext").value=document.getElementById("c_ext").value;

    document.getElementById("b_street").value=document.getElementById("c_street").value;
    document.getElementById("b_barangay").value=document.getElementById("c_barangay").value;
    document.getElementById("b_city").value=document.getElementById("c_city").value;
    document.getElementById("b_province").value=document.getElementById("c_province").value;
    document.getElementById("b_region").value=document.getElementById("c_region").value;

    document.getElementById("b_mobile").value=document.getElementById("c_mobile").value;

    document.getElementById("b_birthdate").value=document.getElementById("c_birthdate").value;

    document.getElementById("b_age").value=document.getElementById("c_age").value;

    document.getElementById("b_sex").value=document.getElementById("c_sex").value;

    document.getElementById("b_civil").value=document.getElementById("c_civil").value;

    document.getElementById("b_occupation").value=document.getElementById("c_occupation").value;

    document.getElementById("b_income").value=document.getElementById("c_income").value;

    updateAge("b_birthdate","b_age");

    }
    else{

    document.querySelectorAll(
    "#b_last,#b_first,#b_middle,#b_ext,#b_street,#b_barangay,#b_city,#b_province,#b_region,#b_mobile,#b_birthdate,#b_age,#b_civil,#b_occupation,#b_income"
    )
    .forEach(input=>input.value="");

    }

    });


    /* FAMILY ROW */

    function addFamily(){

    let row=`<tr>

    <td><input name="fam_name[]" class="form-control"></td>

    <td><input name="fam_relation[]" class="form-control"></td>

    <td><input name="fam_age[]" class="form-control"></td>

    <td><input name="fam_occupation[]" class="form-control"></td>

    <td><input name="fam_income[]" class="form-control"></td>

    <td>

    <button type="button" class="btn btn-danger btn-sm"
    onclick="this.closest('tr').remove()">

    <i class="bi bi-trash"></i>

    </button>

    </td>

    </tr>`;

    document.getElementById("family_table")
    .insertAdjacentHTML("beforeend",row);

    }


    /* ASSISTANCE ROW */

    function addAssistance(){

    let row=`<tr>

    <td>
    <input type="text" name="assist_type[]" class="form-control">
    </td>

    <td>
    <input type="date" name="assist_date[]" class="form-control">
    </td>

    <td>

    <button type="button" class="btn btn-danger btn-sm"
    onclick="this.closest('tr').remove()">

    <i class="bi bi-trash"></i>

    </button>

    </td>

    </tr>`;

    document.getElementById("assistance_table")
    .insertAdjacentHTML("beforeend",row);

    }


    /* DSWD ASSISTANCE TOGGLE */

    document.getElementById("assistYes")
    .addEventListener("change",function(){

    document.getElementById("assistanceSection")
    .style.display="block";

    });

    document.getElementById("assistNo")
    .addEventListener("change",function(){

    document.getElementById("assistanceSection")
    .style.display="none";

    });

    /* =========================================
    LGU STYLE DUPLICATE BENEFICIARY CHECK
    ========================================= */

    function checkDuplicateBeneficiary(){

    let last  = document.getElementById("b_last").value.trim();
    let first = document.getElementById("b_first").value.trim();

    if(last === "" || first === ""){
    document.getElementById("duplicateBox").style.display="none";
    return;
    }

    fetch("check_beneficiary.php",{
    method:"POST",
    headers:{
    "Content-Type":"application/x-www-form-urlencoded"
    },
    body:
    "last="+encodeURIComponent(last)+
    "&first="+encodeURIComponent(first)
    })
    .then(response=>response.json())
    .then(data=>{

    let box = document.getElementById("duplicateBox");
    let results = document.getElementById("duplicateResults");

    results.innerHTML="";

    if(data.length === 0){

    box.style.display="none";
    return;

    }

    box.style.display="block";

    data.forEach(person=>{

    let row = `
    <div class="border rounded p-2 mb-2 bg-light">

    <strong>${person.full_name}</strong><br>

    Barangay: ${person.barangay}<br>

    Age: ${person.age}<br>

    Birthdate: ${person.birthdate}

    </div>
    `;

    results.insertAdjacentHTML("beforeend",row);

    });

    });

    }

    /* TRIGGER CHECK */

    document.getElementById("b_last")
    .addEventListener("blur",checkDuplicateBeneficiary);

    document.getElementById("b_first")
    .addEventListener("blur",checkDuplicateBeneficiary);

    </script>

</body>
</html>