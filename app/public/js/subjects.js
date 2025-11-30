let allSubjects = [];
let branchId = null;
let branchData = null;

async function init() {
    branchId = getQueryParam('branch_id');
    if (!branchId) {
        window.location.href = 'index.html';
        return;
    }
    
    await loadBranchInfo();
    await loadSubjects();
    setupFilters();
}

async function loadBranchInfo() {
    try {
        const data = await apiRequest(`/public/branches.php?id=${branchId}`);
        branchData = data.data;
        document.getElementById('branchName').textContent = branchData.name;
        document.getElementById('pageTitle').textContent = `${branchData.name} - Subjects`;
    } catch (error) {
        showError('Failed to load branch information');
    }
}

async function loadSubjects() {
    try {
        const data = await apiRequest(`/public/subjects.php?branch_id=${branchId}`);
        allSubjects = data.data;
        populateYearFilter();
        displaySubjects(allSubjects);
    } catch (error) {
        document.getElementById('subjectsContainer').innerHTML = 
            '<div class="error-message">Failed to load subjects</div>';
    }
}

function populateYearFilter() {
    const yearFilter = document.getElementById('yearFilter');
    const years = [...new Set(allSubjects.map(s => s.year_number))].sort();
    
    years.forEach(year => {
        const option = document.createElement('option');
        option.value = year;
        option.textContent = `Year ${year}`;
        yearFilter.appendChild(option);
    });
}

function setupFilters() {
    document.getElementById('yearFilter').addEventListener('change', filterSubjects);
}

function filterSubjects() {
    const selectedYear = document.getElementById('yearFilter').value;
    
    const filtered = selectedYear 
        ? allSubjects.filter(s => s.year_number == selectedYear)
        : allSubjects;
    
    displaySubjects(filtered);
}

function displaySubjects(subjects) {
    const container = document.getElementById('subjectsContainer');
    
    if (subjects.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">í³š</div>
                <p>No subjects found</p>
            </div>
        `;
        return;
    }
    
    const byYear = subjects.reduce((acc, subject) => {
        const year = subject.year_number;
        if (!acc[year]) acc[year] = [];
        acc[year].push(subject);
        return acc;
    }, {});
    
    container.innerHTML = Object.keys(byYear).sort().map(year => `
        <div class="subjects-by-year">
            <div class="year-header">Year ${year}</div>
            <div class="subjects-grid">
                ${byYear[year].map(subject => `
                    <div class="subject-card" onclick="window.location.href='materials.html?subject_id=${subject.id}&branch_id=${branchId}'">
                        <h3>${subject.subject_name}</h3>
                        ${subject.subject_code ? `<div class="subject-code">${subject.subject_code}</div>` : ''}
                    </div>
                `).join('')}
            </div>
        </div>
    `).join('');
}

document.addEventListener('DOMContentLoaded', init);
