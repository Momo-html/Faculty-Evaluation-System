/**
 * FEU Faculty Evaluation - Professional Form Builder
 */

let builderDirty = false;

function showCreateForm() {
    setBuilderVisibility(true);
    document.getElementById("builderTitle").innerText = "Create Evaluation Form";
    document.getElementById("currentFormId").value = "";
    document.getElementById("title").value = "";
    document.getElementById("description").value = "";
    document.getElementById("sy").value = "";
    document.getElementById("sem").value = "1st Semester";
    document.getElementById("openAt").value = "";
    document.getElementById("closeAt").value = "";
    document.getElementById("isActive").checked = true;
    document.getElementById("formCanvas").innerHTML = "";
    document.getElementById("deleteBtn").hidden = true;
    addQuestion();
    refreshQuestionNumbers();
    updatePreview();
    builderDirty = false;
}

async function loadFormForEdit(id) {
    const form = await API.request(`/admin/get-form-data/${id}`, "GET");
    if (!form) return;

    setBuilderVisibility(true);
    document.getElementById("builderTitle").innerText = `Editing: ${form.title || form.school_year}`;
    document.getElementById("currentFormId").value = form.id;
    document.getElementById("title").value = form.title || "";
    document.getElementById("description").value = form.description || "";
    document.getElementById("sy").value = form.school_year;
    document.getElementById("sem").value = form.semester;
    document.getElementById("isActive").checked = Boolean(form.is_active);
    document.getElementById("deleteBtn").hidden = false;

    if (form.open_at) document.getElementById("openAt").value = form.open_at.replace(" ", "T").slice(0, 16);
    if (form.close_at) document.getElementById("closeAt").value = form.close_at.replace(" ", "T").slice(0, 16);

    const canvas = document.getElementById("formCanvas");
    canvas.innerHTML = "";

    if (form.questions && form.questions.length > 0) {
        form.questions.forEach((q) => addQuestion({
            id: q.id,
            text: q.question_text,
            type: q.type,
            category: q.category || "",
            required: q.is_required,
            options: q.options || [],
            scaleMin: q.scale_min || 1,
            scaleMax: q.scale_max || 5,
        }));
    } else {
        addQuestion();
    }

    refreshQuestionNumbers();
    updatePreview();
    builderDirty = false;
}

function closeBuilder() {
    setBuilderVisibility(false);
}

function setBuilderVisibility(show) {
    document.getElementById("tableSection").hidden = show;
    document.getElementById("builderSection").hidden = !show;
}

function addQuestion(data = {}) {
    const canvas = document.getElementById("formCanvas");
    const div = document.createElement("div");
    const type = data.type || "Scale";
    const options = Array.isArray(data.options) ? data.options.join("\n") : "";

    div.className = "question-card existing-q";
    div.innerHTML = `
        <div class="question-card-header">
            <div class="question-title-group">
                <span class="question-drag-handle" aria-hidden="true">::::</span>
                <span class="question-order">1</span>
                <div>
                    <strong>Question</strong>
                    <small>Build what students will answer</small>
                </div>
            </div>
            <div class="builder-actions">
                <button type="button" class="builder-btn builder-btn-small builder-btn-secondary" onclick="moveQuestion(this, -1)">Up</button>
                <button type="button" class="builder-btn builder-btn-small builder-btn-secondary" onclick="moveQuestion(this, 1)">Down</button>
                <button type="button" class="builder-btn builder-btn-small builder-btn-secondary" onclick="duplicateQuestion(this)">Duplicate</button>
                <button type="button" class="builder-btn builder-btn-small builder-btn-danger" onclick="removeQuestion(this)">Remove</button>
            </div>
        </div>

        <input type="hidden" class="q-id" value="${escapeHtml(data.id || "")}">

        <div class="question-grid">
            <label>
                <span>Question Text</span>
                <textarea class="q-text" rows="2" placeholder="Write the question students will answer">${escapeHtml(data.text || "")}</textarea>
            </label>
            <label>
                <span>Category</span>
                <input type="text" class="q-category" value="${escapeHtml(data.category || "")}" placeholder="Teaching">
            </label>
            <label>
                <span>Question Type</span>
                <select class="q-type" onchange="syncQuestionType(this)">
                    <option value="Scale" ${type === "Scale" ? "selected" : ""}>Rating Scale</option>
                    <option value="Multiple Choice" ${type === "Multiple Choice" ? "selected" : ""}>Multiple Choice</option>
                    <option value="Text" ${type === "Text" ? "selected" : ""}>Text / Comment</option>
                </select>
            </label>
        </div>

        <div class="question-scale">
            <label>
                <span>Scale Minimum</span>
                <input type="number" class="q-scale-min" min="0" max="10" value="${escapeHtml(data.scaleMin || 1)}">
            </label>
            <label>
                <span>Scale Maximum</span>
                <input type="number" class="q-scale-max" min="1" max="10" value="${escapeHtml(data.scaleMax || 5)}">
            </label>
        </div>

        <div class="question-options">
            <label class="builder-full">
                <span>Multiple Choice Options</span>
                <textarea class="q-options" rows="4" placeholder="Put one option per line">${escapeHtml(options)}</textarea>
            </label>
        </div>

        <label class="question-required">
            <input type="checkbox" class="q-required" ${data.required === false ? "" : "checked"}>
            <span class="question-required-switch" aria-hidden="true"></span>
            <span>Required question</span>
        </label>
    `;

    canvas.appendChild(div);
    syncQuestionType(div.querySelector(".q-type"));
    div.querySelectorAll("input, textarea, select").forEach((field) => {
        field.addEventListener("input", () => {
            markBuilderDirty();
            updatePreview();
        });
        field.addEventListener("change", () => {
            markBuilderDirty();
            updatePreview();
        });
    });
    markBuilderDirty();
    updatePreview();
}

function syncQuestionType(select) {
    const card = select.closest(".question-card");
    const scale = card.querySelector(".question-scale");
    const options = card.querySelector(".question-options");
    scale.classList.toggle("hidden", select.value !== "Scale");
    options.classList.toggle("hidden", select.value !== "Multiple Choice");
    markBuilderDirty();
    updatePreview();
}

function moveQuestion(button, direction) {
    const card = button.closest(".question-card");
    const sibling = direction < 0 ? card.previousElementSibling : card.nextElementSibling;
    if (!sibling) return;

    if (direction < 0) {
        card.parentElement.insertBefore(card, sibling);
    } else {
        card.parentElement.insertBefore(sibling, card);
    }

    refreshQuestionNumbers();
    markBuilderDirty();
    updatePreview();
}

function removeQuestion(button) {
    if (!confirm("Remove this question from the form? Existing submitted answers will be protected by archive logic.")) return;
    button.closest(".question-card").remove();
    refreshQuestionNumbers();
    markBuilderDirty();
    updatePreview();
}

function duplicateQuestion(button) {
    const card = button.closest(".question-card");
    if (!card) return;

    addQuestion({
        text: `${card.querySelector(".q-text").value.trim()} Copy`.trim(),
        type: card.querySelector(".q-type").value,
        category: card.querySelector(".q-category").value.trim(),
        required: card.querySelector(".q-required").checked,
        scaleMin: Number(card.querySelector(".q-scale-min").value || 1),
        scaleMax: Number(card.querySelector(".q-scale-max").value || 5),
        options: card.querySelector(".q-options").value
            .split("\n")
            .map((option) => option.trim())
            .filter(Boolean),
    });

    refreshQuestionNumbers();
    markBuilderDirty();
    updatePreview();
}

function refreshQuestionNumbers() {
    document.querySelectorAll(".question-card").forEach((card, index) => {
        card.querySelector(".question-order").textContent = index + 1;
    });
}

function getQuestionsFromBuilder() {
    return [...document.querySelectorAll(".existing-q")]
        .map((box) => ({
            id: box.querySelector(".q-id").value || null,
            text: box.querySelector(".q-text").value.trim(),
            type: box.querySelector(".q-type").value,
            category: box.querySelector(".q-category").value.trim(),
            is_required: box.querySelector(".q-required").checked,
            scale_min: Number(box.querySelector(".q-scale-min").value || 1),
            scale_max: Number(box.querySelector(".q-scale-max").value || 5),
            options: box.querySelector(".q-options").value
                .split("\n")
                .map((option) => option.trim())
                .filter(Boolean),
        }))
        .filter((q) => q.text);
}

async function saveForm() {
    const questions = getQuestionsFromBuilder();
    const payload = {
        form_id: document.getElementById("currentFormId").value,
        title: document.getElementById("title").value.trim(),
        description: document.getElementById("description").value.trim(),
        school_year: document.getElementById("sy").value.trim(),
        semester: document.getElementById("sem").value,
        open_at: document.getElementById("openAt").value,
        close_at: document.getElementById("closeAt").value,
        is_active: document.getElementById("isActive").checked,
        questions,
    };

    const browserError = validatePayload(payload);
    if (browserError) {
        alert(browserError);
        return;
    }

    const result = await API.request("/admin/save-evaluation-form", "POST", payload);
    if (result) {
        builderDirty = false;
        alert("Evaluation form saved.");
        location.reload();
    }
}

function validatePayload(payload) {
    if (!payload.school_year) return "School year is required.";
    if (!payload.open_at) return "Open date and time is required.";
    if (!payload.close_at) return "Close date and time is required.";
    if (new Date(payload.close_at) <= new Date(payload.open_at)) return "Close date must be after open date.";
    if (!payload.questions.length) return "Add at least one question.";

    for (const [index, question] of payload.questions.entries()) {
        if (!question.text) return `Question ${index + 1} needs question text.`;
        if (question.type === "Scale" && question.scale_min >= question.scale_max) {
            return `Question ${index + 1} rating maximum must be greater than the minimum.`;
        }
        if (question.type === "Multiple Choice" && new Set(question.options).size < 2) {
            return `Question ${index + 1} needs at least two unique options.`;
        }
    }

    return null;
}

async function toggleFormStatus(id) {
    const result = await API.request(`/admin/evaluation-forms/${id}/toggle-status`, "PATCH");
    if (result?.success) {
        alert(result.message);
        location.reload();
    }
}

async function deleteForm(id = null) {
    const formId = id || document.getElementById("currentFormId").value;
    if (!formId) {
        alert("No form selected to archive.");
        return;
    }

    if (!confirm("Archive this form? Students will no longer see it, and existing answers will stay protected.")) return;

    const result = await API.request(`/admin/delete-evaluation-form/${formId}`, "DELETE");
    if (result?.success) {
        alert(result.message || "Form archived.");
        location.reload();
    }
}

function previewStudentView() {
    const formId = document.getElementById("currentFormId")?.value;

    if (!formId) {
        alert("Please save the form first before previewing the student view.");
        return;
    }

    if (builderDirty) {
        alert("You have unsaved changes. Save first to preview the latest version.");
        return;
    }

    window.location.href = `/admin/evaluation-forms/${formId}/preview-student`;
}

function updatePreview() {
    const title = document.getElementById("title")?.value.trim() || "Faculty Evaluation Form";
    const schoolYear = document.getElementById("sy")?.value.trim() || "School year";
    const semester = document.getElementById("sem")?.value || "Semester";
    const questions = getQuestionsFromBuilder();

    const titleEl = document.getElementById("previewTitle");
    const metaEl = document.getElementById("previewMeta");
    const listEl = document.getElementById("previewQuestions");
    if (!titleEl || !metaEl || !listEl) return;

    titleEl.textContent = title;
    metaEl.textContent = `${schoolYear} | ${semester}`;
    listEl.innerHTML = "";

    if (!questions.length) {
        listEl.innerHTML = `
            <div class="preview-empty-state">
                <strong>No questions yet</strong>
                <span>Add a question to preview it here.</span>
            </div>
        `;
        return;
    }

    questions.forEach((question, index) => {
        const item = document.createElement("div");
        item.className = "preview-question";
        const required = question.is_required ? " *" : "";
        let answerPreview = "";

        if (question.type === "Scale") {
            answerPreview = `<div class="preview-answer-scale">`;
            for (let i = question.scale_min; i <= question.scale_max; i++) {
                answerPreview += `
                    <label class="preview-radio-option">
                        <span>${i}</span>
                        <input type="radio" disabled>
                    </label>
                `;
            }
            answerPreview += `</div>`;
        } else if (question.type === "Multiple Choice") {
            answerPreview = `
                <div class="preview-answer-options">
                    ${question.options.map((option) => `
                        <label class="preview-option-row">
                            <input type="radio" disabled>
                            <span>${escapeHtml(option)}</span>
                        </label>
                    `).join("")}
                </div>
            `;
        } else {
            answerPreview = `<textarea class="preview-textarea" rows="2" placeholder="Student text response" disabled></textarea>`;
        }

        item.innerHTML = `
            <span class="preview-question-number">Question ${index + 1}</span>
            <strong>${escapeHtml(question.text)}${required}</strong>
            ${answerPreview}
        `;
        listEl.appendChild(item);
    });
}

function escapeHtml(value) {
    return String(value ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll('"', "&quot;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;");
}

function markBuilderDirty() {
    builderDirty = true;
}

document.addEventListener("DOMContentLoaded", () => {
    const editId = new URLSearchParams(window.location.search).get("edit");

    if (editId && /^\d+$/.test(editId)) {
        loadFormForEdit(editId);
    }
});
