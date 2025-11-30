let branches = [];
let years = [];

async function initSubjects() {
  await checkSession();
  updateSidebar();
  await loadBranches();
  await loadYears();
  await loadSubjects();
}

async function loadBranches() {
  try {
    const data = await apiRequest("/public/branches.php");
    branches = data.data;
  } catch (error) {
    console.error("Failed to load branches");
  }
}

async function loadYears() {
  years = [
    { id: 1, year_number: 1 },
    { id: 2, year_number: 2 },
    { id: 3, year_number: 3 },
    { id: 4, year_number: 4 },
  ];
}

async function loadSubjects() {
  try {
    const data = await apiRequest("/admin/subjects.php");
    subjects = data.data;
    displaySubjects();
  } catch (error) {
    showAlert("Failed to load subjects", "error");
  }
}

function displaySubjects() {
  const container = document.getElementById("subjectsContainer");

  if (subjects.length === 0) {
    container.innerHTML =
      '<div class="empty-state">No subjects added yet</div>';
    return;
  }

  container.innerHTML = `
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Subject Name</th>
                        <th>Subject Code</th>
                        <th>Branch</th>
                        <th>Year</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${subjects
                      .map(
                        (subject) => `
                        <tr>
                            <td>${subject.subject_name}</td>
                            <td>${subject.subject_code || "-"}</td>
                            <td>${subject.branch_name}</td>
                            <td>Year ${subject.year_number}</td>
                            <td>
                                <button onclick="editSubject(${
                                  subject.id
                                })" class="btn btn-secondary btn-sm">Edit</button>
                                <button onclick="deleteSubject(${
                                  subject.id
                                })" class="btn btn-danger btn-sm">Delete</button>
                            </td>
                        </tr>
                    `
                      )
                      .join("")}
                </tbody>
            </table>
        </div>
    `;
}

function showAddSubjectModal() {
  populateBranchYearSelects("add");
  document.getElementById("addModal").style.display = "block";
}

function populateBranchYearSelects(prefix) {
  const branchSelect = document.getElementById(`${prefix}Branch`);
  const yearSelect = document.getElementById(`${prefix}Year`);

  if (currentUser.role === "super_admin") {
    branchSelect.innerHTML = branches
      .map((b) => `<option value="${b.id}">${b.name}</option>`)
      .join("");
    yearSelect.innerHTML = years
      .map((y) => `<option value="${y.id}">Year ${y.year_number}</option>`)
      .join("");
  } else {
    branchSelect.innerHTML = `<option value="${currentUser.branch_id}">${currentUser.branch_name}</option>`;
    branchSelect.disabled = true;
    yearSelect.innerHTML = `<option value="${currentUser.year_id}">Year ${currentUser.year_number}</option>`;
    yearSelect.disabled = true;
  }
}

async function saveNewSubject() {
  const data = {
    subject_name: document.getElementById("addName").value,
    subject_code: document.getElementById("addCode").value,
    branch_id: document.getElementById("addBranch").value,
    year_id: document.getElementById("addYear").value,
  };

  try {
    await apiRequest("/admin/subjects.php", {
      method: "POST",
      body: JSON.stringify(data),
    });

    showAlert("Subject added successfully", "success");
    closeAddModal();
    await loadSubjects();
  } catch (error) {
    showAlert(error.message, "error");
  }
}

function editSubject(subjectId) {
  const subject = subjects.find((s) => s.id === subjectId);
  if (!subject) return;

  document.getElementById("editSubjectId").value = subject.id;
  document.getElementById("editName").value = subject.subject_name;
  document.getElementById("editCode").value = subject.subject_code || "";

  document.getElementById("editModal").style.display = "block";
}

async function saveSubjectEdit() {
  const data = {
    id: document.getElementById("editSubjectId").value,
    subject_name: document.getElementById("editName").value,
    subject_code: document.getElementById("editCode").value,
  };

  try {
    await apiRequest("/admin/subjects.php", {
      method: "PUT",
      body: JSON.stringify(data),
    });

    showAlert("Subject updated successfully", "success");
    closeEditModal();
    await loadSubjects();
  } catch (error) {
    showAlert(error.message, "error");
  }
}

async function deleteSubject(subjectId) {
  if (!confirm("Are you sure? This will delete all files in this subject."))
    return;

  try {
    await apiRequest("/admin/subjects.php", {
      method: "DELETE",
      body: JSON.stringify({ id: subjectId }),
    });

    showAlert("Subject deleted successfully", "success");
    await loadSubjects();
  } catch (error) {
    showAlert(error.message, "error");
  }
}

function closeAddModal() {
  document.getElementById("addModal").style.display = "none";
}

function closeEditModal() {
  document.getElementById("editModal").style.display = "none";
}

document.addEventListener("DOMContentLoaded", initSubjects);
