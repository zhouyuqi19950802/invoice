# 电子发票查重工具

一个基于 PHP + MySQL 开发的电子发票管理系统，支持发票二维码扫描、重复检测、数据导出等功能。

## 📋 目录

- [功能特性](#功能特性)
- [技术栈](#技术栈)
- [系统要求](#系统要求)
- [安装指南](#安装指南)
- [使用说明](#使用说明)
- [目录结构](#目录结构)
- [安全特性](#安全特性)
- [常见问题](#常见问题)
- [更新日志](#更新日志)
- [许可证](#许可证)

## ✨ 功能特性

### 核心功能

- **发票管理**
  - 支持通过二维码扫描录入发票信息
  - 自动解析发票代码、发票号码、开票日期、金额等信息
  - 发票重复检测，防止重复录入
  - 发票信息查询、编辑、删除
  - 支持按发票代码、发票号码、使用人、录入人、日期范围等条件筛选
  - 数据导出功能（Excel/CSV格式）

- **用户管理**
  - 多用户系统，支持管理员和普通用户角色
  - 用户信息管理（用户名、真实姓名、头像上传）
  - 用户状态控制（启用/禁用）
  - 密码修改功能

- **系统配置**
  - 网站 Logo 自定义
  - 网站标题图标（Favicon）设置
  - 登录页面标题和描述自定义
  - 主页面标题文字自定义

- **系统日志**
  - 完整的操作日志记录
  - 支持按用户、操作类型、时间范围等条件筛选
  - 日志统计分析（操作统计、用户统计、IP统计等）
  - 管理员可查看所有系统日志

- **安全性**
  - CSRF 令牌防护
  - XSS 攻击防护
  - SQL 注入防护
  - Session 劫持保护
  - 文件上传安全验证
  - 登录速率限制

### 界面特性

- 现代化 UI 设计
- 响应式布局，支持移动端访问
- 实时数据刷新
- 友好的错误提示
- 数据分页显示（默认每页 5 条）

## 🛠 技术栈

### 后端

- **PHP 7.4+**
- **MySQL 5.7+ / MariaDB 10.3+**
- PDO 数据库操作
- Session 管理

### 前端

- 原生 JavaScript (ES6+)
- CSS3 (Flexbox, Grid, Media Queries)
- Font Awesome 图标库

### 服务器

- Apache / Nginx
- 支持反向代理（内网穿透）

## 📦 系统要求

### 最低要求

- PHP 7.4 或更高版本
- MySQL 5.7 或更高版本（或 MariaDB 10.3+）
- Apache 2.4+ 或 Nginx 1.18+
- 启用以下 PHP 扩展：
  - `pdo`
  - `pdo_mysql`
  - `session`
  - `json`
  - `mbstring`
  - `fileinfo`（用于文件上传验证）

### 推荐配置

- PHP 8.0+
- MySQL 8.0+
- 至少 512MB 内存
- HTTPS 支持（生产环境）

## 🚀 安装指南

### 方式一：Web 安装（推荐）

1. **上传文件**
   将项目文件上传到您的 Web 服务器目录（如 `www/invoice`）

2. **访问安装页面**
   在浏览器中访问：
   ```
   http://your-domain.com/invoice/install/
   ```

3. **填写安装信息**
   - 数据库主机和端口（默认：127.0.0.1:3306）
   - 数据库名称
   - 数据库用户名和密码
   - 管理员用户名、密码和真实姓名

4. **完成安装**
   点击"开始安装"按钮，系统将自动：
   - 创建数据库
   - 创建数据表
   - 创建管理员账户
   - 生成配置文件
   - 创建安装锁定文件

5. **安装完成后**
   - 系统会自动跳转到登录页面
   - 安装目录 `install/` 将被保护，无法再次访问

### 方式二：手动安装

1. **创建数据库**
   ```sql
   CREATE DATABASE invoice CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. **配置数据库连接**
   在 `install/.env` 文件中配置数据库信息：
   ```env
   DB_HOST=127.0.0.1:3306
   DB_NAME=invoice
   DB_USERNAME=root
   DB_PASSWORD=your_password
   ```

3. **导入数据库结构**
   访问 `install/index.php` 完成安装，或手动执行 SQL 脚本创建表结构

### 目录权限设置

确保以下目录具有写入权限：
- `install/`（用于创建 `.env` 和 `.installed` 文件）
- `uploads/avatars/`（用于上传用户头像）
- `logs/`（用于记录错误日志）

```bash
chmod 755 install
chmod 755 uploads/avatars
chmod 755 logs
```

### Nginx 配置示例

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/invoice;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    # 保护安装目录
    location ~ ^/install/\.(env|installed)$ {
        deny all;
        return 404;
    }
}
```

### Apache 配置

如果使用 Apache，确保启用 `mod_rewrite` 模块，并确保 `.htaccess` 文件生效。

## 📖 使用说明

### 登录系统

1. 访问 `login.php` 页面
2. 使用安装时创建的管理员账户登录
3. 登录成功后跳转到主页面

### 添加发票

1. 点击"新增发票"按钮
2. 填写以下信息：
   - **二维码内容**：扫描发票二维码获取
   - **凭证使用人**：发票使用人姓名
   - **凭证号**：关联的凭证编号
3. 点击"提交"按钮
4. 系统自动解析二维码并检测重复
5. 如果发票已存在，系统会提示重复信息
6. 如果发票不存在，系统会保存发票信息

### 查询发票

在主页面可以使用以下筛选条件：

- **发票代码**：输入发票代码进行精确搜索
- **发票号码**：输入发票号码进行精确搜索
- **使用人**：输入使用人姓名进行搜索
- **录入人**：选择录入人进行筛选
- **日期范围**：选择开始日期和结束日期
- **重置**：清除所有筛选条件

### 编辑/删除发票

- **编辑**：点击表格中的"编辑"按钮，修改发票信息后保存
- **删除**：点击表格中的"删除"按钮，确认后删除发票记录

### 导出数据

1. 设置筛选条件（可选）
2. 点击"导出数据"按钮
3. 选择导出格式（Excel 或 CSV）
4. 下载导出的文件

### 用户管理（管理员）

1. 点击"用户管理"按钮
2. 可以查看所有用户列表
3. 可以添加新用户
4. 可以编辑用户信息（包括用户名、真实姓名、角色、状态）
5. 可以禁用/启用用户

### 系统配置（管理员）

1. 点击"系统配置"按钮
2. 可以设置以下内容：
   - 网站 Logo（主页面左上角）
   - 网站标题图标（Favicon）
   - 登录页面 Logo
   - 登录页面标题和描述
   - 主页面标题文字
3. 上传文件后点击"保存配置"

### 系统日志（管理员）

1. 点击"系统日志"按钮
2. 可以查看所有系统操作日志
3. 可以按以下条件筛选：
   - 用户名
   - 操作类型
   - 时间范围
   - IP 地址
4. 可以查看日志统计信息

### 个人中心

1. 点击右上角用户名，选择"个人中心"
2. 可以修改真实姓名
3. 可以修改密码
4. 可以上传头像

### 清除缓存

1. 点击右上角用户名，选择"清除缓存"
2. 系统会清除浏览器缓存并刷新页面

## 📁 目录结构

```
invoice/
├── css/                          # 样式文件
│   ├── style.css                # 主样式文件
│   ├── log_styles.css           # 日志页面样式
│   └── resources/               # 资源文件
│       └── fontawesome/         # Font Awesome 图标库
├── error_pages/                 # 错误页面
│   ├── 404.html
│   └── 500.html
├── image/                       # 图片资源
│   ├── bg.jpg                  # 背景图片
│   ├── favicon.ico             # 网站图标
│   └── logo.png                # Logo
├── install/                     # 安装目录
│   ├── index.php               # 安装脚本
│   ├── .env                    # 数据库配置文件（安装后生成）
│   ├── .installed              # 安装锁定文件（安装后生成）
│   └── .htaccess               # 保护敏感文件
├── js/                          # JavaScript 文件
│   ├── script.js               # 主脚本文件
│   └── log_manager.js          # 日志管理脚本
├── logs/                        # 日志目录
│   └── error.log               # 错误日志
├── server/                      # 服务器端 PHP 文件
│   ├── Auth.php                # 认证类
│   ├── config.php              # 数据库配置类
│   ├── ConfigManager.php       # 配置管理类
│   ├── InvoiceProcessor.php    # 发票处理类
│   ├── Logger.php              # 日志记录类
│   ├── SecurityConfig.php      # 安全配置类
│   ├── UserManager.php         # 用户管理类
│   ├── InstallChecker.php      # 安装检查类
│   ├── delete_invoice.php      # 删除发票接口
│   ├── edit_invoice.php        # 编辑发票接口
│   ├── export_invoices.php     # 导出发票接口
│   ├── get_config.php          # 获取配置接口
│   ├── get_invoice_detail.php  # 获取发票详情接口
│   ├── get_invoices.php        # 获取发票列表接口
│   ├── get_log_actions.php     # 获取日志操作类型接口
│   ├── get_log_statistics.php  # 获取日志统计接口
│   ├── get_logs.php            # 获取日志列表接口
│   ├── process_invoice.php     # 处理发票接口
│   ├── save_config.php         # 保存配置接口
│   ├── update_user_realname.php # 更新用户真实姓名接口
│   ├── upload_avatar.php       # 上传头像接口
│   └── upload_system_file.php  # 上传系统文件接口
├── uploads/                     # 上传文件目录
│   └── avatars/                # 用户头像目录
├── index.php                    # 主页面
├── login.php                    # 登录页面
└── README.md                    # 本文档
```

## 🔒 安全特性

### 认证与授权

- 基于 Session 的用户认证
- 角色权限控制（管理员/普通用户）
- 密码使用 `password_hash()` 进行加密存储

### 防护措施

- **CSRF 防护**：所有 POST 请求都包含 CSRF 令牌验证
- **XSS 防护**：所有用户输入都经过 HTML 转义和过滤
- **SQL 注入防护**：使用 PDO 预处理语句，所有参数绑定
- **Session 安全**：
  - Session ID 使用 48 位随机字符串
  - Cookie 设置 HttpOnly 和 Secure（HTTPS）
  - Session 固定攻击防护
  - User-Agent 验证（反向代理环境下已优化）

### 文件上传安全

- 文件类型验证（白名单机制）
- MIME 类型验证
- 文件大小限制（5MB）
- 文件名随机化

### 其他安全措施

- 登录速率限制（防止暴力破解）
- 输入验证和过滤
- 敏感文件保护（`.env`、`.installed` 等）
- 错误信息不暴露敏感信息

## ❓ 常见问题

### 1. 安装时提示"数据库连接失败"

- 检查数据库服务是否已启动
- 确认数据库主机、端口、用户名、密码是否正确
- 确认数据库用户是否有创建数据库的权限

### 2. 登录后页面无限刷新

- 检查 Session 是否正常工作
- 如果使用反向代理，确保正确配置了 `X-Forwarded-For`、`X-Forwarded-Proto` 等头部
- 检查 PHP `session` 扩展是否已启用

### 3. 无法上传文件

- 检查 `uploads/avatars/` 目录是否存在且有写入权限
- 检查 PHP `upload_max_filesize` 和 `post_max_size` 配置
- 检查文件类型是否在允许列表中

### 4. 二维码解析失败

- 确认二维码内容格式正确
- 检查二维码是否完整（没有被截断）
- 查看系统日志了解详细错误信息

### 5. 发票重复检测不准确

- 系统通过发票代码和发票号码组合判断重复
- 确认录入的二维码信息完整正确

### 6. 使用内网穿透后无法登录

参考 Nginx 配置示例，确保正确传递以下头部：
```nginx
proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
proxy_set_header X-Forwarded-Proto $scheme;
proxy_set_header X-Real-IP $remote_addr;
```

### 7. putenv() 函数未定义错误

系统已自动兼容不支持 `putenv()` 的环境。如果仍出现错误，请检查：
- PHP 版本是否为 7.4+
- `SecurityConfig.php` 是否已更新到最新版本

## 📝 更新日志

### v1.0.0 (2025-11)

- ✨ 初始版本发布
- ✅ 发票管理功能
- ✅ 用户管理功能
- ✅ 系统配置功能
- ✅ 系统日志功能
- ✅ Web 安装向导
- ✅ 移动端适配
- ✅ 安全防护机制

## 📄 许可证

本项目采用 MIT 许可证。详见 [LICENSE](LICENSE) 文件（如有）。

## 🤝 贡献

欢迎提交 Issue 和 Pull Request！

## 📧 联系方式

如有问题或建议，请通过以下方式联系：

- 提交 Issue
- 发送邮件

---

**注意**：生产环境部署时，请确保：
1. 使用 HTTPS 协议
2. 修改默认管理员密码
3. 定期备份数据库
4. 定期检查系统日志
5. 保持 PHP 和 MySQL 版本更新


