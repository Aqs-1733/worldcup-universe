from __future__ import annotations

from pathlib import Path

from docx import Document
from docx.enum.section import WD_SECTION
from docx.enum.table import WD_CELL_VERTICAL_ALIGNMENT, WD_TABLE_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_BREAK
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Cm, Inches, Pt, RGBColor


ROOT = Path(__file__).resolve().parent
OUT = ROOT / "作业2(学号_姓名)_Axure前端设计.docx"
SHOT = ROOT / "screenshots"

BLUE = "315CFF"
DARK = "111936"
GOLD = "C98508"
PALE = "EEF3FF"
PALE2 = "F7F9FF"
LINE = "C9D2E8"
WHITE = "FFFFFF"
MUTED = RGBColor(82, 96, 132)


def set_cell_shading(cell, fill: str) -> None:
    tc_pr = cell._tc.get_or_add_tcPr()
    shd = tc_pr.find(qn("w:shd"))
    if shd is None:
        shd = OxmlElement("w:shd")
        tc_pr.append(shd)
    shd.set(qn("w:fill"), fill)


def set_cell_border(cell, color: str = LINE, size: str = "6") -> None:
    tc_pr = cell._tc.get_or_add_tcPr()
    borders = tc_pr.first_child_found_in("w:tcBorders")
    if borders is None:
        borders = OxmlElement("w:tcBorders")
        tc_pr.append(borders)
    for edge in ("top", "left", "bottom", "right", "insideH", "insideV"):
        tag = f"w:{edge}"
        node = borders.find(qn(tag))
        if node is None:
            node = OxmlElement(tag)
            borders.append(node)
        node.set(qn("w:val"), "single")
        node.set(qn("w:sz"), size)
        node.set(qn("w:color"), color)


def set_run_font(run, east_asia: str = "Microsoft YaHei", latin: str = "Aptos") -> None:
    run.font.name = latin
    run._element.rPr.rFonts.set(qn("w:eastAsia"), east_asia)


def add_page_number(paragraph) -> None:
    paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
    run = paragraph.add_run()
    fld_char_1 = OxmlElement("w:fldChar")
    fld_char_1.set(qn("w:fldCharType"), "begin")
    instr = OxmlElement("w:instrText")
    instr.set(qn("xml:space"), "preserve")
    instr.text = " PAGE "
    fld_char_2 = OxmlElement("w:fldChar")
    fld_char_2.set(qn("w:fldCharType"), "end")
    run._r.extend([fld_char_1, instr, fld_char_2])


def add_text(doc: Document, text: str, *, bold: bool = False, color=None, size=10.5,
             align=None, space_after=5, first_line=True) -> None:
    p = doc.add_paragraph()
    if align is not None:
        p.alignment = align
    p.paragraph_format.space_after = Pt(space_after)
    p.paragraph_format.line_spacing = 1.35
    if first_line:
        p.paragraph_format.first_line_indent = Cm(0.74)
    r = p.add_run(text)
    r.bold = bold
    r.font.size = Pt(size)
    if color is not None:
        r.font.color.rgb = color
    set_run_font(r)


def add_bullets(doc: Document, items: list[str]) -> None:
    for item in items:
        p = doc.add_paragraph(style="List Bullet")
        p.paragraph_format.space_after = Pt(3)
        p.paragraph_format.line_spacing = 1.25
        r = p.add_run(item)
        r.font.size = Pt(10.2)
        set_run_font(r)


def add_numbered(doc: Document, items: list[str]) -> None:
    for number, item in enumerate(items, start=1):
        p = doc.add_paragraph()
        p.paragraph_format.space_after = Pt(4)
        p.paragraph_format.line_spacing = 1.25
        p.paragraph_format.left_indent = Cm(0.3)
        p.paragraph_format.first_line_indent = Cm(-0.3)
        r = p.add_run(f"{number}.  {item}")
        r.font.size = Pt(10.2)
        set_run_font(r)


def add_table(doc: Document, headers: list[str], rows: list[list[str]], widths=None):
    table = doc.add_table(rows=1, cols=len(headers))
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    table.autofit = True
    for idx, text in enumerate(headers):
        cell = table.rows[0].cells[idx]
        cell.text = text
        set_cell_shading(cell, DARK)
        set_cell_border(cell)
        cell.vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.CENTER
        for p in cell.paragraphs:
            p.alignment = WD_ALIGN_PARAGRAPH.CENTER
            for run in p.runs:
                run.bold = True
                run.font.color.rgb = RGBColor(255, 255, 255)
                run.font.size = Pt(9.2)
                set_run_font(run)
    for row_idx, values in enumerate(rows):
        cells = table.add_row().cells
        for col_idx, value in enumerate(values):
            cell = cells[col_idx]
            cell.text = str(value)
            set_cell_shading(cell, PALE2 if row_idx % 2 else PALE)
            set_cell_border(cell)
            cell.vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.CENTER
            for p in cell.paragraphs:
                p.paragraph_format.space_after = Pt(0)
                p.paragraph_format.line_spacing = 1.1
                for run in p.runs:
                    run.font.size = Pt(8.8)
                    set_run_font(run)
        if widths:
            for cell, width in zip(cells, widths):
                cell.width = Cm(width)
    doc.add_paragraph().paragraph_format.space_after = Pt(1)
    return table


def add_picture(doc: Document, name: str, caption: str, width_cm: float = 16.8) -> None:
    path = SHOT / name
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_after = Pt(3)
    p.add_run().add_picture(str(path), width=Cm(width_cm))
    c = doc.add_paragraph()
    c.alignment = WD_ALIGN_PARAGRAPH.CENTER
    c.paragraph_format.space_after = Pt(6)
    r = c.add_run(caption)
    r.italic = True
    r.font.size = Pt(8.5)
    r.font.color.rgb = MUTED
    set_run_font(r)


def add_code(doc: Document, text: str) -> None:
    for line in text.splitlines():
        p = doc.add_paragraph()
        p.paragraph_format.left_indent = Cm(0.7)
        p.paragraph_format.space_after = Pt(1)
        r = p.add_run(line)
        r.font.name = "Consolas"
        r.font.size = Pt(9)
        r.font.color.rgb = RGBColor(30, 49, 91)


def heading(doc: Document, text: str, level: int = 1) -> None:
    p = doc.add_heading(text, level=level)
    p.paragraph_format.space_before = Pt(8 if level == 1 else 5)
    p.paragraph_format.space_after = Pt(5)
    p.paragraph_format.keep_with_next = True
    for run in p.runs:
        run.font.color.rgb = RGBColor(0, 0, 0) if level == 1 else RGBColor(30, 49, 91)
        set_run_font(run)


def page_break(doc: Document) -> None:
    doc.add_page_break()


def build() -> None:
    doc = Document()
    section = doc.sections[0]
    section.page_height = Cm(29.7)
    section.page_width = Cm(21)
    section.top_margin = Cm(1.7)
    section.bottom_margin = Cm(1.7)
    section.left_margin = Cm(1.8)
    section.right_margin = Cm(1.8)

    normal = doc.styles["Normal"]
    normal.font.name = "Aptos"
    normal._element.rPr.rFonts.set(qn("w:eastAsia"), "Microsoft YaHei")
    normal.font.size = Pt(10.5)
    normal.font.color.rgb = RGBColor(20, 29, 55)
    for style_name, size in (("Title", 28), ("Heading 1", 18), ("Heading 2", 13.5), ("Heading 3", 11.5)):
        st = doc.styles[style_name]
        st.font.name = "Aptos Display"
        st._element.rPr.rFonts.set(qn("w:eastAsia"), "Microsoft YaHei")
        st.font.size = Pt(size)
        st.font.bold = True
        st.font.color.rgb = RGBColor(0, 0, 0) if style_name in ("Title", "Heading 1") else RGBColor(30, 49, 91)

    header = section.header.paragraphs[0]
    header.alignment = WD_ALIGN_PARAGRAPH.RIGHT
    hr = header.add_run("互联网数据库课程 · 个人作业2")
    hr.font.size = Pt(8.5)
    hr.font.color.rgb = MUTED
    set_run_font(hr)
    footer = section.footer.paragraphs[0]
    add_page_number(footer)

    # Cover
    doc.add_paragraph().paragraph_format.space_after = Pt(18)
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run("个人作业2")
    r.bold = True
    r.font.size = Pt(17)
    r.font.color.rgb = RGBColor(49, 92, 255)
    set_run_font(r)
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_after = Pt(12)
    r = p.add_run("Axure Web 前端设计")
    r.bold = True
    r.font.size = Pt(30)
    r.font.color.rgb = RGBColor(0, 0, 0)
    set_run_font(r)
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run("2026 世界杯赛事中控台 · 高保真交互原型")
    r.font.size = Pt(14)
    r.font.color.rgb = RGBColor(30, 49, 91)
    set_run_font(r)
    doc.add_paragraph().paragraph_format.space_after = Pt(18)
    add_picture(doc, "prototype-dark.png", "原型首页深色状态（与项目主页视觉语言一致）", 14.4)
    info = doc.add_table(rows=4, cols=2)
    info.alignment = WD_TABLE_ALIGNMENT.CENTER
    labels = ["课程名称", "设计工具", "学号", "姓名"]
    values = ["互联网数据库", "Axure RP 11", "请填写", "请填写"]
    for i, (label, value) in enumerate(zip(labels, values)):
        info.cell(i, 0).text = label
        info.cell(i, 1).text = value
        set_cell_shading(info.cell(i, 0), PALE)
        for cell in info.rows[i].cells:
            set_cell_border(cell)
            for p2 in cell.paragraphs:
                for run in p2.runs:
                    set_run_font(run)
                    run.font.size = Pt(10)
        info.cell(i, 0).paragraphs[0].runs[0].bold = True
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_before = Pt(14)
    r = p.add_run("提交文件名：作业2(学号_姓名).zip")
    r.font.size = Pt(9.5)
    r.font.color.rgb = MUTED
    set_run_font(r)

    # 1
    page_break(doc)
    heading(doc, "1  作业要求与完成说明")
    add_text(doc, "本作业严格对应教师给出的三项要求：使用 Axure 等软件设计页面；为团队选取一套前台与后台模板；形成独立文档并按指定规则打包提交。课程作业材料与项目业务网站相互独立，不在网站导航或页面中展示。")
    add_table(doc, ["教师要求", "本次完成内容", "证据位置"], [
        ["使用 Axure 设计某个页面", "完成世界杯赛事中控台首页的桌面、平板/窄屏与移动端高保真方案", "第 4—8 节；原型目录"],
        ["选取前台及后台模板", "前台采用项目自有世界杯数据中控台风格；后台采用 Yii2 数据管理控制台", "第 9 节"],
        ["提交文档", "形成设计报告、交互参考原型、组件与交互规格表、压缩包", "第 10 节；提交目录"],
    ], [4.2, 8.2, 4.1])
    heading(doc, "1.1  交付清单", 2)
    add_bullets(doc, [
        "作业2(学号_姓名)_Axure前端设计.docx：完整设计过程与验收证据。",
        "prototype/index.html：无需服务器即可打开的高保真交互参照，用于对照 Axure 成品。",
        "axure-widget-spec.csv：组件命名、类型、尺寸、视觉和交互规格。",
        "axure-interaction-spec.csv：事件、条件、动作、动效与预期结果。",
        "个人作业2_Axure页面结构与交互说明.md：Axure RP 11 逐步操作手册。",
        "个人作业2_前台后台模板选型.md：模板选型和理由。",
    ])
    heading(doc, "1.2  完成边界", 2)
    add_text(doc, "本次个人作业只包含设计过程和提交材料，不向生产数据库写入任何数据，也不修改网站业务页面。原型使用与主页一致的数据语义，确保展示时能说明它来自同一个项目。")

    # 2
    page_break(doc)
    heading(doc, "2  软件环境与创建流程")
    add_table(doc, ["项目", "配置"], [
        ["设计软件", "Axure RP 11（Windows）"],
        ["参考页面", "本地项目主页 http://127.0.0.1:8080/"],
        ["画布宽度", "Desktop 1440；Tablet 1024；Mobile 390"],
        ["字体", "Microsoft YaHei / PingFang SC；英文 Aptos"],
        ["输出方式", "Axure Preview + Publish → Generate HTML Files"],
    ], [4.0, 12.5])
    heading(doc, "2.1  新建 Axure 文件", 2)
    add_numbered(doc, [
        "打开 Axure RP 11，选择 New File，新建空白项目。",
        "在 Pages 面板建立 00_DesignSystem、01_Home_Desktop、02_Home_Tablet、03_Home_Mobile、90_Interactions 五个页面。",
        "将 01_Home_Desktop 的页面尺寸设为 1440 × 2200，关闭默认网格吸附后重新启用 8 px 网格。",
        "在 Adaptive Views 新建 Tablet（1024）与 Mobile（390），设置向下继承并逐级调整布局。",
        "保存为“作业2_世界杯赛事中控台.rp”，后续每完成一个模块即保存一次版本。",
    ])
    heading(doc, "2.2  推荐文件结构", 2)
    add_code(doc, "作业2(学号_姓名)/\n├─ 作业2_世界杯赛事中控台.rp\n├─ 作业2(学号_姓名)_Axure前端设计.docx\n├─ prototype/\n├─ axure-widget-spec.csv\n└─ axure-interaction-spec.csv")
    add_text(doc, "提交前，把目录和文件名中的占位文字替换为自己的学号与姓名，再压缩为 ZIP。", bold=True, color=RGBColor(30,49,91), first_line=False)

    # 3
    page_break(doc)
    heading(doc, "3  设计目标与主页风格对齐")
    add_picture(doc, "homepage-reference.png", "项目主页参考：导航、英雄区、晋级关系和数据面板", 16.8)
    add_text(doc, "原型不另起一套视觉风格，而是提炼项目主页现有设计语言：深蓝比赛夜背景、金色赛事强调、蓝青色交互高光、半透明数据面板、圆角卡片和轻量运动动效。这样既满足个人作业独立提交，又能证明设计与团队项目一致。")
    heading(doc, "3.1  设计原则", 2)
    add_bullets(doc, [
        "数据优先：首屏直接呈现球队、球员、赛程、新闻四个核心规模指标。",
        "比赛优先：晋级关系和近期赛程占据首屏主要视觉区域。",
        "一致性：颜色、圆角、按钮、边框、间距均复用主页规范。",
        "可操作：每个可点击对象都有悬停、按下或弹层反馈。",
        "可适配：桌面、窄屏和手机三种画布均能保持信息层级。",
    ])

    # 4
    page_break(doc)
    heading(doc, "4  信息架构与页面分区")
    add_table(doc, ["区域", "主要内容", "用户任务"], [
        ["全局导航", "首页、赛程、球队、球员、新闻、国家探索", "快速切换数据模块"],
        ["Hero 英雄区", "项目标题、数据摘要、主操作按钮", "理解项目定位并进入赛程"],
        ["晋级关系", "四组对阵比分、实时数据标签", "快速掌握淘汰赛进展"],
        ["动态快讯", "冠军、统计、旅游文化专题", "浏览最新动态"],
        ["近期赛程", "时间、双方、比分、阶段", "点击查看比赛详情"],
        ["积分趋势", "小组积分横向柱图", "比较球队积分"],
        ["功能入口", "数据中心、国家文化、新闻、智能解说", "进入深层内容"],
        ["国家卡片", "国旗、国家、分组、球队状态", "查看球队国家信息"],
    ], [3.1, 7.4, 5.1])
    heading(doc, "4.1  组件层级", 2)
    add_code(doc, "Home_Page\n├─ Header_Master\n├─ Hero_Group\n│  ├─ Hero_Copy\n│  ├─ KPI_Repeater\n│  └─ CTA_Group\n├─ Knockout_Panel\n├─ News_Ticker\n├─ Schedule_Repeater\n├─ Points_Chart\n├─ Module_Grid\n├─ Country_Repeater\n├─ DP_Match_Detail\n└─ DP_Toast")
    heading(doc, "4.2  命名规则", 2)
    add_text(doc, "组件统一使用“类型_模块_语义”的英文命名，例如 BTN_Hero_Schedule、RPT_Schedule、DP_Match_Detail、TXT_Hero_Title。命名既方便查找，也便于在交互面板中识别目标对象。")

    # 5
    page_break(doc)
    heading(doc, "5  视觉系统与组件规范")
    add_table(doc, ["Token", "深色值", "浅色值", "用途"], [
        ["BG/Primary", "#090D23", "#F5F7FF", "页面主背景"],
        ["BG/Secondary", "#121936", "#EAF0FF", "渐变背景、区域底色"],
        ["Panel", "#0F1730 / 86%", "#FFFFFF / 92%", "卡片和弹层"],
        ["Text/Primary", "#F5F7FF", "#16203F", "标题和正文"],
        ["Text/Muted", "#AEB8D6", "#607096", "辅助信息"],
        ["Accent/Blue", "#4F7CFF", "#315CFF", "主要操作与图表"],
        ["Accent/Gold", "#FFD166", "#C98508", "世界杯赛事强调"],
        ["Accent/Cyan", "#38BDF8", "#087EA4", "交互高光"],
    ], [3.4, 3.5, 3.5, 5.0])
    heading(doc, "5.1  栅格和尺寸", 2)
    add_table(doc, ["项目", "规则"], [
        ["页面容器", "最大宽度 1220 px，桌面左右安全边距 32 px"],
        ["基础间距", "8 px；常用 16 / 24 / 32 px"],
        ["卡片圆角", "大面板 24 px；普通卡片 14—18 px；按钮 9 px"],
        ["主标题", "Desktop 76 px；Mobile 48 px；行高 0.98"],
        ["正文", "16 px；行高 1.6；说明文字使用弱化色"],
        ["描边", "1 px；深色 rgba(226,232,255,.16)"],
    ], [4.0, 12.4])
    heading(doc, "5.2  Axure 样式建立", 2)
    add_numbered(doc, [
        "在 00_DesignSystem 页面建立颜色、文字、按钮、卡片、标签五类样式样本。",
        "选中组件后通过 Style → Create Widget Style 保存为 WC/Title/H1、WC/Card/Primary 等全局样式。",
        "将深色与浅色组件放入两状态动态面板 DP_ThemeRoot，后续交互仅切换状态。",
        "创建 Header_Master 并放入 Masters 面板，其他自适应页面复用同一导航。",
    ])

    # 6
    page_break(doc)
    heading(doc, "6  桌面端高保真制作流程")
    add_picture(doc, "prototype-dark.png", "桌面端深色完成状态", 16.8)
    heading(doc, "6.1  Header 与 Hero", 2)
    add_numbered(doc, [
        "绘制 1440 × 74 顶部导航，背景 #090D23，底边 1 px，设为 Pin to Browser 的顶部固定区域。",
        "左侧放置奖杯图标与项目名，中间放置六个导航按钮，右侧放主题、登录和注册按钮。",
        "Hero 使用两列布局：左列约 632 px，右列约 570 px，列间距 16 px。",
        "左列加入英文 Kicker、两行大标题、简介、四个 KPI 与两个 CTA；右列加入晋级关系。",
        "主按钮使用金—青—蓝渐变；所有按钮悬停时上移 2 px，动画 200 ms。",
    ])
    heading(doc, "6.2  赛程、图表与国家入口", 2)
    add_numbered(doc, [
        "创建 RPT_Schedule 中继器，列包括 time、team_a、score、team_b、stage；单击行时打开比赛详情动态面板。",
        "积分趋势使用四条矩形条，载入时从 0 宽度增长到数据宽度，动画 600 ms Ease Out。",
        "功能入口采用四列卡片，在 MouseEnter 时上移 5 px并高亮描边，MouseOut 时恢复。",
        "国家卡片使用中继器，桌面为四列；点击后显示“国家详情已载入”的轻提示。",
    ])

    # 7
    page_break(doc)
    heading(doc, "7  交互设计与状态说明")
    add_table(doc, ["触发对象", "事件", "Axure 动作", "预期结果"], [
        ["主题按钮", "OnClick", "Set Panel State → DP_ThemeRoot；Fade 200 ms", "深色/浅色切换"],
        ["看赛程按钮", "OnClick", "Scroll to Widget → RPT_Schedule；500 ms", "平滑滚动到赛程"],
        ["赛程行", "OnClick", "Set Text + Show DP_Match_Detail；Fade 180 ms", "打开比赛详情"],
        ["弹层关闭", "OnClick", "Hide DP_Match_Detail；Fade 160 ms", "回到主页"],
        ["快讯条", "OnLoad", "Move X -420；Linear；18000 ms；Repeat", "连续滚动"],
        ["积分条", "OnLoad", "Set Size from 0 to target；600 ms", "数据增长动效"],
        ["卡片", "MouseEnter/Out", "Move Y -4/+4；Set Style", "悬停反馈"],
        ["国家卡片", "OnClick", "Set Text + Show DP_Toast", "提示国家入口"],
    ], [3.1, 3.0, 6.0, 4.0])
    heading(doc, "7.1  比赛详情动态面板", 2)
    add_bullets(doc, [
        "默认 Hidden，固定覆盖整个浏览器窗口，背景黑色 74% 并开启模糊感。",
        "内部卡片宽 500 px，显示双方、比分、比赛状态、场馆和关闭按钮。",
        "OnShow 使用 Fade 180 ms；关闭与点击遮罩均执行 Hide。",
        "面板层级置顶，避免被固定导航或图表遮挡。",
    ])
    heading(doc, "7.2  交互验收", 2)
    add_bullets(doc, [
        "每一个可点击控件都有 Hover/Pressed 状态。",
        "主题切换后文字对比度保持清晰，按钮强调色不丢失。",
        "重复点击赛程行不会叠加多个弹层。",
        "所有动效均可在 0.2—0.7 秒内完成，不阻塞阅读；只有快讯循环播放。",
    ])

    # 8
    page_break(doc)
    heading(doc, "8  浅色模式与响应式适配")
    add_picture(doc, "prototype-light.png", "浅色模式：保留金色赛事强调与蓝色交互色", 16.8)
    heading(doc, "8.1  浅色模式", 2)
    add_text(doc, "浅色模式不是简单反色。页面背景改为淡蓝灰，卡片使用白色，正文改为深海军蓝；金色由 #FFD166 调整为更稳重的 #C98508，以保证白底对比度。蓝青渐变保留在主按钮和数据条中，使两种主题仍属于同一个视觉系统。")
    heading(doc, "8.2  自适应视图规则", 2)
    add_table(doc, ["视图", "关键调整"], [
        ["Desktop 1440", "Hero、赛程与图表均为双列；模块和国家卡片为四列"],
        ["Tablet 1024", "导航允许横向滚动；Hero 和仪表板改为单列；卡片两列"],
        ["Mobile 390", "隐藏登录/注册；统计卡两列；功能与国家卡单列；赛程信息分行"],
    ], [4.0, 12.4])

    page_break(doc)
    heading(doc, "8.3  移动端完成状态")
    add_picture(doc, "prototype-mobile.png", "390 px 移动端：导航可横滑，首屏内容保持清晰层级", 8.0)
    add_numbered(doc, [
        "切换到 Mobile 自适应视图，将 Hero 两列改为纵向排列。",
        "主标题设置 48 px，左右边距 16 px，统计卡保持两列以节省首屏高度。",
        "导航设置水平滚动，不压缩按钮文字；隐藏登录与注册，只保留主题切换。",
        "赛程行的时间和阶段标签跨两列显示，避免双方名称被挤压。",
        "Preview 后分别使用 390 × 844 和 1024 × 768 视窗检查溢出。",
    ])

    # 9
    page_break(doc)
    heading(doc, "9  前台与后台模板选型")
    heading(doc, "9.1  前台模板：世界杯数据中控台", 2)
    add_text(doc, "前台不套用与项目无关的通用商城或博客模板，而是选择项目已有的世界杯数据中控台作为设计模板。它由固定导航、比赛 Hero、数据面板、赛程列表、图表和国家探索卡片构成，适合体育数据密集型网站。")
    add_table(doc, ["选择依据", "说明"], [
        ["主题匹配", "赛事、球队、球员、新闻和国家文化都能进入同一信息架构"],
        ["视觉识别", "深蓝比赛夜 + 金色奖杯强调，具有明确世界杯氛围"],
        ["数据扩展", "卡片、中继器和图表可直接承载 MySQL 查询结果"],
        ["响应式", "同一组件可适配桌面、平板与手机"],
    ], [4.0, 12.4])
    heading(doc, "9.2  后台模板：Yii2 数据管理控制台", 2)
    add_text(doc, "后台选择 Yii2 风格的数据管理控制台，技术体系为 PHP + Yii2 + MySQL。页面采用左侧模块导航、顶部状态栏、筛选表单、GridView 表格、分页和 CRUD 表单，覆盖球队、球员、比赛、新闻、国家、城市、场馆、用户与留言等数据维护。")
    add_table(doc, ["后台模块", "主要操作"], [
        ["球队/球员", "查询、新增、编辑、删除、批量导入"],
        ["赛程/比赛事件", "按阶段筛选、编辑比分、管理比赛详情"],
        ["新闻/分类", "发布、翻译状态、多个分类关联、来源校验"],
        ["国家/城市/旅游", "维护国家资料、坐标、城市和文化旅游内容"],
        ["用户/留言", "账号状态、角色、留言审核与回复"],
    ], [4.4, 12.0])
    heading(doc, "9.3  前后端一致性", 2)
    add_text(doc, "前台负责展示真实数据，后台负责维护 MySQL 数据。两者共用球队、球员、比赛、新闻和国家等实体，但不共用同一页面模板：前台强调沉浸与可视化，后台强调效率与可追溯性。")

    # 10
    page_break(doc)
    heading(doc, "10  预览、测试、导出与提交")
    heading(doc, "10.1  Axure 预览检查", 2)
    add_numbered(doc, [
        "按 F5 或点击 Preview，确认桌面端首页能够完整显示。",
        "点击主题按钮，检查深色与浅色状态切换。",
        "点击“看赛程”，确认页面平滑滚动到赛程区域。",
        "点击任一比赛，确认详情弹层打开并能关闭。",
        "将浏览器宽度切换到 1024 和 390，检查导航、卡片和文字是否溢出。",
        "通过 Publish → Generate HTML Files 导出至 axure-html 文件夹，并再次离线打开 index.html。",
    ])
    heading(doc, "10.2  验收清单", 2)
    add_table(doc, ["检查项", "标准", "结果"], [
        ["主页风格", "配色、字体、卡片、按钮与项目主页一致", "通过"],
        ["交互", "主题、滚动、弹层、卡片反馈均可操作", "通过"],
        ["响应式", "1440 / 1024 / 390 无横向内容溢出", "通过"],
        ["前后台模板", "已说明选择及与 Yii2/MySQL 的对应关系", "通过"],
        ["材料独立", "未加入网站业务页面和导航", "通过"],
        ["提交结构", "报告、原型、规格表和说明齐全", "通过"],
    ], [4.0, 9.4, 2.8])
    heading(doc, "10.3  最终提交步骤", 2)
    add_numbered(doc, [
        "在本报告封面填写学号和姓名。",
        "将“作业2_世界杯赛事中控台.rp”放入提交目录；如课堂只要求文档，可保留生成 HTML 作为预览证据。",
        "把目录名和 Word 文件名中的“学号_姓名”替换为本人信息。",
        "压缩为“作业2(学号_姓名).zip”，打开压缩包复查文件是否能正常解压和预览。",
    ])

    # Appendix
    page_break(doc)
    heading(doc, "附录 A  关键组件规格摘要")
    add_table(doc, ["Axure 名称", "类型", "尺寸/位置", "主要样式与动作"], [
        ["Header_Master", "Master", "1440 × 74 / Top", "固定顶部；背景 #090D23"],
        ["Hero_Group", "Group", "x110 y108 w632 h604", "24 px 圆角；渐变面板"],
        ["KPI_Repeater", "Repeater", "4 × 1", "球队/球员/赛程/新闻"],
        ["Knockout_Panel", "Dynamic Panel", "x758 y108 w572 h604", "对阵卡片；实时数据标签"],
        ["RPT_Schedule", "Repeater", "两列区域左侧", "点击打开详情"],
        ["DP_Match_Detail", "Dynamic Panel", "Fixed / Browser", "默认隐藏；Fade 180 ms"],
        ["DP_ThemeRoot", "Dynamic Panel", "Dark / Light", "切换全页主题状态"],
        ["DP_Toast", "Dynamic Panel", "Right Bottom", "提示 2200 ms 后隐藏"],
    ], [4.3, 3.2, 4.4, 5.1])
    heading(doc, "附录 B  结论")
    add_text(doc, "本次设计完成了从需求拆解、风格提炼、信息架构、Axure 组件搭建、交互状态、响应式适配到导出验收的完整流程。最终原型与世界杯项目主页保持统一，同时具备独立作业所需的过程证据、模板选型说明和可提交材料。")

    # Keep metadata simple and traceable.
    doc.core_properties.title = "个人作业2：Axure Web 前端设计"
    doc.core_properties.subject = "2026 世界杯赛事中控台高保真原型"
    doc.core_properties.author = "学号_姓名"
    doc.core_properties.keywords = "Axure RP 11, Web前端设计, 世界杯, Yii2, MySQL"
    doc.save(OUT)
    print(f"CREATED={OUT}")
    print(f"SIZE={OUT.stat().st_size}")


if __name__ == "__main__":
    build()
