// 系统日志管理类
class LogManager {
    constructor() {
        this.logs = [];
        this.pagination = {
            currentPage: 1,
            totalPages: 1,
            totalRecords: 0,
            limit: 5
        };
        this.searchParams = {};
        this.actions = [];
        this.statistics = {};
        this.init();
    }

    async init() {
        this.bindEvents();
        await this.loadLogActions();
        await this.loadStatistics();
        await this.loadLogData();
    }



    // 绑定事件
    bindEvents() {
        // 按钮事件
        document.getElementById('systemLogBtn')?.addEventListener('click', () => this.showSystemLogModal());
        document.getElementById('closeSystemLogModal')?.addEventListener('click', () => this.hideSystemLogModal());
        document.getElementById('closeSystemLogBtn')?.addEventListener('click', () => this.hideSystemLogModal());
        
        // 搜索事件
        document.getElementById('searchLogBtn')?.addEventListener('click', () => this.searchLogs());
        document.getElementById('resetLogFilterBtn')?.addEventListener('click', () => this.resetSearch());
        
        // 分页事件
        document.getElementById('logFirstPageBtn')?.addEventListener('click', () => this.goToPage(1));
        document.getElementById('logPrevPageBtn')?.addEventListener('click', () => this.goToPage(this.pagination.currentPage - 1));
        document.getElementById('logNextPageBtn')?.addEventListener('click', () => this.goToPage(this.pagination.currentPage + 1));
        document.getElementById('logLastPageBtn')?.addEventListener('click', () => this.goToPage(this.pagination.totalPages));
        document.getElementById('logPageSizeSelect')?.addEventListener('change', (e) => this.changePageSize(e.target.value));
        
        // 点击弹窗外部关闭
        document.getElementById('systemLogModal')?.addEventListener('click', (e) => {
            if (e.target === document.getElementById('systemLogModal')) {
                this.hideSystemLogModal();
            }
        });
    }

    // 显示系统日志弹窗
    async showSystemLogModal() {
        document.getElementById('systemLogModal').style.display = 'flex';
        await this.loadStatistics();
        await this.loadLogData();
    }

    // 隐藏系统日志弹窗
    hideSystemLogModal() {
        document.getElementById('systemLogModal').style.display = 'none';
    }

    // 加载日志操作类型
    async loadLogActions() {
        try {
            const response = await fetch('server/get_log_actions.php');
            const result = await response.json();
            
            if (result.success) {
                this.actions = result.data;
                this.populateActionFilter();
            } else {
                // 加载操作类型失败
            }
        } catch (error) {
            // 加载操作类型失败
        }
    }

    // 填充操作类型筛选下拉框
    populateActionFilter() {
        const actionSelect = document.getElementById('logAction');
        if (!actionSelect) return;
        
        actionSelect.innerHTML = '<option value="">全部操作</option>';
        
        this.actions.forEach(action => {
            const option = document.createElement('option');
            option.value = action;
            option.textContent = this.getActionDisplayName(action);
            actionSelect.appendChild(option);
        });
    }

    // 获取操作显示名称
    getActionDisplayName(action) {
        const actionNames = {
            'LOGIN': '登录',
            'LOGOUT': '退出',
            'INVOICE_CREATE': '新增发票',
            'INVOICE_DUPLICATE': '发票重复',
            'INVOICE_PARSE_ERROR': '解析错误',
            'INVOICE_SAVE_ERROR': '保存错误',
            'INVOICE_PROCESS_ERROR': '处理错误',
            'INVOICE_EDIT': '编辑发票',
            'INVOICE_DELETE': '删除发票',
            'USER_CREATE': '新增用户',
            'USER_EDIT': '编辑用户',
            'USER_DELETE': '删除用户',
            'USER_PASSWORD_CHANGE': '修改密码',
            'USER_ROLE_CHANGE': '修改角色',
            'USER_STATUS_TOGGLE': '切换状态'
        };
        
        return actionNames[action] || action;
    }

    // 加载统计信息
    async loadStatistics() {
        try {
            const response = await fetch('server/get_log_statistics.php');
            const result = await response.json();
            
            if (result.success) {
                this.statistics = result.data;
                this.updateStatisticsDisplay();
            } else {
                // 加载统计信息失败
            }
        } catch (error) {
            // 加载统计信息失败
        }
    }

    // 更新统计信息显示
    updateStatisticsDisplay() {
        document.getElementById('todayLogins').textContent = this.statistics.today_logins || 0;
        document.getElementById('weekLogins').textContent = this.statistics.week_logins || 0;
        document.getElementById('monthLogins').textContent = this.statistics.month_logins || 0;
        document.getElementById('activeUsers').textContent = this.statistics.active_users || 0;
        document.getElementById('failedLogins').textContent = this.statistics.failed_logins || 0;
    }

    // 加载日志数据
    async loadLogData(page = 1) {
        const loading = document.getElementById('logLoading');
        const empty = document.getElementById('logEmpty');
        const tbody = document.getElementById('logTableBody');
        
        if (!loading || !empty || !tbody) {
            // 表格元素未找到，静默返回
            return;
        }
        
        loading.style.display = 'block';
        empty.style.display = 'none';
        tbody.innerHTML = '';
        
        try {
            // 构建查询参数
            const params = new URLSearchParams({
                page: page,
                limit: this.pagination.limit,
                ...this.searchParams
            });
            
            const url = `server/get_logs.php?${params}`;
            
            const response = await fetch(url);
            
            // 检查响应状态
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                this.logs = result.data.logs || [];
                // 更新分页数据，保留logs属性
                this.pagination.totalRecords = result.data.total || 0;
                this.pagination.currentPage = result.data.page || 1;
                this.pagination.totalPages = result.data.total_pages || 0;
                this.pagination.limit = result.data.limit || 5;
                this.renderLogTable();
                this.renderLogPagination();
            } else {
                // 加载日志数据失败
                this.showToast('加载日志数据失败', 'error');
                // 加载失败时也要渲染空状态
                this.logs = [];
                this.renderLogTable();
                this.renderLogPagination();
            }
        } catch (error) {
            // 加载日志数据失败
            this.showToast('网络错误，请重试', 'error');
            // 异常时也要渲染空状态
            this.logs = [];
            this.renderLogTable();
            this.renderLogPagination();
        } finally {
            loading.style.display = 'none';
        }
    }

    // 渲染日志表格
    renderLogTable() {
        const tbody = document.getElementById('logTableBody');
        const empty = document.getElementById('logEmpty');
        
        if (!tbody || !empty) return;
        
        tbody.innerHTML = '';
        
        if (!this.logs || this.logs.length === 0) {
            empty.style.display = 'block';
            const paginationContainer = document.getElementById('logPaginationContainer');
            if (paginationContainer) {
                paginationContainer.style.display = 'none';
            }
            return;
        }
        
        this.logs.forEach(log => {
            const row = document.createElement('tr');
            
            const statusClass = log.F_status == 1 ? 'success' : 'failed';
            const statusText = log.F_status == 1 ? '成功' : '失败';
            
            row.innerHTML = `
                <td>${log.F_create_time}</td>
                <td>${log.F_username || '-'}</td>
                <td>${this.getActionDisplayName(log.F_action)}</td>
                <td title="${log.F_description || ''}">${(log.F_description || '-').substring(0, 50)}${(log.F_description || '').length > 50 ? '...' : ''}</td>
                <td>${log.F_ip_address || '-'}</td>
                <td><span class="log-status ${statusClass}">${statusText}</span></td>
                <td>
                    <span class="log-action" onclick="logManager.showLogDetail(${log.F_id})">详情</span>
                </td>
            `;
            
            tbody.appendChild(row);
        });
        
        empty.style.display = 'none';
    }

    // 渲染分页控件
    renderLogPagination() {
        const paginationContainer = document.getElementById('logPaginationContainer');
        const paginationInfo = document.getElementById('logPaginationInfo');
        const pageNumbers = document.getElementById('logPageNumbers');
        const firstPageBtn = document.getElementById('logFirstPageBtn');
        const prevPageBtn = document.getElementById('logPrevPageBtn');
        const nextPageBtn = document.getElementById('logNextPageBtn');
        const lastPageBtn = document.getElementById('logLastPageBtn');
        const pageSizeSelect = document.getElementById('logPageSizeSelect');
        
        if (!paginationContainer) {
            return;
        }
        // 如果没有数据，隐藏分页
        if (!this.pagination.totalRecords) {
            paginationContainer.style.display = 'none';
            return;
        }
        
        // 即使只有一页也显示分页控件，让用户可以调整每页显示数量
        paginationContainer.style.display = 'flex';
        
        // 更新分页信息，确保数字有效
        const currentPage = this.pagination.currentPage || 1;
        const limit = this.pagination.limit || 5;
        const totalRecords = this.pagination.totalRecords || 0;
        
        const start = (currentPage - 1) * limit + 1;
        const end = Math.min(currentPage * limit, totalRecords);
        
        if (paginationInfo) {
            paginationInfo.textContent = `显示第 ${start}-${end} 条，共 ${totalRecords} 条记录`;
        }
        
        // 更新按钮状态
        if (firstPageBtn) firstPageBtn.disabled = currentPage === 1;
        if (prevPageBtn) prevPageBtn.disabled = currentPage === 1;
        if (nextPageBtn) nextPageBtn.disabled = currentPage === this.pagination.totalPages;
        if (lastPageBtn) lastPageBtn.disabled = currentPage === this.pagination.totalPages;
        
        // 更新每页显示数量
        if (pageSizeSelect) pageSizeSelect.value = limit;
        
        // 生成页码
        if (pageNumbers) {
            pageNumbers.innerHTML = '';
            const maxVisiblePages = 5;
            const startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
            const endPage = Math.min(this.pagination.totalPages, startPage + maxVisiblePages - 1);
            
            // 如果当前页不在中间，调整起始页
            const adjustedStartPage = Math.max(1, endPage - maxVisiblePages + 1);
            
            for (let i = adjustedStartPage; i <= endPage; i++) {
                const pageBtn = document.createElement('button');
                pageBtn.className = `page-number ${i === currentPage ? 'active' : ''}`;
                pageBtn.textContent = i;
                pageBtn.onclick = () => this.goToPage(i);
                pageNumbers.appendChild(pageBtn);
            }
        }
    }

    // 跳转到指定页面
    goToPage(page) {
        if (page < 1 || page > this.pagination.totalPages || page === this.pagination.currentPage) {
            return;
        }
        this.loadLogData(page);
    }

    // 改变每页显示数量
    changePageSize(limit) {
        this.pagination.limit = parseInt(limit);
        this.loadLogData(1);
    }

    // 搜索日志
    searchLogs() {
        const action = document.getElementById('logAction')?.value || '';
        const username = document.getElementById('logUsername')?.value.trim() || '';
        const ipAddress = document.getElementById('logIpAddress')?.value.trim() || '';
        const status = document.getElementById('logStatus')?.value || '';
        
        // 只保存非空的搜索参数
        this.searchParams = {};
        if (action) this.searchParams.action = action;
        if (username) this.searchParams.username = username;
        if (ipAddress) this.searchParams.ip_address = ipAddress;
        if (status !== '') this.searchParams.status = status;
        
        // 重新加载数据，回到第一页
        this.loadLogData(1);
    }

    // 重置搜索
    resetSearch() {
        const logAction = document.getElementById('logAction');
        const logUsername = document.getElementById('logUsername');
        const logIpAddress = document.getElementById('logIpAddress');
        const logStatus = document.getElementById('logStatus');
        
        if (logAction) logAction.value = '';
        if (logUsername) logUsername.value = '';
        if (logIpAddress) logIpAddress.value = '';
        if (logStatus) logStatus.value = '';
        
        // 清空搜索参数
        this.searchParams = {};
        
        // 重新加载数据，回到第一页
        this.loadLogData(1);
        this.showToast('搜索条件已重置', 'success');
    }

    // 显示日志详情
    async showLogDetail(logId) {
        try {
            // 从当前日志列表中查找
            const log = this.logs.find(l => l.F_id == logId);
            if (!log) {
                this.showToast('日志记录不存在', 'error');
                return;
            }
            
            this.showLogDetailModal(log);
        } catch (error) {
            // 显示日志详情失败
            this.showToast('网络错误，请重试', 'error');
        }
    }

    // 显示日志详情弹窗
    showLogDetailModal(log) {
        // 创建详情弹窗
        const modalHtml = `
            <div id="logDetailModal" class="modal log-detail-modal" style="display: flex;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2><i class="fas fa-info-circle"></i> 日志详情</h2>
                        <button class="close-modal" id="closeLogDetailModal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="log-detail-section">
                            <h4>基本信息</h4>
                            <div class="log-detail-item">
                                <span class="log-detail-label">日志ID:</span>
                                <span class="log-detail-value">${log.F_id}</span>
                            </div>
                            <div class="log-detail-item">
                                <span class="log-detail-label">操作时间:</span>
                                <span class="log-detail-value">${log.F_create_time}</span>
                            </div>
                            <div class="log-detail-item">
                                <span class="log-detail-label">用户:</span>
                                <span class="log-detail-value">${log.F_username || '-'}</span>
                            </div>
                            <div class="log-detail-item">
                                <span class="log-detail-label">操作类型:</span>
                                <span class="log-detail-value">${this.getActionDisplayName(log.F_action)}</span>
                            </div>
                            <div class="log-detail-item">
                                <span class="log-detail-label">状态:</span>
                                <span class="log-detail-value ${log.F_status == 1 ? 'success' : 'failed'}">${log.F_status == 1 ? '成功' : '失败'}</span>
                            </div>
                        </div>
                        
                        <div class="log-detail-section">
                            <h4>操作描述</h4>
                            <div class="log-detail-item">
                                <span class="log-detail-value" style="text-align: left; max-width: 100%;">${log.F_description || '-'}</span>
                            </div>
                        </div>
                        
                        <div class="log-detail-section">
                            <h4>网络信息</h4>
                            <div class="log-detail-item">
                                <span class="log-detail-label">IP地址:</span>
                                <span class="log-detail-value">${log.F_ip_address || '-'}</span>
                            </div>
                            <div class="log-detail-item">
                                <span class="log-detail-label">目标类型:</span>
                                <span class="log-detail-value">${log.F_target_type || '-'}</span>
                            </div>
                            <div class="log-detail-item">
                                <span class="log-detail-label">目标ID:</span>
                                <span class="log-detail-value">${log.F_target_id || '-'}</span>
                            </div>
                        </div>
                        
                        ${log.F_error_message ? `
                        <div class="log-detail-section">
                            <h4>错误信息</h4>
                            <div class="log-detail-item">
                                <span class="log-detail-value failed" style="text-align: left; max-width: 100%;">${log.F_error_message}</span>
                            </div>
                        </div>
                        ` : ''}
                        
                        ${log.F_user_agent ? `
                        <div class="log-detail-section">
                            <h4>用户代理</h4>
                            <div class="log-detail-user-agent">${log.F_user_agent}</div>
                        </div>
                        ` : ''}
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline" id="closeLogDetailBtn">关闭</button>
                    </div>
                </div>
            </div>
        `;
        
        // 添加到页面
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // 绑定事件
        const modal = document.getElementById('logDetailModal');
        const closeBtn = document.getElementById('closeLogDetailModal');
        const closeFooterBtn = document.getElementById('closeLogDetailBtn');
        
        closeBtn.addEventListener('click', () => this.hideLogDetailModal());
        closeFooterBtn.addEventListener('click', () => this.hideLogDetailModal());
        
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                this.hideLogDetailModal();
            }
        });
        
        // 显示弹窗
        modal.style.display = 'flex';
    }

    // 隐藏日志详情弹窗
    hideLogDetailModal() {
        const modal = document.getElementById('logDetailModal');
        if (modal) {
            modal.remove();
        }
    }

    // 显示提示消息（复用主应用的方法）
    showToast(message, type = 'success') {
        if (window.invoiceManager && window.invoiceManager.showToast) {
            window.invoiceManager.showToast(message, type);
        } else {
            // 显示提示消息
        }
    }
}

// 创建日志管理器实例
let logManager;

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', () => {
    // 立即初始化，确保DOM元素已经存在
    logManager = new LogManager();
    // 将日志管理器实例暴露到全局
    window.logManager = logManager;
});