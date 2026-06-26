<div class="topbar">

<div>

<h4>

Welcome,
<?php echo $_SESSION['name']; ?> 👋

</h4>

<small>

Role :
<?php echo $_SESSION['role']; ?>

</small>

</div>

<div class="profile">

<i class="fa-regular fa-bell fa-lg"></i>

<div class="avatar">

<?php

echo strtoupper(substr($_SESSION['name'],0,1));

?>

</div>

</div>

</div>