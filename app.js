// REPLACE function saveGameResult di app.js DENGAN INI:

async function saveGameResult(result, duration) {
    try {
        const opponent = gameMode === 'ai' ? 'AI' : 'Multiplayer';
        
        console.log('💾 Saving game:', {
            opponent,
            result,
            game_type: gameMode,
            moves_count: moveCount,
            duration
        });
        
        // PENTING: Ganti dari game_api.php ke save_game.php
        const response = await fetch('save_game.php', {  // ✅ BENAR!
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `opponent=${encodeURIComponent(opponent)}&result=${encodeURIComponent(result)}&game_type=${encodeURIComponent(gameMode)}&moves_count=${moveCount}&duration=${duration}`
        });
        
        console.log('Response status:', response.status);
        
        const text = await response.text();
        console.log('Response text:', text);
        
        const data = JSON.parse(text);
        
        if (data.success) {
            console.log('✅ Game saved successfully!', data);
            showNotification('✅ Permainan berhasil disimpan!', 'success');
        } else {
            console.error('❌ Save failed:', data.message);
            showNotification('❌ Gagal: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('💥 Error saving game:', error);
        showNotification('💥 Error: ' + error.message, 'error');
    }
}

// Tambahkan function showNotification jika belum ada
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        border-radius: 10px;
        color: white;
        font-weight: 600;
        z-index: 10000;
        box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        animation: slideIn 0.3s ease-out;
    `;
    
    if (type === 'success') {
        notification.style.background = 'linear-gradient(135deg, #11998e 0%, #38ef7d 100%)';
    } else if (type === 'error') {
        notification.style.background = 'linear-gradient(135deg, #eb3349 0%, #f45c43 100%)';
    } else {
        notification.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
    }
    
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// CSS untuk animation
if (!document.getElementById('notification-style')) {
    const style = document.createElement('style');
    style.id = 'notification-style';
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(400px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(400px); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
}