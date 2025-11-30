let subjects = [];
let examFiles = [];

async function initUpload() {
    await checkSession();
    updateSidebar();
    await loadSubjects();
    setupUploadForm();
}

async function loadSubjects() {
    try {
        const data = await apiRequest('/admin/subjects.php');
        subjects = data.data;
        populateSubjectSelect();
    } catch (error) {
        showAlert('Failed to load subjects', 'error');
    }
}

function populateSubjectSelect() {
    const select = document.getElementById('subjectId');
    select.innerHTML = '<option value="">Select Subject</option>' +
        subjects.map(s => `
            <option value="${s.id}">${s.subject_name} (${s.branch_code} - Year ${s.year_number})</option>
        `).join('');
}

function setupUploadForm() {
    const fileTypeSelect = document.getElementById('fileType');
    const examTypeGroup = document.getElementById('examTypeGroup');
    const linkedExamGroup = document.getElementById('linkedExamGroup');
    
    fileTypeSelect.addEventListener('change', async (e) => {
        const fileType = e.target.value;
        
        if (fileType === 'exam' || fileType === 'solution') {
            examTypeGroup.style.display = 'block';
        } else {
            examTypeGroup.style.display = 'none';
        }
        
        if (fileType === 'solution') {
            linkedExamGroup.style.display = 'block';
            await loadExamFiles();
        } else {
            linkedExamGroup.style.display = 'none';
        }
    });
    
    document.getElementById('file').addEventListener('change', (e) => {
        const file = e.target.files[0];
        const label = document.getElementById('fileName');
        
        if (file) {
            if (file.type !== 'application/pdf') {
                showAlert('Only PDF files are allowed', 'error');
                e.target.value = '';
                label.textContent = 'No file chosen';
                return;
            }
            label.textContent = file.name;
        } else {
            label.textContent = 'No file chosen';
        }
    });
    
    document.getElementById('uploadForm').addEventListener('submit', handleUpload);
}

async function loadExamFiles() {
    try {
        const data = await apiRequest('/admin/files.php?file_type=exam');
        examFiles = data.data;
        
        const select = document.getElementById('linkedExamId');
        select.innerHTML = '<option value="">Select Exam</option>' +
            examFiles.map(f => `
                <option value="${f.id}">${f.title} (${f.exam_type})</option>
            `).join('');
    } catch (error) {
        console.error('Failed to load exam files:', error);
    }
}

async function handleUpload(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const submitBtn = document.getElementById('submitBtn');
    
    // Validation
    if (!formData.get('file').name) {
        showAlert('Please select a file', 'error');
        return;
    }
    
    if (!formData.get('teacher_name').trim()) {
        showAlert('Teacher name is required', 'error');
        return;
    }
    
    submitBtn.disabled = true;
    submitBtn.textContent = 'Uploading...';
    
    try {
        const response = await fetch('/api/admin/upload.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Upload failed');
        }
        
        showAlert('File uploaded successfully!', 'success');
        e.target.reset();
        document.getElementById('fileName').textContent = 'No file chosen';
        document.getElementById('examTypeGroup').style.display = 'none';
        document.getElementById('linkedExamGroup').style.display = 'none';
    } catch (error) {
        showAlert(error.message, 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Upload File';
    }
}

document.addEventListener('DOMContentLoaded', initUpload);