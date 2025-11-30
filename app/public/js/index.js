async function loadBranches() {
    try {
        const data = await apiRequest('/public/branches.php');
        displayBranches(data.data);
    } catch (error) {
        document.getElementById('branchesGrid').innerHTML = 
            '<div class="error-message">Failed to load branches. Please try again.</div>';
    }
}

function displayBranches(branches) {
    const grid = document.getElementById('branchesGrid');
    
    const icons = {
        'CSE': 'í²»',
        'ECE': 'í³¡',
        'MECH': 'âš™ï¸',
        'CIVIL': 'í¿—ï¸'
    };
    
    grid.innerHTML = branches.map(branch => `
        <a href="subjects.html?branch_id=${branch.id}" class="branch-card">
            <div class="branch-icon">${icons[branch.code] || 'í³š'}</div>
            <h3>${branch.name}</h3>
            <p>${branch.code}</p>
        </a>
    `).join('');
}

document.addEventListener('DOMContentLoaded', loadBranches);
