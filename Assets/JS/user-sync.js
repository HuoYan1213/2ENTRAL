// 用户信息同步管理器
class UserSyncManager {
    constructor() {
        this.init();
    }
    
    init() {
        // 监听用户更新事件
        window.addEventListener('userProfileUpdated', (e) => {
            this.handleUserUpdate(e.detail);
        });
        
        // 检查是否有存储的用户信息
        this.restoreUserInfo();
    }
    
    handleUserUpdate(userData) {
        // 更新导航栏
        this.updateNavigation(userData);
        
        // 存储到sessionStorage
        this.storeUserInfo(userData);
    }
    
    updateNavigation(userData) {
        // 这里放置与Dashboard.php相同的更新逻辑
        // ...
    }
    
    storeUserInfo(userData) {
        if (typeof(Storage) !== "undefined") {
            sessionStorage.setItem('userName', userData.userName || '');
            sessionStorage.setItem('userImage', userData.userImage || '');
            sessionStorage.setItem('lastUpdate', new Date().getTime());
        }
    }
    
    restoreUserInfo() {
        if (typeof(Storage) !== "undefined") {
            const userName = sessionStorage.getItem('userName');
            const userImage = sessionStorage.getItem('userImage');
            
            if (userName || userImage) {
                this.updateNavigation({
                    userName: userName,
                    userImage: userImage
                });
            }
        }
    }
}

// 初始化
document.addEventListener('DOMContentLoaded', () => {
    window.userSync = new UserSyncManager();
});