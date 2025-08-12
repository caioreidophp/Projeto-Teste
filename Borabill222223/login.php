<?php
// Login PHP file

// ... Other code ... 

// Line 46
// Change "🤟 Handly" to "Handly"
$logo = "Handly";

// Benefits section
// Lines 135-149 updating emojis
$benefits = [
    "Disk Storage",  // 💾 
    "Target Achievements",  // 🎯 
    "Mobile Access",  // 📱 
    "Trophy Rewards"  // 🏆 
];

// Adding bottom navigation bar
echo '<nav style="position: fixed; bottom: 0; width: 100%; background-color: white;">
    <ul style="list-style-type: none; display: flex; justify-content: space-around; padding: 10px;">
        <li style="flex: 1; text-align: center;"><a href="#">Início</a></li>
        <li style="flex: 1; text-align: center;"><a href="#">Aprender</a></li>
        <li style="flex: 1; text-align: center;"><a href="#">Missões</a></li>
        <li style="flex: 1; text-align: center;"><a href="#">Progresso</a></li>
        <li style="flex: 1; text-align: center;"><a href="#">Perfil</a></li>
    </ul>
</nav>'; 

// ... Other code ... 
?>