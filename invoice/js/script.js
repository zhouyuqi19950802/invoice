// js/script.js - 电子发票查重工具完整JavaScript代码

// 中文日期选择器类
class ChineseDatePicker {
    constructor(inputElement) {
        this.input = inputElement;
        this.currentDate = new Date();
        this.selectedDate = null;
        this.isOpen = false;
        this.modal = null;
        this.init();
    }
    
    init() {
        this.createDatePickerModal();
        this.bindEvents();
    }
    
    createDatePickerModal() {
        // 创建日期选择器弹窗
        const wrapper = document.createElement('div');
        wrapper.className = 'date-picker-wrapper';
        this.input.parentNode.insertBefore(wrapper, this.input);
        wrapper.appendChild(this.input);
        
        const modal = document.createElement('div');
        modal.className = 'date-picker-modal';
        modal.style.display = 'none';
        
        modal.innerHTML = `
            <div class="date-picker-header">
                <div class="date-picker-title">${this.formatYearMonth(this.currentDate)}</div>
                <div class="date-picker-nav">
                    <button type="button" data-action="prev-year">«</button>
                    <button type="button" data-action="prev-month">‹</button>
                    <button type="button" data-action="today">今</button>
                    <button type="button" data-action="next-month">›</button>
                    <button type="button" data-action="next-year">»</button>
                </div>
            </div>
            <div class="date-picker-grid" id="calendarGrid"></div>
            <div class="date-picker-footer">
                <div class="date-picker-info" id="dateInfo">请选择日期</div>
                <div class="date-picker-actions">
                    <button type="button" id="clearDate">清空</button>
                    <button type="button" id="confirmDate" class="primary">确定</button>
                </div>
            </div>
        `;
        
        wrapper.appendChild(modal);
        this.modal = modal;
    }
    
    bindEvents() {
        // 输入框点击事件
        this.input.addEventListener('click', (e) => {
            e.stopPropagation();
            this.open();
        });
        
        // 导航按钮事件
        this.modal.querySelectorAll('[data-action]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const action = btn.dataset.action;
                this.handleNavAction(action);
            });
        });
        
        // 清空按钮
        this.modal.querySelector('#clearDate').addEventListener('click', (e) => {
            e.stopPropagation();
            this.clear();
        });
        
        // 确定按钮
        this.modal.querySelector('#confirmDate').addEventListener('click', (e) => {
            e.stopPropagation();
            this.confirm();
        });
        
        // 点击外部关闭
        document.addEventListener('click', (e) => {
            if (!this.modal.contains(e.target) && e.target !== this.input) {
                this.close();
            }
        });
        
        // 点击弹窗内部不关闭
        this.modal.addEventListener('click', (e) => {
            e.stopPropagation();
        });
    }
    
    handleNavAction(action) {
        switch(action) {
            case 'prev-year':
                this.currentDate.setFullYear(this.currentDate.getFullYear() - 1);
                break;
            case 'prev-month':
                this.currentDate.setMonth(this.currentDate.getMonth() - 1);
                break;
            case 'today':
                this.currentDate = new Date();
                break;
            case 'next-month':
                this.currentDate.setMonth(this.currentDate.getMonth() + 1);
                break;
            case 'next-year':
                this.currentDate.setFullYear(this.currentDate.getFullYear() + 1);
                break;
        }
        this.renderCalendar();
    }
    
    renderCalendar() {
        const year = this.currentDate.getFullYear();
        const month = this.currentDate.getMonth();
        
        // 更新标题
        this.modal.querySelector('.date-picker-title').textContent = this.formatYearMonth(this.currentDate);
        
        // 生成日历
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const startDate = new Date(firstDay);
        startDate.setDate(startDate.getDate() - firstDay.getDay());
        
        const grid = this.modal.querySelector('#calendarGrid');
        grid.innerHTML = '';
        
        // 添加星期标题
        const weekdays = ['日', '一', '二', '三', '四', '五', '六'];
        weekdays.forEach(day => {
            const dayEl = document.createElement('div');
            dayEl.className = 'date-picker-weekday';
            dayEl.textContent = day;
            grid.appendChild(dayEl);
        });
        
        // 生成日期格子
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        for (let i = 0; i < 42; i++) {
            const currentDate = new Date(startDate);
            currentDate.setDate(startDate.getDate() + i);
            
            const dayEl = document.createElement('div');
            dayEl.className = 'date-picker-day';
            dayEl.textContent = currentDate.getDate();
            dayEl.dataset.date = this.formatDateISO(currentDate);
            
            // 添加样式类
            if (currentDate.getMonth() !== month) {
                dayEl.classList.add('other-month');
            }
            
            if (currentDate.getTime() === today.getTime()) {
                dayEl.classList.add('today');
            }
            
            if (this.selectedDate && currentDate.getTime() === this.selectedDate.getTime()) {
                dayEl.classList.add('selected');
            }
            
            // 点击事件
            dayEl.addEventListener('click', (e) => {
                e.stopPropagation();
                this.selectDate(currentDate);
            });
            
            grid.appendChild(dayEl);
            
            // 如果到了下个月的第一天且已经显示了当前月的所有天，则停止
            if (i > 35 && currentDate.getMonth() !== month) {
                break;
            }
        }
    }
    
    selectDate(date) {
        this.selectedDate = new Date(date);
        this.renderCalendar();
        this.updateDateInfo();
    }
    
    updateDateInfo() {
        const infoEl = this.modal.querySelector('#dateInfo');
        if (this.selectedDate) {
            infoEl.textContent = this.formatChineseDate(this.selectedDate);
        } else {
            infoEl.textContent = '请选择日期';
        }
    }
    
    formatYearMonth(date) {
        return `${date.getFullYear()}年${date.getMonth() + 1}月`;
    }
    
    formatChineseDate(date) {
        const year = date.getFullYear();
        const month = date.getMonth() + 1;
        const day = date.getDate();
        const weekdays = ['星期日', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六'];
        const weekday = weekdays[date.getDay()];
        
        return `${year}年${month}月${day}日 ${weekday}`;
    }
    
    formatDateISO(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    
    open() {
        this.isOpen = true;
        this.modal.style.display = 'block';
        this.renderCalendar();
        this.updateDateInfo();
        
        // 关闭其他日期选择器
        document.querySelectorAll('.date-picker-modal').forEach(modal => {
            if (modal !== this.modal) {
                modal.style.display = 'none';
            }
        });
    }
    
    close() {
        this.isOpen = false;
        this.modal.style.display = 'none';
    }
    
    confirm() {
        if (this.selectedDate) {
            this.input.value = this.formatChineseDate(this.selectedDate);
            this.input.dataset.isoDate = this.formatDateISO(this.selectedDate);
            
            // 触发change事件
            const event = new Event('change', { bubbles: true });
            this.input.dispatchEvent(event);
        }
        this.close();
    }
    
    clear() {
        this.selectedDate = null;
        this.input.value = '';
        this.input.dataset.isoDate = '';
        this.renderCalendar();
        this.updateDateInfo();
        
        // 触发change事件
        const event = new Event('change', { bubbles: true });
        this.input.dispatchEvent(event);
    }
    
    getISODate() {
        return this.input.dataset.isoDate || '';
    }
    
    setISODate(isoDate) {
        if (isoDate) {
            const date = new Date(isoDate + 'T00:00:00');
            if (!isNaN(date.getTime())) {
                this.selectedDate = date;
                this.input.value = this.formatChineseDate(date);
                this.input.dataset.isoDate = isoDate;
            }
        } else {
            this.clear();
        }
    }
}

// 发票管理应用
class InvoiceManager {
    constructor() {
        this.invoices = [];
        this.filteredInvoices = [];
        this.users = [];
        this.currentUser = null;
        this.pagination = {
            currentPage: 1,
            totalPages: 1,
            totalRecords: 0,
            limit: 5
        };
        this.searchParams = {};
        this.init();
    }

    async init() {
        await this.loadCurrentUser();
        this.bindEvents();
        this.initDatePickers();
        await this.loadUsersForFilter();
        await this.loadInvoiceData();
    }

    // 初始化日期选择器
    initDatePickers() {
        const startDatePicker = new ChineseDatePicker(document.getElementById('startDate'));
        const endDatePicker = new ChineseDatePicker(document.getElementById('endDate'));
        
        this.startDatePicker = startDatePicker;
        this.endDatePicker = endDatePicker;
    }

    // 加载当前用户信息
    async loadCurrentUser() {
        try {
            const response = await fetch('server/Auth.php?action=getCurrentUser');
            
            // 检查响应状态
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            // 获取响应文本，以便检查是否是JSON
            const responseText = await response.text();
            
            // 尝试解析JSON
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (parseError) {
                // 如果解析失败，可能是PHP错误或非JSON响应
                throw new Error('服务器响应格式错误');
            }
            
            if (result.success && result.user) {
                this.currentUser = result.user;
                
                // 检查密码是否为初始密码
                if (this.currentUser.is_initial_password) {
                    this.showForceChangePasswordModal();
                }
            } else {
                // 获取用户信息失败，检查是否是未登录
                if (result.message === '用户未登录' || result.message === '请先登录') {
                    // 如果是未登录，才跳转到登录页
                window.location.href = 'login.php';
                } else {
                    // 其他错误，显示提示但不跳转
                    this.showToast('获取用户信息失败: ' + (result.message || '未知错误'), 'error');
                }
            }
        } catch (error) {
            // 加载用户信息失败
            // 只有网络错误或其他严重错误时才跳转，避免循环刷新
            if (error.message.includes('HTTP 401') || error.message.includes('HTTP 403')) {
            window.location.href = 'login.php';
            } else {
                // 其他错误显示提示，不跳转
                this.showToast('获取用户信息失败，请刷新页面重试', 'error');
            }
        }
    }
    
    // 显示强制修改密码弹窗
    showForceChangePasswordModal() {
        // 创建强制修改密码弹窗
        const modalHtml = `
            <div id="forceChangePasswordModal" class="modal" style="display: flex; z-index: 9999;">
                <div class="modal-content" style="max-width: 500px;">
                    <div class="modal-header" style="background-color: #ff6b6b; color: white;">
                        <h3>安全提醒</h3>
                    </div>
                    <div class="modal-body" style="padding: 20px;">
                        <div style="text-align: center; margin-bottom: 20px;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #ff6b6b;"></i>
                            <h4 style="margin: 15px 0; color: #333;">密码安全提醒</h4>
                            <p style="color: #666; line-height: 1.6;">
                                检测到您的密码为初始密码 <strong>000000</strong>，存在安全风险。<br>
                                为了您的账户安全，请立即修改密码。
                            </p>
                        </div>
                        <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                            <strong>安全建议：</strong>
                            <ul style="margin: 10px 0; padding-left: 20px;">
                                <li>密码长度至少6位</li>
                                <li>包含字母、数字和特殊字符</li>
                                <li>不要使用简单密码或个人信息</li>
                                <li>定期更换密码</li>
                            </ul>
                        </div>
                    </div>
                    <div class="modal-footer" style="text-align: center; padding: 20px; border-top: 1px solid #eee;">
                        <button id="forceChangePasswordBtn" class="btn btn-primary" style="padding: 10px 30px;">
                            <i class="fas fa-key"></i> 立即修改密码
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        // 添加到页面
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // 绑定事件
        const modal = document.getElementById('forceChangePasswordModal');
        const changeBtn = document.getElementById('forceChangePasswordBtn');
        
        // 阻止关闭弹窗
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
        
        // 修改密码按钮事件
        changeBtn.addEventListener('click', () => {
            this.showPasswordModal();
            this.hideForceChangePasswordModal();
        });
        
        // 显示弹窗
        modal.style.display = 'flex';
    }
    
    // 隐藏强制修改密码弹窗
    hideForceChangePasswordModal() {
        const modal = document.getElementById('forceChangePasswordModal');
        if (modal) {
            modal.remove();
        }
    }

    // 加载用户列表（用于筛选下拉框）
    async loadUsersForFilter() {
        try {
            const response = await fetch('server/UserManager.php?action=getUsers');
            const result = await response.json();
            
            if (result.success) {
                this.users = result.users;
                this.populateUserFilter();
            } else {
                // 加载用户列表失败
            }
        } catch (error) {
            // 加载用户列表失败
            this.showToast('加载用户列表失败', 'error');
        }
    }

    // 填充用户筛选下拉框
    populateUserFilter() {
        const creatorSelect = document.getElementById('creator');
        if (!creatorSelect) return;
        
        creatorSelect.innerHTML = '<option value="">全部录入人</option>';
        
        this.users.forEach(user => {
            const option = document.createElement('option');
            option.value = user.F_id;
            option.textContent = user.F_realname || user.F_username;
            creatorSelect.appendChild(option);
        });
    }

    // 绑定事件
    bindEvents() {
        // 按钮事件
        document.getElementById('addInvoiceBtn')?.addEventListener('click', () => this.showModal());
        document.getElementById('refreshBtn')?.addEventListener('click', () => this.refreshData());
        document.getElementById('searchBtn')?.addEventListener('click', () => this.searchInvoices());
        document.getElementById('resetSearchBtn')?.addEventListener('click', () => this.resetSearch());
        document.getElementById('exportBtn')?.addEventListener('click', () => this.exportData());
        
        // 弹窗事件
        document.getElementById('closeModal')?.addEventListener('click', () => this.hideModal());
        document.getElementById('cancelBtn')?.addEventListener('click', () => this.hideModal());
        document.getElementById('saveBtn')?.addEventListener('click', () => this.saveInvoice());
        
        // 用户操作事件
        document.getElementById('profileCenterBtn')?.addEventListener('click', (e) => {
            e.preventDefault();
            this.showProfileCenterModal();
        });
        
        document.getElementById('clearCacheBtn')?.addEventListener('click', (e) => {
            e.preventDefault();
            this.clearCache();
        });
        
        document.getElementById('logoutBtn')?.addEventListener('click', (e) => {
            e.preventDefault();
            this.logout();
        });
        
        document.getElementById('closePasswordModal')?.addEventListener('click', () => this.hidePasswordModal());
        document.getElementById('cancelPasswordBtn')?.addEventListener('click', () => this.hidePasswordModal());
        document.getElementById('savePasswordBtn')?.addEventListener('click', () => this.changePassword());
        
        // 详情弹窗事件
        document.getElementById('closeDetailModal')?.addEventListener('click', () => this.hideDetailModal());
        document.getElementById('closeDetailBtn')?.addEventListener('click', () => this.hideDetailModal());
        document.getElementById('editDetailBtn')?.addEventListener('click', () => this.editFromDetail());
        
        // 用户管理事件
        document.getElementById('userManageBtn')?.addEventListener('click', () => this.showUserManageModal());
        document.getElementById('closeUserManageModal')?.addEventListener('click', () => this.hideUserManageModal());
        document.getElementById('closeUserManageBtn')?.addEventListener('click', () => this.hideUserManageModal());
        document.getElementById('addUserBtn')?.addEventListener('click', () => this.showUserFormModal());
        
        // 用户表单事件
        document.getElementById('closeUserFormModal')?.addEventListener('click', () => this.hideUserFormModal());
        document.getElementById('cancelUserFormBtn')?.addEventListener('click', () => this.hideUserFormModal());
        document.getElementById('saveUserBtn')?.addEventListener('click', () => this.saveUser());
        
        // 编辑发票事件
        document.getElementById('closeEditModal')?.addEventListener('click', () => this.hideEditModal());
        document.getElementById('cancelEditBtn')?.addEventListener('click', () => this.hideEditModal());
        document.getElementById('saveEditBtn')?.addEventListener('click', () => this.saveEditInvoice());
        
        // 修改用户密码事件
        document.getElementById('closeChangeUserPasswordModal')?.addEventListener('click', () => this.hideChangeUserPasswordModal());
        document.getElementById('cancelChangePasswordBtn')?.addEventListener('click', () => this.hideChangeUserPasswordModal());
        document.getElementById('saveChangePasswordBtn')?.addEventListener('click', () => this.saveChangeUserPassword());
        
        // 修改用户角色事件
        document.getElementById('closeChangeUserRoleModal')?.addEventListener('click', () => this.hideChangeUserRoleModal());
        document.getElementById('cancelChangeRoleBtn')?.addEventListener('click', () => this.hideChangeUserRoleModal());
        document.getElementById('saveChangeRoleBtn')?.addEventListener('click', () => this.saveChangeUserRole());
        
        // 二维码输入变化时解析
        document.getElementById('qrCode')?.addEventListener('input', (e) => this.parseQRCode(e.target.value));
        
        // 回车键自动保存
        document.getElementById('invUser')?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.saveInvoice();
            }
        });
        
        document.getElementById('invDoc')?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.saveInvoice();
            }
        });
        
        // 编辑表单回车键保存
        document.getElementById('editInvUser')?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('editInvDoc').focus();
            }
        });
        
        document.getElementById('editInvDoc')?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.saveEditInvoice();
            }
        });
        
        // 点击弹窗外部关闭
        document.getElementById('addInvoiceModal')?.addEventListener('click', (e) => {
            if (e.target === document.getElementById('addInvoiceModal')) {
                this.hideModal();
            }
        });
        
        document.getElementById('changePasswordModal')?.addEventListener('click', (e) => {
            if (e.target === document.getElementById('changePasswordModal')) {
                this.hidePasswordModal();
            }
        });
        
        document.getElementById('invoiceDetailModal')?.addEventListener('click', (e) => {
            if (e.target === document.getElementById('invoiceDetailModal')) {
                this.hideDetailModal();
            }
        });
        
        document.getElementById('userManageModal')?.addEventListener('click', (e) => {
            if (e.target === document.getElementById('userManageModal')) {
                this.hideUserManageModal();
            }
        });
        
        document.getElementById('userFormModal')?.addEventListener('click', (e) => {
            if (e.target === document.getElementById('userFormModal')) {
                this.hideUserFormModal();
            }
        });
        
        document.getElementById('changeUserPasswordModal')?.addEventListener('click', (e) => {
            if (e.target === document.getElementById('changeUserPasswordModal')) {
                this.hideChangeUserPasswordModal();
            }
        });
        
        document.getElementById('changeUserRoleModal')?.addEventListener('click', (e) => {
            if (e.target === document.getElementById('changeUserRoleModal')) {
                this.hideChangeUserRoleModal();
            }
        });
        
        document.getElementById('editInvoiceModal')?.addEventListener('click', (e) => {
            if (e.target === document.getElementById('editInvoiceModal')) {
                this.hideEditModal();
            }
        });
        
        // 系统配置事件
        document.getElementById('systemConfigBtn')?.addEventListener('click', () => this.showSystemConfigModal());
        document.getElementById('closeSystemConfigModal')?.addEventListener('click', () => this.hideSystemConfigModal());
        document.getElementById('cancelSystemConfigBtn')?.addEventListener('click', () => this.hideSystemConfigModal());
        document.getElementById('saveSystemConfigBtn')?.addEventListener('click', () => this.saveSystemConfig());
        document.getElementById('systemConfigModal')?.addEventListener('click', (e) => {
            if (e.target === document.getElementById('systemConfigModal')) {
                this.hideSystemConfigModal();
            }
        });
        
        // 系统文件上传事件
        document.getElementById('configSiteFavicon')?.addEventListener('change', (e) => {
            this.handleSystemFileUpload(e.target, 'favicon');
        });
        document.getElementById('configLoginLogo')?.addEventListener('change', (e) => {
            this.handleSystemFileUpload(e.target, 'login_logo');
        });
        document.getElementById('configMainLogo')?.addEventListener('change', (e) => {
            this.handleSystemFileUpload(e.target, 'main_logo');
        });
        
        // 个人中心事件
        document.getElementById('closeProfileCenterModal')?.addEventListener('click', () => this.hideProfileCenterModal());
        document.getElementById('cancelProfileCenterBtn')?.addEventListener('click', () => this.hideProfileCenterModal());
        document.getElementById('saveProfileCenterBtn')?.addEventListener('click', () => this.saveProfileCenter());
        document.getElementById('uploadAvatarBtn')?.addEventListener('click', () => document.getElementById('profileAvatarUpload').click());
        document.getElementById('profileAvatarUpload')?.addEventListener('change', (e) => this.handleAvatarUpload(e));
        document.getElementById('profileCenterModal')?.addEventListener('click', (e) => {
            if (e.target === document.getElementById('profileCenterModal')) {
                this.hideProfileCenterModal();
            }
        });

        // 分页事件
        document.getElementById('firstPageBtn')?.addEventListener('click', () => this.goToPage(1));
        document.getElementById('prevPageBtn')?.addEventListener('click', () => this.goToPage(this.pagination.currentPage - 1));
        document.getElementById('nextPageBtn')?.addEventListener('click', () => this.goToPage(this.pagination.currentPage + 1));
        document.getElementById('lastPageBtn')?.addEventListener('click', () => this.goToPage(this.pagination.totalPages));
        document.getElementById('pageSizeSelect')?.addEventListener('change', (e) => this.changePageSize(e.target.value));
        
        // 键盘快捷键
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.hideModal();
                this.hidePasswordModal();
                this.hideDetailModal();
                this.hideUserManageModal();
                this.hideUserFormModal();
                this.hideChangeUserPasswordModal();
                this.hideEditModal();
            }
        });
    }

    // 清除缓存
    async clearCache() {
        try {
            // 清除localStorage（但保留登录状态相关的，实际上登录状态在session中）
            // 浏览器保存的密码不会在localStorage中，所以可以安全清除
            const keysToKeep = []; // 可以保留特定的key，目前不需要
            
            if (localStorage.length > 0) {
                const keys = Object.keys(localStorage);
                keys.forEach(key => {
                    if (!keysToKeep.includes(key)) {
                        localStorage.removeItem(key);
                    }
                });
            }
            
            // 清除sessionStorage（不影响session cookie）
            sessionStorage.clear();
            
            // 清除Cache API缓存（如果支持）
            if ('caches' in window) {
                try {
                    const cacheNames = await caches.keys();
                    await Promise.all(
                        cacheNames.map(cacheName => caches.delete(cacheName))
                    );
                } catch (e) {
                    // Cache API清除失败不影响其他操作
                }
            }
            
            // 显示成功提示
            this.showToast('缓存清除成功，页面即将刷新', 'success');
            
            // 延迟刷新页面
            setTimeout(() => {
                // 刷新页面，重新加载所有资源
                // localStorage和sessionStorage已清除，浏览器会重新获取最新资源
                window.location.reload();
            }, 800);
        } catch (error) {
            // 清除缓存出错
            this.showToast('清除缓存时发生错误', 'error');
        }
    }

    // 退出登录
    async logout() {
        // 直接退出登录，无需确认
        
        try {
            const response = await fetch('server/Auth.php?action=logout');
            const result = await response.json();
            
            if (result.success) {
                this.showToast('退出登录成功', 'success');
                // 延迟一下让用户看到提示消息
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 1000);
            } else {
                this.showToast(result.message || '退出登录失败', 'error');
            }
        } catch (error) {
            // 退出登录出错
            this.showToast('网络错误，请重试', 'error');
        }
    }

    // 显示新增发票弹窗
    showModal() {
        document.getElementById('addInvoiceModal').style.display = 'flex';
        document.getElementById('qrCode').focus();
    }

    // 隐藏新增发票弹窗
    hideModal() {
        document.getElementById('addInvoiceModal').style.display = 'none';
        document.getElementById('invoiceForm').reset();
        this.clearParsedFields();
    }

    // 显示修改密码弹窗
    showPasswordModal() {
        document.getElementById('changePasswordModal').style.display = 'flex';
        document.getElementById('currentPassword').focus();
    }

    // 隐藏修改密码弹窗
    hidePasswordModal() {
        document.getElementById('changePasswordModal').style.display = 'none';
        document.getElementById('passwordForm').reset();
    }

    // 清空解析的字段
    clearParsedFields() {
        document.getElementById('invCode').value = '';
        document.getElementById('invNum').value = '';
        document.getElementById('invDate').value = '';
        document.getElementById('invMoney').value = '';
    }

    // 解析二维码
    parseQRCode(qrValue) {
        if (!qrValue) {
            this.clearParsedFields();
            return;
        }

        try {
            // 分割二维码内容
            const parts = qrValue.split(',');

            // 检查二维码格式
            if (parts.length < 6) {
                this.showToast('二维码格式不正确，请检查', 'error');
                return;
            }

            /*----- 解析字符串部分 -----*/
            const part2 = (parts[2] || '').trim();
            const part3 = (parts[3] || '').trim();
            const strPart = part2 ? `${part2}+${part3}` : part3;

            // 处理发票代码和号码
            let [F_inv_num, F_inv_code] = strPart.includes('+')
                ? strPart.split('+')
                : [strPart, strPart];

            /*----- 处理发票日期 -----*/
            let F_inv_date = (parts[5] || '').trim();
            if (F_inv_date) {
                // 统一转换为YYYY-MM-DD格式
                if (/^\d{8}$/.test(F_inv_date)) {
                    F_inv_date = `${F_inv_date.slice(0, 4)}-${F_inv_date.slice(4, 6)}-${F_inv_date.slice(6, 8)}`;
                } else if (!/^\d{4}-\d{2}-\d{2}$/.test(F_inv_date)) {
                    F_inv_date = ''; // 无效格式清空
                }
            }

            /*----- 处理发票金额 -----*/
            const F_inv_money = (parts[4] || '').trim();

            // 更新表单字段
            document.getElementById('invCode').value = F_inv_code;
            document.getElementById('invNum').value = F_inv_num;
            document.getElementById('invDate').value = F_inv_date;
            document.getElementById('invMoney').value = F_inv_money ? `¥${parseFloat(F_inv_money).toFixed(2)}` : '';

        } catch (error) {
            // 解析二维码出错
            this.showToast('二维码解析失败，请检查格式', 'error');
        }
    }

    // 保存发票
    async saveInvoice() {
        const qrValue = document.getElementById('qrCode').value.trim();
        const invUser = document.getElementById('invUser').value.trim();
        const invDoc = document.getElementById('invDoc').value.trim();
        const invCode = document.getElementById('invCode').value.trim();
        const invNum = document.getElementById('invNum').value.trim();
        
        if (!qrValue) {
            this.showToast('请输入发票二维码内容', 'error');
            return;
        }
        
        if (!invCode || !invNum) {
            this.showToast('请检查二维码格式，无法解析发票信息', 'error');
            return;
        }
        
        // 验证必输项
        if (!invUser) {
            this.showToast('凭证使用人为必输项，请填写', 'error');
            document.getElementById('invUser').focus();
            return;
        }
        
        if (!invDoc) {
            this.showToast('凭证号为必输项，请填写', 'error');
            document.getElementById('invDoc').focus();
            return;
        }
        
        // 准备保存数据
        const saveBtn = document.getElementById('saveBtn');
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 保存中...';
        saveBtn.disabled = true;
        
        try {
            // 发送到后端处理
            const formData = new FormData();
            formData.append('qr_code', qrValue);
            formData.append('inv_user', invUser);
            formData.append('inv_doc', invDoc);
            formData.append('csrf_token', document.getElementById('csrf_token').value);
            
            const response = await fetch('server/process_invoice.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                if (result.duplicate) {
                    let message = '该发票已存在，请勿重复录入！';
                    
                    if (result.existing_record) {
                        const record = result.existing_record;
                        if (!record.F_inv_user && !record.F_inv_doc) {
                            message = '该发票已报销，请仔细查验！';
                        } else if (!record.F_inv_user) {
                            message = `该发票已在 ${record.F_inv_doc} 凭证中报销，请仔细查验！`;
                        } else if (!record.F_inv_doc) {
                            message = `该发票已由 ${record.F_inv_user} 报销，请仔细查验！`;
                        } else {
                            message = `该发票已由 ${record.F_inv_user} 在 ${record.F_inv_doc} 凭证中报销，请仔细查验！`;
                        }
                    }
                    
                    this.showToast(message, 'error');
                } else {
                    this.showToast('发票保存成功！', 'success');
                    // 不关闭弹窗，清空表单并重新聚焦
                    document.getElementById('invoiceForm').reset();
                    this.clearParsedFields();
                    document.getElementById('qrCode').focus();
                    // 重新加载数据
                    this.loadInvoiceData();
                }
            } else {
                this.showToast(result.message || '保存失败，请重试', 'error');
            }
        } catch (error) {
            // 保存发票出错
            this.showToast('网络错误，请检查连接后重试', 'error');
        } finally {
            saveBtn.innerHTML = '保存发票';
            saveBtn.disabled = false;
        }
    }

    // 修改密码
    async changePassword() {
        const currentPassword = document.getElementById('currentPassword').value;
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        const saveBtn = document.getElementById('savePasswordBtn');
        
        if (!currentPassword || !newPassword || !confirmPassword) {
            this.showToast('请填写所有密码字段', 'error');
            return;
        }
        
        if (newPassword.length < 6) {
            this.showToast('新密码长度至少6位', 'error');
            return;
        }
        
        if (newPassword !== confirmPassword) {
            this.showToast('新密码和确认密码不一致', 'error');
            return;
        }
        
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 修改中...';
        saveBtn.disabled = true;
        
        try {
            const formData = new FormData();
            formData.append('current_password', currentPassword);
            formData.append('new_password', newPassword);
            
            const response = await fetch('server/UserManager.php?action=changePassword', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showToast('密码修改成功', 'success');
                this.hidePasswordModal();
            } else {
                this.showToast(result.message || '密码修改失败', 'error');
            }
        } catch (error) {
            console.error('修改密码出错:', error);
            this.showToast('网络错误，请重试', 'error');
        } finally {
            saveBtn.innerHTML = '修改密码';
            saveBtn.disabled = false;
        }
    }

    // 加载发票数据 - 从数据库获取真实数据
    async loadInvoiceData(page = 1) {
        const loadingTable = document.getElementById('loadingTable');
        const emptyState = document.getElementById('emptyState');
        const invoiceTableBody = document.getElementById('invoiceTableBody');
        
        if (!loadingTable || !emptyState || !invoiceTableBody) return;
        
        loadingTable.classList.add('show');
        emptyState.classList.remove('show');
        invoiceTableBody.innerHTML = '';
        
        try {
            // 构建查询参数
            const params = new URLSearchParams({
                page: page,
                limit: this.pagination.limit,
                ...this.searchParams
            });
            
            // 从数据库获取真实发票数据
            const response = await fetch(`server/get_invoices.php?${params}`);
            
            // 检查响应状态
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            // 获取响应文本，以便检查是否是JSON
            const responseText = await response.text();
            
            // 尝试解析JSON
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (parseError) {
                // 如果解析失败，可能是PHP错误或非JSON响应
                throw new Error('服务器响应格式错误，请检查服务器日志');
            }
            
            if (result.success) {
                this.invoices = result.invoices || [];
                this.pagination = result.pagination || {
                    currentPage: 1,
                    totalPages: 1,
                    totalRecords: 0,
                    limit: this.pagination.limit
                };
                this.renderInvoiceTable();
                this.renderPagination();
            } else {
                // 加载发票数据失败
                const errorMessage = result.message || '加载发票数据失败';
                this.showToast(errorMessage, 'error');
                // 显示空状态
                emptyState.classList.add('show');
            }
        } catch (error) {
            // 加载数据出错
            const errorMessage = error.message || '加载数据失败，请刷新重试';
            this.showToast(errorMessage, 'error');
            // 显示空状态
            emptyState.classList.add('show');
        } finally {
            loadingTable.classList.remove('show');
        }
    }

    // 格式化开票日期为统一格式 YYYY-MM-DD
    formatInvoiceDate(dateString) {
        if (!dateString || dateString === '-') {
            return '-';
        }
        
        // 处理 2025/11/18 格式，转换为 2025-11-18
        if (typeof dateString === 'string') {
            // 将 / 替换为 -
            dateString = dateString.replace(/\//g, '-');
            
            // 验证日期格式 YYYY-MM-DD
            const datePattern = /^(\d{4})-(\d{1,2})-(\d{1,2})$/;
            const match = dateString.match(datePattern);
            
            if (match) {
                // 确保月份和日期是两位数
                const year = match[1];
                const month = match[2].padStart(2, '0');
                const day = match[3].padStart(2, '0');
                return `${year}-${month}-${day}`;
            }
        }
        
        // 如果格式不正确，返回原值
        return dateString;
    }

    // 渲染发票表格
    renderInvoiceTable() {
        const invoiceTableBody = document.getElementById('invoiceTableBody');
        const emptyState = document.getElementById('emptyState');
        const tableCount = document.getElementById('tableCount');
        
        if (!invoiceTableBody || !emptyState || !tableCount) return;
        
        invoiceTableBody.innerHTML = '';
        
        if (this.invoices.length === 0) {
            emptyState.classList.add('show');
            tableCount.textContent = `共 ${this.pagination.totalRecords} 条记录`;
            document.getElementById('paginationContainer').style.display = 'none';
            return;
        }
        
        this.invoices.forEach(invoice => {
            const row = document.createElement('tr');
            
            let statusClass = 'status-new';
            let statusText = '新增';
            
            // 查找录入人名称 - 优先使用发票数据中的用户信息，如果不存在则使用本地用户列表
            let creatorName = '未知';
            if (invoice.F_realname || invoice.F_username) {
                // 使用发票数据中直接包含的用户信息
                creatorName = invoice.F_realname || invoice.F_username;
            } else if (this.users && this.users.length > 0) {
                // 使用本地用户列表查找
                const creator = this.users.find(u => u.F_id == invoice.F_creator_id);
                creatorName = creator ? (creator.F_realname || creator.F_username) : '未知';
            }
            
            // 格式化开票日期
            const formattedDate = this.formatInvoiceDate(invoice.F_inv_date);
            
            row.innerHTML = `
                <td>${invoice.F_inv_code}</td>
                <td>${invoice.F_inv_num}</td>
                <td>${formattedDate}</td>
                <td>¥${parseFloat(invoice.F_inv_money).toFixed(2)}</td>
                <td>${invoice.F_inv_user || '-'}</td>
                <td>${invoice.F_inv_doc || '-'}</td>
                <td>${creatorName}</td>
                <td>${invoice.F_CreatorTime}</td>
                <td><span class=\"status ${statusClass}\">${statusText}</span></td>
                <td>
                    <div class=\"action-icons\">
                        <i class=\"fas fa-edit\" title=\"编辑\" onclick=\"invoiceManager.editInvoice(${invoice.F_Id})\"></i>
                        <i class=\"fas fa-trash\" title=\"删除\" onclick=\"invoiceManager.deleteInvoice(${invoice.F_Id})\"></i>
                        <i class=\"fas fa-eye\" title=\"查看详情\" onclick=\"invoiceManager.viewInvoice(${invoice.F_Id})\"></i>
                    </div>
                </td>
            `;
            
            invoiceTableBody.appendChild(row);
        });
        
        tableCount.textContent = `共 ${this.pagination.totalRecords} 条记录`;
        emptyState.classList.remove('show');
        document.getElementById('paginationContainer').style.display = 'flex';
    }

    // 搜索发票
    searchInvoices() {
        const invoiceNumber = document.getElementById('invoiceNumber')?.value.trim() || '';
        const invoiceUser = document.getElementById('invoiceUser')?.value.trim() || '';
        const creator = document.getElementById('creator')?.value || '';
        const startDate = this.startDatePicker?.getISODate() || '';
        const endDate = this.endDatePicker?.getISODate() || '';
        
        // 保存搜索参数
        this.searchParams = {
            invoiceNumber,
            invoiceUser,
            creator,
            startDate,
            endDate
        };
        
        // 重新加载数据，回到第一页
        this.loadInvoiceData(1);
    }

    // 重置搜索
    resetSearch() {
        const invoiceNumber = document.getElementById('invoiceNumber');
        const invoiceUser = document.getElementById('invoiceUser');
        const creator = document.getElementById('creator');
        
        if (invoiceNumber) invoiceNumber.value = '';
        if (invoiceUser) invoiceUser.value = '';
        if (creator) creator.value = '';
        
        // 清空日期选择器
        if (this.startDatePicker) this.startDatePicker.clear();
        if (this.endDatePicker) this.endDatePicker.clear();
        
        // 清空搜索参数
        this.searchParams = {};
        
        // 重新加载数据，回到第一页
        this.loadInvoiceData(1);
        this.showToast('搜索条件已重置', 'success');
    }

    // 刷新数据
    refreshData() {
        this.loadInvoiceData(this.pagination.currentPage);
        this.showToast('数据已刷新', 'success');
    }

    // 导出数据
    exportData() {
        try {
            // 构建导出参数
            const params = new URLSearchParams({
                type: 'csv', // 默认导出CSV格式
                ...this.searchParams
            });
            
            // 创建导出链接并触发下载
            const exportUrl = `server/export_invoices.php?${params}`;
            
            // 默认导出CSV格式
            params.set('type', 'csv');
            
            // 创建隐藏的下载链接
            const link = document.createElement('a');
            link.href = `server/export_invoices.php?${params}`;
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            this.showToast('正在导出CSV格式文件...', 'success');
        } catch (error) {
            // 导出失败
            this.showToast('导出失败，请重试', 'error');
        }
    }

    // 编辑发票
    async editInvoice(id) {
        try {
            const response = await fetch(`server/get_invoice_detail.php?id=${id}`);
            const result = await response.json();
            
            if (result.success) {
                this.showEditModal(result.invoice);
            } else {
                this.showToast(result.message || '获取发票信息失败', 'error');
            }
        } catch (error) {
            // 获取发票信息出错
            this.showToast('网络错误，请重试', 'error');
        }
    }

    // 删除发票
    async deleteInvoice(id) {
        if (confirm('确定要删除这条发票记录吗？')) {
            try {
                const response = await fetch('server/delete_invoice.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `invoice_id=${id}&csrf_token=${document.getElementById('csrf_token').value}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    this.showToast('发票记录已删除', 'success');
                    this.loadInvoiceData();
                } else {
                    this.showToast(result.message || '删除失败', 'error');
                }
            } catch (error) {
                // 删除发票出错
                this.showToast('网络错误，请重试', 'error');
            }
        }
    }

    // 查看发票详情
    async viewInvoice(id) {
        try {
            const response = await fetch(`server/get_invoice_detail.php?id=${id}`);
            const result = await response.json();
            
            if (result.success) {
                this.showDetailModal(result.invoice);
            } else {
                this.showToast(result.message || '获取发票详情失败', 'error');
            }
        } catch (error) {
            // 获取发票详情出错
            this.showToast('网络错误，请重试', 'error');
        }
    }

    // 渲染分页控件
    renderPagination() {
        const paginationContainer = document.getElementById('paginationContainer');
        const paginationInfo = document.getElementById('paginationInfo');
        const pageNumbers = document.getElementById('pageNumbers');
        const firstPageBtn = document.getElementById('firstPageBtn');
        const prevPageBtn = document.getElementById('prevPageBtn');
        const nextPageBtn = document.getElementById('nextPageBtn');
        const lastPageBtn = document.getElementById('lastPageBtn');
        const pageSizeSelect = document.getElementById('pageSizeSelect');
        
        if (!paginationContainer || this.pagination.totalPages <= 1) {
            paginationContainer.style.display = 'none';
            return;
        }
        
        paginationContainer.style.display = 'flex';
        
        // 更新分页信息
        const start = (this.pagination.currentPage - 1) * this.pagination.limit + 1;
        const end = Math.min(this.pagination.currentPage * this.pagination.limit, this.pagination.totalRecords);
        paginationInfo.textContent = `显示第 ${start}-${end} 条，共 ${this.pagination.totalRecords} 条记录`;
        
        // 更新按钮状态
        firstPageBtn.disabled = this.pagination.currentPage === 1;
        prevPageBtn.disabled = this.pagination.currentPage === 1;
        nextPageBtn.disabled = this.pagination.currentPage === this.pagination.totalPages;
        lastPageBtn.disabled = this.pagination.currentPage === this.pagination.totalPages;
        
        // 更新每页显示数量
        pageSizeSelect.value = this.pagination.limit;
        
        // 生成页码
        pageNumbers.innerHTML = '';
        const maxVisiblePages = 5;
        const startPage = Math.max(1, this.pagination.currentPage - Math.floor(maxVisiblePages / 2));
        const endPage = Math.min(this.pagination.totalPages, startPage + maxVisiblePages - 1);
        
        // 如果当前页不在中间，调整起始页
        const adjustedStartPage = Math.max(1, endPage - maxVisiblePages + 1);
        
        for (let i = adjustedStartPage; i <= endPage; i++) {
            const pageBtn = document.createElement('button');
            pageBtn.className = `page-number ${i === this.pagination.currentPage ? 'active' : ''}`;
            pageBtn.textContent = i;
            pageBtn.onclick = () => this.goToPage(i);
            pageNumbers.appendChild(pageBtn);
        }
    }
    
    // 跳转到指定页面
    goToPage(page) {
        if (page < 1 || page > this.pagination.totalPages || page === this.pagination.currentPage) {
            return;
        }
        this.loadInvoiceData(page);
    }
    
    // 改变每页显示数量
    changePageSize(limit) {
        this.pagination.limit = parseInt(limit);
        this.loadInvoiceData(1);
    }

    // 显示详情弹窗
    showDetailModal(invoice) {
        // 填充详情数据
        document.getElementById('detailInvCode').textContent = invoice.F_inv_code || '-';
        document.getElementById('detailInvNum').textContent = invoice.F_inv_num || '-';
        // 格式化开票日期
        const formattedDate = this.formatInvoiceDate(invoice.F_inv_date);
        document.getElementById('detailInvDate').textContent = formattedDate;
        document.getElementById('detailInvMoney').textContent = invoice.F_inv_money ? `¥${parseFloat(invoice.F_inv_money).toFixed(2)}` : '-';
        document.getElementById('detailInvUser').textContent = invoice.F_inv_user || '-';
        document.getElementById('detailInvDoc').textContent = invoice.F_inv_doc || '-';
        
        const creatorName = invoice.F_realname || invoice.F_username || '未知';
        document.getElementById('detailCreator').textContent = creatorName;
        document.getElementById('detailCreateTime').textContent = invoice.F_CreatorTime || '-';
        document.getElementById('detailQRCode').value = invoice.F_inv_qr || '';
        
        // 显示弹窗
        document.getElementById('invoiceDetailModal').style.display = 'flex';
        
        // 保存当前发票ID，用于编辑功能
        this.currentDetailId = invoice.F_Id;
    }
    
    // 隐藏详情弹窗
    hideDetailModal() {
        document.getElementById('invoiceDetailModal').style.display = 'none';
        this.currentDetailId = null;
    }
    
    // 从详情弹窗编辑
    editFromDetail() {
        if (this.currentDetailId) {
            this.hideDetailModal();
            this.editInvoice(this.currentDetailId);
        }
    }
    
    // 复制二维码内容
    copyQRCode() {
        const qrCodeElement = document.getElementById('detailQRCode');
        if (!qrCodeElement || !qrCodeElement.value) {
            this.showToast('二维码内容为空', 'warning');
            return;
        }
        
        try {
            // 复制到剪贴板
            qrCodeElement.select();
            document.execCommand('copy');
            this.showToast('二维码内容已复制到剪贴板', 'success');
        } catch (error) {
            // 备用方案：使用现代API
            navigator.clipboard.writeText(qrCodeElement.value).then(() => {
                this.showToast('二维码内容已复制到剪贴板', 'success');
            }).catch(() => {
                this.showToast('复制失败，请手动复制', 'error');
            });
        }
    }

    // 显示用户管理弹窗
    showUserManageModal() {
        document.getElementById('userManageModal').style.display = 'flex';
        this.loadUsers();
    }
    
    // 隐藏用户管理弹窗
    hideUserManageModal() {
        document.getElementById('userManageModal').style.display = 'none';
    }
    
    // 加载用户列表
    async loadUsers() {
        const loading = document.getElementById('userLoading');
        const empty = document.getElementById('userEmpty');
        const tbody = document.getElementById('userTableBody');
        
        loading.style.display = 'block';
        empty.style.display = 'none';
        tbody.innerHTML = '';
        
        try {
            const response = await fetch('server/UserManager.php?action=getUsers');
            const result = await response.json();
            
            if (result.success) {
                this.users = result.users;
                this.renderUserTable();
            } else {
                this.showToast(result.message || '加载用户列表失败', 'error');
            }
        } catch (error) {
            // 加载用户列表出错
            this.showToast('网络错误，请重试', 'error');
        } finally {
            loading.style.display = 'none';
        }
    }
    
    // 渲染用户表格
    renderUserTable() {
        const tbody = document.getElementById('userTableBody');
        const empty = document.getElementById('userEmpty');
        
        tbody.innerHTML = '';
        
        if (this.users.length === 0) {
            empty.style.display = 'block';
            return;
        }
        
        this.users.forEach(user => {
            const row = document.createElement('tr');
            
            const statusClass = user.F_status === 1 ? 'active' : 'inactive';
            const statusText = user.F_status === 1 ? '激活' : '禁用';
            
            const roleClass = user.F_role === 'admin' ? 'admin' : 'user';
            const roleText = user.F_role === 'admin' ? '管理员' : '普通用户';
            
            row.innerHTML = `
                <td>${user.F_id}</td>
                <td>${user.F_username}</td>
                <td>${user.F_realname}</td>
                <td>
                    <span class="user-role ${roleClass}">${roleText}</span>
                    <div class="user-action-icons">
                        <i class="fas fa-user-cog" title="修改角色" onclick="invoiceManager.showChangeUserRoleModal(${user.F_id}, '${user.F_username}', '${user.F_role}')"></i>
                    </div>
                </td>
                <td>
                    <span class="user-status ${statusClass}">${statusText}</span>
                    <div class="user-action-icons">
                        <i class="fas fa-power-off" title="切换状态" onclick="invoiceManager.toggleUserStatus(${user.F_id}, '${user.F_username}', ${user.F_status})"></i>
                    </div>
                </td>
                <td>${user.F_create_time}</td>
                <td>
                    <div class="user-action-icons">
                        <i class="fas fa-key" title="修改密码" onclick="invoiceManager.showChangeUserPasswordModal(${user.F_id}, '${user.F_username}')"></i>
                        <i class="fas fa-trash delete" title="删除用户" onclick="invoiceManager.deleteUser(${user.F_id}, '${user.F_username}')"></i>
                    </div>
                </td>
            `;
            
            tbody.appendChild(row);
        });
        
        empty.style.display = 'none';
    }
    
    // 显示用户表单弹窗
    showUserFormModal(userId = null) {
        const modal = document.getElementById('userFormModal');
        const title = document.getElementById('userFormTitle');
        const passwordRequired = document.getElementById('passwordRequired');
        const passwordField = document.getElementById('userPassword');
        
        if (userId) {
            // 编辑模式
            title.innerHTML = '<i class="fas fa-user-edit"></i> 编辑用户';
            passwordRequired.style.display = 'none';
            passwordField.required = false;
            passwordField.placeholder = '留空表示不修改密码';
            // 这里可以加载用户数据进行填充，暂时简化处理
        } else {
            // 新增模式
            title.innerHTML = '<i class="fas fa-user-plus"></i> 新增用户';
            passwordRequired.style.display = 'inline';
            passwordField.required = true;
            passwordField.placeholder = '请输入密码';
            document.getElementById('userForm').reset();
        }
        
        modal.style.display = 'flex';
        document.getElementById('userUsername').focus();
    }
    
    // 隐藏用户表单弹窗
    hideUserFormModal() {
        document.getElementById('userFormModal').style.display = 'none';
        document.getElementById('userForm').reset();
    }
    
    // 保存用户
    async saveUser() {
        const username = document.getElementById('userUsername').value.trim();
        const realname = document.getElementById('userRealname').value.trim();
        const password = document.getElementById('userPassword').value;
        const role = document.getElementById('userRole').value;
        
        if (!username || !realname) {
            this.showToast('请填写必填项', 'error');
            return;
        }
        
        if (!password && !document.getElementById('userPassword').required) {
            // 编辑模式下密码为空表示不修改
        } else if (password && password.length < 6) {
            this.showToast('密码长度至少6位', 'error');
            return;
        } else if (!password && document.getElementById('userPassword').required) {
            this.showToast('请输入密码', 'error');
            return;
        }
        
        const saveBtn = document.getElementById('saveUserBtn');
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 保存中...';
        saveBtn.disabled = true;
        
        try {
            const formData = new FormData();
            formData.append('username', username);
            formData.append('realname', realname);
            formData.append('password', password);
            formData.append('role', role);
            
            const response = await fetch('server/UserManager.php?action=addUser', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showToast('用户保存成功', 'success');
                this.hideUserFormModal();
                this.loadUsers();
            } else {
                this.showToast(result.message || '保存失败', 'error');
            }
        } catch (error) {
            // 保存用户出错
            this.showToast('网络错误，请重试', 'error');
        } finally {
            saveBtn.innerHTML = '保存';
            saveBtn.disabled = false;
        }
    }
    
    // 删除用户
    async deleteUser(userId, username) {
        if (!confirm(`确定要删除用户 "${username}" 吗？`)) {
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('user_id', userId);
            
            const response = await fetch('server/UserManager.php?action=deleteUser', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showToast('用户删除成功', 'success');
                this.loadUsers();
            } else {
                this.showToast(result.message || '删除失败', 'error');
            }
        } catch (error) {
            // 删除用户出错
            this.showToast('网络错误，请重试', 'error');
        }
    }
    
    // 显示修改用户密码弹窗
    showChangeUserPasswordModal(userId, username) {
        document.getElementById('changePasswordUsername').value = username;
        document.getElementById('changePasswordUserId').value = userId;
        document.getElementById('newUserPassword').value = '';
        document.getElementById('confirmUserPassword').value = '';
        document.getElementById('changeUserPasswordModal').style.display = 'flex';
        document.getElementById('newUserPassword').focus();
    }
    
    // 隐藏修改用户密码弹窗
    hideChangeUserPasswordModal() {
        document.getElementById('changeUserPasswordModal').style.display = 'none';
        document.getElementById('changeUserPasswordForm').reset();
    }
    
    // 保存用户密码修改
    async saveChangeUserPassword() {
        const userId = document.getElementById('changePasswordUserId').value;
        const newPassword = document.getElementById('newUserPassword').value;
        const confirmPassword = document.getElementById('confirmUserPassword').value;
        
        if (!newPassword || !confirmPassword) {
            this.showToast('请填写所有密码字段', 'error');
            return;
        }
        
        if (newPassword.length < 6) {
            this.showToast('新密码长度至少6位', 'error');
            return;
        }
        
        if (newPassword !== confirmPassword) {
            this.showToast('新密码和确认密码不一致', 'error');
            return;
        }
        
        const saveBtn = document.getElementById('saveChangePasswordBtn');
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 修改中...';
        saveBtn.disabled = true;
        
        try {
            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('new_password', newPassword);
            
            const response = await fetch('server/UserManager.php?action=changeUserPassword', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showToast('密码修改成功', 'success');
                this.hideChangeUserPasswordModal();
            } else {
                this.showToast(result.message || '密码修改失败', 'error');
            }
        } catch (error) {
            console.error('修改密码出错:', error);
            this.showToast('网络错误，请重试', 'error');
        } finally {
            saveBtn.innerHTML = '修改密码';
            saveBtn.disabled = false;
        }
    }

    // 切换用户状态
    async toggleUserStatus(userId, username, currentStatus) {
        const newStatus = currentStatus === 1 ? 0 : 1;
        const actionText = newStatus === 1 ? '激活' : '禁用';
        
        // 直接切换状态，无需确认
        
        try {
            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('status', newStatus);
            
            const response = await fetch('server/UserManager.php?action=changeUserStatus', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showToast(`用户${actionText}成功`, 'success');
                this.loadUsers();
            } else {
                this.showToast(result.message || `${actionText}失败`, 'error');
            }
        } catch (error) {
            // 切换用户状态出错
            this.showToast('网络错误，请重试', 'error');
        }
    }

    // 显示修改用户角色弹窗
    showChangeUserRoleModal(userId, username, currentRole) {
        document.getElementById('changeRoleUsername').value = username;
        document.getElementById('changeRoleUserId').value = userId;
        document.getElementById('userRoleSelect').value = currentRole;
        document.getElementById('changeUserRoleModal').style.display = 'flex';
        document.getElementById('userRoleSelect').focus();
    }

    // 隐藏修改用户角色弹窗
    hideChangeUserRoleModal() {
        document.getElementById('changeUserRoleModal').style.display = 'none';
        document.getElementById('changeUserRoleForm').reset();
    }

    // 保存用户角色修改
    async saveChangeUserRole() {
        const userId = document.getElementById('changeRoleUserId').value;
        const newRole = document.getElementById('userRoleSelect').value;
        
        if (!newRole) {
            this.showToast('请选择用户角色', 'error');
            return;
        }
        
        const saveBtn = document.getElementById('saveChangeRoleBtn');
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 修改中...';
        saveBtn.disabled = true;
        
        try {
            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('role', newRole);
            
            const response = await fetch('server/UserManager.php?action=changeUserRole', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showToast('用户角色修改成功', 'success');
                this.hideChangeUserRoleModal();
                this.loadUsers();
            } else {
                this.showToast(result.message || '角色修改失败', 'error');
            }
        } catch (error) {
            // 修改用户角色出错
            this.showToast('网络错误，请重试', 'error');
        } finally {
            saveBtn.innerHTML = '修改角色';
            saveBtn.disabled = false;
        }
    }

    // 显示编辑发票弹窗
    showEditModal(invoice) {
        // 填充编辑表单数据
        document.getElementById('editInvoiceId').value = invoice.F_Id;
        document.getElementById('editInvCode').value = invoice.F_inv_code || '';
        document.getElementById('editInvNum').value = invoice.F_inv_num || '';
        // 格式化开票日期
        const formattedDate = this.formatInvoiceDate(invoice.F_inv_date);
        document.getElementById('editInvDate').value = formattedDate || '';
        document.getElementById('editInvMoney').value = invoice.F_inv_money ? `¥${parseFloat(invoice.F_inv_money).toFixed(2)}` : '';
        document.getElementById('editInvUser').value = invoice.F_inv_user || '';
        document.getElementById('editInvDoc').value = invoice.F_inv_doc || '';
        document.getElementById('editCreator').value = invoice.F_realname || invoice.F_username || '未知';
        document.getElementById('editCreateTime').value = invoice.F_CreatorTime || '';
        
        // 显示弹窗
        document.getElementById('editInvoiceModal').style.display = 'flex';
        
        // 聚焦到第一个可编辑字段
        setTimeout(() => {
            document.getElementById('editInvUser').focus();
        }, 100);
    }
    
    // 隐藏编辑发票弹窗
    hideEditModal() {
        document.getElementById('editInvoiceModal').style.display = 'none';
        document.getElementById('editInvoiceForm').reset();
    }
    
    // 保存编辑的发票
    async saveEditInvoice() {
        const invoiceId = document.getElementById('editInvoiceId').value;
        const invUser = document.getElementById('editInvUser').value.trim();
        const invDoc = document.getElementById('editInvDoc').value.trim();
        
        if (!invUser) {
            this.showToast('凭证使用人为必输项，请填写', 'error');
            document.getElementById('editInvUser').focus();
            return;
        }
        
        if (!invDoc) {
            this.showToast('凭证号为必输项，请填写', 'error');
            document.getElementById('editInvDoc').focus();
            return;
        }
        
        const saveBtn = document.getElementById('saveEditBtn');
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 保存中...';
        saveBtn.disabled = true;
        
        try {
            const formData = new FormData();
            formData.append('invoice_id', invoiceId);
            formData.append('inv_user', invUser);
            formData.append('inv_doc', invDoc);
            formData.append('csrf_token', document.getElementById('editCsrfToken').value);
            
            const response = await fetch('server/edit_invoice.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showToast('发票信息修改成功', 'success');
                this.hideEditModal();
                // 重新加载数据
                this.loadInvoiceData();
            } else {
                this.showToast(result.message || '修改失败，请重试', 'error');
            }
        } catch (error) {
            // 保存编辑发票出错
            this.showToast('网络错误，请检查连接后重试', 'error');
        } finally {
            saveBtn.innerHTML = '保存修改';
            saveBtn.disabled = false;
        }
    }

    // 显示消息提示
    showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        if (!toast) return;
        
        toast.textContent = message;
        toast.className = `toast ${type} show`;
        
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }
    
    // ==================== 系统配置管理 ====================
    
    // 显示系统配置弹窗
    async showSystemConfigModal() {
        const modal = document.getElementById('systemConfigModal');
        modal.style.display = 'flex';
        
        // 加载配置
        try {
            const response = await fetch('server/get_config.php');
            const result = await response.json();
            
            if (result.success && result.data) {
                // Favicon
                const faviconPath = result.data.site_favicon?.value || 'image/favicon.ico';
                document.getElementById('configSiteFaviconPath').value = faviconPath;
                const faviconPreview = document.getElementById('configSiteFaviconPreview');
                if (faviconPath) {
                    faviconPreview.src = faviconPath;
                    faviconPreview.style.display = 'block';
                }
                
                // 登录Logo
                const loginLogoPath = result.data.login_logo?.value || 'image/logo.png';
                document.getElementById('configLoginLogoPath').value = loginLogoPath;
                const loginLogoPreview = document.getElementById('configLoginLogoPreview');
                if (loginLogoPath) {
                    loginLogoPreview.src = loginLogoPath;
                    loginLogoPreview.style.display = 'block';
                }
                
                // 主页面Logo
                const mainLogoPath = result.data.main_logo?.value || 'image/logo.png';
                document.getElementById('configMainLogoPath').value = mainLogoPath;
                const mainLogoPreview = document.getElementById('configMainLogoPreview');
                if (mainLogoPath) {
                    mainLogoPreview.src = mainLogoPath;
                    mainLogoPreview.style.display = 'block';
                }
                
                // 文本配置
                document.getElementById('configLoginTitle').value = result.data.login_title?.value || '电子发票查重工具';
                document.getElementById('configLoginDescription').value = result.data.login_description?.value || '请登录您的账户';
                document.getElementById('configMainTitleText').value = result.data.main_title_text?.value || '电子发票查重工具';
            }
        } catch (error) {
            // 加载配置失败
            this.showToast('加载配置失败', 'error');
        }
    }
    
    // 处理系统文件上传（Favicon/Logo）
    async handleSystemFileUpload(fileInput, fileType) {
        const file = fileInput.files[0];
        if (!file) return false;
        
        // 验证文件类型
        const allowedTypes = {
            'favicon': ['image/x-icon', 'image/png', 'image/jpeg'],
            'login_logo': ['image/png', 'image/jpeg', 'image/gif', 'image/svg+xml'],
            'main_logo': ['image/png', 'image/jpeg', 'image/gif', 'image/svg+xml']
        };
        
        if (!allowedTypes[fileType] || !allowedTypes[fileType].includes(file.type)) {
            this.showToast('不支持的文件类型', 'error');
            fileInput.value = '';
            return false;
        }
        
        // 验证文件大小
        const maxSize = fileType === 'favicon' ? 500 * 1024 : 2 * 1024 * 1024;
        if (file.size > maxSize) {
            this.showToast(`文件大小超过限制（最大${Math.round(maxSize / 1024)}KB）`, 'error');
            fileInput.value = '';
            return false;
        }
        
        // 预览图片
        const reader = new FileReader();
        reader.onload = (e) => {
            const previewId = `config${fileType === 'favicon' ? 'SiteFavicon' : (fileType === 'login_logo' ? 'LoginLogo' : 'MainLogo')}Preview`;
            const preview = document.getElementById(previewId);
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
        };
        reader.readAsDataURL(file);
        
        // 上传文件
        const formData = new FormData();
        formData.append('system_file', file);
        formData.append('file_type', fileType === 'login_logo' ? 'login_logo' : (fileType === 'main_logo' ? 'main_logo' : 'favicon'));
        formData.append('csrf_token', document.getElementById('configCsrfToken').value);
        
        try {
            const response = await fetch('server/upload_system_file.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showToast('文件上传成功', 'success');
                // 更新隐藏的路径字段
                const pathFieldId = `config${fileType === 'favicon' ? 'SiteFavicon' : (fileType === 'login_logo' ? 'LoginLogo' : 'MainLogo')}Path`;
                document.getElementById(pathFieldId).value = result.file_path;
                return true;
            } else {
                this.showToast(result.message || '文件上传失败', 'error');
                fileInput.value = '';
                return false;
            }
        } catch (error) {
            // 上传文件出错
            this.showToast('网络错误，请重试', 'error');
            fileInput.value = '';
            return false;
        }
    }
    
    // 隐藏系统配置弹窗
    hideSystemConfigModal() {
        document.getElementById('systemConfigModal').style.display = 'none';
        document.getElementById('systemConfigForm').reset();
    }
    
    // 保存系统配置
    async saveSystemConfig() {
        const saveBtn = document.getElementById('saveSystemConfigBtn');
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 保存中...';
        saveBtn.disabled = true;
        
        try {
            // 保存配置（使用隐藏字段中的路径，文件已通过change事件上传）
            const formData = new FormData();
            formData.append('csrf_token', document.getElementById('configCsrfToken').value);
            formData.append('site_favicon', document.getElementById('configSiteFaviconPath').value || 'image/favicon.ico');
            formData.append('login_logo', document.getElementById('configLoginLogoPath').value || 'image/logo.png');
            formData.append('login_title', document.getElementById('configLoginTitle').value.trim());
            formData.append('login_description', document.getElementById('configLoginDescription').value.trim());
            formData.append('main_logo', document.getElementById('configMainLogoPath').value || 'image/logo.png');
            formData.append('main_title_text', document.getElementById('configMainTitleText').value.trim());
            
            const response = await fetch('server/save_config.php', {
                method: 'POST',
                body: formData
            });
            
            // 检查响应状态
            if (!response.ok) {
                throw new Error(`HTTP错误: ${response.status} ${response.statusText}`);
            }
            
            // 检查响应内容类型
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                // 如果不是JSON，尝试读取文本
                const text = await response.text();
                // 服务器返回非JSON响应
                throw new Error('服务器返回格式错误，请检查服务器日志');
            }
            
            const result = await response.json();
            
            if (result.success) {
                this.showToast('配置保存成功，请刷新页面查看效果', 'success');
                this.hideSystemConfigModal();
                // 延迟刷新页面以应用配置
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                this.showToast(result.message || '配置保存失败', 'error');
            }
        } catch (error) {
            // 保存配置出错
            let errorMessage = '网络错误，请重试';
            if (error.message) {
                errorMessage = error.message;
            }
            this.showToast(errorMessage, 'error');
        } finally {
            saveBtn.innerHTML = '保存配置';
            saveBtn.disabled = false;
        }
    }
    
    // ==================== 个人中心管理 ====================
    
    // 显示个人中心弹窗
    async showProfileCenterModal() {
        const modal = document.getElementById('profileCenterModal');
        modal.style.display = 'flex';
        
        // 加载用户信息
        try {
            const response = await fetch('server/Auth.php?action=getCurrentUser');
            const result = await response.json();
            
            if (result.success && result.user) {
                const user = result.user;
                document.getElementById('profileUsername').value = user.F_username || '';
                document.getElementById('profileRealname').value = user.F_realname || '';
                document.getElementById('profileUsername').setAttribute('data-original-realname', user.F_realname || '');
                document.getElementById('profileRole').value = user.F_role === 'admin' ? '管理员' : '普通用户';
                
                // 显示头像
                const avatarPath = user.F_avatar || 'image/logo.png';
                document.getElementById('profileAvatarPreview').src = avatarPath;
                
                // 清空密码字段
                document.getElementById('profileCurrentPassword').value = '';
                document.getElementById('profileNewPassword').value = '';
                document.getElementById('profileConfirmPassword').value = '';
            }
        } catch (error) {
            // 加载用户信息失败
            this.showToast('加载用户信息失败', 'error');
        }
    }
    
    // 隐藏个人中心弹窗
    hideProfileCenterModal() {
        document.getElementById('profileCenterModal').style.display = 'none';
        document.getElementById('profileCenterForm').reset();
    }
    
    // 处理头像上传
    async handleAvatarUpload(event) {
        const file = event.target.files[0];
        if (!file) return;
        
        // 验证文件类型
        if (!file.type.startsWith('image/')) {
            this.showToast('请选择图片文件', 'error');
            return;
        }
        
        // 验证文件大小（2MB）
        if (file.size > 2 * 1024 * 1024) {
            this.showToast('文件大小不能超过2MB', 'error');
            return;
        }
        
        // 预览图片
        const reader = new FileReader();
        reader.onload = (e) => {
            document.getElementById('profileAvatarPreview').src = e.target.result;
        };
        reader.readAsDataURL(file);
        
        // 上传头像
        const formData = new FormData();
        formData.append('avatar', file);
        formData.append('csrf_token', document.getElementById('profileCsrfToken').value);
        
        try {
            const response = await fetch('server/upload_avatar.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showToast('头像上传成功', 'success');
                // 更新头像显示
                document.getElementById('profileAvatarPreview').src = result.avatar_path;
                // 更新页面上的用户头像（如果存在）
                const userAvatar = document.querySelector('.user-btn img');
                if (userAvatar) {
                    userAvatar.src = result.avatar_path;
                }
            } else {
                this.showToast(result.message || '头像上传失败', 'error');
            }
        } catch (error) {
            // 上传头像出错
            this.showToast('网络错误，请重试', 'error');
        }
    }
    
    // 保存个人中心信息
    async saveProfileCenter() {
        const newRealname = document.getElementById('profileRealname').value.trim();
        const newPassword = document.getElementById('profileNewPassword').value.trim();
        const confirmPassword = document.getElementById('profileConfirmPassword').value.trim();
        const currentPassword = document.getElementById('profileCurrentPassword').value.trim();
        
        // 验证姓名
        if (!newRealname) {
            this.showToast('真实姓名不能为空', 'error');
            return;
        }
        
        if (newRealname.length > 50) {
            this.showToast('真实姓名长度不能超过50个字符', 'error');
            return;
        }
        
        // 如果填写了新密码，需要验证
        if (newPassword) {
            if (!currentPassword) {
                this.showToast('修改密码需要输入当前密码', 'error');
                return;
            }
            
            if (newPassword.length < 6) {
                this.showToast('新密码长度至少6位', 'error');
                return;
            }
            
            if (newPassword !== confirmPassword) {
                this.showToast('新密码和确认密码不一致', 'error');
                return;
            }
        }
        
        // 检查是否有修改
        const hasNameChange = newRealname !== (document.getElementById('profileUsername').getAttribute('data-original-realname') || '');
        const hasPasswordChange = !!newPassword;
        
        if (!hasNameChange && !hasPasswordChange) {
            this.showToast('未修改任何信息', 'info');
            this.hideProfileCenterModal();
            return;
        }
        
        const saveBtn = document.getElementById('saveProfileCenterBtn');
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 保存中...';
        saveBtn.disabled = true;
        
        try {
            let allSuccess = true;
            
            // 修改姓名
            if (hasNameChange) {
                const formData = new FormData();
                formData.append('realname', newRealname);
                formData.append('csrf_token', document.getElementById('profileCsrfToken').value);
                
                const response = await fetch('server/update_user_realname.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (!result.success) {
                    this.showToast(result.message || '姓名修改失败', 'error');
                    allSuccess = false;
                }
            }
            
            // 修改密码
            if (hasPasswordChange && allSuccess) {
                const formData = new FormData();
                formData.append('current_password', currentPassword);
                formData.append('new_password', newPassword);
                
                const response = await fetch('server/UserManager.php?action=changePassword', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (!result.success) {
                    this.showToast(result.message || '密码修改失败', 'error');
                    allSuccess = false;
                }
            }
            
            if (allSuccess) {
                let message = '';
                if (hasNameChange && hasPasswordChange) {
                    message = '姓名和密码修改成功';
                } else if (hasNameChange) {
                    message = '姓名修改成功';
                } else {
                    message = '密码修改成功';
                }
                this.showToast(message, 'success');
                
                // 更新显示的用户信息
                if (hasNameChange) {
                    const userBtn = document.querySelector('.user-btn');
                    if (userBtn) {
                        const nameSpan = userBtn.querySelector('.user-name');
                        if (nameSpan) {
                            nameSpan.textContent = newRealname;
                        }
                    }
                }
                
                this.hideProfileCenterModal();
                // 刷新页面以更新用户信息
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }
        } catch (error) {
            // 保存信息出错
            this.showToast('网络错误，请重试', 'error');
        } finally {
            saveBtn.innerHTML = '保存';
            saveBtn.disabled = false;
        }
    }
}

// 页面加载完成后初始化应用
let invoiceManager;

document.addEventListener('DOMContentLoaded', function() {
    invoiceManager = new InvoiceManager();
});

// 全局函数，用于HTML中的onclick事件
function refreshInvoiceData() {
    if (invoiceManager) {
        invoiceManager.refreshData();
    }
}

function addNewInvoice() {
    if (invoiceManager) {
        invoiceManager.showModal();
    }
}

function searchInvoices() {
    if (invoiceManager) {
        invoiceManager.searchInvoices();
    }
}

function resetSearch() {
    if (invoiceManager) {
        invoiceManager.resetSearch();
    }
}

function logoutUser() {
    if (invoiceManager) {
        invoiceManager.logout();
    }
}

// 用户管理相关功能

// 切换用户状态
async function toggleUserStatus(userId, username, currentStatus) {
    if (invoiceManager) {
        invoiceManager.toggleUserStatus(userId, username, currentStatus);
    }
}

// 显示修改用户角色弹窗
function showChangeUserRoleModal(userId, username, currentRole) {
    if (invoiceManager) {
        invoiceManager.showChangeUserRoleModal(userId, username, currentRole);
    }
}

// 保存用户角色修改
function saveChangeUserRole() {
    if (invoiceManager) {
        invoiceManager.saveChangeUserRole();
    }
}

// 隐藏修改用户角色弹窗
function hideChangeUserRoleModal() {
    if (invoiceManager) {
        invoiceManager.hideChangeUserRoleModal();
    }
}

// 用户管理相关功能

// 切换用户状态
async function toggleUserStatus(userId, username, currentStatus) {
    if (invoiceManager) {
        invoiceManager.toggleUserStatus(userId, username, currentStatus);
    }
}

// 显示修改用户角色弹窗
function showChangeUserRoleModal(userId, username, currentRole) {
    if (invoiceManager) {
        invoiceManager.showChangeUserRoleModal(userId, username, currentRole);
    }
}

// 保存用户角色修改
function saveChangeUserRole() {
    if (invoiceManager) {
        invoiceManager.saveChangeUserRole();
    }
}

// 隐藏修改用户角色弹窗
function hideChangeUserRoleModal() {
    if (invoiceManager) {
        invoiceManager.hideChangeUserRoleModal();
    }
}