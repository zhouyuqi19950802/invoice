# 电子发票查重工具

一个基于 PHP + MySQL 的电子发票管理系统，支持发票二维码扫描、查重、查询和管理功能。

在线体验：https://inv.zhouyuqi.com.cn
账号：zhouyuqi
密码：12345678

## 功能特性

### 核心功能
- ✅ **发票管理**
  - 支持扫描或手动输入发票二维码
  - 自动解析二维码提取发票信息（发票代码、发票号码、开票日期、发票金额）
  - 发票查重功能，防止重复录入
  - 发票查询（支持按发票号码、使用人、录入人、录入日期筛选）
  - 发票编辑和删除
  - 发票详情查看
  - 数据导出功能

- ✅ **用户认证系统**
  - 安全的用户登录/登出
  - 密码加密存储（使用 PHP password_hash）
  - 登录速率限制（防止暴力破解）
  - Session 安全配置
  - CSRF 防护

- ✅ **用户管理**（管理员功能）
  - 用户列表查看
  - 新增用户
  - 删除用户
  - 修改用户角色（管理员/普通用户）
  - 修改用户状态（启用/禁用）
  - 重置用户密码

- ✅ **个人中心**
  - 修改个人信息（真实姓名）
  - 上传和修改头像
  - 修改密码

- ✅ **系统日志**（管理员功能）
  - 记录所有用户操作（登录、登出、发票操作、用户管理等）
  - 日志查询和筛选（按操作类型、用户、IP地址、状态等）
  - 统计信息（今日登录、本周登录、本月登录、活跃用户、失败登录等）
  - 分页显示

- ✅ **系统配置**（管理员功能）
  - 配置网站标题图标（favicon）
  - 配置登录页面 Logo 和标题
  - 配置主页面 Logo 和标题文字

### 安全特性
- 🔒 **CSRF 防护**：所有表单提交都包含 CSRF Token 验证
- 🔒 **XSS 防护**：输入数据自动转义和过滤
- 🔒 **SQL 注入防护**：使用 PDO 预处理语句
- 🔒 **登录安全**：登录速率限制、Session 劫持防护
- 🔒 **文件上传安全**：文件类型验证、MIME 类型检查、文件大小限制

## 技术栈

- **后端**：PHP 7.4+
- **数据库**：MySQL 5.7+ / MariaDB 10.3+
- **前端**：HTML5 + CSS3 + JavaScript (原生)
- **图标库**：Font Awesome

## 系统要求

- PHP 7.4 或更高版本
- MySQL 5.7 或更高版本（或 MariaDB 10.3+）
- Apache/Nginx Web 服务器
- PHP 扩展：
  - PDO
  - PDO_MySQL
  - GD（用于图片处理）
  - fileinfo（用于文件类型验证）

## 安装指南

### 1. 下载项目

将项目文件上传到您的 Web 服务器目录（如 `htdocs`、`www` 或 `public_html`）。

### 2. 设置文件权限

确保以下目录具有写入权限：
- `uploads/avatars/` - 用户头像上传目录
- `install/` - 安装程序目录（用于创建配置文件）

```bash
chmod -R 755 uploads/
chmod -R 755 install/
```

### 3. 运行安装程序

1. 在浏览器中访问：`http://your-domain.com/invoice/install/`
2. 按照安装向导填写以下信息：
   - **数据库配置**
     - 数据库主机（默认：127.0.0.1）
     - 端口（默认：3306）
     - 数据库名称（默认：invoice）
     - 数据库用户名
     - 数据库密码
   - **管理员账户**
     - 管理员用户名
     - 管理员真实姓名
     - 管理员密码（至少6位）
     - 确认密码

3. 点击"开始安装"，系统将自动：
   - 创建数据库（如果不存在）
   - 创建数据表（users、invoice_info、system_logs、system_config）
   - 创建管理员账户
   - 生成配置文件（`install/.env`）
   - 创建安装锁定文件

### 4. 完成安装

安装完成后，系统会自动跳转到登录页面，使用刚才创建的管理员账户登录即可。

> **注意**：安装完成后，安装程序会被自动禁用。如需重新安装，请删除 `install/.installed` 文件。

## 使用说明

### 登录系统

1. 访问登录页面：`http://your-domain.com/invoice/login.php`
2. 输入用户名和密码
3. 点击"登录"按钮

### 新增发票

1. 点击"新增发票"按钮
2. 在弹窗中：
   - 扫描或手动输入发票二维码（系统会自动解析）
   - 填写凭证使用人（必填）
   - 填写凭证号（必填）
3. 点击"保存发票"按钮

### 查询发票

在搜索区域可以按以下条件查询：
- 发票号码
- 发票使用人
- 录入人
- 录入日期范围

点击"查询"按钮执行搜索，点击"重置"按钮清空搜索条件。

### 编辑发票

1. 在发票列表中点击"编辑"按钮
2. 修改凭证使用人或凭证号
3. 点击"保存修改"按钮

> **注意**：发票代码、发票号码、开票日期、发票金额等由二维码解析得出，不可修改。

### 删除发票

在发票列表中点击"删除"按钮，确认后即可删除。

### 导出数据

点击"导出"按钮，系统会将当前查询结果导出为 CSV 格式文件。

### 用户管理（管理员）

1. 点击"用户管理"按钮
2. 可以执行以下操作：
   - 新增用户
   - 编辑用户信息
   - 修改用户角色
   - 修改用户状态（启用/禁用）
   - 重置用户密码
   - 删除用户

### 系统日志（管理员）

1. 点击"系统日志"按钮
2. 查看系统操作日志
3. 可以按操作类型、用户、IP地址、状态等条件筛选
4. 查看统计信息（今日登录、本周登录等）

### 系统配置（管理员）

1. 点击"系统配置"按钮
2. 可以配置：
   - 网站标题图标（favicon.ico）
   - 登录页面 Logo
   - 登录页面标题和描述
   - 主页面 Logo
   - 主页面标题文字

### 个人中心

1. 点击右上角用户头像，选择"个人中心"
2. 可以：
   - 修改真实姓名
   - 上传/更换头像
   - 修改密码

## 数据库结构

### users 表（用户表）
- `F_id` - 用户ID（主键）
- `F_username` - 用户名（唯一）
- `F_password` - 密码（加密）
- `F_realname` - 真实姓名
- `F_avatar` - 头像路径
- `F_role` - 角色（admin/user）
- `F_status` - 状态（1-启用，0-禁用）
- `F_create_time` - 创建时间
- `F_update_time` - 更新时间

### invoice_info 表（发票信息表）
- `F_Id` - 发票ID（主键）
- `F_inv_code` - 发票代码
- `F_inv_num` - 发票号码
- `F_inv_date` - 开票日期
- `F_inv_money` - 发票金额
- `F_inv_user` - 凭证使用人
- `F_inv_doc` - 凭证号
- `F_inv_qr` - 发票二维码内容
- `F_creator_id` - 录入人ID（外键）
- `F_CreatorTime` - 录入时间

### system_logs 表（系统日志表）
- `F_id` - 日志ID（主键）
- `F_user_id` - 用户ID
- `F_username` - 用户名
- `F_action` - 操作类型
- `F_description` - 操作描述
- `F_ip_address` - IP地址
- `F_user_agent` - 用户代理
- `F_target_type` - 目标类型
- `F_target_id` - 目标ID
- `F_status` - 状态（1-成功，0-失败）
- `F_error_message` - 错误信息
- `F_create_time` - 创建时间

### system_config 表（系统配置表）
- `F_key` - 配置键（主键）
- `F_value` - 配置值
- `F_description` - 配置描述
- `F_update_time` - 更新时间

## 配置文件

系统配置文件位于 `install/.env`，包含数据库连接信息：

```env
DB_HOST=127.0.0.1:3306
DB_NAME=invoice
DB_USERNAME=root
DB_PASSWORD=your_password
```

> **安全提示**：请妥善保管 `.env` 文件，不要将其提交到版本控制系统。

## 安全建议

1. **生产环境部署**：
   - 删除或重命名 `install/` 目录
   - 确保 `.env` 文件权限设置为 600（仅所有者可读写）
   - 启用 HTTPS
   - 定期备份数据库

2. **密码安全**：
   - 使用强密码（至少8位，包含字母、数字和特殊字符）
   - 定期更换密码
   - 不要使用默认密码

3. **服务器安全**：
   - 保持 PHP 和 MySQL 版本更新
   - 配置防火墙规则
   - 限制数据库访问权限

## 常见问题

### Q: 安装时提示数据库连接失败？
A: 请检查：
- 数据库服务是否启动
- 数据库主机、端口、用户名、密码是否正确
- 数据库用户是否有创建数据库的权限

### Q: 无法上传头像？
A: 请检查：
- `uploads/avatars/` 目录是否存在
- 目录权限是否设置为 755 或 777
- PHP 的 `upload_max_filesize` 和 `post_max_size` 配置是否足够

### Q: 登录后自动退出？
A: 可能是 Session 配置问题，请检查：
- PHP 的 `session.save_path` 是否可写
- 服务器时间是否正确
- 是否使用了反向代理（系统已支持）

### Q: 如何重置管理员密码？
A: 可以通过以下方式：
1. 使用数据库管理工具直接修改 `users` 表中的密码（使用 `password_hash('新密码', PASSWORD_DEFAULT)` 生成）
2. 删除 `install/.installed` 文件，重新运行安装程序

## 项目截图
<img width="2560" height="1440" alt="64350e52c060d32f6562b08b51974e5c" src="https://github.com/user-attachments/assets/7dd5b6d7-fc7c-4b4d-b431-df923371cdc2" />

<img width="2560" height="1440" alt="29bd2e2b0cc5e96c189510a59568cad3" src="https://github.com/user-attachments/assets/8a084d8d-62b0-471b-8738-14158931d346" />

<img width="2560" height="1440" alt="7d4415147fd686f4b170a03cfbe98c17" src="https://github.com/user-attachments/assets/809cfd82-52c6-4af5-9cae-fbf70a5eb88a" />

<img width="2560" height="1440" alt="52a36b04c9e8db8b7fe59dd8e7bfe7b2" src="https://github.com/user-attachments/assets/e94fae6f-d7d3-4eb7-9ae4-63b3c63b9cf9" />

<img width="2560" height="1440" alt="00e056647043aec2dcfb85b41eee0efc" src="https://github.com/user-attachments/assets/5c8fd598-d2ae-4d6c-9a63-6f2b20eb18fb" />







## 许可证

本项目采用 MIT 许可证。

## 技术支持

如有问题或建议，请提交 Issue 或联系开发者。

---

**注意**：本系统仅供学习和内部使用，请勿用于商业用途。使用本系统时，请遵守相关法律法规。

