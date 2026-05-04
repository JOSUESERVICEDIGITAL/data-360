<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f5f7fb;
}

/* LAYOUT */
.back-wrapper {
    display: flex;
}

.back-main {
    flex: 1;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

.back-content {
    padding: 20px;
}

/* HEADER */
.header {
    height: 60px;
    background: #fff;
    border-bottom: 1px solid #e5eaf0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 20px;
}

/* SIDEBAR */
.sidebar {
    width: 240px;
    background: #0f172a;
    color: white;
    min-height: 100vh;
    padding: 20px;
}

.sidebar a {
    display: block;
    color: #cbd5e1;
    padding: 10px;
    border-radius: 6px;
    margin-bottom: 5px;
    text-decoration: none;
}

.sidebar a:hover {
    background: #1e293b;
    color: #fff;
}

/* ALERTS */
.alert {
    padding: 10px;
    border-radius: 6px;
    margin-bottom: 15px;
}

.alert-success {
    background: #dcfce7;
    color: #166534;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 16px;
    margin: 20px 0;
}

.stat-card,
.card {
    background: #fff;
    border-radius: 10px;
    padding: 18px;
    box-shadow: 0 6px 16px rgba(15, 23, 42, .08);
}

.stat-card h3 {
    margin: 0;
    color: #64748b;
    font-size: 14px;
}

.stat-card strong {
    display: block;
    font-size: 32px;
    margin-top: 8px;
    color: #0f172a;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 14px;
}

.table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
}

.table th,
.table td {
    padding: 12px;
    border-bottom: 1px solid #e5e7eb;
    text-align: left;
}

.btn-primary,
.btn-secondary,
.btn-danger {
    display: inline-block;
    padding: 9px 14px;
    border-radius: 6px;
    text-decoration: none;
    border: none;
    cursor: pointer;
}

.btn-primary {
    background: #005da8;
    color: #fff;
}

.btn-secondary {
    background: #e2e8f0;
    color: #0f172a;
}

.btn-danger {
    background: #dc2626;
    color: #fff;
}

.form-card {
    background: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 6px 16px rgba(15, 23, 42, .08);
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.form-group input,
.form-group select,
.form-group textarea {
    padding: 10px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
}

.actions {
    display: flex;
    gap: 8px;
    margin-top: 16px;
}
</style>