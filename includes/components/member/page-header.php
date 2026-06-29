<!-- Page Header -->
<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
    <div>
        <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
            <i class="fas fa-users text-indigo-600"></i>
            Members Management
        </h1>
        <p class="text-gray-500 mt-1">Manage all registered members and their information</p>
    </div>
    <div class="flex gap-3 mt-4 md:mt-0">
        <a href="export_csv.php" 
           class="export-btn px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-all duration-200 flex items-center gap-2 shadow-lg shadow-emerald-600/20">
            <i class="fas fa-file-export"></i> Export CSV
        </a>
        <a href="add.php" 
           class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-all duration-200 flex items-center gap-2 shadow-lg shadow-indigo-600/20">
            <i class="fas fa-plus-circle"></i> Add Member
        </a>
    </div>
</div>