/**
 * admin/students.js - Student Masterlist Logic
 */

function toggleQuickAdd() {
    const container = document.getElementById("quickAddContainer");
    if (!container) return;
    const isHidden = window.getComputedStyle(container).display === "none";
    container.style.display = isHidden ? "block" : "none";

    if (isHidden) {
        setTimeout(() => document.getElementById("stuId")?.focus(), 10);
    }
}

function searchTable() {
    const input = document.getElementById("userSearch")?.value.toUpperCase();
    const rows = document.querySelectorAll(
        "#userTable tbody tr.student-main-row",
    );

    requestAnimationFrame(() => {
        rows.forEach((row) => {
            const text = row.innerText.toUpperCase();
            row.style.display = text.includes(input) ? "" : "none";
            const detailRow = row.nextElementSibling;
            if (detailRow && detailRow.classList.contains("detail-row")) {
                detailRow.style.display = "none";
            }
        });
    });
}

async function submitStudent() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const payload = {
        student_id: document.getElementById('stuId').value,
        email: document.getElementById('stuEmail').value,
        first_name: document.getElementById('firstName').value,
        middle_name: document.getElementById('middleName').value,
        last_name: document.getElementById('lastName').value,
        department_id: document.getElementById('stuDept').value,
        section_id: document.getElementById('stuSection').value,
        _token: csrfToken
    };

    if (!payload.student_id || !payload.first_name || !payload.last_name || !payload.department_id) {
        return alert("Please fill in all required fields.");
    }

    try {
        const response = await API.request(
            "/admin/add-student",
            "POST",
            payload
        );
        if (response) {
            location.reload();
        }
    } catch (error) {
        if (error.status === 422) {
            alert("Error: Student ID or Email already exists.");
        } else {
            console.error(error);
            alert("An error occurred while saving the student.");
        }
    }
}

async function handleCSVUpload(input) {
    const file = input.files[0];
    if (!file?.name.endsWith(".csv")) return alert("Upload a valid CSV.");

    const formData = new FormData();
    formData.append("csv_file", file);

    if (await API.request("/admin/import-users", "POST", formData)) {
        alert("Users imported!");
        location.reload();
    }
    input.value = "";
}

function toggleDetails(rowId) {
    const row = document.getElementById(rowId);
    const isHidden = row.style.display === "none";
    document
        .querySelectorAll(".detail-row")
        .forEach((r) => (r.style.display = "none"));
    row.style.display = isHidden ? "table-row" : "none";
}

async function inlineAssign(userId) {
    const selectElement = document.getElementById(`sub-select-${userId}`);

    if (!selectElement) {
        console.error("Could not find dropdown for user:", userId);
        return;
    }

    const mappingId = selectElement.value;

    if (!mappingId) {
        alert("Please select a course mapping first.");
        return;
    }

    try {
        const response = await API.request("/admin/assign-subject", "POST", {
            user_id: userId,
            mapping_id: mappingId,
        });

        if (response) {
            alert("Course assigned successfully.");
            location.reload();
        }
    } catch (error) {
        console.error("Assignment Error:", error);
        alert(
            "Failed to assign. Check if the student is already enrolled in this class.",
        );
    }
}

async function unassignSubject(studentId, subjectId) {
    if (event) event.stopPropagation();
    if (!confirm("Remove this course from the student?")) return;

    const result = await API.request("/admin/unassign-subject", "POST", {
        user_id: studentId,
        subject_id: subjectId,
    });
    if (result) location.reload();
}

// Updates the student's primary class assignment and related mapped courses.
async function updateStudentSection(userId) {
    const selectElement = document.getElementById(`update-sec-${userId}`);
    const sectionId = selectElement.value;

    try {
        const response = await API.request("/admin/update-student-section", "POST", {
            user_id: userId,
            section_id: sectionId || null
        });

        if (response) {
            alert("Class assignment updated successfully.");
            location.reload();
        }
    } catch (error) {
        console.error("Class Update Error:", error);
        alert("Failed to update class assignment.");
    }
}

function sortCourseDropdowns() {
    const selects = document.querySelectorAll('select[id^="sub-select-"]');
    
    selects.forEach(select => {
        const userDept = select.getAttribute('data-user-dept');
        if (!userDept) return; // Skip if student has no department assigned
        
        const options = Array.from(select.options);
        const defaultOption = options.shift(); // Remove and hold the "-- Select Assigned Mapping --" option
        
        // Sort options so matching department attributes appear first
        options.sort((a, b) => {
            const aMatch = a.getAttribute('data-dept') == userDept ? -1 : 1;
            const bMatch = b.getAttribute('data-dept') == userDept ? -1 : 1;
            return aMatch - bMatch;
        });
        
        // Clear select and re-append sorted options
        select.innerHTML = '';
        select.appendChild(defaultOption);
        options.forEach(opt => select.appendChild(opt));
    });
}

document.addEventListener("DOMContentLoaded", () => {
    document
        .getElementById("userSearch")
        ?.addEventListener("keyup", searchTable);
        
    sortCourseDropdowns();
});
