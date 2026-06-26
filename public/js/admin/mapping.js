/**
 * UI: Toggles the visibility of the Mapping card
 */
function toggleQuickAdd() {
    const container = document.getElementById("quickAddContainer");
    if (container.style.display === "none" || container.style.display === "") {
        container.style.display = "block";
    } else {
        container.style.display = "none";
    }
}

/**
 * UI: Show/Hide/Select Logic for Searchable Dropdowns
 */
function showDropdown(type) {
    // Hide all other dropdowns first for a cleaner feel
    document.querySelectorAll(".floating-list").forEach((list) => {
        list.style.display = "none";
    });
    const list = document.getElementById(type + "DropdownList");
    if (list) list.style.display = "block";
}

function selectItem(type, label, id) {
    document.getElementById(type + "Search").value = label;
    document.getElementById(type === "sub" ? "mapSub" : "mapFac").value = id;
    document.getElementById(type + "DropdownList").style.display = "none";
}

/**
 * Logic: Filters the floating list based on Search text
 * Simplified: Removed Type/Dept sort dependencies for a cleaner UX
 */
function runCombinedFilter(type) {
    showDropdown(type);

    const input = document.getElementById(type + "Search");
    const filter = input.value.toLowerCase();
    const items = document.querySelectorAll(
        `#${type}DropdownList .dropdown-item`,
    );

    items.forEach((item) => {
        const text = item.getAttribute("data-info").toLowerCase();
        // Since we are moving to a minimalist search, we focus primarily on text matching
        const matchesSearch = text.includes(filter);
        item.style.display = matchesSearch ? "block" : "none";
    });
}

/**
 * Action: Create Mapping
 */
async function addMapping() {
    const payload = {
        department_id: document.getElementById("mapDept").value,
        subject_id: document.getElementById("mapSub").value,
        faculty_id: document.getElementById("mapFac").value,
        section_id: document.getElementById("mapSection").value,
        semester: document.getElementById("mapSemester").value,
    };

    // Validation update
    if (
        !payload.subject_id ||
        !payload.faculty_id ||
        !payload.section_id ||
        !payload.semester ||
        !payload.department_id
    ) {
        return alert(
            "Please fill in all fields including the primary department.",
        );
    }

    try {
        const response = await API.request(
            "/admin/mapping/store",
            "POST",
            payload,
        );
        if (response) {
            alert("Mapping created successfully.");
            location.reload();
        }
    } catch (error) {
        console.error("Mapping Error:", error);
        alert("Failed to link this course and faculty assignment.");
    }
}

/**
 * Close floating dropdowns when clicking outside
 */
window.addEventListener("click", function (e) {
    if (!e.target.closest(".dropdown-wrapper")) {
        document.querySelectorAll(".floating-list").forEach((list) => {
            list.style.display = "none";
        });
    }
});
