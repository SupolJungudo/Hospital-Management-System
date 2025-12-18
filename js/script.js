// File: js/script.js
// Generate patient ID
function generatePatientID() {
    const prefix = 'METRO-';
    const random = Math.floor(1000 + Math.random() * 9000);
    const year = new Date().getFullYear().toString().slice(-2);
    return prefix + year + random;
}

// Auto-generate patient ID on registration page load
document.addEventListener('DOMContentLoaded', function() {
    const patientIdField = document.getElementById('patient-id');
    if(patientIdField) {
        patientIdField.value = generatePatientID();
        patientIdField.readOnly = true;
    }
    
    // Load pending prescriptions for pharmacy
    if(document.getElementById('pending-prescriptions')) {
        loadPharmacyStats();
    }
});

// Load pharmacy statistics
async function loadPharmacyStats() {
    try {
        const response = await fetch('api/get_pending_prescriptions.php');
        const data = await response.json();
        document.getElementById('pending-prescriptions').textContent = data.count;
    } catch(error) {
        console.error('Error loading stats:', error);
    }
}

// Search patient function
function searchPatient() {
    const searchTerm = document.getElementById('search-patient').value;
    if(searchTerm.length < 2) return;
    
    fetch(`api/search_patient.php?q=${searchTerm}`)
        .then(response => response.json())
        .then(data => {
            const resultsDiv = document.getElementById('search-results');
            resultsDiv.innerHTML = '';
            
            if(data.length > 0) {
                data.forEach(patient => {
                    const div = document.createElement('div');
                    div.className = 'patient-result';
                    div.innerHTML = `
                        <strong>${patient.patient_id}</strong> - ${patient.first_name} ${patient.last_name}
                        <button onclick="selectPatient('${patient.patient_id}')">Select</button>
                    `;
                    resultsDiv.appendChild(div);
                });
            } else {
                resultsDiv.innerHTML = '<p>No patients found</p>';
            }
        });
}

// Select patient for consultation
function selectPatient(patientId) {
    document.getElementById('patient-id-field').value = patientId;
    document.getElementById('search-results').innerHTML = '';
    
    // Load patient details
    fetch(`api/get_patient_details.php?id=${patientId}`)
        .then(response => response.json())
        .then(patient => {
            document.getElementById('patient-name').textContent = 
                `${patient.first_name} ${patient.last_name}`;
            document.getElementById('patient-dob').textContent = patient.dob;
            document.getElementById('patient-gender').textContent = patient.gender;
        });
}