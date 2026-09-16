# 个人作业1：Web 前端初探证据模板与软件指导

> 单独提交，不放进网站页面。后续按个人学号姓名补截图并打包。

## 需要的软件

1. 浏览器：Chrome 或 Edge。
2. 开发者工具：浏览器内置 DevTools，不用单独下载；按 `F12` 或右键页面选择“检查”。
3. JQuery：可从官方 CDN 引入：`https://releases.jquery.com/jquery/`。
4. 浏览器插件：Chrome/Edge 自带扩展管理页即可开发，打开 `chrome://extensions/`，开启“开发者模式”，选择“加载已解压的扩展程序”。

## 1. GET / POST 请求调研

推荐直接使用本项目页面完成：

- GET：访问 `http://127.0.0.1:8080/news`，在 DevTools → Network 中截图请求和响应。
- POST：登录后在 `http://127.0.0.1:8080/fan-space` 提交留言，截图 POST 请求、Form Data、响应状态。

要写入文档的内容：

- 请求 URL。
- 请求方法 GET / POST。
- 请求头和响应头截图。
- 参数或 Form Data 截图。
- 响应状态码和页面变化截图。

## 2. JQuery 事件

示例：新建一个 HTML 页面，引入 JQuery，点击按钮后改变文字和颜色。

```html
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<button id="demo">点我</button>
<p id="result">触发前</p>
<script>
  $('#demo').on('click', function () {
    $('#result').text('JQuery 事件已触发').css('color', 'red');
  });
</script>
```

文档里写三句话：事件绑定在按钮上；点击后修改段落文字；截图对比触发前后状态。

## 3. 浏览器插件

建议做一个最简单插件：点击插件按钮后给当前页面加一个世界杯提示条。

文件结构：

```text
worldcup-extension/
  manifest.json
  popup.html
  popup.js
```

加载方法：`chrome://extensions/` → 开发者模式 → 加载已解压的扩展程序 → 选择文件夹。
