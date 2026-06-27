<div class="topbar">
    <div class="topbar-left">
        <button class="menu-toggle" id="menuToggle">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" placeholder="Search members, loans, payments...">
        </div>
    </div>
    
    <div class="topbar-right">
        <button class="icon-btn">
            <i class="fa-regular fa-sun"></i>
        </button>
        <button class="icon-btn">
            <i class="fa-regular fa-bell"></i>
            <span class="badge-dot"></span>
        </button>
        <div class="profile">
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['name'] ?? 'Admin'); ?>&background=4f46e5&color=fff&size=36" class="avatar">
            <div>
                <h6><?php echo $_SESSION['name'] ?? 'Admin'; ?></h6>
                <small><?php echo ucfirst($_SESSION['role'] ?? 'Admin'); ?></small>
            </div>
            <i class="fa-solid fa-chevron-down" style="font-size:12px;color:var(--gray-400);"></i>
        </div>
    </div>
</div>