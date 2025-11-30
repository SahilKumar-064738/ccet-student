let allMaterials = [];
let subjectId = null;
let branchId = null;
let subjectData = null;

async function init() {
    subjectId = getQueryParam('subject_id');
    branchId = getQueryParam('branch_id');
    
    if (!subjectId || !branchId) {
        window.location.href = 'index.html';
        return;
    }
    
    await loadSubjectInfo();
    await loadMaterials();
    setupFilters();
}

async function loadSubjectInfo() {
    try {
        const data = await apiRequest(`/public/subjects.php?id=${subjectId}`);
        subjectData = data.data;
        
        document.getElementById('subjectName').textContent = subjectData.subject_name;
        document.getElementById('pageTitle').textContent = subjectData.subject_name;
        document.getElementById('subjectDetails').textContent = 
            `${subjectData.branch_name} - Year ${subjectData.year_number}`;
        
        document.getElementById('breadcrumbBranch').href = `subjects.html?branch_id=${branchId}`;
        document.getElementById('breadcrumbBranch').textContent = subjectData.branch_name;
    } catch (error) {
        showError('Failed to load subject information');
    }
}

async function loadMaterials() {
    try {
        const data = await apiRequest(`/public/files.php?subject_id=${subjectId}`);
        allMaterials = data.data;
        populateTeacherFilter();
        displayMaterials(allMaterials);
    } catch (error) {
        document.getElementById('materialsContainer').innerHTML = 
            '<div class="error-message">Failed to load materials</div>';
    }
}

function populateTeacherFilter() {
    const teacherFilter = document.getElementById('teacherFilter');
    const teachers = [...new Set(allMaterials.map(m => m.teacher_name))].sort();
    
    teachers.forEach(teacher => {
        const option = document.createElement('option');
        option.value = teacher;
        option.textContent = teacher;
        teacherFilter.appendChild(option);
    });
}

function setupFilters() {
    document.getElementById('fileTypeFilter').addEventListener('change', filterMaterials);
    document.getElementById('examTypeFilter').addEventListener('change', filterMaterials);
    document.getElementById('teacherFilter').addEventListener('change', filterMaterials);
    document.getElementById('searchInput').addEventListener('input', filterMaterials);
}

function filterMaterials() {
    const fileType = document.getElementById('fileTypeFilter').value;
    const examType = document.getElementById('examTypeFilter').value;
    const teacher = document.getElementById('teacherFilter').value;
    const search = document.getElementById('searchInput').value.toLowerCase();
    
    const filtered = allMaterials.filter(material => {
        if (fileType && material.file_type !== fileType) return false;
        if (examType && material.exam_type !== examType) return false;
        if (teacher && material.teacher_name !== teacher) return false;
        if (search && !material.title.toLowerCase().includes(search) && 
            !material.description?.toLowerCase().includes(search)) return false;
        return true;
    });
    
    displayMaterials(filtered);
}

function displayMaterials(materials) {
    const container = document.getElementById('materialsContainer');
    
    if (materials.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">Ì≥Ñ</div>
                <p>No materials found</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = `
        <div class="materials-grid">
            ${materials.map(material => `
                <div class="material-card">
                    <div class="material-info">
                        <div class="material-header">
                            <span class="material-type type-${material.file_type}">${material.file_type}</span>
                            ${material.exam_type ? `<span class="exam-badge">${material.exam_type}</span>` : ''}
                        </div>
                        <div class="material-title">${material.title}</div>
                        <div class="material-meta">
                            <span>Ì±®‚ÄçÌø´ ${material.teacher_name}</span>
                            <span>Ì≥Ö ${formatDate(material.created_at)}</span>
                            <span>Ì≥• ${material.download_count} downloads</span>
                            <span>Ì≤æ ${formatFileSize(material.file_size)}</span>
                        </div>
                        ${material.description ? `<div class="material-description">${material.description}</div>` : ''}
                    </div>
                    <div class="material-actions">
                        <button class="btn btn-secondary" onclick="previewPdf(${material.id}, '${material.title}')">
                            Ì±ÅÔ∏è Preview
                        </button>
                        <a href="/api/public/download.php?file_id=${material.id}" class="btn btn-primary" download>
                            ‚¨áÔ∏è Download
                        </a>
                    </div>
                </div>
            `).join('')}
        </div>
    `;
}

function previewPdf(fileId, title) {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('pdfViewer').src = `/api/public/preview.php?file_id=${fileId}`;
    document.getElementById('pdfModal').style.display = 'block';
}

function closePdfModal() {
    document.getElementById('pdfModal').style.display = 'none';
    document.getElementById('pdfViewer').src = '';
}

window.onclick = function(event) {
    const modal = document.getElementById('pdfModal');
    if (event.target === modal) {
        closePdfModal();
    }
}

document.addEventListener('DOMContentLoaded', init);
