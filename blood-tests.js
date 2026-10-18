// Sample data - in a real app, this would come from a backend API
let bloodTests = [
    {
        id: 1,
        donor_id: 101,
        test_date: '2023-06-15 10:30:00',
        blood_type: 'A',
        rh_factor: '+',
        hbsag: 'Negative',
        hcv: 'Negative',
        hiv: 'Negative',
        syphilis: 'Negative',
        hematocrit: 42.5,
        hemoglobin: 14.2,
        status: 'Eligible',
        notes: 'Donor meets all eligibility criteria'
    },
    {
        id: 2,
        donor_id: 102,
        test_date: '2023-06-16 11:45:00',
        blood_type: 'B',
        rh_factor: '-',
        hbsag: 'Negative',
        hcv: 'Negative',
        hiv: 'Negative',
        syphilis: 'Positive',
        hematocrit: 38.7,
        hemoglobin: 12.9,
        status: 'Rejected',
        notes: 'Syphilis positive - ineligible for donation'
    },
    {
        id: 3,
        donor_id: 103,
        test_date: '2023-06-17 09:15:00',
        blood_type: 'O',
        rh_factor: '+',
        hbsag: 'Negative',
        hcv: 'Negative',
        hiv: 'Negative',
        syphilis: 'Negative',
        hematocrit: 40.2,
        hemoglobin: 13.5,
        status: 'Pending',
        notes: 'Awaiting final review'
    }
];

// Initialize DataTable
$(document).ready(function() {
    loadTestsTable();
    
    // Search functionality
    $('#searchInput').on('keyup', function() {
        const searchText = $(this).val().toLowerCase();
        $('#testsTableBody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(searchText) > -1);
        });
    });
    
    // Save new test
    $('#saveTestBtn').click(function() {
        const newTest = {
            id: bloodTests.length > 0 ? Math.max(...bloodTests.map(t => t.id)) + 1 : 1,
            donor_id: parseInt($('#donor_id').val()),
            test_date: $('#test_date').val() ? new Date($('#test_date').val()).toISOString().slice(0, 19).replace('T', ' ') : new Date().toISOString().slice(0, 19).replace('T', ' '),
            blood_type: $('#blood_type').val(),
            rh_factor: $('#rh_factor').val(),
            hbsag: $('#hbsag').val(),
            hcv: $('#hcv').val(),
            hiv: $('#hiv').val(),
            syphilis: $('#syphilis').val(),
            hematocrit: $('#hematocrit').val() ? parseFloat($('#hematocrit').val()) : null,
            hemoglobin: $('#hemoglobin').val() ? parseFloat($('#hemoglobin').val()) : null,
            status: 'Pending',
            notes: $('#notes').val()
        };
        
        bloodTests.push(newTest);
        $('#addTestModal').modal('hide');
        $('#addTestForm')[0].reset();
        loadTestsTable();
    });
    
    // Update test
    $('#updateTestBtn').click(function() {
        const testId = parseInt($('#edit_id').val());
        const index = bloodTests.findIndex(t => t.id === testId);
        
        if (index !== -1) {
            bloodTests[index] = {
                ...bloodTests[index],
                donor_id: parseInt($('#edit_donor_id').val()),
                test_date: $('#edit_test_date').val() ? new Date($('#edit_test_date').val()).toISOString().slice(0, 19).replace('T', ' ') : new Date().toISOString().slice(0, 19).replace('T', ' '),
                blood_type: $('#edit_blood_type').val(),
                rh_factor: $('#edit_rh_factor').val(),
                hbsag: $('#edit_hbsag').val(),
                hcv: $('#edit_hcv').val(),
                hiv: $('#edit_hiv').val(),
                syphilis: $('#edit_syphilis').val(),
                hematocrit: $('#edit_hematocrit').val() ? parseFloat($('#edit_hematocrit').val()) : null,
                hemoglobin: $('#edit_hemoglobin').val() ? parseFloat($('#edit_hemoglobin').val()) : null,
                status: $('#edit_status').val(),
                notes: $('#edit_notes').val()
            };
            
            $('#editTestModal').modal('hide');
            loadTestsTable();
        }
    });
    
    // Print all tests
    $('#printAllBtn').click(function() {
        printAllTests();
    });
    
    // Print single test
    $('#printSingleBtn').click(function() {
        const testId = parseInt($('#printTestId').val());
        printTest(testId);
    });
});

// Load tests into the table
function loadTestsTable() {
    const tableBody = $('#testsTableBody');
    tableBody.empty();
    
    bloodTests.forEach(test => {
        const row = `
            <tr>
                <td>${test.id}</td>
                <td>${test.donor_id}</td>
                <td>${formatDateTime(test.test_date)}</td>
                <td>${test.blood_type}${test.rh_factor}</td>
                <td>${test.rh_factor}</td>
                <td>
                    <span class="badge ${getStatusBadgeClass(test.status)}">${test.status}</span>
                </td>
                <td>
                    <button class="btn btn-sm btn-primary view-btn" data-id="${test.id}">View</button>
                    <button class="btn btn-sm btn-warning edit-btn" data-id="${test.id}">Edit</button>
                    <button class="btn btn-sm btn-danger delete-btn" data-id="${test.id}">Delete</button>
                    <button class="btn btn-sm btn-info print-btn" data-id="${test.id}" data-bs-toggle="modal" data-bs-target="#printModal">Print</button>
                </td>
            </tr>
        `;
        tableBody.append(row);
    });
    
    // Add event listeners to buttons
    $('.view-btn').click(function() {
        const testId = parseInt($(this).data('id'));
        viewTest(testId);
    });
    
    $('.edit-btn').click(function() {
        const testId = parseInt($(this).data('id'));
        editTest(testId);
    });
    
    $('.delete-btn').click(function() {
        const testId = parseInt($(this).data('id'));
        deleteTest(testId);
    });
    
    $('.print-btn').click(function() {
        const testId = parseInt($(this).data('id'));
        $('#printTestId').val(testId);
    });
}

// View test details
function viewTest(testId) {
    const test = bloodTests.find(t => t.id === testId);
    if (test) {
        alert(`
            Blood Test Details (ID: ${test.id})
            ----------------------------
            Donor ID: ${test.donor_id}
            Test Date: ${formatDateTime(test.test_date)}
            Blood Type: ${test.blood_type}${test.rh_factor}
            HBsAg: ${test.hbsag}
            HCV: ${test.hcv}
            HIV: ${test.hiv}
            Syphilis: ${test.syphilis}
            Hematocrit: ${test.hematocrit || 'N/A'}%
            Hemoglobin: ${test.hemoglobin || 'N/A'} g/dL
            Status: ${test.status}
            Notes: ${test.notes || 'None'}
        `);
    }
}

// Edit test
function editTest(testId) {
    const test = bloodTests.find(t => t.id === testId);
    if (test) {
        $('#edit_id').val(test.id);
        $('#edit_donor_id').val(test.donor_id);
        $('#edit_test_date').val(formatDateTimeForInput(test.test_date));
        $('#edit_blood_type').val(test.blood_type);
        $('#edit_rh_factor').val(test.rh_factor);
        $('#edit_hbsag').val(test.hbsag);
        $('#edit_hcv').val(test.hcv);
        $('#edit_hiv').val(test.hiv);
        $('#edit_syphilis').val(test.syphilis);
        $('#edit_hematocrit').val(test.hematocrit || '');
        $('#edit_hemoglobin').val(test.hemoglobin || '');
        $('#edit_status').val(test.status);
        $('#edit_notes').val(test.notes || '');
        
        $('#editTestModal').modal('show');
    }
}

// Delete test
function deleteTest(testId) {
    if (confirm('Are you sure you want to delete this blood test?')) {
        bloodTests = bloodTests.filter(t => t.id !== testId);
        loadTestsTable();
    }
}

// Print single test
function printTest(testId) {
    const test = bloodTests.find(t => t.id === testId);
    if (test) {
        const printContent = generatePrintContent([test]);
        $('#printSection').html(printContent);
        $('#printModal').modal('hide');
        window.print();
    }
}

// Print all tests
function printAllTests() {
    const printContent = generatePrintContent(bloodTests);
    $('#printSection').html(printContent);
    window.print();
}

// Generate print content
function generatePrintContent(tests) {
    let content = '';
    
    tests.forEach((test, index) => {
        const eligibility = determineEligibility(test);
        
        content += `
            <div class="p-4 ${index < tests.length - 1 ? 'page-break' : ''}">
                <h3 class="text-center mb-4">Blood Test Report</h3>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <p><strong>Test ID:</strong> ${test.id}</p>
                        <p><strong>Donor ID:</strong> ${test.donor_id}</p>
                        <p><strong>Test Date:</strong> ${formatDateTime(test.test_date)}</p>
                    </div>
                    <div class="col-md-6 text-end">
                        <p><strong>Blood Type:</strong> ${test.blood_type}${test.rh_factor}</p>
                        <p><strong>Status:</strong> <span class="badge ${getStatusBadgeClass(test.status)}">${test.status}</span></p>
                    </div>
                </div>
                
                <h5 class="mb-3">Test Results</h5>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Test</th>
                            <th>Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>HBsAg (Hepatitis B)</td>
                            <td>${test.hbsag}</td>
                        </tr>
                        <tr>
                            <td>HCV (Hepatitis C)</td>
                            <td>${test.hcv}</td>
                        </tr>
                        <tr>
                            <td>HIV</td>
                            <td>${test.hiv}</td>
                        </tr>
                        <tr>
                            <td>Syphilis</td>
                            <td>${test.syphilis}</td>
                        </tr>
                        <tr>
                            <td>Hematocrit</td>
                            <td>${test.hematocrit || 'N/A'}%</td>
                        </tr>
                        <tr>
                            <td>Hemoglobin</td>
                            <td>${test.hemoglobin || 'N/A'} g/dL</td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="mt-3">
                    <h5>Eligibility Assessment</h5>
                    <p>${eligibility.message}</p>
                    ${eligibility.reasons ? `<ul>${eligibility.reasons.map(r => `<li>${r}</li>`).join('')}</ul>` : ''}
                </div>
                
                <div class="mt-3">
                    <h5>Notes</h5>
                    <p>${test.notes || 'No notes available.'}</p>
                </div>
                
                <div class="mt-4 text-center">
                    <p class="text-muted">Generated on ${formatDateTime(new Date().toISOString())}</p>
                </div>
            </div>
        `;
    });
    
    return content;
}

// Determine eligibility based on test results
function determineEligibility(test) {
    if (test.status === 'Eligible') {
        return {
            message: 'The donor is eligible for blood donation.',
            reasons: []
        };
    } else if (test.status === 'Rejected') {
        const reasons = [];
        
        if (test.hbsag === 'Positive') reasons.push('Hepatitis B positive');
        if (test.hcv === 'Positive') reasons.push('Hepatitis C positive');
        if (test.hiv === 'Positive') reasons.push('HIV positive');
        if (test.syphilis === 'Positive') reasons.push('Syphilis positive');
        
        // Check hematocrit and hemoglobin levels
        if (test.hematocrit !== null && test.hematocrit < 38) {
            reasons.push(`Low hematocrit level (${test.hematocrit}%, minimum 38% required)`);
        }
        
        if (test.hemoglobin !== null && test.hemoglobin < 12.5) {
            reasons.push(`Low hemoglobin level (${test.hemoglobin} g/dL, minimum 12.5 g/dL required)`);
        }
        
        return {
            message: 'The donor is NOT eligible for blood donation due to the following reasons:',
            reasons: reasons.length > 0 ? reasons : ['Unknown reason (manual rejection)']
        };
    } else {
        return {
            message: 'The test results are pending review.',
            reasons: []
        };
    }
}

// Helper functions
function formatDateTime(dateTimeString) {
    const options = { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric', 
        hour: '2-digit', 
        minute: '2-digit' 
    };
    return new Date(dateTimeString).toLocaleDateString('en-US', options);
}

function formatDateTimeForInput(dateTimeString) {
    const date = new Date(dateTimeString);
    return date.toISOString().slice(0, 16);
}

function getStatusBadgeClass(status) {
    switch(status) {
        case 'Eligible': return 'bg-success';
        case 'Rejected': return 'bg-danger';
        default: return 'bg-warning text-dark';
    }
}