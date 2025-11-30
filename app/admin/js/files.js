let allFiles = [];

async function initFiles() {
  await checkSession();
  updateSidebar();
  await loadFiles();
}

async function loadFiles() {
  try {
    const data = await apiRequest("/admin/files.php");
    allFiles = data.data;
    displayFiles();
  } catch (error) {
    showAlert("Failed to load files", "error");
  }
}

function displayFiles() {
  const container = document.getElementById("filesContainer");

  if (allFiles.length === 0) {
    container.innerHTML =
      '<div class="empty-state">No files uploaded yet</div>';
    return;
  }

  container.innerHTML = `
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Subject</th>
                        <th>Type</th>
                        <th>Exam Type</th>
                        <th>Teacher</th>
                        <th>Downloads</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${allFiles
                      .map(
                        (file) => `
                        <tr>
                            <td>${file.title}</td>
                            <td>${file.subject_name}</td>
                            <td><span class="badge badge-primary">${
                              file.file_type
                            }</span></td>
                            <td>${file.exam_type || "-"}</td>
                            <td>${file.teacher_name}</td>
                            <td>${file.download_count}</td>
                            <td>${formatDate(file.created_at)}</td>
                            <td>
                                <button onclick="editFile(${
                                  file.id
                                })" class="btn btn-secondary btn-sm">Edit</button>
                                ${
                                  canDelete(file)
                                    ? `
                                    <button onclick="deleteFile(${file.id})" class="btn btn-danger btn-sm">Delete</button>
                                `
                                    : ""
                                }
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

function canDelete(file) {
  return (
    currentUser.role === "super_admin" || file.uploaded_by == currentUser.id
  );
}

function editFile(fileId) {
  const file = allFiles.find((f) => f.id === fileId);
  if (!file) return;

  document.getElementById("editFileId").value = file.id;
  document.getElementById("editTitle").value = file.title;
  document.getElementById("editDescription").value = file.description || "";
  document.getElementById("editTeacher").value = file.teacher_name;

  document.getElementById("editModal").style.display = "block";
}

async function saveFileEdit() {
  const fileId = document.getElementById("editFileId").value;
  const data = {
    id: fileId,
    title: document.getElementById("editTitle").value,
    description: document.getElementById("editDescription").value,
    teacher_name: document.getElementById("editTeacher").value,
  };

  try {
    await apiRequest("/admin/files.php", {
      method: "PUT",
      body: JSON.stringify(data),
    });

    showAlert("File updated successfully", "success");
    closeEditModal();
    await loadFiles();
  } catch (error) {
    showAlert(error.message, "error");
  }
}

async function deleteFile(fileId) {
  if (!confirm("Are you sure you want to delete this file?")) return;

  try {
    await apiRequest("/admin/files.php", {
      method: "DELETE",
      body: JSON.stringify({ id: fileId }),
    });

    showAlert("File deleted successfully", "success");
    await loadFiles();
  } catch (error) {
    showAlert(error.message, "error");
  }
}

function closeEditModal() {
  document.getElementById("editModal").style.display = "none";
}

document.addEventListener("DOMContentLoaded", initFiles);
