async function submitFaculty() {
    const deptElement = document.getElementById("facDept");

    const payload = {
        employee_id: document.getElementById("facId")?.value.trim(),
        first_name: document.getElementById("facfirstName")?.value.trim(),
        middle_name: document.getElementById("facmiddleName")?.value.trim(),
        last_name: document.getElementById("faclastName")?.value.trim(),
        email: document.getElementById("facEmail")?.value.trim(),
        department_id: deptElement?.value,
    };

    if (!payload.employee_id || !payload.first_name || !payload.last_name || !payload.department_id) {
        alert("Please provide the Faculty ID, full name, and department.");
        return;
    }

    const response = await API.request("/admin/add-faculty", "POST", payload);

    if (response) {
        alert("Faculty added successfully.");
        location.reload();
    }
}

function searchTable() {
    const input = document.getElementById("userSearch")?.value.toUpperCase() || "";
    const rows = document.querySelectorAll("#userTable tbody tr.faculty-main-row");

    rows.forEach((row) => {
        const matchesSearch = row.innerText.toUpperCase().includes(input);
        row.style.display = matchesSearch ? "" : "none";

        const detailRow = row.nextElementSibling;
        if (detailRow?.classList.contains("faculty-manage-row")) {
            detailRow.style.display = "none";
        }
    });
}

function toggleFacultyManage(rowId) {
    const row = document.getElementById(rowId);
    if (!row) return;

    const isHidden = row.style.display === "none";
    document
        .querySelectorAll(".faculty-manage-row")
        .forEach((item) => (item.style.display = "none"));

    row.style.display = isHidden ? "table-row" : "none";
}

function saveFacultyAssignment(facultyId) {
    const fields = {
        department: document.getElementById(`fac-manage-dept-${facultyId}`),
        section: document.getElementById(`fac-manage-section-${facultyId}`),
        semester: document.getElementById(`fac-manage-semester-${facultyId}`),
        course: document.getElementById(`fac-manage-course-${facultyId}`),
        instructor: document.getElementById(`fac-manage-instructor-${facultyId}`),
    };

    const missingField = Object.values(fields).some((field) => !field?.value);

    if (missingField) {
        alert("Please complete the department, section, semester, course, and instructor assignment.");
        return;
    }

    alert("Faculty assignment saved for preview.");
    toggleFacultyManage(`faculty-manage-${facultyId}`);
}
