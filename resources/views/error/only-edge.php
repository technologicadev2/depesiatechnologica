<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès Restreint - Microsoft Edge Requis</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #64748b 0%, #475569 100%);
            position: relative;
            overflow-x: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Animated background particles */
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }
        
        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        
        .particle:nth-child(1) { width: 80px; height: 80px; left: 10%; animation-delay: 0s; }
        .particle:nth-child(2) { width: 120px; height: 120px; left: 20%; animation-delay: 2s; }
        .particle:nth-child(3) { width: 60px; height: 60px; left: 25%; animation-delay: 4s; }
        .particle:nth-child(4) { width: 100px; height: 100px; left: 40%; animation-delay: 0s; }
        .particle:nth-child(5) { width: 90px; height: 90px; left: 70%; animation-delay: 3s; }
        .particle:nth-child(6) { width: 140px; height: 140px; left: 80%; animation-delay: 1s; }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); opacity: 0.4; }
            50% { transform: translateY(-100px) rotate(180deg); opacity: 0.8; }
        }
        
        /* Main container */
        .container {
            position: relative;
            z-index: 10;
            max-width: 580px;
            width: 90%;
            margin: 0 auto;
        }
        
        .error-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 50px 40px;
            text-align: center;
            box-shadow: 
                0 25px 50px rgba(0, 0, 0, 0.15),
                0 0 0 1px rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
            transform: translateY(20px);
            animation: slideUp 0.8s ease-out forwards;
        }
        
        @keyframes slideUp {
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        /* Gradient border effect */
        .error-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #10b981, #059669, #047857, #065f46);
            background-size: 300% 100%;
            animation: gradientShift 3s ease-in-out infinite;
        }
        
        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        /* Icon */
        .icon-container {
            position: relative;
            margin-bottom: 30px;
        }
        
        .error-icon {
            width: 90px;
            height: 90px;
            margin: 0 auto;
            background: linear-gradient(135deg, #6b7280, #4b5563);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 15px 35px rgba(107, 114, 128, 0.25);
            position: relative;
            animation: pulse 2s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .error-icon::before {
            content: '🔒';
            font-size: 36px;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
        }
        
        .error-icon::after {
            content: '';
            position: absolute;
            top: -10px;
            left: -10px;
            right: -10px;
            bottom: -10px;
            border: 2px solid rgba(107, 114, 128, 0.2);
            border-radius: 50%;
            animation: ripple 2s linear infinite;
        }
        
        @keyframes ripple {
            0% { transform: scale(0.8); opacity: 1; }
            100% { transform: scale(1.2); opacity: 0; }
        }
        
        /* Typography */
        .title {
            font-size: 32px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 16px;
            letter-spacing: -0.5px;
        }
        
        .subtitle {
            font-size: 18px;
            color: #4a5568;
            margin-bottom: 12px;
            font-weight: 500;
        }
        
        .description {
            font-size: 16px;
            color: #718096;
            line-height: 1.6;
            margin-bottom: 40px;
            max-width: 420px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* Button */
        .button-container {
            margin-top: 40px;
        }
        
        .edge-button {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 16px 32px;
            font-size: 16px;
            font-weight: 600;
            color: white;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
            border-radius: 16px;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
            position: relative;
            overflow: hidden;
            min-width: 280px;
        }
        
        .edge-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }
        
        .edge-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(16, 185, 129, 0.4);
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
        }
        
        .edge-button:hover::before {
            left: 100%;
        }
        
        .edge-button:active {
            transform: translateY(-1px);
        }
        
        .edge-icon {
            font-size: 20px;
            filter: drop-shadow(0 1px 2px rgba(0,0,0,0.1));
        }
        
        /* Browser info */
        .browser-info {
            margin-top: 30px;
            padding: 20px;
            background: rgba(156, 163, 175, 0.1);
            border-radius: 12px;
            border-left: 4px solid #6b7280;
        }
        
        .browser-info-title {
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }
        
        .browser-info-text {
            font-size: 13px;
            color: #4a5568;
            line-height: 1.5;
        }
        
        /* Security badge */
        .security-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            color: #047857;
            margin-top: 20px;
        }
        
        .security-badge::before {
            content: '🛡️';
            font-size: 14px;
        }
        
        /* Responsive */
        @media (max-width: 640px) {
            .error-card {
                padding: 40px 30px;
            }
            
            .title {
                font-size: 28px;
            }
            
            .subtitle {
                font-size: 16px;
            }
            
            .edge-button {
                min-width: 240px;
                padding: 14px 28px;
            }
        }
        
        /* Loading animation */
        .loading {
            opacity: 0;
            animation: fadeIn 0.6s ease-out 0.2s forwards;
        }
        
        @keyframes fadeIn {
            to { opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>
    
    <div class="container">
        <div class="error-card loading">
            <div class="icon-container">
                <div class="error-icon"></div>
            </div>
            
            <h1 class="title">Accès Restreint</h1>
            <p class="subtitle">Application Sécurisée</p>
            <p class="description">
                Cette application nécessite Microsoft Edge pour garantir une sécurité optimale et une expérience utilisateur complète.
            </p>
            
            <div class="button-container">
                <a href="microsoft-edge:https://depensia.ma/" class="edge-button">
                    <span class="edge-icon">↗</span>
                    Ouvrir avec Microsoft Edge
                </a>
            </div>
            
            <div class="security-badge">
                Connexion Sécurisée Requise
            </div>
            
            <div class="browser-info">
                <div class="browser-info-title">Pourquoi Microsoft Edge ?</div>
                <div class="browser-info-text">
                    Edge offre des fonctionnalités de sécurité avancées et une compatibilité optimale avec nos systèmes d'entreprise.
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Smooth animations on load
        document.addEventListener('DOMContentLoaded', function() {
            const card = document.querySelector('.error-card');
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.8s cubic-bezier(0.4, 0, 0.2, 1)';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100);
        });
        
        // Add click feedback
        document.querySelector('.edge-button').addEventListener('click', function(e) {
            const button = e.currentTarget;
            button.style.transform = 'translateY(-1px) scale(0.98)';
            setTimeout(() => {
                button.style.transform = 'translateY(-3px) scale(1)';
            }, 150);
        });
    </script>
</body>
</html>