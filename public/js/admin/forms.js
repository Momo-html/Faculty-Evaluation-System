/**
 * FEU Faculty Evaluation - Form Builder Logic
 */

function showCreateForm() {
    document.getElementById('builderTitle').innerText = "Create New Evaluation Period";
    document.getElementById('currentFormId').value = "";
    document.getElementById('sy').value = "";
    document.getElementById('sem').value = "1st Semester";
    document.getElementById('openAt').value = "";
    document.getElementById('closeAt').value = "";
    document.getElementById('formCanvas').innerHTML = "";
    
    // Hide delete button for new entries
    const delBtn = document.getElementById('deleteBtn');
    if (delBtn) delBtn.style.display = 'none';

    addQuestion();
    document.getElementById('tableSection').style.display = 'none';
    document.getElementById('builderSection').style.display = 'block';
}

async function loadFormForEdit(id) {
    // API is your global helper, ensure it handles tokens
    const form = await API.request(`/admin/get-form-data/${id}`, "GET");
    if (!form) return;

    document.getElementById('builderTitle').innerText = `Editing: SY ${form.school_year}`;
    document.getElementById('currentFormId').value = form.id;
    document.getElementById('sy').value = form.school_year;
    document.getElementById('sem').value = form.semester;
    
    // Show delete button when editing
    const delBtn = document.getElementById('deleteBtn');
    if (delBtn) delBtn.style.display = 'inline-block';

    if (form.open_at) document.getElementById('openAt').value = form.open_at.replace(' ', 'T').slice(0, 16);
    if (form.close_at) document.getElementById('closeAt').value = form.close_at.replace(' ', 'T').slice(0, 16);

    const canvas = document.getElementById('formCanvas');
    canvas.innerHTML = '';
    
    if (form.questions && form.questions.length > 0) {
        form.questions.forEach(q => addQuestion(q.question_text, q.type));
    } else {
        addQuestion();
    }

    document.getElementById('tableSection').style.display = 'none';
    document.getElementById('builderSection').style.display = 'block';
}

function closeBuilder() {
    document.getElementById('builderSection').style.display = 'none';
    document.getElementById('tableSection').style.display = 'block';
}

function addQuestion(text = "", type = "Scale") {
    const canvas = document.getElementById("formCanvas");
    const num = canvas.querySelectorAll(".form-q-box").length + 1;
    const div = document.createElement("div");
    
    div.className = "form-q-box existing-q"; 
    div.style = "margin-bottom: 10px; display: flex; gap: 10px; align-items: center; background: #fdfdfd; padding: 10px; border: 1px solid #eee; border-radius: 4px;";

    div.innerHTML = `
        <span style="font-weight:bold; color:var(--feu-green); min-width:25px;">${num}.</span>
        <input type="text" placeholder="Enter Question" value="${text}" class="q-text" style="flex:3; padding: 8px;">
        <select class="q-type" style="flex:1; padding: 8px;">
            <option value="Scale" ${type === 'Scale' ? 'selected' : ''}>Scale (1-5)</option>
            <option value="Text" ${type === 'Text' ? 'selected' : ''}>Text Response</option>
        </select>
        <button class="btn-danger" onclick="this.parentElement.remove()" style="padding: 8px 12px;">✕</button>
    `;
    canvas.appendChild(div);
}

async function saveForm() {
    const questions = [...document.querySelectorAll(".existing-q")]
        .map((box) => ({
            text: box.querySelector(".q-text").value.trim(),
            type: box.querySelector(".q-type").value,
        }))
        .filter((q) => q.text);

    const payload = {
        form_id: document.getElementById('currentFormId').value,
        school_year: document.getElementById('sy').value.trim(),
        semester: document.getElementById('sem').value,
        open_at: document.getElementById('openAt').value,
        close_at: document.getElementById('closeAt').value,
        questions: questions
    };

    if (!payload.school_year || !payload.open_at || !questions.length) {
        return alert("Please fill in all fields and add at least one question.");
    }

    const result = await API.request("/admin/save-evaluation-form", "POST", payload);
    if (result) {
        alert("Success!");
        location.reload();
    }
}

/**
 * Modified deleteForm: 
 * It now uses the ID from the hidden input field instead of a Blade variable.
 */
async function deleteForm() {
    const id = document.getElementById('currentFormId').value;
    
    if (!id) {
        alert("No form selected to delete.");
        return;
    }

    if (!confirm("Are you sure? This will delete the period and all associated questions.")) return;

    try {
        const result = await API.request(`/admin/delete-evaluation-form/${id}`, "DELETE");
        if (result.success) {
            alert("Deleted successfully.");
            location.reload();
        } else {
            alert(result.message || "Could not delete form.");
        }
    } catch (e) {
        // Handle error response from Controller (e.g. data already exists)
        const errorMsg = e.response?.data?.message || "Error deleting form.";
        alert(errorMsg);
    }
}