<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Management</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: #f5f7fa;
            padding: 2rem;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 2rem;
        }

        .header-left h1 {
            font-size: 2rem;
            color: #1a202c;
            margin-bottom: 0.5rem;
        }

        .header-left p {
            color: #718096;
            font-size: 0.95rem;
        }

        .add-btn {
            background-color: #0d9488;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-size: 0.95rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: background-color 0.2s;
        }

        .add-btn:hover {
            background-color: #0f766e;
        }

        .search-filter-bar {
            background-color: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .search-box {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .search-icon {
            color: #a0aec0;
        }

        .search-input {
            flex: 1;
            border: none;
            outline: none;
            font-size: 0.95rem;
            color: #2d3748;
        }

        .search-input::placeholder {
            color: #cbd5e0;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
        }

        .filter-btn, .export-btn {
            background-color: transparent;
            border: none;
            color: #4a5568;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            transition: background-color 0.2s;
        }

        .filter-btn:hover, .export-btn:hover {
            background-color: #f7fafc;
        }

        .patient-table-container {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .table-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .table-header h2 {
            font-size: 1.1rem;
            color: #1a202c;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background-color: #f7fafc;
        }

        th {
            text-align: left;
            padding: 1rem 1.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #4a5568;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        tbody tr {
            border-bottom: 1px solid #e2e8f0;
            transition: background-color 0.2s;
        }

        tbody tr:hover {
            background-color: #f7fafc;
        }

        td {
            padding: 1.25rem 1.5rem;
            color: #2d3748;
            font-size: 0.95rem;
        }

        .patient-cell {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background-color: #a7f3d0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #065f46;
            font-size: 0.875rem;
        }

        .patient-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .patient-name {
            font-weight: 600;
            color: #1a202c;
        }

        .patient-id {
            font-size: 0.875rem;
            color: #718096;
        }

        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .contact-phone {
            color: #718096;
            font-size: 0.875rem;
        }

        .status-badge {
            display: inline-block;
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-active {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-inactive {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .actions-cell {
            display: flex;
            gap: 0.75rem;
        }

        .action-icon {
            cursor: pointer;
            color: #a0aec0;
            transition: color 0.2s;
            font-size: 1.25rem;
        }

        .action-icon:hover {
            color: #4a5568;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <h1>Patients</h1>
            <p>Manage your patient records and information</p>
        </div>
        <button class="add-btn">
            <span>👤</span>
            Add New Patient
        </button>
    </div>

    <div class="search-filter-bar">
        <div class="search-box">
            <span class="search-icon">🔍</span>
            <input type="text" class="search-input" placeholder="Search patients by name, email, or phone..." id="searchInput">
        </div>
        <div class="action-buttons">
            <button class="filter-btn">
                <span>⚙️</span>
                Filter
            </button>
            <button class="export-btn">
                <span>⬇️</span>
                Export
            </button>
        </div>
    </div>

    <div class="patient-table-container">
        <div class="table-header">
            <h2>All Patients (<span id="patientCount">8</span>)</h2>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Patient</th>
                    <th>Contact</th>
                    <th>Last Visit</th>
                    <th>Next Appointment</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="patientTableBody">
                <!-- Patient rows will be inserted here by JavaScript -->
            </tbody>
        </table>
    </div>

    <script>
        const patients = [
            {
                name: "Sarah Johnson",
                id: "P001",
                initials: "SJ",
                email: "sarah.johnson@email.com",
                phone: "+1 (555) 123-4567",
                lastVisit: "2024-01-10",
                nextAppointment: "2024-02-15",
                status: "active"
            },
            {
                name: "Michael Chen",
                id: "P002",
                initials: "MC",
                email: "michael.chen@email.com",
                phone: "+1 (555) 234-5678",
                lastVisit: "2024-01-08",
                nextAppointment: "2024-01-25",
                status: "active"
            },
            {
                name: "Emily Davis",
                id: "P003",
                initials: "ED",
                email: "emily.davis@email.com",
                phone: "+1 (555) 345-6789",
                lastVisit: "2024-01-05",
                nextAppointment: "Not scheduled",
                status: "active"
            },
            {
                name: "Robert Wilson",
                id: "P004",
                initials: "RW",
                email: "robert.wilson@email.com",
                phone: "+1 (555) 456-7890",
                lastVisit: "2023-12-20",
                nextAppointment: "2024-01-30",
                status: "active"
            },
            {
                name: "Lisa Anderson",
                id: "P005",
                initials: "LA",
                email: "lisa.anderson@email.com",
                phone: "+1 (555) 567-8901",
                lastVisit: "2024-01-12",
                nextAppointment: "2024-02-10",
                status: "active"
            },
            {
                name: "David Brown",
                id: "P006",
                initials: "DB",
                email: "david.brown@email.com",
                phone: "+1 (555) 678-9012",
                lastVisit: "2023-11-15",
                nextAppointment: "Not scheduled",
                status: "inactive"
            },
            {
                name: "Jennifer Martinez",
                id: "P007",
                initials: "JM",
                email: "jennifer.martinez@email.com",
                phone: "+1 (555) 789-0123",
                lastVisit: "2024-01-14",
                nextAppointment: "2024-02-20",
                status: "active"
            },
            {
                name: "Christopher Taylor",
                id: "P008",
                initials: "CT",
                email: "christopher.taylor@email.com",
                phone: "+1 (555) 890-1234",
                lastVisit: "2024-01-06",
                nextAppointment: "2024-01-28",
                status: "active"
            }
        ];

        function renderPatients(patientsToRender) {
            const tbody = document.getElementById('patientTableBody');
            tbody.innerHTML = '';

            patientsToRender.forEach(patient => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>
                        <div class="patient-cell">
                            <div class="avatar">${patient.initials}</div>
                            <div class="patient-info">
                                <div class="patient-name">${patient.name}</div>
                                <div class="patient-id">ID: ${patient.id}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="contact-info">
                            <div>${patient.email}</div>
                            <div class="contact-phone">${patient.phone}</div>
                        </div>
                    </td>
                    <td>${patient.lastVisit}</td>
                    <td>${patient.nextAppointment}</td>
                    <td>
                        <span class="status-badge status-${patient.status}">${patient.status}</span>
                    </td>
                    <td>
                        <div class="actions-cell">
                            <span class="action-icon" title="View">👁️</span>
                            <span class="action-icon" title="Edit">✏️</span>
                            <span class="action-icon" title="Delete">🗑️</span>
                        </div>
                    </td>
                `;
                tbody.appendChild(row);
            });

            document.getElementById('patientCount').textContent = patientsToRender.length;
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            const filtered = patients.filter(patient => 
                patient.name.toLowerCase().includes(searchTerm) ||
                patient.email.toLowerCase().includes(searchTerm) ||
                patient.phone.includes(searchTerm) ||
                patient.id.toLowerCase().includes(searchTerm)
            );
            renderPatients(filtered);
        });

        // Initial render
        renderPatients(patients);
    </script>
</body>
</html>