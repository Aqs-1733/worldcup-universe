# 首页通道 Hero · 本地审查版

日期：2026-10-22。基线：`origin/codex/yii2-full-stack` 的 `3230fc82ed73cad976552da1d86002ae712a92d9`，已 fetch 确认；继承 `D:/internet/worldcup-teammate-preview` 中完整的 V2 未提交工作。没有提交、合并或推送。

## 预览与回退

- 新 worktree：`D:/internet/worldcup-hero-preview`，分支 `codex/home-tunnel-hero`，http://127.0.0.1:8082/ 。
- 原 worktree：`D:/internet/worldcup-teammate-preview`，分支 `codex/v2-visual-refinement`，http://127.0.0.1:8081/ 。原工作区未修改，切回该地址即可比较上一版。
- `D:/internet/worldcup-hero-evidence/committed-baseline.zip`：3230fc8 完整已跟踪源码。
- `D:/internet/worldcup-hero-evidence/current-source/`：本次开始前所有已修改和未跟踪源码，保留相对目录。与基线解压到新的空目录后覆盖此目录，即可恢复原 V2 源码。运行依赖及 .env 仍保留在原 worktree，不在源码归档中。
- 只回退本次 Hero：从 current-source 恢复 `config/presentation.php`、`views/site/index.php`、`views/layouts/main.php`、`public/assets/css/edition.css`、`public/assets/js/edition.js`；新增 home-hero.css/js 与图片将不再被引用。

## 本次改动范围

- `views/site/index.php`：只替换首个 Hero section，第二屏真实赛事数据和之后的模块保持原样。
- `views/layouts/main.php`：首页 body 标记及首页专用 CSS/JS 条件加载；其他路由不加载这套规则。
- `config/presentation.php`：home 图片、来源、标题、副标题、入口、桌面/手机裁切、遮罩及淡入时长；主推球员配置未变。
- `public/assets/css/home-hero.css`：通道 Hero 唯一样式入口，首页导航精简；桌面文案在左侧暗墙，手机完整人物位于文案上方。
- `public/assets/js/home-hero.js`：10px 淡入、滚动导航色、继续探索。原生链接无 JS 也可用，减少动态效果设置关闭动画和顺滑滚动。
- `public/assets/css/edition.css` 与 `public/assets/js/edition.js`：移除退役 Hero 专属规则，保留共享按钮及球员规则。
- `public/assets/media/photos/tunnel-hero.png`：参考图原始资源，本地加载，883341 bytes，1408×768；没有放大附图截图或合成人物。

## CSS 覆盖关系

原 Bootstrap → tokens/site → Arena/Tabler/Multiverse → matchday → refinement/topic → edition 继续服务已有页面。首页在 edition 后条件加载 home-hero.css。Hero 改用唯一 `.tunnel-hero` 命名空间；导航覆盖限定 `.home-tunnel`，并清除 edition 中旧 `.edition-hero` 规则。没有改动其他历史样式文件。

## 素材事实与限制

参考原图对应来源：[Beira do Campo](https://beiradocampo.com.br/radar-transferencias-cebolinha-caio-henrique-bidon-2026)。原始资源：

https://pub-b064ffca19cd4d36a0ab9ad642dfe6fd.r2.dev/articles/radar-transferencias-cebolinha-caio-henrique-bidon-2026.png

SHA256：`9C25DDC8D01152766AD9D0EC0ABE899C15C427972EDAC3B3261E27DB979EFFC3`。

用户随后明确本项目用于个人学校作业，允许使用参考图来源素材。图片在页面保留来源链接；这不代表已获得站外公开发布许可。来源没有摄影师/相机/拍摄事件信息，因此**真实性待确认，不能声称已验证真实摄影**。其主体与用户参考图一致；没有移除原图内水印（下载的原图本身没有截图里的站点徽标）。素材分辨率适合本次 1440px 验收，不是 4K 摄影资源。

## 运行和视觉验收

- Chrome 实际渲染 1440×900、390×844，逐张查看截图；人物和出口未被标题覆盖，手机保留完整剪影，左右通道墙因竖屏裁切减少。
- 1440、768、390、320 宽度：无横向溢出；首页 active 唯一；主图加载；移动菜单；赛事、球队、登录导航；继续探索滚动和键盘焦点；减少动态效果；零 pageerror。
- 普通动画结束后内容 opacity=1；禁用 JS 后标题仍可见。
- `/players` 关键标题字体、尺寸、行高、颜色与原 8081 工作区一致；其他模块未重新设计。
- PHP 三个修改文件语法检查、JS 语法检查、git diff --check 通过。
- 预览读取同一隔离预览数据库 `worldcup_teammate_preview_20261022`，没有修改业务数据或执行迁移。

## 截图

全部在 `D:/internet/worldcup-hero-evidence/`：

- `before-home-1440.png` / `before-home-390.png`：上一轮改造前历史首页。
- `before-1440.png` / `before-390.png`：本次开始前实际截图（上一版 V2）。
- `after-1440.png` / `after-390.png`：本次通道 Hero 最终首屏。
- `mobile-menu.png`：移动菜单展开。
- `navigation-results.json`：四个宽度交互检查结果。

本版本的构图与参考图明显更接近，保留为独立可审查候选版本；没有覆盖上一版服务。
