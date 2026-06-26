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

async function removeFaculty(id, button) {
    if (!confirm("Remove this faculty member?")) {
        return;
    }

    const response = await API.request(`/admin/delete-faculty/${id}`, "DELETE");

    if (response) {
        button.closest("tr")?.remove();
    }
}
