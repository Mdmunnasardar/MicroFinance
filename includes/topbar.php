<div class="topbar">

    <!-- Left -->
    <div class="topbar-left">

        <button class="menu-toggle">
            <i class="fa-solid fa-bars"></i>
        </button>

        <div class="search-box">

            <i class="fa-solid fa-magnifying-glass"></i>

            <input
                type="text"
                placeholder="Search members, loans, payments..."
            >

        </div>

    </div>

    <!-- Right -->
    <div class="topbar-right">

        <button class="icon-btn">

            <i class="fa-regular fa-sun"></i>

        </button>

        <button class="icon-btn notification-btn">

            <i class="fa-regular fa-bell"></i>

            <span class="badge-dot">5</span>

        </button>

        <div class="profile">

            <img
            src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['name']); ?>&background=4f46e5&color=fff"
            class="avatar">

            <div>

                <h6><?php echo $_SESSION['name']; ?></h6>

                <small>

                    <?php echo ucfirst($_SESSION['role']); ?>

                </small>

            </div>

            <i class="fa-solid fa-chevron-down"></i>

        </div>

    </div>

</div>