# Font Awesome 本地化说明

## 下载步骤

1. 访问 Font Awesome 官网：https://fontawesome.com/download
2. 下载 Font Awesome 6.4.0 的 Web 版本
3. 解压后将以下文件复制到对应位置：
   - `css/all.min.css` → `css/resources/fontawesome/all.min.css`
   - `webfonts/` 目录下的所有字体文件 → `css/resources/fontawesome/webfonts/`

## 或者使用CDN镜像下载

使用以下命令下载Font Awesome 6.4.0文件：

```bash
# 下载CSS文件
curl -o css/resources/fontawesome/all.min.css https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css

# 下载字体文件（需要下载所有woff2文件）
# fa-solid-900.woff2
# fa-regular-400.woff2
# fa-brands-400.woff2
# fa-v4compatibility.woff2
```

## 文件结构

```
css/resources/fontawesome/
├── all.min.css
└── webfonts/
    ├── fa-solid-900.woff2
    ├── fa-regular-400.woff2
    ├── fa-brands-400.woff2
    └── fa-v4compatibility.woff2
```

