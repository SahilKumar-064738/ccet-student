let dashboardStats = null;

async function initDashboard() {
  await checkSession();
  updateSidebar();
  await loadDashboardStats();
}

function updateSidebar() {
  document.getElementById("userName").textContent = currentUser.name;
  document.getElementById("userRole").textContent =
    currentUser.role === "super_admin"
      ? "Super Administrator"
      : `${currentUser.branch_name} - Year ${currentUser.year_number}`;
}

async function loadDashboardStats() {
  try {
    const data = await apiRequest("/admin/dashboard.php");
    dashboardStats = data.data;
    displayStats();
    displayRecentUploads();
  } catch (error) {
    showAlert("Failed to load dashboard data", "error");
  }
}

function displayStats() {
  const statsContainer = document.getElementById("statsContainer");

  if (currentUser.role === "super_admin") {
    statsContainer.innerHTML = `
            <div class="stat-card">
                <div class="stat-label">Total Subjects</div>
                <div class="stat-value">${dashboardStats.total_subjects}</div>
                <div class="stat-icon">📚</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Files</div>
                <div class="stat-value">${dashboardStats.total_files}</div>
                <div class="stat-icon">📄</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Admins</div>
                <div class="stat-value">${dashboardStats.total_admins}</div>
                <div class="stat-icon">👥</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Downloads</div>
                <div class="stat-value">${dashboardStats.total_downloads}</div>
                <div class="stat-icon">⬇️</div>
            </div>
        `;
  } else {
    statsContainer.innerHTML = `
            <div class="stat-card">
                <div class="stat-label">My Subjects</div>
                <div class="stat-value">${dashboardStats.my_subjects}</div>
                <div class="stat-icon">📚</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Files</div>
                <div class="stat-value">${dashboardStats.my_files}</div>
                <div class="stat-icon">📄</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Uploaded by Me</div>
                <div class="stat-value">${dashboardStats.uploaded_by_me}</div>
                <div class="stat-icon">📤</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">My Downloads</div>
                <div class="stat-value">${dashboardStats.my_downloads}</div>
                <div class="stat-icon">⬇️</div>
            </div>
        `;
  }
}

function displayRecentUploads() {
  const container = document.getElementById("recentUploads");
  const uploads =
    currentUser.role === "super_admin"
      ? dashboardStats.recent_uploads
      : dashboardStats.my_recent_uploads;

  if (!uploads || uploads.length === 0) {
    container.innerHTML = '<div class="empty-state">No recent uploads</div>';
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
                        ${
                          currentUser.role === "super_admin"
                            ? "<th>Uploaded By</th>"
                            : ""
                        }
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    ${uploads
                      .map(
                        (file) => `
                        <tr>
                            <td>${file.title}</td>
                            <td>${file.subject_name}</td>
                            <td><span class="badge badge-primary">${
                              file.file_type
                            }</span></td>
                            ${
                              currentUser.role === "super_admin"
                                ? `<td>${file.uploader_name}</td>`
                                : ""
                            }
                            <td>${formatDate(file.created_at)}</td>
                        </tr>
                    `
                      )
                      .join("")}
                </tbody>
            </table>
        </div>
    `;
}

document.addEventListener("DOMContentLoaded", initDashboard);
