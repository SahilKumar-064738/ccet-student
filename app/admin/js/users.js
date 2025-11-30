let branches = [];
let years = [];
let allUsers = [];

async function initUsers() {
    await checkSession();
    
    if (currentUser.role !== 'super_admin') {
        window.location.href = 'dashboard.html';
        return;
    }
    
    updateSidebar();
    await loadBranches();
    await loadYears();
    await loadUsers();
}

function updateSidebar() {
    document.getElementById('userName').textContent = currentUser.name;
    document.getElementById('userRole').textContent = 'Super Administrator';
}

async function loadBranches() {
    try {
        const data = await apiRequest('/public/branches.php');
        branches = data.data;
    } catch (error) {
        console.error('Failed to load branches');
    }
}

async function loadYears() {
    years = [
        { id: 1, year_number: 1 },
        { id: 2, year_number: 2 },
        { id: 3, year_number: 3 },
        { id: 4, year_number: 4 }
    ];
}

async function loadUsers() {
    try {
        const data = await apiRequest('/admin/users.php');
        allUsers = data.data;
        displayUsers();
    } catch (error) {
        showAlert('Failed to load users', 'error');
    }
}

function displayUsers() {
    const container = document.getElementById('usersContainer');
    
    if (allUsers.length === 0) {
        container.innerHTML = '<div class="empty-state">No users found</div>';
        return;
    }
    
    container.innerHTML = `
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Branch</th>
                        <th>Year</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${allUsers.map(user => `
                        <tr>
                            <td>${user.name}</td>
                            <td>${user.email}</td>
                            <td><span class="badge ${user.role === 'super_admin' ? 'badge-danger' : 'badge-primary'}">${user.role === 'super_admin' ? 'Super Admin' : 'Admin'}</span></td>
                            <td>${user.branch_name || '-'}</td>
                            <td>${user.year_number ? 'Year ' + user.year_number : '-'}</td>
                            <td><span class="badge ${user.is_active ? 'badge-success' : 'badge-danger'}">${user.is_active ? 'Active' : 'Inactive'}</span></td>
                            <td>
                                ${user.role !== 'super_admin' ? `
                                    <button onclick="toggleUserStatus(${user.id}, ${user.is_active})" class="btn btn-secondary btn-sm">
                                        ${user.is_active ? 'Deactivate' : 'Activate'}
                                    </button>
                                    <button onclick="deleteUser(${user.id})" class="btn btn-danger btn-sm">Delete</button>
                                ` : '<span style="color: var(--text-light);">Protected</span>'}
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

function showAddUserModal() {
    populateBranchYearSelects();
    document.getElementById('addModal').style.display = 'block';
}

function populateBranchYearSelects() {
    const branchSelect = document.getElementById('addBranch');
    const yearSelect = document.getElementById('addYear');
    
    branchSelect.innerHTML = '<option value="">Select Branch</option>' + 
        branches.map(b => `<option value="${b.id}">${b.name}</option>`).join('');
    
    yearSelect.innerHTML = '<option value="">Select Year</option>' +
        years.map(y => `<option value="${y.id}">Year ${y.year_number}</option>`).join('');
}

async function saveNewUser() {
    const data = {
        name: document.getElementById('addName').value,
        email: document.getElementById('addEmail').value,
        role: document.getElementById('addRole').value,
        branch_id: document.getElementById('addBranch').value,
        year_id: document.getElementById('addYear').value
    };
    
    if (!data.name || !data.email || !data.branch_id || !data.year_id) {
        showAlert('All fields are required', 'error');
        return;
    }
    
    try {
        await apiRequest('/admin/users.php', {
            method: 'POST',
            body: JSON.stringify(data)
        });
        
        showAlert('Admin created successfully', 'success');
        closeAddModal();
        await loadUsers();
    } catch (error) {
        showAlert(error.message, 'error');
    }
}

async function toggleUserStatus(userId, currentStatus) {
    try {
        await apiRequest('/admin/users.php', {
            method: 'PUT',
            body: JSON.stringify({
                id: userId,
                is_active: currentStatus ? 0 : 1
            })
        });
        
        showAlert('User status updated', 'success');
        await loadUsers();
    } catch (error) {
        showAlert(error.message, 'error');
    }
}

async function deleteUser(userId) {
    if (!confirm('Are you sure you want to delete this user?')) return;
    
    try {
        await apiRequest('/admin/users.php', {
            method: 'DELETE',
            body: JSON.stringify({ id: userId })
        });
        
        showAlert('User deleted successfully', 'success');
        await loadUsers();
    } catch (error) {
        showAlert(error.message, 'error');
    }
}

function closeAddModal() {
    document.getElementById('addModal').style.display = 'none';
    document.getElementById('addName').value = '';
    document.getElementById('addEmail').value = '';
}

document.addEventListener('DOMContentLoaded', initUsers);
