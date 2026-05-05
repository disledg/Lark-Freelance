<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $db->escape($_POST['full_name']);
    $email = $db->escape($_POST['email']);
    $level = $db->escape($_POST['level']);
    $skills = $db->escape($_POST['skills']);
    $experience = $db->escape($_POST['experience']);
    $portfolio = $db->escape($_POST['portfolio']);
    $telegram = $db->escape($_POST['telegram']);
    $github = $db->escape($_POST['github']);
    
    $sql = "INSERT INTO developer_applications (full_name, email, level, skills, experience, portfolio, telegram, github) 
            VALUES ('$full_name', '$email', '$level', '$skills', '$experience', '$portfolio', '$telegram', '$github')";
    
    if ($db->query($sql)) {
        redirect('../thanks.php?type=developer');
    } else {
        $error = 'Ошибка при отправке заявки';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация разработчика</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="dark-theme">
    <div class="container" style="max-width: 600px; margin-top: 100px;">
        <div class="cyber-card" style="padding: 2rem;">
            <h2 class="form-title">Стать разработчиком</h2>
            
            <?php if (isset($error)): ?>
                <div style="color: #ff3366;"><?= $error ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div style="margin-bottom: 1rem;">
                    <input type="text" name="full_name" placeholder="ФИО *" required 
                           style="width: 100%; padding: 0.8rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,215,0,0.3); color: white;">
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <input type="email" name="email" placeholder="Email *" required 
                           style="width: 100%; padding: 0.8rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,215,0,0.3); color: white;">
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <select name="level" style="width: 100%; padding: 0.8rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,215,0,0.3); color: white;">
                        <option value="junior">Junior</option>
                        <option value="middle">Middle</option>
                    </select>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <textarea name="skills" placeholder="Навыки (через запятую) *" required 
                              style="width: 100%; padding: 0.8rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,215,0,0.3); color: white;" rows="3"></textarea>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <textarea name="experience" placeholder="Опыт работы *" required 
                              style="width: 100%; padding: 0.8rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,215,0,0.3); color: white;" rows="5"></textarea>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <input type="url" name="portfolio" placeholder="Ссылка на портфолио" 
                           style="width: 100%; padding: 0.8rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,215,0,0.3); color: white;">
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <input type="text" name="telegram" placeholder="Telegram" 
                           style="width: 100%; padding: 0.8rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,215,0,0.3); color: white;">
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <input type="url" name="github" placeholder="GitHub" 
                           style="width: 100%; padding: 0.8rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,215,0,0.3); color: white;">
                </div>
                
                <button type="submit" class="btn-cyber btn-gold" style="width: 100%;">
                    Отправить заявку
                </button>
            </form>
        </div>
    </div>
</body>
</html>