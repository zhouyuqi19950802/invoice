<?php
// 检查是否已安装
require_once 'server/InstallChecker.php';
if (!InstallChecker::isInstalled()) {
    InstallChecker::redirectToInstall();
}

require_once 'server/error_handler.php';
require_once 'server/Auth.php';
require_once 'server/SecurityConfig.php';
require_once 'server/ConfigManager.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = $auth->getCurrentUser();
// 确保用户信息存在，避免未定义错误
$username = isset($user['user']['F_username']) ? $user['user']['F_username'] : '';
$realname = isset($user['user']['F_realname']) ? $user['user']['F_realname'] : $username;
$role = isset($user['user']['F_role']) ? $user['user']['F_role'] : 'user';
$isAdmin = ($role === 'admin');

// 加载系统配置
$configManager = new ConfigManager();
$siteFavicon = $configManager->getConfig('site_favicon', 'image/favicon.ico');
$mainLogo = $configManager->getConfig('main_logo', 'image/logo.png');
$mainTitleText = $configManager->getConfig('main_title_text', '电子发票查重工具');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($mainTitleText); ?></title>
    <link rel="icon" href="<?php echo htmlspecialchars($siteFavicon); ?>" type="image/x-icon">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/log_styles.css">
    <link rel="stylesheet" href="css/resources/fontawesome/all.min.css">
</head>
<body>
    <div class="container">
        <!-- 头部 -->
        <header>
            <div class="logo">
                <img src="<?php echo htmlspecialchars($mainLogo); ?>" alt="<?php echo htmlspecialchars($mainTitleText); ?>" style="width: 48px; height: 48px;">
                <h1><?php echo htmlspecialchars($mainTitleText); ?></h1>
            </div>
            <div class="header-actions">
                <div class="actions">
                    <button class="btn btn-primary" id="addInvoiceBtn">
                        <i class="fas fa-plus"></i> 新增发票
                    </button>
                    <button class="btn btn-outline" id="refreshBtn">
                        <i class="fas fa-sync-alt"></i> 刷新数据
                    </button>
                    <?php if ($isAdmin): ?>
                    <button class="btn btn-outline" id="systemLogBtn">
                        <i class="fas fa-list-alt"></i> 系统日志
                    </button>
                    <button class="btn btn-outline" id="userManageBtn">
                        <i class="fas fa-users"></i> 用户管理
                    </button>
                    <button class="btn btn-outline" id="systemConfigBtn">
                        <i class="fas fa-cog"></i> 系统配置
                    </button>
                    <?php endif; ?>
                </div>
                <div class="user-info">
                    <div class="user-dropdown">
                        <button class="btn btn-outline user-btn">
                            <?php 
                            $avatarPath = isset($user['user']['F_avatar']) && !empty($user['user']['F_avatar']) && file_exists($user['user']['F_avatar']) 
                                ? htmlspecialchars($user['user']['F_avatar']) 
                                : 'image/logo.png';
                            ?>
                            <img src="<?php echo $avatarPath; ?>" alt="头像" style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover; margin-right: 5px;">
                            <span><?php echo htmlspecialchars($realname); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="user-menu">
                            <a href="#" id="profileCenterBtn"><i class="fas fa-user"></i> 个人中心</a>
                            <a href="#" id="clearCacheBtn"><i class="fas fa-trash-alt"></i> 清除缓存</a>
                            <a href="#" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> 退出登录</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- 搜索区域 -->
        <section class="search-section">
            <h2><i class="fas fa-search"></i> 发票查询</h2>
            <div class="search-filters">
                <div class="filter-group">
                    <label for="invoiceNumber">发票号码</label>
                    <input type="text" id="invoiceNumber" class="filter-input" placeholder="输入发票号码">
                </div>
                <div class="filter-group">
                    <label for="invoiceUser">发票使用人</label>
                    <input type="text" id="invoiceUser" class="filter-input" placeholder="输入使用人姓名">
                </div>
                <div class="filter-group">
                    <label for="creator">录入人</label>
                    <select id="creator" class="filter-input">
                        <option value="">全部录入人</option>
                        <!-- 将通过JS动态加载 -->
                    </select>
                </div>
                <div class="filter-group">
                    <label for="startDate">录入日期起</label>
                    <input type="text" id="startDate" class="filter-input date-picker" placeholder="点击选择日期" readonly>
                </div>
                <div class="filter-group">
                    <label for="endDate">录入日期止</label>
                    <input type="text" id="endDate" class="filter-input date-picker" placeholder="点击选择日期" readonly>
                </div>
            </div>
            <div class="search-actions">
                <button class="btn btn-outline" id="resetSearchBtn">
                    <i class="fas fa-undo"></i> 重置
                </button>
                <button class="btn btn-primary" id="searchBtn">
                    <i class="fas fa-search"></i> 查询
                </button>
            </div>
        </section>

        <!-- 表格区域 -->
        <section class="table-section">
            <div class="table-header">
                <h2><i class="fas fa-list"></i> 发票记录</h2>
                <div class="table-info">
                    <span id="tableCount">共 0 条记录</span>
                </div>
                <div class="table-actions">
                    <button class="btn btn-outline" id="exportBtn">
                        <i class="fas fa-download"></i> 导出
                    </button>
                </div>
            </div>
            
            <div class="table-container">
                <table id="invoiceTable">
                    <thead>
                        <tr>
                            <th>发票代码</th>
                            <th>发票号码</th>
                            <th>开票日期</th>
                            <th>发票金额</th>
                            <th>使用人</th>
                            <th>凭证号</th>
                            <th>录入人</th>
                            <th>录入时间</th>
                            <th>状态</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody id="invoiceTableBody">
                        <!-- 数据将通过JS动态加载 -->
                    </tbody>
                </table>
                
                <!-- 分页控件 -->
                <div class="pagination-container" id="paginationContainer" style="display: none;">
                    <div class="pagination-info">
                        <span id="paginationInfo">显示第 1-5 条，共 0 条记录</span>
                    </div>
                    <div class="pagination-controls">
                        <button class="btn btn-outline btn-sm" id="firstPageBtn" disabled>
                            <i class="fas fa-angle-double-left"></i> 首页
                        </button>
                        <button class="btn btn-outline btn-sm" id="prevPageBtn" disabled>
                            <i class="fas fa-angle-left"></i> 上一页
                        </button>
                        <span class="page-numbers" id="pageNumbers">
                            <!-- 页码将动态生成 -->
                        </span>
                        <button class="btn btn-outline btn-sm" id="nextPageBtn" disabled>
                            下一页 <i class="fas fa-angle-right"></i>
                        </button>
                        <button class="btn btn-outline btn-sm" id="lastPageBtn" disabled>
                            末页 <i class="fas fa-angle-double-right"></i>
                        </button>
                    </div>
                    <div class="page-size-selector">
                        <label for="pageSizeSelect">每页显示：</label>
                        <select id="pageSizeSelect" class="form-control-sm">
                            <option value="5" selected>5条</option>
                            <option value="10">10条</option>
                            <option value="20">20条</option>
                            <option value="50">50条</option>
                        </select>
                    </div>
                </div>
                
                <!-- 空状态 -->
                <div class="empty-state" id="emptyState">
                    <i class="fas fa-receipt"></i>
                    <p>暂无发票数据，点击"新增发票"开始录入</p>
                </div>
                
                <!-- 加载状态 -->
                <div class="loading" id="loadingTable">
                    <div class="spinner"></div>
                    <p>正在加载数据...</p>
                </div>
            </div>
        </section>
    </div>

    <!-- 新增发票弹窗 -->
    <div class="modal" id="addInvoiceModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-plus-circle"></i> 新增发票</h2>
                <button class="close-modal" id="closeModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="invoiceForm">
                    <div class="form-group">
                        <label for="qrCode">发票二维码 <span class="required">*</span></label>
                        <textarea id="qrCode" class="form-control" rows="4" placeholder="请扫描或输入发票二维码内容"></textarea>
                        <input type="hidden" id="csrf_token" value="<?php echo SecurityConfig::generateCSRFToken(); ?>">
                        <small class="form-text">系统将自动解析二维码内容，提取发票信息</small>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="invUser">凭证使用人 <span class="required">*</span></label>
                            <input type="text" id="invUser" class="form-control" placeholder="输入凭证使用人" required>
                        </div>
                        <div class="form-group">
                            <label for="invDoc">凭证号 <span class="required">*</span></label>
                            <input type="text" id="invDoc" class="form-control" placeholder="输入凭证号" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="invCode">发票代码</label>
                            <input type="text" id="invCode" class="form-control" placeholder="系统自动解析" readonly>
                        </div>
                        <div class="form-group">
                            <label for="invNum">发票号码</label>
                            <input type="text" id="invNum" class="form-control" placeholder="系统自动解析" readonly>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="invDate">开票日期</label>
                            <input type="text" id="invDate" class="form-control" placeholder="系统自动解析" readonly>
                        </div>
                        <div class="form-group">
                            <label for="invMoney">发票金额</label>
                            <input type="text" id="invMoney" class="form-control" placeholder="系统自动解析" readonly>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>录入人</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($realname); ?>" readonly>
                        <small class="form-text">发票将由当前登录用户录入</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" id="cancelBtn">取消</button>
                <button class="btn btn-primary" id="saveBtn">保存发票</button>
            </div>
        </div>
    </div>

    <!-- 修改密码弹窗 -->
    <div class="modal" id="changePasswordModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-key"></i> 修改密码</h2>
                <button class="close-modal" id="closePasswordModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="passwordForm">
                    <div class="form-group">
                        <label for="currentPassword">当前密码 <span class="required">*</span></label>
                        <input type="password" id="currentPassword" class="form-control" placeholder="请输入当前密码" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="newPassword">新密码 <span class="required">*</span></label>
                        <input type="password" id="newPassword" class="form-control" placeholder="请输入新密码" required>
                        <small class="form-text">密码长度至少6位</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirmPassword">确认新密码 <span class="required">*</span></label>
                        <input type="password" id="confirmPassword" class="form-control" placeholder="请再次输入新密码" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" id="cancelPasswordBtn">取消</button>
                <button class="btn btn-primary" id="savePasswordBtn">修改密码</button>
            </div>
        </div>
    </div>

    <!-- 发票详情弹窗 -->
    <div class="modal" id="invoiceDetailModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-eye"></i> 发票详情</h2>
                <button class="close-modal" id="closeDetailModal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="detail-container">
                    <div class="detail-section">
                        <h3><i class="fas fa-receipt"></i> 发票基本信息</h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label>发票代码：</label>
                                <span id="detailInvCode">-</span>
                            </div>
                            <div class="detail-item">
                                <label>发票号码：</label>
                                <span id="detailInvNum">-</span>
                            </div>
                            <div class="detail-item">
                                <label>开票日期：</label>
                                <span id="detailInvDate">-</span>
                            </div>
                            <div class="detail-item">
                                <label>发票金额：</label>
                                <span id="detailInvMoney">-</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <h3><i class="fas fa-file-alt"></i> 凭证信息</h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label>凭证使用人：</label>
                                <span id="detailInvUser">-</span>
                            </div>
                            <div class="detail-item">
                                <label>凭证号：</label>
                                <span id="detailInvDoc">-</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <h3><i class="fas fa-user"></i> 录入信息</h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label>录入人：</label>
                                <span id="detailCreator">-</span>
                            </div>
                            <div class="detail-item">
                                <label>录入时间：</label>
                                <span id="detailCreateTime">-</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <h3><i class="fas fa-qrcode"></i> 二维码内容</h3>
                        <div class="qr-content">
                            <textarea id="detailQRCode" class="form-control" rows="4" readonly></textarea>
                            <button class="btn btn-outline btn-sm" onclick="invoiceManager.copyQRCode()">
                                <i class="fas fa-copy"></i> 复制二维码
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" id="closeDetailBtn">关闭</button>
                <button class="btn btn-primary" id="editDetailBtn" style="display: none;">
                    <i class="fas fa-edit"></i> 编辑
                </button>
            </div>
        </div>
    </div>

    <!-- 用户管理弹窗 -->
    <div class="modal" id="userManageModal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h2><i class="fas fa-users"></i> 用户管理</h2>
                <button class="close-modal" id="closeUserManageModal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="user-manage-header">
                    <div class="user-manage-actions">
                        <button class="btn btn-primary" id="addUserBtn">
                            <i class="fas fa-user-plus"></i> 新增用户
                        </button>
                    </div>
                </div>
                
                <div class="user-table-container">
                    <table id="userTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>用户名</th>
                                <th>真实姓名</th>
                                <th>角色</th>
                                <th>状态</th>
                                <th>创建时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody id="userTableBody">
                            <!-- 用户数据将动态加载 -->
                        </tbody>
                    </table>
                    
                    <div class="user-loading" id="userLoading" style="display: none;">
                        <div class="spinner"></div>
                        <p>正在加载用户数据...</p>
                    </div>
                    
                    <div class="user-empty" id="userEmpty" style="display: none;">
                        <i class="fas fa-users"></i>
                        <p>暂无用户数据</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" id="closeUserManageBtn">关闭</button>
            </div>
        </div>
    </div>

    <!-- 新增/编辑用户弹窗 -->
    <div class="modal" id="userFormModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="userFormTitle"><i class="fas fa-user-plus"></i> 新增用户</h2>
                <button class="close-modal" id="closeUserFormModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="userForm">
                    <div class="form-group">
                        <label for="userUsername">用户名 <span class="required">*</span></label>
                        <input type="text" id="userUsername" class="form-control" placeholder="请输入用户名" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="userRealname">真实姓名 <span class="required">*</span></label>
                        <input type="text" id="userRealname" class="form-control" placeholder="请输入真实姓名" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="userPassword">密码 <span class="required" id="passwordRequired">*</span></label>
                        <input type="password" id="userPassword" class="form-control" placeholder="请输入密码" required>
                        <small class="form-text">密码长度至少6位</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="userRole">角色</label>
                        <select id="userRole" class="form-control">
                            <option value="user">普通用户</option>
                            <option value="admin">管理员</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" id="cancelUserFormBtn">取消</button>
                <button class="btn btn-primary" id="saveUserBtn">保存</button>
            </div>
        </div>
    </div>

    <!-- 编辑发票弹窗 -->
    <div class="modal" id="editInvoiceModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> 编辑发票</h2>
                <button class="close-modal" id="closeEditModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editInvoiceForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label>发票代码</label>
                            <input type="text" id="editInvCode" class="form-control" readonly>
                        </div>
                        <div class="form-group">
                            <label>发票号码</label>
                            <input type="text" id="editInvNum" class="form-control" readonly>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>开票日期</label>
                            <input type="text" id="editInvDate" class="form-control" readonly>
                        </div>
                        <div class="form-group">
                            <label>发票金额</label>
                            <input type="text" id="editInvMoney" class="form-control" readonly>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editInvUser">凭证使用人 <span class="required">*</span></label>
                            <input type="text" id="editInvUser" class="form-control" placeholder="输入凭证使用人" required>
                        </div>
                        <div class="form-group">
                            <label for="editInvDoc">凭证号 <span class="required">*</span></label>
                            <input type="text" id="editInvDoc" class="form-control" placeholder="输入凭证号" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>录入人</label>
                        <input type="text" id="editCreator" class="form-control" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>录入时间</label>
                        <input type="text" id="editCreateTime" class="form-control" readonly>
                    </div>
                    
                    <input type="hidden" id="editInvoiceId">
                    <input type="hidden" id="editCsrfToken" value="<?php echo SecurityConfig::generateCSRFToken(); ?>">
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" id="cancelEditBtn">取消</button>
                <button class="btn btn-primary" id="saveEditBtn">保存修改</button>
            </div>
        </div>
    </div>

    <!-- 修改用户密码弹窗 -->
    <div class="modal" id="changeUserPasswordModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-key"></i> 修改用户密码</h2>
                <button class="close-modal" id="closeChangeUserPasswordModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="changeUserPasswordForm">
                    <div class="form-group">
                        <label>用户</label>
                        <input type="text" id="changePasswordUsername" class="form-control" readonly>
                        <input type="hidden" id="changePasswordUserId">
                    </div>
                    
                    <div class="form-group">
                        <label for="newUserPassword">新密码 <span class="required">*</span></label>
                        <input type="password" id="newUserPassword" class="form-control" placeholder="请输入新密码" required>
                        <small class="form-text">密码长度至少6位</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirmUserPassword">确认新密码 <span class="required">*</span></label>
                        <input type="password" id="confirmUserPassword" class="form-control" placeholder="请再次输入新密码" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" id="cancelChangePasswordBtn">取消</button>
                <button class="btn btn-primary" id="saveChangePasswordBtn">修改密码</button>
            </div>
        </div>
    </div>

    <!-- 系统日志弹窗 -->
    <div class="modal" id="systemLogModal">
        <div class="modal-content" style="max-width: 1200px;">
            <div class="modal-header">
                <h2><i class="fas fa-list-alt"></i> 系统日志</h2>
                <button class="close-modal" id="closeSystemLogModal">&times;</button>
            </div>
            <div class="modal-body">
                <!-- 统计信息 -->
                <div class="log-stats">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-sign-in-alt"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number" id="todayLogins">0</div>
                            <div class="stat-label">今日登录</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-week"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number" id="weekLogins">0</div>
                            <div class="stat-label">本周登录</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number" id="monthLogins">0</div>
                            <div class="stat-label">本月登录</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number" id="activeUsers">0</div>
                            <div class="stat-label">活跃用户</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number" id="failedLogins">0</div>
                            <div class="stat-label">失败登录</div>
                        </div>
                    </div>
                </div>
                
                <!-- 搜索过滤器 -->
                <div class="log-filters">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="logAction">操作类型</label>
                            <select id="logAction" class="filter-input">
                                <option value="">全部操作</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="logUsername">用户名</label>
                            <input type="text" id="logUsername" class="filter-input" placeholder="输入用户名">
                        </div>
                        <div class="filter-group">
                            <label for="logIpAddress">IP地址</label>
                            <input type="text" id="logIpAddress" class="filter-input" placeholder="输入IP地址">
                        </div>
                        <div class="filter-group">
                            <label for="logStatus">状态</label>
                            <select id="logStatus" class="filter-input">
                                <option value="">全部状态</option>
                                <option value="1">成功</option>
                                <option value="0">失败</option>
                            </select>
                        </div>
                    </div>
                    <div class="filter-row">
                        <div class="filter-actions">
                            <button class="btn btn-outline" id="resetLogFilterBtn">
                                <i class="fas fa-undo"></i> 重置
                            </button>
                            <button class="btn btn-primary" id="searchLogBtn">
                                <i class="fas fa-search"></i> 查询
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- 日志表格 -->
                <div class="log-table-container">
                    <table id="logTable">
                        <thead>
                            <tr>
                                <th>时间</th>
                                <th>用户</th>
                                <th>操作</th>
                                <th>描述</th>
                                <th>IP地址</th>
                                <th>状态</th>
                                <th>详情</th>
                            </tr>
                        </thead>
                        <tbody id="logTableBody">
                            <!-- 日志数据将动态加载 -->
                        </tbody>
                    </table>
                    
                    <!-- 加载状态 -->
                    <div class="log-loading" id="logLoading" style="display: none;">
                        <div class="spinner"></div>
                        <p>正在加载日志数据...</p>
                    </div>
                    
                    <!-- 空状态 -->
                    <div class="log-empty" id="logEmpty" style="display: none;">
                        <i class="fas fa-list-alt"></i>
                        <p>暂无日志数据</p>
                    </div>
                </div>
                
                <!-- 分页控件 -->
                <div class="pagination-container" id="logPaginationContainer" style="display: none;">
                    <div class="pagination-info">
                        <span id="logPaginationInfo">显示第 1-20 条，共 0 条记录</span>
                    </div>
                    <div class="pagination-controls">
                        <button class="btn btn-outline btn-sm" id="logFirstPageBtn" disabled>
                            <i class="fas fa-angle-double-left"></i> 首页
                        </button>
                        <button class="btn btn-outline btn-sm" id="logPrevPageBtn" disabled>
                            <i class="fas fa-angle-left"></i> 上一页
                        </button>
                        <span class="page-numbers" id="logPageNumbers">
                            <!-- 页码将动态生成 -->
                        </span>
                        <button class="btn btn-outline btn-sm" id="logNextPageBtn" disabled>
                            下一页 <i class="fas fa-angle-right"></i>
                        </button>
                        <button class="btn btn-outline btn-sm" id="logLastPageBtn" disabled>
                            末页 <i class="fas fa-angle-double-right"></i>
                        </button>
                    </div>
                    <div class="page-size-selector">
                        <label for="logPageSizeSelect">每页显示：</label>
                        <select id="logPageSizeSelect" class="form-control-sm">
                            <option value="5" selected>5条</option>
                            <option value="10">10条</option>
                            <option value="20">20条</option>
                            <option value="50">50条</option>
                            <option value="100">100条</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" id="closeSystemLogBtn">关闭</button>
            </div>
        </div>
    </div>

    <!-- 修改用户角色弹窗 -->
    <div class="modal" id="changeUserRoleModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-cog"></i> 修改用户角色</h2>
                <button class="close-modal" id="closeChangeUserRoleModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="changeUserRoleForm">
                    <div class="form-group">
                        <label>用户</label>
                        <input type="text" id="changeRoleUsername" class="form-control" readonly>
                        <input type="hidden" id="changeRoleUserId">
                    </div>
                    
                    <div class="form-group">
                        <label for="userRoleSelect">用户角色 <span class="required">*</span></label>
                        <select id="userRoleSelect" class="form-control" required>
                            <option value="">请选择角色</option>
                            <option value="user">普通用户</option>
                            <option value="admin">管理员</option>
                        </select>
                        <small class="form-text">管理员拥有用户管理权限</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" id="cancelChangeRoleBtn">取消</button>
                <button class="btn btn-primary" id="saveChangeRoleBtn">修改角色</button>
            </div>
        </div>
    </div>

    <!-- 系统配置弹窗 -->
    <div class="modal" id="systemConfigModal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h2><i class="fas fa-cog"></i> 系统配置</h2>
                <button class="close-modal" id="closeSystemConfigModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="systemConfigForm">
                    <input type="hidden" id="configCsrfToken" value="<?php echo SecurityConfig::generateCSRFToken(); ?>">
                    
                    <div class="form-group">
                        <label for="configSiteFavicon">网站标题图标 (favicon.ico) <span class="required">*</span></label>
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <img id="configSiteFaviconPreview" src="" alt="Favicon预览" style="width: 32px; height: 32px; border: 1px solid var(--border); border-radius: 4px; object-fit: contain; display: none;">
                            <div style="flex: 1;">
                                <input type="file" id="configSiteFavicon" class="form-control" accept=".ico,.png,.jpg,.jpeg" style="padding: 5px;">
                                <small class="form-text">支持.ico、.png、.jpg格式，最大500KB</small>
                                <input type="hidden" id="configSiteFaviconPath" value="">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="configLoginLogo">登录页面Logo <span class="required">*</span></label>
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <img id="configLoginLogoPreview" src="" alt="Logo预览" style="max-width: 150px; max-height: 80px; border: 1px solid var(--border); border-radius: 4px; object-fit: contain; display: none;">
                            <div style="flex: 1;">
                                <input type="file" id="configLoginLogo" class="form-control" accept=".png,.jpg,.jpeg,.gif,.svg" style="padding: 5px;">
                                <small class="form-text">支持.png、.jpg、.gif、.svg格式，最大2MB</small>
                                <input type="hidden" id="configLoginLogoPath" value="">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="configLoginTitle">登录页面标题（第一行） <span class="required">*</span></label>
                        <input type="text" id="configLoginTitle" class="form-control" placeholder="例如: 电子发票查重工具" required>
                        <small class="form-text">显示在登录页面logo下方的第一行标题文字</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="configLoginDescription">登录页面描述（第二行） <span class="required">*</span></label>
                        <input type="text" id="configLoginDescription" class="form-control" placeholder="例如: 请登录您的账户" required>
                        <small class="form-text">显示在登录页面标题下方的第二行描述文字</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="configMainLogo">主页面Logo <span class="required">*</span></label>
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <img id="configMainLogoPreview" src="" alt="Logo预览" style="max-width: 150px; max-height: 80px; border: 1px solid var(--border); border-radius: 4px; object-fit: contain; display: none;">
                            <div style="flex: 1;">
                                <input type="file" id="configMainLogo" class="form-control" accept=".png,.jpg,.jpeg,.gif,.svg" style="padding: 5px;">
                                <small class="form-text">支持.png、.jpg、.gif、.svg格式，最大2MB</small>
                                <input type="hidden" id="configMainLogoPath" value="">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="configMainTitleText">主页面标题文字 <span class="required">*</span></label>
                        <input type="text" id="configMainTitleText" class="form-control" placeholder="例如: 电子发票查重工具" required>
                        <small class="form-text">显示在主页面logo右侧的标题文字</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" id="cancelSystemConfigBtn">取消</button>
                <button class="btn btn-primary" id="saveSystemConfigBtn">保存配置</button>
            </div>
        </div>
    </div>

    <!-- 个人中心弹窗 -->
    <div class="modal" id="profileCenterModal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2><i class="fas fa-user"></i> 个人中心</h2>
                <button class="close-modal" id="closeProfileCenterModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="profileCenterForm">
                    <input type="hidden" id="profileCsrfToken" value="<?php echo SecurityConfig::generateCSRFToken(); ?>">
                    
                    <!-- 头像区域 -->
                    <div class="form-group" style="text-align: center;">
                        <label>用户头像</label>
                        <div style="margin: 20px 0;">
                            <img id="profileAvatarPreview" src="image/logo.png" alt="头像预览" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid var(--border);">
                        </div>
                        <input type="file" id="profileAvatarUpload" accept="image/*" style="display: none;">
                        <button type="button" class="btn btn-outline" id="uploadAvatarBtn" style="margin: 0 auto; display: block;">
                            <i class="fas fa-upload"></i> 上传头像
                        </button>
                        <small class="form-text">支持JPG、PNG格式，建议尺寸100x100像素</small>
                    </div>
                    
                    <!-- 用户信息 -->
                    <div class="form-group">
                        <label>用户名</label>
                        <input type="text" id="profileUsername" class="form-control" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="profileRealname">真实姓名 <span class="required">*</span></label>
                        <input type="text" id="profileRealname" class="form-control" placeholder="请输入真实姓名" maxlength="50">
                        <small class="form-text">可以修改您的真实姓名</small>
                    </div>
                    
                    <div class="form-group">
                        <label>角色</label>
                        <input type="text" id="profileRole" class="form-control" readonly>
                    </div>
                    
                    <hr style="margin: 30px 0; border: none; border-top: 1px solid var(--border);">
                    
                    <!-- 修改密码 -->
                    <h3 style="font-size: 16px; margin-bottom: 20px; color: var(--dark);">
                        <i class="fas fa-key"></i> 修改密码
                    </h3>
                    
                    <div class="form-group">
                        <label for="profileCurrentPassword">当前密码 <span class="required">*</span></label>
                        <input type="password" id="profileCurrentPassword" class="form-control" placeholder="请输入当前密码">
                    </div>
                    
                    <div class="form-group">
                        <label for="profileNewPassword">新密码</label>
                        <input type="password" id="profileNewPassword" class="form-control" placeholder="请输入新密码（留空则不修改）">
                        <small class="form-text">密码长度至少6位</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="profileConfirmPassword">确认新密码</label>
                        <input type="password" id="profileConfirmPassword" class="form-control" placeholder="请再次输入新密码">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" id="cancelProfileCenterBtn">取消</button>
                <button class="btn btn-primary" id="saveProfileCenterBtn">保存</button>
            </div>
        </div>
    </div>

    <!-- 消息提示 -->
    <div id="toast" class="toast"></div>

    <script src="js/script.js"></script>
    <script src="js/log_manager.js"></script>
</body>
</html>